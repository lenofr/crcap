<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle = 'Notícias e Comunicados · CRCAP';
$activeMenu = 'inicio';

$busca    = trim($_GET['busca'] ?? '');
$cat_slug = $_GET['categoria'] ?? '';
$page_num = max(1, (int)($_GET['pagina'] ?? 1));
$perPage  = 9;
$offset   = ($page_num - 1) * $perPage;

$cats = dbFetchAll($pdo, "SELECT * FROM categories WHERE status='active' ORDER BY order_position ASC");

$where  = ["p.status='published'"];
$params = [];
if ($busca)    { $where[] = "(p.title LIKE ? OR p.excerpt LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($cat_slug) { $where[] = "c.slug=?"; $params[] = $cat_slug; }

$sql   = "SELECT p.*, c.name AS cat_name, c.color AS cat_color, c.slug AS cat_slug,
           u.full_name AS author_name
          FROM posts p
          LEFT JOIN categories c ON p.category_id=c.id
          LEFT JOIN users u ON p.author_id=u.id
          WHERE ".implode(' AND ',$where)."
          ORDER BY p.is_featured DESC, p.published_at DESC
          LIMIT $perPage OFFSET $offset";
$posts = dbFetchAll($pdo, $sql, $params);

$total = dbFetch($pdo,
    "SELECT COUNT(*) AS t FROM posts p
     LEFT JOIN categories c ON p.category_id=c.id
     WHERE ".implode(' AND ',$where), $params)['t'] ?? 0;
$pages = ceil($total / $perPage);

// Posts exemplos quando BD vazio
$exPosts = [
    ['title'=>'Nova Resolução CFA redefine critérios de fiscalização','excerpt'=>'Alterações importantes entram em vigor no próximo mês, trazendo modernização aos processos de auditoria e fiscalização profissional no Brasil.','featured_image'=>'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=600&h=300&fit=crop','cat_name'=>'Fiscalização','cat_color'=>'#001644','slug'=>'nova-resolucao-fiscalizacao','published_at'=>'2026-02-20','is_featured'=>1,'views'=>1240,'author_name'=>'Redação CRCAP'],
    ['title'=>'Programa de Regularização 2026 com descontos especiais','excerpt'=>'Profissionais inadimplentes podem regularizar sua situação com até 80% de desconto em multas durante o período de campanha.','featured_image'=>'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=600&h=300&fit=crop','cat_name'=>'Comunicados','cat_color'=>'#BF8D1A','slug'=>'programa-regularizacao-2026','published_at'=>'2026-02-15','is_featured'=>0,'views'=>980,'author_name'=>'Assessoria CRCAP'],
    ['title'=>'CRCAP lança novo portal do profissional com funcionalidades avançadas','excerpt'=>'O novo sistema integrado permite acesso a carteira digital, emissão de certidões e histórico de pagamentos em uma única plataforma.','featured_image'=>'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&h=300&fit=crop','cat_name'=>'Tecnologia','cat_color'=>'#006633','slug'=>'novo-portal-profissional','published_at'=>'2026-02-10','is_featured'=>0,'views'=>870,'author_name'=>'TI CRCAP'],
    ['title'=>'Assembleia Geral Ordinária 2026 – convocação oficial','excerpt'=>'O Presidente do CRCAP convoca todos os profissionais inscritos para a Assembleia Geral Ordinária a realizar-se em março de 2026.','featured_image'=>'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600&h=300&fit=crop','cat_name'=>'Institucional','cat_color'=>'#022E6B','slug'=>'assembleia-geral-2026','published_at'=>'2026-02-05','is_featured'=>0,'views'=>720,'author_name'=>'Secretaria CRCAP'],
    ['title'=>'CFA divulga novas diretrizes para o Código de Ética do Administrador','excerpt'=>'Atualização do Código de Ética traz novas responsabilidades e esclarecimentos sobre condutas profissionais no ambiente digital.','featured_image'=>'https://images.unsplash.com/photo-1450101499163-c627a92ad1ab?w=600&h=300&fit=crop','cat_name'=>'Ética','cat_color'=>'#BF8D1A','slug'=>'novas-diretrizes-etica','published_at'=>'2026-01-28','is_featured'=>0,'views'=>640,'author_name'=>'Redação CRCAP'],
    ['title'=>'Resultado do Concurso Público nº 001/2025 – lista dos aprovados','excerpt'=>'O CRCAP divulga a lista final dos candidatos aprovados no Concurso Público para provimento de vagas no quadro permanente.','featured_image'=>'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=600&h=300&fit=crop','cat_name'=>'Concursos','cat_color'=>'#006633','slug'=>'resultado-concurso-2025','published_at'=>'2026-01-20','is_featured'=>0,'views'=>1580,'author_name'=>'RH CRCAP'],
    ['title'=>'Workshop de Planejamento Estratégico – inscrições abertas','excerpt'=>'O CRCAP realiza workshop exclusivo sobre planejamento estratégico para profissionais de administração do Amapá.','featured_image'=>'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&h=300&fit=crop','cat_name'=>'Eventos','cat_color'=>'#BF8D1A','slug'=>'workshop-planejamento','published_at'=>'2026-01-15','is_featured'=>0,'views'=>490,'author_name'=>'Eventos CRCAP'],
    ['title'=>'CRCAP firma parceria com universidades do Amapá para educação continuada','excerpt'=>'Acordo de cooperação técnica prevê descontos em pós-graduação, cursos de extensão e eventos acadêmicos para profissionais registrados.','featured_image'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&h=300&fit=crop','cat_name'=>'Educação','cat_color'=>'#001644','slug'=>'parceria-universidades','published_at'=>'2026-01-10','is_featured'=>0,'views'=>380,'author_name'=>'Presidência CRCAP'],
    ['title'=>'Balanço financeiro 2025: CRCAP encerra ano com superávit','excerpt'=>'O relatório financeiro anual demonstra equilíbrio fiscal e crescimento na arrecadação, resultado de uma gestão eficiente dos recursos.','featured_image'=>'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&h=300&fit=crop','cat_name'=>'Financeiro','cat_color'=>'#006633','slug'=>'balanco-financeiro-2025','published_at'=>'2026-01-05','is_featured'=>0,'views'=>610,'author_name'=>'Tesouraria CRCAP'],
];

include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 30% 50%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Notícias</span>
        </div>
        <div class="flex flex-col md:flex-row md:items-end gap-6">
            <div class="flex items-start gap-6 flex-1">
                <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-newspaper"></i></div>
                <div>
                    <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Notícias e Comunicados</h1>
                    <p class="text-white/70 text-sm max-w-2xl">Acompanhe as últimas notícias, comunicados oficiais e informações do CRCAP.</p>
                </div>
            </div>
            <form method="GET" class="flex gap-2 md:w-80">
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar notícias..."
                    class="flex-1 px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:border-[#BF8D1A] text-xs">
                <button class="px-4 py-2.5 bg-[#BF8D1A] rounded-xl hover:bg-white hover:text-[#001644] transition text-white"><i class="fas fa-search text-sm"></i></button>
            </form>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-4 gap-8">

        <!-- Sidebar -->
        <aside class="space-y-5">
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4 pb-3 border-b border-[#001644]/10">Categorias</h3>
                <nav class="space-y-1">
                    <a href="/crcap/pages/noticias.php" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition <?= !$cat_slug ? 'bg-[#001644] text-white' : 'text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                        <span>Todas as categorias</span>
                        <span class="text-[9px] opacity-60"><?= $total ?></span>
                    </a>
                    <?php
                    $displayCats = !empty($cats) ? $cats : [
                        ['name'=>'Notícias','slug'=>'noticias','color'=>'#001644'],
                        ['name'=>'Fiscalização','slug'=>'fiscalizacao','color'=>'#022E6B'],
                        ['name'=>'Eventos','slug'=>'eventos','color'=>'#BF8D1A'],
                        ['name'=>'Governança','slug'=>'governanca','color'=>'#006633'],
                        ['name'=>'Comunicados','slug'=>'comunicados','color'=>'#001644'],
                    ];
                    foreach ($displayCats as $cat): ?>
                    <a href="?categoria=<?= urlencode($cat['slug']) ?>" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs transition <?= $cat_slug===$cat['slug'] ? 'bg-[#001644] text-white font-semibold' : 'text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:<?= htmlspecialchars($cat['color']) ?>"></span>
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!-- Mais lidos -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4 pb-3 border-b border-[#001644]/10">Mais Lidos</h3>
                <?php
                $topPosts = dbFetchAll($pdo, "SELECT title, slug, views, published_at FROM posts WHERE status='published' ORDER BY views DESC LIMIT 5");
                if (empty($topPosts)) $topPosts = array_slice($exPosts, 0, 5);
                foreach ($topPosts as $i => $tp): ?>
                <a href="/crcap/pages/post.php?slug=<?= urlencode($tp['slug']) ?>" class="flex gap-3 mb-4 group">
                    <span class="text-2xl font-bold font-serif text-[#001644]/15 w-6 flex-shrink-0 leading-none"><?= $i+1 ?></span>
                    <div>
                        <h4 class="text-xs font-semibold text-[#001644] group-hover:text-[#BF8D1A] transition line-clamp-2 leading-snug"><?= htmlspecialchars($tp['title']) ?></h4>
                        <span class="text-[9px] text-[#022E6B] mt-1 block"><?= date('d/m/Y', strtotime($tp['published_at'])) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Newsletter lateral -->
            <div class="bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-5 text-white">
                <i class="fas fa-envelope text-2xl text-[#BF8D1A] mb-3 block"></i>
                <h3 class="font-bold text-sm mb-2">Fique por dentro!</h3>
                <p class="text-[10px] text-white/70 mb-4">Receba as notícias do CRCAP direto no seu e-mail.</p>
                <form method="POST" action="/index.php" class="space-y-2">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="email" name="newsletter_email" placeholder="seu@email.com" required
                        class="w-full px-3 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:border-[#BF8D1A] text-xs">
                    <button type="submit" class="w-full py-2.5 bg-[#BF8D1A] text-white font-bold rounded-xl hover:bg-white hover:text-[#001644] transition text-xs">Inscrever-se</button>
                </form>
            </div>
        </aside>

        <!-- Conteúdo principal -->
        <div class="lg:col-span-3">
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-[#022E6B]">
                    <strong class="text-[#001644]"><?= empty($posts) ? count($exPosts) : $total ?></strong> notícia(s) encontrada(s)
                    <?php if ($busca): ?>— buscando por "<em><?= htmlspecialchars($busca) ?></em>"<?php endif; ?>
                </p>
                <?php if ($busca || $cat_slug): ?>
                <a href="/crcap/pages/noticias.php" class="text-xs text-[#BF8D1A] hover:underline flex items-center gap-1"><i class="fas fa-times text-[9px]"></i>Limpar</a>
                <?php endif; ?>
            </div>

            <?php $displayPosts = !empty($posts) ? $posts : $exPosts; ?>

            <!-- Post destaque (primeiro) -->
            <?php if (!empty($displayPosts)): $featured = $displayPosts[0]; ?>
            <a href="/crcap/pages/post.php?slug=<?= urlencode($featured['slug']) ?>" class="block bg-white rounded-2xl overflow-hidden border border-[#001644]/3 shadow-sm hover:shadow-xl hover:shadow-[#001644]/10 hover:border-[#BF8D1A]/20 transition mb-6 group">
                <div class="grid md:grid-cols-2">
                    <div class="relative h-56 md:h-auto overflow-hidden">
                        <img src="<?= htmlspecialchars($featured['featured_image'] ?: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=700&h=400&fit=crop') ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div class="absolute top-3 left-3">
                            <span class="bg-[#BF8D1A] text-white text-[9px] font-bold px-2 py-0.5 rounded-full"><i class="fas fa-star mr-1"></i>Destaque</span>
                        </div>
                    </div>
                    <div class="p-6 flex flex-col justify-center">
                        <?php if ($featured['cat_name']): ?>
                        <span class="text-[10px] font-bold uppercase tracking-wider mb-2 block" style="color:<?= $featured['cat_color'] ?? '#BF8D1A' ?>"><?= htmlspecialchars($featured['cat_name']) ?></span>
                        <?php endif; ?>
                        <h2 class="font-serif text-xl font-bold text-[#001644] mb-3 group-hover:text-[#BF8D1A] transition leading-snug"><?= htmlspecialchars($featured['title']) ?></h2>
                        <p class="text-xs text-[#022E6B] leading-relaxed mb-4 line-clamp-3"><?= htmlspecialchars($featured['excerpt'] ?? '') ?></p>
                        <div class="flex items-center gap-3 text-[10px] text-[#022E6B]">
                            <span class="flex items-center gap-1"><i class="fas fa-calendar text-[#BF8D1A]"></i><?= date('d/m/Y', strtotime($featured['published_at'])) ?></span>
                            <?php if ($featured['author_name']): ?><span class="flex items-center gap-1"><i class="fas fa-user text-[#BF8D1A]"></i><?= htmlspecialchars($featured['author_name']) ?></span><?php endif; ?>
                            <?php if ($featured['views']): ?><span class="flex items-center gap-1"><i class="fas fa-eye text-[#BF8D1A]"></i><?= number_format($featured['views']) ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Grid demais posts -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
                <?php foreach (array_slice($displayPosts, 1) as $post): ?>
                <a href="/crcap/pages/post.php?slug=<?= urlencode($post['slug']) ?>" class="bg-white rounded-2xl overflow-hidden border border-[#001644]/3 shadow-sm hover:-translate-y-1.5 hover:shadow-lg hover:border-[#BF8D1A]/20 transition group flex flex-col">
                    <div class="relative h-40 overflow-hidden flex-shrink-0">
                        <img src="<?= htmlspecialchars($post['featured_image'] ?: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=400&h=200&fit=crop') ?>" alt="" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </div>
                    <div class="p-4 flex flex-col flex-1">
                        <?php if ($post['cat_name']): ?>
                        <span class="text-[9px] font-bold uppercase tracking-wider mb-1 block" style="color:<?= $post['cat_color'] ?? '#BF8D1A' ?>"><?= htmlspecialchars($post['cat_name']) ?></span>
                        <?php endif; ?>
                        <h3 class="font-bold text-[#001644] text-sm mb-2 group-hover:text-[#BF8D1A] transition line-clamp-2 leading-snug flex-1"><?= htmlspecialchars($post['title']) ?></h3>
                        <p class="text-[10px] text-[#022E6B] line-clamp-2 mb-3"><?= htmlspecialchars($post['excerpt'] ?? '') ?></p>
                        <div class="flex items-center justify-between text-[9px] text-[#022E6B] mt-auto">
                            <span><?= date('d/m/Y', strtotime($post['published_at'])) ?></span>
                            <?php if ($post['views']): ?><span class="flex items-center gap-1"><i class="fas fa-eye text-[#BF8D1A]"></i><?= number_format($post['views']) ?></span><?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Paginação -->
            <?php if ($pages > 1): ?>
            <div class="flex justify-center gap-2">
                <?php if ($page_num > 1): ?><a href="?pagina=<?= $page_num-1 ?>&busca=<?= urlencode($busca) ?>&categoria=<?= urlencode($cat_slug) ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                <?php for ($i=1; $i<=$pages; $i++): ?>
                <a href="?pagina=<?= $i ?>&busca=<?= urlencode($busca) ?>&categoria=<?= urlencode($cat_slug) ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold transition <?= $i===$page_num ? 'bg-[#001644] text-white' : 'bg-white text-[#001644] border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A]' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page_num < $pages): ?><a href="?pagina=<?= $page_num+1 ?>&busca=<?= urlencode($busca) ?>&categoria=<?= urlencode($cat_slug) ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
