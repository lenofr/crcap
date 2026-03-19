<?php
// crcap/usuario/sse-usuario.php
// Atualização em tempo real para área do usuário:
//   - Detecta quando admin responde mensagem
//   - Detecta quando status de inscrição muda
// ─────────────────────────────────────────────
if (ob_get_level()) ob_end_clean();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Exige login — sem sessão, retorna erro SSE limpo
if (!isLogged()) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    echo "event: erro\ndata: {\"msg\":\"nao_autenticado\"}\n\n";
    ob_flush(); flush();
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

$user  = currentUser();
$email = $user['email'] ?? '';
$uid   = (int)($user['id'] ?? 0);

if (!$email || !$uid) {
    echo "event: erro\ndata: {\"msg\":\"usuario_invalido\"}\n\n";
    ob_flush(); flush();
    exit;
}

// ── Helper SSE ────────────────────────────────
function sseU(string $evento, array $dados): void {
    echo "event: {$evento}\n";
    echo "data: " . json_encode($dados, JSON_UNESCAPED_UNICODE) . "\n\n";
    ob_flush(); flush();
}

// ── Loop: 4 minutos, ciclo de 12 segundos ─────
$limite = time() + 240;

while (time() < $limite) {

    // ── 1) STATUS DAS MENSAGENS ────────────────
    // Envia lista com id + status + replied_at + reply_message
    // O JS compara com o que está no DOM e atualiza badges / exibe resposta
    $msgs = dbFetchAll($pdo,
        "SELECT id, status, reply_message, replied_at
         FROM contacts
         WHERE email = ?
         ORDER BY created_at DESC
         LIMIT 20",
        [$email]
    );

    if (!empty($msgs)) {
        $payload = [];
        foreach ($msgs as $m) {
            $payload[] = [
                'id'            => (int)$m['id'],
                'status'        => $m['status'],
                'reply_message' => $m['reply_message'] ?? '',
                'replied_at'    => $m['replied_at'] ?? '',
            ];
        }
        sseU('sync_msgs', ['mensagens' => $payload]);
    }

    // ── 2) STATUS DAS INSCRIÇÕES ───────────────
    // Envia id + status de cada inscrição ativa do usuário
    $regs = dbFetchAll($pdo,
        "SELECT er.id, er.status
         FROM event_registrations er
         JOIN events e ON e.id = er.event_id
         WHERE er.email = ?
           AND e.event_date >= CURDATE()
         ORDER BY er.registered_at DESC
         LIMIT 20",
        [$email]
    );

    if (!empty($regs)) {
        $payload = [];
        foreach ($regs as $r) {
            $payload[] = [
                'id'     => (int)$r['id'],
                'status' => $r['status'],
            ];
        }
        sseU('sync_regs', ['inscricoes' => $payload]);
    }

    // Heartbeat
    echo ": heartbeat\n\n";
    ob_flush(); flush();

    sleep(12);
}

sseU('reconectar', ['msg' => 'ok']);