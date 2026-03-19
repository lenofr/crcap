<?php
$pageTitle = 'Eventos · Admin CRCAP';
$activeAdm = 'events';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$adminUser = currentUser();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ── DELETE EVENTO ────────────────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    dbExec($pdo, "DELETE FROM events WHERE id=?", [$id]);
    header('Location: /crcap/admin/events.php?msg=deleted'); exit;
}

// ── TOGGLE STATUS ────────────────────────────────────────────────────────────
if ($action === 'toggle' && $id) {
    $cur = dbFetch($pdo, "SELECT status FROM events WHERE id=?", [$id]);
    $new = $cur['status'] === 'published' ? 'draft' : 'published';
    dbExec($pdo, "UPDATE events SET status=? WHERE id=?", [$new, $id]);
    header('Location: /crcap/admin/events.php?msg=updated'); exit;
}

// ── DELETE INSCRIÇÃO ─────────────────────────────────────────────────────────
if ($action === 'del_reg' && $id) {
    $evId = (int)($_GET['ev'] ?? 0);
    dbExec($pdo, "DELETE FROM event_registrations WHERE id=?", [$id]);
    // Atualiza contador
    if ($evId) dbExec($pdo,
        "UPDATE events SET current_participants = (SELECT COUNT(*) FROM event_registrations WHERE event_id=? AND status!='cancelled') WHERE id=?",
        [$evId, $evId]);
    header("Location: /crcap/admin/events.php?action=registrations&id={$evId}&msg=reg_deleted"); exit;
}

// ── ALTERAR STATUS DA INSCRIÇÃO ──────────────────────────────────────────────
if ($action === 'reg_status' && $id) {
    $evId   = (int)($_GET['ev']     ?? 0);
    $status = $_GET['status'] ?? '';
    $allowed = ['pending','confirmed','cancelled','attended'];
    if (in_array($status, $allowed)) {
        $confirmedAt = ($status === 'confirmed' || $status === 'attended') ? date('Y-m-d H:i:s') : null;
        dbExec($pdo,
            "UPDATE event_registrations SET status=?, confirmed_at=? WHERE id=?",
            [$status, $confirmedAt, $id]);
        if ($evId) dbExec($pdo,
            "UPDATE events SET current_participants = (SELECT COUNT(*) FROM event_registrations WHERE event_id=? AND status!='cancelled') WHERE id=?",
            [$evId, $evId]);
    }
    header("Location: /crcap/admin/events.php?action=registrations&id={$evId}&msg=reg_updated"); exit;
}

// ── SALVAR EDIÇÃO DE INSCRIÇÃO ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_reg_edit'])) {
    $regId   = (int)($_POST['reg_id'] ?? 0);
    $evId    = (int)($_POST['ev_id']  ?? 0);
    if ($regId) {
        dbExec($pdo,
            "UPDATE event_registrations SET
                name=?, email=?, phone=?, cpf=?, company=?, position=?,
                status=?, payment_status=?, additional_info=?
             WHERE id=?",
            [
                trim($_POST['name']          ?? ''),
                trim($_POST['email']         ?? ''),
                preg_replace('/\D/','',$_POST['phone'] ?? ''),
                preg_replace('/\D/','',$_POST['cpf']   ?? ''),
                trim($_POST['company']       ?? ''),
                trim($_POST['position']      ?? ''),
                $_POST['status']             ?? 'pending',
                $_POST['payment_status']     ?? 'pending',
                trim($_POST['additional_info'] ?? ''),
                $regId,
            ]);
    }
    header("Location: /crcap/admin/events.php?action=registrations&id={$evId}&msg=reg_updated"); exit;
}

// ── EXPORTAR CSV ─────────────────────────────────────────────────────────────
if ($action === 'export_csv' && $id) {
    $evTitle = dbFetch($pdo, "SELECT title FROM events WHERE id=?", [$id])['title'] ?? 'evento';
    $regs    = dbFetchAll($pdo,
        "SELECT name, email, phone, cpf, company, position, status, payment_status,
                confirmation_code, registered_at, confirmed_at, additional_info
         FROM event_registrations WHERE event_id=? ORDER BY registered_at ASC", [$id]);
    $filename = 'inscricoes_' . preg_replace('/[^a-z0-9]/i','_', $evTitle) . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 para Excel
    fputcsv($out, ['Nome','E-mail','Telefone','CPF','Empresa','Cargo','Status','Pagamento','Código','Inscrito em','Confirmado em','Obs']);
    foreach ($regs as $r) {
        fputcsv($out, [
            $r['name'], $r['email'], $r['phone'], $r['cpf'],
            $r['company'], $r['position'], $r['status'], $r['payment_status'],
            $r['confirmation_code'],
            $r['registered_at'] ? date('d/m/Y H:i', strtotime($r['registered_at'])) : '',
            $r['confirmed_at']  ? date('d/m/Y H:i', strtotime($r['confirmed_at']))  : '',
            $r['additional_info'],
        ]);
    }
    fclose($out); exit;
}

// ── NOTIFICAÇÃO EM MASSA ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_notify'])) {
    $evId    = (int)($_POST['ev_id'] ?? 0);
    $channel = $_POST['channel'] ?? 'whatsapp'; // 'whatsapp' | 'email'
    $message = trim($_POST['message'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Informação sobre o evento');
    $filter  = $_POST['notify_filter'] ?? 'all'; // all | pending | confirmed | attended

    $result  = ['sent' => 0, 'failed' => 0, 'errors' => []];

    if ($evId && $message) {
        $where = "event_id=?";
        $params = [$evId];
        if ($filter !== 'all') { $where .= " AND status=?"; $params[] = $filter; }
        $regs = dbFetchAll($pdo,
            "SELECT name, email, phone FROM event_registrations WHERE {$where}", $params);

        $evInfo = dbFetch($pdo, "SELECT title, event_date, start_time, location FROM events WHERE id=?", [$evId]);

        if ($channel === 'whatsapp') {
            require_once __DIR__ . '/../includes/whatsapp.php';
            try {
                $wa = WhatsApp::fromSettings($pdo);
                if (!$wa->isConfigured()) throw new Exception('WhatsApp não configurado');

                // Regra de horário para Evolution
                if ($wa->getProvider() === 'evolution') {
                    $hour = (int)date('G');
                    if ($hour < 8 || $hour >= 20) {
                        $result['errors'][] = 'Evolution API: envio bloqueado fora do horário (8h–20h).';
                        goto notify_done;
                    }
                }

                foreach ($regs as $r) {
                    $phone = preg_replace('/\D/', '', $r['phone'] ?? '');
                    if (strlen($phone) < 10) { $result['failed']++; continue; }
                    if (strlen($phone) <= 11) $phone = '55' . $phone;

                    $msg = str_replace(
                        ['{{nome}}', '{{evento}}', '{{data}}', '{{local}}'],
                        [
                            $r['name'] ?: 'Profissional',
                            $evInfo['title'] ?? '',
                            $evInfo['event_date'] ? date('d/m/Y', strtotime($evInfo['event_date'])) : '',
                            $evInfo['location'] ?? '',
                        ],
                        $message
                    );
                    try {
                        $wa->sendText($phone, $msg);
                        $result['sent']++;
                        // Delay 4–10s entre msgs (anti-spam Evolution)
                        if ($wa->getProvider() === 'evolution') usleep(rand(4000000, 10000000));
                        else usleep(rand(500000, 1000000));
                    } catch (Exception $e) {
                        $result['failed']++;
                        $result['errors'][] = $r['name'].': '.$e->getMessage();
                    }
                }
            } catch (Exception $e) {
                $result['errors'][] = $e->getMessage();
            }
        } else {
            // Email via CrcapMailer (mailer.php)
            $mailerPath = __DIR__ . '/../includes/mailer.php';
            if (file_exists($mailerPath)) {
                require_once $mailerPath;
                $mailer = new CrcapMailer($pdo);
                foreach ($regs as $r) {
                    $body = str_replace(
                        ['{{nome}}','{{evento}}','{{data}}','{{local}}'],
                        [
                            $r['name'],
                            $evInfo['title'] ?? '',
                            $evInfo['event_date'] ? date('d/m/Y', strtotime($evInfo['event_date'])) : '',
                            $evInfo['location'] ?? '',
                        ],
                        $message
                    );
                    $html = '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px">'
                          . '<p>Olá, <strong>' . htmlspecialchars($r['name']) . '</strong>!</p>'
                          . '<div style="background:#f5f5f5;padding:16px;border-radius:8px;white-space:pre-line;line-height:1.6">'
                          . htmlspecialchars($body)
                          . '</div>'
                          . '<hr style="border:none;border-top:1px solid #e2e8f0;margin:20px 0">'
                          . '<p style="color:#94a3b8;font-size:11px">CRCAP – Conselho Regional de Contabilidade do Amapá</p>'
                          . '</div>';
                    try {
                        $ok = $mailer->send($r['email'], $r['name'], $subject, $html);
                        if ($ok) $result['sent']++;
                        else     { $result['failed']++; $result['errors'][] = $r['email'].': falha no envio'; }
                    } catch (Exception $e) {
                        $result['failed']++;
                        $result['errors'][] = $r['email'] . ': ' . $e->getMessage();
                    }
                }
            } else {
                $result['errors'][] = 'mailer.php não encontrado em includes/.';
            }
        }
    }

    notify_done:
    $notifyResult = $result;
    // Continua para a tela de inscrições
    $action = 'registrations';
    $id = (int)($_POST['ev_id'] ?? 0);
}

// ── SAVE EVENTO ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_event'])) {
    $eid       = (int)($_POST['eid'] ?? 0);
    $title     = trim($_POST['title'] ?? '');
    $slug      = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/','-', iconv('UTF-8','ASCII//TRANSLIT',$title)));
    $desc      = trim($_POST['description'] ?? '');
    $content   = $_POST['content'] ?? '';
    $evType    = trim($_POST['event_type'] ?? '');
    $location  = trim($_POST['location'] ?? '');
    $evDate    = $_POST['event_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime   = $_POST['end_time'] ?? null;
    $status    = $_POST['status'] ?? 'draft';
    $isFeatured= isset($_POST['is_featured']) ? 1 : 0;
    $isFree    = isset($_POST['is_free']) ? 1 : 0;
    $price     = (float)($_POST['price'] ?? 0);
    $maxPart   = (int)($_POST['max_participants'] ?? 0) ?: null;
    $regReq    = isset($_POST['registration_required']) ? 1 : 0;
    $extLink   = trim($_POST['external_link'] ?? '');
    $regLink   = trim($_POST['registration_link'] ?? '');
    $img       = trim($_POST['featured_image'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');
    $email     = trim($_POST['contact_email'] ?? '');
    $phone     = trim($_POST['contact_phone'] ?? '');

    if ($title && $evDate && $startTime) {
        try {
            if ($eid) {
                dbExec($pdo, "UPDATE events SET title=?,slug=?,description=?,content=?,event_type=?,location=?,event_date=?,start_time=?,end_time=?,status=?,is_featured=?,is_free=?,price=?,max_participants=?,registration_required=?,external_link=?,registration_link=?,featured_image=?,organizer=?,contact_email=?,contact_phone=? WHERE id=?",
                    [$title,$slug,$desc,$content,$evType,$location,$evDate,$startTime,$endTime?:null,$status,$isFeatured,$isFree,$price,$maxPart,$regReq,$extLink,$regLink,$img,$organizer,$email,$phone,$eid]);
            } else {
                dbExec($pdo, "INSERT INTO events (title,slug,description,content,event_type,location,event_date,start_time,end_time,status,is_featured,is_free,price,max_participants,registration_required,external_link,registration_link,featured_image,organizer,contact_email,contact_phone,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$title,$slug,$desc,$content,$evType,$location,$evDate,$startTime,$endTime?:null,$status,$isFeatured,$isFree,$price,$maxPart,$regReq,$extLink,$regLink,$img,$organizer,$email,$phone,$adminUser['id']]);
            }
            header('Location: /crcap/admin/events.php?msg=saved'); exit;
        } catch (Exception $e) { $msg = 'Erro: '.$e->getMessage(); }
    } else { $msg = 'Preencha todos os campos obrigatórios.'; }
}

if ($action === 'edit' && $id) {
    $event = dbFetch($pdo, "SELECT * FROM events WHERE id=?", [$id]);
    if (!$event) { header('Location: /crcap/admin/events.php'); exit; }
}
if ($action === 'new') {
    $event = ['id'=>0,'title'=>'','slug'=>'','description'=>'','content'=>'','event_type'=>'','location'=>'','event_date'=>'','start_time'=>'','end_time'=>'','status'=>'draft','is_featured'=>0,'is_free'=>1,'price'=>0,'max_participants'=>'','registration_required'=>0,'external_link'=>'','registration_link'=>'','featured_image'=>'','organizer'=>'','contact_email'=>'','contact_phone'=>''];
}

// ════════════════════════════════════════════════════════════════════════════
// VIEW: INSCRIÇÕES (action=registrations)
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'registrations' && $id) {
    $evData = dbFetch($pdo, "SELECT * FROM events WHERE id=?", [$id]);
    if (!$evData) { header('Location: /crcap/admin/events.php'); exit; }

    $statusFilter = $_GET['sf'] ?? '';
    $regWhere  = "event_id=?"; $regParams = [$id];
    if ($statusFilter) { $regWhere .= " AND status=?"; $regParams[] = $statusFilter; }
    $regs = dbFetchAll($pdo,
        "SELECT * FROM event_registrations WHERE {$regWhere} ORDER BY registered_at DESC",
        $regParams);

    // Stats por status
    $stats = dbFetchAll($pdo,
        "SELECT status, COUNT(*) AS n FROM event_registrations WHERE event_id=? GROUP BY status", [$id]);
    $statMap = ['pending'=>0,'confirmed'=>0,'cancelled'=>0,'attended'=>0];
    foreach ($stats as $s) $statMap[$s['status']] = (int)$s['n'];
    $totalAll = array_sum($statMap);

    // WhatsApp settings para mostrar canal disponível
    $wppProvider = getSetting($pdo, 'whatsapp_provider', 'evolution');

    require_once __DIR__ . '/admin_header.php';
?>

<!-- ══ HEADER DA SEÇÃO ══════════════════════════════════════════════════════ -->
<div class="flex flex-wrap items-center gap-3 mb-6">
    <a href="/crcap/admin/events.php" class="w-8 h-8 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#022E6B] flex items-center justify-center transition text-sm">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div class="min-w-0 flex-1">
        <h2 class="text-lg font-bold text-[#001644] leading-tight truncate"><?= htmlspecialchars($evData['title']) ?></h2>
        <p class="text-[10px] text-[#022E6B]/50">
            <?= date('d/m/Y', strtotime($evData['event_date'])) ?> · <?= substr($evData['start_time'],0,5) ?>
            <?= $evData['location'] ? ' · '.htmlspecialchars($evData['location']) : '' ?>
        </p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="/crcap/admin/events.php?action=export_csv&id=<?= $id ?>"
           class="btn-gold text-xs py-2"><i class="fas fa-file-csv"></i> Exportar CSV</a>
        <a href="/crcap/admin/events.php?action=edit&id=<?= $id ?>"
           class="px-3 py-2 rounded-xl text-xs font-semibold bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#022E6B] transition flex items-center gap-1.5">
            <i class="fas fa-edit"></i> Editar Evento
        </a>
    </div>
</div>

<!-- ══ MENSAGENS ════════════════════════════════════════════════════════════ -->
<?php
$msgMapReg = [
    'reg_deleted' => ['Inscrição removida.','bg-[#006633]/10 border-[#006633]/30 text-[#001644]'],
    'reg_updated' => ['Inscrição atualizada!','bg-[#006633]/10 border-[#006633]/30 text-[#001644]'],
];
if (isset($_GET['msg']) && isset($msgMapReg[$_GET['msg']])): ?>
<div class="border text-xs rounded-xl px-4 py-3 mb-5 flex items-center gap-2 <?= $msgMapReg[$_GET['msg']][1] ?>">
    <i class="fas fa-check-circle"></i> <?= $msgMapReg[$_GET['msg']][0] ?>
</div>
<?php endif; ?>

<?php if (isset($notifyResult)): ?>
<div class="border text-xs rounded-xl px-4 py-3 mb-5 <?= $notifyResult['sent'] > 0 ? 'bg-[#006633]/10 border-[#006633]/30 text-[#001644]' : 'bg-red-50 border-red-200 text-red-700' ?>">
    <i class="fas <?= $notifyResult['sent'] > 0 ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
    Notificações: <strong><?= $notifyResult['sent'] ?> enviadas</strong>, <?= $notifyResult['failed'] ?> falhas.
    <?php if ($notifyResult['errors']): ?>
    <details class="mt-1"><summary class="cursor-pointer text-[10px]">Ver detalhes</summary>
        <ul class="mt-1 space-y-0.5"><?php foreach (array_slice($notifyResult['errors'],0,5) as $e): ?><li class="text-[10px]"><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </details>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══ STATS CARDS ══════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
    <?php
    $statCards = [
        ['all',       'Total',       $totalAll,          'fa-users',         'bg-[#001644]'],
        ['pending',   'Pendentes',   $statMap['pending'],'fa-clock',         'bg-amber-500'],
        ['confirmed', 'Confirmados', $statMap['confirmed'],'fa-check-circle','bg-[#006633]'],
        ['attended',  'Presentes',   $statMap['attended'],'fa-user-check',   'bg-[#022E6B]'],
        ['cancelled', 'Cancelados',  $statMap['cancelled'],'fa-times-circle','bg-red-500'],
    ];
    foreach ($statCards as [$sf, $label, $count, $icon, $color]):
        $active = ($statusFilter === $sf) || ($sf === 'all' && !$statusFilter);
    ?>
    <a href="?action=registrations&id=<?= $id ?><?= $sf !== 'all' ? '&sf='.$sf : '' ?>"
       class="card p-4 text-center transition hover:shadow-md <?= $active ? 'ring-2 ring-[#BF8D1A]' : '' ?>">
        <div class="w-9 h-9 rounded-xl <?= $color ?> flex items-center justify-center text-white text-sm mx-auto mb-2">
            <i class="fas <?= $icon ?>"></i>
        </div>
        <p class="text-lg font-bold font-serif text-[#001644] leading-none"><?= $count ?></p>
        <p class="text-[10px] text-[#022E6B]/60 mt-0.5"><?= $label ?></p>
    </a>
    <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    <!-- ══ TABELA DE INSCRITOS ══════════════════════════════════════════════ -->
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <!-- Filtros rápidos -->
            <div class="p-4 border-b border-[#001644]/5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-1 flex-wrap">
                    <?php foreach ([
                        '' => 'Todos',
                        'pending'   => 'Pendentes',
                        'confirmed' => 'Confirmados',
                        'attended'  => 'Presentes',
                        'cancelled' => 'Cancelados',
                    ] as $sf => $lbl): ?>
                    <a href="?action=registrations&id=<?= $id ?><?= $sf ? '&sf='.$sf : '' ?>"
                       class="px-2.5 py-1 rounded-lg text-[10px] font-semibold transition
                              <?= $statusFilter === $sf ? 'bg-[#001644] text-white' : 'bg-[#F8FAFC] text-[#022E6B] border border-[#001644]/10 hover:bg-[#001644]/5' ?>">
                        <?= $lbl ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <p class="text-[10px] text-[#022E6B]/50"><?= count($regs) ?> registro(s)</p>
            </div>

            <table class="w-full">
                <thead><tr>
                    <th class="text-left">Inscrito</th>
                    <th class="text-center hidden md:table-cell">Código</th>
                    <th class="text-center">Status</th>
                    <th class="text-center hidden lg:table-cell">Data</th>
                    <th class="text-center">Ações</th>
                </tr></thead>
                <tbody>
                <?php if (empty($regs)): ?>
                <tr><td colspan="5" class="text-center py-12 text-[#001644]/30">
                    <i class="fas fa-users text-4xl mb-3 block"></i>
                    Nenhuma inscrição <?= $statusFilter ? 'com este status' : '' ?>
                </td></tr>
                <?php else: foreach ($regs as $r):
                    $badgeClass = match($r['status']) {
                        'confirmed' => 'badge-green',
                        'attended'  => 'badge-blue',
                        'cancelled' => 'badge-red',
                        default     => 'badge-gold',
                    };
                ?>
                <tr id="row-<?= $r['id'] ?>">
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                <?= strtoupper(substr($r['name'],0,1)) ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-[#001644] text-xs truncate max-w-[160px]"><?= htmlspecialchars($r['name']) ?></p>
                                <p class="text-[10px] text-[#022E6B]/50 truncate max-w-[160px]"><?= htmlspecialchars($r['email']) ?></p>
                                <?php if ($r['phone']): ?>
                                <p class="text-[10px] text-[#BF8D1A] font-mono"><?= htmlspecialchars($r['phone']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-center font-mono text-xs hidden md:table-cell">
                        <span class="bg-[#F0F4F8] px-2 py-0.5 rounded-lg text-[#001644]">
                            <?= htmlspecialchars($r['confirmation_code'] ?? '—') ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <!-- Dropdown de status rápido -->
                        <div class="relative group inline-block">
                            <button class="badge <?= $badgeClass ?> cursor-pointer hover:opacity-80 transition">
                                <?= $r['status'] ?> <i class="fas fa-chevron-down text-[7px] ml-1"></i>
                            </button>
                            <div class="absolute right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-[#001644]/10 z-20 hidden group-hover:block min-w-[130px]">
                                <?php foreach ([
                                    'pending'   => ['badge-gold','Pendente'],
                                    'confirmed' => ['badge-green','Confirmado'],
                                    'attended'  => ['badge-blue','Presente'],
                                    'cancelled' => ['badge-red','Cancelado'],
                                ] as $sv => [$sc, $sl]): ?>
                                <a href="?action=reg_status&id=<?= $r['id'] ?>&ev=<?= $id ?>&status=<?= $sv ?>&sf=<?= $statusFilter ?>"
                                   class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[#F8FAFC] transition <?= $r['status']===$sv ? 'font-bold text-[#001644]' : 'text-[#022E6B]' ?>">
                                    <span class="badge <?= $sc ?> text-[8px]"><?= $sl ?></span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-center text-[10px] text-[#022E6B]/50 hidden lg:table-cell">
                        <?= date('d/m/Y', strtotime($r['registered_at'])) ?><br>
                        <span class="text-[9px]"><?= date('H:i', strtotime($r['registered_at'])) ?></span>
                    </td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <!-- Editar (abre modal) -->
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($r)) ?>)"
                                    class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <!-- WhatsApp individual -->
                            <?php if ($r['phone']): $ph = preg_replace('/\D/','',$r['phone']); if(strlen($ph)<=11)$ph='55'.$ph; ?>
                            <a href="https://wa.me/<?= $ph ?>?text=<?= rawurlencode('Olá '.$r['name'].'! Informações sobre o evento: '.$evData['title']) ?>"
                               target="_blank"
                               class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs hover:opacity-80 transition"
                               style="background:#25D366" title="Abrir WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <?php endif; ?>
                            <!-- Deletar -->
                            <a href="?action=del_reg&id=<?= $r['id'] ?>&ev=<?= $id ?>&sf=<?= $statusFilter ?>"
                               onclick="return confirm('Remover esta inscrição?')"
                               class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs"
                               title="Remover">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══ PAINEL LATERAL ═══════════════════════════════════════════════════ -->
    <div class="space-y-5">

        <!-- Card notificação em massa -->
        <div class="card p-5">
            <h3 class="font-bold text-[#001644] text-sm mb-4 flex items-center gap-2">
                <i class="fas fa-bullhorn text-[#BF8D1A]"></i> Notificar Inscritos
            </h3>
            <form method="POST" id="formNotify">
                <input type="hidden" name="form_notify" value="1">
                <input type="hidden" name="ev_id"      value="<?= $id ?>">
                <div class="space-y-3">
                    <!-- Canal -->
                    <div>
                        <label class="form-label">Canal</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition has-[:checked]:border-[#25D366] has-[:checked]:bg-[#25D366]/5 border-[#001644]/10">
                                <input type="radio" name="channel" value="whatsapp" checked class="accent-[#25D366]">
                                <span class="text-xs font-semibold text-[#022E6B] flex items-center gap-1.5">
                                    <i class="fab fa-whatsapp text-[#25D366]"></i> WhatsApp
                                </span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition has-[:checked]:border-[#001644] has-[:checked]:bg-[#001644]/5 border-[#001644]/10">
                                <input type="radio" name="channel" value="email" class="accent-[#001644]">
                                <span class="text-xs font-semibold text-[#022E6B] flex items-center gap-1.5">
                                    <i class="fas fa-envelope text-[#001644]"></i> E-mail
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Destinatários -->
                    <div>
                        <label class="form-label">Destinatários</label>
                        <select name="notify_filter" class="form-input text-xs">
                            <option value="all">Todos (<?= $totalAll ?>)</option>
                            <option value="pending">Pendentes (<?= $statMap['pending'] ?>)</option>
                            <option value="confirmed">Confirmados (<?= $statMap['confirmed'] ?>)</option>
                            <option value="attended">Presentes (<?= $statMap['attended'] ?>)</option>
                        </select>
                    </div>

                    <!-- Assunto (só email) -->
                    <div id="subjectRow" class="hidden">
                        <label class="form-label">Assunto do e-mail</label>
                        <input type="text" name="subject" value="Informação sobre o evento"
                               class="form-input text-xs" placeholder="Assunto">
                    </div>

                    <!-- Mensagem -->
                    <div>
                        <label class="form-label">Mensagem</label>
                        <textarea name="message" rows="6" required
                                  placeholder="Olá {{nome}}!&#10;&#10;Lembramos que o evento *{{evento}}* acontece em {{data}}.&#10;&#10;Local: {{local}}&#10;&#10;Atenciosamente, CRCAP"
                                  class="form-input resize-none text-xs leading-relaxed"></textarea>
                        <div class="flex flex-wrap gap-1 mt-1.5">
                            <?php foreach (['{{nome}}','{{evento}}','{{data}}','{{local}}'] as $var): ?>
                            <button type="button"
                                    onclick="insertNotifyVar('<?= $var ?>')"
                                    class="px-2 py-0.5 text-[9px] font-bold bg-[#001644]/5 hover:bg-[#BF8D1A] hover:text-white text-[#022E6B] rounded-lg transition">
                                <?= $var ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Aviso WhatsApp -->
                    <div class="bg-[#25D366]/8 border border-[#25D366]/20 rounded-xl p-3 text-[10px] text-[#022E6B]/70 space-y-1" id="wppNote">
                        <p><i class="fas fa-info-circle text-[#BF8D1A] mr-1"></i>
                        Provider: <strong><?= strtoupper($wppProvider) ?></strong>
                        <?php if ($wppProvider === 'evolution'): ?>
                        · Delay automático 4–10s entre msgs · Horário: 8h–20h
                        <?php else: ?>
                        · Delay 0.5–1s · Sem restrição de horário
                        <?php endif; ?>
                        </p>
                    </div>

                    <button type="submit"
                            onclick="return confirm('Confirmar envio de notificações?')"
                            class="w-full py-2.5 rounded-xl font-bold text-white text-xs flex items-center justify-center gap-2 transition hover:opacity-90"
                            style="background:linear-gradient(135deg,#001644,#022E6B)">
                        <i class="fas fa-paper-plane"></i> Enviar Notificações
                    </button>
                </div>
            </form>
        </div>

        <!-- Atalhos rápidos de status em massa -->
        <div class="card p-5">
            <h3 class="font-bold text-[#001644] text-sm mb-3 flex items-center gap-2">
                <i class="fas fa-bolt text-[#BF8D1A]"></i> Ações Rápidas
            </h3>
            <div class="space-y-2 text-xs">
                <a href="?action=registrations&id=<?= $id ?>&sf=pending"
                   class="flex items-center justify-between p-3 rounded-xl bg-amber-50 border border-amber-200 hover:bg-amber-100 transition text-[#022E6B]">
                    <span class="flex items-center gap-2"><i class="fas fa-clock text-amber-500"></i> Ver pendentes</span>
                    <span class="badge badge-gold"><?= $statMap['pending'] ?></span>
                </a>
                <a href="?action=export_csv&id=<?= $id ?>"
                   class="flex items-center justify-between p-3 rounded-xl bg-[#F8FAFC] border border-[#001644]/10 hover:bg-[#001644]/5 transition text-[#022E6B]">
                    <span class="flex items-center gap-2"><i class="fas fa-download text-[#BF8D1A]"></i> Exportar todos (CSV)</span>
                    <span class="badge badge-gray"><?= $totalAll ?></span>
                </a>
                <a href="/crcap/pages/evento.php?slug=<?= urlencode($evData['slug']) ?>" target="_blank"
                   class="flex items-center gap-2 p-3 rounded-xl bg-[#F8FAFC] border border-[#001644]/10 hover:bg-[#001644]/5 transition text-[#022E6B]">
                    <i class="fas fa-external-link-alt text-[#BF8D1A]"></i> Ver página pública
                </a>
            </div>
        </div>

    </div><!-- /lateral -->
</div><!-- /grid -->


<!-- ══ MODAL: EDITAR INSCRIÇÃO ══════════════════════════════════════════════ -->
<div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-[#001644]/5">
            <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2">
                <i class="fas fa-user-edit text-[#BF8D1A]"></i> Editar Inscrição
            </h3>
            <button onclick="closeEditModal()"
                    class="w-8 h-8 rounded-xl bg-[#F8FAFC] hover:bg-red-50 hover:text-red-500 text-[#022E6B] flex items-center justify-center transition text-xs">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="p-5 space-y-4">
            <input type="hidden" name="form_reg_edit" value="1">
            <input type="hidden" name="reg_id"  id="modal_reg_id">
            <input type="hidden" name="ev_id"   value="<?= $id ?>">

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" id="modal_name" required class="form-input text-xs">
                </div>
                <div>
                    <label class="form-label">E-mail *</label>
                    <input type="email" name="email" id="modal_email" required class="form-input text-xs">
                </div>
                <div>
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="phone" id="modal_phone" class="form-input text-xs font-mono" placeholder="96999990000">
                </div>
                <div>
                    <label class="form-label">CPF</label>
                    <input type="text" name="cpf" id="modal_cpf" class="form-input text-xs font-mono">
                </div>
                <div>
                    <label class="form-label">Empresa</label>
                    <input type="text" name="company" id="modal_company" class="form-input text-xs">
                </div>
                <div>
                    <label class="form-label">Cargo</label>
                    <input type="text" name="position" id="modal_position" class="form-input text-xs">
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" id="modal_status" class="form-input text-xs">
                        <option value="pending">Pendente</option>
                        <option value="confirmed">Confirmado</option>
                        <option value="attended">Presente</option>
                        <option value="cancelled">Cancelado</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Pagamento</label>
                    <select name="payment_status" id="modal_payment" class="form-input text-xs">
                        <option value="pending">Pendente</option>
                        <option value="paid">Pago</option>
                        <option value="refunded">Reembolsado</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Observações</label>
                <textarea name="additional_info" id="modal_info" rows="2" class="form-input resize-none text-xs"></textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary flex-1 justify-center text-xs">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border border-[#001644]/10 rounded-xl text-xs font-medium text-[#022E6B] hover:bg-[#F8FAFC] transition">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Modal editar ────────────────────────────────────────────────────────────
function openEditModal(r) {
    document.getElementById('modal_reg_id').value  = r.id;
    document.getElementById('modal_name').value    = r.name    || '';
    document.getElementById('modal_email').value   = r.email   || '';
    document.getElementById('modal_phone').value   = r.phone   || '';
    document.getElementById('modal_cpf').value     = r.cpf     || '';
    document.getElementById('modal_company').value = r.company || '';
    document.getElementById('modal_position').value= r.position|| '';
    document.getElementById('modal_status').value  = r.status  || 'pending';
    document.getElementById('modal_payment').value = r.payment_status || 'pending';
    document.getElementById('modal_info').value    = r.additional_info || '';
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// ── Inserir variável na textarea ─────────────────────────────────────────────
function insertNotifyVar(v) {
    const ta = document.querySelector('[name="message"]');
    const s = ta.selectionStart, e = ta.selectionEnd;
    ta.value = ta.value.substring(0,s) + v + ta.value.substring(e);
    ta.selectionStart = ta.selectionEnd = s + v.length;
    ta.focus();
}

// ── Toggle assunto (só para email) ───────────────────────────────────────────
document.querySelectorAll('[name="channel"]').forEach(function(r) {
    r.addEventListener('change', function() {
        const isEmail = this.value === 'email';
        document.getElementById('subjectRow').classList.toggle('hidden', !isEmail);
        document.getElementById('wppNote').classList.toggle('hidden', isEmail);
    });
});
</script>

<?php
    require_once __DIR__ . '/admin_footer.php'; exit;
}

// ════════════════════════════════════════════════════════════════════════════
// HEADER (para edit/new/list)
// ════════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/admin_header.php';

// ════════════════════════════════════════════════════════════════════════════
// VIEW: EDIT / NEW
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'edit' || $action === 'new'): ?>

<div class="flex items-center gap-3 mb-6">
    <a href="/crcap/admin/events.php" class="text-[#022E6B] hover:text-[#BF8D1A] transition text-sm"><i class="fas fa-arrow-left"></i></a>
    <h2 class="text-lg font-bold text-[#001644]"><?= $id ? 'Editar Evento' : 'Novo Evento' ?></h2>
</div>

<?php if (!empty($msg)): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="POST" class="grid lg:grid-cols-3 gap-6">
    <input type="hidden" name="form_event" value="1">
    <input type="hidden" name="eid" value="<?= $event['id'] ?>">

    <div class="lg:col-span-2 space-y-5">
        <div class="card p-6 space-y-4">
            <div><label class="form-label">Título *</label><input type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" required class="form-input" placeholder="Título do evento"></div>
            <div><label class="form-label">Slug</label><input type="text" name="slug" value="<?= htmlspecialchars($event['slug']) ?>" class="form-input" placeholder="slug-do-evento"></div>
            <div><label class="form-label">Descrição Curta</label><textarea name="description" rows="3" class="form-input resize-none"><?= htmlspecialchars($event['description']) ?></textarea></div>
            <div><label class="form-label">Conteúdo Detalhado</label><textarea name="content" rows="10" class="form-input resize-none font-mono text-xs"><?= htmlspecialchars($event['content']) ?></textarea></div>
        </div>
        <div class="card p-6">
            <h3 class="text-sm font-bold text-[#001644] mb-4">Data, Horário e Local</h3>
            <div class="grid sm:grid-cols-3 gap-4 mb-4">
                <div><label class="form-label">Data *</label><input type="date" name="event_date" value="<?= $event['event_date'] ?>" required class="form-input"></div>
                <div><label class="form-label">Início *</label><input type="time" name="start_time" value="<?= substr($event['start_time'],0,5) ?>" required class="form-input"></div>
                <div><label class="form-label">Término</label><input type="time" name="end_time" value="<?= substr($event['end_time']??'',0,5) ?>" class="form-input"></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="form-label">Tipo de Evento</label><input type="text" name="event_type" value="<?= htmlspecialchars($event['event_type']) ?>" class="form-input" placeholder="Ex: Workshop, Congresso, Live"></div>
                <div><label class="form-label">Local / Plataforma</label><input type="text" name="location" value="<?= htmlspecialchars($event['location']) ?>" class="form-input" placeholder="Endereço ou link da plataforma"></div>
            </div>
        </div>
        <div class="card p-6">
            <h3 class="text-sm font-bold text-[#001644] mb-4">Inscrições e Preço</h3>
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <label class="flex items-center gap-2 cursor-pointer p-3 bg-[#F8FAFC] rounded-xl"><input type="checkbox" name="registration_required" <?= $event['registration_required']?'checked':'' ?>><span class="text-xs font-medium text-[#022E6B]">Requer inscrição</span></label>
                <label class="flex items-center gap-2 cursor-pointer p-3 bg-[#F8FAFC] rounded-xl"><input type="checkbox" name="is_free" <?= $event['is_free']?'checked':'' ?>><span class="text-xs font-medium text-[#022E6B]">Evento gratuito</span></label>
                <div><label class="form-label">Preço (R$)</label><input type="number" name="price" value="<?= $event['price'] ?>" step="0.01" min="0" class="form-input"></div>
                <div><label class="form-label">Máx. de participantes</label><input type="number" name="max_participants" value="<?= $event['max_participants'] ?>" min="0" class="form-input" placeholder="0 = ilimitado"></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="form-label">Link externo</label><input type="url" name="external_link" value="<?= htmlspecialchars($event['external_link']) ?>" class="form-input" placeholder="https://..."></div>
                <div><label class="form-label">Link de inscrição externa</label><input type="url" name="registration_link" value="<?= htmlspecialchars($event['registration_link']) ?>" class="form-input" placeholder="https://..."></div>
            </div>
        </div>
        <div class="card p-6">
            <h3 class="text-sm font-bold text-[#001644] mb-4">Organizador / Contato</h3>
            <div class="grid sm:grid-cols-3 gap-4">
                <div><label class="form-label">Organizador</label><input type="text" name="organizer" value="<?= htmlspecialchars($event['organizer']) ?>" class="form-input" placeholder="Nome do organizador"></div>
                <div><label class="form-label">E-mail de contato</label><input type="email" name="contact_email" value="<?= htmlspecialchars($event['contact_email']) ?>" class="form-input"></div>
                <div><label class="form-label">Telefone de contato</label><input type="tel" name="contact_phone" value="<?= htmlspecialchars($event['contact_phone']) ?>" class="form-input" placeholder="(96) 9xxxx-xxxx"></div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-5">
        <div class="card p-5">
            <h3 class="text-sm font-bold text-[#001644] mb-4">Publicação</h3>
            <div class="space-y-3 mb-4">
                <div><label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <?php foreach(['draft'=>'Rascunho','published'=>'Publicado','cancelled'=>'Cancelado','postponed'=>'Adiado'] as $v=>$l): ?><option value="<?= $v ?>" <?= $event['status']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
                    </select>
                </div>
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="is_featured" <?= $event['is_featured']?'checked':'' ?>><span class="text-xs text-[#022E6B] font-medium">Evento destaque</span></label>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center"><i class="fas fa-save"></i>Salvar</button>
                <a href="/crcap/admin/events.php" class="px-4 py-2.5 border border-[#001644]/10 rounded-xl text-xs font-medium text-[#022E6B] hover:bg-[#F8FAFC] transition">Cancelar</a>
            </div>
        </div>
        <div class="card p-5">
            <h3 class="text-sm font-bold text-[#001644] mb-3">Imagem do Evento</h3>
            <input type="text" name="featured_image" value="<?= htmlspecialchars($event['featured_image']) ?>" class="form-input mb-3" placeholder="URL da imagem">
            <?php if ($event['featured_image']): ?><img src="<?= htmlspecialchars($event['featured_image']) ?>" class="w-full h-28 object-cover rounded-xl"><?php else: ?>
            <div class="w-full h-28 bg-[#F8FAFC] rounded-xl border-2 border-dashed border-[#001644]/10 flex items-center justify-center"><i class="fas fa-image text-[#001644]/20 text-2xl"></i></div><?php endif; ?>
        </div>
        <?php if ($id): ?>
        <div class="card p-5">
            <h3 class="text-sm font-bold text-[#001644] mb-3">Inscrições</h3>
            <?php $regCount = dbFetch($pdo, "SELECT COUNT(*) AS n FROM event_registrations WHERE event_id=?", [$id])['n'] ?? 0; ?>
            <p class="text-2xl font-bold font-serif text-[#001644] mb-3"><?= $regCount ?> <span class="text-sm font-normal text-[#022E6B]">inscritos</span></p>
            <a href="/crcap/admin/events.php?action=registrations&id=<?= $id ?>" class="btn-gold w-full justify-center"><i class="fas fa-users"></i>Gerenciar inscrições</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php
else: // ════════════════════════════════════════════════════════════════════
      // VIEW: LIST
      // ════════════════════════════════════════════════════════════════════
$page_n = max(1,(int)($_GET['p'] ?? 1));
$perP   = 15; $off = ($page_n-1)*$perP;
$filter = $_GET['filter'] ?? '';
$where  = ['1=1']; $params = [];
if ($filter) { $where[] = 'status=?'; $params[] = $filter; }
$events = dbFetchAll($pdo, "SELECT * FROM events WHERE ".implode(' AND ',$where)." ORDER BY event_date DESC LIMIT $perP OFFSET $off", $params);
$total  = dbFetch($pdo, "SELECT COUNT(*) AS n FROM events WHERE ".implode(' AND ',$where), $params)['n'] ?? 0;
$pages  = ceil($total/$perP);
$msgMap = ['saved'=>'Salvo!','deleted'=>'Excluído.','updated'=>'Atualizado!'];
?>
<?php if ($m = $msgMap[$_GET['msg'] ?? ''] ?? null): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-xs rounded-xl px-4 py-3 mb-5 text-[#001644]"><?= $m ?></div><?php endif; ?>

<div class="flex items-center justify-between mb-5">
    <div class="flex gap-1 flex-wrap">
        <?php foreach([''=>'Todos','draft'=>'Rascunho','published'=>'Publicado','cancelled'=>'Cancelado'] as $v=>$l): ?>
        <a href="?filter=<?= $v ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $filter===$v?'bg-[#001644] text-white':'bg-white text-[#022E6B] border border-[#001644]/10 hover:bg-[#001644]/5' ?>"><?= $l ?></a>
        <?php endforeach; ?>
    </div>
    <a href="/crcap/admin/events.php?action=new" class="btn-gold"><i class="fas fa-plus"></i>Novo Evento</a>
</div>

<div class="card overflow-hidden">
    <table class="w-full">
        <thead><tr>
            <th class="text-left">Evento</th>
            <th class="text-center hidden md:table-cell">Data</th>
            <th class="text-center hidden lg:table-cell">Status</th>
            <th class="text-right hidden lg:table-cell">Inscritos</th>
            <th class="text-center">Ações</th>
        </tr></thead>
        <tbody>
            <?php if (empty($events)): ?><tr><td colspan="5" class="text-center py-12 text-[#001644]/30"><i class="fas fa-calendar-times text-4xl mb-3 block"></i>Nenhum evento</td></tr>
            <?php else: foreach ($events as $ev): $d = new DateTime($ev['event_date']); $isPast = $d < new DateTime(); ?>
            <tr class="<?= $isPast?'opacity-60':'' ?>">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-xl flex flex-col items-center justify-center text-white flex-shrink-0 text-center">
                            <span class="text-xs font-bold leading-none"><?= $d->format('d') ?></span>
                            <span class="text-[8px] uppercase"><?= $d->format('M') ?></span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-[#001644] truncate max-w-xs"><?= htmlspecialchars($ev['title']) ?></p>
                            <p class="text-[10px] text-[#022E6B]"><?= htmlspecialchars($ev['location'] ?: '—') ?></p>
                        </div>
                    </div>
                </td>
                <td class="text-center hidden md:table-cell text-xs"><?= $d->format('d/m/Y') ?><br><span class="text-[9px] text-[#022E6B]"><?= substr($ev['start_time'],0,5) ?></span></td>
                <td class="text-center hidden lg:table-cell">
                    <span class="badge <?= $ev['status']==='published'?'badge-green':($ev['status']==='cancelled'?'badge-red':'badge-gray') ?>"><?= $ev['status'] ?></span>
                    <?php if ($ev['is_featured']): ?><span class="badge badge-gold ml-1">Destaque</span><?php endif; ?>
                </td>
                <td class="text-right hidden lg:table-cell">
                    <a href="/crcap/admin/events.php?action=registrations&id=<?= $ev['id'] ?>"
                       class="text-xs font-semibold text-[#BF8D1A] hover:underline">
                        <?= dbFetch($pdo,"SELECT COUNT(*) AS n FROM event_registrations WHERE event_id=?",[$ev['id']])['n'] ?? 0 ?> inscritos
                    </a>
                </td>
                <td class="text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="/crcap/admin/events.php?action=edit&id=<?= $ev['id'] ?>" class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="/crcap/admin/events.php?action=registrations&id=<?= $ev['id'] ?>" class="w-7 h-7 rounded-lg bg-[#BF8D1A]/10 hover:bg-[#BF8D1A] hover:text-white text-[#BF8D1A] flex items-center justify-center transition text-xs" title="Inscrições"><i class="fas fa-users"></i></a>
                        <a href="/crcap/admin/events.php?action=toggle&id=<?= $ev['id'] ?>" class="w-7 h-7 rounded-lg bg-[#006633]/10 hover:bg-[#006633] hover:text-white text-[#006633] flex items-center justify-center transition text-xs" title="<?= $ev['status']==='published'?'Despublicar':'Publicar' ?>"><i class="fas <?= $ev['status']==='published'?'fa-eye-slash':'fa-eye' ?>"></i></a>
                        <a href="/crcap/pages/evento.php?slug=<?= urlencode($ev['slug']) ?>" target="_blank" class="w-7 h-7 rounded-lg bg-[#022E6B]/5 hover:bg-[#022E6B] hover:text-white text-[#022E6B] flex items-center justify-center transition text-xs" title="Ver página"><i class="fas fa-external-link-alt"></i></a>
                        <a href="/crcap/admin/events.php?action=delete&id=<?= $ev['id'] ?>" onclick="return confirm('Excluir este evento?')" class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs" title="Excluir"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <div class="flex justify-center gap-2 p-4 border-t border-[#001644]/5">
        <?php for ($i=1;$i<=$pages;$i++): ?><a href="?filter=<?= $filter ?>&p=<?= $i ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-semibold transition <?= $i===$page_n?'bg-[#001644] text-white':'bg-[#F8FAFC] text-[#001644] hover:bg-[#001644]/10' ?>"><?= $i ?></a><?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; require_once __DIR__ . '/admin_footer.php'; ?>