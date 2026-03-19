<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle  = 'O que é Desenvolvimento Profissional?';
$activePage = 'dev';

$devMenu = [
    ['agenda-lives.php',       'fa-broadcast-tower','Agenda de Lives'],
    ['cursos-ead.php',         'fa-laptop',         'Cursos a Distância'],
    ['o-que-e.php',            'fa-question-circle','O que é?'],
    ['cadastro-palestrante.php','fa-microphone',    'Cadastro de Palestrante'],
    ['educacao-continuada.php','fa-book',           'Educação Continuada'],
    ['sistema-eventos.php',    'fa-calendar-alt',   'Sistema de Evento'],
    ['cursos-epc.php',         'fa-certificate',    'Cursos Credenciados EPC'],
];

$page = dbQueryOne("SELECT * FROM pages WHERE slug = 'o-que-e' AND status = 'published'");
include __DIR__ . '/../../includes/header.php';
$bannerTitle     = 'O que é Desenvolvimento Profissional?';
$bannerSubtitle  = 'Conheça o programa e seus objetivos';
$bannerIcon      = 'fa-question-circle';
$bannerBreadcrumb = [['Home','/index.php'],['Des. Profissional',null],['O que é Desenvolvimento Profissional?',null]];
include __DIR__ . '/../../includes/page_banner.php';
?>
<main class="container mx-auto px-4 py-12">
<div class="grid lg:grid-cols-4 gap-8">
    <aside class="lg:col-span-1">
        <div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden">
            <div class="bg-[#001644] px-5 py-4"><h3 class="text-sm font-bold text-white flex items-center gap-2"><i class="fas fa-graduation-cap"></i> Des. Profissional</h3></div>
            <nav class="p-2">
                <?php foreach ($devMenu as $m): ?>
                <a href="/crcap/pages/desenvolvimento/<?php echo $m[0]; ?>"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[11px] font-semibold transition mb-0.5 <?php echo basename($_SERVER['PHP_SELF'])==$m[0] ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]'; ?>">
                    <i class="fas <?php echo $m[1]; ?> w-4 text-center text-[#BF8D1A] text-[9px]"></i><?php echo $m[2]; ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>
    <div class="lg:col-span-3">
        <?php if ($page && $page['content']): ?>
        <div class="bg-white rounded-2xl border border-[#001644]/5 p-8 prose max-w-none text-sm"><?php echo $page['content']; ?></div>
        <?php else: ?>
        <div class="bg-white rounded-2xl border border-[#001644]/5 p-12 text-center">
            <div class="w-16 h-16 bg-[#001644]/8 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl text-[#001644]"><i class="fas fa-question-circle"></i></div>
            <h2 class="text-xl font-bold text-[#001644] mb-3">O que é Desenvolvimento Profissional?</h2>
            <p class="text-sm text-[#001644]/60 max-w-lg mx-auto">Conheça o programa e seus objetivos</p>
            <div class="mt-6 p-4 bg-[#F8FAFC] rounded-xl text-xs text-[#001644]/50">
                <i class="fas fa-info-circle text-[#BF8D1A] mr-1"></i>
                Conteúdo será disponibilizado em breve. Entre em contato para mais informações.
            </div>
            <a href="/crcap/pages/contato.php" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition">
                <i class="fas fa-headset"></i> Fale Conosco
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
