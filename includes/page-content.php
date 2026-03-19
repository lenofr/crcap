<?php
/**
 * /crcap/includes/page-content.php
 * Layout: grid lg:grid-cols-4 com aside (col-1) + conteúdo (col-3)
 * Igual ao padrão das páginas de governança existentes
 */
if (!isset($pdo)) {
    if (isset($pageDefaultContent) && is_callable($pageDefaultContent)) $pageDefaultContent();
    return;
}

// ── Buscar conteúdo no BD ─────────────────────────────────────────────────
$_pc_row = null;
try {
    $_pc_row = dbFetch($pdo,
        "SELECT id, title, content, seo_title, seo_description, views, updated_at, status
         FROM pages WHERE slug = ? LIMIT 1",
        [$pageSlug ?? '']
    );
} catch (Exception $_e) {}

$_pc_html     = '';
$_pc_btnpanel = '';
$_pc_hasdb    = false;

if ($_pc_row && !empty(trim($_pc_row['content'] ?? ''))) {
    $raw = $_pc_row['content'];
    if (preg_match('/^<!--BTNPANEL:([A-Za-z0-9+\/=]+)-->/', $raw, $_m)) {
        $_dec = base64_decode($_m[1]);
        $_aln = 'text-align:left;';
        if (preg_match('/\|\|ALIGN:(\w+)$/', $_dec, $_am)) {
            $_dec = substr($_dec, 0, -(strlen('||ALIGN:'.$_am[1])));
            $_aln = 'text-align:'.$_am[1].';';
        }
        $_pc_btnpanel = '<div style="margin:0 0 1.5rem;'.$_aln.'">'.$_dec.'</div>';
        $raw = substr($raw, strlen($_m[0]));
    }
    if (trim($raw) !== '') {
        $_pc_html  = $raw;
        $_pc_hasdb = true;
        if (!empty($_pc_row['seo_title']))       $pageTitle       = $_pc_row['seo_title'].' · CRCAP';
        if (!empty($_pc_row['seo_description'])) $metaDescription = $_pc_row['seo_description'];
        try { dbExec($pdo, "UPDATE pages SET views=views+1 WHERE id=?", [$_pc_row['id']]); }
        catch(Exception $_e2) {}
    }
}

$_pc_hasSidebar = !empty($activeMenu) && $activeMenu !== 'geral';
?>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-4 gap-8">

        <?php if ($_pc_hasSidebar): ?>
        <!-- ── Sidebar ─────────────────────────────────────────────── -->
        <aside class="lg:col-span-1">
            <?php include __DIR__ . '/sidebar-menu.php'; ?>

            <?php /* Caixa extra: mobile toggle */ ?>
            <div class="lg:hidden mt-3">
                <button onclick="var s=document.getElementById('mob-sb-inner');s.classList.toggle('hidden')"
                        class="flex items-center gap-2 px-4 py-2.5 bg-[#001644] text-white rounded-xl text-sm font-bold w-full">
                    <i class="fas fa-bars text-[#BF8D1A] text-xs"></i>
                    <span>Menu da seção</span>
                    <i class="fas fa-chevron-down ml-auto text-[10px] opacity-50"></i>
                </button>
                <div id="mob-sb-inner" class="hidden mt-2">
                    <?php include __DIR__ . '/sidebar-menu.php'; ?>
                </div>
            </div>
        </aside>
        <?php endif; ?>

        <!-- ── Conteúdo ────────────────────────────────────────────── -->
        <div class="<?= $_pc_hasSidebar ? 'lg:col-span-3' : 'lg:col-span-4' ?>">

            <?php if ($_pc_hasdb): ?>
            <!-- Conteúdo do BD -->
            <?php if ($_pc_btnpanel): ?>
            <div class="mb-5"><?= $_pc_btnpanel ?></div>
            <?php endif; ?>
            <div class="bg-white rounded-2xl border border-[#001644]/5 overflow-hidden">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-[#001644] mb-4">
                        <?= htmlspecialchars($_pc_row['title'] ?? '') ?>
                    </h2>
                    <div class="prose max-w-none text-sm text-[#001644] leading-relaxed">
                        <?= $_pc_html ?>
                    </div>
                    <?php if (!empty($_pc_row['updated_at'])): ?>
                    <p class="text-[10px] text-[#001644]/30 mt-8 pt-4 border-t border-[#001644]/5 flex items-center gap-1.5">
                        <i class="fas fa-clock"></i>
                        Atualizado em <?= date('d/m/Y \à\s H\hi', strtotime($_pc_row['updated_at'])) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Caixa "Precisa de mais informações?" -->
            <div class="mt-6 bg-[#F8FAFC] border border-[#001644]/5 rounded-2xl p-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#001644]/8 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-headset text-[#001644] text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-bold text-[#001644]">Precisa de mais informações?</p>
                        <p class="text-[10px] text-[#001644]/60">Entre em contato com nossa equipe.</p>
                    </div>
                    <a href="/crcap/pages/contato.php"
                       class="px-4 py-2 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition flex-shrink-0">
                        Fale Conosco
                    </a>
                </div>
            </div>

            <?php else: ?>
            <!-- Fallback: conteúdo original hardcoded -->
            <?php if (isset($pageDefaultContent) && is_callable($pageDefaultContent)) $pageDefaultContent(); ?>
            <?php endif; ?>

        </div>
    </div>
</main>

<style>
/* Prose compatível com conteúdo do Quill editor */
.prose p { margin: .75rem 0; }
.prose h1,.prose h2,.prose h3,.prose h4 { font-weight:700; color:#001644; margin:1.5rem 0 .75rem; line-height:1.3; }
.prose h2 { font-size:1.25rem; border-bottom:1px solid rgba(0,22,68,.08); padding-bottom:.4rem; }
.prose h3 { font-size:1.05rem; }
.prose a  { color:#BF8D1A; text-decoration:underline; }
.prose a:hover { color:#001644; }
.prose ul,.prose ol { margin:.75rem 0 .75rem 1.5rem; }
.prose li { margin:.3rem 0; }
.prose table { width:100%; border-collapse:collapse; margin:1rem 0; font-size:.85rem; }
.prose td,.prose th { padding:8px 12px; border:1px solid rgba(0,22,68,.1); }
.prose th { background:#001644; color:white; font-weight:600; }
.prose tr:nth-child(even) td { background:#F8FAFC; }
.prose img { max-width:100%; border-radius:.75rem; height:auto; }
.prose blockquote { border-left:4px solid #BF8D1A; padding:.5rem 1rem; background:#FFFDF5; margin:1rem 0; border-radius:0 .5rem .5rem 0; }
.prose iframe { max-width:100%; border-radius:.5rem; }
.prose hr { border:none; border-top:2px solid rgba(0,22,68,.08); margin:2rem 0; }
/* Quill classes */
.ql-align-justify { text-align:justify; }
.ql-align-center  { text-align:center; }
.ql-align-right   { text-align:right; }
</style>
<?php
unset($_pc_row,$_pc_html,$_pc_btnpanel,$_pc_hasdb,$_pc_hasSidebar,$_m,$_dec,$_aln,$_am,$_e,$_e2);