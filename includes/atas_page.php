<?php
// includes/atas_page.php – Template para páginas de Atas das Câmaras
// Variáveis esperadas: $ataSlug, $ataTitle, $ataIcon

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$search  = trim($_GET['q'] ?? '');
$anoFilt = (int)($_GET['ano'] ?? 0);

// Anos disponíveis para este tipo de ata
$anosRows = dbFetchAll($pdo,
    "SELECT DISTINCT YEAR(publication_date) AS ano
     FROM documents
     WHERE category='atas' AND document_type=? AND publication_date IS NOT NULL AND status='active' AND is_public=1
     ORDER BY ano DESC",
    [$ataSlug]);
$anosDisp = array_column($anosRows, 'ano');

// Ano atual (ou mais recente disponível)
$anoAtual  = (int)date('Y');
// Abre: filtro ativo > ano atual (se tiver dados) > mais recente disponível
$anoAberto = $anoFilt ?: (in_array($anoAtual, $anosDisp) ? $anoAtual : ($anosDisp[0] ?? $anoAtual));

// Busca todos os documentos (sem paginação — agrupamos por ano)
$where  = ["d.status='active'", "d.is_public=1", "d.category='atas'", "d.document_type = ?"];
$params = [$ataSlug];
if ($search) { $where[] = "d.title LIKE ?"; $params[] = "%$search%"; }
if ($anoFilt) { $where[] = "YEAR(d.publication_date) = ?"; $params[] = $anoFilt; }
$whereSQL = implode(' AND ', $where);

$atas  = dbFetchAll($pdo,
    "SELECT * FROM documents d WHERE $whereSQL ORDER BY d.publication_date DESC, d.created_at DESC",
    $params);
$total = count($atas);

// Agrupa por ano
$porAno = [];
foreach ($atas as $ata) {
    $y = $ata['publication_date'] ? (int)date('Y', strtotime($ata['publication_date'])) : 0;
    $porAno[$y][] = $ata;
}
krsort($porAno);

// ── Renderiza página ──────────────────────────────────────────────────────────
include __DIR__ . '/header.php';
$bannerTitle      = 'Atas – ' . $ataTitle;
$bannerSubtitle   = 'Registros oficiais das reuniões da Câmara de ' . $ataTitle;
$bannerIcon       = $ataIcon;
$bannerBreadcrumb = [['Home','/index.php'],['Atas das Câmaras',null],[$ataTitle,null]];
include __DIR__ . '/page_banner.php';

$atasMenu = [
    ['desenvolvimento',  'fa-graduation-cap',  'Desenvolvimento Profissional'],
    ['administrativa',   'fa-briefcase',       'Administrativa'],
    ['fiscalizacao',     'fa-search',          'Fiscalização'],
    ['registro',         'fa-file-signature',  'Registro'],
    ['controle-interno', 'fa-check-double',    'Controle Interno'],
];
?>
<main class="container mx-auto px-4 py-10">
<div class="grid lg:grid-cols-4 gap-8">

    <!-- ── Sidebar esquerda (menu original) ─────────────────────────────────── -->
    <aside class="lg:col-span-1 space-y-4">
        <div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden">
            <div class="bg-[#001644] px-5 py-4">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fas fa-file-alt"></i> Atas das Câmaras
                </h3>
            </div>
            <nav class="p-2">
                <?php foreach ($atasMenu as $m): ?>
                <a href="/crcap/pages/atas/<?= $m[0] ?>.php"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[11px] font-semibold transition mb-0.5
                          <?= $ataSlug === $m[0] ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                    <i class="fas <?= $m[1] ?> w-4 text-center text-[#BF8D1A] text-[9px]"></i>
                    <?= $m[2] ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>

    <!-- ── Conteúdo principal ───────────────────────────────────────────────── -->
    <div class="lg:col-span-3">

    <!-- ── Barra de filtros (linha única) ──────────────────────────────────── -->
    <form method="GET" class="bg-white border border-[#001644]/8 rounded-2xl px-4 py-3 mb-8 shadow-sm flex flex-wrap items-center gap-2">

        <!-- Campo busca -->
        <div class="relative flex-1 min-w-[160px]">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#022E6B]/30 text-[10px]"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Buscar ata ou referência..."
                   class="w-full h-9 pl-8 pr-3 text-xs border border-[#001644]/10 rounded-xl focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 bg-[#F8FAFC] transition">
        </div>

        <!-- Select de ano -->
        <?php if (!empty($anosDisp)): ?>
        <div class="relative">
            <i class="fas fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-[#BF8D1A] text-[10px] pointer-events-none"></i>
            <select name="ano"
                    onchange="this.form.submit()"
                    class="h-9 pl-7 pr-8 text-xs font-semibold border border-[#001644]/10 rounded-xl bg-[#F8FAFC] text-[#001644] focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 transition appearance-none cursor-pointer">
                <option value="">Todos os anos</option>
                <?php foreach ($anosDisp as $a): ?>
                <option value="<?= $a ?>" <?= $anoFilt === $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>
            <i class="fas fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-[9px] pointer-events-none"></i>
        </div>
        <?php endif; ?>

        <!-- Botão buscar -->
        <button type="submit"
                class="h-9 px-4 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition flex items-center gap-1.5 flex-shrink-0">
            <i class="fas fa-search text-[10px]"></i>
            <span class="hidden sm:inline">Buscar</span>
        </button>

        <!-- Limpar (só aparece se há filtro ativo) -->
        <?php if ($search || $anoFilt): ?>
        <a href="/crcap/pages/atas/<?= $ataSlug ?>.php"
           class="h-9 px-3 text-xs font-semibold text-[#022E6B] border border-[#001644]/10 rounded-xl hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition flex items-center gap-1.5 flex-shrink-0">
            <i class="fas fa-times text-[10px]"></i>
            <span class="hidden sm:inline">Limpar</span>
        </a>
        <?php endif; ?>

        <!-- Contador -->
        <span class="text-[10px] text-[#022E6B]/50 ml-auto flex-shrink-0">
            <span class="font-bold text-[#001644]"><?= $total ?></span> ata<?= $total !== 1 ? 's' : '' ?>
        </span>

    </form>

    <?php if (empty($atas)): ?>
    <div class="bg-white rounded-2xl border border-[#001644]/5 p-16 text-center">
        <i class="fas fa-file-alt text-5xl text-[#001644]/20 mb-4 block"></i>
        <h3 class="font-bold text-[#001644] text-lg mb-2">Nenhuma ata encontrada</h3>
        <p class="text-sm text-[#001644]/50">
            <?= $search ? 'Tente outros termos de busca.' : 'Nenhuma ata publicada nesta câmara.' ?>
        </p>
    </div>

    <?php else: ?>
    <div class="space-y-5" id="atasContainer">

        <?php foreach ($porAno as $ano => $docs):
            $yearLabel  = $ano ?: 'Sem data';
            $isOpen     = (!$anoFilt && $ano === $anoAberto) || ($anoFilt && $ano === $anoFilt) || $search;
            $panelId    = 'ano_'.$ano;
            $count      = count($docs);
        ?>
        <div class="bg-white rounded-2xl border border-[#001644]/8 overflow-hidden shadow-sm hover:shadow-md transition"
             id="block_<?= $ano ?>">

            <!-- Header do ano (clicável) -->
            <div class="flex items-center gap-3 px-5 py-4 cursor-pointer select-none
                        <?= $isOpen ? 'bg-[#001644]' : 'bg-white hover:bg-[#F8FAFC]' ?> transition"
                 onclick="toggleAno('<?= $panelId ?>', '<?= $ano ?>')">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                            <?= $isOpen ? 'bg-[#BF8D1A]' : 'bg-[#001644]/8' ?>">
                    <i class="fas fa-calendar-alt text-sm <?= $isOpen ? 'text-white' : 'text-[#001644]' ?>"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-sm <?= $isOpen ? 'text-white' : 'text-[#001644]' ?>">
                        <?= $yearLabel ?>
                    </p>
                    <p class="text-[10px] <?= $isOpen ? 'text-white/60' : 'text-[#022E6B]/50' ?>">
                        <?= $count ?> ata<?= $count !== 1 ? 's' : '' ?>
                    </p>
                </div>
                <i id="ico_<?= $panelId ?>"
                   class="fas fa-chevron-down text-xs transition-transform duration-200
                          <?= $isOpen ? 'text-white/80 rotate-180' : 'text-[#001644]/30' ?>"></i>
            </div>

            <!-- Lista de atas do ano -->
            <div id="<?= $panelId ?>" class="<?= $isOpen ? '' : 'hidden' ?>">
                <div class="divide-y divide-[#001644]/5">
                    <?php foreach ($docs as $ata):
                        $ext = strtolower(pathinfo($ata['file_name'] ?? '', PATHINFO_EXTENSION));
                        $extMap = [
                            'pdf'  => ['fa-file-pdf',   'bg-red-50 text-red-500'],
                            'doc'  => ['fa-file-word',  'bg-blue-50 text-blue-600'],
                            'docx' => ['fa-file-word',  'bg-blue-50 text-blue-600'],
                            'xls'  => ['fa-file-excel', 'bg-green-50 text-green-600'],
                            'xlsx' => ['fa-file-excel', 'bg-green-50 text-green-600'],
                        ];
                        [$extIcon, $extCls] = $extMap[$ext] ?? ['fa-file-alt', 'bg-gray-50 text-gray-500'];
                    ?>
                    <div class="flex items-center gap-4 px-5 py-4 hover:bg-[#F8FAFC] transition group">

                        <!-- Ícone tipo -->
                        <div class="w-10 h-10 rounded-xl <?= $extCls ?> flex items-center justify-center flex-shrink-0">
                            <i class="fas <?= $extIcon ?> text-base"></i>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                <?php if ($ata['reference_number']): ?>
                                <span class="text-[9px] font-bold bg-[#001644]/8 text-[#001644] px-2 py-0.5 rounded-full">
                                    <?= htmlspecialchars($ata['reference_number']) ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($ata['publication_date']): ?>
                                <span class="text-[9px] text-[#022E6B]/50">
                                    <i class="fas fa-calendar text-[#BF8D1A] mr-0.5"></i>
                                    <?= date('d/m/Y', strtotime($ata['publication_date'])) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-semibold text-[#001644] text-sm group-hover:text-[#BF8D1A] transition line-clamp-1">
                                <?= htmlspecialchars($ata['title']) ?>
                            </h3>
                            <?php if ($ata['description']): ?>
                            <p class="text-[10px] text-[#022E6B]/60 mt-0.5 line-clamp-1">
                                <?= htmlspecialchars($ata['description']) ?>
                            </p>
                            <?php endif; ?>
                            <div class="flex gap-3 mt-1 text-[9px] text-[#001644]/40">
                                <span><i class="fas fa-download mr-1"></i><?= (int)$ata['downloads'] ?> downloads</span>
                                <?php if ($ata['file_size']): ?>
                                <span><?= $ata['file_size'] >= 1048576 ? round($ata['file_size']/1048576,1).' MB' : round($ata['file_size']/1024).' KB' ?></span>
                                <?php endif; ?>
                                <span class="uppercase font-bold"><?= htmlspecialchars(strtoupper($ext)) ?></span>
                            </div>
                        </div>

                        <!-- Botões: Visualizar (PDF) + Baixar -->
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <?php if ($ext === 'pdf'):
                                $docId  = (int)$ata['id'];
                                $docTit = addslashes($ata['title']);
                                $docUrl = '/crcap/pages/download.php?id=' . $docId . '&view=1';
                            ?>
                            <button type="button"
                                    onclick="openPdfModal('<?= $docId ?>', '<?= $docTit ?>', '<?= $docUrl ?>')"
                                    class="flex items-center gap-1.5 px-3 py-2 bg-white border border-[#001644]/15 text-[#001644] text-[10px] font-bold rounded-xl hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition">
                                <i class="fas fa-eye"></i>
                                <span class="hidden sm:inline">Ver</span>
                            </button>
                            <?php endif; ?>
                            <a href="/crcap/pages/download.php?id=<?= (int)$ata['id'] ?>"
                               class="flex items-center gap-1.5 px-3 py-2 bg-[#001644] text-white text-[10px] font-bold rounded-xl hover:bg-[#BF8D1A] transition">
                                <i class="fas fa-download"></i>
                                <span class="hidden sm:inline">Baixar</span>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
    <?php endif; ?>

    </div><!-- /col-span-3 -->
</div><!-- /grid -->
</main>

<!-- ════════════════════════════════════════════════════════════════════════
     MODAL DE VISUALIZAÇÃO PDF
════════════════════════════════════════════════════════════════════════ -->
<div id="pdfModal" class="fixed inset-0 z-[200] hidden" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" onclick="closePdfModal()"></div>

    <!-- Panel -->
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col pointer-events-auto overflow-hidden">

            <!-- Header do modal -->
            <div class="flex items-center gap-3 px-5 py-3.5 border-b border-[#001644]/8 flex-shrink-0 bg-white">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-file-pdf text-red-500"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p id="pdfModalTitle" class="font-bold text-[#001644] text-sm truncate"></p>
                    <p class="text-[10px] text-[#022E6B]/50">Visualização do documento</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <!-- Imprimir -->
                    <button onclick="printPdf()"
                            class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-[#001644] bg-[#F8FAFC] hover:bg-[#001644] hover:text-white rounded-xl transition border border-[#001644]/10">
                        <i class="fas fa-print"></i>
                        <span class="hidden sm:inline">Imprimir</span>
                    </button>
                    <!-- Baixar -->
                    <a id="pdfModalDownload" href="#"
                       class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-[#BF8D1A] hover:bg-[#001644] rounded-xl transition">
                        <i class="fas fa-download"></i>
                        <span class="hidden sm:inline">Baixar</span>
                    </a>
                    <!-- Fechar -->
                    <button onclick="closePdfModal()"
                            class="w-9 h-9 rounded-xl bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Área do PDF -->
            <div class="flex-1 bg-[#525659] relative min-h-0">
                <!-- Loading spinner -->
                <div id="pdfLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-[#525659] z-10">
                    <div class="w-10 h-10 border-3 border-white/20 border-t-white rounded-full animate-spin mb-3"
                         style="border-width:3px"></div>
                    <p class="text-white/70 text-xs">Carregando PDF...</p>
                </div>
                <!-- iFrame PDF -->
                <iframe id="pdfIframe" src="" class="w-full h-full border-0"
                        onload="document.getElementById('pdfLoading').style.display='none'"></iframe>
            </div>

            <!-- Footer: aviso mobile -->
            <div class="px-5 py-2.5 bg-[#F8FAFC] border-t border-[#001644]/5 flex-shrink-0 flex items-center justify-between">
                <p class="text-[10px] text-[#022E6B]/50">
                    <i class="fas fa-info-circle text-[#BF8D1A] mr-1"></i>
                    Se o PDF não carregar, use o botão Baixar.
                </p>
                <button onclick="closePdfModal()"
                        class="text-[10px] font-semibold text-[#022E6B]/50 hover:text-[#001644] transition">
                    Fechar <i class="fas fa-times ml-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
function toggleAno(panelId, ano) {
    var el  = document.getElementById(panelId);
    var ico = document.getElementById('ico_' + panelId);
    var hdr = el ? el.previousElementSibling : null;
    var isHidden = el.classList.contains('hidden');

    el.classList.toggle('hidden');
    if (ico) ico.classList.toggle('rotate-180');
    if (hdr) {
        if (isHidden) {
            hdr.classList.add('bg-[#001644]');
            hdr.classList.remove('bg-white','hover:bg-[#F8FAFC]');
        } else {
            hdr.classList.remove('bg-[#001644]');
            hdr.classList.add('bg-white','hover:bg-[#F8FAFC]');
        }
        // Atualiza cores dos textos dentro do header
        hdr.querySelectorAll('p').forEach(function(p, i) {
            if (isHidden) {
                p.className = p.className.replace('text-[#001644]','text-white').replace('text-[#022E6B]/50','text-white/60');
            } else {
                p.className = p.className.replace('text-white/60','text-[#022E6B]/50').replace('text-white','text-[#001644]');
            }
        });
    }
}

// ── PDF Modal ─────────────────────────────────────────────────────────────────
var _pdfDownloadUrl = '';

function openPdfModal(id, title, inlineUrl) {
    _pdfDownloadUrl = '/crcap/pages/download.php?id=' + id;

    document.getElementById('pdfModalTitle').textContent   = title;
    document.getElementById('pdfModalDownload').href        = _pdfDownloadUrl;
    document.getElementById('pdfLoading').style.display    = 'flex';
    document.getElementById('pdfIframe').src               = inlineUrl;
    document.getElementById('pdfModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePdfModal() {
    document.getElementById('pdfModal').classList.add('hidden');
    document.getElementById('pdfIframe').src = '';
    document.body.style.overflow = '';
}

function printPdf() {
    var iframe = document.getElementById('pdfIframe');
    try {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    } catch(e) {
        // Cross-origin fallback: open in new tab and print from there
        var w = window.open(_pdfDownloadUrl, '_blank');
        if (w) { w.addEventListener('load', function(){ w.print(); }); }
    }
}

// ESC fecha modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePdfModal();
});
</script>

<?php include __DIR__ . '/footer.php'; ?>