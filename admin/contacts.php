<?php
// ── Handle POST AJAX antes de qualquer output HTML ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send_reply') {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/mailer.php';
    header('Content-Type: application/json; charset=utf-8');

    // Migração: adicionar coluna reply_message se não existir
    try { $pdo->exec("ALTER TABLE contacts ADD COLUMN reply_message TEXT NULL AFTER replied_by"); } catch (Exception $e) {}

    if (!isAdmin()) {
        echo json_encode(['success'=>false,'message'=>'Sem permissão.']); exit;
    }

    $adminUser = currentUser();
    $contactId = (int)($_POST['contact_id'] ?? 0);
    $replyText = trim($_POST['reply_body'] ?? '');

    if (!$contactId || !$replyText) {
        echo json_encode(['success'=>false,'message'=>'Preencha a mensagem de resposta.']); exit;
    }

    $contact = dbFetch($pdo, "SELECT * FROM contacts WHERE id=?", [$contactId]);
    if (!$contact) {
        echo json_encode(['success'=>false,'message'=>'Contato não encontrado.']); exit;
    }

    $mailer  = mailer();
    $subject = 'Re: ' . ($contact['subject'] ?: 'Sua mensagem para o CRCAP');
    $html    = $mailer->wrapTemplate(
        'Resposta à sua mensagem',
        '<p>Olá, <strong>' . htmlspecialchars($contact['name']) . '</strong>!</p>
         <p>' . nl2br(htmlspecialchars($replyText)) . '</p>
         <hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0">
         <p style="font-size:12px;color:#94a3b8">Em resposta à sua mensagem:<br>
         <em>' . htmlspecialchars(mb_substr($contact['message'], 0, 200)) . (mb_strlen($contact['message']) > 200 ? '…' : '') . '</em></p>',
        'Acessar Portal CRCAP', 'https://crcap.org.br'
    );

    $ok = $mailer->send($contact['email'], $contact['name'], $subject, $html, strip_tags($replyText));

    if ($ok) {
        dbExec($pdo,
            "UPDATE contacts SET status='replied', replied_at=NOW(), replied_by=?, reply_message=? WHERE id=?",
            [$adminUser['id'], $replyText, $contactId]);
        echo json_encode(['success'=>true,'message'=>'Resposta enviada para ' . $contact['email']]);
    } else {
        $errs = $mailer->getErrors();
        echo json_encode(['success'=>false,'message'=>'Falha: ' . implode(', ', $errs ?: ['Configure o SMTP em Admin → SMTP/Email.'])]);
    }
    exit;
}

// ── Ações GET que fazem redirect (antes de qualquer output) ──────────────────
$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($action && $id && in_array($action, ['read','reply','archive','delete'])) {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    if (!isAdmin()) { header('Location: /crcap/pages/login.php'); exit; }
    $adminUser = currentUser();

    $statusMap = ['read'=>'read','reply'=>'replied','archive'=>'archived'];
    if (isset($statusMap[$action])) {
        $extra = $action === 'reply' ? ', replied_at=NOW(), replied_by=' . (int)$adminUser['id'] : '';
        dbExec($pdo, "UPDATE contacts SET status=? $extra WHERE id=?", [$statusMap[$action], $id]);
    }
    if ($action === 'delete') dbExec($pdo, "DELETE FROM contacts WHERE id=?", [$id]);

    // Redirect back preserving filter
    $filter = $_GET['filter'] ?? 'all';
    header('Location: /crcap/admin/contacts.php?filter=' . urlencode($filter)); exit;
}

// ── Página normal ─────────────────────────────────────────────────────────────
$pageTitle = 'Contatos Recebidos · Admin CRCAP';
$activeAdm = 'contacts';
require_once __DIR__ . '/admin_header.php';

// Filtros
$filter = $_GET['filter'] ?? 'new';
$busca  = trim($_GET['busca'] ?? '');
$page_n = max(1,(int)($_GET['p'] ?? 1));
$perP   = 20; $off = ($page_n-1)*$perP;

$where = ['1=1']; $params = [];
if ($filter && $filter !== 'all') { $where[] = 'status=?'; $params[] = $filter; }
if ($busca) { $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)'; for($i=0;$i<4;$i++) $params[] = "%$busca%"; }

$contacts = dbFetchAll($pdo, "SELECT * FROM contacts WHERE ".implode(' AND ',$where)." ORDER BY created_at DESC LIMIT $perP OFFSET $off", $params);
$total    = dbFetch($pdo, "SELECT COUNT(*) AS n FROM contacts WHERE ".implode(' AND ',$where), $params)['n'] ?? 0;
$pages    = ceil($total / $perP);

// Contagem por status
$counts = dbFetchAll($pdo, "SELECT status, COUNT(*) AS n FROM contacts GROUP BY status");
$cMap   = array_column($counts, 'n', 'status');

// Abrir mensagem
$openMsg = $id ? dbFetch($pdo, "SELECT * FROM contacts WHERE id=?", [$id]) : null;
if ($openMsg && $openMsg['status'] === 'new') {
    dbExec($pdo, "UPDATE contacts SET status='read' WHERE id=?", [$id]);
    $openMsg['status'] = 'read';
}
?>

<div class="flex items-center justify-between mb-5">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2"><i class="fas fa-inbox text-[#BF8D1A]"></i>Contatos Recebidos</h2>
    <div class="flex items-center gap-2 text-xs">
        <?php if ($cMap['new'] ?? 0): ?><span class="badge badge-gold"><?= $cMap['new'] ?> novas</span><?php endif; ?>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <!-- Lista -->
    <div class="lg:col-span-<?= $openMsg ? '1' : '3' ?>">
        <!-- Filtros -->
        <div class="card p-4 mb-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex gap-1">
                    <?php $filters = ['all'=>'Todas','new'=>'Novas','read'=>'Lidas','replied'=>'Respondidas','archived'=>'Arquivadas'];
                    foreach ($filters as $fv => $fl): $cnt = $fv==='all' ? array_sum($cMap) : ($cMap[$fv]??0); ?>
                    <a href="?filter=<?= $fv ?>&busca=<?= urlencode($busca) ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $filter===$fv ? 'bg-[#001644] text-white' : 'bg-[#F8FAFC] text-[#022E6B] hover:bg-[#001644]/10' ?>">
                        <?= $fl ?> <?php if ($cnt): ?><span class="ml-1 text-[9px] opacity-70">(<?= $cnt ?>)</span><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <form method="GET" class="ml-auto flex gap-2">
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                    <div class="relative"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#022E6B]/30 text-xs"></i><input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar..." class="form-input pl-8 w-48 py-2 text-xs"></div>
                    <button class="btn-primary py-2 px-3 text-xs"><i class="fas fa-filter"></i></button>
                </form>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card overflow-hidden">
            <?php if (empty($contacts)): ?>
            <div class="text-center py-16 text-[#001644]/30"><i class="fas fa-inbox text-4xl mb-3 block"></i><p class="font-semibold">Nenhuma mensagem</p></div>
            <?php else: ?>
            <div class="divide-y divide-[#001644]/5">
                <?php foreach ($contacts as $c):
                    $isNew = $c['status'] === 'new'; ?>
                <div class="flex items-start gap-3 p-4 <?= $isNew ? 'bg-[#BF8D1A]/5' : '' ?> hover:bg-[#F8FAFC] transition group">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-xs font-bold flex-shrink-0"><?= strtoupper(substr($c['name'],0,1)) ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <a href="?filter=<?= $filter ?>&id=<?= $c['id'] ?>" class="font-<?= $isNew?'bold':'semibold' ?> text-[#001644] text-xs hover:text-[#BF8D1A] transition"><?= htmlspecialchars($c['name']) ?></a>
                            <span class="badge badge-<?= $c['status']==='new'?'gold':($c['status']==='replied'?'green':'gray') ?>"><?= $c['status'] ?></span>
                            <?php if ($c['department']): ?><span class="badge badge-blue"><?= htmlspecialchars($c['department']) ?></span><?php endif; ?>
                        </div>
                        <p class="text-[10px] text-[#022E6B] font-<?= $isNew?'semibold':'normal' ?> truncate"><?= htmlspecialchars($c['subject'] ?: substr($c['message'],0,60)) ?></p>
                        <p class="text-[9px] text-[#022E6B]/50"><?= htmlspecialchars($c['email']) ?> · <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></p>
                    </div>
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                        <a href="?filter=<?= $filter ?>&action=reply&id=<?= $c['id'] ?>" class="w-6 h-6 rounded-lg bg-[#006633]/10 hover:bg-[#006633] hover:text-white text-[#006633] flex items-center justify-center text-[9px] transition" title="Marcar respondido"><i class="fas fa-check"></i></a>
                        <a href="?filter=<?= $filter ?>&action=archive&id=<?= $c['id'] ?>" class="w-6 h-6 rounded-lg bg-[#022E6B]/10 hover:bg-[#022E6B] hover:text-white text-[#022E6B] flex items-center justify-center text-[9px] transition" title="Arquivar"><i class="fas fa-archive"></i></a>
                        <a href="?filter=<?= $filter ?>&action=delete&id=<?= $c['id'] ?>" onclick="return confirm('Excluir esta mensagem?')" class="w-6 h-6 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center text-[9px] transition" title="Excluir"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
            <div class="flex justify-center gap-2 p-4 border-t border-[#001644]/5">
                <?php for ($i=1;$i<=$pages;$i++): ?><a href="?filter=<?= $filter ?>&p=<?= $i ?>&busca=<?= urlencode($busca) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-semibold transition <?= $i===$page_n?'bg-[#001644] text-white':'bg-[#F8FAFC] text-[#001644] hover:bg-[#001644]/10' ?>"><?= $i ?></a><?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensagem aberta -->
    <?php if ($openMsg): ?>
    <div class="lg:col-span-2">
        <div class="card p-6 sticky top-24">
            <div class="flex items-start justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white font-bold text-lg"><?= strtoupper(substr($openMsg['name'],0,1)) ?></div>
                    <div>
                        <h3 class="font-bold text-[#001644]"><?= htmlspecialchars($openMsg['name']) ?></h3>
                        <a href="mailto:<?= htmlspecialchars($openMsg['email']) ?>" class="text-xs text-[#BF8D1A] hover:underline"><?= htmlspecialchars($openMsg['email']) ?></a>
                        <?php if ($openMsg['phone']): ?><p class="text-xs text-[#022E6B]"><?= htmlspecialchars($openMsg['phone']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="?filter=<?= $filter ?>&action=reply&id=<?= $openMsg['id'] ?>" class="btn-primary py-2 px-3"><i class="fas fa-check"></i>Marcar respondido</a>
                    <a href="?filter=<?= $filter ?>" class="px-3 py-2 border border-[#001644]/10 rounded-xl text-xs font-medium text-[#022E6B] hover:bg-[#F8FAFC] transition"><i class="fas fa-times"></i></a>
                </div>
            </div>

            <div class="bg-[#F8FAFC] rounded-xl p-4 mb-5">
                <div class="flex items-center gap-2 mb-2">
                    <?php if ($openMsg['subject']): ?><h4 class="font-semibold text-[#001644] text-sm"><?= htmlspecialchars($openMsg['subject']) ?></h4><?php endif; ?>
                    <?php if ($openMsg['department']): ?><span class="badge badge-blue"><?= htmlspecialchars($openMsg['department']) ?></span><?php endif; ?>
                </div>
                <p class="text-sm text-[#022E6B] leading-relaxed"><?= nl2br(htmlspecialchars($openMsg['message'])) ?></p>
                <p class="text-[10px] text-[#022E6B]/50 mt-3"><?= date('d/m/Y \à\s H:i', strtotime($openMsg['created_at'])) ?></p>
            </div>

            <!-- Resposta enviada (se houver) -->
            <?php if (!empty($openMsg['reply_message'])): ?>
            <div class="bg-[#006633]/5 border border-[#006633]/20 rounded-xl p-4 mb-5">
                <h4 class="text-xs font-bold text-[#006633] mb-2 flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> Resposta enviada
                    <?php if ($openMsg['replied_at']): ?>
                    <span class="font-normal text-[#022E6B]/50"><?= date('d/m/Y \à\s H:i', strtotime($openMsg['replied_at'])) ?></span>
                    <?php endif; ?>
                </h4>
                <p class="text-xs text-[#022E6B] leading-relaxed whitespace-pre-line"><?= htmlspecialchars($openMsg['reply_message']) ?></p>
            </div>
            <?php endif; ?>

            <!-- Responder via e-mail -->
            <div class="border border-[#001644]/10 rounded-xl p-4">
                <h4 class="text-xs font-bold text-[#001644] mb-3 flex items-center gap-2">
                    <i class="fas fa-reply text-[#BF8D1A]"></i>
                    Responder por E-mail
                    <span class="text-[10px] font-normal text-[#022E6B]/60">→ <?= htmlspecialchars($openMsg['email']) ?></span>
                </h4>
                <textarea id="replyBody" rows="5" class="form-input resize-none mb-3 text-xs"
                    placeholder="Digite sua resposta aqui..."></textarea>
                <div id="replyFeedback" class="hidden text-xs rounded-lg px-3 py-2 mb-3"></div>
                <div class="flex gap-2">
                    <button onclick="sendReply(<?= $openMsg['id'] ?>)"
                            id="replyBtn"
                            class="btn-gold flex-1 justify-center">
                        <i class="fas fa-paper-plane"></i>Enviar Resposta
                    </button>
                    <a href="mailto:<?= htmlspecialchars($openMsg['email']) ?>?subject=Re:+<?= urlencode($openMsg['subject'] ?: 'Contato CRCAP') ?>"
                       class="px-4 py-2 text-xs font-semibold text-[#022E6B] border border-[#001644]/20 rounded-xl hover:bg-[#F0F4F8] transition flex items-center gap-1"
                       title="Abrir no cliente de e-mail">
                        <i class="fas fa-external-link-alt text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
async function sendReply(contactId) {
    const body = document.getElementById('replyBody').value.trim();
    const btn  = document.getElementById('replyBtn');
    const fb   = document.getElementById('replyFeedback');

    if (!body) {
        fb.className = 'text-xs rounded-lg px-3 py-2 mb-3 bg-yellow-50 text-yellow-700 border border-yellow-200';
        fb.textContent = '⚠️ Digite a mensagem antes de enviar.';
        fb.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    fb.classList.add('hidden');

    const fd = new FormData();
    fd.append('action', 'send_reply');
    fd.append('contact_id', contactId);
    fd.append('reply_body', body);

    try {
        const r    = await fetch(window.location.pathname, { method: 'POST', body: fd });
        const data = await r.json();

        fb.className = 'text-xs rounded-lg px-3 py-2 mb-3 ' +
            (data.success
                ? 'bg-green-50 text-green-700 border border-green-200'
                : 'bg-red-50 text-red-700 border border-red-200');
        fb.textContent = (data.success ? '✅ ' : '❌ ') + data.message;
        fb.classList.remove('hidden');

        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Enviado!';
            btn.className = btn.className.replace('btn-gold','btn-primary');
            document.getElementById('replyBody').value = '';
            // Update badge in list after 1s
            setTimeout(() => location.reload(), 1500);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i>Enviar Resposta';
        }
    } catch(e) {
        fb.className = 'text-xs rounded-lg px-3 py-2 mb-3 bg-red-50 text-red-700 border border-red-200';
        fb.textContent = '❌ Erro de comunicação: ' + e.message;
        fb.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i>Enviar Resposta';
    }
}
</script>
<?php require_once __DIR__ . '/admin_footer.php'; ?>