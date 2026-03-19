<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
$pageTitle = 'Agenda de Lives · Desenvolvimento Profissional · CRCAP';
$activeMenu = 'desenv';

$events = dbFetchAll($pdo,
    "SELECT * FROM events WHERE status='published' AND event_type IN('live','conference') ORDER BY event_date ASC");

include '../../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 30% 70%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white">Início</a><i class="fas fa-chevron-right text-[9px]"></i>
            <span>Desenvolvimento Profissional</span><i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Agenda de Lives</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-video"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Agenda de Lives</h1>
                <p class="text-white/70 text-sm max-w-2xl">Acompanhe as transmissões ao vivo com palestras, debates e formações para profissionais de administração.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-4 gap-8">

        <!-- Sidebar menu -->
        <aside>
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm sticky top-24">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Desenvolvimento Profissional</h3>
                <nav class="space-y-1">
                    <?php $devMenu = [
                        ['/pages/desenvolvimento/agenda-lives.php','fa-video','Agenda de Lives','active'],
                        ['/pages/desenvolvimento/cursos-ead.php','fa-laptop','Cursos a Distância',''],
                        ['/pages/desenvolvimento/o-que-e.php','fa-question-circle','O que é?',''],
                        ['/pages/desenvolvimento/cadastro-palestrante.php','fa-microphone','Cadastro de Palestrante',''],
                        ['/pages/desenvolvimento/educacao-continuada.php','fa-book-open','Educação Continuada',''],
                        ['/pages/desenvolvimento/sistema-eventos.php','fa-calendar-alt','Sistema de Evento',''],
                        ['/pages/desenvolvimento/cursos-epc.php','fa-certificate','Cursos Credenciados EPC',''],
                    ]; foreach ($devMenu as $m): ?>
                    <a href="<?= $m[0] ?>" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs transition <?= $m[3]==='active' ? 'bg-[#001644] text-white font-semibold' : 'text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                        <i class="fas <?= $m[1] ?> w-4 text-center <?= $m[3]==='active' ? 'text-[#BF8D1A]' : '' ?>"></i><?= $m[2] ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <!-- Content -->
        <div class="lg:col-span-3">
            <!-- Próxima live em destaque -->
            <?php
            $exLives = [
                ['title'=>'Gestão de Pessoas na Era Digital','description'=>'Como líderes podem utilizar ferramentas digitais para potencializar o desempenho das equipes e reter talentos.','event_date'=>'2026-03-10','start_time'=>'19:00:00','end_time'=>'21:00:00','external_link'=>'https://youtube.com','slug'=>'#','featured_image'=>'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&h=400&fit=crop'],
                ['title'=>'Planejamento Estratégico 2026','description'=>'Ferramentas e técnicas modernas para construir um planejamento estratégico eficaz para sua empresa ou carreira.','event_date'=>'2026-03-17','start_time'=>'18:30:00','end_time'=>'20:30:00','external_link'=>'https://youtube.com','slug'=>'#','featured_image'=>'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=800&h=400&fit=crop'],
                ['title'=>'Compliance e Governança Corporativa','description'=>'A importância do compliance no ambiente empresarial moderno e como implementar práticas de governança.','event_date'=>'2026-03-24','start_time'=>'19:00:00','end_time'=>'21:00:00','external_link'=>'https://youtube.com','slug'=>'#','featured_image'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&h=400&fit=crop'],
                ['title'=>'Finanças Pessoais para Administradores','description'=>'Como usar o conhecimento em administração para organizar suas finanças pessoais e construir patrimônio.','event_date'=>'2026-04-07','start_time'=>'19:00:00','end_time'=>'20:30:00','external_link'=>'https://youtube.com','slug'=>'#','featured_image'=>'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=800&h=400&fit=crop'],
                ['title'=>'Marketing Digital para PMEs','description'=>'Estratégias acessíveis de marketing digital para pequenas e médias empresas aumentarem sua presença online.','event_date'=>'2026-04-14','start_time'=>'19:00:00','end_time'=>'21:00:00','external_link'=>'https://youtube.com','slug'=>'#','featured_image'=>'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&h=400&fit=crop'],
            ];
            $displayLives = !empty($events) ? $events : $exLives;
            $nextLive = $displayLives[0];
            $nextDate = new DateTime($nextLive['event_date']);
            ?>

            <!-- Próxima live - destaque -->
            <div class="bg-white rounded-2xl overflow-hidden border border-[#001644]/3 shadow-sm mb-8 group">
                <div class="relative h-60 overflow-hidden">
                    <img src="<?= htmlspecialchars($nextLive['featured_image'] ?? '') ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#001644] via-[#001644]/40 to-transparent"></div>
                    <div class="absolute top-4 left-4">
                        <span class="bg-red-500 text-white text-[10px] font-bold px-3 py-1 rounded-full flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>PRÓXIMA LIVE
                        </span>
                    </div>
                    <div class="absolute bottom-4 left-4 right-4 text-white">
                        <p class="text-[10px] text-white/70 mb-1"><?= $nextDate->format('d/m/Y') ?> às <?= substr($nextLive['start_time'],0,5) ?></p>
                        <h2 class="font-serif text-2xl font-bold"><?= htmlspecialchars($nextLive['title']) ?></h2>
                    </div>
                </div>
                <div class="p-6 flex items-start justify-between gap-4">
                    <p class="text-sm text-[#022E6B] flex-1"><?= htmlspecialchars($nextLive['description']) ?></p>
                    <?php if ($nextLive['external_link']): ?>
                    <a href="<?= htmlspecialchars($nextLive['external_link']) ?>" target="_blank" class="flex-shrink-0 px-6 py-3 bg-red-500 text-white font-bold rounded-xl hover:bg-[#001644] transition text-sm flex items-center gap-2">
                        <i class="fab fa-youtube"></i>Assistir
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendário de Lives -->
            <h2 class="font-bold text-[#001644] text-lg mb-5 flex items-center gap-2"><i class="fas fa-calendar-alt text-[#BF8D1A]"></i>Calendário de Lives <?= date('Y') ?></h2>
            <div class="space-y-3">
                <?php foreach (array_slice($displayLives, 1) as $live):
                    $d = new DateTime($live['event_date']);
                    $isPast = $d < new DateTime();
                ?>
                <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm flex gap-4 <?= $isPast ? 'opacity-60' : '' ?> hover:border-[#BF8D1A]/30 hover:shadow-md transition">
                    <div class="w-16 h-16 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-xl flex flex-col items-center justify-center text-white flex-shrink-0">
                        <span class="text-xl font-bold font-serif leading-none"><?= $d->format('d') ?></span>
                        <span class="text-[9px] uppercase mt-0.5"><?= $d->format('M') ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold text-[#001644] text-sm mb-1"><?= htmlspecialchars($live['title']) ?></h3>
                                <p class="text-xs text-[#022E6B] line-clamp-2 mb-2"><?= htmlspecialchars($live['description']) ?></p>
                                <div class="flex items-center gap-3 text-[10px] text-[#022E6B]">
                                    <span class="flex items-center gap-1"><i class="fas fa-clock text-[#BF8D1A]"></i><?= substr($live['start_time'],0,5) ?></span>
                                    <?php if ($live['end_time']): ?><span class="flex items-center gap-1"><i class="fas fa-hourglass-end text-[#BF8D1A]"></i><?= substr($live['end_time'],0,5) ?></span><?php endif; ?>
                                </div>
                            </div>
                            <?php if ($live['external_link'] && !$isPast): ?>
                            <a href="<?= htmlspecialchars($live['external_link']) ?>" target="_blank" class="flex-shrink-0 px-4 py-2 bg-[#001644] text-white text-[10px] font-bold rounded-xl hover:bg-[#BF8D1A] transition flex items-center gap-1.5">
                                <i class="fab fa-youtube"></i>Acessar
                            </a>
                            <?php elseif ($isPast): ?>
                            <span class="badge badge-gray flex-shrink-0">Encerrado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Canais -->
            <div class="mt-10 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-8 text-white text-center">
                <i class="fab fa-youtube text-red-400 text-4xl mb-4 block"></i>
                <h3 class="font-bold text-xl mb-2">Siga nosso canal no YouTube</h3>
                <p class="text-sm text-white/70 mb-6">Assista às gravações de todas as lives anteriores e ative o sininho para não perder os próximos eventos.</p>
                <a href="https://youtube.com" target="_blank" class="inline-flex items-center gap-2 px-8 py-3.5 bg-red-500 text-white font-bold rounded-xl hover:bg-white hover:text-red-500 transition">
                    <i class="fab fa-youtube text-lg"></i>Inscrever-se no canal
                </a>
            </div>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>
