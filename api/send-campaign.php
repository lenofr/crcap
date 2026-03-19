<?php
/**
 * API: send-campaign.php
 * Envia e-mails de campanha em lotes (batch).
 */
ob_start(); // captura qualquer output PHP acidental
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

// Garante que a resposta seja sempre JSON — mesmo em caso de erro fatal
function sendJson(array $data): void {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Migration automática: UNIQUE KEY em email_logs ──────────────────────────
try {
    $pdo->exec("ALTER TABLE email_logs ADD UNIQUE KEY uq_campaign_recipient (campaign_id, recipient_email)");
} catch (Exception $e) { /* já existe — ok */ }

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin','editor'])) {
    sendJson(['success'=>false,'message'=>'Acesso negado']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success'=>false,'message'=>'Método não permitido']);
}

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$campaignId = (int)($body['campaign_id'] ?? 0);
$offset     = (int)($body['offset']      ?? 0);
$batchSize  = max(1, min(100, (int)($body['batch_size'] ?? 50)));

if (!$campaignId) {
    sendJson(['success'=>false,'message'=>'ID de campanha inválido']);
}

$campaign = dbFetch($pdo, "SELECT * FROM email_campaigns WHERE id=?", [$campaignId]);
if (!$campaign) {
    sendJson(['success'=>false,'message'=>'Campanha não encontrada']);
}
if ($campaign['status'] === 'sent') {
    sendJson(['success'=>false,'message'=>'Campanha já foi enviada completamente']);
}

// ── Monta filtro SQL por categoria ─────────────────────────────────────────
$whereExtra = '';
$catParams  = [];

if (!empty($campaign['segment_filter'])) {
    $sf = json_decode($campaign['segment_filter'], true);
    if (!empty($sf['categorias']) && is_array($sf['categorias'])) {
        $cats = array_values(array_filter(array_map('trim', $sf['categorias'])));
        if (!empty($cats)) {
            $placeholders = implode(',', array_fill(0, count($cats), '?'));
            $whereExtra   = " AND categoria IN ($placeholders)";
            $catParams    = $cats;
        }
    }
}

// ── Primeiro batch: conta total e inicia envio ──────────────────────────────
$totalRecipients = 0;
if ($offset === 0) {
    $countSql        = "SELECT COUNT(*) AS n FROM newsletters WHERE status='subscribed' AND confirmed=1" . $whereExtra;
    $totalRecipients = (int)(dbFetch($pdo, $countSql, $catParams)['n'] ?? 0);

    if ($totalRecipients === 0) {
        dbExec($pdo, "UPDATE email_campaigns SET status='sent', sent_at=NOW(), total_recipients=0, sent_count=0 WHERE id=?", [$campaignId]);
        sendJson([
            'success'=>true,'done'=>true,'progress'=>100,'sent'=>0,'total'=>0,
            'message'=>'Nenhum destinatário encontrado para as categorias selecionadas.',
            'batch_failed'=>0,'errors'=>[],
        ]);
    }

    dbExec($pdo,
        "UPDATE email_campaigns SET status='sending', total_recipients=?, sent_at=NOW() WHERE id=?",
        [$totalRecipients, $campaignId]
    );
} else {
    $totalRecipients = max(1, (int)$campaign['total_recipients']);
}

// ── Busca lote de destinatários ─────────────────────────────────────────────
$fetchParams = array_merge($catParams, [$batchSize, $offset]);
$fetchSql    = "SELECT id, email, name, full_name, categoria FROM newsletters
                WHERE status='subscribed' AND confirmed=1" . $whereExtra . "
                ORDER BY id ASC LIMIT ? OFFSET ?";

$recipients = dbFetchAll($pdo, $fetchSql, $fetchParams);
$mailer     = new CrcapMailer($pdo);
$sentCount  = 0;
$failCount  = 0;
$errors     = [];

// ── Funções de rastreamento ─────────────────────────────────────────────────
$trackBase = 'https://artemidiaweb.com.br/crcap/api/track.php';

/**
 * Injeta pixel de abertura e envolve todos os <a href> com URL de clique.
 */
function injectTracking(string $html, int $campaignId, string $email, string $trackBase): string {
    $hash = hash('sha256', $campaignId . ':' . $email);

    // 1) Substituir links — ignora ancoras (#), mailto:, unsubscribe e já rastreados
    $html = preg_replace_callback(
        '/<a\s([^>]*?)href\s*=\s*["\']([^"\']+)["\']/i',
        function($m) use ($campaignId, $hash, $trackBase) {
            $attrs = $m[1];
            $url   = $m[2];
            if (
                strpos($url, '#') === 0 ||
                strpos($url, 'mailto:') === 0 ||
                strpos($url, 'tel:') === 0 ||
                strpos($url, 'track.php') !== false
            ) {
                return $m[0];
            }
            $trackUrl = $trackBase
                . '?t=click'
                . '&c=' . $campaignId
                . '&e=' . urlencode($hash)
                . '&u=' . urlencode($url);
            return '<a ' . $attrs . 'href="' . $trackUrl . '"';
        },
        $html
    );

    // 2) Injetar pixel de abertura antes de </body> (ou no final)
    $pixel = '<img src="' . $trackBase . '?t=open&c=' . $campaignId . '&e=' . urlencode($hash)
           . '" width="1" height="1" alt="" style="display:block;width:1px;height:1px;border:0;" />';

    if (stripos($html, '</body>') !== false) {
        $html = str_ireplace('</body>', $pixel . '</body>', $html);
    } else {
        $html .= $pixel;
    }

    return $html;
}

foreach ($recipients as $r) {
    $nome         = trim($r['name'] ?: $r['full_name'] ?: '');
    $primeiroNome = $nome ? explode(' ', $nome)[0] : 'Prezado(a)';
    $categoria    = $r['categoria'] ?? '';
    $unsubUrl     = 'https://artemidiaweb.com.br/crcap/unsubscribe.php?email=' . urlencode($r['email']);

    $html = str_replace(
        ['{{nome}}','{{email}}','{{categoria}}','{{unsubscribe_url}}'],
        [htmlspecialchars($primeiroNome), htmlspecialchars($r['email']), htmlspecialchars($categoria), $unsubUrl],
        $campaign['content_html']
    );

    // Injeta pixel de abertura + links rastreados
    $html = injectTracking($html, $campaignId, $r['email'], $trackBase);

    try {
        $ok = $mailer->send($r['email'], $primeiroNome, $campaign['subject'], $html);
        $status = $ok ? 'sent' : 'failed';
        if ($ok) { $sentCount++; } else { $failCount++; $errors[] = "Falha ao enviar: {$r['email']}"; }

        // Salvar log — INSERT simples com IGNORE para evitar erro de duplicata
        try {
            dbExec($pdo,
                "INSERT IGNORE INTO email_logs
                 (campaign_id, recipient_email, recipient_name, subject, status, sent_at)
                 VALUES (?,?,?,?,?,NOW())",
                [$campaignId, $r['email'], $primeiroNome, $campaign['subject'], $status]
            );
        } catch (Exception $e) { /* ignora erro de log — não impede o envio */ }

    } catch (Exception $e) {
        $failCount++;
        $errors[] = substr($r['email'] . ': ' . $e->getMessage(), 0, 120);
    }

    // Pausa leve a cada 10 envios para não sobrecarregar o SMTP
    if (($sentCount + $failCount) % 10 === 0 && ($sentCount + $failCount) > 0) {
        usleep(150000);
    }
}

// ── Atualiza contadores ─────────────────────────────────────────────────────
$nextOffset   = $offset + $batchSize;
$isDone       = count($recipients) < $batchSize;
$newSentTotal = (int)($campaign['sent_count'] ?? 0) + $sentCount;
$newFail      = (int)($campaign['bounced_count'] ?? 0) + $failCount;

if ($isDone) {
    dbExec($pdo, "UPDATE email_campaigns SET status='sent', sent_count=?, bounced_count=? WHERE id=?",
        [$newSentTotal, $newFail, $campaignId]);
} else {
    dbExec($pdo, "UPDATE email_campaigns SET sent_count=?, bounced_count=? WHERE id=?",
        [$newSentTotal, $newFail, $campaignId]);
}

$progress = $totalRecipients > 0 ? min(99, (int)round($nextOffset / $totalRecipients * 100)) : 100;
if ($isDone) $progress = 100;

$msg = $isDone
    ? "Envio concluído! {$newSentTotal} e-mail(s) enviado(s)" . ($failCount ? ", {$failCount} falha(s)." : '.')
    : "Enviando… {$newSentTotal}/{$totalRecipients}";

sendJson([
    'success'     => true,
    'done'        => $isDone,
    'progress'    => $progress,
    'message'     => $msg,
    'sent'        => $sentCount,
    'total'       => $totalRecipients,
    'next_offset' => $nextOffset,
    'batch_failed'=> $failCount,
    'errors'      => $errors,
]);