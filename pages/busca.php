<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$q       = trim($_GET['q'] ?? '');
$type    = $_GET['tipo'] ?? 'all';
$pg      = max(1, (int)($_GET['p'] ?? 1));
$perPage = 12;
$offset  = ($pg - 1) * $perPage;

$results = [];
$total   = 0;

if (strlen($q) >= 3) {
    $like = "%$q%";

    if ($type === 'all' || $type === 'posts') {
        $posts = dbFetchAll($pdo,
            "SELECT 'post' AS _type, p.id, p.title, p.slug, p.excerpt AS description,
                    p.featured_image, p.published_at AS date, c.name AS category
             FROM posts p LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status='published' AND (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)
             ORDER BY p.published_at DESC LIMIT 50",
            [$like, $like, $like]);
        $results = array_merge($results, $posts);
    }

    if ($type === 'all' || $type === 'events') {
        $events = dbFetchAll($pdo,
            "SELECT 'event' AS _type, id, title, slug, description,
                    featured_image, event_date AS date, event_type AS category
             FROM events
             WHERE status='published' AND (title LIKE ? OR description LIKE ?)
             ORDER BY event_date DESC LIMIT 50",
            [$like, $like]);
        $results = array_merge($results, $events);
    }

    if ($type === 'all' || $type === 'documents') {
        $docs = dbFetchAll($pdo,
            "SELECT 'document' AS _type, id, title, NULL AS slug,
                    description, NULL AS featured_image, publication_date AS date,
                    category AS category
             FROM documents
             WHERE is_public=1 AND status='active' AND (title LIKE ? OR description LIKE ?)
             ORDER BY publication_date DESC LIMIT 50",
            [$like, $like]);
        $results = array_merge($results, $docs);
    }

    if ($type === 'all' || $type === 'pages') {
        $pages = dbFetchAll($pdo,
            "SELECT 'page' AS _type, id, title, slug, NULL AS description,
                    featured_image, updated_at AS date, menu_section AS category
             FROM pages
             WHERE status='published' AND visibility='public' AND (title LIKE ? OR content LIKE ?)
             ORDER BY title LIMIT 50",
            [$like, $like]);
        $results = array_merge($results, $pages);
    }

    $total = count($results);
    $results = array_slice($results, $offset, $perPage);
}

$pages_count = $total > 0 ? ceil($total / $perPage) : 0;

$pageTitle  = $q ? "Busca: \"$q\" · CRCAP" : 'Busca · CRCAP';
$activeMenu = '';
include __DIR__ . '/../includes/header.php';

// Type labels
$typeLabels = [
    'post'     => ['fa-newspaper',  'Notícia',   'bg-[#001644]/10 text-[#001644]'],
    'event'    => ['fa-calendar',   'Evento',    'bg-[#006633]/10 text-[#006633]'],
    'document' => ['fa-file-pdf',   'Documento', 'bg-red-50 text-red-600'],
    'page'     => ['fa-file-alt',   'Página',    'bg-[#BF8D1A]/10 text-[#BF8D1A]'],
];

function resultUrl($r) {
    return match($r['_type']) {
        'post'     => '/pages/post.php?slug='.urlencode($r['slug']),
        'event'    => '/pages/evento.php?slug='.urlencode($r['slug']),
        'document' => '/pages/download.php?id='.$r['id'],
        'page'     => '/pages/'.($r['slug'] ? h($r['slug']).'.php' : ''),
        default    => '#',
    };
}
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 60% 50%,#BF8D1A 0%,transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-2xl mx-auto text-center mb-8">
            <h1 class="font-serif text-3xl font-bold mb-2">Busca</h1>
            <p class="text-white/70 text-sm">Pesquise em notícias, eventos, documentos e páginas</p>
        </div>
        <form method="GET" class="max-w-2xl mx-auto">
            <div class="flex gap-2">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-white/40"></i>
                    <input type="text" name="q" value="<?= h($q) ?>"
                           placeholder="Digite sua busca..."
                           class="w-full pl-11 pr-4 py-3.5 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:border-[#BF8D1A] text-sm"
                           autofocus>
                </div>
                <button type="submit" class="px-6 py-3.5 bg-[#BF8D1A] text-white font-semibold rounded-xl hover:bg-white hover:text-[#001644] transition text-sm">
                    Buscar
                </button>
            </div>
            <!-- Filter tabs -->
            <div class="flex flex-wrap gap-2 mt-4 justify-center">
                <?php foreach (['all'=>'Todos','posts'=>'Notícias','events'=>'Eventos','documents'=>'Documentos','pages'=>'Páginas'] as $v=>$l): ?>
                <a href="?q=<?= urlencode($q) ?>&tipo=<?= $v ?>"
                   class="px-3 py-1.5 rounded-full text-xs font-semibold transition
                   <?= $type===$v ? 'bg-[#BF8D1A] text-white' : 'bg-white/10 text-white/70 hover:bg-white/20' ?>">
                    <?= $l ?>
                </a>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</section>

<main class="container mx-auto px-4 py-10">

    <?php if (strlen($q) >= 3): ?>
    <!-- Results header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-[#022E6B]">
                <?php if ($total > 0): ?>
                    <span class="font-bold text-[#001644]"><?= number_format($total) ?></span> resultado<?= $total !== 1 ? 's' : '' ?> para
                    <span class="font-bold text-[#BF8D1A]">"<?= h($q) ?>"</span>
                <?php else: ?>
                    Nenhum resultado para <span class="font-bold text-[#BF8D1A]">"<?= h($q) ?>"</span>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($total > 0): ?>
        <p class="text-xs text-[#022E6B]">Página <?= $pg ?> de <?= $pages_count ?></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($results)): ?>
    <div class="space-y-4 mb-8">
        <?php foreach ($results as $r):
            $tl   = $typeLabels[$r['_type']] ?? ['fa-file','Item','bg-gray-100 text-gray-600'];
            $url  = resultUrl($r);
            $date = $r['date'] ? date('d/m/Y', strtotime($r['date'])) : '';
        ?>
        <a href="<?= $url ?>" <?= $r['_type']==='document'?'':''; ?>
           class="flex gap-5 bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm hover:shadow-md hover:border-[#BF8D1A]/30 transition group">

            <!-- Thumbnail -->
            <?php if ($r['featured_image']): ?>
            <img src="<?= h($r['featured_image']) ?>" alt="" class="w-20 h-20 rounded-xl object-cover flex-shrink-0">
            <?php else: ?>
            <div class="w-20 h-20 rounded-xl bg-[#F8FAFC] border border-[#001644]/5 flex items-center justify-center flex-shrink-0">
                <i class="fas <?= $tl[0] ?> text-[#001644]/30 text-2xl"></i>
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $tl[2] ?>"><?= $tl[1] ?></span>
                    <?php if ($r['category']): ?>
                    <span class="text-[10px] text-[#022E6B]"><?= h($r['category']) ?></span>
                    <?php endif; ?>
                    <?php if ($date): ?>
                    <span class="text-[10px] text-[#022E6B] ml-auto"><?= $date ?></span>
                    <?php endif; ?>
                </div>
                <h3 class="font-bold text-[#001644] text-sm mb-1 group-hover:text-[#BF8D1A] transition line-clamp-1">
                    <?= h($r['title']) ?>
                </h3>
                <?php if ($r['description']): ?>
                <p class="text-xs text-[#022E6B] line-clamp-2 leading-relaxed">
                    <?= h(strip_tags($r['description'])) ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="flex-shrink-0 self-center text-[#001644]/20 group-hover:text-[#BF8D1A] transition">
                <i class="fas <?= $r['_type']==='document' ? 'fa-download' : 'fa-arrow-right' ?>"></i>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages_count > 1): ?>
    <div class="flex items-center justify-center gap-2">
        <?php if ($pg > 1): ?>
        <a href="?q=<?= urlencode($q) ?>&tipo=<?= $type ?>&p=<?= $pg-1 ?>" class="w-10 h-10 rounded-xl bg-white border border-[#001644]/10 flex items-center justify-center hover:border-[#BF8D1A] transition">
            <i class="fas fa-chevron-left text-xs text-[#001644]"></i>
        </a>
        <?php endif; ?>
        <?php for ($i = max(1,$pg-2); $i <= min($pages_count,$pg+2); $i++): ?>
        <a href="?q=<?= urlencode($q) ?>&tipo=<?= $type ?>&p=<?= $i ?>"
           class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-semibold transition
                  <?= $i===$pg ? 'bg-[#001644] text-white' : 'bg-white border border-[#001644]/10 text-[#001644] hover:border-[#BF8D1A]' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
        <?php if ($pg < $pages_count): ?>
        <a href="?q=<?= urlencode($q) ?>&tipo=<?= $type ?>&p=<?= $pg+1 ?>" class="w-10 h-10 rounded-xl bg-white border border-[#001644]/10 flex items-center justify-center hover:border-[#BF8D1A] transition">
            <i class="fas fa-chevron-right text-xs text-[#001644]"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- No results -->
    <div class="text-center py-16">
        <div class="w-20 h-20 rounded-full bg-[#001644]/5 flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-search text-3xl text-[#001644]/20"></i>
        </div>
        <h2 class="text-lg font-bold text-[#001644] mb-2">Nenhum resultado encontrado</h2>
        <p class="text-sm text-[#022E6B] mb-6 max-w-md mx-auto">
            Não encontramos nada para <strong>"<?= h($q) ?>"</strong>. Tente palavras diferentes ou mais gerais.
        </p>
        <div class="flex flex-wrap gap-2 justify-center text-xs">
            <span class="text-[#022E6B]">Sugestões:</span>
            <?php foreach (['Notícias','Editais','Eventos','Ouvidoria','Cursos'] as $sug): ?>
            <a href="?q=<?= urlencode(strtolower($sug)) ?>" class="px-3 py-1.5 bg-white border border-[#001644]/10 rounded-full text-[#001644] hover:border-[#BF8D1A] transition">
                <?= $sug ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif (strlen($q) > 0 && strlen($q) < 3): ?>
    <div class="text-center py-10 text-sm text-[#022E6B]">
        <i class="fas fa-info-circle text-[#BF8D1A] text-xl mb-2 block"></i>
        Digite pelo menos <strong>3 caracteres</strong> para buscar.
    </div>

    <?php else: ?>
    <!-- Initial state -->
    <div class="max-w-2xl mx-auto">
        <h2 class="font-bold text-[#001644] text-lg mb-6 text-center">Pesquisas frequentes</h2>
        <div class="grid sm:grid-cols-2 gap-3">
            <?php foreach ([
                ['fa-file-invoice','Anuidade','anuidade'],
                ['fa-certificate','Certidão','certidão'],
                ['fa-graduation-cap','Cursos','cursos'],
                ['fa-gavel','Editais','editais'],
                ['fa-calendar','Eventos','eventos'],
                ['fa-search','Fiscalização','fiscalização'],
                ['fa-file-alt','Resolução','resolução'],
                ['fa-user-check','Registro','registro'],
            ] as $s): ?>
            <a href="?q=<?= urlencode($s[2]) ?>" class="flex items-center gap-3 p-4 bg-white rounded-xl border border-[#001644]/5 hover:border-[#BF8D1A] hover:shadow-md transition group">
                <div class="w-10 h-10 rounded-xl bg-[#001644]/5 flex items-center justify-center group-hover:bg-[#BF8D1A] group-hover:text-white transition">
                    <i class="fas <?= $s[0] ?> text-sm text-[#001644] group-hover:text-white"></i>
                </div>
                <span class="font-semibold text-sm text-[#001644] group-hover:text-[#BF8D1A] transition"><?= $s[1] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
