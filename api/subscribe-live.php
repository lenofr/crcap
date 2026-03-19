<?php
// /crcap/api/subscribe-live.php
// Recebe e salva push subscription do browser
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

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

$data = json_decode(file_get_contents('php://input'), true);
$ep   = $data['endpoint'] ?? '';
if (!$ep) { echo json_encode(['ok'=>false,'msg'=>'endpoint vazio']); exit; }

try {
    $pdo->prepare("INSERT IGNORE INTO push_subscriptions (endpoint,p256dh,auth,user_agent)
                   VALUES (?,?,?,?)")
        ->execute([
            $ep,
            $data['keys']['p256dh'] ?? null,
            $data['keys']['auth']   ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
