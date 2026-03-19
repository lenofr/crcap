<?php
// /crcap/api/send-live-push.php
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

// Garante que tabela existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `push_subscriptions` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `endpoint` TEXT NOT NULL,
      `p256dh` TEXT NULL,
      `auth`    VARCHAR(255) NULL,
      `user_agent` VARCHAR(500) NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `endpoint_hash` (( LEFT(endpoint,255) ))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) { /* já existe */ }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];
$title = htmlspecialchars($data['title'] ?? '🔴 AO VIVO — CRCAP');
$body  = htmlspecialchars($data['body']  ?? 'Transmissão ao vivo iniciada!');
$url   = htmlspecialchars($data['url']   ?? '/crcap/');

$subs = $pdo->query("SELECT * FROM push_subscriptions")->fetchAll(PDO::FETCH_ASSOC);
$total = count($subs);

// Sem inscritos — retorna sucesso com aviso (NÃO é erro)
if ($total === 0) {
    echo json_encode([
        'ok'    => true,
        'count' => 0,
        'note'  => 'Nenhum inscrito ainda. Visitantes precisam aceitar notificações na home primeiro.'
    ]);
    exit;
}

$sent = 0; $failed = 0;
foreach ($subs as $sub) {
    $payload = json_encode(['title'=>$title,'body'=>$body,'url'=>$url]);
    $ch = curl_init($sub['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
            'TTL: 86400',
        ],
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) $sent++;
    elseif ($code === 410 || $code === 404) {
        $pdo->prepare("DELETE FROM push_subscriptions WHERE id=?")->execute([$sub['id']]);
        $failed++;
    } else $failed++;
}

echo json_encode(['ok'=>true,'count'=>$sent,'failed'=>$failed,'total'=>$total]);