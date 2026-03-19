<?php
/**
 * page-template.php — Template base para páginas geradas automaticamente
 * Deploy: /crcap/includes/page-template.php
 *
 * Uso: cada página gerada inclui este arquivo.
 * A variável $PAGE_SLUG deve estar definida antes do include.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$slug = $PAGE_SLUG ?? basename($_SERVER['PHP_SELF'], '.php');

// Busca no BD
$page = dbFetch($pdo, "SELECT * FROM pages WHERE slug=?", [$slug]);

// Verifica visibilidade
if (!$page || $page['status'] === 'draft' && !(isLogged() && in_array($_SESSION['role']??'', ['admin','editor']))) {
    http_response_code(404);
    require_once __DIR__ . '/header.php';
    echo '<div style="text-align:center;padding:5rem 2rem"><h2 style="color:#001644">Página não encontrada</h2><a href="/crcap/index.php" style="color:#BF8D1A">Voltar ao início</a></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

// Incrementa views
try { $pdo->prepare("UPDATE pages SET views=views+1 WHERE id=?")->execute([$page['id']]); } catch(Exception $e){}

// SEO
$pageTitle = $page['seo_title'] ?: $page['title'];
$pageDesc  = $page['seo_description'] ?: '';

// Extrai botão painel se existir
$content = $page['content'] ?? '';
$btnPanelHtml = ''; $btnAlign = 'left';
if (str_starts_with($content, '<!--BTNPANEL:')) {
    preg_match('/<!--BTNPANEL:([^-]+)-->/', $content, $m);
    if ($m) {
        $decoded = base64_decode($m[1]);
        [$btnPanelHtml, $alignPart] = array_pad(explode('||ALIGN:', $decoded), 2, 'left');
        $btnAlign = $alignPart;
        $content = preg_replace('/<!--BTNPANEL:[^-]+-->/', '', $content);
    }
}

require_once __DIR__ . '/header.php';
?>
<main class="min-h-screen" style="background:#F8FAFC">

    <!-- Cabeçalho da página -->
    <div style="background:linear-gradient(135deg,#001644,#022E6B);padding:3rem 0 2rem">
        <div style="max-width:900px;margin:0 auto;padding:0 1.5rem">
            <div style="display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:rgba(255,255,255,.45);margin-bottom:.875rem">
                <a href="/crcap/index.php" style="color:rgba(255,255,255,.45);text-decoration:none">Início</a>
                <i class="fas fa-chevron-right" style="font-size:8px"></i>
                <?php if ($page['menu_section']): ?>
                <span><?= htmlspecialchars(ucfirst($page['menu_section'])) ?></span>
                <i class="fas fa-chevron-right" style="font-size:8px"></i>
                <?php endif; ?>
                <span style="color:rgba(255,255,255,.7)"><?= htmlspecialchars($page['title']) ?></span>
            </div>
            <h1 style="color:white;font-size:2rem;font-weight:800;margin:0;line-height:1.25">
                <?= htmlspecialchars($page['title']) ?>
            </h1>
            <?php if ($pageDesc): ?>
            <p style="color:rgba(255,255,255,.6);font-size:.9rem;margin:.75rem 0 0;max-width:640px">
                <?= htmlspecialchars($pageDesc) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Conteúdo -->
    <div style="max-width:900px;margin:0 auto;padding:2.5rem 1.5rem 4rem">

        <?php if ($btnPanelHtml): ?>
        <div style="margin-bottom:1.5rem;text-align:<?= htmlspecialchars($btnAlign) ?>">
            <?= $btnPanelHtml ?>
        </div>
        <?php endif; ?>

        <div class="page-content" style="font-family:system-ui,-apple-system,sans-serif;font-size:15px;line-height:1.8;color:#022E6B">
            <?= $content ?>
        </div>

        <!-- Rodapé da página -->
        <div style="margin-top:3rem;padding-top:1.5rem;border-top:1px solid rgba(0,22,68,.08);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
            <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:.5rem;color:rgba(2,46,107,.45);font-size:.8rem;font-weight:600;text-decoration:none">
                <i class="fas fa-arrow-left" style="font-size:.7rem"></i> Voltar
            </a>
            <?php if (!empty($page['updated_at'])): ?>
            <span style="font-size:.72rem;color:rgba(2,46,107,.3)">
                <i class="fas fa-clock" style="margin-right:3px"></i>
                Atualizado em <?= date('d/m/Y', strtotime($page['updated_at'])) ?>
            </span>
            <?php endif; ?>
        </div>

        <?php if (isLogged() && in_array($_SESSION['role']??'', ['admin','editor'])): ?>
        <a href="/crcap/admin/pages.php?action=edit&id=<?= $page['id'] ?>"
           style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:100;display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.1rem;background:#001644;color:white;border-radius:2rem;font-size:.75rem;font-weight:700;text-decoration:none;box-shadow:0 8px 24px rgba(0,22,68,.3)">
            <i class="fas fa-edit" style="color:#BF8D1A"></i> Editar
        </a>
        <?php endif; ?>
    </div>
</main>

<style>
.page-content h1,.page-content h2,.page-content h3,.page-content h4{color:#001644;margin:1.5rem 0 .75rem;line-height:1.3}
.page-content h1{font-size:1.875rem;font-weight:800}.page-content h2{font-size:1.5rem;font-weight:700}
.page-content h3{font-size:1.25rem;font-weight:700}.page-content h4{font-size:1.1rem;font-weight:600}
.page-content p{margin:0 0 1rem}.page-content ul,.page-content ol{padding-left:1.5rem;margin:0 0 1rem}
.page-content li{margin-bottom:.4rem}.page-content a{color:#BF8D1A;text-decoration:underline}
.page-content a:hover{color:#001644}.page-content img{max-width:100%;height:auto;border-radius:8px}
.page-content table{width:100%;border-collapse:collapse;margin:1rem 0;font-size:14px}
.page-content td,.page-content th{padding:10px 14px;border:1px solid rgba(0,22,68,.1)}
.page-content th{background:#001644;color:white;font-weight:700}
.page-content tr:nth-child(even) td{background:rgba(0,22,68,.02)}
.page-content iframe{border-radius:8px;max-width:100%}
.page-content blockquote{border-left:4px solid #BF8D1A;padding:1rem 1.25rem;background:#FFFDF5;border-radius:0 8px 8px 0;margin:1.5rem 0;color:#001644}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
