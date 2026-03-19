<?php
// =====================================================
// CRCAP - Conexão com Banco de Dados + Helpers
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'crcapor_crc2026');
define('DB_USER', 'crcapor_crc2026');
define('DB_PASS', '@rtM!dia2022#');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Graceful error page instead of die()
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erro de Conexão</title></head><body style="font-family:sans-serif;text-align:center;padding:60px"><h2>⚠️ Erro de Conexão</h2><p>Não foi possível conectar ao banco de dados. Verifique as configurações em <code>includes/db.php</code>.</p></body></html>';
    exit;
}

// ── Query helpers ─────────────────────────────────────────────────────────────

function dbFetch($pdo, $sql, $params = []) {
    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetch() ?: null;
}

function dbFetchAll($pdo, $sql, $params = []) {
    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

function dbExec($pdo, $sql, $params = []) {
    $s = $pdo->prepare($sql);
    $s->execute($params);
    return $s;
}

// Aliases for legacy template code
function dbQuery($sql, $params = []) {
    global $pdo;
    return dbFetchAll($pdo, $sql, $params);
}

function dbQueryOne($sql, $params = []) {
    global $pdo;
    return dbFetch($pdo, $sql, $params);
}

// ── Output helpers ────────────────────────────────────────────────────────────

/**
 * Safe HTML output - escape for display
 */
function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format file size
 */
function formatBytes($bytes, $decimals = 1) {
    if (!$bytes) return '—';
    $units = ['B','KB','MB','GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / 1024**$i, $decimals) . ' ' . $units[$i];
}

/**
 * Slug generator
 */
function makeSlug($str) {
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    return strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $str), '-'));
}

/**
 * Truncate text
 */
function truncate($str, $len = 100, $suffix = '...') {
    if (mb_strlen($str) <= $len) return $str;
    return mb_substr($str, 0, $len) . $suffix;
}

/**
 * Get site setting value
 */
function getSetting($pdo, $key, $default = '') {
    $row = dbFetch($pdo, "SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1", [$key]);
    return $row ? $row['setting_value'] : $default;
}

// ─── App base path helper ─────────────────────────────────────────────────────
// Returns the base path prefix (e.g. '' for root, '/crcap' for subdirectory).
// Priority: 1) settings table (app_basepath), 2) auto-detect from filesystem
if (!function_exists('appBase')) {
    function appBase(): string {
        static $base = null;
        if ($base === null) {
            // 1. Try settings table first (admin-configurable)
            global $pdo;
            if ($pdo) {
                try {
                    $row = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='app_basepath' LIMIT 1")->fetch();
                    if ($row && $row['setting_value'] !== null && $row['setting_value'] !== '') {
                        $base = rtrim($row['setting_value'], '/');
                        return $base;
                    }
                } catch (Exception $e) {}
            }
            // 2. Auto-detect from filesystem
            $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
            $appRoot = rtrim(dirname(__DIR__), '/');
            $base    = $docRoot ? str_replace($docRoot, '', $appRoot) : '';
        }
        return $base;
    }
}