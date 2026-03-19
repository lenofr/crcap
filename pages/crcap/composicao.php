<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle = 'Composição CRCAP';
$activePage = 'crcap-composicao';
$page = dbQueryOne("SELECT * FROM pages WHERE slug = 'composicao' AND status = 'published'");
include __DIR__ . '/../../includes/header.php';
$bannerTitle = 'Composição CRCAP';
$bannerSubtitle = 'Conheça os membros do Conselho Regional de Administração do Amapá';
$bannerIcon = 'fa-users';
$bannerBreadcrumb = [['Home','/index.php'],['CRCAP',null],['Composição',null]];
include __DIR__ . '/../../includes/page_banner.php';

// Composição estática (dados viriam do BD via users ou pages)
$composicao = [
    'Diretoria Executiva' => [
        ['Presidente','Dr. João Silva, CRA 0001','fa-user-tie','#001644'],
        ['Vice-Presidente','Dra. Maria Santos, CRA 0002','fa-user-tie','#022E6B'],
        ['Secretário-Geral','Carlos Oliveira, CRA 0010','fa-user-cog','#001644'],
        ['Tesoureiro','Ana Lima, CRA 0015','fa-coins','#BF8D1A'],
    ],
    'Câmara Administrativa' => [
        ['Presidente da Câmara','Paulo Ferreira, CRA 0020','fa-balance-scale','#006633'],
        ['Membro','Lucia Menezes, CRA 0025','fa-user','#006633'],
        ['Membro','Roberto Costa, CRA 0030','fa-user','#006633'],
    ],
    'Câmara de Fiscalização' => [
        ['Presidente da Câmara','Sandra Pereira, CRA 0035','fa-search','#022E6B'],
        ['Membro','Marcos Alves, CRA 0040','fa-user','#022E6B'],
        ['Membro','Fernanda Rocha, CRA 0045','fa-user','#022E6B'],
    ],
    'Câmara de Desenvolvimento Profissional' => [
        ['Presidente da Câmara','Rafael Mendes, CRA 0050','fa-graduation-cap','#BF8D1A'],
        ['Membro','Camila Torres, CRA 0055','fa-user','#BF8D1A'],
        ['Membro','Diego Pinheiro, CRA 0060','fa-user','#BF8D1A'],
    ],
    'Câmara de Registro' => [
        ['Presidente da Câmara','Beatriz Nunes, CRA 0065','fa-file-signature','#001644'],
        ['Membro','Thiago Barbosa, CRA 0070','fa-user','#001644'],
        ['Membro','Juliana Gomes, CRA 0075','fa-user','#001644'],
    ],
    'Controle Interno' => [
        ['Controlador','Eduardo Farias, CRA 0080','fa-check-double','#006633'],
    ],
];
?>
<main class="container mx-auto px-4 py-12">
<div class="grid lg:grid-cols-4 gap-8">
    <aside class="lg:col-span-1">
        <div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden">
            <div class="bg-[#001644] px-5 py-4"><h3 class="text-sm font-bold text-white flex items-center gap-2"><i class="fas fa-landmark"></i> CRCAP</h3></div>
            <nav class="p-2">
                <?php foreach ([['historico.php','fa-book-open','Histórico',false],['organograma.php','fa-sitemap','Organograma',false],['delegacias.php','fa-map-marker-alt','Delegacias',false],['composicao.php','fa-users','Composição',true],['editais.php','fa-file-alt','Editais',false],['concurso.php','fa-trophy','Concurso',false]] as $l): ?>
                <a href="/crcap/pages/crcap/<?= $l[0] ?>" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[11px] font-semibold transition mb-0.5 <?= $l[3] ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                    <i class="fas <?= $l[1] ?> w-4 text-center text-[#BF8D1A]"></i><?= $l[2] ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>

    <div class="lg:col-span-3 space-y-8">
        <?php if ($page && $page['content']): ?>
        <div class="bg-white rounded-2xl border border-[#001644]/5 p-7 prose max-w-none text-sm"><?= $page['content'] ?></div>
        <?php endif; ?>

        <?php foreach ($composicao as $camara => $membros): ?>
        <div>
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px flex-1 bg-[#001644]/10"></div>
                <h2 class="text-sm font-bold text-[#001644] uppercase tracking-wider whitespace-nowrap"><?= $camara ?></h2>
                <div class="h-px flex-1 bg-[#001644]/10"></div>
            </div>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($membros as $m): ?>
                <div class="bg-white border border-[#001644]/5 rounded-2xl p-5 text-center hover:shadow-lg hover:-translate-y-1 hover:border-[#BF8D1A]/20 transition group">
                    <div class="w-16 h-16 rounded-2xl mx-auto mb-3 flex items-center justify-center text-white text-2xl" style="background:<?= $m[3] ?>">
                        <i class="fas <?= $m[2] ?>"></i>
                    </div>
                    <p class="text-[9px] font-bold uppercase tracking-wider text-[#BF8D1A] mb-1"><?= $m[0] ?></p>
                    <p class="text-[11px] font-semibold text-[#001644] leading-tight"><?= $m[1] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
