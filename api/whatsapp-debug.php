<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/whatsapp.php';

$wpp = WhatsApp::fromSettings($pdo);
$status = $wpp->getStatus();

header('Content-Type: application/json');
echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);