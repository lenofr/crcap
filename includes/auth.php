<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLogged(): bool {
    return isset($_SESSION['user_id']);
}
function isAdmin(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin','editor']);
}
function requireLogin(string $redirect = '/crcap/pages/login.php'): void {
    if (!isLogged()) { header("Location: $redirect"); exit; }
}
function requireAdmin(string $redirect = '/crcap/pages/login.php'): void {
    if (!isAdmin()) { header("Location: $redirect"); exit; }
}
function currentUser(): array {
    // Dados base da sessão
    $base = [
        'id'        => $_SESSION['user_id'] ?? 0,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role'] ?? 'viewer',
        'avatar'    => $_SESSION['avatar'] ?? '',
        'email'     => $_SESSION['email']    ?? '',
        'phone'     => $_SESSION['phone']    ?? '',
        'password'  => '',
    ];
    // Busca registro completo do banco se tiver conexão
    if ($base['id'] && isset($GLOBALS['pdo'])) {
        global $pdo;
        if (!function_exists('dbFetch')) {
            require_once __DIR__ . '/db.php';
        }
        $row = dbFetch($pdo, "SELECT * FROM users WHERE id=? LIMIT 1", [$base['id']]);
        if ($row) return $row;
    }
    return $base;
}

function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfVerify(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}
?>