<?php
/**
 * API: /crcap/api/test-smtp.php
 * SEMPRE retorna JSON — nunca HTML, nunca redirect.
 */
ob_start(); // captura qualquer output inesperado

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store');
header('X-Content-Type-Options: nosniff');

// Silencia warnings para não quebrar JSON
set_error_handler(function($code, $msg, $file, $line) {
    // ignora — erros viram parte da resposta JSON se críticos
});

try {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/mailer.php';
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Init error: ' . $e->getMessage()]);
    exit;
}

// Auth: retorna JSON em vez de redirecionar
if (!isAdmin()) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Método inválido. Use POST.']);
    exit;
}

// Descarta output acumulado antes da resposta
ob_end_clean();

// Parse input
$raw   = file_get_contents('php://input');
$input = !empty($raw) ? (json_decode($raw, true) ?? $_POST) : $_POST;

$smtpId    = (int)($input['smtp_id']    ?? 0);
$sendTest  = !empty($input['send_test']);
$testEmail = filter_var($input['test_email'] ?? '', FILTER_VALIDATE_EMAIL);

try {
    if ($smtpId) {
        $cfg = dbFetch($pdo, "SELECT * FROM smtp_settings WHERE id=?", [$smtpId]);
        if (!$cfg) {
            echo json_encode(['success' => false, 'message' => 'SMTP id=' . $smtpId . ' não encontrado.']);
            exit;
        }
        $cfg['password'] = base64_decode($cfg['password'] ?? '');

        // Teste TCP
        $host   = $cfg['host'] ?? '';
        $port   = (int)($cfg['port'] ?? 587);
        $enc    = $cfg['encryption'] ?? 'tls';
        $prefix = ($enc === 'ssl') ? 'ssl://' : '';
        $ctx    = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $conn   = @stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);

        if (!$conn) {
            echo json_encode(['success' => false, 'message' => "Falha TCP em {$host}:{$port} — {$errstr} (#{$errno})"]);
            exit;
        }
        $banner = trim(fgets($conn, 512));
        fclose($conn);

        $result = ['success' => true, 'message' => "✓ Porta {$port} OK em {$host}. Banner: {$banner}"];

        if ($sendTest && $testEmail) {
            $mailer = new CrcapMailer($pdo);
            $html   = $mailer->wrapTemplate(
                'Teste SMTP – CRCAP',
                '<p>Configuração <strong>' . htmlspecialchars($cfg['name']) . '</strong> funcionando.</p>
                 <p>Servidor: ' . htmlspecialchars($host . ':' . $port) . ' / ' . htmlspecialchars($enc) . '</p>',
                'Painel Admin', '/crcap/admin/'
            );
            $sent = $mailer->send($testEmail, 'Admin CRCAP', 'Teste SMTP – CRCAP', $html);
            $result['test_sent']  = $sent;
            $result['test_email'] = $testEmail;
            if ($sent) {
                $result['message'] .= " | E-mail enviado para {$testEmail}.";
            } else {
                $errs = $mailer->getErrors();
                $result['success'] = false;
                $result['message'] = 'Conexão OK mas envio falhou: ' . implode('; ', $errs);
            }
        }

        echo json_encode($result);
        exit;
    }

    // Sem ID: testar config padrão
    $mailer = new CrcapMailer($pdo);
    $test   = $mailer->testConnection();

    if ($sendTest && $testEmail) {
        $html = $mailer->wrapTemplate(
            'Teste SMTP – CRCAP',
            '<p>E-mail enviado às ' . date('H:i') . ' de ' . date('d/m/Y') . '.</p>',
            'Painel Admin', '/crcap/admin/'
        );
        $sent = $mailer->send($testEmail, 'Admin CRCAP', 'Teste SMTP – CRCAP', $html);
        $test['test_sent']  = $sent;
        $test['test_email'] = $testEmail;
        if (!$sent) {
            $test['smtp_errors'] = $mailer->getErrors();
            $test['message']     = 'Falha no envio: ' . implode('; ', $mailer->getErrors());
        } else {
            $test['message'] = "E-mail enviado com sucesso para {$testEmail}!";
        }
    }

    echo json_encode($test);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exceção: ' . $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']'
    ]);
}