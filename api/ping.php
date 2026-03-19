<?php
// Diagnóstico: acesse https://artemidiaweb.com.br/crcap/api/ping.php
// Apague após uso
header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'php' => PHP_VERSION,
    'session' => session_status(),
    'path' => __FILE__,
    'time' => date('H:i:s')
]);