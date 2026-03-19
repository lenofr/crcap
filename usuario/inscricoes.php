<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$pageTitle = 'Minhas Inscrições · CRCAP';
$activeMenu = '';

// Cancel registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    if (!csrfVerify()) { http_response_code(403); exit; }
    $regId = (int)$_POST['cancel_id'];
    $reg = dbFetch($pdo,
        "SELECT er.*, e.event_date FROM event_registrations er
         JOIN events e ON e.id=er.event_id
         WHERE er.id=? AND er.email=?",
        [$regId, $user['email']]);
    if ($reg && $reg['event_date'] > date('Y-m-d')) {
        dbExec($pdo, "UPDATE event_registrations SET status='cancelled' WHERE id=?", [$regId]);
        $cancelMsg = 'Inscrição cancelada com sucesso.';
    }
}

$tab = $_GET['tab'] ?? 'upcoming';
$pg  = max(1, (int)($_GET['p'] ?? 1));
$pp  = 10;
$off = ($pg-1)*$pp;

$baseWhere = "er.email=?";
$params    = [$user['email']];

if ($tab === 'upcoming') {
    $where  = "$baseWhere AND e.event_date >= CURDATE() AND er.status != 'cancelled'";
    $order  = "e.event_date ASC";
} elseif ($tab === 'past') {
    $where  = "$baseWhere AND e.event_date < CURDATE()";
    $order  = "e.event_date DESC";
} else {
    $where  = "$baseWhere AND er.status='cancelled'";
    $order  = "er.registered_at DESC";
}

$total = dbFetch($pdo,
    "SELECT COUNT(*) AS n FROM event_registrations er JOIN events e ON e.id=er.event_id WHERE $where",
    $params)['n'] ?? 0;

$registrations = dbFetchAll($pdo,
    "SELECT er.*, e.title, e.event_date, e.start_time, e.end_time, e.location,
            e.featured_image, e.event_type, e.slug AS event_slug
     FROM event_registrations er
     JOIN events e ON e.id=er.event_id
     WHERE $where ORDER BY $order LIMIT $pp OFFSET $off",
    $params);

$pages = ceil($total / $pp);

// Counts per tab
$upcoming_count = dbFetch($pdo,
    "SELECT COUNT(*) AS n FROM event_registrations er JOIN events e ON e.id=er.event_id WHERE er.email=? AND e.event_date>=CURDATE() AND er.status!='cancelled'",
    [$user['email']])['n'] ?? 0;
$past_count = dbFetch($pdo,
    "SELECT COUNT(*) AS n FROM event_registrations er JOIN events e ON e.id=er.event_id WHERE er.email=? AND e.event_date<CURDATE()",
    [$user['email']])['n'] ?? 0;

include __DIR__ . '/../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] to-[#022E6B] text-white py-10">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-white/50 text-xs mb-4">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <a href="/crcap/usuario/perfil.php" class="hover:text-white transition">Meu Perfil</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Minhas Inscrições</span>
        </nav>
        <h1 class="font-serif text-2xl font-bold">Minhas Inscrições em Eventos</h1>
        <p class="text-white/70 text-sm mt-1">Gerencie suas inscrições e acompanhe eventos</p>
    </div>
</section>

<main class="container mx-auto px-4 py-10">
    <?php if (isset($cancelMsg)): ?>
    <div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-6">
        <i class="fas fa-check-circle mr-2"></i><?= h($cancelMsg) ?>
    </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-4 gap-8">
        <!-- Sidebar -->
        <aside class="space-y-4">
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/5 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-xl font-bold">
                        <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-bold text-[#001644] text-sm"><?= h($user['full_name'] ?? $user['username']) ?></p>
                        <p class="text-[10px] text-[#022E6B]"><?= h($user['email']) ?></p>
                    </div>
                </div>
                <nav class="space-y-1">
                    <a href="/crcap/usuario/perfil.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">
                        <i class="fas fa-user w-4 text-center"></i>Meu Perfil
                    </a>
                    <a href="/crcap/usuario/inscricoes.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold bg-[#001644] text-white">
                        <i class="fas fa-calendar-check w-4 text-center"></i>Minhas Inscrições
                    </a>
                    <a href="/crcap/usuario/downloads.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">
                        <i class="fas fa-download w-4 text-center"></i>Downloads
                    </a>
                    <a href="/crcap/usuario/mensagens.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">
                        <i class="fas fa-envelope w-4 text-center"></i>Minhas Mensagens
                    </a>
                </nav>
            </div>

            <a href="/crcap/pages/eventos.php" class="flex items-center gap-3 p-4 bg-[#BF8D1A] rounded-2xl text-white hover:bg-[#001644] transition">
                <i class="fas fa-calendar-alt text-xl"></i>
                <div>
                    <p class="font-bold text-sm">Ver Eventos</p>
                    <p class="text-white/70 text-xs">Inscreva-se em novos eventos</p>
                </div>
            </a>
        </aside>

        <!-- Content -->
        <div class="lg:col-span-3">
            <!-- Tabs -->
            <div class="flex gap-1 bg-white border border-[#001644]/5 rounded-xl p-1 mb-6 shadow-sm">
                <?php foreach (['upcoming'=>"Próximos ($upcoming_count)",'past'=>"Histórico ($past_count)",'cancelled'=>'Canceladas'] as $t=>$l): ?>
                <a href="?tab=<?= $t ?>" class="flex-1 py-2 rounded-lg text-center text-xs font-semibold transition
                   <?= $tab===$t ? 'bg-[#001644] text-white shadow-sm' : 'text-[#022E6B] hover:text-[#001644]' ?>">
                    <?= $l ?>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($registrations)): ?>
            <div class="bg-white rounded-2xl p-16 text-center border border-[#001644]/5 shadow-sm">
                <i class="fas fa-calendar-times text-4xl text-[#001644]/15 mb-4 block"></i>
                <p class="font-semibold text-[#001644] mb-2">
                    <?= $tab==='upcoming' ? 'Nenhuma inscrição ativa' : ($tab==='past'?'Nenhum evento passado':'Nenhuma inscrição cancelada') ?>
                </p>
                <?php if ($tab==='upcoming'): ?>
                <a href="/crcap/pages/eventos.php" class="inline-flex items-center gap-2 mt-3 px-5 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#BF8D1A] transition">
                    <i class="fas fa-search"></i>Explorar eventos
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>

            <div class="space-y-4">
                <?php foreach ($registrations as $r):
                    $isPast      = $r['event_date'] < date('Y-m-d');
                    $isToday     = $r['event_date'] === date('Y-m-d');
                    $isCancelled = $r['status'] === 'cancelled';
                ?>
                <div class="bg-white rounded-2xl border border-[#001644]/5 shadow-sm overflow-hidden hover:shadow-md transition">
                    <div class="flex">
                        <!-- Date block -->
                        <div class="w-20 bg-gradient-to-b from-[#001644] to-[#022E6B] flex flex-col items-center justify-center py-4 flex-shrink-0">
                            <span class="text-white text-xl font-black"><?= date('d', strtotime($r['event_date'])) ?></span>
                            <span class="text-[#BF8D1A] text-[10px] font-bold uppercase"><?= date('M', strtotime($r['event_date'])) ?></span>
                            <span class="text-white/50 text-[9px]"><?= date('Y', strtotime($r['event_date'])) ?></span>
                        </div>
                        <!-- Info -->
                        <div class="flex-1 p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-[#001644] text-sm mb-1 line-clamp-1"><?= h($r['title']) ?></h3>
                                    <div class="flex flex-wrap gap-3 text-[10px] text-[#022E6B]">
                                        <span class="flex items-center gap-1"><i class="fas fa-clock text-[#BF8D1A]"></i><?= substr($r['start_time'],0,5) ?><?= $r['end_time']?' – '.substr($r['end_time'],0,5):'' ?></span>
                                        <?php if ($r['location']): ?>
                                        <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-[#BF8D1A]"></i><?= h($r['location']) ?></span>
                                        <?php endif; ?>
                                        <span class="flex items-center gap-1"><i class="fas fa-ticket-alt text-[#BF8D1A]"></i>Cód: <strong><?= h($r['confirmation_code'] ?? 'N/A') ?></strong></span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                    <span class="badge
                                        <?= $isCancelled ? 'badge-red' : ($r['status']==='confirmed' ? 'badge-green' : ($isPast ? 'badge-gray' : 'badge-gold')) ?>">
                                        <?= $isCancelled ? 'Cancelada' : ($r['status']==='confirmed' ? 'Confirmada' : ($isPast ? 'Realizado' : ucfirst($r['status']))) ?>
                                    </span>
                                    <?php if ($isToday): ?>
                                    <span class="badge badge-blue">Hoje!</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!$isPast && !$isCancelled): ?>
                            <div class="flex gap-2 mt-4">
                                <a href="/crcap/pages/evento.php?slug=<?= urlencode($r['event_slug']) ?>"
                                   class="px-4 py-1.5 bg-[#001644] text-white rounded-lg text-xs font-semibold hover:bg-[#022E6B] transition">
                                    <i class="fas fa-external-link-alt mr-1"></i>Ver evento
                                </a>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="cancel_id" value="<?= $r['id'] ?>">
                                    <button type="submit" onclick="return confirm('Cancelar inscrição?')"
                                            class="px-4 py-1.5 border border-red-200 text-red-500 rounded-lg text-xs font-semibold hover:bg-red-50 transition">
                                        <i class="fas fa-times mr-1"></i>Cancelar
                                    </button>
                                </form>
                            </div>
                            <?php elseif ($isPast): ?>
                            <a href="/crcap/pages/evento.php?slug=<?= urlencode($r['event_slug']) ?>"
                               class="inline-flex items-center gap-1 mt-4 text-xs text-[#BF8D1A] font-semibold hover:underline">
                                Ver detalhes →
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="flex justify-center gap-2 mt-6">
                <?php for ($i=1;$i<=$pages;$i++): ?>
                <a href="?tab=<?= $tab ?>&p=<?= $i ?>"
                   class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-semibold transition
                          <?= $i===$pg ? 'bg-[#001644] text-white' : 'bg-white border border-[#001644]/10 text-[#001644] hover:border-[#BF8D1A]' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
