<?php
// =====================================================
// CRCAP - Gerador de páginas de Governança
// Uso: Este arquivo não é chamado diretamente.
// Cada página de governança inclui este arquivo após definir $govPageSlug
// =====================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$page = dbQueryOne("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$govPageSlug]);

include __DIR__ . '/header.php';
$bannerTitle     = $govPageTitle;
$bannerSubtitle  = $govPageSubtitle ?? '';
$bannerIcon      = $govPageIcon ?? 'fa-shield-alt';
$bannerBreadcrumb = [['Home','/crcap/index.php'],['Governança',null],[$govPageTitle,null]];
include __DIR__ . '/page_banner.php';

$activeGov = $govPageSlug;
?>
<main class="container mx-auto px-4 py-12">
<div class="grid lg:grid-cols-4 gap-8">
    <aside class="lg:col-span-1">
        <?php include __DIR__ . '/gov_sidebar.php'; ?>
    </aside>

    <div class="lg:col-span-3">
        <?php if ($page): ?>
        <div class="bg-white rounded-2xl border border-[#001644]/5 overflow-hidden">
            <?php if ($page['featured_image']): ?>
            <div class="h-60 overflow-hidden"><img src="<?= h($page['featured_image']) ?>" alt="<?= h($page['title']) ?>" class="w-full h-full object-cover"></div>
            <?php endif; ?>
            <div class="p-8">
                <h2 class="text-2xl font-bold text-[#001644] mb-4"><?= h($page['title']) ?></h2>
                <div class="prose max-w-none text-sm text-[#001644] leading-relaxed"><?= $page['content'] ?></div>
            </div>
        </div>
        <?php else: ?>
        <!-- Conteúdo padrão quando não há registro no BD -->
        <div class="space-y-5">
            <div class="bg-white rounded-2xl border border-[#001644]/5 p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl flex items-center justify-center text-white text-2xl">
                        <i class="fas <?= $govPageIcon ?? 'fa-shield-alt' ?>"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-[#001644]"><?= h($govPageTitle) ?></h2>
                        <?php if (!empty($govPageSubtitle)): ?>
                        <p class="text-xs text-[#022E6B]/70"><?= h($govPageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($govPageContent)): ?>
                <div class="text-sm text-[#001644]/80 leading-relaxed space-y-4">
                    <?= $govPageContent ?>
                </div>
                <?php else: ?>
                <?php
                // Default content per slug when DB has no record
                $defaultContents = [
                    'sobre-governanca' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O CRCAP adota um modelo de governança orientado à transparência, integridade e eficiência na gestão pública. Nossa estrutura de governança garante que todas as decisões sejam tomadas com base em critérios técnicos, éticos e alinhados ao interesse público.</p><p class="text-sm text-[#001644]/80 leading-relaxed mt-4">O modelo de governança corporativa do CRCAP segue as melhores práticas nacionais e internacionais, integrando os princípios de <strong>transparência</strong>, <strong>equidade</strong>, <strong>prestação de contas</strong> e <strong>responsabilidade corporativa</strong>.</p><ul class="mt-4 space-y-2 text-sm text-[#001644]/80"><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5 flex-shrink-0"></i>Gestão orientada a resultados e ao cidadão</li><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5 flex-shrink-0"></i>Transparência ativa na divulgação de informações</li><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5 flex-shrink-0"></i>Controle interno eficaz e auditorias periódicas</li><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5 flex-shrink-0"></i>Participação democrática e acesso à informação</li></ul>',
                    'dados-abertos' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O CRCAP disponibiliza dados públicos em formato aberto e acessível, em conformidade com a <strong>Lei de Acesso à Informação (LAI – Lei nº 12.527/2011)</strong> e os princípios de governo aberto.</p><div class="mt-6 grid sm:grid-cols-2 gap-4"><div class="bg-[#F8FAFC] rounded-xl p-4 border border-[#001644]/5"><i class="fas fa-database text-[#BF8D1A] text-2xl mb-2 block"></i><h4 class="font-bold text-[#001644] text-sm mb-1">Dados Financeiros</h4><p class="text-xs text-[#022E6B]">Orçamentos, balanços e demonstrativos financeiros anuais.</p></div><div class="bg-[#F8FAFC] rounded-xl p-4 border border-[#001644]/5"><i class="fas fa-users text-[#BF8D1A] text-2xl mb-2 block"></i><h4 class="font-bold text-[#001644] text-sm mb-1">Dados de Pessoas</h4><p class="text-xs text-[#022E6B]">Quadro de pessoal, remunerações e cargos públicos.</p></div><div class="bg-[#F8FAFC] rounded-xl p-4 border border-[#001644]/5"><i class="fas fa-file-contract text-[#BF8D1A] text-2xl mb-2 block"></i><h4 class="font-bold text-[#001644] text-sm mb-1">Contratos e Licitações</h4><p class="text-xs text-[#022E6B]">Contratos firmados, licitações e editais publicados.</p></div><div class="bg-[#F8FAFC] rounded-xl p-4 border border-[#001644]/5"><i class="fas fa-chart-line text-[#BF8D1A] text-2xl mb-2 block"></i><h4 class="font-bold text-[#001644] text-sm mb-1">Indicadores</h4><p class="text-xs text-[#022E6B]">Indicadores de desempenho e metas institucionais.</p></div></div>',
                    'transparencia-contas' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O CRCAP publica anualmente seu Relatório de Gestão, balanços financeiros e demonstrações contábeis, em conformidade com as normas do Tribunal de Contas e do Conselho Federal de Administração – CFA.</p><div class="mt-6 space-y-3"><div class="flex items-center gap-4 p-4 bg-[#F8FAFC] rounded-xl border border-[#001644]/5 hover:border-[#BF8D1A]/30 transition"><div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0"><i class="fas fa-file-pdf text-red-500"></i></div><div class="flex-1"><p class="font-semibold text-[#001644] text-xs">Relatório de Gestão 2025</p><p class="text-[10px] text-[#022E6B]">Publicado em Jan/2026 · PDF · 2,4 MB</p></div><span class="px-3 py-1 bg-[#001644] text-white text-[10px] rounded-lg font-semibold">Download</span></div><div class="flex items-center gap-4 p-4 bg-[#F8FAFC] rounded-xl border border-[#001644]/5 hover:border-[#BF8D1A]/30 transition"><div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0"><i class="fas fa-file-pdf text-red-500"></i></div><div class="flex-1"><p class="font-semibold text-[#001644] text-xs">Balanço Patrimonial 2025</p><p class="text-[10px] text-[#022E6B]">Exercício 2025 · PDF · 1,1 MB</p></div><span class="px-3 py-1 bg-[#001644] text-white text-[10px] rounded-lg font-semibold">Download</span></div></div>',
                    'auditoria' => '<p class="text-sm text-[#001644]/80 leading-relaxed">A Auditoria Interna do CRCAP atua como órgão de controle, avaliando a conformidade das ações institucionais com a legislação vigente, normas internas e princípios da boa governança pública.</p><h3 class="font-bold text-[#001644] mt-6 mb-3">Competências</h3><ul class="space-y-2 text-sm text-[#001644]/80"><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5"></i>Avaliar a adequação dos controles internos</li><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5"></i>Verificar conformidade com leis e regulamentos</li><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5"></i>Assessorar a Administração na prevenção de irregularidades</li><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5"></i>Emitir pareceres e relatórios periódicos de auditoria</li></ul>',
                    'lgpd' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O CRCAP está comprometido com a proteção dos dados pessoais de seus profissionais, colaboradores e usuários, em conformidade com a <strong>Lei Geral de Proteção de Dados (Lei nº 13.709/2018 – LGPD)</strong>.</p><h3 class="font-bold text-[#001644] mt-6 mb-3">Seus Direitos como Titular</h3><div class="grid sm:grid-cols-2 gap-3 text-xs"><div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#001644]/5"><strong class="text-[#001644]">Acesso:</strong><p class="text-[#022E6B] mt-0.5">Solicitar acesso aos seus dados pessoais tratados pelo CRCAP.</p></div><div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#001644]/5"><strong class="text-[#001644]">Correção:</strong><p class="text-[#022E6B] mt-0.5">Corrigir dados incompletos, inexatos ou desatualizados.</p></div><div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#001644]/5"><strong class="text-[#001644]">Exclusão:</strong><p class="text-[#022E6B] mt-0.5">Solicitar a eliminação de dados tratados com seu consentimento.</p></div><div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#001644]/5"><strong class="text-[#001644]">Portabilidade:</strong><p class="text-[#022E6B] mt-0.5">Solicitar a portabilidade dos dados para outro fornecedor.</p></div></div><p class="text-xs text-[#022E6B] mt-4">Para exercer seus direitos, entre em contato com nosso Encarregado de Dados (DPO): <strong>lgpd@crcap.org.br</strong></p>',
                    'comite-integridade' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O Comitê de Integridade do CRCAP é responsável por promover a cultura de integridade, ética e compliance em toda a organização, em conformidade com o Decreto Federal nº 9.203/2017.</p><h3 class="font-bold text-[#001644] mt-6 mb-3">Atribuições</h3><ul class="space-y-2 text-sm text-[#001644]/80"><li class="flex items-start gap-2"><i class="fas fa-shield-alt text-[#BF8D1A] mt-0.5"></i>Supervisionar a implementação do Programa de Integridade</li><li class="flex items-start gap-2"><i class="fas fa-shield-alt text-[#BF8D1A] mt-0.5"></i>Analisar e propor melhorias nos mecanismos de prevenção à corrupção</li><li class="flex items-start gap-2"><i class="fas fa-shield-alt text-[#BF8D1A] mt-0.5"></i>Receber e analisar denúncias de irregularidades</li><li class="flex items-start gap-2"><i class="fas fa-shield-alt text-[#BF8D1A] mt-0.5"></i>Promover treinamentos sobre ética e conduta</li></ul>',
                    'comissao-conduta' => '<p class="text-sm text-[#001644]/80 leading-relaxed">A Comissão de Ética e Conduta do CRCAP atua na promoção dos valores institucionais e na orientação sobre condutas adequadas dos servidores, conselheiros e colaboradores.</p><h3 class="font-bold text-[#001644] mt-6 mb-3">Código de Conduta</h3><p class="text-sm text-[#001644]/80">O Código de Conduta do CRCAP estabelece os padrões de comportamento esperados de todos que atuam em nome da instituição, incluindo princípios de imparcialidade, transparência, respeito e comprometimento com o interesse público.</p>',
                    'gestao-risco' => '<p class="text-sm text-[#001644]/80 leading-relaxed">A Gestão de Riscos do CRCAP é um processo estruturado para identificar, avaliar e tratar riscos que possam comprometer o alcance dos objetivos institucionais, em conformidade com a norma <strong>ABNT NBR ISO 31000:2018</strong>.</p><div class="mt-6 grid sm:grid-cols-3 gap-4"><div class="text-center p-4 bg-[#F8FAFC] rounded-xl"><div class="w-10 h-10 mx-auto mb-2 bg-[#001644] rounded-xl flex items-center justify-center"><i class="fas fa-search text-white text-sm"></i></div><h4 class="font-bold text-[#001644] text-xs">Identificação</h4><p class="text-[10px] text-[#022E6B] mt-1">Mapeamento dos riscos estratégicos e operacionais</p></div><div class="text-center p-4 bg-[#F8FAFC] rounded-xl"><div class="w-10 h-10 mx-auto mb-2 bg-[#BF8D1A] rounded-xl flex items-center justify-center"><i class="fas fa-balance-scale text-white text-sm"></i></div><h4 class="font-bold text-[#001644] text-xs">Avaliação</h4><p class="text-[10px] text-[#022E6B] mt-1">Análise de impacto e probabilidade de cada risco</p></div><div class="text-center p-4 bg-[#F8FAFC] rounded-xl"><div class="w-10 h-10 mx-auto mb-2 bg-[#006633] rounded-xl flex items-center justify-center"><i class="fas fa-shield-alt text-white text-sm"></i></div><h4 class="font-bold text-[#001644] text-xs">Tratamento</h4><p class="text-[10px] text-[#022E6B] mt-1">Medidas de controle e planos de contingência</p></div></div>',
                    'governanca-digital' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O CRCAP está em processo contínuo de transformação digital, adotando tecnologias para modernizar seus serviços e garantir acessibilidade, eficiência e segurança no atendimento aos profissionais de Administração do Amapá.</p><h3 class="font-bold text-[#001644] mt-6 mb-3">Iniciativas Digitais</h3><ul class="space-y-2 text-sm text-[#001644]/80"><li class="flex items-start gap-2"><i class="fas fa-laptop text-[#BF8D1A] mt-0.5"></i>Portal do Profissional online 24h</li><li class="flex items-start gap-2"><i class="fas fa-file-signature text-[#BF8D1A] mt-0.5"></i>Emissão de certidões e documentos digitais</li><li class="flex items-start gap-2"><i class="fas fa-video text-[#BF8D1A] mt-0.5"></i>Cursos e eventos online (EAD)</li><li class="flex items-start gap-2"><i class="fas fa-mobile-alt text-[#BF8D1A] mt-0.5"></i>Sistema de notificações e comunicados digitais</li></ul>',
                    'logistica-sustentavel' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O Plano de Logística Sustentável (PLS) do CRCAP estabelece práticas e metas para reduzir o impacto ambiental das atividades institucionais, promovendo consumo responsável e sustentabilidade.</p><div class="mt-6 grid sm:grid-cols-2 gap-4"><div class="bg-[#006633]/5 border border-[#006633]/20 rounded-xl p-4"><i class="fas fa-leaf text-[#006633] text-2xl mb-2 block"></i><h4 class="font-bold text-[#001644] text-sm mb-1">Uso de Papel</h4><p class="text-xs text-[#022E6B]">Redução de 30% no consumo de papel com digitalização de processos.</p></div><div class="bg-[#006633]/5 border border-[#006633]/20 rounded-xl p-4"><i class="fas fa-bolt text-[#006633] text-2xl mb-2 block"></i><h4 class="font-bold text-[#001644] text-sm mb-1">Energia</h4><p class="text-xs text-[#022E6B]">Uso de equipamentos eficientes e desligamento programado.</p></div><div class="bg-[#006633]/5 border border-[#006633]/20 rounded-xl p-4"><i class="fas fa-recycle text-[#006633] text-2xl mb-2 block"></i><h4 class="font-bold text-[#001644] text-sm mb-1">Reciclagem</h4><p class="text-xs text-[#022E6B]">Coleta seletiva e descarte correto de materiais.</p></div><div class="bg-[#006633]/5 border border-[#006633]/20 rounded-xl p-4"><i class="fas fa-car text-[#006633] text-2xl mb-2 block"></i><h4 class="font-bold text-[#001644] text-sm mb-1">Mobilidade</h4><p class="text-xs text-[#022E6B]">Incentivo ao uso de transporte público e reuniões por videoconferência.</p></div></div>',
                    'seguranca-informacao' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O CRCAP implementa políticas e controles de segurança da informação para proteger os dados institucionais e pessoais, em conformidade com as melhores práticas da norma <strong>ABNT NBR ISO/IEC 27001</strong>.</p><h3 class="font-bold text-[#001644] mt-6 mb-3">Pilares da Segurança</h3><ul class="space-y-2 text-sm text-[#001644]/80"><li class="flex items-start gap-2"><i class="fas fa-lock text-[#BF8D1A] mt-0.5"></i><div><strong>Confidencialidade:</strong> acesso restrito a informações sensíveis</div></li><li class="flex items-start gap-2"><i class="fas fa-check-circle text-[#BF8D1A] mt-0.5"></i><div><strong>Integridade:</strong> proteção contra alterações não autorizadas</div></li><li class="flex items-start gap-2"><i class="fas fa-wifi text-[#BF8D1A] mt-0.5"></i><div><strong>Disponibilidade:</strong> sistemas acessíveis quando necessário</div></li></ul>',
                    'plano-contratacao' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O Plano Anual de Contratações (PAC) do CRCAP consolida todas as contratações previstas para o exercício, em conformidade com o art. 12 da Lei nº 14.133/2021 (Nova Lei de Licitações).</p><p class="text-sm text-[#001644]/80 mt-4 leading-relaxed">O PAC é elaborado com base nas necessidades identificadas pelas unidades organizacionais e submetido à aprovação da Presidência, sendo publicado até o dia 31 de março de cada ano.</p>',
                    'plano-lideranca' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O Plano de Desenvolvimento de Líderes do CRCAP busca identificar, desenvolver e reter talentos com potencial de liderança, alinhando o crescimento individual às estratégias institucionais.</p><h3 class="font-bold text-[#001644] mt-6 mb-3">Eixos do Programa</h3><ul class="space-y-2 text-sm text-[#001644]/80"><li class="flex items-start gap-2"><i class="fas fa-graduation-cap text-[#BF8D1A] mt-0.5"></i>Capacitação técnica e comportamental</li><li class="flex items-start gap-2"><i class="fas fa-users text-[#BF8D1A] mt-0.5"></i>Mentoria com líderes seniores</li><li class="flex items-start gap-2"><i class="fas fa-chart-line text-[#BF8D1A] mt-0.5"></i>Avaliação de desempenho e feedback estruturado</li><li class="flex items-start gap-2"><i class="fas fa-star text-[#BF8D1A] mt-0.5"></i>Plano de sucessão e carreira</li></ul>',
                    'relato-integrado' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O Relato Integrado do CRCAP apresenta de forma transparente e abrangente a criação de valor da organização ao longo do tempo, integrando informações financeiras e não financeiras, conforme o framework do <strong>IIRC (International Integrated Reporting Council)</strong>.</p><p class="text-sm text-[#001644]/80 mt-4">O documento abrange os seis capitais: financeiro, manufaturado, intelectual, humano, social e natural, evidenciando como o CRCAP gera valor para os profissionais de Administração e para a sociedade amapaense.</p>',
                    'carta-servico' => '<p class="text-sm text-[#001644]/80 leading-relaxed">A Carta de Serviços ao Usuário do CRCAP, elaborada em conformidade com o Decreto nº 9.094/2017, informa aos usuários sobre os serviços prestados, os canais de atendimento, os padrões de qualidade e os compromissos do Conselho.</p><h3 class="font-bold text-[#001644] mt-6 mb-3">Nossos Serviços</h3><div class="grid sm:grid-cols-2 gap-3 text-xs"><div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#001644]/5"><i class="fas fa-id-card text-[#BF8D1A] mb-1 block text-sm"></i><strong class="text-[#001644]">Inscrição Profissional</strong><p class="text-[#022E6B] mt-0.5">Prazo: até 30 dias úteis</p></div><div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#001644]/5"><i class="fas fa-file-certificate text-[#BF8D1A] mb-1 block text-sm"></i><strong class="text-[#001644]">Certidão Negativa</strong><p class="text-[#022E6B] mt-0.5">Prazo: imediato (online)</p></div><div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#001644]/5"><i class="fas fa-building text-[#BF8D1A] mb-1 block text-sm"></i><strong class="text-[#001644]">Registro de Empresa</strong><p class="text-[#022E6B] mt-0.5">Prazo: até 15 dias úteis</p></div><div class="bg-[#F8FAFC] p-3 rounded-xl border border-[#001644]/5"><i class="fas fa-graduation-cap text-[#BF8D1A] mb-1 block text-sm"></i><strong class="text-[#001644]">Educação Continuada</strong><p class="text-[#022E6B] mt-0.5">Cursos e certificações online</p></div></div>',
                    'cadeia-valor' => '<p class="text-sm text-[#001644]/80 leading-relaxed">A Cadeia de Valor do CRCAP representa o conjunto de processos e atividades que geram valor para os profissionais de Administração, organizações e para a sociedade amapaense.</p><div class="mt-6 space-y-4"><div class="bg-[#001644] text-white rounded-xl p-4"><p class="text-xs font-bold uppercase tracking-wider mb-2 text-[#BF8D1A]">Processos Finalísticos</p><div class="grid sm:grid-cols-3 gap-2 text-xs"><span class="bg-white/10 px-3 py-2 rounded-lg">Registro Profissional</span><span class="bg-white/10 px-3 py-2 rounded-lg">Fiscalização</span><span class="bg-white/10 px-3 py-2 rounded-lg">Desenvolvimento Profissional</span></div></div><div class="bg-[#022E6B] text-white rounded-xl p-4"><p class="text-xs font-bold uppercase tracking-wider mb-2 text-[#BF8D1A]">Processos de Suporte</p><div class="grid sm:grid-cols-3 gap-2 text-xs"><span class="bg-white/10 px-3 py-2 rounded-lg">Gestão de Pessoas</span><span class="bg-white/10 px-3 py-2 rounded-lg">TI e Inovação</span><span class="bg-white/10 px-3 py-2 rounded-lg">Financeiro</span></div></div></div>',
                    'ouvidoria' => '<p class="text-sm text-[#001644]/80 leading-relaxed">A Ouvidoria do CRCAP é o canal oficial para receber manifestações de elogios, sugestões, solicitações, reclamações e denúncias relacionadas às atividades do Conselho.</p><div class="mt-6 grid sm:grid-cols-2 gap-4"><a href="/crcap/pages/ouvidoria.php" class="block p-5 bg-[#001644] text-white rounded-xl hover:bg-[#022E6B] transition text-center"><i class="fas fa-bullhorn text-2xl mb-2 block text-[#BF8D1A]"></i><p class="font-bold text-sm">Registrar Manifestação</p><p class="text-white/60 text-xs mt-1">Clique para acessar o formulário</p></a><div class="p-5 bg-[#F8FAFC] rounded-xl border border-[#001644]/5"><i class="fas fa-clock text-[#BF8D1A] text-2xl mb-2 block"></i><p class="font-bold text-[#001644] text-sm">Prazo de Resposta</p><p class="text-[#022E6B] text-xs mt-1">Até <strong>30 dias úteis</strong> para manifestações gerais. Denúncias tratadas com prioridade e sigilo.</p></div></div>',
                    'calendario' => '<p class="text-sm text-[#001644]/80 leading-relaxed">O Calendário Institucional do CRCAP reúne os principais eventos, reuniões e atividades planejadas para o exercício vigente. Consulte a agenda de eventos públicos abaixo ou acesse a <a href="/crcap/pages/agenda.php" class="text-[#BF8D1A] font-semibold hover:underline">Agenda do Presidente</a> para compromissos da presidência.</p><div class="mt-6 text-center"><a href="/crcap/pages/eventos.php" class="inline-flex items-center gap-2 px-6 py-3 bg-[#001644] text-white rounded-xl font-semibold hover:bg-[#BF8D1A] transition text-sm"><i class="fas fa-calendar-alt"></i>Ver Eventos e Cursos</a></div>',
                ];
                $defaultContent = $defaultContents[$govPageSlug] ?? null;
                ?>
                <?php if ($defaultContent): ?>
                <div class="text-sm text-[#001644]/80 leading-relaxed">
                    <?= $defaultContent ?>
                </div>
                <?php else: ?>
                <div class="text-center py-10 text-[#001644]/40">
                    <i class="fas fa-file-alt text-4xl mb-3 block"></i>
                    <p class="text-sm font-semibold text-[#001644]/50">Conteúdo sendo preparado</p>
                    <p class="text-xs mt-1">Esta seção será atualizada em breve. Para mais informações, <a href="/crcap/pages/contato.php" class="text-[#BF8D1A] hover:underline">entre em contato</a>.</p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($govPageDocs)): ?>
            <div>
                <h3 class="text-lg font-bold text-[#001644] mb-4 flex items-center gap-2">
                    <i class="fas fa-file-download text-[#BF8D1A]"></i> Documentos Relacionados
                </h3>
                <div class="space-y-3">
                    <?php foreach ($govPageDocs as $doc): ?>
                    <div class="bg-white border border-[#001644]/5 rounded-2xl p-4 hover:shadow-md hover:border-[#BF8D1A]/20 transition group flex items-center gap-4">
                        <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-file-pdf text-red-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-[#001644] text-xs group-hover:text-[#BF8D1A] transition"><?= h($doc['title']) ?></p>
                            <?php if ($doc['publication_date']): ?>
                            <p class="text-[9px] text-[#001644]/50"><?= date('d/m/Y', strtotime($doc['publication_date'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="/crcap/pages/download.php?id=<?= $doc['id'] ?>"
                           class="flex-shrink-0 px-3 py-1.5 bg-[#001644] text-white text-[10px] font-semibold rounded-lg hover:bg-[#BF8D1A] transition">
                            <i class="fas fa-download mr-1"></i>Baixar
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Contato institucional -->
        <div class="mt-6 bg-[#F8FAFC] border border-[#001644]/5 rounded-2xl p-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#001644]/8 rounded-xl flex items-center justify-center">
                    <i class="fas fa-headset text-[#001644] text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-bold text-[#001644]">Precisa de mais informações?</p>
                    <p class="text-[10px] text-[#001644]/60">Entre em contato com nossa equipe.</p>
                </div>
                <a href="/crcap/pages/contato.php" class="px-4 py-2 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition">
                    Fale Conosco
                </a>
            </div>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
