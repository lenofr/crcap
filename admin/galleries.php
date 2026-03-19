<?php
$pageTitle = 'Galerias de Fotos · Admin CRCAP';
$activeAdm = 'galleries';
require_once __DIR__ . '/admin_header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';

// AJAX: add image to gallery
if ($action === 'add_img_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $gid  = (int)($_POST['gallery_id'] ?? 0);
    $path = trim($_POST['image_path'] ?? '');
    if (!$gid || !$path) { echo json_encode(['success'=>false,'message'=>'Dados inválidos']); exit; }
    dbExec($pdo, "INSERT INTO gallery_images (gallery_id, image_path, order_position) VALUES (?,?,?)",
        [$gid, $path, (int)dbFetch($pdo,"SELECT COUNT(*) AS c FROM gallery_images WHERE gallery_id=?",[$gid])['c']]);
    echo json_encode(['success'=>true,'id'=>(int)$pdo->lastInsertId()]);
    exit;
}

// Delete single image
if ($action === 'delete_img') {
    $imgId = (int)($_GET['img_id'] ?? 0);
    $gid   = (int)($_GET['gallery_id'] ?? 0);
    if ($imgId) dbExec($pdo, "DELETE FROM gallery_images WHERE id=? AND gallery_id=?", [$imgId, $gid]);
    header("Location: /crcap/admin/galleries.php?action=edit&id=$gid&msg=img_deleted");
    exit;
}

if ($action==='delete'&&$id){dbExec($pdo,"DELETE FROM galleries WHERE id=?",[$id]);$msg='deleted';$action='list';}

if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['form_gallery'])){
    $d=['title'=>trim($_POST['title']??''),'slug'=>trim($_POST['slug']??''),'description'=>trim($_POST['description']??''),'category'=>$_POST['category']??'','event_date'=>$_POST['event_date']?:null,'photographer'=>trim($_POST['photographer']??''),'cover_image'=>trim($_POST['cover_image']??''),'status'=>$_POST['status']??'draft','created_by'=>$_SESSION['user_id']];
    if(!$d['slug'])$d['slug']=strtolower(preg_replace('/[^a-z0-9]+/','-',iconv('UTF-8','ASCII//TRANSLIT',$d['title'])));
    try{
        if($id){$sets=implode(',',array_map(fn($k)=>"`$k`=?",array_keys($d)));dbExec($pdo,"UPDATE galleries SET $sets WHERE id=?",[...array_values($d),$id]);$msg='updated';}
        else{$keys=implode(',',array_map(fn($k)=>"`$k`",array_keys($d)));$phs=implode(',',array_fill(0,count($d),'?'));dbExec($pdo,"INSERT INTO galleries ($keys) VALUES ($phs)",array_values($d));$id=(int)$pdo->lastInsertId();$msg='created';}
        $action='edit';
    }catch(Exception $e){$msg='error';}
}

if ($action==='new'||$action==='edit'){
    $gallery=$id?dbFetch($pdo,"SELECT * FROM galleries WHERE id=?",[$id]):[];
    $images=$id?dbFetchAll($pdo,"SELECT * FROM gallery_images WHERE gallery_id=? ORDER BY order_position",[$id]):[];
?>
<div class="flex items-center gap-3 mb-6">
    <a href="/crcap/admin/galleries.php" class="w-8 h-8 rounded-lg bg-white border border-[#001644]/10 flex items-center justify-center hover:border-[#BF8D1A] transition"><i class="fas fa-arrow-left text-xs text-[#001644]"></i></a>
    <h2 class="text-lg font-bold text-[#001644]"><?= $id?'Editar':'Nova' ?> Galeria</h2>
</div>
<?php if($msg==='created'||$msg==='updated'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5">Galeria <?= $msg==='created'?'criada':'atualizada' ?>!</div><?php endif; ?>
<form method="POST" class="grid lg:grid-cols-3 gap-6">
    <input type="hidden" name="form_gallery" value="1">
    <div class="lg:col-span-2 space-y-5">
        <div class="card p-6 space-y-4">
            <div><label class="form-label">Título *</label><input type="text" name="title" value="<?= htmlspecialchars($gallery['title']??'') ?>" required class="form-input"></div>
            <div><label class="form-label">Descrição</label><textarea name="description" rows="3" class="form-input"><?= htmlspecialchars($gallery['description']??'') ?></textarea></div>
            <div><label class="form-label">URL da Capa</label><input type="url" name="cover_image" value="<?= htmlspecialchars($gallery['cover_image']??'') ?>" class="form-input" placeholder="https://..."></div>
        </div>
        <?php if($id&&!empty($images)): ?>
        <div class="card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="form-label">Imagens da Galeria (<span id="imgCount"><?= count($images) ?></span>)</h3>
                <?php if($id): ?>
                <label for="uploadInput" class="btn-primary cursor-pointer text-xs py-1.5 px-3">
                    <i class="fas fa-plus"></i>Adicionar fotos
                </label>
                <input type="file" id="uploadInput" multiple accept="image/*" class="hidden">
                <?php endif; ?>
            </div>
            <!-- Upload progress -->
            <div id="uploadProgress" class="hidden mb-3">
                <div class="bg-[#F8FAFC] rounded-xl p-3 text-xs text-[#022E6B]">
                    <div class="flex items-center gap-2 mb-2"><i class="fas fa-spinner fa-spin text-[#BF8D1A]"></i><span id="progressText">Enviando...</span></div>
                    <div class="h-1.5 bg-[#001644]/10 rounded-full overflow-hidden"><div id="progressBar" class="h-full bg-[#BF8D1A] rounded-full transition-all" style="width:0%"></div></div>
                </div>
            </div>
            <!-- Drop zone -->
            <?php if($id): ?>
            <div id="dropZone" class="border-2 border-dashed border-[#001644]/10 rounded-xl p-6 text-center text-[#001644]/30 mb-4 hover:border-[#BF8D1A]/50 transition cursor-pointer"
                 onclick="document.getElementById('uploadInput').click()">
                <i class="fas fa-cloud-upload-alt text-3xl mb-2 block"></i>
                <p class="text-xs">Arraste fotos aqui ou clique para selecionar</p>
                <p class="text-[10px] mt-1">JPG, PNG, WebP — máx 5MB por foto</p>
            </div>
            <?php endif; ?>
            <!-- Image grid -->
            <div class="grid grid-cols-4 gap-3" id="imgGrid">
                <?php foreach($images as $img): ?>
                <div class="relative group rounded-xl overflow-hidden h-20" id="img-<?= $img['id'] ?>">
                    <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1">
                        <a href="?action=delete_img&gallery_id=<?= $id ?>&img_id=<?= $img['id'] ?>" onclick="return confirm('Remover?')"
                           class="w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-xs"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if($id): ?>
        <script>
        (function(){
            const galleryId = <?= $id ?>;
            const input     = document.getElementById('uploadInput');
            const dropZone  = document.getElementById('dropZone');
            const grid      = document.getElementById('imgGrid');
            const progress  = document.getElementById('uploadProgress');
            const bar       = document.getElementById('progressBar');
            const txt       = document.getElementById('progressText');
            const countEl   = document.getElementById('imgCount');

            // Drag & drop
            dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('border-[#BF8D1A]'); });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-[#BF8D1A]'));
            dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('border-[#BF8D1A]'); uploadFiles(e.dataTransfer.files); });
            input.addEventListener('change', () => uploadFiles(input.files));

            async function uploadFiles(files) {
                const arr = Array.from(files);
                progress.classList.remove('hidden');
                let done = 0;
                for (const file of arr) {
                    txt.textContent = `Enviando ${file.name} (${done+1}/${arr.length})…`;
                    bar.style.width = (done/arr.length*100) + '%';
                    const fd = new FormData();
                    fd.append('file', file);
                    fd.append('type', 'gallery');
                    try {
                        const res  = await fetch('/crcap/api/upload.php', { method:'POST', body:fd });
                        const data = await res.json();
                        if (data.success) {
                            // Save to gallery_images via AJAX action
                            const r2 = await fetch('/admin/galleries.php?action=add_img_ajax', {
                                method:'POST',
                                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                                body:'gallery_id='+galleryId+'&image_path='+encodeURIComponent(data.url)
                            });
                            const d2 = await r2.json();
                            if (d2.success) {
                                const div = document.createElement('div');
                                div.className = 'relative group rounded-xl overflow-hidden h-20';
                                div.id = 'img-'+d2.id;
                                div.innerHTML = `<img src="${data.url}" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                        <a href="?action=delete_img&gallery_id=${galleryId}&img_id=${d2.id}" onclick="return confirm('Remover?')" class="w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-xs"><i class="fas fa-trash"></i></a>
                                    </div>`;
                                grid.insertBefore(div, grid.firstChild);
                                countEl.textContent = parseInt(countEl.textContent) + 1;
                            }
                        } else { alert('Erro: ' + data.message); }
                    } catch(e) { console.error(e); }
                    done++;
                }
                bar.style.width = '100%';
                txt.textContent = `${done} foto(s) adicionada(s)!`;
                setTimeout(() => progress.classList.add('hidden'), 2000);
                input.value = '';
            }
        })();
        </script>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="space-y-5">
        <div class="card p-5 space-y-3">
            <div><label class="form-label">Status</label>
            <select name="status" class="form-input">
                <?php foreach(['draft'=>'Rascunho','published'=>'Publicada','private'=>'Privada'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($gallery['status']??'draft')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select></div>
            <div><label class="form-label">Categoria</label><input type="text" name="category" value="<?= htmlspecialchars($gallery['category']??'') ?>" class="form-input" placeholder="Ex: eventos, formatura"></div>
            <div><label class="form-label">Data do Evento</label><input type="date" name="event_date" value="<?= $gallery['event_date']??'' ?>" class="form-input"></div>
            <div><label class="form-label">Fotógrafo</label><input type="text" name="photographer" value="<?= htmlspecialchars($gallery['photographer']??'') ?>" class="form-input"></div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary flex-1 justify-center"><i class="fas fa-save"></i>Salvar</button>
            <a href="/crcap/admin/galleries.php" class="flex-1 py-2.5 text-xs font-semibold text-[#001644] border-2 border-[#001644]/20 rounded-xl hover:border-[#BF8D1A] transition text-center">Cancelar</a>
        </div>
        <?php if($id): ?><a href="?action=delete&id=<?= $id ?>" onclick="return confirm('Excluir?')" class="btn-danger w-full justify-center" style="display:flex"><i class="fas fa-trash"></i>Excluir</a><?php endif; ?>
    </div>
</form>
<?php
}else{
    $galleries=dbFetchAll($pdo,"SELECT g.*,(SELECT COUNT(*) FROM gallery_images WHERE gallery_id=g.id) AS img_count FROM galleries g ORDER BY g.created_at DESC LIMIT 30");
?>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2"><i class="fas fa-images text-[#BF8D1A]"></i>Galerias de Fotos</h2>
    <a href="?action=new" class="btn-primary"><i class="fas fa-plus"></i>Nova Galeria</a>
</div>
<?php if($msg==='deleted'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5">Galeria excluída.</div><?php endif; ?>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
<?php if(empty($galleries)): ?>
<div class="col-span-3 card p-12 text-center text-[#001644]/30"><i class="fas fa-images text-4xl mb-3 block"></i>Nenhuma galeria criada</div>
<?php else: foreach($galleries as $g): ?>
<div class="card overflow-hidden group">
    <div class="relative h-36">
        <?php if($g['cover_image']): ?><img src="<?= htmlspecialchars($g['cover_image']) ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition"><?php else: ?><div class="w-full h-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center"><i class="fas fa-images text-3xl text-white/30"></i></div><?php endif; ?>
        <div class="absolute top-2 right-2"><span class="badge <?= $g['status']==='published'?'badge-green':'badge-gray' ?>"><?= $g['status'] ?></span></div>
    </div>
    <div class="p-4">
        <h3 class="font-bold text-[#001644] text-sm mb-1 line-clamp-1"><?= htmlspecialchars($g['title']) ?></h3>
        <p class="text-[10px] text-[#022E6B] mb-3"><?= $g['img_count'] ?> foto(s) <?= $g['event_date']?' · '.date('d/m/Y',strtotime($g['event_date'])):'' ?></p>
        <div class="flex gap-2">
            <a href="?action=edit&id=<?= $g['id'] ?>" class="btn-primary flex-1 justify-center py-2"><i class="fas fa-edit"></i>Editar</a>
            <a href="?action=delete&id=<?= $g['id'] ?>" onclick="return confirm('Excluir?')" class="w-9 h-9 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center text-xs"><i class="fas fa-trash"></i></a>
        </div>
    </div>
</div>
<?php endforeach;endif;?>
</div>
<?php }?>
<?php require_once __DIR__ . '/admin_footer.php'; ?>
