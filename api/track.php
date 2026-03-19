<?php
/**
 * track.php — Rastreamento de abertura e cliques de e-mail
 * Deploy: /crcap/api/track.php
 *
 * Abertura : GET /crcap/api/track.php?t=open&c={campaign_id}&e={email_hash}
 * Clique   : GET /crcap/api/track.php?t=click&c={campaign_id}&e={email_hash}&u={url_encoded_destino}
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/db.php';

$type       = $_GET['t']  ?? '';   // 'open' ou 'click'
$campaignId = (int)($_GET['c']    ?? 0);
$emailHash  = trim($_GET['e']     ?? '');
$destUrl    = trim($_GET['u']     ?? '');

// ── Resolve e-mail a partir do hash ─────────────────────────────────────────
function resolveEmail(PDO $pdo, int $cid, string $hash): ?string {
    // O hash é sha256( campaign_id . ':' . email )
    $logs = dbFetchAll($pdo,
        "SELECT recipient_email FROM email_logs WHERE campaign_id = ?",
        [$cid]
    );
    foreach ($logs as $row) {
        if (hash('sha256', $cid . ':' . $row['recipient_email']) === $hash) {
            return $row['recipient_email'];
        }
    }
    return null;
}

if ($campaignId > 0 && $emailHash !== '') {
    $email = resolveEmail($pdo, $campaignId, $emailHash);

    if ($email) {
        if ($type === 'open') {
            // Marca como aberto (só na primeira vez)
            dbExec($pdo,
                "UPDATE email_logs
                 SET status = 'opened', opened_at = COALESCE(opened_at, NOW())
                 WHERE campaign_id = ? AND recipient_email = ? AND status IN ('sent','queued')",
                [$campaignId, $email]
            );
            // Atualiza contador na campanha
            dbExec($pdo,
                "UPDATE email_campaigns
                 SET opened_count = (
                     SELECT COUNT(*) FROM email_logs
                     WHERE campaign_id = ? AND opened_at IS NOT NULL
                 ) WHERE id = ?",
                [$campaignId, $campaignId]
            );

        } elseif ($type === 'click' && $destUrl !== '') {
            $destDecoded = urldecode($destUrl);
            // Marca como clicado
            dbExec($pdo,
                "UPDATE email_logs
                 SET status = 'clicked', clicked_at = COALESCE(clicked_at, NOW()),
                     opened_at = COALESCE(opened_at, NOW())
                 WHERE campaign_id = ? AND recipient_email = ?",
                [$campaignId, $email]
            );
            // Atualiza contadores
            dbExec($pdo,
                "UPDATE email_campaigns
                 SET clicked_count = (
                     SELECT COUNT(*) FROM email_logs
                     WHERE campaign_id = ? AND clicked_at IS NOT NULL
                 ), opened_count = (
                     SELECT COUNT(*) FROM email_logs
                     WHERE campaign_id = ? AND opened_at IS NOT NULL
                 ) WHERE id = ?",
                [$campaignId, $campaignId, $campaignId]
            );
            // Redireciona para destino
            header('Location: ' . $destDecoded, true, 302);
            exit;
        }
    }
}

// ── Retorna pixel 1×1 transparente (abertura) ───────────────────────────────
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
// GIF 1x1 transparente (35 bytes)
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
exit;