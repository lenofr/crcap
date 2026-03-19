<?php
/**
 * API: /api/pdf-viewer.php
 * Serve PDFs com headers corretos para exibição em iframe
 * GET ?file=uploads/documents/2025/01/arquivo.pdf
 */
require_once __DIR__ . '/../includes/db.php';

$file = trim($_GET['file'] ?? '');

// Segurança: bloqueia path traversal
$file = ltrim($file, '/');
$file = str_replace(['../', '..\\', "\0"], '', $file);

// Só permite arquivos dentro de /uploads/
if (!str_starts_with($file, 'uploads/')) {
    http_response_code(403);
    exit('Acesso negado.');
}

// Só permite PDFs
if (!str_ends_with(strtolower($file), '.pdf')) {
    http_response_code(403);
    exit('Apenas PDFs são permitidos.');
}

$realPath = __DIR__ . '/../' . $file;

if (!is_file($realPath)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$size = filesize($realPath);
$name = basename($realPath);

// ── Headers que permitem o iframe funcionar ────────────────────────────────
header('Content-Type: application/pdf');
header('Content-Length: ' . $size);
header('Content-Disposition: inline; filename="' . addslashes($name) . '"');

// Remove bloqueios de iframe
header('X-Frame-Options: SAMEORIGIN');
header('Content-Security-Policy: frame-ancestors \'self\'');

// Cache moderado
header('Cache-Control: private, max-age=3600');
header('Pragma: public');

// Suporte a range requests (necessário para PDF.js e navegadores mobile)
header('Accept-Ranges: bytes');

if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    preg_match('/bytes=(\d+)-(\d*)/', $range, $m);
    $start = (int)$m[1];
    $end   = isset($m[2]) && $m[2] !== '' ? (int)$m[2] : $size - 1;
    $length = $end - $start + 1;

    http_response_code(206);
    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: $length");

    $fp = fopen($realPath, 'rb');
    fseek($fp, $start);
    $remaining = $length;
    while ($remaining > 0 && !feof($fp)) {
        $chunk = min(8192, $remaining);
        echo fread($fp, $chunk);
        $remaining -= $chunk;
        ob_flush(); flush();
    }
    fclose($fp);
} else {
    readfile($realPath);
}
exit;