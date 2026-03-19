<?php
// /crcap/api/check-live.php — polling endpoint para verificar live ativa
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

try {
    $live = dbFetch($pdo,
        "SELECT id, title FROM posts
         WHERE is_live=1 AND status='published'
         AND (live_ended_at IS NULL OR live_ended_at > NOW())
         LIMIT 1"
    );
    echo json_encode(['live' => !empty($live), 'title' => $live['title'] ?? null]);
} catch (Exception $e) {
    echo json_encode(['live' => false]);
}
