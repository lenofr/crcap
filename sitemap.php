<?php
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

$base = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'crcap.org.br');

$urls = [];

// Static pages
$statics = [
    ['/crcap/', '1.0', 'daily'],
    ['/noticias', '0.9', 'daily'],
    ['/eventos', '0.8', 'weekly'],
    ['/agenda', '0.7', 'weekly'],
    ['/galeria', '0.6', 'monthly'],
    ['/contato', '0.5', 'monthly'],
    ['/ouvidoria', '0.6', 'monthly'],
    ['/editais', '0.7', 'weekly'],
    ['/faq', '0.5', 'monthly'],
    ['/busca', '0.4', 'monthly'],
    ['/crcap/pages/historico.php', '0.5', 'yearly'],
    ['/crcap/pages/organograma.php', '0.5', 'yearly'],
    ['/crcap/pages/delegacias.php', '0.5', 'yearly'],
    ['/crcap/pages/composicao.php', '0.5', 'yearly'],
    ['/crcap/pages/comissoes.php', '0.6', 'monthly'],
];
foreach ($statics as $s) {
    $urls[] = ['loc'=>$base.$s[0], 'priority'=>$s[1], 'changefreq'=>$s[2], 'lastmod'=>date('Y-m-d')];
}

// Posts
$posts = dbFetchAll($pdo, "SELECT slug, updated_at FROM posts WHERE status='published' ORDER BY updated_at DESC LIMIT 500");
foreach ($posts as $p) {
    $urls[] = ['loc'=>$base.'/noticias/'.rawurlencode($p['slug']), 'priority'=>'0.7', 'changefreq'=>'weekly', 'lastmod'=>date('Y-m-d', strtotime($p['updated_at']))];
}

// Events
$events = dbFetchAll($pdo, "SELECT slug, updated_at FROM events WHERE status='published' ORDER BY event_date DESC LIMIT 200");
foreach ($events as $e) {
    $urls[] = ['loc'=>$base.'/eventos/'.rawurlencode($e['slug']), 'priority'=>'0.6', 'changefreq'=>'weekly', 'lastmod'=>date('Y-m-d', strtotime($e['updated_at']))];
}

// Static pages from DB
$pages = dbFetchAll($pdo, "SELECT slug, updated_at FROM pages WHERE status='published' AND visibility='public' LIMIT 100");
foreach ($pages as $pg) {
    $urls[] = ['loc'=>$base.'/pagina/'.rawurlencode($pg['slug']), 'priority'=>'0.5', 'changefreq'=>'monthly', 'lastmod'=>date('Y-m-d', strtotime($pg['updated_at']))];
}
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($url['loc']) ?></loc>
        <lastmod><?= $url['lastmod'] ?></lastmod>
        <changefreq><?= $url['changefreq'] ?></changefreq>
        <priority><?= $url['priority'] ?></priority>
    </url>
<?php endforeach; ?>
</urlset>

/**
 * Dynamic XML Sitemap
 * Access: /sitemap.php  (or configure as sitemap.xml via .htaccess)
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
         . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$urls = [];

// Static pages
$static = [
    ['/crcap/', '1.0', 'daily'],
    ['/crcap/pages/noticias.php', '0.9', 'daily'],
    ['/crcap/pages/eventos.php', '0.9', 'weekly'],
    ['/crcap/pages/galeria.php', '0.7', 'weekly'],
    ['/crcap/pages/agenda.php', '0.7', 'daily'],
    ['/crcap/pages/contato.php', '0.6', 'monthly'],
    ['/crcap/pages/ouvidoria.php', '0.6', 'monthly'],
    ['/crcap/pages/faq.php', '0.6', 'monthly'],
    ['/crcap/pages/editais.php', '0.8', 'weekly'],
    ['/crcap/pages/historico.php', '0.5', 'yearly'],
    ['/crcap/pages/organograma.php', '0.5', 'monthly'],
    ['/crcap/pages/delegacias.php', '0.5', 'monthly'],
    ['/crcap/pages/composicao.php', '0.5', 'monthly'],
    ['/crcap/pages/comissoes.php', '0.6', 'monthly'],
    ['/crcap/pages/busca.php', '0.4', 'monthly'],
    ['/crcap/pages/governanca/sobre-governanca.php', '0.6', 'monthly'],
    ['/crcap/pages/governanca/dados-abertos.php', '0.6', 'monthly'],
    ['/crcap/pages/governanca/transparencia-contas.php', '0.7', 'monthly'],
    ['/crcap/pages/governanca/auditoria.php', '0.5', 'monthly'],
    ['/crcap/pages/governanca/lgpd.php', '0.5', 'monthly'],
    ['/crcap/pages/governanca/gestao-risco.php', '0.5', 'monthly'],
    ['/crcap/pages/governanca/governanca-digital.php', '0.5', 'monthly'],
    ['/crcap/pages/fiscalizacao/o-que-e.php', '0.5', 'yearly'],
    ['/crcap/pages/fiscalizacao/denuncia.php', '0.6', 'monthly'],
];

foreach ($static as [$loc, $priority, $freq]) {
    $urls[] = ['loc' => $baseUrl . $loc, 'priority' => $priority, 'changefreq' => $freq, 'lastmod' => date('Y-m-d')];
}

// Published posts
$posts = dbFetchAll($pdo,
    "SELECT slug, updated_at FROM posts WHERE status='published' ORDER BY updated_at DESC LIMIT 500");
foreach ($posts as $p) {
    $urls[] = [
        'loc'        => $baseUrl . '/crcap/pages/post.php?slug=' . urlencode($p['slug']),
        'priority'   => '0.8',
        'changefreq' => 'weekly',
        'lastmod'    => date('Y-m-d', strtotime($p['updated_at'])),
    ];
}

// Published events
$events = dbFetchAll($pdo,
    "SELECT slug, updated_at FROM events WHERE status='published' ORDER BY event_date DESC LIMIT 200");
foreach ($events as $e) {
    $urls[] = [
        'loc'        => $baseUrl . '/crcap/pages/evento.php?slug=' . urlencode($e['slug']),
        'priority'   => '0.7',
        'changefreq' => 'weekly',
        'lastmod'    => date('Y-m-d', strtotime($e['updated_at'])),
    ];
}

// Published galleries
$galleries = dbFetchAll($pdo,
    "SELECT slug, updated_at FROM galleries WHERE status='published' ORDER BY updated_at DESC LIMIT 100");
foreach ($galleries as $g) {
    $urls[] = [
        'loc'        => $baseUrl . '/crcap/pages/galeria.php?galeria=' . urlencode($g['slug']),
        'priority'   => '0.5',
        'changefreq' => 'monthly',
        'lastmod'    => date('Y-m-d', strtotime($g['updated_at'])),
    ];
}

// Published pages from DB
$pages = dbFetchAll($pdo,
    "SELECT slug, updated_at FROM pages WHERE status='published' AND visibility='public' ORDER BY updated_at DESC");
foreach ($pages as $pg) {
    $urls[] = [
        'loc'        => $baseUrl . '/crcap/pages/' . urlencode($pg['slug']) . '.php',
        'priority'   => '0.5',
        'changefreq' => 'monthly',
        'lastmod'    => date('Y-m-d', strtotime($pg['updated_at'])),
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $url) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
    echo "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
    echo "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $url['priority'] . "</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
