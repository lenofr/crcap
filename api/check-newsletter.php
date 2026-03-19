<?php
/**
 * API: check-newsletter.php
 * Verifica se um e-mail já está inscrito na newsletter.
 * Usado pelo formulário de registro para feedback visual em tempo real.
 */
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/db.php';

$email = filter_var(trim($_GET['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['subscribed' => false, 'unsubscribed' => false, 'categoria' => null]);
    exit;
}

try {
    $row = dbFetch($pdo, "SELECT status, categoria FROM newsletters WHERE email=? LIMIT 1", [$email]);

    if (!$row) {
        echo json_encode(['subscribed' => false, 'unsubscribed' => false, 'categoria' => null]);
    } elseif ($row['status'] === 'subscribed') {
        echo json_encode([
            'subscribed'   => true,
            'unsubscribed' => false,
            'categoria'    => $row['categoria'] ?? null,
        ]);
    } else {
        // unsubscribed ou bounced
        echo json_encode([
            'subscribed'   => false,
            'unsubscribed' => true,
            'categoria'    => $row['categoria'] ?? null,
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['subscribed' => false, 'unsubscribed' => false, 'categoria' => null]);
}