<?php
$pageTitle = 'Páginas · Admin CRCAP';
$activeAdm = 'pages';

// ── DELETE/SAVES antes de qualquer output ────────────────────────────────────
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$slug   = trim($_GET['slug'] ?? '');
$msg    = '';

// ══════════════════════════════════════════════════════════════════
// MAPA COMPLETO DO SITE
// ══════════════════════════════════════════════════════════════════
$systemPages = [
    'Principal' => [
        ['file'=>'index.php','url'=>'/crcap/index.php','title'=>'Home · Página Inicial','icon'=>'fa-home','slug'=>'home','section'=>'geral','pdf'=>false],
    ],
    'CRCAP' => [
        ['file'=>'historico.php',   'url'=>'/crcap/pages/historico.php',   'title'=>'Histórico',                   'icon'=>'fa-landmark',        'slug'=>'historico',    'section'=>'crcap','pdf'=>'embed'],
        ['file'=>'organograma.php', 'url'=>'/crcap/pages/organograma.php', 'title'=>'Organograma CRCAP',           'icon'=>'fa-sitemap',          'slug'=>'organograma',  'section'=>'crcap','pdf'=>'embed'],
        ['file'=>'delegacias.php',  'url'=>'/crcap/pages/delegacias.php',  'title'=>'Delegacias / Representações', 'icon'=>'fa-map-marker-alt',   'slug'=>'delegacias',   'section'=>'crcap','pdf'=>false],
        ['file'=>'composicao.php',  'url'=>'/crcap/pages/composicao.php',  'title'=>'Composição do Conselho',      'icon'=>'fa-users',            'slug'=>'composicao',   'section'=>'crcap','pdf'=>'embed'],
        ['file'=>'editais.php',     'url'=>'/crcap/pages/editais.php',     'title'=>'Editais',                     'icon'=>'fa-file-alt',         'slug'=>'editais',      'section'=>'crcap','pdf'=>'list'],
        ['file'=>'concurso.php',    'url'=>'/crcap/pages/concurso.php',    'title'=>'Concurso',                    'icon'=>'fa-trophy',           'slug'=>'concurso',     'section'=>'crcap','pdf'=>'list'],
        ['file'=>'comissoes.php',   'url'=>'/crcap/pages/comissoes.php',   'title'=>'Comissões',                   'icon'=>'fa-users-cog',        'slug'=>'comissoes',    'section'=>'crcap','pdf'=>false],
    ],
    'Governança' => [
        ['file'=>'governanca/sobre.php',             'url'=>'/crcap/pages/governanca/sobre.php',             'title'=>'Sobre a Governança',                'icon'=>'fa-shield-alt',       'slug'=>'sobre-governanca',     'section'=>'governanca','pdf'=>false],
        ['file'=>'governanca/dados-abertos.php',     'url'=>'/crcap/pages/governanca/dados-abertos.php',     'title'=>'Dados Abertos',                     'icon'=>'fa-database',         'slug'=>'dados-abertos',        'section'=>'governanca','pdf'=>'list'],
        ['file'=>'governanca/transparencia.php',     'url'=>'/crcap/pages/governanca/transparencia.php',     'title'=>'Transparência e Prestação de Contas','icon'=>'fa-balance-scale',    'slug'=>'transparencia-contas', 'section'=>'governanca','pdf'=>'list'],
        ['file'=>'governanca/auditoria.php',         'url'=>'/crcap/pages/governanca/auditoria.php',         'title'=>'Auditoria',                         'icon'=>'fa-search-dollar',    'slug'=>'auditoria',            'section'=>'governanca','pdf'=>'list'],
        ['file'=>'governanca/calendario.php',        'url'=>'/crcap/pages/governanca/calendario.php',        'title'=>'Calendário CRCAP',                  'icon'=>'fa-calendar',         'slug'=>'calendario',           'section'=>'governanca','pdf'=>'embed'],
        ['file'=>'governanca/relato-integrado.php',  'url'=>'/crcap/pages/governanca/relato-integrado.php',  'title'=>'Relato Integrado',                  'icon'=>'fa-file-chart-line',  'slug'=>'relato-integrado',     'section'=>'governanca','pdf'=>'embed'],
        ['file'=>'governanca/carta-servico.php',     'url'=>'/crcap/pages/governanca/carta-servico.php',     'title'=>'Carta de Serviço',                  'icon'=>'fa-envelope-open-text','slug'=>'carta-servico',        'section'=>'governanca','pdf'=>'embed'],
        ['file'=>'governanca/cadeia-valor.php',      'url'=>'/crcap/pages/governanca/cadeia-valor.php',      'title'=>'Cadeia de Valor',                   'icon'=>'fa-project-diagram',  'slug'=>'cadeia-valor',         'section'=>'governanca','pdf'=>'embed'],
        ['file'=>'governanca/plano-contratacao.php', 'url'=>'/crcap/pages/governanca/plano-contratacao.php', 'title'=>'Plano Anual de Contratação',         'icon'=>'fa-clipboard-list',   'slug'=>'plano-contratacao',    'section'=>'governanca','pdf'=>'embed'],
        ['file'=>'governanca/plano-lideranca.php',   'url'=>'/crcap/pages/governanca/plano-lideranca.php',   'title'=>'Plano de Desenvolvimento de Líderes','icon'=>'fa-user-tie',         'slug'=>'plano-lideranca',      'section'=>'governanca','pdf'=>'embed'],
        ['file'=>'ouvidoria.php',                    'url'=>'/crcap/pages/ouvidoria.php',                    'title'=>'Ouvidoria',                         'icon'=>'fa-comment-dots',     'slug'=>'ouvidoria',            'section'=>'governanca','pdf'=>false],
        ['file'=>'governanca/comite-integridade.php','url'=>'/crcap/pages/governanca/comite-integridade.php','title'=>'Comitê de Integridade',             'icon'=>'fa-handshake',        'slug'=>'comite-integridade',   'section'=>'governanca','pdf'=>false],
        ['file'=>'governanca/comissao-conduta.php',  'url'=>'/crcap/pages/governanca/comissao-conduta.php',  'title'=>'Comissão de Conduta',               'icon'=>'fa-gavel',            'slug'=>'comissao-conduta',     'section'=>'governanca','pdf'=>false],
        ['file'=>'governanca/gestao-risco.php',      'url'=>'/crcap/pages/governanca/gestao-risco.php',      'title'=>'Gestão de Risco',                   'icon'=>'fa-exclamation-triangle','slug'=>'gestao-risco',       'section'=>'governanca','pdf'=>'list'],
        ['file'=>'governanca/lgpd.php',              'url'=>'/crcap/pages/governanca/lgpd.php',              'title'=>'LGPD',                              'icon'=>'fa-lock',             'slug'=>'lgpd',                 'section'=>'governanca','pdf'=>false],
        ['file'=>'governanca/seguranca-info.php',    'url'=>'/crcap/pages/governanca/seguranca-info.php',    'title'=>'Segurança da Informação',           'icon'=>'fa-shield-virus',     'slug'=>'seguranca-informacao', 'section'=>'governanca','pdf'=>false],
        ['file'=>'governanca/gov-digital.php',       'url'=>'/crcap/pages/governanca/gov-digital.php',       'title'=>'Governança Digital',                'icon'=>'fa-laptop-code',      'slug'=>'governanca-digital',   'section'=>'governanca','pdf'=>false],
        ['file'=>'governanca/logistica.php',         'url'=>'/crcap/pages/governanca/logistica.php',         'title'=>'Logística Sustentável',             'icon'=>'fa-leaf',             'slug'=>'logistica-sustentavel','section'=>'governanca','pdf'=>false],
    ],
    'Atas das Câmaras' => [
        ['file'=>'atas/desenvolvimento.php','url'=>'/crcap/pages/atas/desenvolvimento.php','title'=>'Atas — Desenvolvimento','icon'=>'fa-file-signature','slug'=>'atas-desenvolvimento','section'=>'atas','pdf'=>'atas'],
        ['file'=>'atas/administrativa.php', 'url'=>'/crcap/pages/atas/administrativa.php', 'title'=>'Atas — Administrativa', 'icon'=>'fa-file-signature','slug'=>'atas-administrativa', 'section'=>'atas','pdf'=>'atas'],
        ['file'=>'atas/fiscalizacao.php',   'url'=>'/crcap/pages/atas/fiscalizacao.php',   'title'=>'Atas — Fiscalização',   'icon'=>'fa-file-signature','slug'=>'atas-fiscalizacao',   'section'=>'atas','pdf'=>'atas'],
        ['file'=>'atas/registro.php',       'url'=>'/crcap/pages/atas/registro.php',       'title'=>'Atas — Registro',       'icon'=>'fa-file-signature','slug'=>'atas-registro',       'section'=>'atas','pdf'=>'atas'],
        ['file'=>'atas/controle-interno.php','url'=>'/crcap/pages/atas/controle-interno.php','title'=>'Atas — Controle Interno','icon'=>'fa-file-signature','slug'=>'atas-controle-interno','section'=>'atas','pdf'=>'atas'],
    ],
    'Desenvolvimento Profissional' => [
        ['file'=>'desenvolvimento/agenda-lives.php',       'url'=>'/crcap/pages/desenvolvimento/agenda-lives.php',       'title'=>'Agenda de Lives',        'icon'=>'fa-video',         'slug'=>'agenda-lives',          'section'=>'desenvolvimento','pdf'=>false],
        ['file'=>'desenvolvimento/cursos-ead.php',         'url'=>'/crcap/pages/desenvolvimento/cursos-ead.php',         'title'=>'Cursos a Distância EAD', 'icon'=>'fa-graduation-cap','slug'=>'cursos-ead',            'section'=>'desenvolvimento','pdf'=>false],
        ['file'=>'desenvolvimento/o-que-e.php',            'url'=>'/crcap/pages/desenvolvimento/o-que-e.php',            'title'=>'O que é?',               'icon'=>'fa-question-circle','slug'=>'o-que-e-desenvolvimento','section'=>'desenvolvimento','pdf'=>false],
        ['file'=>'desenvolvimento/palestrante.php',        'url'=>'/crcap/pages/desenvolvimento/palestrante.php',        'title'=>'Cadastro de Palestrante','icon'=>'fa-microphone',    'slug'=>'cadastro-palestrante',  'section'=>'desenvolvimento','pdf'=>false],
        ['file'=>'desenvolvimento/educacao-continuada.php','url'=>'/crcap/pages/desenvolvimento/educacao-continuada.php','title'=>'Educação Continuada',    'icon'=>'fa-book-open',     'slug'=>'educacao-continuada',   'section'=>'desenvolvimento','pdf'=>false],
        ['file'=>'desenvolvimento/sistema-eventos.php',    'url'=>'/crcap/pages/desenvolvimento/sistema-eventos.php',    'title'=>'Sistema de Eventos',     'icon'=>'fa-calendar-plus', 'slug'=>'sistema-eventos',       'section'=>'desenvolvimento','pdf'=>false],
        ['file'=>'desenvolvimento/cursos-epc.php',         'url'=>'/crcap/pages/desenvolvimento/cursos-epc.php',         'title'=>'Cursos Credenciados EPC','icon'=>'fa-certificate',   'slug'=>'cursos-epc',            'section'=>'desenvolvimento','pdf'=>false],
    ],
    'Fiscalização' => [
        ['file'=>'fiscalizacao/o-que-e.php',  'url'=>'/crcap/pages/fiscalizacao/o-que-e.php',  'title'=>'O que é? (Fiscalização)','icon'=>'fa-question-circle',  'slug'=>'o-que-e-fiscalizacao',   'section'=>'fiscalizacao','pdf'=>false],
        ['file'=>'fiscalizacao/denuncia.php', 'url'=>'/crcap/pages/fiscalizacao/denuncia.php', 'title'=>'Denúncia',               'icon'=>'fa-exclamation-circle','slug'=>'denuncia',               'section'=>'fiscalizacao','pdf'=>false],
        ['file'=>'fiscalizacao/eletronica.php','url'=>'/crcap/pages/fiscalizacao/eletronica.php','title'=>'Fiscalização Eletrônica','icon'=>'fa-laptop',           'slug'=>'fiscalizacao-eletronica','section'=>'fiscalizacao','pdf'=>false],
    ],
    'Institucional' => [
        ['file'=>'contato.php',       'url'=>'/crcap/pages/contato.php',       'title'=>'Fale Conosco',           'icon'=>'fa-envelope',       'slug'=>'contato',       'section'=>'geral','pdf'=>false],
        ['file'=>'faq.php',           'url'=>'/crcap/pages/faq.php',           'title'=>'FAQ',                    'icon'=>'fa-question',       'slug'=>'faq',           'section'=>'geral','pdf'=>false],
        ['file'=>'acessibilidade.php','url'=>'/crcap/pages/acessibilidade.php','title'=>'Acessibilidade',         'icon'=>'fa-universal-access','slug'=>'acessibilidade','section'=>'geral','pdf'=>false],
        ['file'=>'privacidade.php',   'url'=>'/crcap/pages/privacidade.php',   'title'=>'Política de Privacidade','icon'=>'fa-user-shield',    'slug'=>'privacidade',   'section'=>'geral','pdf'=>false],
        ['file'=>'termos.php',        'url'=>'/crcap/pages/termos.php',        'title'=>'Termos de Uso',          'icon'=>'fa-file-contract',  'slug'=>'termos',        'section'=>'geral','pdf'=>false],
    ],
];

$slugMap = [];
foreach ($systemPages as $sec => $list) foreach ($list as $pg) $slugMap[$pg['slug']] = $pg;

// ── Gera arquivo PHP no diretório /pages/ ────────────────────────
function generatePageFile(string $slug, string $title, int $pageId): void {
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) return;
    $dir  = dirname(__DIR__) . '/pages/';
    $file = $dir . $slug . '.php';
    $tpl  = '<?php' . PHP_EOL;
    $tpl .= '// Gerado automaticamente pelo admin CRCAP' . PHP_EOL;
    $tpl .= '$PAGE_SLUG = \'' . $slug . '\';' . PHP_EOL;
    $tpl .= "require_once __DIR__ . '/../includes/page-template.php';" . PHP_EOL;
    @file_put_contents($file, $tpl);
}

// ── DELETE ────────────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    // Remove arquivo .php gerado se existir
    $delPage = dbFetch($pdo, "SELECT slug FROM pages WHERE id=?", [$id]);
    if ($delPage && !isset($slugMap[$delPage['slug']])) {
        $delFile = dirname(__DIR__) . '/pages/' . $delPage['slug'] . '.php';
        if (file_exists($delFile)) @unlink($delFile);
        dbExec($pdo, "DELETE FROM menu_items WHERE url=?", ['/crcap/pages/' . $delPage['slug'] . '.php']);
    }
    dbExec($pdo, "DELETE FROM pages WHERE id=?", [$id]);
    header('Location: /crcap/admin/pages.php?deleted=1'); exit;
}
if ($action === 'delete-menu' && $id) {
    dbExec($pdo, "DELETE FROM menu_items WHERE id=?", [$id]);
    header('Location: ?action=menus'); exit;
}

// ── SAVE PAGE ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_page'])) {
    $rawContent = $_POST['content'] ?? '';
    $blocksJson = $_POST['page_blocks_json'] ?? '';

    // Processar blocos de PDF
    $pdfBlocksJson = trim($_POST['pdf_blocks'] ?? '[]');
    $pdfBlocks = json_decode($pdfBlocksJson, true) ?: [];
    $pdfHtml = '';
    foreach ($pdfBlocks as $blk) {
        if (empty($blk['url'])) continue;
        $url = htmlspecialchars($blk['url'], ENT_NOQUOTES);
        $nm  = htmlspecialchars($blk['name'] ?? basename($blk['url']), ENT_QUOTES);
        if (($blk['type'] ?? 'link') === 'viewer') {
            $sep = str_contains($url, '?') ? '&' : '?';
            $viewUrl = str_contains($url, 'view=1') ? $url : $url . $sep . 'view=1';
            $pdfHtml .= '<div style="margin:1.5rem 0;border-radius:12px;overflow:hidden;border:1px solid #e8edf5;">'
                . '<div style="display:flex;align-items:center;gap:10px;padding:10px 16px;background:#001644;color:#fff;font-size:12px;font-weight:600;">'
                . '<span>&#128196; ' . $nm . '</span>'
                . '<a href="' . $url . '" target="_blank" style="margin-left:auto;font-size:10px;color:#BF8D1A;text-decoration:none;">&#11015; Baixar</a></div>'
                . '<iframe src="' . $viewUrl . '" width="100%" height="520" style="display:block;border:none;" loading="lazy" allowfullscreen></iframe></div>';
        } else {
            $pdfHtml .= '<p style="margin:1.2rem 0;">'
                . '<a href="' . $url . '" target="_blank" rel="noopener" '
                . 'style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:#001644;color:#fff;border-radius:50px;text-decoration:none;font-size:13px;font-weight:700;">'
                . '&#128196; ' . $nm . '</a></p>';
        }
    }

    // Botão painel
    $btnHtml  = trim($_POST['btn_panel_html'] ?? '');
    $btnAlign = trim($_POST['btn_panel_align'] ?? 'left');
    $body = $pdfHtml . $rawContent;
    if ($btnHtml) {
        $body = '<!--BTNPANEL:' . base64_encode($btnHtml . '||ALIGN:' . $btnAlign) . '-->' . $body;
    }

    $d = [
        'title'           => trim($_POST['title'] ?? ''),
        'slug'            => trim($_POST['slug'] ?? ''),
        'content'         => $body,
        'content_blocks'  => $blocksJson ?: null,
        'menu_section'    => trim($_POST['menu_section'] ?? ''),
        'status'          => $_POST['status'] ?? 'published',
        'visibility'      => $_POST['visibility'] ?? 'public',
        'show_in_menu'    => isset($_POST['show_in_menu']) ? 1 : 0,
        'seo_title'       => trim($_POST['seo_title'] ?? ''),
        'seo_description' => trim($_POST['seo_description'] ?? ''),
        'seo_keywords'    => trim($_POST['seo_keywords'] ?? ''),
        'author_id'       => $_SESSION['user_id'] ?? 1,
    ];
    if (!$d['slug'])
        $d['slug'] = strtolower(preg_replace('/[^a-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $d['title'])));

    // Check if content_blocks column exists
    try {
        $colCheck = $pdo->query("SHOW COLUMNS FROM pages LIKE 'content_blocks'")->fetch();
        if (!$colCheck) {
            $pdo->exec("ALTER TABLE pages ADD COLUMN content_blocks LONGTEXT NULL AFTER content");
        }
    } catch (Exception $e) {}

    try {
        if ($id) {
            $sets = implode(',', array_map(fn($k) => "`$k`=?", array_keys($d)));
            dbExec($pdo, "UPDATE pages SET $sets WHERE id=?", [...array_values($d), $id]);
            $msg = 'updated';
        } else {
            $ex = dbFetch($pdo, "SELECT id FROM pages WHERE slug=?", [$d['slug']]);
            if ($ex) {
                $id = (int)$ex['id'];
                $sets = implode(',', array_map(fn($k) => "`$k`=?", array_keys($d)));
                dbExec($pdo, "UPDATE pages SET $sets WHERE id=?", [...array_values($d), $id]);
                $msg = 'updated';
            } else {
                $keys = implode(',', array_map(fn($k) => "`$k`", array_keys($d)));
                $phs  = implode(',', array_fill(0, count($d), '?'));
                dbExec($pdo, "INSERT INTO pages ($keys) VALUES ($phs)", array_values($d));
                $id = (int)$pdo->lastInsertId();
                $msg = 'created';
            }
        }
        // ── Gera arquivo PHP + sync menu para páginas customizadas ──
        if (!isset($slugMap[$d['slug']]) && $d['slug']) {
            generatePageFile($d['slug'], $d['title'], $id);
            $pagePhpUrl = '/crcap/pages/' . $d['slug'] . '.php';
            if ($d['show_in_menu']) {
                $exMenu = dbFetch($pdo, "SELECT id FROM menu_items WHERE url=?", [$pagePhpUrl]);
                if ($exMenu) {
                    dbExec($pdo, "UPDATE menu_items SET title=?,status='active' WHERE id=?", [$d['title'], $exMenu['id']]);
                } else {
                    $mo = dbFetch($pdo, "SELECT MAX(order_position) as mo FROM menu_items WHERE menu_location='main'");
                    dbExec($pdo, "INSERT INTO menu_items (menu_location,title,url,status,order_position) VALUES ('main',?,?,'active',?)",
                        [$d['title'], $pagePhpUrl, (int)($mo['mo']??0)+1]);
                }
            } else {
                dbExec($pdo, "UPDATE menu_items SET status='inactive' WHERE url=?", [$pagePhpUrl]);
            }
        }

        $bs = trim($_POST['back_slug'] ?? '');
        if ($bs) { $action = 'edit-slug'; $slug = $bs; } else $action = 'edit';
    } catch (Exception $e) { $msg = 'error:' . $e->getMessage(); }
}

// ── SAVE MENU ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_menu'])) {
    foreach (($_POST['mi'] ?? []) as $mid => $data)
        dbExec($pdo, "UPDATE menu_items SET title=?,url=? WHERE id=?", [trim($data['title']), trim($data['url']), (int)$mid]);
    foreach (($_POST['new_title'] ?? []) as $k => $nt) {
        $nt = trim($nt); $nu = trim($_POST['new_url'][$k] ?? ''); $nl = trim($_POST['new_loc'][$k] ?? 'main');
        if ($nt && $nu) dbExec($pdo, "INSERT INTO menu_items (menu_location,title,url,status) VALUES(?,?,?,'active')", [$nl, $nt, $nu]);
    }
    header('Location: ?action=menus&saved=1'); exit;
}

// ══════════════════════════════════════════════════════════════════
// ROUTER
// ══════════════════════════════════════════════════════════════════
require_once __DIR__ . '/admin_header.php';

if (in_array($action, ['new', 'edit', 'edit-slug'])):
    if ($action === 'edit' && $id)
        $page = dbFetch($pdo, "SELECT * FROM pages WHERE id=?", [$id]);
    elseif ($action === 'edit-slug' && $slug) {
        $page = dbFetch($pdo, "SELECT * FROM pages WHERE slug=?", [$slug]);
        if ($page) $id = (int)$page['id'];
    } else $page = [];

    $phpMeta  = $slug ? ($slugMap[$slug] ?? null) : null;
    if (!$page && $phpMeta)
        $page = ['title' => $phpMeta['title'], 'slug' => $phpMeta['slug'],
                 'menu_section' => $phpMeta['section'], 'status' => 'published',
                 'visibility' => 'public', 'show_in_menu' => 1, 'content' => ''];

    $fromFile = ($action === 'edit-slug');
    $pageUrl  = $phpMeta['url'] ?? ($id ? '/crcap/pages/' . ($page['slug'] ?? '') : '');
    $pdfMode  = $phpMeta['pdf'] ?? false;
    $pageSlug = $page['slug'] ?? $slug ?? '';

    // Determine page view URL for custom pages
    // Páginas customizadas apontam para o arquivo .php gerado
    if (!$phpMeta && $id && !empty($page['slug'])) {
        $pageUrl = '/crcap/pages/' . $page['slug'] . '.php';
    }

    // Restore blocks
    $savedBlocksJson = $page['content_blocks'] ?? '';
?>
<style>
#ew{display:flex;gap:0;height:calc(100vh - 7rem);overflow:hidden}
#em{flex:1;overflow-y:auto;padding:1.5rem 1.5rem 6rem;min-width:0}
#es{width:280px;flex-shrink:0;overflow-y:auto;border-left:1px solid rgba(0,22,68,.06);background:#fafbfd;padding:1.25rem;display:flex;flex-direction:column;gap:1rem}
#etb{display:flex;align-items:center;gap:.6rem;padding:.75rem 1.25rem;background:white;border-bottom:1px solid rgba(0,22,68,.06);flex-wrap:wrap}
.tb{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .75rem;border-radius:.6rem;font-size:.7rem;font-weight:600;cursor:pointer;border:1px solid transparent;transition:all .15s;white-space:nowrap}
.tbg{color:#022E6B;border-color:rgba(0,22,68,.1);background:transparent}.tbg:hover{background:#F0F4F8;border-color:#BF8D1A}
.tbp{background:#001644;color:white}.tbp:hover{background:#022E6B;box-shadow:0 4px 12px rgba(0,22,68,.25)}
.tbgo{background:#BF8D1A;color:white}.tbgo:hover{background:#a87a17}
.tbgr{background:#006633;color:white}.tbgr:hover{background:#005528}
.tbdv{width:1px;height:1.4rem;background:rgba(0,22,68,.08);flex-shrink:0}
.sc{background:white;border-radius:1rem;border:1px solid rgba(0,22,68,.06);padding:1rem}
.sc h4{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(2,46,107,.4);margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem}
.si{width:100%;padding:.5rem .75rem;border:1px solid rgba(0,22,68,.1);border-radius:.65rem;font-size:.78rem;background:#F8FAFC;transition:all .15s;outline:none}
.si:focus{border-color:#BF8D1A;background:white;box-shadow:0 0 0 3px rgba(191,141,26,.1)}
.sl{width:100%;padding:.5rem .75rem;border:1px solid rgba(0,22,68,.1);border-radius:.65rem;font-size:.78rem;background:#F8FAFC;outline:none;cursor:pointer}
.sl2{font-size:.63rem;font-weight:700;color:#022E6B;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:.3rem}
.modal-hidden{display:none!important}
.modal-bd{position:fixed;inset:0;z-index:9998;background:rgba(0,10,30,.65);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:1rem}
.modal-box{background:white;border-radius:1.5rem;box-shadow:0 25px 60px rgba(0,22,68,.3);width:100%;overflow:hidden;animation:mIn .22s cubic-bezier(.34,1.56,.64,1)}
@keyframes mIn{from{transform:scale(.93) translateY(10px);opacity:0}to{transform:scale(1) translateY(0);opacity:1}}
.modal-hd{background:linear-gradient(135deg,#001644,#022E6B);padding:1.1rem 1.4rem;display:flex;align-items:center;justify-content:space-between}
.modal-hd h3{color:white;font-size:.875rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
.modal-cl{width:1.9rem;height:1.9rem;border-radius:.6rem;background:rgba(255,255,255,.1);border:none;color:rgba(255,255,255,.7);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.15s;flex-shrink:0}
.modal-cl:hover{background:rgba(255,255,255,.2);color:white}
.modal-body{padding:1.4rem}.modal-ft{padding:.9rem 1.4rem;background:#F8FAFC;border-top:1px solid rgba(0,22,68,.06);display:flex;gap:.65rem}
.sw{width:1.35rem;height:1.35rem;border-radius:.4rem;cursor:pointer;border:2px solid transparent;transition:.15s;flex-shrink:0}
.sw:hover{transform:scale(1.2)}.sw.on{border-color:#001644;box-shadow:0 0 0 2px white,0 0 0 3px #001644}
.mb-back{position:fixed;bottom:1.5rem;right:1.5rem;z-index:500;box-shadow:0 8px 30px rgba(0,22,68,.3);transform:translateY(80px);opacity:0;transition:all .3s cubic-bezier(.34,1.56,.64,1)}
.mb-back.show{transform:translateY(0);opacity:1}
.ah{display:flex;align-items:center;justify-content:space-between;padding:.7rem 1rem;background:#F8FAFC;border-radius:.875rem;cursor:pointer;font-size:.78rem;font-weight:600;color:#001644;transition:.15s;user-select:none}
.ah:hover{background:#F0F4F8}.ah.open .aa{transform:rotate(180deg)}
.aa{transition:transform .2s;font-size:.65rem;color:rgba(2,46,107,.4)}
.ab{display:none;padding:.75rem 1rem}.ab.open{display:block}
/* ── Block Editor ── */
#blockEditorPanel{border:1.5px solid rgba(0,22,68,.1);border-radius:1rem;overflow:hidden;margin-bottom:1rem}
#blockEditorPanel .bep-hd{background:linear-gradient(135deg,#001644,#022E6B);padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between}
.bep-hd span{color:white;font-size:.75rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
.bep-toggle{background:rgba(255,255,255,.15);border:none;color:white;font-size:.65rem;font-weight:700;padding:.3rem .7rem;border-radius:.5rem;cursor:pointer;transition:.15s}
.bep-toggle:hover{background:rgba(255,255,255,.25)}
#blockEditorInner{display:flex;min-height:300px}
#bepSidebar{width:160px;flex-shrink:0;background:#F8FAFC;border-right:1px solid rgba(0,22,68,.06);padding:1rem .75rem}
#bepSidebar p{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(2,46,107,.4);margin-bottom:.6rem}
.bep-add-btn{width:100%;display:flex;align-items:center;gap:.5rem;padding:.55rem .65rem;border-radius:.65rem;border:1.5px dashed rgba(0,22,68,.12);background:transparent;cursor:pointer;font-size:.7rem;font-weight:600;color:rgba(2,46,107,.55);transition:all .15s;margin-bottom:.35rem}
.bep-add-btn:hover{border-color:#BF8D1A;background:#FFFDF5;color:#001644}
.bep-add-btn i{color:#BF8D1A;width:.9rem;text-align:center}
#bepCanvas{flex:1;background:#E8ECF0;overflow-y:auto;padding:1rem}
#bepCanvas.empty-canvas{display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:.8rem;text-align:center}
.bep-block-wrap{position:relative;margin-bottom:.5rem;cursor:pointer}
.bep-block-wrap .bep-ctrl{display:none;position:absolute;top:4px;right:4px;z-index:10;gap:4px;flex-direction:row}
.bep-block-wrap:hover .bep-ctrl{display:flex}
.bep-block-wrap:hover{outline:2px solid #BF8D1A}
.bep-ctrl button{background:#001644;color:white;border:none;border-radius:5px;width:26px;height:26px;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center}
.bep-ctrl button.del{background:#ef4444}
.bep-ctrl button.edit-btn{color:#BF8D1A}
/* Block modal */
#bepModal{position:fixed;inset:0;z-index:9999;background:rgba(0,10,30,.65);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:1rem}
#bepModal.open{display:flex}
#bepModalBox{background:white;border-radius:1.25rem;width:100%;max-width:520px;max-height:85vh;overflow-y:auto;box-shadow:0 25px 50px rgba(0,22,68,.3)}
.form-label{display:block;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(2,46,107,.4);margin-bottom:.3rem}
.form-input{width:100%;padding:.5rem .75rem;border:1px solid rgba(0,22,68,.1);border-radius:.65rem;font-size:.78rem;background:#F8FAFC;outline:none;transition:all .15s}
.form-input:focus{border-color:#BF8D1A;box-shadow:0 0 0 3px rgba(191,141,26,.08)}
/* Mode tabs */
#editorModeTabs{display:flex;gap:.4rem;margin-bottom:.75rem}
.em-tab{flex:1;padding:.5rem;border-radius:.65rem;border:1.5px solid rgba(0,22,68,.08);background:white;cursor:pointer;font-size:.7rem;font-weight:600;color:rgba(2,46,107,.5);transition:all .15s;text-align:center;display:flex;align-items:center;justify-content:center;gap:.4rem}
.em-tab.active{background:#001644;color:white;border-color:#001644}
.em-tab:hover:not(.active){border-color:#BF8D1A;color:#001644}
/* Tag wrap */
.tag-wrap{display:flex;flex-wrap:wrap;gap:.3rem;min-height:2.25rem;padding:.35rem;border:1px solid rgba(0,22,68,.1);border-radius:.75rem;background:#F8FAFC;cursor:text}
.tag-wrap:focus-within{border-color:#BF8D1A;box-shadow:0 0 0 3px rgba(191,141,26,.1)}
.tp{display:inline-flex;align-items:center;gap:.25rem;background:#001644;color:white;padding:.15rem .5rem;border-radius:9999px;font-size:.62rem;font-weight:600}
.tp button{background:none;border:none;color:rgba(255,255,255,.6);cursor:pointer;font-size:.55rem;padding:0;line-height:1}
.ti{border:none;outline:none;background:transparent;font-size:.72rem;min-width:70px;flex:1;padding:.1rem .2rem}
[data-tip]{position:relative}
[data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 5px);left:50%;transform:translateX(-50%);background:#001644;color:white;font-size:.62rem;padding:.25rem .5rem;border-radius:.4rem;white-space:nowrap;pointer-events:none;z-index:9999}
.note-editor.note-frame{border:1.5px solid rgba(0,22,68,.1)!important;border-radius:1rem!important;overflow:hidden}
.note-toolbar{background:#F8FAFC!important;border-bottom:1px solid rgba(0,22,68,.08)!important}
.note-editable{padding:1.5rem!important;font-size:.9rem!important;line-height:1.75!important}
.note-placeholder{padding:1.5rem!important;color:rgba(2,46,107,.3)!important}
</style>

<div id="etb">
    <a href="/crcap/admin/pages.php" class="tb tbg"><i class="fas fa-arrow-left text-[9px]"></i> Páginas</a>
    <div class="tbdv"></div>
    <?php if ($action !== 'new'): ?>
    <span class="flex items-center gap-1.5 text-xs font-bold text-[#001644]">
        <span class="w-2 h-2 rounded-full <?= ($page['status'] ?? 'draft') === 'published' ? 'bg-[#006633]' : 'bg-amber-400' ?>"></span>
        <?= htmlspecialchars($page['title'] ?? 'Nova Página') ?>
    </span>
    <div class="tbdv"></div>
    <?php endif; ?>
    <!-- Modo editor -->
    <button type="button" onclick="setEditorMode('blocks')" id="tabBlocks" class="tb tbg" data-tip="Editor visual em blocos"><i class="fas fa-th-large text-violet-500"></i> Blocos</button>
    <button type="button" onclick="setEditorMode('rich')" id="tabRich" class="tb tbg" data-tip="Editor de texto rico"><i class="fas fa-align-left text-blue-500"></i> Editor</button>
    <div class="tbdv"></div>
    <button type="button" onclick="_btnTarget='editor';openModal('mBtn')" class="tb tbg" data-tip="Botão estilizado"><i class="fas fa-square-plus text-[#BF8D1A]"></i> Botão</button>
    <button type="button" onclick="openModal('mTbl')" class="tb tbg" data-tip="Tabela"><i class="fas fa-table text-blue-500"></i> Tabela</button>
    <button type="button" onclick="openModal('mAlr')" class="tb tbg" data-tip="Alerta"><i class="fas fa-exclamation-circle text-amber-500"></i> Alerta</button>
    <button type="button" onclick="openModal('mCol')" class="tb tbg" data-tip="Colunas"><i class="fas fa-columns text-violet-500"></i> Colunas</button>
    <button type="button" onclick="openModal('mDiv')" class="tb tbg" data-tip="Divisor"><i class="fas fa-minus text-[#022E6B]/30"></i> Divider</button>
    <div class="tbdv"></div>
    <button type="button" onclick="toggleHtml()" id="htmlBtn" class="tb tbg" data-tip="HTML bruto"><i class="fas fa-code"></i> HTML</button>
    <button type="button" onclick="prevPage()" class="tb tbg" data-tip="Pré-visualizar"><i class="fas fa-eye text-[#006633]"></i> Preview</button>
    <div class="ml-auto flex items-center gap-2">
        <?php if ($pageUrl): ?>
        <a href="<?= htmlspecialchars($pageUrl) ?>" target="_blank" class="tb tbgr"><i class="fas fa-external-link-alt text-[9px]"></i> Ver no site</a>
        <?php endif; ?>
        <button type="button" onclick="doSave()" class="tb tbp"><i class="fas fa-save text-[9px]"></i> Salvar</button>
    </div>
</div>

<?php if ($msg === 'created' || $msg === 'updated'): ?>
<div class="flex items-center gap-2 bg-[#006633]/10 border border-[#006633]/20 text-[#006633] text-xs rounded-xl px-4 py-2.5 mx-6 mt-3">
    <i class="fas fa-check-circle"></i> Página <?= $msg === 'created' ? 'criada' : 'atualizada' ?> com sucesso!
    <?php if ($pageUrl): ?><a href="<?= htmlspecialchars($pageUrl) ?>" target="_blank" class="ml-auto font-bold underline">Ver →</a><?php endif; ?>
</div>
<?php elseif (str_starts_with((string)$msg, 'error:')): ?>
<div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-2.5 mx-6 mt-3">
    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars(substr($msg, 6)) ?>
</div>
<?php endif; ?>

<form method="POST" id="mf">
    <input type="hidden" name="form_page" value="1">
    <input type="hidden" name="page_blocks_json" id="pageBlocksJsonHidden" value="">
    <?php if ($fromFile): ?><input type="hidden" name="back_slug" value="<?= htmlspecialchars($slug) ?>"><?php endif; ?>
    <div id="ew">
        <div id="em">
            <!-- TÍTULO -->
            <div class="mb-5">
                <input type="text" name="title" id="pgTitle"
                       value="<?= htmlspecialchars($page['title'] ?? '') ?>"
                       placeholder="Título da página..." required
                       oninput="autoSlug();dirty()"
                       class="w-full text-2xl font-bold text-[#001644] border-none outline-none bg-transparent placeholder-[#001644]/20 leading-tight">
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="text-[9px] text-[#022E6B]/35 font-mono">/pages/</span>
                    <input type="text" name="slug" id="slugInp"
                           value="<?= htmlspecialchars($page['slug'] ?? '') ?>"
                           class="text-[9px] font-mono text-[#022E6B]/55 bg-transparent border-b border-dashed border-[#022E6B]/15 outline-none hover:border-[#BF8D1A] focus:border-[#BF8D1A] px-1 py-0.5 w-72 <?= $fromFile ? 'opacity-50 cursor-not-allowed' : '' ?>"
                           <?= $fromFile ? 'readonly' : '' ?>>
                    <?php if ($fromFile): ?><span class="text-[8px] text-[#022E6B]/25"><i class="fas fa-lock mr-0.5"></i>Fixo</span><?php endif; ?>
                </div>
                <div class="h-px bg-gradient-to-r from-[#001644]/08 to-transparent mt-3"></div>
            </div>

            <!-- MODO DE EDIÇÃO TABS -->
            <div id="editorModeTabs">
                <button type="button" onclick="setEditorMode('blocks')" id="emtBlocks" class="em-tab active">
                    <i class="fas fa-th-large"></i> Blocos
                </button>
                <button type="button" onclick="setEditorMode('rich')" id="emtRich" class="em-tab">
                    <i class="fas fa-align-left"></i> Editor Rico
                </button>
            </div>

            <!-- ════ EDITOR EM BLOCOS ════ -->
            <div id="blockEditorPanel">
                <div class="bep-hd">
                    <span><i class="fas fa-th-large"></i> Editor em Blocos</span>
                    <div class="flex items-center gap-2">
                        <button type="button" id="bepHtmlToggle" onclick="bepToggleHtml()" class="bep-toggle"><i class="fas fa-code mr-1"></i>HTML</button>
                        <button type="button" onclick="bepTogglePreview()" id="bepPrevToggle" class="bep-toggle"><i class="fas fa-eye mr-1"></i>Preview</button>
                    </div>
                </div>
                <div id="blockEditorInner">
                    <!-- Sidebar: adicionar blocos -->
                    <div id="bepSidebar">
                        <p>Adicionar bloco</p>
                        <button type="button" onclick="bepAddBlock('heading')" class="bep-add-btn"><i class="fas fa-heading"></i>Título</button>
                        <button type="button" onclick="bepAddBlock('text')" class="bep-add-btn"><i class="fas fa-align-left"></i>Texto</button>
                        <button type="button" onclick="bepAddBlock('image')" class="bep-add-btn"><i class="fas fa-image"></i>Imagem</button>
                        <button type="button" onclick="bepAddBlock('columns')" class="bep-add-btn"><i class="fas fa-columns"></i>Colunas</button>
                        <button type="button" onclick="bepAddBlock('button')" class="bep-add-btn"><i class="fas fa-hand-pointer"></i>Botão</button>
                        <button type="button" onclick="bepAddBlock('alert')" class="bep-add-btn"><i class="fas fa-exclamation-circle"></i>Alerta</button>
                        <button type="button" onclick="bepAddBlock('divider')" class="bep-add-btn"><i class="fas fa-minus"></i>Divisor</button>
                        <button type="button" onclick="bepAddBlock('spacer')" class="bep-add-btn"><i class="fas fa-arrows-alt-v"></i>Espaço</button>
                        <button type="button" onclick="bepAddBlock('html')" class="bep-add-btn"><i class="fas fa-code"></i>HTML Livre</button>
                    </div>
                    <!-- Canvas -->
                    <div id="bepCanvas" class="empty-canvas">
                        <div id="bepEmpty"><i class="fas fa-cubes" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.25"></i>Adicione blocos para compor a página</div>
                    </div>
                </div>
                <!-- HTML raw panel -->
                <div id="bepHtmlPanel" style="display:none;background:#1e1e2e;padding:1rem">
                    <textarea id="bepHtmlRaw" style="width:100%;height:300px;background:transparent;color:#86efac;font-family:monospace;font-size:.78rem;resize:y;border:none;outline:none" spellcheck="false"></textarea>
                </div>
                <!-- Preview panel -->
                <div id="bepPreviewPanel" style="display:none;background:#f1f5f9;padding:1rem">
                    <iframe id="bepPreviewFrame" style="width:100%;min-height:400px;border:none;border-radius:.75rem;background:white;box-shadow:0 4px 20px rgba(0,22,68,.1)"></iframe>
                </div>
            </div>

            <!-- ════ EDITOR RICO (Summernote) — modo alternativo ════ -->
            <div id="richEditorPanel" style="display:none">
                <!-- PAINEL BOTÕES EXTERNOS -->
                <div id="btnPanelArea" style="background:white;border:1.5px dashed rgba(0,22,68,.1);border-radius:1rem;padding:1rem;margin-bottom:.75rem;transition:all .2s">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
                        <span style="font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(2,46,107,.4);display:flex;align-items:center;gap:.4rem">
                            <i class="fas fa-square-plus" style="color:#BF8D1A"></i> Botões de ação
                        </span>
                        <div style="display:flex;align-items:center;gap:.5rem">
                            <div style="display:flex;border:1px solid rgba(0,22,68,.1);border-radius:.5rem;overflow:hidden">
                                <button type="button" id="paL" onclick="setPanelAlign('left')"  style="padding:.3rem .6rem;font-size:.6rem;font-weight:700;border:none;cursor:pointer;background:#001644;color:white;transition:.15s"><i class="fas fa-align-left"></i></button>
                                <button type="button" id="paC" onclick="setPanelAlign('center')" style="padding:.3rem .6rem;font-size:.6rem;font-weight:700;border:none;border-left:1px solid rgba(0,22,68,.1);border-right:1px solid rgba(0,22,68,.1);cursor:pointer;background:white;color:#022E6B;transition:.15s"><i class="fas fa-align-center"></i></button>
                                <button type="button" id="paR" onclick="setPanelAlign('right')"  style="padding:.3rem .6rem;font-size:.6rem;font-weight:700;border:none;cursor:pointer;background:white;color:#022E6B;transition:.15s"><i class="fas fa-align-right"></i></button>
                            </div>
                            <button type="button" onclick="openPanelBtnModal()" style="display:flex;align-items:center;gap:.3rem;padding:.35rem .7rem;font-size:.7rem;font-weight:700;color:#001644;border:1px solid rgba(0,22,68,.1);border-radius:.6rem;background:white;cursor:pointer">
                                <i class="fas fa-plus" style="color:#BF8D1A;font-size:.6rem"></i> Adicionar botão
                            </button>
                            <button type="button" id="btnPanelClearBtn" onclick="clearBtnPanel()" style="display:none;padding:.35rem .6rem;font-size:.6rem;font-weight:700;color:#dc2626;border:1px solid #fca5a5;border-radius:.6rem;background:white;cursor:pointer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div id="btnPanelDisplay" style="min-height:2.25rem;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;justify-content:flex-start">
                        <span id="btnPanelHint" style="font-size:.7rem;color:rgba(2,46,107,.2);font-style:italic">Nenhum botão — clique em "Adicionar botão"</span>
                    </div>
                </div>
                <input type="hidden" name="btn_panel_html" id="btnPanelHtml" value="">
                <input type="hidden" name="btn_panel_align" id="btnPanelAlign" value="left">
                <input type="hidden" name="pdf_blocks" id="pdfBlocksData" value="[]">
                <div id="pdfBlocksArea"></div>
                <textarea id="sn" name="content" style="display:block"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                <textarea id="htmlArea" class="hidden w-full mt-2 p-4 font-mono text-xs border border-[#BF8D1A]/35 rounded-xl outline-none resize-y bg-[#FFFDF5]" rows="22" oninput="dirty()"></textarea>
            </div>

            <!-- SEO ACCORDION -->
            <div class="mt-5 border border-[#001644]/05 rounded-xl overflow-hidden">
                <div class="ah" onclick="toggleAcc(this)">
                    <span class="flex items-center gap-2"><i class="fas fa-search text-[#BF8D1A] text-xs"></i> SEO & Meta Tags</span>
                    <i class="fas fa-chevron-down aa"></i>
                </div>
                <div class="ab">
                    <div class="space-y-3">
                        <div>
                            <label class="sl2">Meta Título</label>
                            <input type="text" name="seo_title" id="seoT" value="<?= htmlspecialchars($page['seo_title'] ?? '') ?>" oninput="seoBar(this,'sTB',60)" placeholder="Vazio = usa o título da página" class="si w-full">
                            <div class="flex items-center gap-2 mt-1"><div class="flex-1 h-0.5 bg-[#001644]/05 rounded overflow-hidden"><div id="sTB" class="h-full bg-[#006633] transition-all" style="width:0%"></div></div><span id="sTBn" class="text-[9px] text-[#022E6B]/35">0/60</span></div>
                        </div>
                        <div>
                            <label class="sl2">Meta Descrição</label>
                            <textarea name="seo_description" id="seoD" oninput="seoBar(this,'sDB',160)" rows="2" class="si w-full resize-none" placeholder="120–160 caracteres"><?= htmlspecialchars($page['seo_description'] ?? '') ?></textarea>
                            <div class="flex items-center gap-2 mt-1"><div class="flex-1 h-0.5 bg-[#001644]/05 rounded overflow-hidden"><div id="sDB" class="h-full bg-[#006633] transition-all" style="width:0%"></div></div><span id="sDBn" class="text-[9px] text-[#022E6B]/35">0/160</span></div>
                        </div>
                        <div>
                            <label class="sl2">Palavras-chave</label>
                            <div class="tag-wrap" id="kwW" onclick="document.getElementById('kwI').focus()"></div>
                            <input type="hidden" name="seo_keywords" id="kwH" value="<?= htmlspecialchars($page['seo_keywords'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- PDF SECTION (only for system pages with pdf mode) -->
            <?php if ($pdfMode === 'atas'): ?>
            <div class="flex gap-2 p-3.5 bg-amber-50 border border-amber-200 rounded-xl mt-5 text-xs">
                <i class="fas fa-file-signature text-amber-500 mt-0.5"></i>
                <div><p class="font-bold text-amber-800">PDFs gerenciados em Documentos</p>
                <a href="/crcap/admin/documents.php?category=atas" class="text-[10px] text-amber-700 underline">Ir para Documentos →</a></div>
            </div>
            <?php elseif ($pdfMode !== false): ?>
            <div class="mt-5 border border-[#001644]/05 rounded-xl overflow-hidden">
                <div class="ah" onclick="toggleAcc(this)">
                    <span class="flex items-center gap-2"><i class="fas fa-file-pdf text-red-500 text-xs"></i> Arquivos PDF <span id="pdfCnt" class="text-[9px] bg-[#001644]/05 text-[#022E6B] px-1.5 py-0.5 rounded-full">0</span></span>
                    <i class="fas fa-chevron-down aa"></i>
                </div>
                <div class="ab">
                    <input type="file" id="pdfInp" accept=".pdf" multiple class="hidden">
                    <div class="relative mb-2">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[9px] text-[#022E6B]/25 pointer-events-none"></i>
                        <input type="text" id="pdfSrch" autocomplete="off" oninput="searchPdfs(this.value)" placeholder="Buscar PDF no servidor..." class="w-full pl-8 pr-3 py-2 text-xs border border-[#001644]/08 rounded-xl bg-[#F8FAFC] focus:outline-none focus:border-[#BF8D1A]">
                        <div id="pdfRes" class="hidden absolute z-30 w-full mt-1 bg-white border border-[#001644]/08 rounded-xl shadow-xl max-h-44 overflow-y-auto"></div>
                    </div>
                    <div id="pdfDz" onclick="document.getElementById('pdfInp').click()" class="border-2 border-dashed border-[#001644]/10 rounded-xl p-4 text-center mb-2 cursor-pointer hover:border-[#BF8D1A] hover:bg-amber-50/30 transition">
                        <i class="fas fa-cloud-upload-alt text-xl text-[#001644]/12 block mb-1"></i>
                        <p class="text-xs text-[#022E6B]/35">Arraste ou clique para enviar PDFs</p>
                    </div>
                    <div id="pdfProg" class="hidden mb-2 bg-[#F8FAFC] rounded-xl p-2.5">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-1.5 text-xs"><div class="w-3 h-3 border-2 border-[#001644] border-t-transparent rounded-full animate-spin"></div><span id="pdfPT">Enviando...</span></div>
                            <span id="pdfPP" class="text-xs font-bold text-[#BF8D1A]">0%</span>
                        </div>
                        <div class="w-full bg-[#001644]/06 rounded-full h-1"><div id="pdfPB" class="bg-[#BF8D1A] h-1 rounded-full transition-all" style="width:0%"></div></div>
                    </div>
                    <div id="pdfMsg2" class="hidden mb-2 text-xs rounded-xl px-3 py-1.5"></div>
                    <div id="pdfList" class="space-y-1.5 max-h-72 overflow-y-auto">
                        <div id="pdfEmpty" class="text-center py-5 bg-[#F8FAFC] rounded-xl"><i class="fas fa-inbox text-xl text-[#001644]/08 block mb-1"></i><p class="text-[9px] text-[#022E6B]/25 italic">Nenhum PDF ainda.</p></div>
                    </div>
                    <div class="mt-2 pt-2 border-t border-[#001644]/04 flex gap-4 text-[9px]">
                        <span class="flex items-center gap-1 text-blue-500"><i class="fas fa-tv"></i><b>Viewer</b> visualiza inline</span>
                        <span class="flex items-center gap-1 text-[#BF8D1A]"><i class="fas fa-link"></i><b>Link</b> botão download</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- /em -->

        <!-- SIDEBAR -->
        <div id="es">
            <div class="sc">
                <h4><i class="fas fa-rocket text-[#BF8D1A]"></i> Publicação</h4>
                <div class="space-y-2.5">
                    <div><label class="sl2">Status</label>
                    <select name="status" class="sl"><option value="published" <?= ($page['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>✅ Publicada</option><option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>📝 Rascunho</option><option value="private" <?= ($page['status'] ?? '') === 'private' ? 'selected' : '' ?>>🔒 Privada</option></select></div>
                    <div><label class="sl2">Visibilidade</label>
                    <select name="visibility" class="sl"><option value="public" <?= ($page['visibility'] ?? 'public') === 'public' ? 'selected' : '' ?>>🌐 Pública</option><option value="members_only" <?= ($page['visibility'] ?? '') === 'members_only' ? 'selected' : '' ?>>👤 Apenas membros</option><option value="private" <?= ($page['visibility'] ?? '') === 'private' ? 'selected' : '' ?>>🔒 Privada</option></select></div>
                    <div><label class="sl2">Seção do menu</label>
                    <input type="text" name="menu_section" value="<?= htmlspecialchars($page['menu_section'] ?? '') ?>" placeholder="crcap, governanca..." class="si"></div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <div class="relative w-8 h-[18px]">
                            <input type="checkbox" name="show_in_menu" class="sr-only peer" <?= ($page['show_in_menu'] ?? 1) ? 'checked' : '' ?>>
                            <div class="w-full h-full bg-[#001644]/10 rounded-full peer-checked:bg-[#006633] transition"></div>
                            <div class="absolute top-[2px] left-[2px] w-[14px] h-[14px] bg-white rounded-full shadow transition peer-checked:translate-x-[14px]"></div>
                        </div>
                        <span class="text-xs font-semibold text-[#001644]">Exibir no menu</span>
                    </label>
                </div>
            </div>
            <div class="space-y-1.5">
                <button type="button" onclick="doSave()" class="w-full tb tbp justify-center py-2.5 text-sm"><i class="fas fa-save"></i> Salvar página</button>
                <?php if ($pageUrl): ?><a href="<?= htmlspecialchars($pageUrl) ?>" target="_blank" class="w-full tb tbgr justify-center py-2.5 block text-center text-sm"><i class="fas fa-eye text-[9px]"></i> Ver no site</a><?php endif; ?>
                <a href="/crcap/admin/pages.php" class="w-full flex items-center justify-center py-2 text-xs font-semibold text-[#001644] border border-[#001644]/10 rounded-xl hover:bg-[#F0F4F8] transition">Cancelar</a>
                <?php if ($id): ?>
                <a href="?action=delete&id=<?= $id ?>" onclick="return confirm('Excluir esta página?')" class="w-full flex items-center justify-center gap-1.5 py-2 text-xs font-semibold text-red-500 border border-red-200 rounded-xl hover:bg-red-50 transition"><i class="fas fa-trash text-[9px]"></i> Excluir do banco</a>
                <?php endif; ?>
            </div>
            <?php if ($phpMeta): ?>
            <div class="sc"><h4><i class="fas fa-file-code text-violet-500"></i> Arquivo PHP</h4>
            <p class="text-[9px] font-mono text-[#022E6B]/50 break-all mb-2"><?= htmlspecialchars($phpMeta['file']) ?></p>
            <a href="<?= htmlspecialchars($phpMeta['url']) ?>" target="_blank" class="text-[9px] text-[#006633] hover:underline flex items-center gap-1"><i class="fas fa-external-link-alt"></i> Abrir no site</a></div>
            <?php endif; ?>
            <?php if (!empty($page['views']) && $page['views'] > 0): ?>
            <div class="sc"><h4><i class="fas fa-chart-bar text-sky-500"></i> Stats</h4>
            <div class="text-center"><p class="text-2xl font-bold text-[#001644]"><?= number_format($page['views']) ?></p><p class="text-[9px] text-[#022E6B]/35">visualizações</p></div></div>
            <?php endif; ?>
        </div>
    </div>
    <button id="fsBtn" type="button" onclick="doSave()" class="mb-back tb tbp py-3 px-5"><i class="fas fa-save"></i> Salvar alterações</button>
</form>

<!-- ══ MODAIS (Tabela, Alerta, Colunas, Divisor, Botão) ══ -->
<div id="mBtn" class="modal-bd modal-hidden">
<div class="modal-box max-w-2xl">
    <div class="modal-hd"><div><h3><i class="fas fa-magic text-[#BF8D1A]"></i> Criador de Botões</h3></div><button class="modal-cl" onclick="closeM('mBtn')"><i class="fas fa-times text-xs"></i></button></div>
    <div class="modal-body space-y-3">
        <div><p class="text-[8px] font-bold text-[#001644]/35 uppercase tracking-widest mb-1.5">Preview</p>
        <div id="bPrev" class="min-h-10 bg-[#F8FAFC] rounded-xl px-4 py-3 flex flex-wrap gap-2 items-center border border-[#001644]/05"><span id="bPE" class="text-[9px] text-[#022E6B]/25 italic">Configure abaixo...</span></div></div>
        <div id="bList" class="space-y-1.5 max-h-32 overflow-y-auto"></div>
        <div class="bg-[#F8FAFC] rounded-xl p-3.5 border border-[#001644]/05 space-y-3">
            <div class="grid grid-cols-2 gap-2.5">
                <div><label class="sl2">Texto *</label><input type="text" id="bLbl" placeholder="Ex: Acessar Portal" class="si" oninput="updBP()"></div>
                <div><label class="sl2">URL *</label><input type="text" id="bUrl" placeholder="https:// ou /crcap/..." class="si"></div>
            </div>
            <div class="grid grid-cols-3 gap-2.5">
                <div><label class="sl2">Estilo</label><select id="bSty" class="sl" onchange="updBP()"><option value="filled">Preenchido</option><option value="pill">Pílula</option><option value="outline">Contorno</option><option value="ghost">Ghost</option><option value="3d">3D</option><option value="gradient">Gradiente</option></select></div>
                <div><label class="sl2">Tamanho</label><select id="bSz" class="sl" onchange="updBP()"><option value="sm">Pequeno</option><option value="md" selected>Médio</option><option value="lg">Grande</option></select></div>
                <div><label class="sl2">Abrir em</label><select id="bTgt" class="sl"><option value="_blank">Nova aba</option><option value="_self">Mesma aba</option></select></div>
            </div>
            <div><label class="sl2">Cor</label>
            <div class="flex items-center gap-1.5 flex-wrap">
                <input type="color" id="bClr" value="#001644" oninput="updBP()" class="w-8 h-8 rounded-lg border border-[#001644]/08 cursor-pointer p-0.5">
                <?php foreach (['#001644','#BF8D1A','#006633','#dc2626','#7c3aed','#0284c7','#ea580c','#0f172a'] as $c): ?>
                <button type="button" onclick="setBC('<?= $c ?>')" class="sw" style="background:<?= $c ?>"></button>
                <?php endforeach; ?>
            </div></div>
            <div><label class="sl2">Ícone Font Awesome</label>
            <div class="flex items-center gap-2">
                <input type="text" id="bIco" placeholder="fa-download, fa-arrow-right..." class="si flex-1" oninput="updBP()">
                <div class="flex gap-1"><?php foreach (['fa-arrow-right'=>'→','fa-download'=>'↓','fa-external-link-alt'=>'↗','fa-file-pdf'=>'📄','fa-check'=>'✓','fa-phone'=>'📞'] as $ic => $lb): ?>
                <button type="button" onclick="document.getElementById('bIco').value='<?= $ic ?>';updBP()" class="px-2 py-1.5 text-[9px] bg-white border border-[#001644]/08 rounded-lg hover:border-[#BF8D1A] transition"><?= $lb ?></button>
                <?php endforeach; ?></div>
            </div></div>
            <div><label class="sl2">Alinhamento</label>
            <div class="flex gap-2">
                <label class="flex-1 flex flex-col items-center gap-1.5 p-2 border border-[#001644]/08 rounded-xl cursor-pointer hover:border-[#BF8D1A] has-[:checked]:border-[#BF8D1A] has-[:checked]:bg-amber-50 transition"><input type="radio" name="bAlign" value="left" checked class="sr-only" onchange="updBP()"><i class="fas fa-align-left text-sm text-[#001644]"></i><span class="text-[9px] font-bold text-[#001644]">Esquerda</span></label>
                <label class="flex-1 flex flex-col items-center gap-1.5 p-2 border border-[#001644]/08 rounded-xl cursor-pointer hover:border-[#BF8D1A] has-[:checked]:border-[#BF8D1A] has-[:checked]:bg-amber-50 transition"><input type="radio" name="bAlign" value="center" class="sr-only" onchange="updBP()"><i class="fas fa-align-center text-sm text-[#001644]"></i><span class="text-[9px] font-bold text-[#001644]">Centralizar</span></label>
                <label class="flex-1 flex flex-col items-center gap-1.5 p-2 border border-[#001644]/08 rounded-xl cursor-pointer hover:border-[#BF8D1A] has-[:checked]:border-[#BF8D1A] has-[:checked]:bg-amber-50 transition"><input type="radio" name="bAlign" value="right" class="sr-only" onchange="updBP()"><i class="fas fa-align-right text-sm text-[#001644]"></i><span class="text-[9px] font-bold text-[#001644]">Direita</span></label>
            </div></div>
            <button type="button" onclick="addBtn()" class="w-full py-2 text-xs font-bold bg-[#001644] text-white rounded-xl hover:bg-[#022E6B] transition flex items-center justify-center gap-1.5"><i class="fas fa-plus-circle text-[9px]"></i> Adicionar</button>
        </div>
    </div>
    <div class="modal-ft">
        <button type="button" onclick="closeM('mBtn')" class="flex-1 py-2 text-xs font-semibold text-[#001644] border border-[#001644]/10 rounded-xl hover:bg-[#F0F4F8] transition">Cancelar</button>
        <button type="button" onclick="insertBtns()" id="btnInsertLabel" class="flex-[2] py-2.5 text-sm font-bold bg-[#BF8D1A] text-white rounded-xl hover:bg-[#a87a17] transition flex items-center justify-center gap-2"><i class="fas fa-check-circle"></i><span id="btnInsertLbl">Inserir no editor</span></button>
    </div>
</div></div>

<div id="mTbl" class="modal-bd modal-hidden">
<div class="modal-box max-w-lg">
    <div class="modal-hd"><div><h3><i class="fas fa-table text-blue-400"></i> Inserir Tabela</h3></div><button class="modal-cl" onclick="closeM('mTbl')"><i class="fas fa-times text-xs"></i></button></div>
    <div class="modal-body space-y-4">
        <div class="grid grid-cols-3 gap-3">
            <div><label class="sl2">Linhas</label><input type="number" id="tR" value="3" min="1" max="20" class="si text-center"></div>
            <div><label class="sl2">Colunas</label><input type="number" id="tC" value="3" min="1" max="10" class="si text-center"></div>
            <div><label class="sl2">Cabeçalho</label><select id="tH" class="sl"><option value="1">Com cabeçalho</option><option value="0">Sem</option></select></div>
        </div>
        <div><label class="sl2">Estilo</label>
        <div class="grid grid-cols-2 gap-2"><?php foreach (['stripe' => 'Listrada', 'bordered' => 'Com bordas', 'minimal' => 'Minimalista', 'crcap' => 'CRCAP Azul'] as $v => $l): ?>
            <label class="flex items-center gap-2 p-2.5 border border-[#001644]/08 rounded-xl cursor-pointer hover:border-[#BF8D1A] transition has-[:checked]:border-[#BF8D1A] has-[:checked]:bg-amber-50"><input type="radio" name="tS" value="<?= $v ?>" <?= $v === 'stripe' ? 'checked' : '' ?> class="accent-[#BF8D1A]"><span class="text-xs font-semibold text-[#001644]"><?= $l ?></span></label>
        <?php endforeach; ?></div></div>
    </div>
    <div class="modal-ft">
        <button type="button" onclick="closeM('mTbl')" class="flex-1 py-2 text-xs font-semibold text-[#001644] border border-[#001644]/10 rounded-xl">Cancelar</button>
        <button type="button" onclick="insertTbl()" class="flex-[2] py-2.5 text-sm font-bold bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition flex items-center justify-center gap-2"><i class="fas fa-table"></i> Inserir tabela</button>
    </div>
</div></div>

<div id="mAlr" class="modal-bd modal-hidden">
<div class="modal-box max-w-lg">
    <div class="modal-hd"><div><h3><i class="fas fa-exclamation-circle text-amber-400"></i> Inserir Alerta</h3></div><button class="modal-cl" onclick="closeM('mAlr')"><i class="fas fa-times text-xs"></i></button></div>
    <div class="modal-body space-y-3">
        <div><label class="sl2">Tipo</label>
        <div class="grid grid-cols-3 gap-2"><?php foreach (['info' => ['ℹ️','#eff6ff','Informação'], 'success' => ['✅','#f0fdf4','Sucesso'], 'warning' => ['⚠️','#fffbeb','Aviso'], 'danger' => ['🚫','#fef2f2','Atenção'], 'tip' => ['💡','#f5f3ff','Dica'], 'crcap' => ['⭐','#fffdf5','CRCAP']] as $v => [$em, $bg, $l]): ?>
            <label class="flex items-center gap-2 p-2 border border-[#001644]/08 rounded-xl cursor-pointer hover:border-[#BF8D1A] has-[:checked]:border-[#BF8D1A] transition" style="background:<?= $bg ?>"><input type="radio" name="aT" value="<?= $v ?>" <?= $v === 'info' ? 'checked' : '' ?> class="sr-only"><span class="text-base"><?= $em ?></span><span class="text-xs font-semibold text-[#001644]"><?= $l ?></span></label>
        <?php endforeach; ?></div></div>
        <div><label class="sl2">Título (opcional)</label><input type="text" id="aT" placeholder="Ex: Importante!" class="si w-full"></div>
        <div><label class="sl2">Mensagem *</label><textarea id="aM" rows="3" class="si w-full resize-none" placeholder="Texto do alerta..."></textarea></div>
    </div>
    <div class="modal-ft">
        <button type="button" onclick="closeM('mAlr')" class="flex-1 py-2 text-xs font-semibold text-[#001644] border border-[#001644]/10 rounded-xl">Cancelar</button>
        <button type="button" onclick="insertAlr()" class="flex-[2] py-2.5 text-sm font-bold bg-amber-600 text-white rounded-xl hover:bg-amber-700 transition flex items-center justify-center gap-2"><i class="fas fa-check"></i> Inserir alerta</button>
    </div>
</div></div>

<div id="mCol" class="modal-bd modal-hidden">
<div class="modal-box max-w-md">
    <div class="modal-hd"><div><h3><i class="fas fa-columns text-violet-400"></i> Inserir Colunas</h3></div><button class="modal-cl" onclick="closeM('mCol')"><i class="fas fa-times text-xs"></i></button></div>
    <div class="modal-body space-y-3">
        <div><label class="sl2">Número de colunas</label>
        <div class="flex gap-2"><?php foreach ([2,3,4] as $n): ?>
            <label class="flex-1 flex flex-col items-center gap-1.5 p-3 border border-[#001644]/08 rounded-xl cursor-pointer hover:border-[#BF8D1A] has-[:checked]:border-[#BF8D1A] has-[:checked]:bg-amber-50 transition"><input type="radio" name="cN" value="<?= $n ?>" <?= $n===2?'checked':'' ?> class="sr-only"><span class="text-xl font-bold text-[#001644]"><?= $n ?></span><span class="text-[9px] text-[#022E6B]/40 font-medium"><?= $n === 2 ? '50/50' : ($n===3?'33/33/33':'25/25/25/25') ?></span></label>
        <?php endforeach; ?></div></div>
        <div><label class="sl2">Gap entre colunas (px)</label><input type="number" id="cG" value="24" min="8" max="48" class="si text-center"></div>
    </div>
    <div class="modal-ft">
        <button type="button" onclick="closeM('mCol')" class="flex-1 py-2 text-xs font-semibold text-[#001644] border border-[#001644]/10 rounded-xl">Cancelar</button>
        <button type="button" onclick="insertCol()" class="flex-[2] py-2.5 text-sm font-bold bg-violet-600 text-white rounded-xl hover:bg-violet-700 transition flex items-center justify-center gap-2"><i class="fas fa-columns"></i> Inserir colunas</button>
    </div>
</div></div>

<div id="mDiv" class="modal-bd modal-hidden">
<div class="modal-box max-w-sm">
    <div class="modal-hd"><div><h3><i class="fas fa-minus text-[#022E6B]/50"></i> Inserir Separador</h3></div><button class="modal-cl" onclick="closeM('mDiv')"><i class="fas fa-times text-xs"></i></button></div>
    <div class="modal-body space-y-2"><?php foreach (['solid'=>'Sólido','dashed'=>'Tracejado','dotted'=>'Pontilhado','gradient'=>'Gradiente CRCAP','ornament'=>'Ornamento ✦'] as $v=>$l): ?>
        <label class="flex items-center gap-3 p-3 border border-[#001644]/08 rounded-xl cursor-pointer hover:border-[#BF8D1A] has-[:checked]:border-[#BF8D1A] has-[:checked]:bg-amber-50 transition"><input type="radio" name="dS" value="<?= $v ?>" <?= $v==='solid'?'checked':'' ?> class="accent-[#BF8D1A]"><span class="text-xs font-semibold text-[#001644]"><?= $l ?></span></label>
    <?php endforeach; ?></div>
    <div class="modal-ft">
        <button type="button" onclick="closeM('mDiv')" class="flex-1 py-2 text-xs font-semibold text-[#001644] border border-[#001644]/10 rounded-xl">Cancelar</button>
        <button type="button" onclick="insertDiv()" class="flex-[2] py-2.5 text-sm font-bold bg-[#022E6B] text-white rounded-xl hover:bg-[#001644] transition flex items-center justify-center gap-2"><i class="fas fa-check"></i> Inserir separador</button>
    </div>
</div></div>

<!-- ══ MODAL EDITAR BLOCO ══ -->
<div id="bepModal">
<div id="bepModalBox">
    <div style="background:linear-gradient(135deg,#001644,#022E6B);padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;border-radius:1.25rem 1.25rem 0 0">
        <h4 id="bepModalTitle" style="color:white;font-size:.875rem;font-weight:700">Editar Bloco</h4>
        <button onclick="closeBepModal()" style="background:rgba(255,255,255,.1);border:none;color:rgba(255,255,255,.7);width:28px;height:28px;border-radius:8px;cursor:pointer;font-size:1rem">&times;</button>
    </div>
    <div id="bepModalBody" style="padding:1.25rem;display:flex;flex-direction:column;gap:.875rem"></div>
    <div style="padding:.875rem 1.25rem;background:#F8FAFC;border-top:1px solid rgba(0,22,68,.06);display:flex;gap:.5rem;border-radius:0 0 1.25rem 1.25rem">
        <button type="button" onclick="bepModalApply()" style="flex:2;padding:.65rem;font-size:.8rem;font-weight:700;background:#001644;color:white;border:none;border-radius:.75rem;cursor:pointer"><i class="fas fa-check mr-1"></i>Aplicar</button>
        <button type="button" onclick="closeBepModal()" style="flex:1;padding:.65rem;font-size:.75rem;font-weight:600;background:white;color:#001644;border:1px solid rgba(0,22,68,.12);border-radius:.75rem;cursor:pointer">Cancelar</button>
    </div>
</div>
</div>

<!-- ══════════════════════ SCRIPTS ══════════════════════ -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
<script>
// ══════════════════════════════════════════════════════════════
// EDITOR MODE: 'blocks' | 'rich'
// ══════════════════════════════════════════════════════════════
let _editorMode = 'blocks';

function setEditorMode(mode) {
    _editorMode = mode;
    document.getElementById('blockEditorPanel').style.display  = mode === 'blocks' ? '' : 'none';
    document.getElementById('richEditorPanel').style.display   = mode === 'rich'   ? '' : 'none';
    // tab styles
    document.getElementById('emtBlocks').className = 'em-tab' + (mode==='blocks'?' active':'');
    document.getElementById('emtRich').className   = 'em-tab' + (mode==='rich'?' active':'');
    document.getElementById('tabBlocks').className = 'tb tbg' + (mode==='blocks'?' tbgo':'');
    document.getElementById('tabRich').className   = 'tb tbg' + (mode==='rich'?' tbgo':'');
    if (mode === 'rich') initSummernote();
}

// ══════════════════════════════════════════════════════════════
// BLOCO DEFAULTS
// ══════════════════════════════════════════════════════════════
const BEP_DEFAULTS = {
    heading:  { level: 'h2', text: 'Título da seção', align: 'left', color: '#001644' },
    text:     { content: '<p>Escreva o conteúdo aqui.</p>', bgColor: '#ffffff' },
    image:    { src: '', alt: '', link: '', align: 'center', width: '100%', caption: '', bgColor: '#ffffff' },
    columns:  { count: 2, gap: 24, cols: [] },
    button:   { label: 'Clique aqui', url: '#', style: 'filled', size: 'md', color: '#001644', align: 'left', target: '_blank' },
    alert:    { type: 'info', title: '', message: 'Mensagem do alerta.' },
    divider:  { style: 'solid' },
    spacer:   { height: 32 },
    html:     { code: '<p>Código HTML livre aqui</p>' },
};

let _bepBlocks   = [];
let _bepEditIdx  = null;
let _bepHtmlMode = false;
let _bepPrevMode = false;

// ══════════════════════════════════════════════════════════════
// RENDER BLOCK → HTML for page
// ══════════════════════════════════════════════════════════════
function bepBlockToHtml(b) {
    const aligns = {left:'text-align:left',center:'text-align:center',right:'text-align:right'};
    switch (b.type) {
        case 'heading': {
            const lvl = b.level || 'h2';
            const sz  = {h1:'2rem',h2:'1.5rem',h3:'1.25rem',h4:'1.1rem'}[lvl] || '1.5rem';
            const fw  = {h1:'800',h2:'700',h3:'700',h4:'600'}[lvl] || '700';
            return `<${lvl} style="margin:1.5rem 0 .75rem;${aligns[b.align||'left']};color:${b.color||'#001644'};font-size:${sz};font-weight:${fw};font-family:system-ui">${b.text||''}</${lvl}>`;
        }
        case 'text':
            return `<div style="background:${b.bgColor||'#fff'};padding:1rem 0;font-family:system-ui;font-size:15px;line-height:1.75;color:#022E6B">${b.content||''}</div>`;
        case 'image': {
            if (!b.src) return `<div style="padding:2rem;text-align:center;background:#f8fafc;border-radius:12px;color:#94a3b8;font-size:13px;border:2px dashed #e2e8f0">🖼️ Imagem não definida</div>`;
            let img = `<img src="${b.src}" alt="${b.alt||''}" style="max-width:100%;width:${b.width||'100%'};display:block;border-radius:8px${b.align!=='left'?';margin:0 auto':''}" loading="lazy">`;
            if (b.link) img = `<a href="${b.link}" target="_blank">${img}</a>`;
            const cap = b.caption ? `<p style="text-align:center;font-size:12px;color:#64748b;margin:.5rem 0 0">${b.caption}</p>` : '';
            return `<div style="${aligns[b.align||'center']};background:${b.bgColor||'#fff'};padding:.5rem 0">${img}${cap}</div>`;
        }
        case 'columns': {
            const n   = parseInt(b.count) || 2;
            const g   = parseInt(b.gap)   || 24;
            const cols = b.cols || [];
            let cells = '';
            for (let i = 0; i < n; i++) {
                const c = cols[i] || {};
                cells += `<div style="flex:1;min-width:180px;padding:1rem;background:${c.bg||'#F8FAFC'};border-radius:10px;border:1px solid rgba(0,22,68,.06);box-sizing:border-box"><div style="font-family:system-ui;font-size:14px;line-height:1.7;color:#022E6B">${c.content||('<p style="color:#94a3b8;font-style:italic">Coluna '+(i+1)+'</p>')}</div></div>`;
            }
            return `<div style="display:flex;gap:${g}px;margin:1rem 0;flex-wrap:wrap">${cells}</div>`;
        }
        case 'button': {
            const css = bCss(b.label||'Botão', b.url||'#', b.color||'#001644', b.style||'filled', b.size||'md', b.target||'_blank', b.icon||'');
            return `<p style="margin:1.2rem 0;${aligns[b.align||'left']}">${css}</p>`;
        }
        case 'alert': {
            const aC = {info:{i:'ℹ️',bg:'#eff6ff',bd:'#bfdbfe',c:'#1d4ed8'},success:{i:'✅',bg:'#f0fdf4',bd:'#bbf7d0',c:'#15803d'},warning:{i:'⚠️',bg:'#fffbeb',bd:'#fde68a',c:'#b45309'},danger:{i:'🚫',bg:'#fef2f2',bd:'#fecaca',c:'#dc2626'},tip:{i:'💡',bg:'#f5f3ff',bd:'#ddd6fe',c:'#7c3aed'},crcap:{i:'⭐',bg:'#fffdf5',bd:'#fde68a',c:'#92400e'}};
            const cf = aC[b.type] || aC.info;
            const tl = b.title || '';
            return `<div style="display:flex;gap:12px;align-items:flex-start;padding:16px 20px;background:${cf.bg};border:1.5px solid ${cf.bd};border-radius:12px;margin:1rem 0;font-family:system-ui">`
                + `<span style="font-size:18px;flex-shrink:0;margin-top:1px">${cf.i}</span>`
                + `<div>${tl?`<strong style="display:block;color:${cf.c};font-size:13px;font-weight:700;margin-bottom:4px">${tl}</strong>`:''}`
                + `<span style="color:${cf.c};font-size:13px;opacity:.85">${b.message||''}</span></div></div>`;
        }
        case 'divider': {
            const dS = {solid:`<hr style="border:none;border-top:2px solid rgba(0,22,68,.1);margin:2rem 0">`,dashed:`<hr style="border:none;border-top:2px dashed rgba(0,22,68,.15);margin:2rem 0">`,dotted:`<hr style="border:none;border-top:2px dotted rgba(0,22,68,.15);margin:2rem 0">`,gradient:`<div style="height:1px;background:linear-gradient(90deg,transparent,rgba(0,22,68,.2) 20%,#BF8D1A 50%,rgba(0,22,68,.2) 80%,transparent);margin:2rem 0"></div>`,ornament:`<div style="text-align:center;margin:2rem 0;color:rgba(0,22,68,.2);font-size:1.25rem;letter-spacing:.5rem">✦ ✦ ✦</div>`};
            return dS[b.style||'solid'] || dS.solid;
        }
        case 'spacer':
            return `<div style="height:${b.height||32}px"></div>`;
        case 'html':
            return b.code || '';
        default:
            return '';
    }
}

function bepBuildHtml() {
    return _bepBlocks.map(bepBlockToHtml).join('\n');
}

// ══════════════════════════════════════════════════════════════
// RENDER CANVAS
// ══════════════════════════════════════════════════════════════
function bepRenderCanvas() {
    const canvas = document.getElementById('bepCanvas');
    if (!canvas) return;
    canvas.innerHTML = '';
    canvas.className = 'bep-block-wrap';
    if (_bepBlocks.length === 0) {
        canvas.className = 'empty-canvas';
        canvas.innerHTML = `<div id="bepEmpty" style="text-align:center;padding:3rem 2rem"><i class="fas fa-cubes" style="font-size:2.5rem;display:block;margin-bottom:1rem;color:#cbd5e1"></i><p style="font-size:.85rem;color:#94a3b8">Adicione blocos para compor a página</p></div>`;
        return;
    }
    canvas.className = 'bep-canvas-filled';
    canvas.style.cssText = 'flex:1;background:#E8ECF0;overflow-y:auto;padding:1rem';

    _bepBlocks.forEach((b, idx) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'bep-block-wrap';

        // Render block HTML into a preview container
        const preview = document.createElement('div');
        preview.style.cssText = 'background:white;border-radius:8px;padding:1rem 1.25rem;box-shadow:0 1px 4px rgba(0,22,68,.06)';
        preview.innerHTML = bepBlockToHtml(b);
        wrapper.appendChild(preview);

        // Controls
        const ctrl = document.createElement('div');
        ctrl.className = 'bep-ctrl';
        ctrl.innerHTML = `
            <button class="edit-btn" onclick="bepEditBlock(${idx})" title="Editar"><i class="fas fa-pen"></i></button>
            <button onclick="bepMoveBlock(${idx},-1)" ${idx===0?'disabled style="opacity:.3"':''} title="Subir"><i class="fas fa-chevron-up"></i></button>
            <button onclick="bepMoveBlock(${idx},1)" ${idx===_bepBlocks.length-1?'disabled style="opacity:.3"':''} title="Descer"><i class="fas fa-chevron-down"></i></button>
            <button class="del" onclick="bepRemoveBlock(${idx})" title="Remover"><i class="fas fa-trash"></i></button>`;
        wrapper.appendChild(ctrl);
        canvas.appendChild(wrapper);
    });

    // Sync hidden fields
    syncBepToForm();
}

function syncBepToForm() {
    document.getElementById('pageBlocksJsonHidden').value = JSON.stringify(_bepBlocks);
    // Also sync content textarea with rendered HTML for compatibility
    const ta = document.getElementById('sn');
    if (ta) ta.value = bepBuildHtml();
}

// ══════════════════════════════════════════════════════════════
// CRUD BLOCKS
// ══════════════════════════════════════════════════════════════
function bepAddBlock(type) {
    const def = JSON.parse(JSON.stringify(BEP_DEFAULTS[type] || {}));
    if (type === 'columns') {
        def.cols = [];
        for (let i = 0; i < (def.count||2); i++) def.cols.push({content:'', bg:'#F8FAFC'});
    }
    _bepBlocks.push({ type, ...def });
    bepRenderCanvas();
    // Scroll to bottom
    const canvas = document.getElementById('bepCanvas');
    if (canvas) canvas.scrollTop = canvas.scrollHeight;
    // Auto-open edit for new block
    bepEditBlock(_bepBlocks.length - 1);
}

function bepMoveBlock(idx, dir) {
    const to = idx + dir;
    if (to < 0 || to >= _bepBlocks.length) return;
    [_bepBlocks[idx], _bepBlocks[to]] = [_bepBlocks[to], _bepBlocks[idx]];
    bepRenderCanvas();
}

function bepRemoveBlock(idx) {
    if (!confirm('Remover este bloco?')) return;
    _bepBlocks.splice(idx, 1);
    bepRenderCanvas();
}

// ══════════════════════════════════════════════════════════════
// BLOCK EDITOR MODAL
// ══════════════════════════════════════════════════════════════
function bepEditBlock(idx) {
    _bepEditIdx = idx;
    const b = _bepBlocks[idx];
    const labels = {heading:'Título',text:'Texto',image:'Imagem',columns:'Colunas',button:'Botão',alert:'Alerta',divider:'Divisor',spacer:'Espaço',html:'HTML Livre'};
    document.getElementById('bepModalTitle').textContent = 'Editar — ' + (labels[b.type]||b.type);

    const body = document.getElementById('bepModalBody');
    body.innerHTML = '';

    const fi = (lbl, key, type='text', extra='', opt='') => {
        const id = `bepf_${key}`;
        const val = (b[key]??'').toString().replace(/"/g,'&quot;');
        let inp;
        if (type === 'textarea') inp = `<textarea id="${id}" class="form-input text-xs font-mono resize-y" rows="5">${(b[key]??'')}</textarea>`;
        else if (type === 'select') inp = `<select id="${id}" class="form-input text-xs">${opt}</select>`;
        else inp = `<input type="${type}" id="${id}" value="${val}" class="form-input text-xs" ${extra}>`;
        return `<div><label class="form-label">${lbl}</label>${inp}</div>`;
    };
    const colorRow = (lbl, key) => `<div style="display:flex;align-items:center;gap:.75rem">
        <label class="form-label" style="flex:1;margin:0">${lbl}</label>
        <input type="color" id="bepf_${key}" value="${b[key]||'#ffffff'}" style="width:2.25rem;height:2rem;border-radius:.4rem;border:1px solid rgba(0,22,68,.1);cursor:pointer;padding:2px">
        <input type="text" id="bepf_${key}_txt" value="${b[key]||'#ffffff'}" class="form-input text-xs" style="width:6.5rem" oninput="document.getElementById('bepf_${key}').value=this.value">
    </div>`;

    const alignOpts = (cur) => ['left','center','right'].map(v=>`<option value="${v}" ${cur===v?'selected':''}>${{left:'Esquerda',center:'Centro',right:'Direita'}[v]}</option>`).join('');

    let html = '';
    switch (b.type) {
        case 'heading':
            html = fi('Texto do título','text') +
                   fi('Nível','level','select','',['h1','h2','h3','h4'].map(v=>`<option value="${v}" ${b.level===v?'selected':''}>${v.toUpperCase()}</option>`).join('')) +
                   fi('Alinhamento','align','select','',alignOpts(b.align)) +
                   colorRow('Cor do texto','color');
            break;
        case 'text':
            html = fi('Conteúdo HTML','content','textarea') + colorRow('Cor de fundo','bgColor');
            break;
        case 'image':
            html = `<div><label class="form-label">URL da Imagem</label>
                <input type="text" id="bepf_src" value="${(b.src||'').replace(/"/g,'&quot;')}" class="form-input text-xs" placeholder="https://..."></div>` +
                   fi('Texto alternativo','alt') +
                   fi('Link ao clicar','link') +
                   fi('Largura','width','text','placeholder="100%, 480px..."') +
                   fi('Legenda','caption') +
                   fi('Alinhamento','align','select','',alignOpts(b.align)) +
                   colorRow('Cor de fundo','bgColor');
            break;
        case 'columns': {
            const n = parseInt(b.count)||2;
            let colsHtml = fi('Número de colunas','count','number','min="2" max="4"') + fi('Gap (px)','gap','number','min="8" max="48"');
            for (let i = 0; i < n; i++) {
                const cc = b.cols?.[i] || {};
                colsHtml += `<div style="border:1px solid rgba(0,22,68,.08);border-radius:.75rem;padding:.875rem"><p class="form-label" style="margin-bottom:.5rem">Coluna ${i+1}</p>
                    <label class="form-label">Conteúdo HTML</label>
                    <textarea id="bepf_col${i}_content" class="form-input text-xs font-mono resize-y" rows="3">${cc.content||''}</textarea>
                    <div style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem">
                        <label class="form-label" style="margin:0;flex:1">Cor de fundo</label>
                        <input type="color" id="bepf_col${i}_bg" value="${cc.bg||'#F8FAFC'}" style="width:2rem;height:1.75rem;border-radius:.35rem;border:1px solid rgba(0,22,68,.1);cursor:pointer;padding:2px">
                    </div></div>`;
            }
            html = colsHtml; break;
        }
        case 'button':
            html = fi('Texto do botão','label') +
                   fi('URL','url') +
                   fi('Estilo','style','select','',['filled','pill','outline','ghost','3d','gradient'].map(v=>`<option value="${v}" ${b.style===v?'selected':''}>${{filled:'Preenchido',pill:'Pílula',outline:'Contorno',ghost:'Ghost','3d':'3D',gradient:'Gradiente'}[v]}</option>`).join('')) +
                   fi('Tamanho','size','select','',['sm','md','lg'].map(v=>`<option value="${v}" ${b.size===v?'selected':''}>${{sm:'Pequeno',md:'Médio',lg:'Grande'}[v]}</option>`).join('')) +
                   colorRow('Cor','color') +
                   fi('Alinhamento','align','select','',alignOpts(b.align)) +
                   fi('Abrir em','target','select','',['_blank','_self'].map(v=>`<option value="${v}" ${b.target===v?'selected':''}>${{_blank:'Nova aba',_self:'Mesma aba'}[v]}</option>`).join(''));
            break;
        case 'alert':
            html = fi('Tipo','type','select','',['info','success','warning','danger','tip','crcap'].map(v=>`<option value="${v}" ${b.type===v?'selected':''}>${{info:'ℹ️ Informação',success:'✅ Sucesso',warning:'⚠️ Aviso',danger:'🚫 Atenção',tip:'💡 Dica',crcap:'⭐ CRCAP'}[v]}</option>`).join('')) +
                   fi('Título (opcional)','title') +
                   fi('Mensagem','message','textarea');
            break;
        case 'divider':
            html = fi('Estilo','style','select','',['solid','dashed','dotted','gradient','ornament'].map(v=>`<option value="${v}" ${b.style===v?'selected':''}>${{solid:'Sólido',dashed:'Tracejado',dotted:'Pontilhado',gradient:'Gradiente CRCAP',ornament:'Ornamento ✦'}[v]}</option>`).join(''));
            break;
        case 'spacer':
            html = fi('Altura (px)','height','number','min="8" max="200"');
            break;
        case 'html':
            html = fi('Código HTML','code','textarea');
            break;
    }
    body.innerHTML = html;

    // Sync color pickers ↔ text
    body.querySelectorAll('input[type="color"]').forEach(ci => {
        const ti = document.getElementById(ci.id+'_txt');
        if (ti) ci.addEventListener('input', () => { ti.value = ci.value; });
    });

    // Set select values
    body.querySelectorAll('select[id^="bepf_"]').forEach(sel => {
        const key = sel.id.replace('bepf_','');
        if (b[key] !== undefined) sel.value = b[key];
    });

    document.getElementById('bepModal').classList.add('open');
}

function bepModalApply() {
    if (_bepEditIdx === null) return;
    const b = _bepBlocks[_bepEditIdx];

    document.querySelectorAll('#bepModalBody [id^="bepf_"]').forEach(el => {
        if (el.id.endsWith('_txt')) return;
        const key = el.id.replace('bepf_', '');
        // Handle column sub-fields
        const colMatch = key.match(/^col(\d+)_(.+)$/);
        if (colMatch) {
            const ci = parseInt(colMatch[1]);
            const ck = colMatch[2];
            if (!b.cols) b.cols = [];
            if (!b.cols[ci]) b.cols[ci] = {};
            b.cols[ci][ck] = el.value;
        } else {
            b[key] = el.value;
        }
    });

    // If count changed, re-init cols
    if (b.type === 'columns') {
        const n = parseInt(b.count) || 2;
        while (b.cols.length < n) b.cols.push({content:'',bg:'#F8FAFC'});
    }

    closeBepModal();
    bepRenderCanvas();
}

function closeBepModal() {
    document.getElementById('bepModal').classList.remove('open');
    _bepEditIdx = null;
}

// ══════════════════════════════════════════════════════════════
// HTML / PREVIEW TOGGLE
// ══════════════════════════════════════════════════════════════
function bepToggleHtml() {
    _bepHtmlMode = !_bepHtmlMode;
    _bepPrevMode = false;
    document.getElementById('bepHtmlPanel').style.display    = _bepHtmlMode ? '' : 'none';
    document.getElementById('bepPreviewPanel').style.display = 'none';
    document.getElementById('bepCanvas').style.display       = _bepHtmlMode ? 'none' : '';
    document.getElementById('bepHtmlToggle').style.background = _bepHtmlMode ? '#BF8D1A' : '';
    document.getElementById('bepPrevToggle').style.background = '';
    if (_bepHtmlMode) document.getElementById('bepHtmlRaw').value = bepBuildHtml();
}

function bepTogglePreview() {
    _bepPrevMode = !_bepPrevMode;
    _bepHtmlMode = false;
    document.getElementById('bepPreviewPanel').style.display = _bepPrevMode ? '' : 'none';
    document.getElementById('bepHtmlPanel').style.display    = 'none';
    document.getElementById('bepCanvas').style.display       = _bepPrevMode ? 'none' : '';
    document.getElementById('bepPrevToggle').style.background = _bepPrevMode ? '#BF8D1A' : '';
    document.getElementById('bepHtmlToggle').style.background = '';
    if (_bepPrevMode) {
        const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:system-ui;padding:2rem;max-width:900px;margin:0 auto;color:#022E6B}img{max-width:100%}</style></head><body>${bepBuildHtml()}</body></html>`;
        document.getElementById('bepPreviewFrame').srcdoc = html;
    }
}

// ══════════════════════════════════════════════════════════════
// LOAD SAVED BLOCKS
// ══════════════════════════════════════════════════════════════
(function initBepBlocks() {
    const saved = <?= json_encode($savedBlocksJson ?: '') ?>;
    if (saved && saved.trim().startsWith('[')) {
        try {
            const parsed = JSON.parse(saved);
            if (Array.isArray(parsed) && parsed.length > 0) {
                _bepBlocks = parsed;
                bepRenderCanvas();
                return;
            }
        } catch(e) {}
    }
    // Fallback: if existing content, wrap as html block
    const existingContent = <?= json_encode(trim($page['content'] ?? '')) ?>;
    if (existingContent) {
        _bepBlocks = [{ type: 'html', code: existingContent }];
        bepRenderCanvas();
    }
})();

// ══════════════════════════════════════════════════════════════
// SAVE
// ══════════════════════════════════════════════════════════════
function doSave() {
    if (_editorMode === 'blocks') {
        if (_bepHtmlMode) {
            // User edited HTML raw — use as single html block
            const rawHtml = document.getElementById('bepHtmlRaw').value;
            _bepBlocks = [{ type: 'html', code: rawHtml }];
        }
        syncBepToForm();
        // Clear rich editor content so blocks win
        document.getElementById('sn').value = bepBuildHtml();
    } else {
        // rich editor mode — get summernote content
        if (typeof $ !== 'undefined' && $('#sn').data('summernote')) {
            document.getElementById('sn').value = $('#sn').summernote('code');
        }
        // Clear blocks json
        document.getElementById('pageBlocksJsonHidden').value = '';
    }
    document.getElementById('mf').submit();
}

// ══════════════════════════════════════════════════════════════
// RICH EDITOR (Summernote) — only init when mode = rich
// ══════════════════════════════════════════════════════════════
let _snInited = false;
function initSummernote() {
    if (_snInited) return;
    _snInited = true;
    $('#sn').summernote({
        height: 450,
        lang: 'pt-BR',
        placeholder: 'Escreva o conteúdo da página...',
        toolbar: [
            ['style',  ['style']],
            ['font',   ['bold','underline','italic','strikethrough']],
            ['fontsize',['fontsize']],
            ['color',  ['color']],
            ['para',   ['ul','ol','paragraph']],
            ['table',  ['table']],
            ['insert', ['link','picture','video']],
            ['view',   ['fullscreen','codeview','help']],
        ],
        callbacks: {
            onChange: () => { dirty(); }
        }
    });
}

function toggleHtml() {
    if (_editorMode !== 'rich') { setEditorMode('rich'); return; }
    const sn = document.getElementById('sn');
    const ha = document.getElementById('htmlArea');
    const btn= document.getElementById('htmlBtn');
    if (ha.classList.contains('hidden')) {
        ha.value = $('#sn').summernote('code');
        ha.classList.remove('hidden');
        $('#sn').summernote('disable');
        btn.classList.add('tbgo');
    } else {
        $('#sn').summernote('enable');
        $('#sn').summernote('code', ha.value);
        ha.classList.add('hidden');
        btn.classList.remove('tbgo');
    }
}

function prevPage() {
    if (_editorMode === 'blocks') { bepTogglePreview(); return; }
    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:system-ui;padding:2rem;max-width:900px;margin:0 auto;color:#022E6B}img{max-width:100%}</style></head><body>${$('#sn').summernote('code')}</body></html>`;
    const w = window.open('','_blank');
    w.document.write(html); w.document.close();
}

// ══════════════════════════════════════════════════════════════
// Misc UI helpers (accordion, seo bar, slug, dirty, keywords)
// ══════════════════════════════════════════════════════════════
let _dirty = false;
function dirty() {
    _dirty = true;
    const b = document.getElementById('fsBtn');
    if (b) b.classList.add('show');
}
window.addEventListener('beforeunload', e => {
    if (_dirty) { e.preventDefault(); e.returnValue=''; }
});
function toggleAcc(el) {
    el.classList.toggle('open');
    el.nextElementSibling.classList.toggle('open');
}
function seoBar(inp,barId,max) {
    const n=inp.value.length;
    const bar=document.getElementById(barId);
    const num=document.getElementById(barId+'n');
    if(bar)bar.style.width=Math.min(100,n/max*100)+'%';
    if(num)num.textContent=n+'/'+max;
}
function autoSlug() {
    const sl=document.getElementById('slugInp');
    if(!sl||sl.readOnly)return;
    const v=document.getElementById('pgTitle').value.toLowerCase();
    sl.value=v.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}
function openModal(id){document.getElementById(id).classList.remove('modal-hidden');}
function closeM(id){document.getElementById(id).classList.add('modal-hidden');}

// Keywords
(function initKw(){
    const h=document.getElementById('kwH'),w=document.getElementById('kwW');
    if(!h||!w)return;
    const inp=document.createElement('input');inp.id='kwI';inp.className='ti';inp.placeholder='palavra-chave...';
    function addTag(v){v=v.trim();if(!v)return;const t=document.createElement('span');t.className='tp';t.innerHTML=v+'<button type="button" onclick="this.parentElement.remove();syncKw()">×</button>';w.insertBefore(t,inp);syncKw();}
    function syncKw(){h.value=Array.from(w.querySelectorAll('.tp')).map(t=>t.childNodes[0].nodeValue).join(',');}
    inp.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===','){e.preventDefault();addTag(inp.value);inp.value='';}else if(e.key==='Backspace'&&!inp.value){const ls=w.querySelectorAll('.tp');if(ls.length){ls[ls.length-1].remove();syncKw();}}});
    w.appendChild(inp);
    if(h.value)h.value.split(',').forEach(v=>{if(v.trim())addTag(v.trim());});
})();

// SEO bars init
['sTB','sDB'].forEach(id=>{const el=document.getElementById(id==='sTB'?'seoT':'seoD');if(el)seoBar(el,id,id==='sTB'?60:160);});

// ══════════════════════════════════════════════════════════════
// RICH EDITOR HELPERS (paste, insertTbl, insertAlr, insertCol, etc.)
// ══════════════════════════════════════════════════════════════
function paste(h){
    if(_editorMode!=='rich'){setEditorMode('rich');setTimeout(()=>{$('#sn').summernote('focus');$('#sn').summernote('pasteHTML',h);dirty();},300);return;}
    if(!_snInited)initSummernote();
    $('#sn').summernote('focus');$('#sn').summernote('pasteHTML',h);dirty();
}

// TABLE
function insertTbl(){
    var r=parseInt(document.getElementById('tR').value)||3,c=parseInt(document.getElementById('tC').value)||3,h=parseInt(document.getElementById('tH').value),s=document.querySelector('[name="tS"]:checked').value;
    var styles={stripe:'border-collapse:collapse;width:100%;font-family:system-ui;font-size:14px',bordered:'border-collapse:collapse;width:100%;border:1.5px solid rgba(0,22,68,.15);font-family:system-ui;font-size:14px',minimal:'border-collapse:collapse;width:100%;font-family:system-ui;font-size:14px',crcap:'border-collapse:collapse;width:100%;font-family:system-ui;font-size:14px'};
    var thS={stripe:'padding:10px 14px;background:#001644;color:white;font-weight:700;text-align:left;border:none',bordered:'padding:10px 14px;background:#001644;color:white;font-weight:700;text-align:left;border:1px solid rgba(0,22,68,.15)',minimal:'padding:10px 14px;border-bottom:2px solid #001644;font-weight:700;color:#001644;text-align:left',crcap:'padding:10px 14px;background:#001644;color:white;font-weight:700;text-align:left'};
    var tdS={stripe:'padding:9px 14px;border-bottom:1px solid rgba(0,22,68,.07)',bordered:'padding:9px 14px;border:1px solid rgba(0,22,68,.1)',minimal:'padding:9px 14px;border-bottom:1px solid rgba(0,22,68,.07)',crcap:'padding:9px 14px;border:1px solid rgba(0,22,68,.1)'};
    var t='<table style="'+styles[s]+'" data-tbl="1"><tbody>';
    for(var i=0;i<r;i++){t+='<tr>';for(var j=0;j<c;j++){if(h&&i===0)t+='<th style="'+thS[s]+'">Cabeçalho '+(j+1)+'</th>';else t+='<td style="'+tdS[s]+(s==='stripe'&&i%2?' background:rgba(0,22,68,.02)':'')+'">'+(h&&i===0?'':'Célula')+'</td>';}t+='</tr>';}
    t+='</tbody></table>';paste(t);closeM('mTbl');
}
// ALERT
var aC={info:{i:'ℹ️',bg:'#eff6ff',bd:'#bfdbfe',c:'#1d4ed8',t:'Informação'},success:{i:'✅',bg:'#f0fdf4',bd:'#bbf7d0',c:'#15803d',t:'Sucesso'},warning:{i:'⚠️',bg:'#fffbeb',bd:'#fde68a',c:'#b45309',t:'Aviso'},danger:{i:'🚫',bg:'#fef2f2',bd:'#fecaca',c:'#dc2626',t:'Importante'},tip:{i:'💡',bg:'#f5f3ff',bd:'#ddd6fe',c:'#7c3aed',t:'Dica'},crcap:{i:'⭐',bg:'#fffdf5',bd:'#fde68a',c:'#92400e',t:'CRCAP'}};
function insertAlr(){var tp=document.querySelector('[name="aT"]:checked').value,cf=aC[tp],tl=document.getElementById('aT').value.trim()||cf.t,tx=document.getElementById('aM').value.trim();if(!tx)return;var h='<div style="display:flex;gap:12px;align-items:flex-start;padding:16px 20px;background:'+cf.bg+';border:1.5px solid '+cf.bd+';border-radius:12px;margin:1rem 0;font-family:system-ui"><span style="font-size:18px;flex-shrink:0;margin-top:1px">'+cf.i+'</span><div><strong style="display:block;color:'+cf.c+';font-size:13px;font-weight:700;margin-bottom:4px">'+tl+'</strong><span style="color:'+cf.c+';font-size:13px;opacity:.85">'+tx+'</span></div></div>';paste(h);closeM('mAlr');document.getElementById('aT').value='';document.getElementById('aM').value='';}
// COLUMNS
function insertCol(){var n=parseInt(document.querySelector('[name="cN"]:checked').value)||2,g=parseInt(document.getElementById('cG').value)||24;var cs='flex:1;min-width:180px;padding:12px 16px;background:#F8FAFC;border-radius:10px;border:1px solid rgba(0,22,68,.08);min-height:80px;box-sizing:border-box';var h='<div style="display:flex;gap:'+g+'px;margin:1rem 0;flex-wrap:wrap">';for(var i=0;i<n;i++)h+='<div style="'+cs+'"><p style="margin:0;font-size:14px;color:#022E6B;font-family:system-ui">Conteúdo da coluna '+(i+1)+'</p></div>';h+='</div>';paste(h);closeM('mCol');}
// DIVIDER
var dS={solid:'<hr style="border:none;border-top:2px solid rgba(0,22,68,.1);margin:2rem 0">',dashed:'<hr style="border:none;border-top:2px dashed rgba(0,22,68,.15);margin:2rem 0">',dotted:'<hr style="border:none;border-top:2px dotted rgba(0,22,68,.15);margin:2rem 0">',gradient:'<div style="height:1px;background:linear-gradient(90deg,transparent,rgba(0,22,68,.2) 20%,#BF8D1A 50%,rgba(0,22,68,.2) 80%,transparent);margin:2rem 0"></div>',ornament:'<div style="text-align:center;margin:2rem 0;color:rgba(0,22,68,.2);font-size:1.25rem;letter-spacing:.5rem">✦ ✦ ✦</div>'};
function insertDiv(){paste(dS[document.querySelector('[name="dS"]:checked').value]);closeM('mDiv');}
// BUTTONS
var _btns=[];
function setBC(c){document.getElementById('bClr').value=c;updBP();}
function bCss(lbl,url,clr,sty,sz,tgt,ico){var hx=clr.replace('#',''),r=parseInt(hx.substr(0,2),16),g=parseInt(hx.substr(2,2),16),b=parseInt(hx.substr(4,2),16);var lm=(0.299*r+0.587*g+0.114*b)/255,tx=lm>0.6?'#001644':'#fff';var pd={sm:'8px 18px',md:'11px 26px',lg:'14px 36px'},fs={sm:'12px',md:'13px',lg:'15px'};var ih=ico?'<i class="fas '+ico+'" style="font-size:11px"></i>':'',css='';switch(sty){case 'pill':css='display:inline-flex;align-items:center;gap:8px;padding:'+pd[sz]+';background:'+clr+';color:'+tx+';border-radius:50px;text-decoration:none;font-size:'+fs[sz]+';font-weight:700;margin:4px 6px 4px 0;box-shadow:0 4px 16px rgba(0,0,0,.15)';break;case 'outline':css='display:inline-flex;align-items:center;gap:8px;padding:'+pd[sz]+';background:transparent;color:'+clr+';border:2px solid '+clr+';border-radius:10px;text-decoration:none;font-size:'+fs[sz]+';font-weight:700;margin:4px 6px 4px 0';break;case 'ghost':css='display:inline-flex;align-items:center;gap:8px;padding:'+pd[sz]+';background:transparent;color:'+clr+';border:1.5px solid '+clr+'55;border-radius:10px;text-decoration:none;font-size:'+fs[sz]+';font-weight:600;margin:4px 6px 4px 0';break;case '3d':css='display:inline-flex;align-items:center;gap:8px;padding:'+pd[sz]+';background:'+clr+';color:'+tx+';border-radius:10px;text-decoration:none;font-size:'+fs[sz]+';font-weight:700;margin:4px 6px 4px 0;box-shadow:0 6px 0 rgba(0,0,0,.25),0 8px 14px rgba(0,0,0,.15)';break;case 'gradient':var h2=Math.min(parseInt(hx.substr(0,2),16)+40,255).toString(16).padStart(2,'0');css='display:inline-flex;align-items:center;gap:8px;padding:'+pd[sz]+';background:linear-gradient(135deg,'+clr+',#'+h2+hx.substr(2)+');color:'+tx+';border-radius:10px;text-decoration:none;font-size:'+fs[sz]+';font-weight:700;margin:4px 6px 4px 0;box-shadow:0 4px 16px rgba(0,0,0,.18)';break;default:css='display:inline-flex;align-items:center;gap:8px;padding:'+pd[sz]+';background:'+clr+';color:'+tx+';border-radius:10px;text-decoration:none;font-size:'+fs[sz]+';font-weight:700;margin:4px 6px 4px 0;box-shadow:0 4px 14px rgba(0,0,0,.18)';}return '<a href="'+url+'" target="'+tgt+'" rel="noopener" style="'+css+'">'+ih+lbl+'</a>';}
function updBP(){var lbl=document.getElementById('bLbl').value||'Botão',clr=document.getElementById('bClr').value,sty=document.getElementById('bSty').value,sz=document.getElementById('bSz').value,ico=document.getElementById('bIco').value.trim(),align=document.querySelector('[name="bAlign"]:checked')?document.querySelector('[name="bAlign"]:checked').value:'left';var a=document.getElementById('bPrev'),e=document.getElementById('bPE');if(e)e.style.display='none';var old=a.querySelector('.blp');if(old)old.remove();var alignStyle={left:'justify-content:flex-start',center:'justify-content:center',right:'justify-content:flex-end'};a.style.cssText='min-height:2.5rem;background:#F8FAFC;border-radius:.75rem;padding:.75rem 1rem;display:flex;flex-wrap:wrap;align-items:center;border:1px solid rgba(0,22,68,.05);'+alignStyle[align];var w=document.createElement('span');w.className='blp';w.innerHTML=bCss(lbl,'#',clr,sty,sz,'_blank',ico);a.appendChild(w);}
function addBtn(){var lbl=document.getElementById('bLbl').value.trim(),url=document.getElementById('bUrl').value.trim();if(!lbl){document.getElementById('bLbl').focus();return;}if(!url){document.getElementById('bUrl').focus();return;}_btns.push({lbl,url,clr:document.getElementById('bClr').value,sty:document.getElementById('bSty').value,sz:document.getElementById('bSz').value,tgt:document.getElementById('bTgt').value,ico:document.getElementById('bIco').value.trim()});renderBtns();document.getElementById('bLbl').value='';document.getElementById('bUrl').value='';document.getElementById('bLbl').focus();}
function renderBtns(){var el=document.getElementById('bList');if(!_btns.length){el.innerHTML='';return;}el.innerHTML=_btns.map((b,i)=>'<div class="flex items-center gap-2 p-2 bg-[#F8FAFC] rounded-xl border border-[#001644]/04"><div class="w-3 h-3 rounded-full flex-shrink-0" style="background:'+b.clr+'"></div><span class="text-[9px] text-[#001644]/30 font-bold uppercase">'+b.sty+'</span><div class="flex-1 min-w-0"><p class="text-xs font-semibold text-[#001644] truncate">'+(b.ico?'<i class="fas '+b.ico+' mr-1"></i>':'')+b.lbl+'</p><p class="text-[9px] text-[#022E6B]/30 truncate">'+b.url+'</p></div><button type="button" onclick="delBtn('+i+')" class="w-5 h-5 rounded bg-red-50 hover:bg-red-500 hover:text-white text-red-400 flex items-center justify-center text-[9px]"><i class="fas fa-times"></i></button></div>').join('');}
function delBtn(i){_btns.splice(i,1);renderBtns();}
['bLbl','bUrl'].forEach(id=>{var e=document.getElementById(id);if(e)e.addEventListener('keydown',e2=>{if(e2.key==='Enter'){e2.preventDefault();addBtn();}});});
var _extBtnAlign='left',_extBtns=[],_btnTarget='editor',_pdfBlocks=[];
function setPanelAlign(a){_extBtnAlign=a;['L','C','R'].forEach(function(x){var el=document.getElementById('pa'+x);if(!el)return;var isActive=(x==='L'&&a==='left')||(x==='C'&&a==='center')||(x==='R'&&a==='right');el.style.background=isActive?'#001644':'white';el.style.color=isActive?'white':'#022E6B';});var d=document.getElementById('btnPanelDisplay');if(d)d.style.justifyContent=a==='left'?'flex-start':a==='center'?'center':'flex-end';dirty();}
function openPanelBtnModal(){_btnTarget='panel';var lbl=document.getElementById('btnInsertLbl');if(lbl)lbl.textContent='Adicionar ao painel';openModal('mBtn');}
function insertBtns(){if(!_btns.length){addBtn();if(!_btns.length)return;}var lbl=document.getElementById('btnInsertLbl');if(_btnTarget==='panel'){_btns.forEach(function(b){_extBtns.push(b);renderPanelBtn(b,_extBtns.length-1);});syncBtnPanel();if(lbl)lbl.textContent='Inserir no editor';closeM('mBtn');_btns=[];_btnTarget='editor';}else{var alignEl=document.querySelector('[name="bAlign"]:checked'),av=alignEl?alignEl.value:'left',am={left:'text-align:left',center:'text-align:center',right:'text-align:right'};paste('<p style="margin:1.2rem 0;'+am[av]+'">'+_btns.map(function(b){return bCss(b.lbl,b.url,b.clr,b.sty,b.sz,b.tgt,b.ico);}).join('\n')+'</p>');if(lbl)lbl.textContent='Inserir no editor';closeM('mBtn');_btns=[];_btnTarget='editor';}}
function renderPanelBtn(b,idx){var d=document.getElementById('btnPanelDisplay');if(!d)return;var hint=document.getElementById('btnPanelHint');if(hint)hint.style.display='none';var wrap=document.createElement('span');wrap.style.cssText='position:relative;display:inline-flex';wrap.setAttribute('data-idx',idx);wrap.innerHTML=bCss(b.lbl,b.url,b.clr,b.sty,b.sz,b.tgt,b.ico);var del=document.createElement('button');del.type='button';del.style.cssText='position:absolute;top:-5px;right:-5px;width:15px;height:15px;background:#dc2626;border-radius:50%;border:none;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:7px;z-index:10;padding:0';del.innerHTML='<i class="fas fa-times"></i>';del.onclick=function(){removePanelBtn(idx);};wrap.appendChild(del);d.appendChild(wrap);var cl=document.getElementById('btnPanelClearBtn');if(cl)cl.style.display='';var pa=document.getElementById('btnPanelArea');if(pa){pa.style.borderStyle='solid';pa.style.borderColor='rgba(191,141,26,.25)';pa.style.background='#FFFDF8';}}
function removePanelBtn(idx){_extBtns[idx]=null;var d=document.getElementById('btnPanelDisplay');if(d){var w=d.querySelector('[data-idx="'+idx+'"]');if(w)w.remove();if(!d.querySelectorAll('[data-idx]').length){var hint=document.getElementById('btnPanelHint');if(hint)hint.style.display='';var cl=document.getElementById('btnPanelClearBtn');if(cl)cl.style.display='none';var pa=document.getElementById('btnPanelArea');if(pa){pa.style.borderStyle='dashed';pa.style.borderColor='rgba(0,22,68,.1)';pa.style.background='white';}}}syncBtnPanel();dirty();}
function clearBtnPanel(){_extBtns=[];var d=document.getElementById('btnPanelDisplay');if(d){d.querySelectorAll('[data-idx]').forEach(function(el){el.remove();});var hint=document.getElementById('btnPanelHint');if(hint)hint.style.display='';}var cl=document.getElementById('btnPanelClearBtn');if(cl)cl.style.display='none';var pa=document.getElementById('btnPanelArea');if(pa){pa.style.borderStyle='dashed';pa.style.borderColor='rgba(0,22,68,.1)';pa.style.background='white';}syncBtnPanel();dirty();}
function syncBtnPanel(){var h=document.getElementById('btnPanelHtml');if(!h)return;var active=_extBtns.filter(Boolean);if(!active.length){h.value='';return;}var am={left:'text-align:left',center:'text-align:center',right:'text-align:right'};h.value='<p style="margin:1.5rem 0;'+(am[_extBtnAlign]||'text-align:left')+'">'+active.map(function(b){return bCss(b.lbl,b.url,b.clr,b.sty,b.sz,b.tgt,b.ico);}).join('\n')+'</p>';}
// PDF blocks
function addPdfBlock(url,name,type){var idx=_pdfBlocks.length;_pdfBlocks.push({url,name,type});renderPdfBlock(url,name,type,idx);syncPdfBlocks();dirty();}
function renderPdfBlock(url,name,type,idx){var area=document.getElementById('pdfBlocksArea');if(!area)return;var nm=name||url.split('/').pop(),wrap=document.createElement('div');wrap.setAttribute('data-pdfidx',idx);wrap.style.cssText='background:white;border:1px solid rgba(0,22,68,.08);border-radius:1rem;overflow:hidden;margin-bottom:.75rem';if(type==='viewer'){var viewUrl=url+(url.indexOf('?')>=0?'&':'?')+'view=1&t='+Date.now();wrap.innerHTML='<div style="display:flex;align-items:center;gap:.5rem;padding:.6rem 1rem;background:#001644;color:white;font-size:.75rem;font-weight:600"><i class="fas fa-file-pdf" style="color:#BF8D1A"></i><span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+nm.replace(/</g,'&lt;')+'</span><a href="'+url+'" target="_blank" style="font-size:.65rem;color:#BF8D1A;text-decoration:none;margin-left:auto">⬇ Baixar</a><button type="button" onclick="removePdfBlock('+idx+')" style="margin-left:.5rem;background:rgba(255,255,255,.1);border:none;color:rgba(255,255,255,.5);cursor:pointer;border-radius:.3rem;padding:.2rem .5rem;font-size:.6rem">✕</button></div><iframe src="'+viewUrl+'" width="100%" height="480" style="display:block;border:none" loading="lazy" allowfullscreen></iframe>';}else{wrap.innerHTML='<div style="padding:.75rem 1rem;display:flex;align-items:center;gap:.75rem"><div style="width:2rem;height:2rem;border-radius:.5rem;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fas fa-file-pdf" style="color:#ef4444"></i></div><div style="flex:1;min-width:0"><p style="font-size:.8rem;font-weight:600;color:#001644;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+nm.replace(/</g,'&lt;')+'</p></div><a href="'+url+'" target="_blank" style="font-size:.7rem;font-weight:600;padding:.4rem .9rem;background:#001644;color:white;border-radius:.5rem;text-decoration:none">Baixar</a><button type="button" onclick="removePdfBlock('+idx+')" style="margin-left:.25rem;width:1.5rem;height:1.5rem;border-radius:.35rem;background:#fef2f2;border:none;color:#ef4444;cursor:pointer">✕</button></div>';}area.appendChild(wrap);}
function removePdfBlock(idx){_pdfBlocks[idx]=null;var area=document.getElementById('pdfBlocksArea');if(area){var el=area.querySelector('[data-pdfidx="'+idx+'"]');if(el)el.remove();}syncPdfBlocks();dirty();}
function syncPdfBlocks(){var h=document.getElementById('pdfBlocksData');if(!h)return;h.value=JSON.stringify(_pdfBlocks.filter(Boolean));}
window.insertPdfEmbed=function(btn){var it=btn.closest('.pdf-item');addPdfBlock(it.dataset.url,it.dataset.name,'viewer');};
window.insertPdfLink=function(btn){var it=btn.closest('.pdf-item');addPdfBlock(it.dataset.url,it.dataset.name,'link');};
// PDF search/upload (keep existing PDF system)
var _allPdfs=[];
function pdfMsg2(t,tp){var e=document.getElementById('pdfMsg2');if(!e)return;e.textContent=t;e.className='mb-2 text-xs rounded-xl px-3 py-1.5 '+(tp==='ok'?'bg-[#006633]/10 border border-[#006633]/20 text-[#006633]':'bg-red-50 border border-red-200 text-red-600');e.classList.remove('hidden');setTimeout(()=>e.classList.add('hidden'),4000);}
async function loadAllPdfs(){try{var r=await fetch('/crcap/api/list-pdfs.php?all=1'),d=await r.json();if(d.success&&d.files)_allPdfs=d.files.map(f=>({url:f.url,rawUrl:f.raw_url||f.url,name:f.name,size:f.size,doc_id:f.doc_id}));}catch(e){}}
var _srchResults=[];
function searchPdfs(q){var res=document.getElementById('pdfRes');if(!res)return;if(!q.trim()){res.classList.add('hidden');_srchResults=[];return;}_srchResults=_allPdfs.filter(f=>f.name.toLowerCase().includes(q.toLowerCase())).slice(0,10);if(!_srchResults.length){res.innerHTML='<p class="px-4 py-3 text-[9px] text-[#022E6B]/35 italic">Nenhum PDF encontrado.</p>';}else{res.innerHTML=_srchResults.map((f,i)=>'<button type="button" data-idx="'+i+'" class="srch-pdf-item w-full flex items-center gap-2.5 px-4 py-2 hover:bg-[#F8FAFC] text-left transition"><i class="fas fa-file-pdf text-red-400 text-xs flex-shrink-0"></i><div class="flex-1 min-w-0"><p class="text-xs font-semibold text-[#001644] truncate">'+f.name+'</p></div><i class="fas fa-plus-circle text-[#BF8D1A] text-xs flex-shrink-0"></i></button>').join('');res.querySelectorAll('.srch-pdf-item').forEach(function(btn){btn.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();var idx=parseInt(this.getAttribute('data-idx')),f=_srchResults[idx];if(f){addPdfCard(f);document.getElementById('pdfSrch').value='';res.classList.add('hidden');_srchResults=[];}});});}res.classList.remove('hidden');}
document.addEventListener('click',function(e){var r=document.getElementById('pdfRes');if(r&&!r.contains(e.target)&&e.target.id!=='pdfSrch')r.classList.add('hidden');});
function addPdfCard(pdf){var emp=document.getElementById('pdfEmpty');if(emp)emp.remove();var lst=document.getElementById('pdfList');if(!lst)return;var nm=pdf.name.replace(/</g,'&lt;'),kb=pdf.size?(pdf.size/1024).toFixed(0)+' KB':'',previewUrl=pdf.rawUrl||pdf.url,it=document.createElement('div');it.className='pdf-item';it.dataset.url=pdf.url;it.dataset.name=pdf.name;if(pdf.doc_id)it.dataset.docId=pdf.doc_id;it.innerHTML='<div class="flex items-center gap-2 p-2.5"><div class="w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0"><i class="fas fa-file-pdf text-red-500 text-sm"></i></div><div class="flex-1 min-w-0"><p class="text-xs font-semibold text-[#001644] truncate">'+nm+'</p><p class="text-[9px] text-[#022E6B]/30">'+kb+'</p></div><a href="'+previewUrl+'" target="_blank" class="w-6 h-6 rounded-lg bg-[#006633]/05 hover:bg-[#006633] hover:text-white text-[#006633] flex items-center justify-center text-[9px]"><i class="fas fa-external-link-alt"></i></a><button type="button" onclick="this.closest(\'.pdf-item\').remove();updPdfCnt()" class="w-6 h-6 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-400 flex items-center justify-center text-[9px]"><i class="fas fa-times"></i></button></div><div class="grid grid-cols-2 border-t border-[#001644]/04"><button type="button" onclick="insertPdfEmbed(this)" class="flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-blue-600 hover:bg-blue-50 transition border-r border-[#001644]/04"><i class="fas fa-tv"></i> Viewer</button><button type="button" onclick="insertPdfLink(this)" class="flex items-center justify-center gap-1 py-1.5 text-[9px] font-bold text-[#BF8D1A] hover:bg-amber-50 transition"><i class="fas fa-link"></i> Link</button></div>';lst.appendChild(it);updPdfCnt();}
function updPdfCnt(){var e=document.getElementById('pdfCnt');if(e)e.textContent=document.querySelectorAll('.pdf-item').length;}
function initPdf(){var inp=document.getElementById('pdfInp'),dz=document.getElementById('pdfDz'),PS=<?= json_encode($pageSlug) ?>;if(!inp)return;if(dz){['dragenter','dragover'].forEach(ev=>dz.addEventListener(ev,e=>{e.preventDefault();dz.classList.add('border-[#BF8D1A]','bg-amber-50/30');}));['dragleave','drop'].forEach(ev=>dz.addEventListener(ev,e=>{e.preventDefault();dz.classList.remove('border-[#BF8D1A]','bg-amber-50/30');}));dz.addEventListener('drop',e=>{if(e.dataTransfer.files.length)uploadPdfs(e.dataTransfer.files);});}inp.addEventListener('change',function(){if(this.files.length)uploadPdfs(this.files);this.value='';});if(PS)fetch('/crcap/api/list-pdfs.php?slug='+encodeURIComponent(PS)).then(r=>r.json()).then(d=>{if(d.success&&d.files)d.files.forEach(f=>addPdfCard({url:f.url,rawUrl:f.raw_url||f.url,name:f.name,size:f.size,doc_id:f.doc_id}));}).catch(()=>{});function uploadPdfs(files){Array.from(files).filter(f=>f.name.endsWith('.pdf')).reduce((p,f,i,a)=>p.then(()=>uploadOne(f,i+1,a.length)),Promise.resolve());}function uploadOne(file,cur,tot){if(file.size>20*1024*1024){pdfMsg2('"'+file.name+'" excede 20 MB.','err');return Promise.resolve();}var pg=document.getElementById('pdfProg'),pt=document.getElementById('pdfPT'),pb=document.getElementById('pdfPB'),pp=document.getElementById('pdfPP');pg.classList.remove('hidden');pt.textContent=tot>1?'Enviando '+cur+'/'+tot+': '+file.name:'Enviando '+file.name+'...';return new Promise(res=>{var fd=new FormData();fd.append('file',file);fd.append('type','document');fd.append('page_slug',PS);var xhr=new XMLHttpRequest();xhr.upload.onprogress=e=>{if(e.lengthComputable){var p=Math.round(e.loaded/e.total*100);pb.style.width=p+'%';pp.textContent=p+'%';}};xhr.onload=()=>{pg.classList.add('hidden');pb.style.width='0%';pp.textContent='0%';try{var d=JSON.parse(xhr.responseText);if(d.success){var cardUrl=d.view_url||d.url;addPdfCard({url:cardUrl,rawUrl:d.url,name:file.name,size:file.size,doc_id:d.doc_id});pdfMsg2('"'+file.name+'" enviado!','ok');}else pdfMsg2(d.message||'Erro no upload.','err');}catch(e){pdfMsg2('Resposta inválida.','err');}res();};xhr.onerror=()=>{pg.classList.add('hidden');pdfMsg2('Falha de conexão.','err');res();};xhr.open('POST','/crcap/api/upload.php');xhr.send(fd);});}}

window.addEventListener('load',function(){
    setPanelAlign('left');
    loadAllPdfs();
    initPdf();
});
</script>

<?php elseif ($action === 'menus'):
    $menuItems = dbFetchAll($pdo, "SELECT * FROM menu_items ORDER BY menu_location,parent_id,order_position,id");
    $byLoc = []; foreach ($menuItems as $m) $byLoc[$m['menu_location']][] = $m;
?>
<style>
.tb{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .75rem;border-radius:.6rem;font-size:.7rem;font-weight:600;cursor:pointer;border:1px solid transparent;transition:all .15s}
.tbg{color:#022E6B;border-color:rgba(0,22,68,.1);background:transparent}.tbg:hover{background:#F0F4F8;border-color:#BF8D1A}
.tbp{background:#001644;color:white}.tbp:hover{background:#022E6B}
.si{width:100%;padding:.5rem .75rem;border:1px solid rgba(0,22,68,.1);border-radius:.65rem;font-size:.78rem;background:#F8FAFC;transition:all .15s;outline:none}
.si:focus{border-color:#BF8D1A;background:white}
.sl2{font-size:.63rem;font-weight:700;color:#022E6B;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:.3rem}
</style>
<div class="flex items-center gap-3 mb-5">
    <a href="/crcap/admin/pages.php" class="tb tbg"><i class="fas fa-arrow-left text-[9px]"></i> Páginas</a>
    <h2 class="text-lg font-bold text-[#001644]">Editor de Menus</h2>
</div>
<?php if (isset($_GET['saved'])): ?><div class="bg-[#006633]/10 border border-[#006633]/20 text-[#006633] text-xs rounded-xl px-4 py-2.5 mb-4 flex items-center gap-2"><i class="fas fa-check-circle"></i> Menu salvo!</div><?php endif; ?>
<form method="POST" class="space-y-4">
    <input type="hidden" name="form_menu" value="1">
    <?php if (empty($byLoc)): ?>
    <div class="card p-8 text-center text-sm text-[#022E6B]/40"><i class="fas fa-bars text-2xl block mb-2 opacity-20"></i>Nenhum item de menu no banco ainda.</div>
    <?php else: foreach ($byLoc as $loc => $items): ?>
    <div class="card overflow-hidden">
        <div class="flex items-center gap-2 px-4 py-2 bg-[#001644]"><i class="fas fa-bars text-[#BF8D1A] text-xs"></i><span class="text-xs font-bold text-white uppercase">Menu: <?= htmlspecialchars($loc) ?></span></div>
        <div class="divide-y divide-[#001644]/04">
        <?php foreach ($items as $it): $ind = $it['parent_id'] ? 'pl-10' : ''; ?>
        <div class="flex items-center gap-2.5 px-4 py-2 hover:bg-[#F8FAFC] <?= $ind ?>">
            <?php if ($it['parent_id']): ?><i class="fas fa-level-down-alt text-[9px] text-[#BF8D1A]/50 flex-shrink-0"></i><?php endif; ?>
            <div class="grid grid-cols-2 gap-2 flex-1">
                <input type="text" name="mi[<?= $it['id'] ?>][title]" value="<?= htmlspecialchars($it['title']) ?>" class="px-3 py-1.5 text-xs border border-[#001644]/08 rounded-lg focus:outline-none focus:border-[#BF8D1A] bg-white">
                <input type="text" name="mi[<?= $it['id'] ?>][url]" value="<?= htmlspecialchars($it['url'] ?? '') ?>" class="px-3 py-1.5 text-xs font-mono border border-[#001644]/08 rounded-lg focus:outline-none focus:border-[#BF8D1A] bg-white">
            </div>
            <a href="?action=delete-menu&id=<?= $it['id'] ?>" onclick="return confirm('Excluir?')" class="w-6 h-6 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-400 flex items-center justify-center text-[9px]"><i class="fas fa-trash"></i></a>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
    <div class="card p-4">
        <h4 class="text-xs font-bold text-[#001644] mb-3 flex items-center gap-2"><i class="fas fa-plus text-[#BF8D1A]"></i> Adicionar novos itens</h4>
        <div id="nmRows" class="space-y-2">
            <div class="grid grid-cols-3 gap-2">
                <input type="text" name="new_loc[]" value="main" placeholder="main / footer / sidebar" class="si">
                <input type="text" name="new_title[]" placeholder="Nome do item" class="si">
                <input type="text" name="new_url[]" placeholder="/crcap/pages/..." class="si">
            </div>
        </div>
        <button type="button" onclick="addMRow()" class="mt-2 text-[10px] text-[#BF8D1A] hover:text-[#001644] font-bold flex items-center gap-1"><i class="fas fa-plus-circle"></i> Mais uma linha</button>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="tb tbp"><i class="fas fa-save"></i> Salvar menus</button>
        <a href="/crcap/admin/pages.php" class="tb tbg">Voltar</a>
    </div>
</form>
<script>function addMRow(){var r=document.createElement('div');r.className='grid grid-cols-3 gap-2';r.innerHTML='<input type="text" name="new_loc[]" value="main" class="si"><input type="text" name="new_title[]" placeholder="Nome" class="si"><input type="text" name="new_url[]" placeholder="/crcap/..." class="si">';document.getElementById('nmRows').appendChild(r);r.querySelectorAll('input')[1].focus();}</script>

<?php else: // ══ LIST VIEW ══
    $dbPages = dbFetchAll($pdo, "SELECT id,slug,title,status,views,menu_section,updated_at FROM pages ORDER BY menu_section,title");
    $dbMap = []; foreach ($dbPages as $p) $dbMap[$p['slug']] = $p;
    $total = array_sum(array_map('count', $systemPages));
?>
<style>
.pg{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:.75rem}
.pc{background:white;border-radius:1.25rem;border:1px solid rgba(0,22,68,.05);overflow:hidden;transition:all .2s;cursor:pointer;position:relative}
.pc:hover{border-color:rgba(191,141,26,.3);box-shadow:0 12px 28px rgba(0,22,68,.07);transform:translateY(-2px)}
.pct{padding:.875rem .875rem .75rem}.pci{width:2.1rem;height:2.1rem;border-radius:.65rem;background:rgba(0,22,68,.04);display:flex;align-items:center;justify-content:center;font-size:.75rem;color:#001644;margin-bottom:.65rem}
.pctit{font-size:.8rem;font-weight:700;color:#001644;line-height:1.3;margin-bottom:.2rem}
.pcb{display:flex;border-top:1px solid rgba(0,22,68,.04)}
.pcba{flex:1;padding:.55rem .5rem;display:flex;align-items:center;justify-content:center;gap:.3rem;font-size:.65rem;font-weight:700;text-decoration:none;transition:.15s;color:rgba(2,46,107,.45)}
.pcba:hover{background:#F8FAFC;color:#001644}
.pcba+.pcba{border-left:1px solid rgba(0,22,68,.04)}
.sec-hd{display:flex;align-items:center;gap:.6rem;margin-top:2rem;margin-bottom:.75rem;padding-bottom:.5rem;border-bottom:1px solid rgba(0,22,68,.06)}
.sec-hd h3{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(2,46,107,.4)}
.badge-ok{background:#006633;color:white;font-size:.55rem;font-weight:700;padding:.15rem .45rem;border-radius:9999px}
.badge-db{background:#BF8D1A;color:white;font-size:.55rem;font-weight:700;padding:.15rem .45rem;border-radius:9999px}
.custom-badge{background:#7c3aed;color:white;font-size:.55rem;font-weight:700;padding:.15rem .45rem;border-radius:9999px}
</style>

<div class="flex flex-wrap items-center gap-3 mb-5">
    <div>
        <h1 class="text-xl font-bold text-[#001644]">Gerenciar Páginas</h1>
        <p class="text-xs text-[#022E6B]/40 mt-0.5"><?= $total ?> páginas do sistema + <?= count($dbPages) ?> do banco</p>
    </div>
    <div class="ml-auto flex gap-2 flex-wrap">
        <a href="?action=menus" class="tb tbg"><i class="fas fa-bars text-[#BF8D1A]"></i> Menus</a>
        <a href="?action=new" class="tb tbp"><i class="fas fa-plus text-[9px]"></i> Nova Página</a>
    </div>
</div>

<?php if (isset($_GET['deleted'])): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-2.5 mb-4 flex items-center gap-2"><i class="fas fa-trash"></i> Página excluída.</div><?php endif; ?>

<!-- Páginas do sistema (PHP files) -->
<?php foreach ($systemPages as $secName => $pages): ?>
<div class="sec-hd"><h3><?= htmlspecialchars($secName) ?></h3><span class="text-[9px] text-[#022E6B]/25"><?= count($pages) ?> páginas</span></div>
<div class="pg">
<?php foreach ($pages as $pg):
    $db = $dbMap[$pg['slug']] ?? null;
    $hasContent = $db && !empty($db['content']);
    $status = $db['status'] ?? 'noBD';
?>
<div class="pc">
    <div class="pct">
        <div class="pci"><i class="fas <?= $pg['icon'] ?>"></i></div>
        <div class="pctit"><?= htmlspecialchars($pg['title']) ?></div>
        <div class="flex items-center gap-1.5 mt-1">
            <span class="text-[8px] font-mono text-[#022E6B]/25 truncate">/<?= htmlspecialchars($pg['file']) ?></span>
            <?php if ($db): ?>
                <span class="badge-ok ml-auto">BD</span>
                <?php if ($hasContent): ?><span class="badge-db">Conteúdo</span><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="pcb">
        <a href="?action=edit-slug&slug=<?= urlencode($pg['slug']) ?>" class="pcba"><i class="fas fa-edit text-[9px]"></i> Editar</a>
        <a href="<?= htmlspecialchars($pg['url']) ?>" target="_blank" class="pcba"><i class="fas fa-eye text-[9px]"></i> Ver</a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>

<!-- Páginas customizadas (apenas no BD, sem PHP file) -->
<?php
$customPages = array_filter($dbPages, fn($p) => !isset($slugMap[$p['slug']]));
if (!empty($customPages)):
?>
<div class="sec-hd mt-8"><h3>Páginas Personalizadas</h3><span class="text-[9px] text-[#022E6B]/25"><?= count($customPages) ?> página<?= count($customPages)!==1?'s':'' ?></span></div>
<div class="pg">
<?php foreach ($customPages as $pg): ?>
<div class="pc">
    <div class="pct">
        <div class="pci"><i class="fas fa-file-alt"></i></div>
        <div class="pctit"><?= htmlspecialchars($pg['title'] ?: '(sem título)') ?></div>
        <div class="flex items-center gap-1.5 mt-1">
            <span class="text-[8px] font-mono text-[#022E6B]/25">/pages/<?= htmlspecialchars($pg['slug']) ?></span>
            <span class="custom-badge ml-auto">Custom</span>
            <?php if ($pg['status'] === 'published'): ?><span class="badge-ok">Pub</span><?php endif; ?>
        </div>
    </div>
    <div class="pcb">
        <a href="?action=edit&id=<?= $pg['id'] ?>" class="pcba"><i class="fas fa-edit text-[9px]"></i> Editar</a>
        <a href="/crcap/pages/<?= htmlspecialchars($pg['slug']) ?>.php" target="_blank" class="pcba"><i class="fas fa-eye text-[9px]"></i> Ver</a>
        <a href="?action=delete&id=<?= $pg['id'] ?>" onclick="return confirm('Excluir?')" class="pcba" style="color:#ef4444"><i class="fas fa-trash text-[9px]"></i></a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; // end router ?>