<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
$pageTitle  = 'Agenda do Presidente · CRCAP';
$activeMenu = '';

$month = (int)($_GET['mes'] ?? date('m'));
$year  = (int)($_GET['ano'] ?? date('Y'));
if ($month < 1 || $month > 12) $month = (int)date('m');
if ($year < 2020 || $year > 2030) $year = (int)date('Y');

$events = dbFetchAll($pdo,
    "SELECT * FROM president_schedule
     WHERE MONTH(event_date)=? AND YEAR(event_date)=? AND is_public=1
     ORDER BY event_date ASC, start_time ASC",
    [$month, $year]);

$monthNames = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];



// Prev/Next month
$prevM = $month - 1; $prevY = $year; if ($prevM < 1) { $prevM = 12; $prevY--; }
$nextM = $month + 1; $nextY = $year; if ($nextM > 12) { $nextM = 1; $nextY++; }

include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 20% 80%, #BF8D1A 0%, transparent 50%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white">Início</a><i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Agenda do Presidente</span>
        </div>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="flex items-start gap-6">
                <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-calendar-alt"></i></div>
                <div>
                    <h1 class="font-serif text-3xl md:text-4xl font-bold mb-1">Agenda do Presidente</h1>
                    <p class="text-white/70 text-sm">Compromissos e atividades oficiais da Presidência do CRCAP</p>
                </div>
            </div>
            <!-- Navegação de mês -->
            <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm px-4 py-2.5 rounded-xl">
                <a href="?mes=<?= $prevM ?>&ano=<?= $prevY ?>" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition"><i class="fas fa-chevron-left text-xs"></i></a>
                <span class="font-semibold text-sm min-w-36 text-center"><?= $monthNames[$month] ?> <?= $year ?></span>
                <a href="?mes=<?= $nextM ?>&ano=<?= $nextY ?>" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition"><i class="fas fa-chevron-right text-xs"></i></a>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-4 gap-8">

        <!-- Sidebar -->
        <aside class="space-y-5">
            <!-- Filtro de meses -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-3">Navegar por mês</h3>
                <div class="grid grid-cols-3 gap-1.5">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <a href="?mes=<?= $m ?>&ano=<?= $year ?>" class="text-center py-2 rounded-xl text-[10px] font-semibold transition <?= $m===$month ? 'bg-[#001644] text-white' : 'bg-[#F8FAFC] text-[#022E6B] hover:bg-[#BF8D1A] hover:text-white' ?>">
                        <?= substr($monthNames[$m],0,3) ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <div class="flex gap-1 mt-2">
                    <a href="?mes=<?= $month ?>&ano=<?= $year-1 ?>" class="flex-1 text-center py-2 bg-[#F8FAFC] text-[#022E6B] rounded-xl text-[10px] font-semibold hover:bg-[#001644] hover:text-white transition"><?= $year-1 ?></a>
                    <div class="flex-1 text-center py-2 bg-[#001644] text-white rounded-xl text-[10px] font-semibold"><?= $year ?></div>
                    <a href="?mes=<?= $month ?>&ano=<?= $year+1 ?>" class="flex-1 text-center py-2 bg-[#F8FAFC] text-[#022E6B] rounded-xl text-[10px] font-semibold hover:bg-[#001644] hover:text-white transition"><?= $year+1 ?></a>
                </div>
            </div>

            <!-- Legenda -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-3">Tipos de Evento</h3>
                <?php $types = ['meeting'=>['fa-handshake','Reunião','#001644'],'ceremony'=>['fa-star','Cerimônia','#BF8D1A'],'conference'=>['fa-microphone','Conferência','#006633'],'trip'=>['fa-plane','Viagem','#022E6B'],'visit'=>['fa-map-marker-alt','Visita','#BF8D1A'],'other'=>['fa-calendar','Outro','#001644']];
                foreach ($types as $t => $info): ?>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs" style="background:<?= $info[2] ?>"><i class="fas <?= $info[0] ?>"></i></div>
                    <span class="text-xs text-[#022E6B]"><?= $info[1] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Eventos -->
        <div class="lg:col-span-3">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-bold text-[#001644] text-lg"><?= count($events) ?> compromisso(s) em <?= $monthNames[$month] ?></h2>
                <a href="?mes=<?= date('m') ?>&ano=<?= date('Y') ?>" class="text-xs text-[#BF8D1A] font-semibold hover:underline">Mês atual</a>
            </div>

            <?php if (empty($events)): ?>
            <div class="bg-white rounded-2xl p-16 border border-[#001644]/3 text-center">
                <i class="fas fa-calendar-times text-4xl text-[#001644]/20 mb-4 block"></i>
                <p class="text-[#022E6B] font-medium">Nenhum compromisso público registrado para <?= $monthNames[$month] ?></p>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php
                $prevDate = null;
                foreach ($events as $ev):
                    $d = new DateTime($ev['event_date']);
                    $dateStr = $d->format('Y-m-d');
                    $isToday = $dateStr === date('Y-m-d');
                    $isPast  = $d < new DateTime('today');
                    $typeInfo = $types[$ev['event_type']] ?? ['fa-calendar','Evento','#001644'];
                ?>
                <?php if ($dateStr !== $prevDate): ?>
                <div class="flex items-center gap-3 mt-6 mb-2 first:mt-0">
                    <div class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-white text-center flex-shrink-0 <?= $isToday ? 'bg-[#BF8D1A]' : 'bg-[#001644]' ?>">
                        <span class="text-sm font-bold leading-none"><?= $d->format('d') ?></span>
                        <span class="text-[8px] uppercase"><?= $d->format('D') ?></span>
                    </div>
                    <span class="text-sm font-bold text-[#001644] <?= $isToday?'text-[#BF8D1A]':'' ?>"><?= $d->format('d \d\e F') ?><?= $isToday?' · <span class="text-[#BF8D1A] font-bold">Hoje</span>':'' ?></span>
                    <div class="flex-1 h-px bg-[#001644]/10"></div>
                </div>
                <?php $prevDate = $dateStr; endif; ?>

                <!-- CARD: foto esquerda 350x200, infos direita -->
                <div class="bg-white rounded-2xl border border-[#001644]/3 shadow-sm hover:border-[#BF8D1A]/30 hover:shadow-md transition overflow-hidden <?= $isPast?'opacity-60':'' ?>">
                    <div class="flex flex-col sm:flex-row">

                        <!-- Foto esquerda: 350x200px fixos -->
                        <?php if ($ev['image']): ?>
                        <div class="relative flex-shrink-0 overflow-hidden rounded-tl-2xl rounded-bl-none sm:rounded-bl-2xl rounded-tr-2xl sm:rounded-tr-none" style="width:350px;height:200px;min-width:350px;">
                            <img src="<?= htmlspecialchars($ev['image']) ?>"
                                 alt="<?= htmlspecialchars($ev['title']) ?>"
                                 style="width:350px;height:200px;object-fit:cover;object-position:center;display:block;">
                            <!-- Badge tipo -->
                            <div class="absolute top-2 left-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold text-white"
                                      style="background:<?= $typeInfo[2] ?>cc;backdrop-filter:blur(4px);">
                                    <i class="fas <?= $typeInfo[0] ?>"></i> <?= $typeInfo[1] ?>
                                </span>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Sem foto: ícone colorido no lugar -->
                        <div class="flex-shrink-0 flex items-center justify-center rounded-tl-2xl rounded-bl-none sm:rounded-bl-2xl rounded-tr-2xl sm:rounded-tr-none"
                             style="width:350px;height:200px;min-width:350px;background:<?= $typeInfo[2] ?>18;">
                            <div class="text-center">
                                <i class="fas <?= $typeInfo[0] ?> text-5xl mb-2 block" style="color:<?= $typeInfo[2] ?>;opacity:0.4;"></i>
                                <span class="text-xs font-semibold" style="color:<?= $typeInfo[2] ?>"><?= $typeInfo[1] ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Infos à direita -->
                        <div class="flex-1 flex flex-col justify-between p-5 min-w-0">

                            <!-- Topo: título + badge status -->
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h3 class="font-bold text-[#001644] text-sm leading-snug"><?= htmlspecialchars($ev['title']) ?></h3>
                                    <span class="badge <?= $ev['status']==='confirmed'?'badge-green':($ev['status']==='cancelled'?'badge-red':'badge-gold') ?> flex-shrink-0 text-[9px]"><?= $ev['status'] ?></span>
                                </div>

                                <!-- Horário e local -->
                                <div class="flex flex-col gap-1.5 mb-3">
                                    <span class="flex items-center gap-2 text-xs font-semibold text-[#BF8D1A]">
                                        <i class="fas fa-clock w-3 text-center"></i>
                                        <?= substr($ev['start_time'],0,5) ?><?= $ev['end_time'] ? ' – '.substr($ev['end_time'],0,5) : '' ?>
                                    </span>
                                    <?php if ($ev['location']): ?>
                                    <span class="flex items-center gap-2 text-xs text-[#022E6B]">
                                        <i class="fas fa-map-marker-alt w-3 text-center text-[#BF8D1A]"></i>
                                        <?= htmlspecialchars($ev['location']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <span class="flex items-center gap-2 text-xs text-[#022E6B]">
                                        <i class="fas fa-calendar w-3 text-center text-[#BF8D1A]"></i>
                                        <?= $d->format('d \\d\\e F \\d\\e Y') ?>
                                    </span>
                                </div>

                                <?php if ($ev['description']): ?>
                                <p class="text-xs text-[#022E6B]/70 leading-relaxed line-clamp-2 mb-3"><?= htmlspecialchars($ev['description']) ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Rodapé: botões de compartilhamento -->
                            <?php
                            $shareUrl   = urlencode('https://artemidiaweb.com.br/crcap/pages/agenda.php?mes='.$month.'&ano='.$year);
                            $shareTitle = urlencode($ev['title'].' – '.$d->format('d/m/Y').' – CRCAP');
                            $shareWpp   = urlencode('📅 '.$ev['title']."
🕐 ".substr($ev['start_time'],0,5).($ev['end_time'] ? ' – '.substr($ev['end_time'],0,5) : '').($ev['location'] ? "
📍 ".$ev['location'] : '')."
🗓 ".$d->format('d/m/Y')."

https://artemidiaweb.com.br/crcap/pages/agenda.php?mes=".$month."&ano=".$year);
                            $copyUrl    = htmlspecialchars('https://artemidiaweb.com.br/crcap/pages/agenda.php?mes='.$month.'&ano='.$year);
                            ?>
                            <div class="flex items-center gap-1.5 pt-3 border-t border-[#001644]/5 mt-auto">
                                <span class="text-[10px] text-[#022E6B]/40 font-medium mr-0.5">Compartilhar:</span>
                                <a href="https://api.whatsapp.com/send?text=<?= $shareWpp ?>" target="_blank" rel="noopener"
                                   class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs hover:scale-110 hover:shadow-md transition"
                                   style="background:#25D366;" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                <a href="https://facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener"
                                   class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs hover:scale-110 hover:shadow-md transition"
                                   style="background:#1877F2;" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                                <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener"
                                   class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs hover:scale-110 hover:shadow-md transition"
                                   style="background:#1DA1F2;" title="Twitter / X"><i class="fab fa-twitter"></i></a>
                                <a href="https://linkedin.com/shareArticle?mini=true&url=<?= $shareUrl ?>&title=<?= $shareTitle ?>" target="_blank" rel="noopener"
                                   class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs hover:scale-110 hover:shadow-md transition"
                                   style="background:#0077B5;" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                                <button onclick="copyAgendaLink('<?= $copyUrl ?>', this)"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs hover:scale-110 hover:shadow-md transition"
                                        style="background:#001644;" title="Copiar link"><i class="fas fa-link"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function copyAgendaLink(url, btn) {
    navigator.clipboard.writeText(url).then(function() {
        const icon = btn.querySelector('i');
        icon.className = 'fas fa-check';
        btn.style.background = '#006633';
        setTimeout(function() {
            icon.className = 'fas fa-link';
            btn.style.background = '#001644';
        }, 2000);
    }).catch(function() {
        prompt('Copie o link:', url);
    });
}
</script>

<?php include '../includes/footer.php'; ?>