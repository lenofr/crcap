<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
$pageTitle  = 'Serviços Online · CRCAP';
$activeMenu = '';
include '../includes/header.php';

$grupos = [
    [
        'id'       => 'publico',
        'icon'     => 'fa-globe',
        'title'    => 'Acesso Público',
        'desc'     => 'Disponível para qualquer cidadão, sem necessidade de login.',
        'cor'      => '#001644',
        'badge'    => 'Sem login',
        'badge_bg' => '#006633',
        'items'    => [
            ['fa-search',      'Consulta Cadastral',                   'https://web.crcap.org.br/spw/ConsultaCadastral/TelaConsultaPublicaCompleta.aspx',  'Consulta ao cadastro de profissionais e empresas registradas no Conselho'],
            ['fa-user-plus',   'Solicitação de Registro',              'https://web.crcap.org.br/spw/fichacadastral/crc/',                                 'Solicitação de Registro Profissional'],
            ['fa-scroll',      'Consulta Protocolo',                   'https://web.crcap.org.br/spw/ConsultaCadastral/ConsultarProcessosPublico.aspx',    'Consulta pública de protocolos'],
            ['fa-flag',        'Denúncia',                             'https://web.crcap.org.br/spw/Sfiv2/Denuncia/Denuncia',                             'Faça aqui a sua denúncia'],
            ['fa-shield-alt',  'Confirmação de Veracidade',            'https://web.crcap.org.br/spw/ConsultaCadastral/ConfirmaVeracidadePublica.aspx',    'Confirma Veracidade das Certidões emitidas pelo CRCAP'],
            ['fa-certificate', 'Certidão de Habilitação Profissional', 'https://web.crcap.org.br/spw/ConsultaCadastral/Externa.aspx',                     'Acesso Público para emitir certidão de habilitação do profissional'],
            ['fa-building',    'Certidão de Habilitação Pessoa Jurídica','https://web.crcap.org.br/spw/ConsultaCadastral/Externa.aspx',                   'Acesso Público para emitir certidão de habilitação de Pessoa Jurídica'],
        ],
    ],
    [
        'id'       => 'profissional',
        'icon'     => 'fa-user-tie',
        'title'    => 'Profissional',
        'desc'     => 'Exclusivo para profissionais registrados no CRCAP.',
        'cor'      => '#BF8D1A',
        'badge'    => 'Login necessário',
        'badge_bg' => '#BF8D1A',
        'items'    => [
            ['fa-address-card',        'Consulta Cadastral',            'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLogin.aspx', 'Consulta ao cadastro completo do Profissional'],
            ['fa-pen',                 'Atualização Cadastral',         'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLogin.aspx', 'Consulta Cadastral Profissional - Dados Pessoais'],
            ['fa-file-invoice-dollar', 'Emissão de Guia',               'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLogin.aspx', 'Emissão de boletos de anuidades em aberto'],
            ['fa-award',               'Emitir Certidão',               'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLogin.aspx', 'Emitir Certidão Negativa de Débitos'],
            ['fa-file-invoice',        'Decore Eletrônica',             'https://sistemas.cfc.org.br/login',                            'Decore Eletrônica'],
            ['fa-check-circle',        'Decore / Confirmar Veracidade', 'https://sistemas.cfc.org.br/decore/consultaexterna',            'DECORE / Confirmar veracidade da certidão'],
            ['fa-id-card',             'Solicitação de Carteira',       'https://web.crcap.org.br/spw/rec/crc/pedidocarteira/',          'Solicitação de Carteira Profissional e Acompanhamento da Solicitação'],
            ['fa-gavel',               'Fiscalização / Defesa Denúncia','https://web.crcap.org.br/spw/ConsultaCadastral/TelaLogin.aspx', 'Área para defesa da Fiscalização'],
            ['fa-exchange-alt',        'Termo de Transferência Online', 'https://web.crcap.org.br/spw/strt/login.aspx',                 'Termo de Transferência Online'],
        ],
    ],
    [
        'id'       => 'pj',
        'icon'     => 'fa-building',
        'title'    => 'Pessoa Jurídica',
        'desc'     => 'Serviços para empresas e organizações registradas no CRCAP.',
        'cor'      => '#022E6B',
        'badge'    => 'Empresa',
        'badge_bg' => '#022E6B',
        'items'    => [
            ['fa-search',              'Consulta Cadastral', 'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLoginEmpresa.aspx', 'Consulta Cadastro completo da Pessoa Jurídica'],
            ['fa-file-invoice-dollar', 'Emissão de Guia',   'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLoginEmpresa.aspx', 'Emissão de boletos de anuidades em aberto'],
            ['fa-stamp',               'Emitir Alvarás',     'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLoginEmpresa.aspx', 'Alvará Sociedade / Empresário'],
            ['fa-certificate',         'Emitir Certidão',    'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLoginEmpresa.aspx', 'Emitir Certidão de Sociedade / Empresário'],
        ],
    ],
    [
        'id'       => 'usuario',
        'icon'     => 'fa-shield-alt',
        'title'    => 'Usuário CRCAP',
        'desc'     => 'Acesso restrito para usuários internos, conselheiros e diretoria.',
        'cor'      => '#006633',
        'badge'    => 'Interno',
        'badge_bg' => '#006633',
        'items'    => [
            ['fa-database',    'Sistema Cadastral',      'https://web.crcap.org.br/spw/scc/login.aspx',                    'Acesso completo ao sistema pelo usuário de Sistema do CRCAP'],
            ['fa-user-shield', 'Conselheiro',            'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLogin.aspx',  'Acesso restrito a conselheiros'],
            ['fa-star',        'Vice Presidente',        'https://web.crcap.org.br/spw/ConsultaCadastral/TelaLogin.aspx',  'Acesso restrito ao Vice Presidente'],
            ['fa-lock',        'Acesso Usuário Interno', 'https://web.crcap.org.br/spw/scc/login.aspx',                    'Acesso ao sistema para usuários internos do CRCAP'],
        ],
    ],
    [
        'id'       => 'comunicacao',
        'icon'     => 'fa-paper-plane',
        'title'    => 'Comunicação do Exercício em Outra Jurisdição',
        'desc'     => 'Para profissionais que exercem atividades fora da jurisdição de registro.',
        'cor'      => '#BF8D1A',
        'badge'    => 'CFC',
        'badge_bg' => '#BF8D1A',
        'items'    => [
            ['fa-paper-plane',  'Comunicação do Exercício em Outra Jurisdição', 'https://www3.cfc.org.br/spw/secundario/inicio.aspx?codigo=1&url=web.crcap.org.br/spw/ConsultaCadastral/principal.aspx', 'Fazer a comunicação do exercício profissional em outra jurisdição'],
            ['fa-clock',        'Andamento da Comunicação',                     'https://www3.cfc.org.br/spw/secundario/inicio.aspx?codigo=1&url=web.crcap.org.br/spw/ConsultaCadastral/principal.aspx', 'Andamento da Comunicação'],
            ['fa-print',        'Emitir Comprovante da Comunicação',            'https://www3.cfc.org.br/spw/secundario/inicio.aspx?codigo=1&url=web.crcap.org.br/spw/ConsultaCadastral/principal.aspx', 'Emitir Comprovante da Comunicação'],
            ['fa-circle-check', 'Confirmar Veracidade do Comprovante',          'https://www3.cfc.org.br/spw/secundario/inicio.aspx?codigo=2&url=web.crcap.org.br/spw/ConsultaCadastral/principal.aspx', 'Confirmar Veracidade do Comprovante'],
        ],
    ],
];
?>

<!-- Hero -->
<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 70% 50%, #BF8D1A 0%,transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Serviços Online</span>
        </div>
        <div class="flex items-start gap-5 mb-8">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0">
                <i class="fas fa-globe"></i>
            </div>
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Serviços Online</h1>
                <p class="text-white/70 text-sm max-w-2xl">Acesse os serviços digitais do CRCAP. Navegue pela categoria correspondente ao seu perfil.</p>
            </div>
        </div>
        <!-- Navegação rápida -->
        <div class="flex flex-wrap gap-2">
            <?php foreach ($grupos as $g): ?>
            <a href="#<?= $g['id'] ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 bg-white/10 hover:bg-white/20 border border-white/10 hover:border-white/30 rounded-lg text-xs font-medium transition">
                <i class="fas <?= $g['icon'] ?> text-[10px]" style="color:#BF8D1A"></i>
                <?= $g['title'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12 space-y-10">

    <?php foreach ($grupos as $g): ?>
    <div id="<?= $g['id'] ?>" class="scroll-mt-28 bg-white rounded-2xl border border-[#001644]/5 shadow-sm overflow-hidden">

        <!-- Cabeçalho do grupo -->
        <div class="flex items-center gap-4 px-6 py-5 border-b border-[#001644]/5"
             style="background:linear-gradient(135deg,<?= $g['cor'] ?>10,<?= $g['cor'] ?>03)">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white text-lg flex-shrink-0 shadow-md"
                 style="background:<?= $g['cor'] ?>">
                <i class="fas <?= $g['icon'] ?>"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="font-bold text-[#001644] text-base"><?= $g['title'] ?></h2>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold text-white"
                          style="background:<?= $g['badge_bg'] ?>"><?= $g['badge'] ?></span>
                </div>
                <p class="text-[11px] text-[#022E6B]/60 mt-0.5"><?= $g['desc'] ?></p>
            </div>
            <span class="text-xs text-[#001644]/30 font-semibold hidden sm:block">
                <?= count($g['items']) ?> serviço<?= count($g['items']) > 1 ? 's' : '' ?>
            </span>
        </div>

        <!-- Grid de serviços -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-px bg-[#001644]/5">
            <?php foreach ($g['items'] as [$icon, $title, $url, $desc]): ?>
            <a href="<?= htmlspecialchars($url) ?>"
               target="_blank" rel="noopener noreferrer"
               class="group flex items-start gap-3 p-4 bg-white hover:bg-[#F8FAFC] transition-all duration-150">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm flex-shrink-0 mt-0.5 transition-transform group-hover:scale-110 shadow-sm"
                     style="background:<?= $g['cor'] ?>">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-[#001644] leading-tight group-hover:text-[#BF8D1A] transition mb-0.5">
                        <?= htmlspecialchars($title) ?>
                    </p>
                    <p class="text-[10px] text-[#022E6B]/55 leading-snug line-clamp-2">
                        <?= htmlspecialchars($desc) ?>
                    </p>
                </div>
                <i class="fas fa-external-link-alt text-[9px] text-[#001644]/20 group-hover:text-[#BF8D1A] transition flex-shrink-0 mt-1"></i>
            </a>
            <?php endforeach; ?>
        </div>

    </div>
    <?php endforeach; ?>

    <!-- Aviso -->
    <div class="flex items-start gap-4 bg-[#001644]/3 border border-[#001644]/8 rounded-2xl p-5">
        <div class="w-9 h-9 rounded-xl bg-[#BF8D1A]/10 flex items-center justify-center text-[#BF8D1A] flex-shrink-0">
            <i class="fas fa-info-circle"></i>
        </div>
        <div>
            <p class="text-sm font-semibold text-[#001644] mb-1">Precisa de ajuda?</p>
            <p class="text-xs text-[#022E6B]/70 leading-relaxed">
                Os serviços são hospedados no portal do CFC ou no sistema SPW do CRCAP.
                Certifique-se de que seu cadastro está ativo e, em caso de dificuldades, entre em contato com nossa equipe.
            </p>
            <a href="/crcap/pages/contato.php"
               class="inline-flex items-center gap-1.5 mt-3 text-xs font-semibold text-[#BF8D1A] hover:underline">
                <i class="fas fa-headset text-[10px]"></i> Falar com o suporte
            </a>
        </div>
    </div>

</main>

<?php include '../includes/footer.php'; ?>