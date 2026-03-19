<?php
// newsletter-action.php
require_once '../includes/db.php';
session_start();

$action = $_GET['action'] ?? '';
$email  = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);

if ($email && $action === 'unsubscribe') {
    dbExec($pdo, "UPDATE newsletters SET status='unsubscribed', unsubscribed_at=NOW() WHERE email=?", [$email]);
    header('Location: /crcap/index.php?newsletter=unsubscribed');
} elseif ($email && $action === 'subscribe') {
    try {
        dbExec($pdo, "INSERT INTO newsletters (email,status,confirmed) VALUES (?,?,1) ON DUPLICATE KEY UPDATE status='subscribed', confirmed=1", [$email, 'subscribed']);
    } catch (Exception $e) {}
    header('Location: /crcap/index.php?newsletter=subscribed');
} else {
    header('Location: /crcap/index.php');
}
exit;
