<?php
$pageTitle = 'Posts e Notícias · Admin CRCAP';
$activeAdm = 'posts';
// Load db and auth WITHOUT outputting HTML yet
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$adminUser = currentUser();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';

// DELETE
if ($action === 'delete' && $id) {
    dbExec($pdo, "DELETE FROM posts WHERE id=?", [$id]);
    header('Location: /crcap/admin/posts.php?msg=deleted'); exit;
}

// TOGGLE STATUS
if ($action === 'toggle' && $id) {
    $cur = dbFetch($pdo, "SELECT status FROM posts WHERE id=?", [$id]);
    $new = $cur['status'] === 'published' ? 'draft' : 'published';
    $pub = $new === 'published' ? date('Y-m-d H:i:s') : null;
    dbExec($pdo, "UPDATE posts SET status=?, published_at=? WHERE id=?", [$new, $pub, $id]);
    header('Location: /crcap/admin/posts.php?msg=updated'); exit;
}

// SAVE (create/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_post'])) {
    $pid     = (int)($_POST['pid'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $slug    = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/','-', iconv('UTF-8','ASCII//TRANSLIT',$title ?: 'sem-titulo-'.time())));
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $catId   = (int)($_POST['category_id'] ?? 0) ?: null;
    $status  = $_POST['status'] ?? 'draft';
    $featured= isset($_POST['is_featured']) ? 1 : 0;
    $img     = trim($_POST['featured_image'] ?? '');
    $gallery    = $_POST['gallery'] ?? '[]';
    $seoT       = trim($_POST['seo_title'] ?? '');
    $seoD       = trim($_POST['seo_description'] ?? '');
    $authId     = $adminUser['id'];
    // Live fields
    $isLive        = isset($_POST['is_live']) ? 1 : 0;
    $liveUrl       = trim($_POST['live_url'] ?? '');
    $livePlatform  = trim($_POST['live_platform'] ?? '') ?: null;
    $liveStartedAt = trim($_POST['live_started_at'] ?? '');
    $liveStartedAt = $liveStartedAt ? date('Y-m-d H:i:s', strtotime($liveStartedAt)) : null;
    $liveEndedAt   = trim($_POST['live_ended_at'] ?? '');
    $liveEndedAt   = $liveEndedAt ? date('Y-m-d H:i:s', strtotime($liveEndedAt)) : null;

    // Data de publicação
    $pubAtRaw = trim($_POST['published_at'] ?? '');
    if ($pubAtRaw) {
        $pubAt = date('Y-m-d H:i:s', strtotime($pubAtRaw));
    } elseif ($status === 'published') {
        $pubAt = date('Y-m-d H:i:s');
    } else {
        $pubAt = null;
    }

    try {
        // Detect available columns once
        $hasGallery = dbFetch($pdo, "SHOW COLUMNS FROM posts LIKE 'gallery'");
        $hasLive    = dbFetch($pdo, "SHOW COLUMNS FROM posts LIKE 'is_live'");

        if ($pid) {
            if ($hasGallery && $hasLive) {
                dbExec($pdo, "UPDATE posts SET title=?,slug=?,excerpt=?,content=?,category_id=?,author_id=?,status=?,is_featured=?,featured_image=?,gallery=?,seo_title=?,seo_description=?,published_at=?,is_live=?,live_url=?,live_platform=?,live_started_at=?,live_ended_at=? WHERE id=?",
                    [$title,$slug,$excerpt,$content,$catId,$authId,$status,$featured,$img,$gallery,$seoT,$seoD,$pubAt,$isLive,$liveUrl,$livePlatform,$liveStartedAt,$liveEndedAt,$pid]);
            } elseif ($hasGallery) {
                dbExec($pdo, "UPDATE posts SET title=?,slug=?,excerpt=?,content=?,category_id=?,author_id=?,status=?,is_featured=?,featured_image=?,gallery=?,seo_title=?,seo_description=?,published_at=? WHERE id=?",
                    [$title,$slug,$excerpt,$content,$catId,$authId,$status,$featured,$img,$gallery,$seoT,$seoD,$pubAt,$pid]);
            } else {
                dbExec($pdo, "UPDATE posts SET title=?,slug=?,excerpt=?,content=?,category_id=?,author_id=?,status=?,is_featured=?,featured_image=?,seo_title=?,seo_description=?,published_at=? WHERE id=?",
                    [$title,$slug,$excerpt,$content,$catId,$authId,$status,$featured,$img,$seoT,$seoD,$pubAt,$pid]);
            }
        } else {
            if ($hasGallery && $hasLive) {
                dbExec($pdo, "INSERT INTO posts (title,slug,excerpt,content,category_id,author_id,status,is_featured,featured_image,gallery,seo_title,seo_description,published_at,is_live,live_url,live_platform,live_started_at,live_ended_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$title,$slug,$excerpt,$content,$catId,$authId,$status,$featured,$img,$gallery,$seoT,$seoD,$pubAt,$isLive,$liveUrl,$livePlatform,$liveStartedAt,$liveEndedAt]);
            } elseif ($hasGallery) {
                dbExec($pdo, "INSERT INTO posts (title,slug,excerpt,content,category_id,author_id,status,is_featured,featured_image,gallery,seo_title,seo_description,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$title,$slug,$excerpt,$content,$catId,$authId,$status,$featured,$img,$gallery,$seoT,$seoD,$pubAt]);
            } else {
                dbExec($pdo, "INSERT INTO posts (title,slug,excerpt,content,category_id,author_id,status,is_featured,featured_image,seo_title,seo_description,published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$title,$slug,$excerpt,$content,$catId,$authId,$status,$featured,$img,$seoT,$seoD,$pubAt]);
            }
            $pid = $pdo->lastInsertId();
        }
        header("Location: /crcap/admin/posts.php?msg=saved"); exit;
    } catch (Exception $e) { $msg = 'Erro: '.$e->getMessage(); }
}

$cats = dbFetchAll($pdo, "SELECT id,name FROM categories WHERE status='active' ORDER BY name ASC");

// EDIT
if ($action === 'edit' && $id) {
    $post = dbFetch($pdo, "SELECT * FROM posts WHERE id=?", [$id]);
    if (!$post) { header('Location: /crcap/admin/posts.php'); exit; }
}

// NEW
if ($action === 'new') {
    $post = ['id'=>0,'title'=>'','slug'=>'','excerpt'=>'','content'=>'','category_id'=>null,'status'=>'draft','is_featured'=>0,'featured_image'=>'','seo_title'=>'','seo_description'=>''];
}

// FORM VIEW
require_once __DIR__ . '/admin_header.php';

if ($action === 'edit' || $action === 'new'):
?>

<div class="flex items-center gap-3 mb-6">
    <a href="/crcap/admin/posts.php" class="text-[#022E6B] hover:text-[#BF8D1A] transition text-sm"><i class="fas fa-arrow-left"></i></a>
    <h2 class="text-lg font-bold text-[#001644]"><?= $id ? 'Editar Post' : 'Novo Post' ?></h2>
</div>

<?php if ($msg): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<form method="POST" class="grid lg:grid-cols-3 gap-6">
    <input type="hidden" name="form_post" value="1">
    <input type="hidden" name="pid" value="<?= $post['id'] ?>">

    <!-- Coluna Principal -->
    <div class="lg:col-span-2 space-y-5">
        <div class="card p-6">
            <div class="space-y-4">
                <div>
                    <label class="form-label">Título</label>
                    <input type="text" name="title" id="titleInput" value="<?= htmlspecialchars($post['title']) ?>" class="form-input text-base font-semibold" placeholder="Título da notícia">
                </div>
                <div>
                    <label class="form-label">Slug (URL)</label>
                    <div class="relative"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#022E6B]/40 text-xs">/posts/</span><input type="text" name="slug" id="slugInput" value="<?= htmlspecialchars($post['slug']) ?>" class="form-input pl-14" placeholder="slug-da-noticia"></div>
                </div>
                <div>
                    <label class="form-label">Resumo / Excerpt</label>
                    <textarea name="excerpt" rows="3" class="form-input resize-none" placeholder="Breve descrição da notícia (aparece nas listagens)"><?= htmlspecialchars($post['excerpt']) ?></textarea>
                </div>
                <div>
                    <label class="form-label">Conteúdo Completo</label>
                    <!-- Quill Editor -->
                    <div class="border border-[#001644]/10 rounded-xl overflow-hidden focus-within:border-[#BF8D1A] transition">
                        <div id="quillEditor" style="min-height:380px; font-size:14px;"></div>
                    </div>
                    <textarea id="contentHidden" name="content" class="hidden"><?= htmlspecialchars($post['content']) ?></textarea>
                    <div class="flex items-center justify-between mt-1.5">
                        <p class="text-[10px] text-[#022E6B]/60">Editor visual completo · HTML preservado</p>
                        <button type="button" onclick="toggleHtmlMode()" class="text-[10px] text-[#022E6B] border border-[#001644]/10 px-2 py-1 rounded-lg hover:bg-[#001644] hover:text-white transition">&lt;/&gt; Ver HTML</button>
                    </div>
                    <textarea id="htmlModeArea" class="hidden w-full mt-2 p-3 font-mono text-xs border border-[#BF8D1A] rounded-xl focus:outline-none resize-y" rows="10" placeholder="HTML direto..."></textarea>
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div class="card p-6">
            <h3 class="text-sm font-bold text-[#001644] mb-4 flex items-center gap-2"><i class="fas fa-search text-[#BF8D1A]"></i>SEO</h3>
            <div class="space-y-4">
                <div><label class="form-label">Título SEO</label><input type="text" name="seo_title" value="<?= htmlspecialchars($post['seo_title']) ?>" class="form-input" placeholder="Título para mecanismos de busca (max 60 chars)"></div>
                <div><label class="form-label">Descrição SEO</label><textarea name="seo_description" rows="2" class="form-input resize-none" placeholder="Descrição para mecanismos de busca (max 160 chars)"><?= htmlspecialchars($post['seo_description']) ?></textarea></div>
            </div>
        </div>
    </div>

    <!-- Sidebar Painel -->
    <div class="space-y-5">
        <!-- Publicação -->
        <div class="card p-5">
            <h3 class="text-sm font-bold text-[#001644] mb-4">Publicação</h3>
            <div class="space-y-3 mb-4">
                <div><label class="form-label">Status</label>
                    <select name="status" id="postStatus" class="form-input" onchange="togglePubDate(this.value)">
                        <?php foreach (['draft'=>'Rascunho','published'=>'Publicado','scheduled'=>'Agendado','archived'=>'Arquivado'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $post['status']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Data de publicação -->
                <div id="pubDateWrap">
                    <label class="form-label flex items-center gap-1">
                        <i class="fas fa-calendar-alt text-[#BF8D1A] text-[10px]"></i>
                        Data de Publicação
                    </label>
                    <?php
                    $pubVal = '';
                    if (!empty($post['published_at'])) {
                        $pubVal = date('Y-m-d\TH:i', strtotime($post['published_at']));
                    }
                    ?>
                    <input type="datetime-local" name="published_at" id="pubDateInput"
                           value="<?= $pubVal ?>"
                           class="form-input text-xs"
                           placeholder="Deixe vazio para usar data atual">
                    <p class="text-[10px] text-[#022E6B]/50 mt-1">
                        Vazio = usa data/hora atual ao publicar
                    </p>
                </div>

                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="is_featured" <?= $post['is_featured']?'checked':'' ?> class="rounded"><span class="text-xs text-[#022E6B] font-medium">Destacar na Home</span></label>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center"><i class="fas fa-save"></i>Salvar</button>
                <a href="/crcap/admin/posts.php" class="px-4 py-2.5 border border-[#001644]/10 rounded-xl text-xs font-medium text-[#022E6B] hover:bg-[#F8FAFC] transition">Cancelar</a>
            </div>
        </div>

        <!-- Categoria -->
        <div class="card p-5">
            <h3 class="text-sm font-bold text-[#001644] mb-3">Categoria</h3>
            <select name="category_id" class="form-input">
                <option value="">Sem categoria</option>
                <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $post['category_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>

        <!-- Imagem Destacada -->
        <div class="card p-5">
            <h3 class="text-sm font-bold text-[#001644] mb-3 flex items-center gap-2">
                <i class="fas fa-image text-[#BF8D1A]"></i>Imagem Destacada
            </h3>
            <input type="text" name="featured_image" id="imgInput" value="<?= htmlspecialchars($post['featured_image']) ?>" class="form-input mb-2" placeholder="URL ou caminho da imagem">
            
            <!-- Upload button -->
            <label for="imgUploadInput" class="flex items-center gap-2 w-full py-2 px-3 text-xs font-semibold text-[#001644] border border-dashed border-[#001644]/20 rounded-xl hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition cursor-pointer mb-3">
                <i class="fas fa-upload text-[#BF8D1A]"></i>Carregar do computador
                <span id="imgUploadStatus" class="ml-auto text-[10px] text-[#022E6B]"></span>
            </label>
            <input type="file" id="imgUploadInput" accept="image/*" class="hidden">

            <!-- Preview -->
            <div id="imgPreviewWrap" class="w-full h-32 bg-[#F8FAFC] rounded-xl border-2 border-dashed border-[#001644]/10 flex items-center justify-center overflow-hidden relative group">
                <?php if ($post['featured_image']): ?>
                <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="" class="w-full h-full object-cover" id="imgPreviewImg">
                <?php else: ?>
                <div class="text-center text-[#001644]/20" id="imgPreviewPlaceholder">
                    <i class="fas fa-image text-3xl block mb-1"></i>
                    <p class="text-[10px]">Pré-visualização</p>
                </div>
                <?php endif; ?>
                <!-- Remove button overlay -->
                <button type="button" id="imgRemoveBtn" onclick="removeImage()"
                        class="absolute top-2 right-2 w-7 h-7 rounded-full bg-red-500 text-white text-xs hidden group-hover:flex items-center justify-center hover:bg-red-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Galeria de Imagens Adicionais -->
        <div class="card p-5">
            <h3 class="text-sm font-bold text-[#001644] mb-3 flex items-center gap-2">
                <i class="fas fa-images text-[#BF8D1A]"></i>Galeria de Imagens
            </h3>
            <p class="text-[10px] text-[#022E6B]/70 mb-3">Imagens adicionais para o post (cópia a URL e cole no conteúdo)</p>
            <div id="galleryGrid" class="grid grid-cols-2 gap-2 mb-3">
                <?php
                $galleryImgs = json_decode($post['gallery'] ?? '[]', true) ?: [];
                foreach ($galleryImgs as $gi): ?>
                <div class="relative group gallery-item">
                    <img src="<?= htmlspecialchars($gi) ?>" class="w-full h-20 object-cover rounded-lg">
                    <div class="absolute inset-0 bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1">
                        <button type="button" onclick="copyGalleryUrl('<?= htmlspecialchars($gi) ?>')" class="text-white text-xs bg-[#BF8D1A] px-2 py-1 rounded"><i class="fas fa-copy"></i></button>
                        <button type="button" onclick="removeGalleryItem(this,'<?= htmlspecialchars($gi) ?>')" class="text-white text-xs bg-red-500 px-2 py-1 rounded"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="gallery" id="galleryData" value="<?= htmlspecialchars(json_encode($galleryImgs)) ?>">
            <label class="flex items-center gap-2 w-full py-2 px-3 text-xs font-semibold text-[#001644] border border-dashed border-[#001644]/20 rounded-xl hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition cursor-pointer">
                <i class="fas fa-plus text-[#BF8D1A]"></i>Adicionar imagem
                <input type="file" id="galleryUpload" accept="image/*" multiple class="hidden">
            </label>
        </div>

        <?php include __DIR__ . '/posts_live_fields.php'; ?>

    </div>
</form>

<script>
// ── Auto-slug ──────────────────────────────────────────────────────────────
document.getElementById('titleInput').addEventListener('input', function(){
    const slug = this.value.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g,'')
        .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    if (!document.getElementById('slugInput').dataset.manual) {
        document.getElementById('slugInput').value = slug;
    }
});
document.getElementById('slugInput').addEventListener('input', function(){
    this.dataset.manual = '1';
});

// ── Image preview on URL input ─────────────────────────────────────────────
document.getElementById('imgInput').addEventListener('input', function(){
    updatePreview(this.value);
});

function updatePreview(url) {
    const wrap = document.getElementById('imgPreviewWrap');
    if (url) {
        wrap.innerHTML = `<img src="${url}" class="w-full h-full object-cover" onerror="this.style.display='none'">
            <button type="button" onclick="removeImage()" class="absolute top-2 right-2 w-7 h-7 rounded-full bg-red-500 text-white text-xs flex items-center justify-center hover:bg-red-600 transition"><i class="fas fa-times"></i></button>`;
        wrap.classList.remove('border-dashed');
    } else {
        wrap.innerHTML = `<div class="text-center text-[#001644]/20"><i class="fas fa-image text-3xl block mb-1"></i><p class="text-[10px]">Pré-visualização</p></div>`;
        wrap.classList.add('border-dashed');
    }
}

function removeImage() {
    document.getElementById('imgInput').value = '';
    updatePreview('');
}

// ── File upload ────────────────────────────────────────────────────────────
document.getElementById('imgUploadInput').addEventListener('change', async function(){
    const file = this.files[0];
    if (!file) return;
    const status = document.getElementById('imgUploadStatus');
    status.textContent = 'Enviando…';

    const fd = new FormData();
    fd.append('file', file);
    fd.append('type', 'image');

    try {
        const res  = await fetch('/crcap/api/upload.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) {
            document.getElementById('imgInput').value = data.url;
            updatePreview(data.url);
            status.textContent = '✓ Enviado';
            setTimeout(() => status.textContent = '', 2500);
        } else {
            status.textContent = '✗ ' + data.message;
        }
    } catch(e) {
        status.textContent = '✗ Erro de rede';
    }
    this.value = '';
});

// ── Drag & drop on preview ─────────────────────────────────────────────────
const wrap = document.getElementById('imgPreviewWrap');
wrap.addEventListener('dragover', e => { e.preventDefault(); wrap.classList.add('border-[#BF8D1A]'); });
wrap.addEventListener('dragleave', () => wrap.classList.remove('border-[#BF8D1A]'));
wrap.addEventListener('drop', async e => {
    e.preventDefault();
    wrap.classList.remove('border-[#BF8D1A]');
    const file = e.dataTransfer.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    const inp = document.getElementById('imgUploadInput');
    const dt  = new DataTransfer();
    dt.items.add(file);
    inp.files = dt.files;
    inp.dispatchEvent(new Event('change'));
});
</script>

<?php
else: // LIST VIEW

$busca  = trim($_GET['busca'] ?? '');
$status = $_GET['status'] ?? '';
$page_n = max(1,(int)($_GET['p'] ?? 1));
$perP   = 15;
$off    = ($page_n - 1) * $perP;

$where  = ['1=1'];
$params = [];
if ($busca)  { $where[] = "(p.title LIKE ? OR p.excerpt LIKE ?)"; $params[] = "%$busca%"; $params[] = "%$busca%"; }
if ($status) { $where[] = "p.status=?"; $params[] = $status; }

$posts = dbFetchAll($pdo, "SELECT p.*,c.name AS cat FROM posts p LEFT JOIN categories c ON p.category_id=c.id WHERE ".implode(' AND ',$where)." ORDER BY p.created_at DESC LIMIT $perP OFFSET $off", $params);
$total = dbFetch($pdo, "SELECT COUNT(*) AS n FROM posts p LEFT JOIN categories c ON p.category_id=c.id WHERE ".implode(' AND ',$where), $params)['n'] ?? 0;
$pages = ceil($total / $perP);

$msgMap = ['saved'=>['green','Salvo com sucesso!'],'deleted'=>['red','Excluído.'],'updated'=>['gold','Atualizado!']];
$gm = $msgMap[$_GET['msg'] ?? ''] ?? null;
?>

<?php if ($gm): ?><div class="bg-[#<?= $gm[0]==='green'?'006633':($gm[0]==='red'?'EF4444':'BF8D1A') ?>]/10 border border-[#<?= $gm[0]==='green'?'006633':($gm[0]==='red'?'EF4444':'BF8D1A') ?>]/30 text-[#001644] text-xs rounded-xl px-4 py-3 mb-5"><?= $gm[1] ?></div><?php endif; ?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-5">
    <div class="flex items-center gap-3">
        <form method="GET" class="flex items-center gap-2">
            <div class="relative"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#022E6B]/30 text-xs"></i><input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar posts..." class="form-input pl-8 w-56 py-2 text-xs"></div>
            <select name="status" class="form-input py-2 text-xs w-36"><option value="">Todos os status</option><?php foreach(['draft'=>'Rascunho','published'=>'Publicado','archived'=>'Arquivado'] as $v=>$l): ?><option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select>
            <button class="btn-primary py-2 px-4"><i class="fas fa-filter"></i>Filtrar</button>
            <?php if ($busca || $status): ?><a href="/crcap/admin/posts.php" class="text-xs text-[#BF8D1A] hover:underline"><i class="fas fa-times text-[9px]"></i> Limpar</a><?php endif; ?>
        </form>
    </div>
    <a href="/crcap/admin/posts.php?action=new" class="btn-gold"><i class="fas fa-plus"></i>Novo Post</a>
</div>

<div class="card overflow-hidden">
    <table class="w-full">
        <thead><tr>
            <th class="text-left">Título</th>
            <th class="text-left hidden md:table-cell">Categoria</th>
            <th class="text-center hidden lg:table-cell">Status</th>
            <th class="text-right hidden lg:table-cell">Views</th>
            <th class="text-center hidden md:table-cell">Data</th>
            <th class="text-center">Ações</th>
        </tr></thead>
        <tbody>
            <?php if (empty($posts)): ?>
            <tr><td colspan="6" class="text-center py-12 text-[#001644]/30"><i class="fas fa-newspaper text-4xl mb-3 block"></i>Nenhum post encontrado</td></tr>
            <?php else: foreach ($posts as $p): ?>
            <tr>
                <td>
                    <div class="flex items-center gap-2">
                        <?php if ($p['is_featured']): ?><i class="fas fa-star text-[#BF8D1A] text-xs flex-shrink-0" title="Destaque"></i><?php endif; ?>
                        <div class="min-w-0"><p class="font-semibold text-[#001644] truncate max-w-xs"><?= htmlspecialchars($p['title']) ?></p><p class="text-[9px] text-[#022E6B]/50 truncate max-w-xs">/posts/<?= htmlspecialchars($p['slug']) ?></p></div>
                    </div>
                </td>
                <td class="hidden md:table-cell text-[#022E6B]"><?= htmlspecialchars($p['cat'] ?? '—') ?></td>
                <td class="text-center hidden lg:table-cell"><span class="badge <?= $p['status']==='published'?'badge-green':($p['status']==='draft'?'badge-gray':'badge-gold') ?>"><?= $p['status'] ?></span></td>
                <td class="text-right font-mono text-xs hidden lg:table-cell"><?= number_format($p['views']) ?></td>
                <td class="text-center hidden md:table-cell text-xs"><?= $p['published_at'] ? date('d/m/Y', strtotime($p['published_at'])) : '—' ?></td>
                <td class="text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="/crcap/admin/posts.php?action=edit&id=<?= $p['id'] ?>" class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs" title="Editar"><i class="fas fa-edit"></i></a>
                        <a href="/crcap/admin/posts.php?action=toggle&id=<?= $p['id'] ?>" class="w-7 h-7 rounded-lg bg-[#006633]/10 hover:bg-[#006633] hover:text-white text-[#006633] flex items-center justify-center transition text-xs" title="<?= $p['status']==='published'?'Despublicar':'Publicar' ?>"><i class="fas <?= $p['status']==='published'?'fa-eye-slash':'fa-eye' ?>"></i></a>
                        <a href="/crcap/pages/post.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="w-7 h-7 rounded-lg bg-[#BF8D1A]/10 hover:bg-[#BF8D1A] hover:text-white text-[#BF8D1A] flex items-center justify-center transition text-xs" title="Ver"><i class="fas fa-external-link-alt"></i></a>
                        <a href="/crcap/admin/posts.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Excluir este post?')" class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs" title="Excluir"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php if ($pages > 1): ?>
    <div class="flex justify-center gap-2 p-4 border-t border-[#001644]/5">
        <?php for ($i=1;$i<=$pages;$i++): ?>
        <a href="?p=<?= $i ?>&busca=<?= urlencode($busca) ?>&status=<?= urlencode($status) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-semibold transition <?= $i===$page_n?'bg-[#001644] text-white':'bg-[#F8FAFC] text-[#001644] hover:bg-[#001644]/10' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php if (isset($action) && ($action === 'edit' || $action === 'new')): ?>
<script>
(function() {
    const div  = document.getElementById('contentDiv');
    const html = document.getElementById('contentHtml');
    const toggle = document.getElementById('toggleHtml');
    const counter = document.getElementById('charCount');
    if (!div) return;

    let htmlMode = false;

    div.closest('form')?.addEventListener('submit', function() {
        if (!htmlMode) html.value = div.innerHTML;
    });

    function updateCount() {
        const len = htmlMode ? html.value.length : div.innerText.length;
        counter.textContent = len.toLocaleString('pt-BR') + ' caracteres';
    }
    div.addEventListener('input', updateCount);
    html.addEventListener('input', updateCount);
    updateCount();

    toggle.addEventListener('click', function() {
        htmlMode = !htmlMode;
        if (htmlMode) {
            html.value = div.innerHTML;
            div.style.display = 'none';
            html.style.display = 'block';
            toggle.textContent = '👁 Visual';
            toggle.classList.add('bg-[#001644]', 'text-white');
        } else {
            div.innerHTML = html.value;
            html.style.display = 'none';
            div.style.display = 'block';
            toggle.textContent = '</> HTML';
            toggle.classList.remove('bg-[#001644]', 'text-white');
        }
        updateCount();
    });

    document.querySelectorAll('.editor-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (htmlMode) return;
            div.focus();
            const cmd = this.dataset.cmd;
            if (!cmd) return;
            const [c, arg] = cmd.split(',');
            if (c === 'createLink') {
                const url = prompt('URL do link:');
                if (url) document.execCommand('createLink', false, url);
            } else if (c === 'insertImage') {
                const url = prompt('URL da imagem:');
                if (url) document.execCommand('insertImage', false, url);
            } else {
                document.execCommand(c, false, arg || null);
            }
            html.value = div.innerHTML;
            updateCount();
        });
    });

    div.addEventListener('paste', function(e) {
        e.preventDefault();
        const text = e.clipboardData.getData('text/html') || e.clipboardData.getData('text/plain');
        document.execCommand('insertHTML', false, text);
    });

    div.addEventListener('dragover', e => e.preventDefault());
    div.addEventListener('drop', async function(e) {
        e.preventDefault();
        const files = e.dataTransfer.files;
        for (const file of files) {
            if (!file.type.startsWith('image/')) continue;
            const fd = new FormData();
            fd.append('file', file);
            fd.append('type', 'image');
            try {
                const res  = await fetch('/crcap/api/upload.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    document.execCommand('insertImage', false, data.url);
                    html.value = div.innerHTML;
                }
            } catch(err) { console.error(err); }
        }
    });
})();
</script>

<!-- Quill Editor -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>

const quill = new Quill('#quillEditor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, 4, false] }],
            [{ 'font': [] }, { 'size': ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'align': [] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            ['blockquote', 'code-block'],
            ['link', 'image', 'video'],
            ['clean']
        ]
    },
    placeholder: 'Digite o conteúdo completo da notícia...'
});

const existingHtml = document.getElementById('contentHidden').value;
if (existingHtml) {
    quill.root.innerHTML = existingHtml;
}

quill.root.style.fontFamily = 'Sora, sans-serif';
quill.root.style.fontSize = '14px';
quill.root.style.color = '#001644';
quill.root.style.lineHeight = '1.7';
quill.root.style.minHeight = '380px';

document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('contentHidden').value = quill.root.innerHTML;
});

quill.getModule('toolbar').addHandler('image', function() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = async function() {
        const file = this.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('type', 'image');
        try {
            const r = await fetch('/crcap/api/upload.php', {method:'POST', body:fd});
            const d = await r.json();
            if (d.success) {
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', d.url);
            }
        } catch(e) { alert('Erro ao enviar imagem'); }
    };
    input.click();
});

let htmlMode = false;
function toggleHtmlMode() {
    const area = document.getElementById('htmlModeArea');
    htmlMode = !htmlMode;
    if (htmlMode) {
        area.value = quill.root.innerHTML;
        area.classList.remove('hidden');
        area.addEventListener('input', function() { quill.root.innerHTML = this.value; });
    } else {
        quill.root.innerHTML = area.value;
        area.classList.add('hidden');
    }
}

function togglePubDate(status) {
    const wrap  = document.getElementById('pubDateWrap');
    const input = document.getElementById('pubDateInput');
    if (!wrap) return;
    if (status === 'scheduled') {
        wrap.style.display = '';
        if (!input.value) {
            const d = new Date(); d.setDate(d.getDate()+1); d.setHours(9,0,0,0);
            input.value = d.toISOString().slice(0,16);
        }
    } else if (status === 'published') {
        wrap.style.display = '';
        if (!input.value) {
            const d = new Date(); d.setSeconds(0,0);
            input.value = d.toISOString().slice(0,16);
        }
    } else {
        wrap.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('postStatus');
    if (sel) togglePubDate(sel.value);
});

function getGallery() { try { return JSON.parse(document.getElementById('galleryData').value || '[]'); } catch(e) { return []; } }
function setGallery(arr) { document.getElementById('galleryData').value = JSON.stringify(arr); }
function copyGalleryUrl(url) { navigator.clipboard.writeText(url); }
function removeGalleryItem(btn, url) {
    btn.closest('.gallery-item').remove();
    setGallery(getGallery().filter(u => u !== url));
}
document.getElementById('galleryUpload').addEventListener('change', async function() {
    for (const file of this.files) {
        const fd = new FormData();
        fd.append('file', file); fd.append('type', 'image');
        try {
            const r = await fetch('/crcap/api/upload.php', {method:'POST', body:fd});
            const d = await r.json();
            if (d.success) {
                const arr = getGallery(); arr.push(d.url); setGallery(arr);
                const grid = document.getElementById('galleryGrid');
                const div = document.createElement('div');
                div.className = 'relative group gallery-item';
                div.innerHTML = `<img src="${d.url}" class="w-full h-20 object-cover rounded-lg">
                    <div class="absolute inset-0 bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1">
                        <button type="button" onclick="copyGalleryUrl('${d.url}')" class="text-white text-xs bg-[#BF8D1A] px-2 py-1 rounded"><i class="fas fa-copy"></i></button>
                        <button type="button" onclick="removeGalleryItem(this,'${d.url}')" class="text-white text-xs bg-red-500 px-2 py-1 rounded"><i class="fas fa-trash"></i></button>
                    </div>`;
                grid.appendChild(div);
            }
        } catch(e) {}
    }
});

</script>

<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>