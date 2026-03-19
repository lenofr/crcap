<?php
// =====================================================
// API: /api/whatsapp-send.php
// Disparo de campanhas WhatsApp — admin only
// POST JSON: { campaign_id: N }
// =====================================================
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/whatsapp.php';

// Aceita JSON ou form-data
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action     = $input['action']      ?? 'send';
$campaignId = (int)($input['campaign_id'] ?? 0);

// ── Ação: status — pode rodar sem autenticação (apenas leitura de config) ───
if ($action === 'status') {
    try {
        $wpp    = WhatsApp::fromSettings($pdo);
        $status = $wpp->getStatus();
        echo json_encode(['success' => true, 'status' => $status]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

// Demais ações exigem admin/editor
if (!isLogged() || !in_array(currentUser()['role'] ?? '', ['admin','editor'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}
if ($action === 'test') {
    $testPhone = trim($input['test_phone'] ?? '');
    $message   = trim($input['message']   ?? 'Mensagem de teste CRCAP ✅');

    if (!$testPhone) {
        echo json_encode(['success' => false, 'message' => 'Número de teste obrigatório']);
        exit;
    }

    try {
        $wpp = WhatsApp::fromSettings($pdo);
        if (!$wpp->isConfigured()) {
            echo json_encode(['success' => false, 'message' => 'WhatsApp não configurado. Configure em Configurações > WhatsApp.']);
            exit;
        }
        $res = $wpp->sendText($testPhone, $message);
        echo json_encode(['success' => true, 'message' => 'Mensagem de teste enviada!', 'data' => $res]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── Ação: send (disparar campanha) ───────────────────────────────────────────
if ($action === 'send') {
    if (!$campaignId) {
        echo json_encode(['success' => false, 'message' => 'campaign_id obrigatório']);
        exit;
    }

    // Verificar campanha
    $camp = dbFetch($pdo, "SELECT * FROM whatsapp_campaigns WHERE id=?", [$campaignId]);
    if (!$camp) {
        echo json_encode(['success' => false, 'message' => 'Campanha não encontrada']);
        exit;
    }
    if ($camp['status'] === 'sending') {
        echo json_encode(['success' => false, 'message' => 'Campanha já está sendo enviada']);
        exit;
    }
    if ($camp['status'] === 'sent') {
        echo json_encode(['success' => false, 'message' => 'Campanha já foi enviada']);
        exit;
    }

    try {
        $wpp = WhatsApp::fromSettings($pdo);
        if (!$wpp->isConfigured()) {
            echo json_encode(['success' => false, 'message' => 'WhatsApp não configurado']);
            exit;
        }

        // Log de início
        dbExec($pdo,
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
             VALUES (?,?,?,?,?,?)",
            [currentUser()['id'], 'whatsapp_campaign_start', 'whatsapp_campaigns',
             $campaignId, "Campanha '{$camp['name']}' iniciada", $_SERVER['REMOTE_ADDR'] ?? '']);

        // Aumentar tempo de execução para envios longos
        set_time_limit(0);
        ignore_user_abort(true);

        $result = $wpp->sendCampaign($campaignId);

        // Log de conclusão
        dbExec($pdo,
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address)
             VALUES (?,?,?,?,?,?)",
            [currentUser()['id'], 'whatsapp_campaign_sent', 'whatsapp_campaigns',
             $campaignId,
             "Campanha '{$camp['name']}' concluída: {$result['sent']} enviados, {$result['failed']} falhas",
             $_SERVER['REMOTE_ADDR'] ?? '']);

        echo json_encode([
            'success' => true,
            'message' => "Campanha enviada! {$result['sent']} de {$result['total']} mensagens entregues.",
            'result'  => $result,
        ]);

    } catch (Exception $e) {
        dbExec($pdo,
            "UPDATE whatsapp_campaigns SET status='draft' WHERE id=?", [$campaignId]);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── Ação: add_contact ────────────────────────────────────────────────────────
if ($action === 'add_contact') {
    $phone = preg_replace('/\D/', '', $input['phone'] ?? '');
    $name  = trim($input['name'] ?? '');
    if (strlen($phone) < 8) {
        echo json_encode(['success'=>false,'message'=>'Número inválido']); exit;
    }
    try {
        dbExec($pdo,
            "INSERT INTO whatsapp_contacts (phone, name) VALUES (?,?)
             ON DUPLICATE KEY UPDATE name=VALUES(name), status='active'",
            [$phone, $name]);
        echo json_encode(['success'=>true,'message'=>'Contato adicionado!']);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

// ── Ação: remove_contact ─────────────────────────────────────────────────────
if ($action === 'remove_contact') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'ID inválido']); exit; }
    dbExec($pdo, "DELETE FROM whatsapp_contacts WHERE id=?", [$id]);
    echo json_encode(['success'=>true,'message'=>'Contato removido.']);
    exit;
}

// ── Ação: stats (progresso em tempo real) ───────────────────────────────────
if ($action === 'stats') {
    if (!$campaignId) {
        echo json_encode(['success' => false, 'message' => 'campaign_id obrigatório']);
        exit;
    }
    $camp = dbFetch($pdo, "SELECT status, total_recipients, sent_count, failed_count FROM whatsapp_campaigns WHERE id=?", [$campaignId]);
    if (!$camp) {
        echo json_encode(['success' => false, 'message' => 'Não encontrada']);
        exit;
    }
    $total = max(1, $camp['total_recipients']);
    echo json_encode([
        'success'    => true,
        'status'     => $camp['status'],
        'total'      => $camp['total_recipients'],
        'sent'       => $camp['sent_count'],
        'failed'     => $camp['failed_count'],
        'percent'    => round(($camp['sent_count'] + $camp['failed_count']) / $total * 100),
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação inválida']);