<?php
// /crcap/api/end-live.php
error_reporting(0);
@ini_set('display_errors', '0');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
if (ob_get_level()) ob_clean();

if (!isLogged()) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'Sessão expirada. Recarregue a página.']);
    exit;
}
if (!in_array($_SESSION['role'] ?? '', ['admin','editor','author'])) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'Sem permissão para esta ação.']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];
$pid  = (int)($data['post_id'] ?? $_POST['post_id'] ?? $_GET['post_id'] ?? 0);

if (!$pid) {
    echo json_encode(['ok'=>false,'msg'=>'post_id inválido.']);
    exit;
}

try {
    $hasLive = $pdo->query("SHOW COLUMNS FROM posts LIKE 'is_live'")->fetch();
    if (!$hasLive) {
        echo json_encode(['ok'=>false,'msg'=>'Execute live_migration.sql no phpMyAdmin primeiro.']);
        exit;
    }
    // is_live=0, live_ended_at=NOW(), live_started_at=NULL
    // Limpa live_started_at também para que reativação funcione do zero
    $pdo->prepare("UPDATE posts SET is_live=0, live_ended_at=NOW(), live_started_at=NULL WHERE id=?")
        ->execute([$pid]);
    echo json_encode(['ok'=>true,'post_id'=>$pid]);
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
