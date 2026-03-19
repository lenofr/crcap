<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle = 'Delegacias e Representações';
$activePage = 'crcap-delegacias';
$page = dbQueryOne("SELECT * FROM pages WHERE slug = 'delegacias' AND status = 'published'");
include __DIR__ . '/../../includes/header.php';
$bannerTitle = 'Delegacias e Representações';
$bannerSubtitle = 'Unidades do CRCAP distribuídas pelo Estado do Amapá';
$bannerIcon = 'fa-map-marker-alt';
$bannerBreadcrumb = [['Home','/index.php'],['CRCAP',null],['Delegacias',null]];
include __DIR__ . '/../../includes/page_banner.php';

$delegacias = [
    ['Sede – Macapá','Av. Cândido Mendes, 1799 – Centro','CEP: 68900-912 – Macapá/AP','(96) 3222-0000','macapa@crcap.gov.br','Mon-Fri 8h-17h','fa-building','#001644','-0.0349', '-51.0694'],
    ['Delegacia de Santana','Rua Principal, 500 – Centro','CEP: 68925-000 – Santana/AP','(96) 3281-0000','santana@crcap.gov.br','Seg-Sex 8h-14h','fa-map-marker-alt','#BF8D1A','-0.0586','-51.1736'],
    ['Delegacia de Laranjal do Jari','Av. 7 de Setembro, 200 – Centro','CEP: 68920-000 – Laranjal do Jari/AP','(96) 3671-0000','laranjal@crcap.gov.br','Seg-Sex 8h-14h','fa-map-marker-alt','#006633','-0.7992','-52.4619'],
];
?>
<main class="container mx-auto px-4 py-12">
<div class="grid lg:grid-cols-4 gap-8">
    <aside class="lg:col-span-1">
        <div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden">
            <div class="bg-[#001644] px-5 py-4"><h3 class="text-sm font-bold text-white flex items-center gap-2"><i class="fas fa-landmark"></i> CRCAP</h3></div>
            <nav class="p-2">
                <?php foreach ([['historico.php','fa-book-open','Histórico',false],['organograma.php','fa-sitemap','Organograma',false],['delegacias.php','fa-map-marker-alt','Delegacias',true],['composicao.php','fa-users','Composição',false],['editais.php','fa-file-alt','Editais',false],['concurso.php','fa-trophy','Concurso',false]] as $l): ?>
                <a href="/crcap/pages/crcap/<?= $l[0] ?>" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[11px] font-semibold transition mb-0.5 <?= $l[3] ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                    <i class="fas <?= $l[1] ?> w-4 text-center text-[#BF8D1A]"></i><?= $l[2] ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>

    <div class="lg:col-span-3 space-y-6">
        <?php if ($page && $page['content']): ?>
        <div class="bg-white rounded-2xl border border-[#001644]/5 p-7 prose max-w-none text-sm text-[#001644]"><?= $page['content'] ?></div>
        <?php endif; ?>

        <div class="grid md:grid-cols-1 gap-5">
            <?php foreach ($delegacias as $d): ?>
            <div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden hover:shadow-xl hover:border-[#BF8D1A]/20 transition-all group">
                <div class="flex flex-col md:flex-row">
                    <div class="md:w-2 flex-shrink-0" style="background:<?= $d[7] ?>"></div>
                    <div class="flex-1 p-6">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white text-xl flex-shrink-0" style="background:<?= $d[7] ?>">
                                    <i class="fas <?= $d[6] ?>"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-[#001644] text-base group-hover:text-[#BF8D1A] transition"><?= $d[0] ?></h3>
                                    <p class="text-[11px] text-[#022E6B]/70"><?= $d[1] ?></p>
                                    <p class="text-[10px] text-[#022E6B]/50"><?= $d[2] ?></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-[11px]">
                                <div class="flex items-center gap-2 text-[#022E6B]/70"><i class="fas fa-phone text-[#BF8D1A] w-3"></i><?= $d[3] ?></div>
                                <div class="flex items-center gap-2 text-[#022E6B]/70"><i class="fas fa-envelope text-[#BF8D1A] w-3"></i><?= $d[4] ?></div>
                                <div class="flex items-center gap-2 text-[#022E6B]/70"><i class="fas fa-clock text-[#BF8D1A] w-3"></i><?= $d[5] ?></div>
                                <a href="https://maps.google.com/?q=<?= $d[8] ?>,<?= $d[9] ?>" target="_blank"
                                   class="flex items-center gap-2 text-[#BF8D1A] font-semibold hover:underline">
                                    <i class="fas fa-map text-[#BF8D1A] w-3"></i>Ver no mapa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Mapa estático placeholder -->
        <div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden">
            <div class="bg-[#F8FAFC] border-b border-[#001644]/5 px-6 py-4 flex items-center justify-between">
                <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2"><i class="fas fa-map text-[#BF8D1A]"></i> Localização das Unidades</h3>
            </div>
            <div class="h-64 bg-gradient-to-br from-[#001644]/5 to-[#BF8D1A]/5 flex items-center justify-center">
                <div class="text-center text-[#001644]/40">
                    <i class="fas fa-map-marked-alt text-6xl mb-3 block"></i>
                    <p class="text-sm font-medium">Mapa interativo</p>
                    <p class="text-xs">Configure a API do Google Maps para exibir o mapa</p>
                    <a href="https://maps.google.com/?q=Macapá,Amapá,Brasil" target="_blank"
                       class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition">
                        <i class="fas fa-external-link-alt"></i> Abrir no Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
