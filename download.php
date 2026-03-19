<?php
// download.php - Secure file download/view handler
require_once __DIR__ . '/includes/db.php';

$id   = (int)($_GET['id'] ?? 0);
$view = isset($_GET['view']) && $_GET['view'] == '1'; // true = inline viewer, false = force download

if (!$id) { http_response_code(404); exit('Not found'); }

$doc = dbFetch($pdo, "SELECT * FROM documents WHERE id=? AND status='active'", [$id]);
if (!$doc) { http_response_code(404); exit('Document not found'); }

if (!$doc['is_public']) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /pages/login.php');
        exit;
    }
}

$filePath = __DIR__.'/'.$doc['file_path'];
if (!file_exists($filePath)) { http_response_code(404); exit('File not found'); }

// Só incrementa downloads quando for download real (não visualização)
if (!$view) {
    dbExec($pdo, "UPDATE documents SET downloads=downloads+1 WHERE id=?", [$id]);
}

$mimeTypes = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'zip'  => 'application/zip',
];

$ext      = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
$fileName = basename($doc['file_name'] ?: $doc['file_path']);

header('Content-Type: ' . $mimeType);

if ($view && $ext === 'pdf') {
    // Modo visualização inline (para iframe)
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    header('X-Frame-Options: SAMEORIGIN');
    header('Cache-Control: private, max-age=3600');
} else {
    // Modo download forçado
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: no-cache');
}

header('Content-Length: ' . filesize($filePath));
ob_clean();
flush();
readfile($filePath);
exit;