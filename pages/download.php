<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$id   = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'document'; // document | media

if (!$id) { http_response_code(400); exit('Parâmetro inválido.'); }

if ($type === 'document') {
    $file = dbFetch($pdo,"SELECT * FROM documents WHERE id=? AND status='active'",[$id]);
    if (!$file) { http_response_code(404); exit('Documento não encontrado.'); }
    if (!$file['is_public'] && !isLogged()) {
        header('Location: /pages/login.php'); exit;
    }
    // file_path already starts with 'uploads/...' so do NOT add /uploads/ again
    $path = __DIR__.'/../'.$file['file_path'];
    if (!file_exists($path)) { http_response_code(404); exit('Arquivo não encontrado no servidor.'); }
    dbExec($pdo,"UPDATE documents SET downloads=downloads+1 WHERE id=?",[$id]);
    $mimes = ['pdf'=>'application/pdf','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xls'=>'application/vnd.ms-excel','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $mime = $mimes[strtolower($file['file_type'])] ?? 'application/octet-stream';
    $name = $file['file_name'];
} elseif ($type === 'media') {
    $file = dbFetch($pdo,"SELECT * FROM media WHERE id=?",[$id]);
    if (!$file) { http_response_code(404); exit('Arquivo não encontrado.'); }
    // file_path already starts with 'uploads/...' so do NOT add /uploads/ again
    $path = __DIR__.'/../'.$file['file_path'];
    if (!file_exists($path)) { http_response_code(404); exit('Arquivo não encontrado no servidor.'); }
    $mime = $file['mime_type'] ?? 'application/octet-stream';
    $name = $file['file_name'];
} else {
    http_response_code(400); exit('Tipo inválido.');
}

// Serve file
// ?view=1 → inline (visualizador embutido/iframe)  padrão → attachment (download)
$ext2    = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$isView  = isset($_GET['view']) && $_GET['view'] === '1' && $ext2 === 'pdf';
$disp    = $isView ? 'inline' : 'attachment';
header('Content-Type: '.$mime);
header('Content-Disposition: '.$disp.'; filename="'.addslashes($name).'"');
header('Content-Length: '.filesize($path));
header('Cache-Control: '.($isView ? 'public, max-age=3600' : 'private, max-age=0'));
if ($isView) { header('X-Frame-Options: SAMEORIGIN'); }
header('Pragma: public');
ob_clean(); flush();
readfile($path);
exit;