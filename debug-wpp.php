<?php
require_once __DIR__ . '/includes/db.php';
$logs = dbFetchAll($pdo, 
    "SELECT phone, status, error_message, sent_at 
     FROM whatsapp_logs ORDER BY id DESC LIMIT 5"
);
foreach ($logs as $l) {
    echo "<pre>";
    print_r($l);
    echo "</pre>";
}