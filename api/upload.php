<?php
/**
 * API: /api/upload.php
 * Secure file upload handler — admin only
 * POST multipart: file, type (image|document|avatar|slider)
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLogged()) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método inválido']);
    exit;
}

$type   = $_POST['type'] ?? 'image';   // image | document | avatar | slider | gallery
$fileKey = 'file';

if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    $errCodes = [1=>'Arquivo muito grande (php.ini)',2=>'Arquivo muito grande (form)',3=>'Upload parcial',4=>'Nenhum arquivo',6=>'Pasta temporária ausente',7=>'Falha ao gravar'];
    $code = $_FILES[$fileKey]['error'] ?? 4;
    echo json_encode(['success'=>false,'message'=>$errCodes[$code] ?? 'Erro desconhecido']);
    exit;
}

$file     = $_FILES[$fileKey];
$origName = pathinfo($file['name'], PATHINFO_FILENAME);
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$mime     = mime_content_type($file['tmp_name']);
$size     = $file['size'];

// Allowed types by category
$allowed = [
    'image'    => ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif','svg'=>'image/svg+xml'],
    'document' => ['pdf'=>'application/pdf','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xls'=>'application/vnd.ms-excel','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','ppt'=>'application/vnd.ms-powerpoint','pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation','txt'=>'text/plain'],
    'avatar'   => ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'],
    'slider'   => ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'],
    'gallery'  => ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'],
    'media'    => ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif','svg'=>'image/svg+xml','pdf'=>'application/pdf','mp4'=>'video/mp4','mp3'=>'audio/mpeg'],
];

$typeMap = ['image'=>'images','document'=>'documents','avatar'=>'avatars','slider'=>'sliders','gallery'=>'gallery','media'=>'media'];
$folder  = $typeMap[$type] ?? 'images';
$maxSize = ($type === 'document') ? 20 * 1024 * 1024 : 5 * 1024 * 1024; // 20MB docs, 5MB images

if (!isset($allowed[$type][$ext])) {
    echo json_encode(['success'=>false,'message'=>"Extensão .$ext não permitida para $type"]);
    exit;
}

// Verify MIME matches extension (security)
$allowedMimes = array_values($allowed[$type]);
if (!in_array($mime, $allowedMimes)) {
    echo json_encode(['success'=>false,'message'=>"Tipo de arquivo inválido: $mime"]);
    exit;
}

if ($size > $maxSize) {
    echo json_encode(['success'=>false,'message'=>'Arquivo muito grande. Máx: '.($maxSize/1024/1024).'MB']);
    exit;
}

// Sanitize filename
$safeName  = preg_replace('/[^a-z0-9_-]/', '', strtolower(iconv('UTF-8','ASCII//TRANSLIT',$origName)));
$safeName  = $safeName ?: 'file';
$filename  = date('Y/m/') . $safeName . '_' . substr(uniqid(), -6) . '.' . $ext;
$uploadDir = __DIR__ . '/../uploads/' . $folder . '/' . date('Y/m/');

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destPath = $uploadDir . $safeName . '_' . substr(uniqid(), -6) . '.' . $ext;
$relPath  = 'uploads/' . $folder . '/' . date('Y/m/') . basename($destPath);

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success'=>false,'message'=>'Falha ao mover arquivo']);
    exit;
}

// For images: get dimensions
$width = $height = null;
if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
    $info = @getimagesize($destPath);
    if ($info) { [$width, $height] = $info; }
}

// Save to media library
$mediaId = null;
try {
    dbExec($pdo,
        "INSERT INTO media (title, file_path, file_name, file_size, file_type, mime_type, width, height, uploaded_by)
         VALUES (?,?,?,?,?,?,?,?,?)",
        [basename($destPath), $relPath, basename($destPath), $size, $ext, $mime, $width, $height, $_SESSION['user_id']]);
    $mediaId = (int)$pdo->lastInsertId();
} catch (Exception $e) { /* media table optional */ }

// For documents (PDF, DOC, etc): also save to documents table
// This allows download.php?id=X&view=1 to serve the file inline
$docId    = null;
$docTitle = $_POST['doc_title'] ?? '';
$pageSlug = trim($_POST['page_slug'] ?? '');
$docCat   = trim($_POST['doc_category'] ?? ($pageSlug ?: 'geral'));
$docType  = trim($_POST['doc_type'] ?? $pageSlug);

if ($type === 'document') {
    // Clean display name: strip hash suffix added by our rename logic
    $displayName = $docTitle ?: preg_replace('/_[a-f0-9]{6,8}(\.[^.]+)$/', '$1', basename($destPath));
    try {
        dbExec($pdo,
            "INSERT INTO documents (title, file_path, file_name, file_size, file_type, category, document_type, is_public, status, uploaded_by)
             VALUES (?,?,?,?,?,?,?,1,'active',?)",
            [$displayName, $relPath, basename($destPath), $size, $ext, $docCat, $docType, $_SESSION['user_id']]);
        $docId = (int)$pdo->lastInsertId();
    } catch (Exception $e) { /* documents table may have constraints */ }
}

// Detect base path dynamically (works at root / or subfolder /crcap/)
$base   = rtrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(__DIR__)), '/');
// Build viewer URL: prefer /download.php?id=X when document saved, else direct file path
// Viewer JS appends ?view=1&t=... at render time
$rawUrl = $docId
    ? $base . '/download.php?id=' . $docId
    : $base . '/' . $relPath;

echo json_encode([
    'success'   => true,
    'url'       => $rawUrl,
    'view_url'  => $rawUrl,      // same as url - ?view=1 added by viewer JS
    'path'      => $relPath,
    'name'      => basename($destPath),
    'size'      => $size,
    'mime'      => $mime,
    'width'     => $width,
    'height'    => $height,
    'media_id'  => $mediaId,
    'doc_id'    => $docId,
]);