<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

$pageTitle  = 'Atas – Câmara de Fiscalização · CRCAP';
$activeMenu = 'atas';
$pageSlug   = 'atas-fiscalizacao';
$icon       = 'fa-file-signature';
$docType    = 'fiscalizacao';

$_atas = dbFetchAll($pdo,
    "SELECT id, title, description, file_path, file_name, file_size, file_type,
            reference_number, publication_date, status
     FROM documents
     WHERE category = 'atas' AND document_type = ? AND status = 'active'
     ORDER BY publication_date DESC, id DESC",
    [$docType]
);

$_atasByYear = [];
foreach ($_atas as $_a) {
    $year = !empty($_a['publication_date']) ? date('Y', strtotime($_a['publication_date'])) : 'S/D';
    $_atasByYear[$year][] = $_a;
}
krsort($_atasByYear);

$_currentYear = date('Y');
$_years       = array_keys($_atasByYear);

function fmtSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes/1048576, 1).' MB';
    if ($bytes >= 1024)    return round($bytes/1024).' KB';
    return $bytes.' B';
}

include '../../includes/header.php';
?>

<!-- ══ MODAL VISUALIZADOR ══════════════════════════════════════════════════ -->
<div id="doc-modal"
     class="fixed inset-0 z-[999] flex items-center justify-center p-4 hidden"
     onclick="if(event.target===this) closeModal()">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-[#001644]/80 backdrop-blur-sm"></div>

    <!-- Janela -->
    <div class="relative bg-white rounded-2xl shadow-2xl flex flex-col z-10
                w-full max-w-5xl" style="height:90vh">

        <!-- Header do modal -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-[#001644]/08 flex-shrink-0">
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-pdf text-red-500 text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p id="modal-title" class="text-sm font-bold text-[#001644] truncate">Documento</p>
                <p id="modal-meta"  class="text-[10px] text-[#022E6B]/50 mt-0.5"></p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Botão baixar -->
                <a id="modal-download" href="#" target="_blank"
                   class="flex items-center gap-2 px-4 py-2 bg-[#001644] hover:bg-[#022E6B] text-white text-xs font-semibold rounded-xl transition">
                    <i class="fas fa-download text-[10px]"></i>
                    <span class="hidden sm:inline">Baixar</span>
                </a>
                <!-- Fechar -->
                <button onclick="closeModal()"
                        class="w-9 h-9 rounded-xl bg-[#F8FAFC] hover:bg-red-50 hover:text-red-500 text-[#001644]/50 flex items-center justify-center transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Corpo: iframe PDF -->
        <div class="flex-1 relative bg-[#F8FAFC] rounded-b-2xl overflow-hidden">
            <!-- Loading -->
            <div id="modal-loading" class="absolute inset-0 flex flex-col items-center justify-center gap-3 z-10 bg-[#F8FAFC]">
                <div class="w-12 h-12 border-4 border-[#001644]/10 border-t-[#BF8D1A] rounded-full animate-spin"></div>
                <p class="text-xs text-[#022E6B]/50 font-medium">Carregando documento...</p>
            </div>
            <!-- Fallback para navegadores que bloqueiam iframe -->
            <div id="modal-fallback" class="absolute inset-0 flex-col items-center justify-center gap-4 z-20 bg-[#F8FAFC] hidden">
                <i class="fas fa-file-pdf text-5xl text-red-400"></i>
                <p class="text-sm font-semibold text-[#001644]">Não foi possível exibir o PDF inline.</p>
                <a id="modal-fallback-link" href="#" target="_blank"
                   class="flex items-center gap-2 px-5 py-2.5 bg-[#001644] text-white text-sm font-semibold rounded-xl hover:bg-[#022E6B] transition">
                    <i class="fas fa-external-link-alt text-[10px]"></i>Abrir em nova aba
                </a>
            </div>
            <iframe id="modal-iframe" src="" title="Visualizador de documento"
                    class="w-full h-full border-0"
                    onload="iframeLoaded()"
                    onerror="iframeError()">
            </iframe>
        </div>
    </div>
</div>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-12 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 20% 80%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-5">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span>Atas das Câmaras</span>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Atas – Câmara de Fiscalização</span>
        </div>
        <div class="flex items-start gap-5">
            <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-2xl flex-shrink-0">
                <i class="fas fa-file-signature"></i>
            </div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Atas – Câmara de Fiscalização</h1>
                <p class="text-white/70 text-sm max-w-2xl">Registros oficiais das reuniões da Câmara de Fiscalização</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
<div class="grid lg:grid-cols-4 gap-8">

    <!-- Sidebar -->
    <aside class="lg:col-span-1">
        <?php include __DIR__ . '/../../includes/sidebar-menu.php'; ?>
    </aside>

    <!-- Conteúdo -->
    <div class="lg:col-span-3">

        <!-- Busca + filtro -->
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-[#001644]/30 text-sm"></i>
                <input type="text" id="ata-search" placeholder="Buscar ata ou referência..."
                       class="w-full pl-11 pr-4 py-3 bg-white border border-[#001644]/08 rounded-xl text-sm text-[#001644] placeholder-[#001644]/35 focus:outline-none focus:ring-2 focus:ring-[#BF8D1A]/30 shadow-sm transition">
            </div>
            <?php if (count($_years) > 1): ?>
            <div class="flex items-center gap-2 bg-white border border-[#001644]/08 rounded-xl px-3 shadow-sm">
                <i class="fas fa-filter text-[#BF8D1A] text-xs flex-shrink-0"></i>
                <select id="year-filter"
                        class="py-3 pr-2 text-sm text-[#001644] font-medium bg-transparent focus:outline-none cursor-pointer">
                    <option value="all">Todos os anos</option>
                    <?php foreach ($_years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $_currentYear ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($_atasByYear)): ?>
        <div class="bg-white rounded-2xl border border-[#001644]/06 shadow-sm p-12 text-center">
            <div class="w-16 h-16 bg-[#F8FAFC] rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-file-signature text-3xl text-[#001644]/20"></i>
            </div>
            <h3 class="text-lg font-bold text-[#001644] mb-2">Nenhuma ata cadastrada</h3>
            <p class="text-sm text-[#022E6B]/50">As atas desta câmara serão publicadas em breve.</p>
        </div>

        <?php else: ?>
        <div id="atas-container" class="space-y-4">
            <?php foreach ($_atasByYear as $year => $docs):
                $isCurrentYear = ($year == $_currentYear);
            ?>
            <div class="ata-year-block" data-year="<?= $year ?>">

                <!-- Header do ano -->
                <button type="button" onclick="toggleYear(this)"
                        class="year-toggle w-full flex items-center gap-3 mb-0 group">
                    <div class="w-10 h-10 bg-[#001644] rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm group-hover:bg-[#022E6B] transition">
                        <i class="fas fa-calendar-alt text-[#BF8D1A] text-sm"></i>
                    </div>
                    <div class="flex-1 text-left">
                        <h2 class="text-base font-bold text-[#001644]"><?= htmlspecialchars($year) ?></h2>
                        <p class="text-[10px] text-[#022E6B]/50"><?= count($docs) ?> <?= count($docs)===1?'ata':'atas' ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-[#001644]/30 text-xs transition-transform duration-300 year-chevron
                              <?= $isCurrentYear ? '' : '-rotate-90' ?>"></i>
                </button>

                <!-- Lista -->
                <div class="year-content mt-3 <?= $isCurrentYear ? '' : 'hidden' ?>">
                    <div class="bg-white rounded-2xl border border-[#001644]/06 shadow-sm overflow-hidden">
                        <?php foreach ($docs as $i => $_d):
                            $dlUrl   = '/crcap/'.ltrim($_d['file_path'] ?? '', '/');
                            $ftype   = strtolower($_d['file_type'] ?? 'pdf');
                            $fsize   = !empty($_d['file_size']) ? fmtSize((int)$_d['file_size']) : '';
                            $pubDate = !empty($_d['publication_date']) ? date('d/m/Y', strtotime($_d['publication_date'])) : '';
                            $ref     = $_d['reference_number'] ?? '';
                            $isLast  = ($i === count($docs) - 1);
                            $isPdf   = ($ftype === 'pdf');
                            $titleJs = addslashes($_d['title']);
                            $metaJs  = addslashes(implode(' · ', array_filter([$pubDate, $fsize ? strtoupper($ftype).' · '.$fsize : ''])));
                        ?>
                        <div class="ata-item flex items-center gap-4 px-5 py-4
                                    <?= $isLast ? '' : 'border-b border-[#001644]/04' ?>
                                    hover:bg-[#F8FAFC] transition group"
                             data-search="<?= strtolower(htmlspecialchars($_d['title'].' '.$ref)) ?>">

                            <!-- Ícone -->
                            <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center shadow-sm
                                        <?= $isPdf ? 'bg-red-50' : 'bg-blue-50' ?>">
                                <i class="fas <?= $isPdf ? 'fa-file-pdf text-red-500' : 'fa-file text-blue-500' ?> text-base"></i>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-[#001644] truncate group-hover:text-[#BF8D1A] transition">
                                    <?= htmlspecialchars($_d['title']) ?>
                                </p>
                                <div class="flex items-center flex-wrap gap-x-3 gap-y-0.5 mt-0.5">
                                    <?php if ($pubDate): ?>
                                    <span class="text-[10px] text-[#022E6B]/50 flex items-center gap-1">
                                        <i class="fas fa-calendar text-[8px]"></i><?= $pubDate ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($fsize): ?>
                                    <span class="text-[10px] text-[#022E6B]/50 font-medium uppercase">
                                        <?= strtoupper($ftype) ?> · <?= $fsize ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($ref): ?>
                                    <span class="text-[10px] bg-[#001644]/05 text-[#001644]/50 px-2 py-0.5 rounded-full font-medium">
                                        Ref: <?= htmlspecialchars($ref) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <!-- Visualizar -->
                                <?php if ($isPdf): ?>
                                <button type="button"
                                        onclick="openModal('<?= htmlspecialchars($dlUrl, ENT_QUOTES) ?>', '<?= $titleJs ?>', '<?= $metaJs ?>')"
                                        class="flex items-center gap-2 px-3 py-2 bg-[#F0F4F8] hover:bg-[#BF8D1A]/10 text-[#001644] hover:text-[#BF8D1A] text-xs font-semibold rounded-xl transition border border-[#001644]/08">
                                    <i class="fas fa-eye text-[10px]"></i>
                                    <span class="hidden sm:inline">Visualizar</span>
                                </button>
                                <?php endif; ?>
                                <!-- Baixar -->
                                <a href="<?= htmlspecialchars($dlUrl) ?>" target="_blank"
                                   class="flex items-center gap-2 px-3 py-2 bg-[#001644] hover:bg-[#022E6B] text-white text-xs font-semibold rounded-xl transition shadow-sm">
                                    <i class="fas fa-download text-[10px]"></i>
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

        <div id="no-results" class="hidden bg-white rounded-2xl border border-[#001644]/06 shadow-sm p-10 text-center mt-4">
            <i class="fas fa-search text-3xl text-[#001644]/15 mb-3 block"></i>
            <p class="text-sm font-semibold text-[#001644] mb-1">Nenhuma ata encontrada</p>
            <p class="text-xs text-[#022E6B]/50">Tente outros termos ou selecione outro ano.</p>
        </div>
        <?php endif; ?>

    </div>
</div>
</main>

<script>
const currentYear = '<?= $_currentYear ?>';

// ── Modal ────────────────────────────────────────────────────────────────
function openModal(url, title, meta) {
    document.getElementById('modal-title').textContent    = title;
    document.getElementById('modal-meta').textContent     = meta;
    document.getElementById('modal-download').href        = url;
    document.getElementById('modal-fallback-link').href   = url;
    document.getElementById('modal-loading').style.display  = 'flex';
    document.getElementById('modal-fallback').classList.add('hidden');
    document.getElementById('modal-iframe').src           = url;
    document.getElementById('doc-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('doc-modal').classList.add('hidden');
    document.getElementById('modal-iframe').src = '';
    document.body.style.overflow = '';
}
function iframeLoaded() {
    document.getElementById('modal-loading').style.display = 'none';
}
function iframeError() {
    document.getElementById('modal-loading').style.display = 'none';
    document.getElementById('modal-fallback').classList.remove('hidden');
}
// Fechar com ESC
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── Toggle ano ───────────────────────────────────────────────────────────
function toggleYear(btn) {
    const content = btn.nextElementSibling;
    const chevron = btn.querySelector('.year-chevron');
    const hidden  = content.classList.contains('hidden');
    content.classList.toggle('hidden', !hidden);
    chevron.style.transform = hidden ? 'rotate(0deg)' : 'rotate(-90deg)';
}

// ── Filtro de ano ─────────────────────────────────────────────────────────
document.getElementById('year-filter')?.addEventListener('change', function () {
    const val = this.value;
    document.querySelectorAll('.ata-year-block').forEach(block => {
        const show = val === 'all' || block.dataset.year === val;
        block.style.display = show ? '' : 'none';
        if (show && val !== 'all') {
            block.querySelector('.year-content')?.classList.remove('hidden');
            const ch = block.querySelector('.year-chevron');
            if (ch) ch.style.transform = 'rotate(0deg)';
        }
    });
    checkEmpty();
});

// ── Busca ─────────────────────────────────────────────────────────────────
document.getElementById('ata-search')?.addEventListener('input', function () {
    const q   = this.value.toLowerCase().trim();
    const sel = document.getElementById('year-filter')?.value ?? 'all';
    document.querySelectorAll('.ata-year-block').forEach(block => {
        const yearOk = sel === 'all' || block.dataset.year === sel;
        if (!yearOk) { block.style.display = 'none'; return; }
        let any = false;
        block.querySelectorAll('.ata-item').forEach(item => {
            const m = !q || item.dataset.search.includes(q);
            item.style.display = m ? '' : 'none';
            if (m) any = true;
        });
        block.style.display = any ? '' : 'none';
        if (q && any) {
            block.querySelector('.year-content')?.classList.remove('hidden');
            const ch = block.querySelector('.year-chevron');
            if (ch) ch.style.transform = 'rotate(0deg)';
        }
    });
    checkEmpty();
});

function checkEmpty() {
    const any = [...document.querySelectorAll('.ata-year-block')].some(b => b.style.display !== 'none');
    document.getElementById('no-results')?.classList.toggle('hidden', any);
}
</script>

<?php include '../../includes/footer.php'; ?>
