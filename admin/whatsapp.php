<?php
// All POST processing happens before any HTML output
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/whatsapp.php';

$action  = $_GET['action'] ?? 'list';
$msg     = '';
$msgType = 'ok';

// ── Normalizar status NULL/vazio para 'draft' ──────────────────────────────
try {
    $pdo->exec("UPDATE `whatsapp_campaigns` SET `status`='draft' WHERE `status` IS NULL OR `status`=''");
} catch (Exception $e) {}

// ── Criar tabelas se não existirem ──────────────────────────────────────────
$pdo->exec("
CREATE TABLE IF NOT EXISTS `whatsapp_campaigns` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(255) NOT NULL,
    `message`           TEXT NOT NULL,
    `media_id`          INT UNSIGNED NULL,
    `document_id`       INT UNSIGNED NULL,
    `link_url`          VARCHAR(500) NULL,
    `recipient_group`   ENUM('newsletter','whatsapp_list','manual') DEFAULT 'whatsapp_list',
    `manual_recipients` TEXT NULL,
    `status`            ENUM('draft','sending','sent','cancelled') DEFAULT 'draft',
    `total_recipients`  INT DEFAULT 0,
    `sent_count`        INT DEFAULT 0,
    `failed_count`      INT DEFAULT 0,
    `scheduled_at`      DATETIME NULL,
    `sent_at`           DATETIME NULL,
    `created_by`        INT UNSIGNED NULL,
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `whatsapp_contacts` (
    `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`     VARCHAR(255) NULL,
    `phone`    VARCHAR(20) NOT NULL,
    `tag`      VARCHAR(100) NULL,
    `status`   ENUM('active','inactive') DEFAULT 'active',
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `whatsapp_logs` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`   INT UNSIGNED NULL,
    `phone`         VARCHAR(20) NOT NULL,
    `status`        ENUM('sent','failed') DEFAULT 'sent',
    `error_message` TEXT NULL,
    `sent_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_campaign` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// ── Processar POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Salvar campanha
    if ($action === 'save') {
        $data = [
            'name'              => trim($_POST['name'] ?? ''),
            'message'           => trim($_POST['message'] ?? ''),
            'media_id'          => (int)($_POST['media_id'] ?? 0) ?: null,
            'document_id'       => (int)($_POST['document_id'] ?? 0) ?: null,
            'link_url'          => trim($_POST['link_url'] ?? '') ?: null,
            'template_name'     => trim($_POST['template_name'] ?? '') ?: null,
            'recipient_group'   => $_POST['recipient_group'] ?? 'whatsapp_list',
            'manual_recipients' => trim($_POST['manual_recipients'] ?? '') ?: null,
            'scheduled_at'      => trim($_POST['scheduled_at'] ?? '') ?: null,
        ];
        if (!$data['name'] || !$data['message']) {
            $msg = 'Nome e mensagem são obrigatórios.'; $msgType = 'err';
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $set = implode(', ', array_map(fn($k) => "`$k`=?", array_keys($data)));
                dbExec($pdo, "UPDATE whatsapp_campaigns SET $set WHERE id=?",
                    [...array_values($data), $id]);
                $msg = 'Campanha atualizada!';
            } else {
                $data['created_by'] = currentUser()['id'];
                $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
                $vals = implode(', ', array_fill(0, count($data), '?'));
                dbExec($pdo, "INSERT INTO whatsapp_campaigns ($cols) VALUES ($vals)", array_values($data));
                $id  = (int)$pdo->lastInsertId();
                $msg = 'Campanha criada com sucesso!';
            }
            header("Location: ?action=edit&id=$id&msg=" . urlencode($msg)); exit;
        }
    }

    // Importar contatos CSV
    if ($action === 'import') {
        if (!empty($_FILES['csv']['tmp_name'])) {
            $lines = file($_FILES['csv']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $imported = $skipped = 0;
            foreach ($lines as $i => $line) {
                if ($i === 0 && stripos($line, 'phone') !== false) continue;
                $cols  = str_getcsv($line);
                $phone = preg_replace('/\D/', '', $cols[0] ?? '');
                $name  = trim($cols[1] ?? '');
                if (strlen($phone) < 8) { $skipped++; continue; }
                try {
                    dbExec($pdo,
                        "INSERT INTO whatsapp_contacts (phone, name) VALUES (?,?)
                         ON DUPLICATE KEY UPDATE name=VALUES(name)", [$phone, $name]);
                    $imported++;
                } catch (Exception) { $skipped++; }
            }
            $msg = "Importação concluída: $imported adicionados, $skipped ignorados.";
        } else { $msg = 'Selecione um arquivo CSV.'; $msgType = 'err'; }
    }

    // Deletar campanha
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        dbExec($pdo, "DELETE FROM whatsapp_campaigns WHERE id=? AND status!='sending'", [$id]);
        header('Location: ?msg=' . urlencode('Campanha removida.')); exit;
    }

    // ── Salvar configurações WhatsApp ───────────────────────────────────────
    if ($action === 'save_config') {
        $fields = ['whatsapp_provider','whatsapp_api_url','whatsapp_api_key',
                   'whatsapp_instance','whatsapp_phone_id'];
        foreach ($fields as $key) {
            $val = trim($_POST[$key] ?? '');

            // ── CORREÇÃO: Sanitizar URL da API ──────────────────────────────
            // Usuário pode colar a URL completa copiada do painel Meta / curl:
            //   ERRADO:  https://graph.facebook.com/v22.0/1032740239921327/messages
            //   CORRETO: https://graph.facebook.com/v22.0
            // A classe WhatsApp monta a URL final automaticamente:
            //   $this->apiUrl . "/{phoneId}/messages"
            if ($key === 'whatsapp_api_url' && !empty($val)) {
                $val = rtrim($val, '/');
                // Remove /{phoneId}/messages do final
                $val = preg_replace('#/\d{10,}/messages$#', '', $val);
                // Remove /{phoneId} do final (sem /messages)
                $val = preg_replace('#/\d{10,}$#', '', $val);
                $val = rtrim($val, '/');
            }

            $exists = dbFetch($pdo, "SELECT id FROM settings WHERE setting_key=?", [$key]);
            if ($exists) {
                dbExec($pdo, "UPDATE settings SET setting_value=? WHERE setting_key=?", [$val, $key]);
            } else {
                dbExec($pdo,
                    "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?,?,'whatsapp')",
                    [$key, $val]);
            }
        }
        $msg = 'Configurações salvas!';
    }
}

// Mensagem via GET redirect
if (!$msg && isset($_GET['msg'])) $msg = $_GET['msg'];

// ── Inclui header HTML (após todo processamento POST) ────────────────────────
$pageTitle = 'WhatsApp · Admin CRCAP';
$activeAdm = 'whatsapp';
require_once __DIR__ . '/admin_header.php';

// ── Dados para as views ─────────────────────────────────────────────────────
$wpp = WhatsApp::fromSettings($pdo);

$stats = [
    'total'     => dbFetch($pdo, "SELECT COUNT(*) n FROM whatsapp_campaigns")['n'] ?? 0,
    'sent'      => dbFetch($pdo, "SELECT COUNT(*) n FROM whatsapp_campaigns WHERE status='sent'")['n'] ?? 0,
    'draft'     => dbFetch($pdo, "SELECT COUNT(*) n FROM whatsapp_campaigns WHERE status='draft'")['n'] ?? 0,
    'contacts'  => dbFetch($pdo, "SELECT COUNT(*) n FROM whatsapp_contacts WHERE status='active'")['n'] ?? 0,
    'msgs_sent' => dbFetch($pdo, "SELECT COALESCE(SUM(sent_count),0) n FROM whatsapp_campaigns WHERE status='sent'")['n'] ?? 0,
];

$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editing = dbFetch($pdo, "SELECT * FROM whatsapp_campaigns WHERE id=?", [(int)$_GET['id']]);
}

$campaigns = dbFetchAll($pdo,
    "SELECT c.*, u.full_name AS author
     FROM whatsapp_campaigns c
     LEFT JOIN users u ON u.id = c.created_by
     ORDER BY c.created_at DESC LIMIT 50");

$medias    = dbFetchAll($pdo, "SELECT id, title, file_path FROM media WHERE file_type IN ('jpg','jpeg','png','webp') ORDER BY created_at DESC LIMIT 100");
$documents = dbFetchAll($pdo, "SELECT id, title, file_name FROM documents WHERE status='active' AND file_type='pdf' ORDER BY created_at DESC LIMIT 100");

$contacts_page  = max(1,(int)($_GET['cp'] ?? 1));
$contacts_total = dbFetch($pdo,"SELECT COUNT(*) n FROM whatsapp_contacts")['n'] ?? 0;
$contacts       = dbFetchAll($pdo,"SELECT * FROM whatsapp_contacts ORDER BY added_at DESC LIMIT 30 OFFSET ".(($contacts_page-1)*30));

// Config atual (já corrigida pelo construtor WhatsApp caso venha errada do BD)
$cfgRows = dbFetchAll($pdo, "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'whatsapp_%'");
$cfg = [];
foreach ($cfgRows as $r) $cfg[str_replace('whatsapp_','',$r['setting_key'])] = $r['setting_value'];

// Exibir a URL já sanitizada (igual ao que a classe usa internamente)
$cfgUrlDisplay = $cfg['api_url'] ?? '';
if (!empty($cfgUrlDisplay)) {
    $cfgUrlDisplay = rtrim($cfgUrlDisplay, '/');
    $cfgUrlDisplay = preg_replace('#/\d{10,}/messages$#', '', $cfgUrlDisplay);
    $cfgUrlDisplay = preg_replace('#/\d{10,}$#', '', $cfgUrlDisplay);
    $cfgUrlDisplay = rtrim($cfgUrlDisplay, '/');
}

$base = appBase();
?>

<div class="flex-1 overflow-y-auto">
<div class="p-6 max-w-7xl mx-auto">

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#25D366] to-[#128C7E] flex items-center justify-center text-white text-xl shadow-lg shadow-[#25D366]/30">
            <i class="fab fa-whatsapp"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-[#001644]">WhatsApp</h1>
            <p class="text-[10px] text-[#022E6B]/50">Campanhas e disparos em massa</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <div id="wppStatus" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-[#001644]/05 text-xs text-[#022E6B]/60">
            <span class="w-2 h-2 rounded-full bg-gray-300 animate-pulse"></span>
            <span>Verificando...</span>
        </div>
        <button onclick="checkStatus()" class="btn-primary text-xs px-3 py-1.5" title="Verificar conexão">
            <i class="fas fa-sync-alt text-[10px]"></i>
        </button>
        <button onclick="openTab('config')" class="btn-primary text-xs px-3 py-1.5">
            <i class="fas fa-cog text-[10px]"></i> Configurar API
        </button>
        <button onclick="openTab('new')" class="btn-gold text-xs px-3 py-2">
            <i class="fas fa-plus text-[10px]"></i> Nova Campanha
        </button>
    </div>
</div>

<?php if ($msg): ?>
<div class="mb-4 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2 <?= $msgType==='ok' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
    <i class="fas <?= $msgType==='ok' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
    <?= h($msg) ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <?php $statCards = [
        ['fab fa-whatsapp',   'Mensagens Enviadas', number_format($stats['msgs_sent']), 'from-[#25D366] to-[#128C7E]'],
        ['fas fa-users',      'Contatos',           number_format($stats['contacts']),  'from-[#001644] to-[#022E6B]'],
        ['fas fa-paper-plane','Campanhas Enviadas',  $stats['sent'],                    'from-[#006633] to-[#022E6B]'],
        ['fas fa-edit',       'Rascunhos',           $stats['draft'],                   'from-[#BF8D1A] to-[#022E6B]'],
        ['fas fa-list',       'Total Campanhas',     $stats['total'],                   'from-[#022E6B] to-[#001644]'],
    ]; foreach ($statCards as [$ico,$lbl,$val,$grad]): ?>
    <div class="card p-4">
        <div class="w-9 h-9 bg-gradient-to-br <?= $grad ?> rounded-xl flex items-center justify-center text-white mb-3">
            <i class="<?= $ico ?> text-sm"></i>
        </div>
        <p class="text-xl font-black text-[#001644]"><?= $val ?></p>
        <p class="text-[10px] text-[#022E6B]/50 mt-0.5"><?= $lbl ?></p>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabs -->
<div class="flex gap-1 mb-5 bg-white rounded-xl p-1 border border-[#001644]/05 w-fit">
    <?php $tabs = [
        ['list',     'fas fa-list',        'Campanhas'],
        ['new',      'fas fa-plus-circle', 'Nova Campanha'],
        ['contacts', 'fas fa-address-book','Contatos'],
        ['config',   'fas fa-sliders-h',   'Configuração'],
    ]; foreach ($tabs as [$tid,$ticon,$tlabel]):
        $isActive  = ($action === $tid) || ($action === 'edit' && $tid === 'list');
        $isEditNew = ($action === 'edit' && $tid === 'new');
    ?>
    <button onclick="openTab('<?= $tid ?>')"
            id="tab-<?= $tid ?>"
            class="tab-btn flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition <?= $isActive||$isEditNew ? 'bg-[#001644] text-white shadow' : 'text-[#022E6B]/60 hover:bg-[#F0F4F8]' ?>">
        <i class="<?= $ticon ?> text-[10px]"></i> <?= $tlabel ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- ══ TAB: CAMPANHAS ══════════════════════════════════════════════════════ -->
<div id="panel-list" class="tab-panel <?= ($action==='list'||$action==='edit')?'':'hidden' ?>">
    <div class="card overflow-hidden">
        <table class="w-full">
            <thead><tr>
                <th class="text-left">Campanha</th>
                <th class="text-left">Tipo</th>
                <th class="text-center">Enviados</th>
                <th class="text-center">Falhas</th>
                <th class="text-center">Status</th>
                <th class="text-center">Data</th>
                <th class="text-center">Ações</th>
            </tr></thead>
            <tbody>
            <?php if (empty($campaigns)): ?>
                <tr><td colspan="7" class="text-center py-12">
                    <i class="fab fa-whatsapp text-4xl text-[#25D366]/20 block mb-3"></i>
                    <p class="text-sm font-semibold text-[#001644]/40">Nenhuma campanha criada</p>
                    <button onclick="openTab('new')" class="mt-3 btn-gold text-xs px-4 py-2">
                        <i class="fas fa-plus"></i> Criar primeira campanha
                    </button>
                </td></tr>
            <?php else: foreach ($campaigns as $c): ?>
                <tr>
                    <td>
                        <p class="font-semibold text-[#001644] text-xs"><?= h($c['name']) ?></p>
                        <p class="text-[10px] text-[#022E6B]/40 mt-0.5 line-clamp-1 max-w-[200px]"><?= h(mb_substr($c['message'],0,60)) ?>...</p>
                    </td>
                    <td>
                        <div class="flex gap-1 flex-wrap">
                            <?php if ($c['media_id']): ?><span class="badge badge-blue"><i class="fas fa-image mr-1 text-[9px]"></i>Imagem</span><?php endif; ?>
                            <?php if ($c['document_id']): ?><span class="badge badge-gold"><i class="fas fa-file-pdf mr-1 text-[9px]"></i>PDF</span><?php endif; ?>
                            <?php if ($c['link_url']): ?><span class="badge" style="background:#F3F4F6;color:#374151"><i class="fas fa-link mr-1 text-[9px]"></i>Link</span><?php endif; ?>
                            <?php if (!$c['media_id'] && !$c['document_id'] && !$c['link_url']): ?><span class="badge badge-gray"><i class="fas fa-font mr-1 text-[9px]"></i>Texto</span><?php endif; ?>
                        </div>
                    </td>
                    <td class="text-center"><span class="font-bold text-[#006633] text-sm"><?= number_format($c['sent_count']) ?></span></td>
                    <td class="text-center"><span class="font-bold text-red-500 text-sm"><?= number_format($c['failed_count']) ?></span></td>
                    <td class="text-center">
                        <?php $statusMap = [
                            'draft'     => ['badge-gray',  'Rascunho'],
                            'sending'   => ['badge-blue',  'Enviando...'],
                            'sent'      => ['badge-green', 'Enviado'],
                            'cancelled' => ['badge-red',   'Cancelado'],
                        ]; [$bc,$bl] = $statusMap[$c['status']] ?? ['badge-gray','Rascunho'];
                        // Normaliza status desconhecido para 'draft' na exibição
                        $isDraft = !in_array($c['status'], ['sending','sent','cancelled']);
                        ?>
                        <span class="badge <?= $bc ?>"><?= $bl ?></span>
                        <?php if ($c['status']==='sending'): ?>
                        <div class="mt-1 h-1 bg-gray-100 rounded-full overflow-hidden w-20 mx-auto">
                            <div class="h-full bg-[#25D366] w-0 transition-all" id="progBar-<?= $c['id'] ?>"></div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-[10px] text-[#022E6B]/50">
                        <?= $c['sent_at'] ? date('d/m H:i', strtotime($c['sent_at'])) : date('d/m', strtotime($c['created_at'])) ?>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <?php if ($isDraft): ?>
                            <button onclick="sendCampaign(<?= $c['id'] ?>, '<?= h($c['name']) ?>')"
                                    class="w-7 h-7 rounded-lg bg-[#25D366]/10 hover:bg-[#25D366] hover:text-white text-[#25D366] flex items-center justify-center text-xs transition" title="Disparar">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <a href="?action=edit&id=<?= $c['id'] ?>"
                               class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center text-xs transition" title="Editar">
                                <i class="fas fa-pencil-alt"></i></a>
                            <?php endif; ?>
                            <?php if ($c['status']==='sending'): ?>
                            <button onclick="pollProgress(<?= $c['id'] ?>)" class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs animate-pulse" title="Ver progresso">
                                <i class="fas fa-spinner fa-spin"></i>
                            </button>
                            <?php endif; ?>
                            <form method="POST" action="?action=delete" class="inline" onsubmit="return confirm('Remover campanha?')">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-400 flex items-center justify-center text-xs transition" title="Remover">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ TAB: NOVA / EDITAR CAMPANHA ════════════════════════════════════════ -->
<div id="panel-new" class="tab-panel <?= ($action==='new'||$action==='edit')?'':'hidden' ?>">
    <form method="POST" action="?action=save">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
        <div class="grid lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 space-y-4">
                <div class="card p-5">
                    <label class="form-label">Nome da Campanha *</label>
                    <input type="text" name="name" required value="<?= h($editing['name'] ?? '') ?>"
                           placeholder="Ex: Convite Assembleia Geral 2026" class="form-input">
                </div>
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-2">
                        <label class="form-label mb-0">Mensagem *</label>
                        <div class="flex gap-1">
                            <?php foreach (['{{nome}}'=>'Nome','{{data}}'=>'Data','{{ano}}'=>'Ano'] as $var=>$label): ?>
                            <button type="button" onclick="insertVar('<?= $var ?>')"
                                    class="px-2 py-0.5 text-[9px] font-bold bg-[#001644]/5 hover:bg-[#BF8D1A] hover:text-white text-[#022E6B] rounded-lg transition">
                                <?= $var ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <textarea name="message" id="wppMsg" required rows="7"
                              placeholder="Olá {{nome}}! 👋&#10;&#10;Temos novidades para você...&#10;&#10;Atenciosamente,&#10;CRCAP"
                              class="form-input resize-none font-mono text-sm leading-relaxed"><?= h($editing['message'] ?? '') ?></textarea>
                    <div class="flex items-center justify-between mt-2">
                        <p class="text-[9px] text-[#022E6B]/40">Use <code class="bg-gray-100 px-1 rounded">{{nome}}</code>, <code class="bg-gray-100 px-1 rounded">{{data}}</code>, <code class="bg-gray-100 px-1 rounded">{{ano}}</code></p>
                        <span id="charCount" class="text-[9px] text-[#022E6B]/40">0 caracteres</span>
                    </div>
                </div>
                <div class="card p-5">
                    <label class="form-label">Link (opcional)</label>
                    <div class="relative">
                        <i class="fas fa-link absolute left-3 top-1/2 -translate-y-1/2 text-[#BF8D1A] text-xs"></i>
                        <input type="url" name="link_url" value="<?= h($editing['link_url'] ?? '') ?>"
                               placeholder="https://crcap.org.br/eventos/assembleia-2026" class="form-input pl-8">
                    </div>
                </div>

                <?php if (($cfg['provider'] ?? 'evolution') === 'meta'): ?>
                <div class="card p-5 border-2 border-[#001644]/10" id="templateCard">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-8 h-8 rounded-xl bg-[#001644] flex items-center justify-center text-white flex-shrink-0">
                            <i class="fas fa-file-alt text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-[#001644]">Template Meta (obrigatório para disparos em massa)</p>
                            <p class="text-[10px] text-[#022E6B]/60 mt-0.5">
                                Sem template aprovado, a Meta bloqueia mensagens para contatos que não escreveram nas últimas 24h (erro 131026).
                            </p>
                        </div>
                    </div>
                    <input type="text" name="template_name"
                           value="<?= h($editing['template_name'] ?? '') ?>"
                           placeholder="Ex: aviso_evento  (nome exato no Meta Business Manager)"
                           class="form-input font-mono">
                    <div class="mt-2 text-[10px] text-[#022E6B]/50 space-y-0.5">
                        <p><i class="fas fa-info-circle text-[#BF8D1A] mr-1"></i>Os parâmetros <code class="bg-gray-100 px-1 rounded">{{nome}}</code>, <code class="bg-gray-100 px-1 rounded">{{email}}</code> e o link serão enviados como <code class="bg-gray-100 px-1 rounded">{{1}}</code>, <code class="bg-gray-100 px-1 rounded">{{2}}</code>, <code class="bg-gray-100 px-1 rounded">{{3}}</code> no template.</p>
                        <p><i class="fas fa-external-link-alt text-[#BF8D1A] mr-1"></i>
                            <a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank" class="text-[#001644] underline">Gerenciar templates no Meta Business Manager</a>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="space-y-4">
                <div class="card p-5">
                    <label class="form-label">Imagem (opcional)</label>
                    <select name="media_id" id="selMedia" class="form-input" onchange="previewMedia(this)">
                        <option value="">— Sem imagem —</option>
                        <?php foreach ($medias as $m): ?>
                        <option value="<?= $m['id'] ?>" data-src="<?= h($base.'/'.$m['file_path']) ?>"
                                <?= ($editing['media_id']??0)==$m['id']?'selected':'' ?>>
                            <?= h($m['title'] ?: basename($m['file_path'])) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="imgPreview" class="mt-3 <?= empty($editing['media_id'])?'hidden':'' ?>">
                        <?php if (!empty($editing['media_id'])):
                            $med = dbFetch($pdo,"SELECT file_path FROM media WHERE id=?",[$editing['media_id']]);
                            if ($med): ?>
                        <img src="<?= h($base.'/'.$med['file_path']) ?>" class="w-full h-32 object-cover rounded-xl border border-[#001644]/05">
                        <?php endif; endif; ?>
                    </div>
                </div>
                <div class="card p-5">
                    <label class="form-label">Documento PDF (opcional)</label>
                    <select name="document_id" class="form-input">
                        <option value="">— Sem documento —</option>
                        <?php foreach ($documents as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($editing['document_id']??0)==$d['id']?'selected':'' ?>>
                            <?= h($d['title'] ?: $d['file_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="card p-5">
                    <label class="form-label">Destinatários *</label>
                    <select name="recipient_group" id="recipGroup" class="form-input" onchange="toggleRecipGroup(this.value)">
                        <option value="whatsapp_list" <?= ($editing['recipient_group']??'')==='whatsapp_list'?'selected':'' ?>>
                            Lista WhatsApp (<?= number_format($stats['contacts']) ?>)
                        </option>
                        <option value="newsletter" <?= ($editing['recipient_group']??'')==='newsletter'?'selected':'' ?>>
                            Newsletter confirmada
                        </option>
                        <option value="manual" <?= ($editing['recipient_group']??'')==='manual'?'selected':'' ?>>
                            Números manuais
                        </option>
                    </select>
                    <div id="manualArea" class="mt-3 <?= ($editing['recipient_group']??'')==='manual'?'':'hidden' ?>">
                        <label class="form-label">Números (um por linha)</label>
                        <textarea name="manual_recipients" rows="5"
                                  placeholder="5596999990000&#10;5596988880000"
                                  class="form-input resize-none font-mono text-xs"><?= h($editing['manual_recipients'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="card p-5">
                    <label class="form-label">Preview WhatsApp</label>
                    <div class="bg-[#ECE5DD] rounded-xl p-3 min-h-[100px]">
                        <div class="bg-white rounded-xl p-3 shadow-sm max-w-[85%] relative">
                            <div class="absolute -left-1.5 top-2 w-3 h-3 bg-white" style="clip-path:polygon(100% 0,100% 100%,0 50%)"></div>
                            <p id="prevText" class="text-xs text-[#111B21] leading-relaxed whitespace-pre-wrap">Sua mensagem aparecerá aqui...</p>
                            <p class="text-[8px] text-gray-400 text-right mt-1">agora ✓✓</p>
                        </div>
                    </div>
                </div>
                <!-- Painel de regras anti-spam -->
                <div class="card p-4 text-[10px]">
                    <p class="font-bold text-[#001644] mb-2 flex items-center gap-1.5">
                        <i class="fas fa-shield-alt text-[#006633]"></i>
                        Regras de envio ativas
                        <span class="ml-auto px-2 py-0.5 rounded-full font-bold text-white text-[9px]
                            <?= ($cfg['provider']??'evolution')==='meta' ? 'bg-[#001644]' : 'bg-[#25D366]' ?>">
                            <?= strtoupper($cfg['provider'] ?? 'evolution') ?>
                        </span>
                    </p>
                    <?php if (($cfg['provider'] ?? 'evolution') === 'evolution'): ?>
                    <ul class="space-y-1 text-[#022E6B]/70">
                        <li><i class="fas fa-clock text-[#BF8D1A] w-3"></i> Delay: <strong>4s–12s aleatório</strong> entre msgs</li>
                        <li><i class="fas fa-layer-group text-[#BF8D1A] w-3"></i> Lotes: <strong>30 msgs</strong> → pausa de 45s</li>
                        <li><i class="fas fa-ban text-[#BF8D1A] w-3"></i> Limite: <strong>200 msgs/execução</strong></li>
                        <li><i class="fas fa-sun text-[#BF8D1A] w-3"></i> Horário: <strong>08h–20h</strong> apenas</li>
                        <li><i class="fab fa-whatsapp text-[#25D366] w-3"></i> "Digitando…" simulado antes de cada msg</li>
                    </ul>
                    <p class="mt-2 text-amber-600 bg-amber-50 rounded-lg p-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Use <strong>número dedicado</strong> para disparos. Risco de ban do número em volumes altos.
                    </p>
                    <?php else: ?>
                    <ul class="space-y-1 text-[#022E6B]/70">
                        <li><i class="fas fa-clock text-[#BF8D1A] w-3"></i> Delay: <strong>0.3s–1s</strong> (Meta gerencia)</li>
                        <li><i class="fas fa-layer-group text-[#BF8D1A] w-3"></i> Lotes: <strong>100 msgs</strong> → pausa de 3s</li>
                        <li><i class="fas fa-ban text-[#BF8D1A] w-3"></i> Limite: <strong>1000 msgs/dia</strong> (Tier 1)</li>
                        <li><i class="fas fa-globe text-[#BF8D1A] w-3"></i> Horário: <strong>sem restrição</strong></li>
                        <li><i class="fas fa-file-alt text-[#001644] w-3"></i> Template <strong>obrigatório</strong> para disparos frios</li>
                    </ul>
                    <p class="mt-2 text-[#006633] bg-[#006633]/10 rounded-lg p-2">
                        <i class="fas fa-check-circle mr-1"></i>
                        API oficial. Sem risco de ban. Cobrança por conversa iniciada.
                    </p>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-2">
                    <button type="submit" class="btn-gold w-full justify-center">
                        <i class="fas fa-save"></i> <?= $editing ? 'Atualizar Campanha' : 'Salvar Campanha' ?>
                    </button>
                    <?php if ($editing && $editing['status'] === 'draft'): ?>
                    <button type="button" onclick="sendCampaign(<?= $editing['id'] ?>, '<?= h($editing['name']) ?>')"
                            class="btn-primary w-full justify-center" style="background:#25D366">
                        <i class="fab fa-whatsapp"></i> Disparar Agora
                    </button>
                    <?php endif; ?>
                    <a href="?action=list" class="text-center text-xs text-[#022E6B]/40 hover:text-[#001644] mt-1">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- ══ TAB: CONTATOS ══════════════════════════════════════════════════════ -->
<div id="panel-contacts" class="tab-panel <?= $action==='contacts'?'':'hidden' ?>">
    <div class="grid lg:grid-cols-3 gap-5">
        <div class="card p-5">
            <h3 class="font-bold text-[#001644] text-sm mb-4 flex items-center gap-2">
                <i class="fas fa-upload text-[#BF8D1A]"></i> Importar Contatos
            </h3>
            <form method="POST" action="?action=import" enctype="multipart/form-data" class="space-y-3">
                <div>
                    <label class="form-label">Arquivo CSV</label>
                    <input type="file" name="csv" accept=".csv,.txt" required class="form-input text-xs py-2">
                </div>
                <div class="bg-[#F8FAFC] rounded-xl p-3 border border-[#001644]/05">
                    <p class="text-[9px] font-bold text-[#022E6B] mb-1">Formato:</p>
                    <code class="text-[9px] text-[#BF8D1A] block">telefone,nome</code>
                    <code class="text-[9px] text-[#022E6B]/50 block">5596999990000,João Silva</code>
                </div>
                <button type="submit" class="btn-primary w-full justify-center text-xs">
                    <i class="fas fa-upload"></i> Importar
                </button>
            </form>
            <div class="mt-4 pt-4 border-t border-[#001644]/05">
                <h4 class="text-xs font-bold text-[#001644] mb-2">Adicionar manualmente</h4>
                <div class="space-y-2">
                    <input type="text" id="newPhone" placeholder="Ex: 5596999990000" class="form-input text-xs">
                    <input type="text" id="newName"  placeholder="Nome (opcional)" class="form-input text-xs">
                    <button type="button" onclick="addContact()" class="btn-gold w-full justify-center text-xs">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                </div>
            </div>
        </div>
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="p-4 border-b border-[#001644]/05 flex items-center justify-between">
                <p class="text-sm font-bold text-[#001644]"><?= number_format($contacts_total) ?> contatos</p>
            </div>
            <table class="w-full">
                <thead><tr>
                    <th class="text-left">Telefone</th>
                    <th class="text-left">Nome</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Adicionado</th>
                    <th class="text-center">Ação</th>
                </tr></thead>
                <tbody>
                <?php if (empty($contacts)): ?>
                <tr><td colspan="5" class="text-center py-10 text-[#022E6B]/30 text-xs">
                    Nenhum contato. Importe um CSV para começar.
                </td></tr>
                <?php else: foreach ($contacts as $ct): ?>
                <tr id="ct-<?= $ct['id'] ?>">
                    <td><span class="font-mono text-xs"><?= h($ct['phone']) ?></span></td>
                    <td class="text-xs"><?= h($ct['name'] ?: '—') ?></td>
                    <td class="text-center">
                        <span class="badge <?= $ct['status']==='active'?'badge-green':'badge-gray' ?>">
                            <?= $ct['status']==='active'?'Ativo':'Inativo' ?>
                        </span>
                    </td>
                    <td class="text-center text-[10px] text-[#022E6B]/50"><?= date('d/m/Y', strtotime($ct['added_at'])) ?></td>
                    <td class="text-center">
                        <button onclick="removeContact(<?= $ct['id'] ?>)"
                                class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-400 flex items-center justify-center text-xs transition mx-auto">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══ TAB: CONFIGURAÇÃO ══════════════════════════════════════════════════ -->
<div id="panel-config" class="tab-panel <?= $action==='config'?'':'hidden' ?>">
    <div class="grid lg:grid-cols-2 gap-5">

        <form method="POST" action="?action=save_config" class="space-y-4">
            <div class="card p-6">
                <h3 class="font-bold text-[#001644] text-sm mb-5 flex items-center gap-2">
                    <i class="fab fa-whatsapp text-[#25D366]"></i> Configuração da API
                </h3>
                <div class="space-y-4">

                    <!-- Provider -->
                    <div>
                        <label class="form-label">Provider</label>
                        <select name="whatsapp_provider" id="cfgProvider" class="form-input" onchange="toggleProvider(this.value)">
                            <option value="evolution" <?= ($cfg['provider']??'')==='evolution'?'selected':'' ?>>
                                Evolution API (open source · recomendado para Brasil)
                            </option>
                            <option value="meta" <?= ($cfg['provider']??'evolution')==='meta'?'selected':'' ?>>
                                Meta Cloud API (oficial)
                            </option>
                        </select>
                    </div>

                    <!-- URL da API — campo corrigido -->
                    <div>
                        <label class="form-label">URL da API</label>
                        <input type="url" name="whatsapp_api_url" id="cfgApiUrl"
                               value="<?= h($cfgUrlDisplay) ?>"
                               placeholder="https://graph.facebook.com/v22.0"
                               class="form-input"
                               oninput="validateApiUrl(this)">
                        <p id="urlHint" class="text-[9px] text-[#022E6B]/40 mt-1">
                            <?php if (($cfg['provider']??'evolution') === 'meta'): ?>
                                Apenas a URL base: <code>https://graph.facebook.com/v22.0</code>
                            <?php else: ?>
                                Ex: <code>http://localhost:8080</code> para Evolution local
                            <?php endif; ?>
                        </p>
                        <!-- Aviso de URL errada (aparece em tempo real) -->
                        <div id="urlWarning" class="hidden mt-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg text-[10px] text-amber-700 flex items-start gap-2">
                            <i class="fas fa-exclamation-triangle mt-0.5 flex-shrink-0"></i>
                            <span>
                                A URL não deve conter o Phone Number ID ou <code>/messages</code>.<br>
                                <strong>Correto:</strong> <code>https://graph.facebook.com/v22.0</code>
                            </span>
                        </div>
                    </div>

                    <!-- API Key / Token -->
                    <div>
                        <label class="form-label">API Key / Token</label>
                        <div class="relative">
                            <input type="password" name="whatsapp_api_key" id="apiKeyInput"
                                   value="<?= h($cfg['api_key'] ?? '') ?>"
                                   class="form-input pr-10">
                            <button type="button" onclick="toggleApiKey()" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#022E6B]/30 hover:text-[#001644] transition text-xs">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Evolution: instância -->
                    <div id="evolutionFields" class="<?= ($cfg['provider']??'evolution')==='evolution'?'':'hidden' ?>">
                        <label class="form-label">Nome da Instância</label>
                        <input type="text" name="whatsapp_instance"
                               value="<?= h($cfg['instance'] ?? '') ?>"
                               placeholder="crcap-instance" class="form-input">
                        <p class="text-[9px] text-[#022E6B]/40 mt-1">Nome da instância criada na Evolution API</p>
                    </div>

                    <!-- Meta: Phone Number ID -->
                    <div id="metaFields" class="<?= ($cfg['provider']??'evolution')==='meta'?'':'hidden' ?>">
                        <label class="form-label">Phone Number ID</label>
                        <input type="text" name="whatsapp_phone_id"
                               value="<?= h($cfg['phone_id'] ?? '') ?>"
                               placeholder="1032740239921327" class="form-input">
                        <p class="text-[9px] text-[#022E6B]/40 mt-1">Encontrado no Meta for Developers → WhatsApp → Introdução</p>
                    </div>
                </div>

                <div class="flex gap-2 mt-5">
                    <button type="submit" class="btn-gold flex-1 justify-center">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                    <button type="button" onclick="testConnection()"
                            class="btn-primary flex-1 justify-center" style="background:#25D366">
                        <i class="fas fa-plug"></i> Testar Conexão
                    </button>
                </div>
            </div>
        </form>

        <!-- Teste de envio -->
        <div class="space-y-4">
            <div class="card p-6">
                <h3 class="font-bold text-[#001644] text-sm mb-4 flex items-center gap-2">
                    <i class="fas fa-vial text-[#BF8D1A]"></i> Envio de Teste
                </h3>
                <div class="space-y-3">
                    <div>
                        <label class="form-label">Número de Teste</label>
                        <input type="text" id="testPhone" placeholder="5596999990000" class="form-input font-mono">
                        <p class="text-[9px] text-[#022E6B]/40 mt-1">Com DDI (55) + DDD + número</p>
                    </div>
                    <div>
                        <label class="form-label">Mensagem de Teste</label>
                        <textarea id="testMsg" rows="4" class="form-input resize-none text-sm">✅ Teste de integração WhatsApp — CRCAP

Olá! Esta é uma mensagem de teste enviada pelo painel administrativo.

📅 Data: <?= date('d/m/Y H:i') ?></textarea>
                    </div>
                    <button onclick="sendTest()" class="btn-primary w-full justify-center" style="background:#25D366">
                        <i class="fab fa-whatsapp"></i> Enviar Mensagem de Teste
                    </button>
                    <div id="testResult" class="hidden text-xs p-3 rounded-xl"></div>
                </div>
            </div>

            <!-- Info de configuração Meta -->
            <div class="card p-5" id="metaGuide" style="<?= ($cfg['provider']??'evolution')!=='meta'?'display:none':'' ?>">
                <h4 class="font-bold text-[#001644] text-xs mb-3 flex items-center gap-2">
                    <i class="fas fa-info-circle text-[#BF8D1A]"></i> Campos Meta Cloud API
                </h4>
                <div class="space-y-2 text-[10px] text-[#022E6B]/70">
                    <div class="flex gap-2 items-start">
                        <span class="w-4 h-4 rounded-full bg-[#001644] text-white flex items-center justify-center text-[8px] flex-shrink-0 mt-0.5">1</span>
                        <span><strong>URL da API:</strong> <code class="bg-gray-100 px-1 rounded">https://graph.facebook.com/v22.0</code> (sem Phone ID)</span>
                    </div>
                    <div class="flex gap-2 items-start">
                        <span class="w-4 h-4 rounded-full bg-[#001644] text-white flex items-center justify-center text-[8px] flex-shrink-0 mt-0.5">2</span>
                        <span><strong>Token:</strong> Gerar token permanente via Sistema de Usuário no Meta Business Suite</span>
                    </div>
                    <div class="flex gap-2 items-start">
                        <span class="w-4 h-4 rounded-full bg-[#001644] text-white flex items-center justify-center text-[8px] flex-shrink-0 mt-0.5">3</span>
                        <span><strong>Phone Number ID:</strong> Meta for Developers → App → WhatsApp → Configuração → campo "De"</span>
                    </div>
                    <div class="flex gap-2 items-start">
                        <span class="w-4 h-4 rounded-full bg-[#BF8D1A] text-white flex items-center justify-center text-[8px] flex-shrink-0 mt-0.5">⚠</span>
                        <span>Em modo <strong>desenvolvimento</strong>, só é possível enviar para números cadastrados como testadores no painel Meta.</span>
                    </div>
                </div>
            </div>

            <div class="card p-5" id="evolutionGuide" style="<?= ($cfg['provider']??'evolution')!=='evolution'?'display:none':'' ?>">
                <h4 class="font-bold text-[#001644] text-xs mb-3 flex items-center gap-2">
                    <i class="fas fa-book text-[#BF8D1A]"></i> Guia Rápido — Evolution API
                </h4>
                <ol class="space-y-2 text-[10px] text-[#022E6B]/70">
                    <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-[#001644] text-white flex items-center justify-center text-[8px] flex-shrink-0 mt-0.5">1</span>Instale a Evolution API: <code class="bg-gray-100 px-1 rounded text-[8px]">docker run evolutionapi/evolution-api</code></li>
                    <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-[#001644] text-white flex items-center justify-center text-[8px] flex-shrink-0 mt-0.5">2</span>Acesse o painel em <code class="bg-gray-100 px-1 rounded text-[8px]">:8080/manager</code></li>
                    <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-[#001644] text-white flex items-center justify-center text-[8px] flex-shrink-0 mt-0.5">3</span>Crie uma instância e escaneie o QR Code</li>
                    <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-[#BF8D1A] text-white flex items-center justify-center text-[8px] flex-shrink-0 mt-0.5">✓</span>Cole URL, API Key e instância acima e clique Testar Conexão</li>
                </ol>
            </div>
        </div>
    </div>
</div>

</div><!-- /p-6 -->
</div><!-- /flex-1 -->

<!-- Modal: Progresso de Disparo -->
<div id="sendModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-2xl p-8 w-full max-w-sm mx-4 shadow-2xl text-center">
        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#25D366] to-[#128C7E] flex items-center justify-center text-white text-3xl mx-auto mb-4 shadow-lg shadow-[#25D366]/30">
            <i class="fab fa-whatsapp"></i>
        </div>
        <h3 class="font-bold text-[#001644] text-lg mb-1" id="modalTitle">Disparando...</h3>
        <p class="text-xs text-[#022E6B]/50 mb-5" id="modalSub">Aguarde enquanto as mensagens são enviadas</p>
        <div class="h-2 bg-[#F0F4F8] rounded-full overflow-hidden mb-2">
            <div id="modalBar" class="h-full bg-gradient-to-r from-[#25D366] to-[#128C7E] rounded-full transition-all duration-500" style="width:0%"></div>
        </div>
        <p class="text-[10px] text-[#022E6B]/40 mb-5" id="modalPct">Preparando...</p>
        <div id="modalResult" class="hidden">
            <div id="modalResultBox" class="text-sm p-3 rounded-xl mb-4"></div>
            <button onclick="document.getElementById('sendModal').classList.add('hidden');location.reload()" class="btn-gold w-full justify-center">
                <i class="fas fa-check"></i> Concluído
            </button>
        </div>
    </div>
</div>

<script>
// ── Tabs ────────────────────────────────────────────────────────────────────
function openTab(id) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-[#001644]','text-white','shadow');
        b.classList.add('text-[#022E6B]/60','hover:bg-[#F0F4F8]');
    });
    var panel = document.getElementById('panel-'+id);
    if (panel) panel.classList.remove('hidden');
    var btn = document.getElementById('tab-'+id);
    if (btn) { btn.classList.add('bg-[#001644]','text-white','shadow'); btn.classList.remove('text-[#022E6B]/60','hover:bg-[#F0F4F8]'); }
}

// ── Preview em tempo real ────────────────────────────────────────────────────
var msgEl  = document.getElementById('wppMsg');
var prevEl = document.getElementById('prevText');
var charEl = document.getElementById('charCount');

function updatePreview() {
    if (!msgEl || !prevEl) return;
    var txt = msgEl.value
        .replace(/\{\{nome\}\}/g,'João Silva')
        .replace(/\{\{data\}\}/g,'<?= date('d/m/Y') ?>')
        .replace(/\{\{ano\}\}/g,'<?= date('Y') ?>');
    prevEl.textContent = txt || 'Sua mensagem aparecerá aqui...';
    if (charEl) charEl.textContent = msgEl.value.length + ' caracteres';
}
if (msgEl) { msgEl.addEventListener('input', updatePreview); updatePreview(); }

function insertVar(v) {
    if (!msgEl) return;
    var s = msgEl.selectionStart, e = msgEl.selectionEnd;
    msgEl.value = msgEl.value.slice(0,s) + v + msgEl.value.slice(e);
    msgEl.selectionStart = msgEl.selectionEnd = s + v.length;
    msgEl.focus(); updatePreview();
}

function previewMedia(sel) {
    var src = sel.options[sel.selectedIndex].getAttribute('data-src');
    var box = document.getElementById('imgPreview');
    if (src) { box.classList.remove('hidden'); box.innerHTML = '<img src="'+src+'" class="w-full h-32 object-cover rounded-xl">'; }
    else box.classList.add('hidden');
}

function toggleRecipGroup(v) {
    document.getElementById('manualArea').classList.toggle('hidden', v !== 'manual');
}

// ── Validação de URL da API em tempo real ───────────────────────────────────
function validateApiUrl(input) {
    var v   = input.value.trim();
    var warn = document.getElementById('urlWarning');
    if (!warn) return;
    // Detecta se contém phoneId (sequência de 10+ dígitos) ou /messages
    var hasBad = /\/\d{10,}/.test(v) || /\/messages/.test(v);
    if (hasBad) {
        warn.classList.remove('hidden');
        // Auto-corrige ao perder foco (ou pressionar Enter)
        input.dataset.badUrl = '1';
    } else {
        warn.classList.add('hidden');
        input.dataset.badUrl = '0';
    }
}

// Auto-corrige a URL antes de submeter o formulário
document.querySelector('form[action*="save_config"]')?.addEventListener('submit', function() {
    var inp = document.getElementById('cfgApiUrl');
    if (!inp) return;
    var v = inp.value.trim().replace(/\/$/, '');
    v = v.replace(/\/\d{10,}\/messages$/, '');
    v = v.replace(/\/\d{10,}$/, '');
    inp.value = v.replace(/\/$/, '');
});

// Checar URL atual ao carregar
var cfgApiUrl = document.getElementById('cfgApiUrl');
if (cfgApiUrl && cfgApiUrl.value) validateApiUrl(cfgApiUrl);

// ── Toggle provider ──────────────────────────────────────────────────────────
function toggleProvider(v) {
    document.getElementById('evolutionFields').classList.toggle('hidden', v !== 'evolution');
    document.getElementById('metaFields').classList.toggle('hidden', v !== 'meta');
    var hint = document.getElementById('urlHint');
    if (hint) hint.innerHTML = v === 'meta'
        ? 'Apenas a URL base: <code>https://graph.facebook.com/v22.0</code>'
        : 'Ex: <code>http://localhost:8080</code> para Evolution local';
    var mg = document.getElementById('metaGuide');
    var eg = document.getElementById('evolutionGuide');
    if (mg) mg.style.display = v === 'meta' ? '' : 'none';
    if (eg) eg.style.display = v === 'evolution' ? '' : 'none';
    // Atualizar placeholder da URL
    var urlInput = document.getElementById('cfgApiUrl');
    if (urlInput) urlInput.placeholder = v === 'meta'
        ? 'https://graph.facebook.com/v22.0'
        : 'http://seu-servidor:8080';
}

// ── Disparar campanha ────────────────────────────────────────────────────────
function sendCampaign(id, name) {
    if (!confirm('Disparar a campanha "' + name + '" para todos os destinatários?')) return;
    var modal = document.getElementById('sendModal');
    var bar   = document.getElementById('modalBar');
    var pct   = document.getElementById('modalPct');
    var res   = document.getElementById('modalResult');
    var title = document.getElementById('modalTitle');
    modal.classList.remove('hidden');
    bar.style.width = '5%'; pct.textContent = 'Iniciando...';
    res.classList.add('hidden'); title.textContent = 'Disparando "' + name + '"';

    fetch('<?= $base ?>/api/whatsapp-send.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'send', campaign_id: id})
    }).then(r => r.json()).then(data => {
        bar.style.width = '100%'; res.classList.remove('hidden');
        var box = document.getElementById('modalResultBox');
        if (data.success) {
            box.className = 'text-sm p-3 rounded-xl mb-4 bg-green-50 text-green-800 border border-green-200';
            box.innerHTML = '<i class="fas fa-check-circle mr-2"></i><strong>' + data.message + '</strong>';
            title.textContent = 'Campanha Enviada! ✅';
        } else {
            box.className = 'text-sm p-3 rounded-xl mb-4 bg-red-50 text-red-800 border border-red-200';
            box.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message;
            title.textContent = 'Erro no disparo';
        }
    }).catch(err => {
        res.classList.remove('hidden');
        var box = document.getElementById('modalResultBox');
        box.className = 'text-sm p-3 rounded-xl mb-4 bg-red-50 text-red-800 border border-red-200';
        box.textContent = 'Erro de conexão: ' + err.message;
        title.textContent = 'Falha';
    });

    var pollInterval = setInterval(function() {
        fetch('<?= $base ?>/api/whatsapp-send.php', {
            method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
            body: JSON.stringify({action:'stats', campaign_id: id})
        }).then(r=>r.json()).then(d => {
            if (d.success) {
                bar.style.width = Math.max(d.percent||0,5) + '%';
                pct.textContent = d.sent + ' enviados, ' + d.failed + ' falhas (' + (d.percent||0) + '%)';
                if (d.status !== 'sending') clearInterval(pollInterval);
            }
        }).catch(() => clearInterval(pollInterval));
    }, 2500);
}

// ── Verificar status ─────────────────────────────────────────────────────────
function checkStatus() {
    var el = document.getElementById('wppStatus');
    el.className = 'flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-[#001644]/05 text-xs text-[#022E6B]/60';
    el.innerHTML = '<span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span><span>Verificando...</span>';

    fetch('<?= $base ?>/api/whatsapp-send.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
        credentials: 'same-origin',
        body: JSON.stringify({action:'status'})
    })
    .then(r => {
        // Captura resposta bruta para diagnóstico
        var ct = r.headers.get('content-type') || '';
        if (!ct.includes('json')) {
            return r.text().then(txt => { throw new Error('Resposta não-JSON: ' + txt.substring(0,120)); });
        }
        return r.json();
    })
    .then(data => {
        // Suporta 2 formatos:
        // { success:true, status:{ connected:true, message:'...' } }  ← whatsapp-send.php
        // { connected:true, message:'...' }                           ← debug direto
        var s = (data.status !== undefined) ? data.status : data;

        if (s.connected) {
            el.className = 'flex items-center gap-2 px-3 py-1.5 rounded-lg bg-green-50 border border-green-200 text-xs text-green-700';
            el.innerHTML = '<span class="w-2 h-2 rounded-full bg-[#25D366]"></span><span>' + (s.message || 'Conectado') + '</span>';
        } else {
            var errMsg = s.message || data.message || 'Desconectado';
            el.className = 'flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-xs text-red-600';
            el.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-400"></span><span title="' + errMsg + '">' + errMsg.substring(0,50) + '</span>';
        }
    })
    .catch(err => {
        el.className = 'flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-700';
        el.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-400"></span><span title="' + err.message + '">API: ' + err.message.substring(0,60) + '</span>';
        console.error('checkStatus error:', err);
    });
}

function testConnection() { checkStatus(); openTab('config'); }

// ── Envio de teste ───────────────────────────────────────────────────────────
function sendTest() {
    var phone = document.getElementById('testPhone').value.trim();
    var msg   = document.getElementById('testMsg').value.trim();
    var res   = document.getElementById('testResult');
    if (!phone) { alert('Informe o número de teste.'); return; }
    res.className = 'text-xs p-3 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-700';
    res.textContent = '⏳ Enviando...'; res.classList.remove('hidden');
    fetch('<?= $base ?>/api/whatsapp-send.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
        body: JSON.stringify({action:'test', test_phone: phone, message: msg})
    }).then(r=>r.json()).then(data => {
        if (data.success) {
            res.className = 'text-xs p-3 rounded-xl bg-green-50 border border-green-200 text-green-700';
            res.innerHTML = '<i class="fas fa-check-circle mr-1"></i>' + data.message;
        } else {
            res.className = 'text-xs p-3 rounded-xl bg-red-50 border border-red-200 text-red-700';
            res.innerHTML = '<i class="fas fa-times-circle mr-1"></i>' + data.message;
        }
    });
}

// ── Toggle API Key visibilidade ──────────────────────────────────────────────
function toggleApiKey() {
    var inp  = document.getElementById('apiKeyInput');
    var icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') { inp.type = 'text'; icon.className = 'fas fa-eye-slash'; }
    else { inp.type = 'password'; icon.className = 'fas fa-eye'; }
}

// ── Contatos: AJAX ───────────────────────────────────────────────────────────
function addContact() {
    var phone = document.getElementById('newPhone').value.trim();
    var name  = document.getElementById('newName').value.trim();
    if (!phone) { alert('Informe o número.'); return; }
    fetch('<?= $base ?>/api/whatsapp-send.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
        body: JSON.stringify({action:'add_contact', phone, name})
    }).then(r=>r.json()).then(data => {
        if (data.success) { document.getElementById('newPhone').value=''; document.getElementById('newName').value=''; location.reload(); }
        else alert(data.message);
    });
}

function removeContact(id) {
    if (!confirm('Remover este contato?')) return;
    fetch('<?= $base ?>/api/whatsapp-send.php', {
        method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
        body: JSON.stringify({action:'remove_contact', id})
    }).then(r=>r.json()).then(data => {
        if (data.success) { var row = document.getElementById('ct-'+id); if (row) row.remove(); }
        else alert(data.message);
    });
}

function pollProgress(id) {
    openTab('list');
    var bar = document.getElementById('progBar-'+id);
    if (!bar) return;
    var iv = setInterval(function() {
        fetch('<?= $base ?>/api/whatsapp-send.php',{
            method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin',
            body:JSON.stringify({action:'stats',campaign_id:id})
        }).then(r=>r.json()).then(d=>{
            if (d.success && bar) {
                bar.style.width = d.percent + '%';
                if (d.status !== 'sending') { clearInterval(iv); location.reload(); }
            }
        }).catch(()=>clearInterval(iv));
    }, 3000);
}

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    checkStatus();
    document.querySelectorAll('[id^="progBar-"]').forEach(el => {
        pollProgress(parseInt(el.id.replace('progBar-','')));
    });
});
</script>

<?php echo '</div></body></html>'; ?>