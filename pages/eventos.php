<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle  = 'Eventos · CRCAP';
$activeMenu = 'desenv';

$busca    = trim($_GET['busca'] ?? '');
$tipo     = $_GET['tipo'] ?? '';
$page_num = max(1,(int)($_GET['pagina']??1));
$perPage  = 9;
$offset   = ($page_num-1)*$perPage;

$where  = ["status='published'","visibility='public'","event_date>=CURDATE()"];
$params = [];
if ($busca) { $where[] = "(title LIKE ? OR description LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($tipo)  { $where[] = "event_type=?"; $params[] = $tipo; }

$events = dbFetchAll($pdo, "SELECT * FROM events WHERE ".implode(' AND ',$where)." ORDER BY event_date ASC LIMIT $perPage OFFSET $offset", $params);
$total  = dbFetch($pdo, "SELECT COUNT(*) as total FROM events WHERE ".implode(' AND ',$where), $params)['total'] ?? 0;
$pages  = ceil($total/$perPage);

include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 70% 30%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Eventos</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-calendar-alt"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Próximos Eventos</h1>
                <p class="text-white/70 text-sm max-w-2xl">Cursos, palestras, workshops, congressos e demais eventos promovidos pelo CRCAP.</p>
            </div>
        </div>
        <!-- Busca no hero -->
        <div class="mt-8 max-w-lg">
            <form method="GET" class="flex gap-2">
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar eventos..."
                    class="flex-1 px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:border-[#BF8D1A] text-sm">
                <button type="submit" class="px-5 py-3 bg-[#BF8D1A] text-white font-semibold rounded-xl hover:bg-white hover:text-[#001644] transition text-sm">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">

    <!-- Filtros de Tipo -->
    <div class="flex flex-wrap gap-2 mb-8">
        <?php $tipos = [
            ['','Todos os Eventos','fa-calendar'],
            ['conference','Congressos','fa-microphone'],
            ['meeting','Reuniões','fa-handshake'],
            ['trip','Palestras','fa-chalkboard-teacher'],
            ['other','Workshops','fa-tools'],
        ]; foreach ($tipos as $t): ?>
        <a href="?tipo=<?= $t[0] ?>&busca=<?= urlencode($busca) ?>" class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition <?= $tipo===$t[0]?'bg-[#001644] text-white shadow-md':'bg-white text-[#001644] border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A]' ?>">
            <i class="fas <?= $t[2] ?> text-[10px]"></i><?= $t[1] ?>
        </a>
        <?php endforeach; ?>
        <span class="ml-auto self-center text-sm text-[#022E6B]"><strong class="text-[#001644]"><?= $total ?></strong> evento(s)</span>
    </div>

    <!-- Grid de Eventos -->
    <?php if (!empty($events)): ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        <?php foreach ($events as $ev): $evDate = new DateTime($ev['event_date']); ?>
        <div class="bg-white rounded-2xl overflow-hidden border border-[#001644]/3 shadow-sm hover:-translate-y-2 hover:shadow-xl hover:shadow-[#001644]/10 transition group">
            <div class="relative h-48 overflow-hidden">
                <img src="<?= htmlspecialchars($ev['featured_image'] ?: 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600&h=300&fit=crop') ?>" alt="<?= htmlspecialchars($ev['title']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                <div class="absolute top-3 left-3 bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-xl text-center shadow-lg">
                    <span class="block text-lg font-bold text-[#001644] leading-none"><?= $evDate->format('d') ?></span>
                    <span class="text-[10px] text-[#BF8D1A] font-bold uppercase"><?= $evDate->format('M') ?></span>
                </div>
                <?php if ($ev['is_free']): ?><span class="absolute top-3 right-3 bg-[#006633] text-white text-[9px] font-bold px-2 py-0.5 rounded-full">GRATUITO</span>
                <?php elseif ($ev['price'] > 0): ?><span class="absolute top-3 right-3 bg-[#BF8D1A] text-white text-[9px] font-bold px-2 py-0.5 rounded-full">R$ <?= number_format($ev['price'],2,',','.') ?></span><?php endif; ?>
                <?php if ($ev['is_featured']): ?><span class="absolute bottom-3 left-3 bg-[#BF8D1A] text-white text-[9px] font-bold px-2 py-0.5 rounded-full"><i class="fas fa-star mr-1"></i>DESTAQUE</span><?php endif; ?>
            </div>
            <div class="p-5">
                <?php if ($ev['event_type']): ?><span class="text-[10px] font-bold text-[#BF8D1A] uppercase tracking-wider"><?= htmlspecialchars($ev['event_type']) ?></span><?php endif; ?>
                <h3 class="font-bold text-[#001644] text-sm mt-1 mb-3 group-hover:text-[#BF8D1A] transition line-clamp-2"><?= htmlspecialchars($ev['title']) ?></h3>
                <?php if ($ev['description']): ?><p class="text-[10px] text-[#022E6B] mb-3 line-clamp-2"><?= htmlspecialchars($ev['description']) ?></p><?php endif; ?>
                <div class="space-y-1.5 mb-4">
                    <div class="flex items-center gap-2 text-[10px] text-[#022E6B]"><i class="fas fa-clock text-[#BF8D1A] w-3"></i><?= substr($ev['start_time'],0,5) ?><?= $ev['end_time'] ? ' – '.substr($ev['end_time'],0,5) : '' ?></div>
                    <?php if ($ev['location']): ?><div class="flex items-center gap-2 text-[10px] text-[#022E6B]"><i class="fas fa-map-marker-alt text-[#BF8D1A] w-3"></i><span class="truncate"><?= htmlspecialchars($ev['location']) ?></span></div><?php endif; ?>
                    <?php if ($ev['registration_required'] && $ev['max_participants']): ?><div class="flex items-center gap-2 text-[10px] text-[#022E6B]"><i class="fas fa-users text-[#BF8D1A] w-3"></i><?= $ev['current_participants'] ?>/<?= $ev['max_participants'] ?> inscritos</div><?php endif; ?>
                </div>
                <a href="/crcap/pages/evento.php?slug=<?= urlencode($ev['slug']) ?>" class="block w-full py-2.5 text-[10px] font-bold text-center text-[#001644] border border-[#001644]/20 rounded-xl hover:bg-[#001644] hover:text-white transition">
                    <?= $ev['registration_required'] ? 'Inscrever-se' : 'Ver detalhes' ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Exemplos quando BD vazio -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
        <?php $exEvents = [
            ['Congresso de Administração 2026','https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600&h=300&fit=crop','15','MAR','09:00 – 18:00','Centro de Convenções – AP',true,'1','congress','50'],
            ['Workshop de Gestão Pública','https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&h=300&fit=crop','22','MAR','09:00 – 17:00','Auditório CRCAP',true,'0','workshop','80'],
            ['Palestra: Liderança e Inovação','https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&h=300&fit=crop','05','ABR','18:30 – 21:00','Online – Zoom',true,'0','palestra','–'],
            ['Curso de Planejamento Estratégico','https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=600&h=300&fit=crop','12','ABR','08:00 – 17:00','Sede CRCAP',false,'350','curso','30'],
            ['Reunião de Conselhos – Regional Norte','https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=600&h=300&fit=crop','18','ABR','10:00 – 16:00','Belém – PA',true,'0','meeting','–'],
            ['Seminário de Compliance Corporativo','https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=600&h=300&fit=crop','25','ABR','09:00 – 18:00','Hotel Macapá',false,'500','seminar','120'],
        ]; foreach ($exEvents as $ev): ?>
        <div class="bg-white rounded-2xl overflow-hidden border border-[#001644]/3 shadow-sm hover:-translate-y-2 hover:shadow-xl hover:shadow-[#001644]/10 transition group">
            <div class="relative h-48 overflow-hidden">
                <img src="<?= $ev[1] ?>" alt="<?= $ev[0] ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                <div class="absolute top-3 left-3 bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-xl text-center shadow-lg">
                    <span class="block text-lg font-bold text-[#001644] leading-none"><?= $ev[2] ?></span>
                    <span class="text-[10px] text-[#BF8D1A] font-bold uppercase"><?= $ev[3] ?></span>
                </div>
                <?php if ($ev[6]): ?><span class="absolute top-3 right-3 bg-[#006633] text-white text-[9px] font-bold px-2 py-0.5 rounded-full">GRATUITO</span><?php else: ?><span class="absolute top-3 right-3 bg-[#BF8D1A] text-white text-[9px] font-bold px-2 py-0.5 rounded-full">R$ <?= $ev[7] ?></span><?php endif; ?>
            </div>
            <div class="p-5">
                <span class="text-[10px] font-bold text-[#BF8D1A] uppercase tracking-wider"><?= $ev[8] ?></span>
                <h3 class="font-bold text-[#001644] text-sm mt-1 mb-3 group-hover:text-[#BF8D1A] transition"><?= $ev[0] ?></h3>
                <div class="space-y-1.5 mb-4">
                    <div class="flex items-center gap-2 text-[10px] text-[#022E6B]"><i class="fas fa-clock text-[#BF8D1A] w-3"></i><?= $ev[4] ?></div>
                    <div class="flex items-center gap-2 text-[10px] text-[#022E6B]"><i class="fas fa-map-marker-alt text-[#BF8D1A] w-3"></i><span class="truncate"><?= $ev[5] ?></span></div>
                    <div class="flex items-center gap-2 text-[10px] text-[#022E6B]"><i class="fas fa-users text-[#BF8D1A] w-3"></i><?= $ev[9] ?> vagas</div>
                </div>
                <a href="#" class="block w-full py-2.5 text-[10px] font-bold text-center text-[#001644] border border-[#001644]/20 rounded-xl hover:bg-[#001644] hover:text-white transition">
                    Inscrever-se
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Paginação -->
    <?php if ($pages > 1): ?>
    <div class="flex justify-center gap-2">
        <?php for ($i=1; $i<=$pages; $i++): ?>
        <a href="?pagina=<?= $i ?>&busca=<?= urlencode($busca) ?>&tipo=<?= urlencode($tipo) ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold transition <?= $i===$page_num?'bg-[#001644] text-white':'bg-white text-[#001644] border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A]' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

</main>

<?php include '../includes/footer.php'; ?>
