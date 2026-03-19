<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle  = 'Governança · CRCAP';
$activeMenu = 'governanca';

include '../includes/header.php';

// Sidebar de navegação governança (reusável)
$govNav = [
    ['Transparência','fa-shield-alt','#BF8D1A',[
        ['Sobre a Governança','sobre-governanca.php'],
        ['Dados Abertos','dados-abertos.php'],
        ['Prestação de Contas','transparencia.php'],
        ['Auditoria','auditoria.php'],
    ]],
    ['Planejamento','fa-calendar-check','#006633',[
        ['Calendário 2025','calendario.php'],
        ['Relato Integrado','relato-integrado.php'],
        ['Carta de Serviço','carta-servico.php'],
        ['Cadeia de Valor','cadeia-valor.php'],
        ['Plano de Contratação','plano-contratacao.php'],
        ['Plano de Líderes','plano-lideres.php'],
    ]],
    ['Compliance','fa-gavel','#022E6B',[
        ['Ouvidoria','ouvidoria.php'],
        ['Comitê de Integridade','comite-integridade.php'],
        ['Comissão de Conduta','comissao-conduta.php'],
        ['Gestão de Risco','gestao-risco.php'],
        ['LGPD','lgpd.php'],
        ['Segurança da Informação','seguranca-informacao.php'],
        ['Governança Digital','governanca-digital.php'],
        ['Logística Sustentável','logistica-sustentavel.php'],
    ]],
];
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 60% 40%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span>Governança</span>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Sobre a Governança</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-shield-alt"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Governança Corporativa</h1>
                <p class="text-white/70 text-sm max-w-2xl">Conheça o modelo de governança do CRCAP, baseado em transparência, ética, responsabilidade e prestação de contas.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-4 gap-8">

        <!-- Sidebar Governança -->
        <aside class="space-y-4">
            <?php foreach ($govNav as $grupo): ?>
            <div class="bg-white rounded-2xl p-4 border border-[#001644]/3 shadow-sm">
                <h4 class="text-[10px] font-bold uppercase tracking-wider mb-3 flex items-center gap-2" style="color:<?= $grupo[2] ?>">
                    <i class="fas <?= $grupo[1] ?>"></i><?= $grupo[0] ?>
                </h4>
                <nav class="space-y-0.5">
                    <?php foreach ($grupo[3] as $link): ?>
                    <a href="/crcap/pages/<?= $link[1] ?>" class="block px-3 py-2 rounded-lg text-xs text-[#022E6B] hover:text-[#BF8D1A] hover:bg-[#F8FAFC] transition <?= basename($_SERVER['PHP_SELF'])===$link[1]?'bg-[#F8FAFC] text-[#BF8D1A] font-semibold':'' ?>">
                        <?= $link[0] ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>
            <?php endforeach; ?>
        </aside>

        <!-- Conteúdo -->
        <div class="lg:col-span-3 space-y-8">

            <!-- Intro -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-serif text-2xl font-bold text-[#001644] mb-4">O que é Governança Corporativa?</h2>
                <p class="text-sm text-[#022E6B] leading-relaxed mb-4">A governança corporativa no CRCAP é o conjunto de processos, costumes, políticas, leis, regulamentos e instituições que regulam a maneira como o Conselho é administrado e controlado. O objetivo é garantir que as decisões tomadas sejam transparentes, éticas e em benefício da sociedade e dos profissionais registrados.</p>
                <p class="text-sm text-[#022E6B] leading-relaxed">O modelo de governança adotado pelo CRCAP segue as diretrizes do sistema CFA/CRAs e está alinhado com as melhores práticas nacionais de gestão pública, integridade e compliance.</p>
            </div>

            <!-- Pilares -->
            <div>
                <h2 class="font-bold text-[#001644] mb-5 text-lg">Pilares da Governança</h2>
                <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <?php $pilares = [
                        ['fa-eye','Transparência','Divulgação ativa de informações sobre decisões, ações e resultados.','#001644'],
                        ['fa-balance-scale','Equidade','Tratamento justo e igualitário a todos os profissionais e partes interessadas.','#BF8D1A'],
                        ['fa-check-circle','Prestação de Contas','Responsabilização pelos resultados e pela gestão dos recursos públicos.','#006633'],
                        ['fa-shield-alt','Responsabilidade Corporativa','Sustentabilidade e impacto positivo na sociedade e no meio ambiente.','#022E6B'],
                    ]; foreach ($pilares as $p): ?>
                    <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm text-center hover:-translate-y-1 hover:shadow-md transition group">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3 text-white text-2xl group-hover:scale-110 transition" style="background:<?= $p[3] ?>"><i class="fas <?= $p[0] ?>"></i></div>
                        <h3 class="font-bold text-[#001644] text-sm mb-2"><?= $p[1] ?></h3>
                        <p class="text-[10px] text-[#022E6B] leading-relaxed"><?= $p[2] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Estrutura de Governança -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] mb-5 text-lg">Estrutura de Governança</h2>
                <div class="space-y-4">
                    <?php $estrutura = [
                        ['Plenário','fa-university','Órgão deliberativo máximo, composto por todos os conselheiros eleitos. Responsável pelas decisões estratégicas do CRCAP.','#001644'],
                        ['Mesa Diretora','fa-users-cog','Composta pelo Presidente, Vice-Presidente, Secretário Geral e Tesoureiro. Coordena a gestão executiva do Conselho.','#BF8D1A'],
                        ['Câmaras Especializadas','fa-layer-group','Câmaras Administrativa, de Fiscalização, de Registro, de Desenvolvimento Profissional e de Controle Interno.','#006633'],
                        ['Auditoria Interna','fa-search','Órgão de controle interno responsável pelo acompanhamento e avaliação dos processos administrativos.','#022E6B'],
                        ['Comitê de Integridade','fa-hand-paper','Responsável pela implementação e monitoramento do Programa de Integridade do CRCAP.','#001644'],
                    ]; foreach ($estrutura as $e): ?>
                    <div class="flex gap-4 p-4 rounded-xl bg-[#F8FAFC] hover:bg-white hover:shadow-sm hover:border-[#BF8D1A]/20 border border-transparent transition">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white flex-shrink-0" style="background:<?= $e[3] ?>">
                            <i class="fas <?= $e[1] ?> text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-[#001644] text-sm mb-1"><?= $e[0] ?></h3>
                            <p class="text-xs text-[#022E6B] leading-relaxed"><?= $e[2] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Documentos de Governança -->
            <div class="bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-8 text-white">
                <h2 class="font-bold text-lg mb-5 flex items-center gap-2"><i class="fas fa-file-pdf text-[#BF8D1A]"></i>Documentos de Governança</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    <?php $govDocs = [
                        ['Código de Ética do CRCAP','PDF','245KB'],
                        ['Programa de Integridade','PDF','512KB'],
                        ['Política Anti-Corrupção','PDF','310KB'],
                        ['Manual de Governança','PDF','1.2MB'],
                        ['Regimento Interno','PDF','890KB'],
                        ['Estatuto Social','PDF','670KB'],
                    ]; foreach ($govDocs as $d): ?>
                    <a href="#" class="flex items-center gap-3 bg-white/10 hover:bg-white/20 rounded-xl px-4 py-3 text-sm font-medium transition group">
                        <i class="fas fa-file-pdf text-[#BF8D1A] text-xl flex-shrink-0"></i>
                        <div class="min-w-0">
                            <span class="block truncate"><?= $d[0] ?></span>
                            <span class="text-[9px] text-white/60"><?= $d[1] ?> · <?= $d[2] ?></span>
                        </div>
                        <i class="fas fa-download ml-auto text-white/30 group-hover:text-white transition text-xs flex-shrink-0"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
