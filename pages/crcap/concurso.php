<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle = 'Concurso';
$activePage = 'crcap-concurso';

$concursos = dbQuery("
    SELECT * FROM documents
    WHERE (category = 'editais' AND document_type LIKE '%concurso%')
       OR (category = 'concurso')
    AND status = 'active' AND is_public = 1
    ORDER BY publication_date DESC
    LIMIT 20
");

$eventsConcurso = dbQuery("
    SELECT * FROM events
    WHERE (title LIKE '%concurso%' OR description LIKE '%concurso%' OR event_type = 'other')
    AND status = 'published' AND event_date >= CURDATE()
    ORDER BY event_date ASC LIMIT 5
");

include __DIR__ . '/../../includes/header.php';
$bannerTitle = 'Concurso Público';
$bannerSubtitle = 'Informações sobre editais e seleções do CRCAP';
$bannerIcon = 'fa-trophy';
$bannerBreadcrumb = [['Home','/index.php'],['CRCAP',null],['Concurso',null]];
include __DIR__ . '/../../includes/page_banner.php';
?>
<main class="container mx-auto px-4 py-12">
<div class="grid lg:grid-cols-4 gap-8">
    <aside class="lg:col-span-1">
        <div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden">
            <div class="bg-[#001644] px-5 py-4"><h3 class="text-sm font-bold text-white"><i class="fas fa-landmark mr-2"></i>CRCAP</h3></div>
            <nav class="p-2">
                <?php foreach ([['historico.php','fa-book-open','Histórico',false],['organograma.php','fa-sitemap','Organograma',false],['delegacias.php','fa-map-marker-alt','Delegacias',false],['composicao.php','fa-users','Composição',false],['editais.php','fa-file-alt','Editais',false],['concurso.php','fa-trophy','Concurso',true]] as $l): ?>
                <a href="/crcap/pages/crcap/<?= $l[0] ?>" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[11px] font-semibold transition mb-0.5 <?= $l[3] ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                    <i class="fas <?= $l[1] ?> w-4 text-center text-[#BF8D1A]"></i><?= $l[2] ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Dúvidas frequentes sobre concurso -->
        <div class="bg-white border border-[#001644]/5 rounded-2xl p-5 mt-4">
            <h4 class="text-xs font-bold text-[#001644] uppercase tracking-wider mb-4">Informações Úteis</h4>
            <?php
            $infos = [
                ['fa-question-circle','Como se inscrever?','Acesse o link do edital e siga as instruções para inscrição online.'],
                ['fa-file-alt','Documentação','Tenha em mãos RG, CPF e comprovante de escolaridade.'],
                ['fa-phone','Dúvidas','Entre em contato com a Secretaria pelo telefone ou e-mail.'],
            ];
            foreach ($infos as $inf): ?>
            <div class="flex gap-3 pb-3 mb-3 border-b border-[#001644]/5 last:border-0 last:mb-0 last:pb-0">
                <div class="w-8 h-8 bg-[#BF8D1A]/10 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas <?= $inf[0] ?> text-[#BF8D1A] text-xs"></i>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-[#001644]"><?= $inf[1] ?></p>
                    <p class="text-[9px] text-[#001644]/60 leading-relaxed"><?= $inf[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <div class="lg:col-span-3 space-y-8">
        <!-- Banner informativo -->
        <div class="bg-gradient-to-r from-[#001644] to-[#022E6B] rounded-2xl p-7 text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full -mr-20 -mt-20 blur-3xl"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-[#BF8D1A] rounded-xl flex items-center justify-center"><i class="fas fa-trophy"></i></div>
                    <h2 class="text-xl font-bold">Concurso Público CRCAP</h2>
                </div>
                <p class="text-white/75 text-sm leading-relaxed">
                    Acompanhe aqui todos os editais, resultados e informações sobre processos seletivos e concursos públicos realizados pelo Conselho Regional de Administração do Amapá.
                </p>
            </div>
        </div>

        <!-- Documentos de Concurso -->
        <div>
            <h2 class="text-lg font-bold text-[#001644] mb-5 flex items-center gap-2">
                <i class="fas fa-file-alt text-[#BF8D1A]"></i> Editais e Documentos
            </h2>
            <?php if (!empty($concursos)): ?>
            <div class="space-y-3">
                <?php foreach ($concursos as $doc): ?>
                <div class="bg-white border border-[#001644]/5 rounded-2xl p-5 hover:shadow-lg hover:border-[#BF8D1A]/20 transition group">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0 group-hover:bg-red-100 transition">
                            <i class="fas fa-file-pdf text-red-500 text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-[#001644] text-sm group-hover:text-[#BF8D1A] transition"><?= h($doc['title']) ?></h3>
                            <?php if ($doc['description']): ?>
                            <p class="text-[11px] text-[#022E6B]/60 mt-0.5 line-clamp-1"><?= h($doc['description']) ?></p>
                            <?php endif; ?>
                            <div class="flex gap-4 mt-2 text-[10px] text-[#001644]/50">
                                <?php if ($doc['publication_date']): ?>
                                <span><i class="fas fa-calendar text-[#BF8D1A] mr-1"></i><?= date('d/m/Y', strtotime($doc['publication_date'])) ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-download mr-1"></i><?= $doc['downloads'] ?> downloads</span>
                            </div>
                        </div>
                        <a href="/crcap/pages/download.php?id=<?= $doc['id'] ?>"
                           class="flex-shrink-0 px-4 py-2 bg-[#001644] text-white text-[10px] font-semibold rounded-xl hover:bg-[#BF8D1A] transition">
                            <i class="fas fa-download mr-1"></i> Baixar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- Se não há concursos no BD, exibe seção genérica -->
            <div class="bg-white border border-[#001644]/5 rounded-2xl p-10 text-center">
                <i class="fas fa-trophy text-5xl text-[#001644]/20 mb-4 block"></i>
                <h3 class="font-bold text-[#001644] text-lg mb-2">Nenhum concurso ativo no momento</h3>
                <p class="text-sm text-[#001644]/50 max-w-md mx-auto">
                    Quando houver processos seletivos ou concursos abertos, as informações serão publicadas aqui. Assine nossa newsletter para ser notificado.
                </p>
                <a href="/crcap/index.php" class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition">
                    <i class="fas fa-envelope"></i> Assinar Newsletter
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Eventos relacionados a concurso -->
        <?php if (!empty($eventsConcurso)): ?>
        <div>
            <h2 class="text-lg font-bold text-[#001644] mb-5 flex items-center gap-2">
                <i class="fas fa-calendar-alt text-[#BF8D1A]"></i> Datas Importantes
            </h2>
            <div class="space-y-3">
                <?php foreach ($eventsConcurso as $ev): ?>
                <div class="flex gap-4 bg-white border border-[#001644]/5 rounded-2xl p-4 hover:shadow-md hover:border-[#BF8D1A]/20 transition">
                    <div class="w-14 h-14 bg-[#001644] rounded-xl flex flex-col items-center justify-center flex-shrink-0 text-white">
                        <span class="text-base font-bold leading-none"><?= date('d', strtotime($ev['event_date'])) ?></span>
                        <span class="text-[9px] font-semibold text-[#BF8D1A] uppercase"><?= date('M', strtotime($ev['event_date'])) ?></span>
                    </div>
                    <div>
                        <h4 class="font-semibold text-[#001644] text-sm"><?= h($ev['title']) ?></h4>
                        <p class="text-[10px] text-[#022E6B]/60 mt-0.5"><?= $ev['description'] ? h(substr($ev['description'],0,100)).'...' : '' ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contato para dúvidas -->
        <div class="bg-[#F8FAFC] border border-[#001644]/5 rounded-2xl p-7">
            <h3 class="font-bold text-[#001644] text-sm mb-3 flex items-center gap-2">
                <i class="fas fa-headset text-[#BF8D1A]"></i> Dúvidas sobre o Concurso?
            </h3>
            <p class="text-xs text-[#001644]/70 mb-4 leading-relaxed">
                Nossa equipe está pronta para esclarecer suas dúvidas sobre inscrições, requisitos e cronograma.
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="/crcap/pages/contato.php" class="flex items-center gap-2 px-4 py-2.5 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition">
                    <i class="fas fa-envelope"></i> Enviar mensagem
                </a>
                <a href="tel:96000000000" class="flex items-center gap-2 px-4 py-2.5 border border-[#001644]/20 text-[#001644] text-xs font-semibold rounded-xl hover:bg-white transition">
                    <i class="fas fa-phone text-[#BF8D1A]"></i> Ligar agora
                </a>
            </div>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
