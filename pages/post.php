<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: /crcap/pages/noticias.php'); exit; }

$post = dbFetch($pdo,
    "SELECT p.*, c.name AS cat_name, c.color AS cat_color, c.slug AS cat_slug,
            u.full_name AS author_name, u.avatar AS author_avatar
     FROM posts p
     LEFT JOIN categories c ON p.category_id=c.id
     LEFT JOIN users u ON p.author_id=u.id
     WHERE p.slug=? AND p.status='published' LIMIT 1", [$slug]);

if (!$post) { http_response_code(404); include '../includes/header.php'; ?>
<main class="container mx-auto px-4 py-24 text-center">
    <i class="fas fa-exclamation-circle text-5xl text-[#001644]/20 mb-4 block"></i>
    <h1 class="text-2xl font-bold text-[#001644] mb-2">Notícia não encontrada</h1>
    <p class="text-[#022E6B] mb-6">A notícia que você procura não existe ou foi removida.</p>
    <a href="/crcap/pages/noticias.php" class="px-6 py-3 bg-[#001644] text-white rounded-xl text-sm font-semibold hover:bg-[#022E6B] transition">Ver todas as notícias</a>
</main>
<?php include '../includes/footer.php'; exit; }

// Incrementa views
dbExec($pdo, "UPDATE posts SET views=views+1 WHERE id=?", [$post['id']]);

// Tags
$tags = dbFetchAll($pdo, "SELECT t.name, t.slug FROM tags t JOIN post_tags pt ON t.id=pt.tag_id WHERE pt.post_id=?", [$post['id']]);

// Posts relacionados
$related = dbFetchAll($pdo,
    "SELECT id, title, slug, featured_image, published_at FROM posts
     WHERE status='published' AND category_id=? AND id!=? ORDER BY published_at DESC LIMIT 3",
    [$post['category_id'], $post['id']]);

$pageTitle  = htmlspecialchars($post['seo_title'] ?: $post['title']).' · CRCAP';
$activeMenu = 'inicio';

include '../includes/header.php';
?>

<!-- Breadcrumb + Meta -->
<div class="bg-[#F8FAFC] border-b border-[#001644]/5 py-3">
    <div class="container mx-auto px-4 flex items-center gap-2 text-xs text-[#022E6B]">
        <a href="/crcap/index.php" class="hover:text-[#BF8D1A] transition">Início</a>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <a href="/crcap/pages/noticias.php" class="hover:text-[#BF8D1A] transition">Notícias</a>
        <?php if ($post['cat_name']): ?>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <a href="/crcap/pages/noticias.php?categoria=<?= urlencode($post['cat_slug']) ?>" class="hover:text-[#BF8D1A] transition"><?= htmlspecialchars($post['cat_name']) ?></a>
        <?php endif; ?>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <span class="text-[#001644] font-medium truncate max-w-xs"><?= htmlspecialchars($post['title']) ?></span>
    </div>
</div>

<main class="container mx-auto px-4 py-10">
    <div class="grid lg:grid-cols-4 gap-10">

        <!-- Artigo -->
        <article class="lg:col-span-3">

            <!-- Cabeçalho -->
            <div class="mb-8">
                <?php if ($post['cat_name']): ?>
                <a href="/crcap/pages/noticias.php?categoria=<?= urlencode($post['cat_slug']) ?>" class="inline-block text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full text-white mb-4" style="background:<?= $post['cat_color'] ?? '#BF8D1A' ?>">
                    <?= htmlspecialchars($post['cat_name']) ?>
                </a>
                <?php endif; ?>
                <h1 class="font-serif text-3xl md:text-4xl font-bold text-[#001644] mb-4 leading-tight"><?= htmlspecialchars($post['title']) ?></h1>
                <?php if ($post['excerpt']): ?>
                <p class="text-base text-[#022E6B] leading-relaxed font-medium mb-5 border-l-4 border-[#BF8D1A] pl-4"><?= htmlspecialchars($post['excerpt']) ?></p>
                <?php endif; ?>

                <!-- Meta info -->
                <div class="flex flex-wrap items-center gap-4 text-xs text-[#022E6B] pb-5 border-b border-[#001644]/10">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-xs font-bold">
                            <?= strtoupper(substr($post['author_name'] ?? 'C', 0, 1)) ?>
                        </div>
                        <span class="font-semibold text-[#001644]"><?= htmlspecialchars($post['author_name'] ?? 'Redação CRCAP') ?></span>
                    </div>
                    <span class="flex items-center gap-1"><i class="fas fa-calendar text-[#BF8D1A]"></i><?= date('d \d\e F \d\e Y', strtotime($post['published_at'])) ?></span>
                    <span class="flex items-center gap-1"><i class="fas fa-eye text-[#BF8D1A]"></i><?= number_format($post['views']) ?> visualizações</span>
                    <?php if ($post['updated_at'] !== $post['created_at']): ?>
                    <span class="flex items-center gap-1 text-[#022E6B]/60"><i class="fas fa-edit text-[9px]"></i>Atualizado em <?= date('d/m/Y', strtotime($post['updated_at'])) ?></span>
                    <?php endif; ?>
                    <!-- Compartilhar -->
                    <div class="ml-auto flex items-center gap-2">
                        <span class="text-[10px] text-[#022E6B]">Compartilhar:</span>
                        <a href="https://facebook.com/sharer/sharer.php?u=<?= urlencode('https://crcap.org.br/pages/post.php?slug='.$slug) ?>" target="_blank" class="w-7 h-7 rounded-lg bg-[#1877F2] text-white flex items-center justify-center text-xs hover:opacity-80 transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://crcap.org.br/pages/post.php?slug='.$slug) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="w-7 h-7 rounded-lg bg-[#1DA1F2] text-white flex items-center justify-center text-xs hover:opacity-80 transition"><i class="fab fa-twitter"></i></a>
                        <a href="https://api.whatsapp.com/send?text=<?= urlencode($post['title'].' - https://crcap.org.br/pages/post.php?slug='.$slug) ?>" target="_blank" class="w-7 h-7 rounded-lg bg-[#25D366] text-white flex items-center justify-center text-xs hover:opacity-80 transition"><i class="fab fa-whatsapp"></i></a>
                        <button onclick="navigator.clipboard.writeText(window.location.href);this.innerHTML='<i class=\'fas fa-check\'></i>';setTimeout(()=>this.innerHTML='<i class=\'fas fa-link\'></i>',2000)" class="w-7 h-7 rounded-lg bg-[#001644] text-white flex items-center justify-center text-xs hover:bg-[#BF8D1A] transition"><i class="fas fa-link"></i></button>
                    </div>
                </div>
            </div>

            <!-- Imagem principal -->
            <?php if ($post['featured_image']): ?>
            <figure class="mb-8 rounded-2xl overflow-hidden">
                <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full max-h-[480px] object-cover">
            </figure>
            <?php endif; ?>

            <!-- Conteúdo -->
            <div class="prose prose-sm max-w-none text-[#022E6B] leading-relaxed mb-8
                        prose-headings:text-[#001644] prose-headings:font-bold
                        prose-a:text-[#BF8D1A] prose-a:no-underline hover:prose-a:underline
                        prose-blockquote:border-[#BF8D1A] prose-blockquote:text-[#022E6B]
                        prose-strong:text-[#001644]">
                <?= $post['content'] ?: '<p>Conteúdo não disponível.</p>' ?>
            </div>


            <!-- Galeria de Fotos -->
            <?php
            $galleryImgs = [];
            if (!empty($post['gallery'])) {
                $galleryImgs = json_decode($post['gallery'], true) ?: [];
            }
            if (!empty($galleryImgs)): ?>
            <div class="mb-8 pb-8 border-b border-[#001644]/10">
                <h3 class="font-bold text-[#001644] text-base mb-4 flex items-center gap-2">
                    <i class="fas fa-images text-[#BF8D1A]"></i>
                    Galeria de Fotos
                    <span class="text-xs font-normal text-[#022E6B]/60">(<?= count($galleryImgs) ?> foto<?= count($galleryImgs) > 1 ? 's' : '' ?>)</span>
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach ($galleryImgs as $idx => $img): ?>
                    <button onclick="openLightbox(<?= $idx ?>)"
                            class="relative group aspect-square rounded-xl overflow-hidden border border-[#001644]/5 hover:border-[#BF8D1A]/40 transition hover:shadow-lg focus:outline-none">
                        <img src="<?= htmlspecialchars($img) ?>" alt="Foto <?= $idx + 1 ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div class="absolute inset-0 bg-[#001644]/0 group-hover:bg-[#001644]/20 transition flex items-center justify-center">
                            <i class="fas fa-expand text-white opacity-0 group-hover:opacity-100 transition text-lg drop-shadow"></i>
                        </div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Lightbox -->
            <div id="lightbox" class="hidden fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4" onclick="closeLightbox()">
                <button onclick="closeLightbox()" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition z-10">
                    <i class="fas fa-times"></i>
                </button>
                <button onclick="event.stopPropagation();prevPhoto()" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition z-10">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button onclick="event.stopPropagation();nextPhoto()" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition z-10 mr-14">
                    <i class="fas fa-chevron-left fa-flip-horizontal"></i>
                </button>
                <div onclick="event.stopPropagation()" class="relative max-w-4xl max-h-[85vh] w-full flex items-center justify-center">
                    <img id="lightboxImg" src="" alt="" class="max-w-full max-h-[85vh] rounded-xl object-contain shadow-2xl">
                    <div class="absolute bottom-0 left-0 right-0 text-center pb-3 pt-8 bg-gradient-to-t from-black/60 to-transparent rounded-b-xl">
                        <span id="lightboxCounter" class="text-white/80 text-xs"></span>
                    </div>
                </div>
            </div>
            <script>
            const _photos = <?= json_encode($galleryImgs) ?>;
            let _cur = 0;
            function openLightbox(idx) {
                _cur = idx;
                document.getElementById('lightboxImg').src = _photos[_cur];
                document.getElementById('lightboxCounter').textContent = (_cur+1) + ' / ' + _photos.length;
                document.getElementById('lightbox').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
            function closeLightbox() {
                document.getElementById('lightbox').classList.add('hidden');
                document.body.style.overflow = '';
            }
            function prevPhoto() { _cur = (_cur - 1 + _photos.length) % _photos.length; openLightbox(_cur); }
            function nextPhoto() { _cur = (_cur + 1) % _photos.length; openLightbox(_cur); }
            document.addEventListener('keydown', function(e) {
                if (document.getElementById('lightbox').classList.contains('hidden')) return;
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') prevPhoto();
                if (e.key === 'ArrowRight') nextPhoto();
            });
            </script>
            <?php endif; ?>

            <!-- Tags -->
            <?php if (!empty($tags)): ?>
            <div class="flex flex-wrap gap-2 mb-8 pb-8 border-b border-[#001644]/10">
                <span class="text-xs font-semibold text-[#022E6B]">Tags:</span>
                <?php foreach ($tags as $tag): ?>
                <a href="/crcap/pages/noticias.php?tag=<?= urlencode($tag['slug']) ?>" class="px-3 py-1 bg-[#F8FAFC] border border-[#001644]/10 text-[#022E6B] rounded-full text-xs hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition">
                    #<?= htmlspecialchars($tag['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Navegação Anterior/Próximo -->
            <?php
            $prev = dbFetch($pdo, "SELECT title, slug FROM posts WHERE status='published' AND published_at < ? ORDER BY published_at DESC LIMIT 1", [$post['published_at']]);
            $next = dbFetch($pdo, "SELECT title, slug FROM posts WHERE status='published' AND published_at > ? ORDER BY published_at ASC LIMIT 1", [$post['published_at']]);
            ?>
            <div class="grid sm:grid-cols-2 gap-4 mb-10">
                <?php if ($prev): ?>
                <a href="/crcap/pages/post.php?slug=<?= urlencode($prev['slug']) ?>" class="group flex items-center gap-3 p-4 bg-white rounded-2xl border border-[#001644]/5 hover:border-[#BF8D1A]/30 hover:shadow-md transition">
                    <i class="fas fa-chevron-left text-[#BF8D1A] text-lg flex-shrink-0"></i>
                    <div><span class="text-[9px] text-[#022E6B] font-semibold uppercase tracking-wider">Anterior</span><p class="text-xs font-semibold text-[#001644] group-hover:text-[#BF8D1A] transition line-clamp-2"><?= htmlspecialchars($prev['title']) ?></p></div>
                </a>
                <?php else: ?><div></div><?php endif; ?>
                <?php if ($next): ?>
                <a href="/crcap/pages/post.php?slug=<?= urlencode($next['slug']) ?>" class="group flex items-center gap-3 p-4 bg-white rounded-2xl border border-[#001644]/5 hover:border-[#BF8D1A]/30 hover:shadow-md transition text-right">
                    <div class="flex-1"><span class="text-[9px] text-[#022E6B] font-semibold uppercase tracking-wider">Próximo</span><p class="text-xs font-semibold text-[#001644] group-hover:text-[#BF8D1A] transition line-clamp-2"><?= htmlspecialchars($next['title']) ?></p></div>
                    <i class="fas fa-chevron-right text-[#BF8D1A] text-lg flex-shrink-0"></i>
                </a>
                <?php endif; ?>
            </div>

            <!-- Relacionados -->
            <?php if (!empty($related)): ?>
            <div>
                <h3 class="font-bold text-[#001644] text-lg mb-5 flex items-center gap-2"><i class="fas fa-layer-group text-[#BF8D1A]"></i>Notícias Relacionadas</h3>
                <div class="grid sm:grid-cols-3 gap-4">
                    <?php foreach ($related as $r): ?>
                    <a href="/crcap/pages/post.php?slug=<?= urlencode($r['slug']) ?>" class="bg-white rounded-xl overflow-hidden border border-[#001644]/3 hover:border-[#BF8D1A]/30 hover:shadow-md transition group">
                        <div class="h-32 overflow-hidden"><img src="<?= htmlspecialchars($r['featured_image'] ?: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=300&h=200&fit=crop') ?>" alt="" class="w-full h-full object-cover group-hover:scale-110 transition duration-500"></div>
                        <div class="p-3"><h4 class="text-xs font-semibold text-[#001644] group-hover:text-[#BF8D1A] transition line-clamp-2 leading-snug"><?= htmlspecialchars($r['title']) ?></h4><span class="text-[9px] text-[#022E6B] mt-1 block"><?= date('d/m/Y', strtotime($r['published_at'])) ?></span></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </article>

        <!-- Sidebar -->
        <aside class="space-y-5">
            <!-- Últimas notícias -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4 pb-3 border-b border-[#001644]/10">Últimas Notícias</h3>
                <?php $latest = dbFetchAll($pdo, "SELECT title, slug, published_at, featured_image FROM posts WHERE status='published' AND id!=? ORDER BY published_at DESC LIMIT 5", [$post['id']]);
                if (empty($latest)) $latest = [
                    ['title'=>'Programa de Regularização 2026','slug'=>'#','published_at'=>'2026-02-15','featured_image'=>'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=100&h=100&fit=crop'],
                    ['title'=>'Novo portal do profissional lançado','slug'=>'#','published_at'=>'2026-02-10','featured_image'=>'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=100&h=100&fit=crop'],
                    ['title'=>'Convocação Assembleia Geral 2026','slug'=>'#','published_at'=>'2026-02-05','featured_image'=>'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=100&h=100&fit=crop'],
                ];
                foreach ($latest as $l): ?>
                <a href="/crcap/pages/post.php?slug=<?= urlencode($l['slug']) ?>" class="flex gap-3 mb-3 last:mb-0 group">
                    <img src="<?= htmlspecialchars($l['featured_image'] ?: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=100&h=100&fit=crop') ?>" alt="" class="w-14 h-14 rounded-lg object-cover flex-shrink-0">
                    <div><h4 class="text-xs font-semibold text-[#001644] group-hover:text-[#BF8D1A] transition line-clamp-2 leading-snug"><?= htmlspecialchars($l['title']) ?></h4><span class="text-[9px] text-[#022E6B] mt-0.5 block"><?= date('d/m/Y', strtotime($l['published_at'])) ?></span></div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Próximos Eventos na sidebar -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4 pb-3 border-b border-[#001644]/10">Próximos Eventos</h3>
                <?php $sideEvents = dbFetchAll($pdo, "SELECT title, slug, event_date, start_time FROM events WHERE status='published' AND event_date>=CURDATE() ORDER BY event_date ASC LIMIT 3");
                if (empty($sideEvents)) $sideEvents = [
                    ['title'=>'Workshop de Gestão Pública','slug'=>'#','event_date'=>'2026-03-22','start_time'=>'09:00:00'],
                    ['title'=>'Congresso de Administração','slug'=>'#','event_date'=>'2026-03-15','start_time'=>'09:00:00'],
                ];
                foreach ($sideEvents as $se): $d = new DateTime($se['event_date']); ?>
                <a href="/crcap/pages/evento.php?slug=<?= urlencode($se['slug']) ?>" class="flex gap-3 mb-3 last:mb-0 group">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-xl flex flex-col items-center justify-center text-white flex-shrink-0">
                        <span class="text-sm font-bold leading-none"><?= $d->format('d') ?></span>
                        <span class="text-[9px] uppercase"><?= $d->format('M') ?></span>
                    </div>
                    <div><h4 class="text-xs font-semibold text-[#001644] group-hover:text-[#BF8D1A] transition line-clamp-2 leading-snug"><?= htmlspecialchars($se['title']) ?></h4><span class="text-[9px] text-[#022E6B]"><?= substr($se['start_time'],0,5) ?></span></div>
                </a>
                <?php endforeach; ?>
                <a href="/crcap/pages/eventos.php" class="block text-center text-xs text-[#BF8D1A] font-semibold hover:underline mt-3">Ver todos os eventos →</a>
            </div>

            <!-- Compartilhar sidebar -->
            <div class="bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-5 text-white text-center">
                <i class="fas fa-share-alt text-2xl text-[#BF8D1A] mb-3 block"></i>
                <h3 class="font-bold text-sm mb-3">Compartilhe esta notícia</h3>
                <div class="flex justify-center gap-2">
                    <a href="https://facebook.com/sharer/sharer.php?u=<?= urlencode('https://crcap.org.br/pages/post.php?slug='.$slug) ?>" target="_blank" class="w-10 h-10 rounded-xl bg-[#1877F2] flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-facebook-f text-sm"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://crcap.org.br/pages/post.php?slug='.$slug) ?>" target="_blank" class="w-10 h-10 rounded-xl bg-[#1DA1F2] flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-twitter text-sm"></i></a>
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode($post['title'].' - https://crcap.org.br/pages/post.php?slug='.$slug) ?>" target="_blank" class="w-10 h-10 rounded-xl bg-[#25D366] flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-whatsapp text-sm"></i></a>
                    <a href="https://linkedin.com/shareArticle?url=<?= urlencode('https://crcap.org.br/pages/post.php?slug='.$slug) ?>" target="_blank" class="w-10 h-10 rounded-xl bg-[#0077B5] flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-linkedin-in text-sm"></i></a>
                </div>
            </div>
        </aside>

    </div>
</main>

<?php include '../includes/footer.php'; ?>