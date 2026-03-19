<?php
// /crcap/api/doc-download.php — incrementa download_count
require_once __DIR__ . '/../includes/db.php';
$id = (int)($_GET['id'] ?? 0);
if ($id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        dbExec($pdo, "UPDATE documents SET download_count = download_count + 1 WHERE id = ?", [$id]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false]);
    }
} else {
    http_response_code(400);
    echo json_encode(['ok' => false]);
}
