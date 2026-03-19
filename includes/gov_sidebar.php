<?php
// includes/gov_sidebar.php
// Variável $activeGov = slug atual
$activeGov = $activeGov ?? '';
$govMenu = [
    'Transparência' => [
        ['sobre-governanca',    'fa-shield-alt',   'Sobre a Governança'],
        ['dados-abertos',       'fa-database',     'Dados Abertos'],
        ['transparencia-contas','fa-chart-bar',    'Transparência e Prestação de Contas'],
        ['auditoria',           'fa-search-dollar','Auditoria'],
    ],
    'Planejamento' => [
        ['calendario',         'fa-calendar-alt', 'Calendário CRCAP'],
        ['relato-integrado',   'fa-book',         'Relato Integrado CRCAP'],
        ['carta-servico',      'fa-scroll',       'Carta de Serviço'],
        ['cadeia-valor',       'fa-project-diagram','Cadeia de Valor'],
        ['plano-contratacao',  'fa-shopping-cart','Plano Anual de Contratação'],
        ['plano-lideranca',    'fa-users-cog',    'Plano Desenv. de Líderes'],
    ],
    'Compliance' => [
        ['ouvidoria',           'fa-microphone-alt','Ouvidoria'],
        ['comite-integridade',  'fa-shield-check',  'Comitê de Integridade'],
        ['comissao-conduta',    'fa-gavel',          'Comissão de Conduta'],
        ['gestao-risco',        'fa-exclamation-triangle','Gestão de Risco'],
        ['lgpd',                'fa-lock',           'LGPD'],
        ['seguranca-informacao','fa-user-shield',    'Segurança da Informação'],
        ['governanca-digital',  'fa-laptop-code',    'Governança Digital'],
        ['logistica-sustentavel','fa-leaf',          'Logística Sustentável'],
    ],
];
?>
<div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden">
    <div class="bg-[#001644] px-5 py-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2"><i class="fas fa-shield-alt"></i> Governança</h3>
    </div>
    <nav class="p-2">
        <?php foreach ($govMenu as $section => $links): ?>
        <div class="mb-2">
            <p class="px-3 py-1 text-[9px] font-bold text-[#001644]/40 uppercase tracking-widest"><?= $section ?></p>
            <?php foreach ($links as $l): ?>
            <a href="/crcap/pages/governanca/<?= $l[0] ?>.php"
               class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-[11px] font-semibold transition mb-0.5
                      <?= $activeGov === $l[0] ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                <i class="fas <?= $l[1] ?> w-4 text-center text-[#BF8D1A] text-[9px]"></i>
                <span><?= $l[2] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>
</div>
