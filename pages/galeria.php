<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle  = 'Galeria de Fotos · CRCAP';
$activeMenu = '';

$galSlug = $_GET['galeria'] ?? '';

// Single gallery view
if ($galSlug) {
    $gal = dbFetch($pdo,"SELECT * FROM galleries WHERE slug=? AND status='published' LIMIT 1",[$galSlug]);
    if (!$gal) { http_response_code(404); }
    else {
        dbExec($pdo,"UPDATE galleries SET views=views+1 WHERE id=?",[$gal['id']]);
        $images = dbFetchAll($pdo,"SELECT * FROM gallery_images WHERE gallery_id=? ORDER BY order_position ASC",[$gal['id']]);
        $pageTitle = htmlspecialchars($gal['title']).' · Galeria CRCAP';
    }
}

// All galleries
$galleries = dbFetchAll($pdo,
    "SELECT g.*, COUNT(gi.id) AS img_count FROM galleries g
     LEFT JOIN gallery_images gi ON g.id=gi.gallery_id
     WHERE g.status='published'
     GROUP BY g.id ORDER BY g.event_date DESC, g.created_at DESC");

// Example data
$exGalleries = [
    ['id'=>1,'title'=>'Posse da Nova Diretoria 2024-2027','slug'=>'posse-diretoria-2024','cover_image'=>'https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=600&h=400&fit=crop','event_date'=>'2024-03-15','img_count'=>24,'views'=>892,'photographer'=>'Assessoria CRCAP'],
    ['id'=>2,'title'=>'Workshop de Governança Corporativa','slug'=>'workshop-governanca','cover_image'=>'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&h=400&fit=crop','event_date'=>'2025-09-10','img_count'=>18,'views'=>654,'photographer'=>''],
    ['id'=>3,'title'=>'Congresso Regional de Administração 2025','slug'=>'congresso-regional-2025','cover_image'=>'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600&h=400&fit=crop','event_date'=>'2025-11-22','img_count'=>42,'views'=>1320,'photographer'=>'Foto Oficial'],
    ['id'=>4,'title'=>'Formatura dos Cursos de Capacitação','slug'=>'formatura-capacitacao','cover_image'=>'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=600&h=400&fit=crop','event_date'=>'2025-12-05','img_count'=>31,'views'=>780,'photographer'=>''],
    ['id'=>5,'title'=>'Dia do Administrador 2025','slug'=>'dia-administrador-2025','cover_image'=>'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=600&h=400&fit=crop','event_date'=>'2025-09-09','img_count'=>15,'views'=>1100,'photographer'=>'Assessoria CRCAP'],
    ['id'=>6,'title'=>'Semana da Mulher Contabilista','slug'=>'semana-mulher-contabilista','cover_image'=>'https://images.unsplash.com/photo-1573164713347-df18c9c76d74?w=600&h=400&fit=crop','event_date'=>'2026-03-08','img_count'=>20,'views'=>560,'photographer'=>''],
];

include '../includes/header.php';

if ($galSlug && ($gal ?? false)):
// === SINGLE GALLERY VIEW ===
$exImages = [
    ['image_path'=>'https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=800&h=600&fit=crop','title'=>'Abertura do evento'],
    ['image_path'=>'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&h=600&fit=crop','title'=>''],
    ['image_path'=>'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=800&h=600&fit=crop','title'=>'Palestrante'],
    ['image_path'=>'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&h=600&fit=crop','title'=>''],
    ['image_path'=>'https://images.unsplash.com/photo-1573164713347-df18c9c76d74?w=800&h=600&fit=crop','title'=>''],
    ['image_path'=>'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&h=600&fit=crop','title'=>'Encerramento'],
];
$displayImages = !empty($images) ? $images : $exImages;
?>

<div class="bg-[#F8FAFC] border-b border-[#001644]/5 py-3">
    <div class="container mx-auto px-4 flex items-center gap-2 text-xs text-[#022E6B]">
        <a href="/crcap/index.php" class="hover:text-[#BF8D1A] transition">Início</a>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <a href="/crcap/pages/galeria.php" class="hover:text-[#BF8D1A] transition">Galerias</a>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <span class="text-[#001644] font-medium"><?= htmlspecialchars($gal['title']) ?></span>
    </div>
</div>

<section class="bg-gradient-to-br from-[#001644] to-[#022E6B] text-white py-12 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 40% 50%, #BF8D1A 0%,transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-start gap-5">
            <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-images"></i></div>
            <div>
                <h1 class="font-serif text-2xl md:text-3xl font-bold mb-2"><?= htmlspecialchars($gal['title']) ?></h1>
                <div class="flex flex-wrap items-center gap-4 text-xs text-white/70">
                    <?php if ($gal['event_date']): ?><span class="flex items-center gap-1"><i class="fas fa-calendar text-[#BF8D1A]"></i><?= date('d/m/Y', strtotime($gal['event_date'])) ?></span><?php endif; ?>
                    <span class="flex items-center gap-1"><i class="fas fa-image text-[#BF8D1A]"></i><?= count($displayImages) ?> fotos</span>
                    <?php if ($gal['photographer']): ?><span class="flex items-center gap-1"><i class="fas fa-camera text-[#BF8D1A]"></i><?= htmlspecialchars($gal['photographer']) ?></span><?php endif; ?>
                    <span class="flex items-center gap-1"><i class="fas fa-eye text-[#BF8D1A]"></i><?= number_format($gal['views']) ?> visualizações</span>
                </div>
                <?php if ($gal['description']): ?><p class="text-white/70 text-sm mt-3 max-w-2xl"><?= htmlspecialchars($gal['description']) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-10">
    <!-- Masonry-style grid -->
    <div class="columns-2 md:columns-3 lg:columns-4 gap-3 space-y-3" id="photoGrid">
        <?php foreach ($displayImages as $i => $img): ?>
        <div class="break-inside-avoid mb-3">
            <a href="<?= htmlspecialchars($img['image_path']) ?>" onclick="openLightbox(<?= $i ?>,event)" class="block rounded-xl overflow-hidden group cursor-zoom-in">
                <img src="<?= htmlspecialchars($img['image_path']) ?>"
                     alt="<?= htmlspecialchars($img['title'] ?? '') ?>"
                     class="w-full h-auto group-hover:scale-105 transition duration-500"
                     loading="lazy">
                <?php if ($img['title']): ?>
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-[#001644]/80 to-transparent p-3 opacity-0 group-hover:opacity-100 transition">
                    <p class="text-white text-xs font-medium"><?= htmlspecialchars($img['title']) ?></p>
                </div>
                <?php endif; ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-8">
        <a href="/crcap/pages/galeria.php" class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-[#001644]/10 rounded-xl text-sm font-semibold text-[#001644] hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition">
            <i class="fas fa-arrow-left"></i>Voltar às Galerias
        </a>
    </div>
</main>

<!-- Lightbox -->
<div id="lightbox" class="fixed inset-0 bg-black/95 z-[100] hidden flex items-center justify-center p-4" onclick="closeLightbox(event)">
    <button onclick="closeLightbox()" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition text-lg"><i class="fas fa-times"></i></button>
    <button onclick="prevPhoto()" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition"><i class="fas fa-chevron-left"></i></button>
    <button onclick="nextPhoto()" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition"><i class="fas fa-chevron-right"></i></button>
    <div class="max-w-5xl max-h-[90vh] flex flex-col items-center" onclick="event.stopPropagation()">
        <img id="lbImg" src="" alt="" class="max-h-[80vh] max-w-full object-contain rounded-xl">
        <p id="lbCaption" class="text-white/70 text-sm mt-3"></p>
        <p id="lbCounter" class="text-white/40 text-xs mt-1"></p>
    </div>
</div>

<script>
const photos = <?= json_encode(array_map(fn($img)=>['src'=>$img['image_path'],'cap'=>$img['title']??''], $displayImages)) ?>;
let current = 0;
function openLightbox(i, e){ e?.preventDefault(); current=i; showPhoto(); document.getElementById('lightbox').classList.remove('hidden'); document.body.style.overflow='hidden'; }
function closeLightbox(e){ if(!e||e.target===document.getElementById('lightbox')){ document.getElementById('lightbox').classList.add('hidden'); document.body.style.overflow=''; } }
function showPhoto(){ document.getElementById('lbImg').src=photos[current].src; document.getElementById('lbCaption').textContent=photos[current].cap; document.getElementById('lbCounter').textContent=(current+1)+' / '+photos.length; }
function nextPhoto(){ current=(current+1)%photos.length; showPhoto(); }
function prevPhoto(){ current=(current-1+photos.length)%photos.length; showPhoto(); }
document.addEventListener('keydown', e=>{ if(document.getElementById('lightbox').classList.contains('hidden'))return; if(e.key==='ArrowRight')nextPhoto(); if(e.key==='ArrowLeft')prevPhoto(); if(e.key==='Escape')closeLightbox(); });
</script>

<?php else:
// === GALLERY LISTING ===
$displayGals = !empty($galleries) ? $galleries : $exGalleries;
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 30% 50%, #BF8D1A 0%,transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Galeria de Fotos</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-images"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Galeria de Fotos</h1>
                <p class="text-white/70 text-sm">Registros fotográficos dos eventos, atividades e momentos especiais do CRCAP.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-6">
        <?php foreach ($displayGals as $gal): ?>
        <a href="/crcap/pages/galeria.php?galeria=<?= urlencode($gal['slug']) ?>" class="group bg-white rounded-2xl overflow-hidden border border-[#001644]/3 shadow-sm hover:-translate-y-2 hover:shadow-xl hover:shadow-[#001644]/10 hover:border-[#BF8D1A]/20 transition">
            <div class="relative h-52 overflow-hidden">
                <img src="<?= htmlspecialchars($gal['cover_image'] ?: 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=600&h=400&fit=crop') ?>"
                     alt="<?= htmlspecialchars($gal['title']) ?>"
                     class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-[#001644]/70 to-transparent opacity-0 group-hover:opacity-100 transition"></div>
                <div class="absolute top-3 right-3 bg-black/50 backdrop-blur-sm text-white text-[10px] font-bold px-2.5 py-1 rounded-full flex items-center gap-1">
                    <i class="fas fa-image text-[8px]"></i><?= number_format($gal['img_count']) ?> fotos
                </div>
                <div class="absolute bottom-3 left-3 right-3 opacity-0 group-hover:opacity-100 transition">
                    <span class="text-white text-xs font-bold flex items-center gap-1.5"><i class="fas fa-search-plus text-[#BF8D1A]"></i>Ver galeria</span>
                </div>
            </div>
            <div class="p-5">
                <h3 class="font-bold text-[#001644] text-sm mb-2 group-hover:text-[#BF8D1A] transition line-clamp-2 leading-snug"><?= htmlspecialchars($gal['title']) ?></h3>
                <div class="flex items-center justify-between text-[10px] text-[#022E6B]">
                    <?php if ($gal['event_date']): ?><span class="flex items-center gap-1"><i class="fas fa-calendar text-[#BF8D1A]"></i><?= date('d/m/Y', strtotime($gal['event_date'])) ?></span><?php endif; ?>
                    <span class="flex items-center gap-1"><i class="fas fa-eye text-[#BF8D1A]"></i><?= number_format($gal['views']) ?></span>
                </div>
                <?php if ($gal['photographer']): ?>
                <p class="text-[9px] text-[#022E6B]/60 mt-2 flex items-center gap-1"><i class="fas fa-camera text-[10px]"></i><?= htmlspecialchars($gal['photographer']) ?></p>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($galleries) && empty($displayGals)): ?>
    <div class="text-center py-20 text-[#001644]/30">
        <i class="fas fa-images text-5xl mb-4 block"></i>
        <h2 class="text-xl font-bold text-[#001644]/50 mb-2">Nenhuma galeria disponível</h2>
        <p class="text-sm">As galerias de fotos serão publicadas em breve.</p>
    </div>
    <?php endif; ?>
</main>

<?php endif; ?>

<?php include '../includes/footer.php'; ?>
