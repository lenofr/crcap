<?php
$log = __DIR__ . '/logs/whatsapp-debug.log';
if (file_exists($log)) {
    echo '<pre>' . htmlspecialchars(file_get_contents($log)) . '</pre>';
} else {
    echo 'Arquivo não encontrado: ' . $log;
}