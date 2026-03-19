<?php
ob_start();
require_once dirname(__DIR__).'/includes/auth.php';
require_once dirname(__DIR__).'/includes/db.php';

header('Content-Type: application/json');
ob_end_clean();

if (!isLogged()) {
    echo json_encode(['success'=>false,'message'=>'Não autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url   = $input['url'] ?? '';

if (!$url) {
    echo json_encode(['success'=>false,'message'=>'URL não informada']);
    exit;
}

try {
    // Busca o registro no banco
    $media = dbFetch($pdo, "SELECT * FROM media WHERE file_path=? OR CONCAT('/crcap/',file_path)=?", [$url, $url]);

    if ($media) {
        // Remove do banco
        dbExec($pdo, "DELETE FROM media WHERE id=?", [(int)$media['id']]);

        // Remove o arquivo físico
        $filePath = $media['file_path'];
        // Normaliza o caminho
        if (strpos($filePath, '/crcap/') === 0) {
            $filePath = substr($filePath, 7); // Remove /crcap/
        }
        $absPath = dirname(__DIR__) . '/' . ltrim($filePath, '/');
        if (file_exists($absPath)) {
            @unlink($absPath);
        }
    } else {
        // Tenta remover pelo URL direto mesmo sem registro no banco
        $path = parse_url($url, PHP_URL_PATH);
        $path = preg_replace('#^/crcap/#', '', $path);
        $absPath = dirname(__DIR__) . '/' . ltrim($path, '/');
        if (file_exists($absPath)) {
            @unlink($absPath);
        }
    }

    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}