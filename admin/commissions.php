<?php
$pageTitle = 'Comissões & Links Rápidos · Admin CRCAP';
$activeAdm = 'commissions';
require_once __DIR__ . '/admin_header.php';

$msg = '';

// ── Quick Links CRUD ──────────────────────────────────────────────────────────
if (isset($_GET['delete_ql'])) {
    dbExec($pdo, "DELETE FROM quick_links WHERE id=?", [(int)$_GET['delete_ql']]);
    header('Location: /crcap/admin/commissions.php?msg=deleted'); exit;
}
if (isset($_GET['toggle_ql'])) {
    $ql = dbFetch($pdo, "SELECT status FROM quick_links WHERE id=?", [(int)$_GET['toggle_ql']]);
    if ($ql) dbExec($pdo, "UPDATE quick_links SET status=? WHERE id=?", [$ql['status']==='active'?'inactive':'active', (int)$_GET['toggle_ql']]);
    header('Location: /crcap/admin/commissions.php'); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['form_ql'])) {
    $d = ['title'=>trim($_POST['ql_title']??''),'icon'=>trim($_POST['ql_icon']??'fa-link'),'url'=>trim($_POST['ql_url']??''),'order_position'=>(int)($_POST['ql_order']??0),'status'=>$_POST['ql_status']??'active'];
    $id = (int)($_POST['ql_id']??0);
    if ($id) { $sets=implode(',',array_map(fn($k)=>"`$k`=?",array_keys($d))); dbExec($pdo,"UPDATE quick_links SET $sets WHERE id=?",[...array_values($d),$id]); }
    else { $keys=implode(',',array_map(fn($k)=>"`$k`",array_keys($d))); $phs=implode(',',array_fill(0,count($d),'?')); dbExec($pdo,"INSERT INTO quick_links ($keys) VALUES ($phs)",array_values($d)); }
    header('Location: /crcap/admin/commissions.php?msg=saved'); exit;
}

// ── Commissions CRUD ──────────────────────────────────────────────────────────
if (isset($_GET['delete_c'])) {
    dbExec($pdo, "DELETE FROM commissions WHERE id=?", [(int)$_GET['delete_c']]);
    header('Location: /crcap/admin/commissions.php?msg=deleted'); exit;
}
if (isset($_GET['toggle_c'])) {
    $c = dbFetch($pdo, "SELECT status FROM commissions WHERE id=?", [(int)$_GET['toggle_c']]);
    if ($c) dbExec($pdo, "UPDATE commissions SET status=? WHERE id=?", [$c['status']==='active'?'inactive':'active', (int)$_GET['toggle_c']]);
    header('Location: /crcap/admin/commissions.php'); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['form_commission'])) {
    $d = [
        'title'         => trim($_POST['c_title']??''),
        'description'   => trim($_POST['c_desc']??''),
        'icon'          => trim($_POST['c_icon']??'fa-users'),
        'gradient_from' => trim($_POST['c_grad_from']??'#001644'),
        'gradient_to'   => trim($_POST['c_grad_to']??'#022E6B'),
        'badge_text'    => trim($_POST['c_badge']??''),
        'badge_color'   => trim($_POST['c_badge_color']??'#BF8D1A'),
        'info1'         => trim($_POST['c_info1']??''),
        'info2'         => trim($_POST['c_info2']??''),
        'link_url'      => trim($_POST['c_url']??''),
        'order_position'=> (int)($_POST['c_order']??0),
        'status'        => $_POST['c_status']??'active',
    ];
    $id = (int)($_POST['c_id']??0);
    if ($id) { $sets=implode(',',array_map(fn($k)=>"`$k`=?",array_keys($d))); dbExec($pdo,"UPDATE commissions SET $sets WHERE id=?",[...array_values($d),$id]); }
    else { $keys=implode(',',array_map(fn($k)=>"`$k`",array_keys($d))); $phs=implode(',',array_fill(0,count($d),'?')); dbExec($pdo,"INSERT INTO commissions ($keys) VALUES ($phs)",array_values($d)); }
    header('Location: /crcap/admin/commissions.php?msg=saved'); exit;
}

$commissions = dbFetchAll($pdo, "SELECT * FROM commissions ORDER BY order_position ASC, id ASC");
$quickLinks  = dbFetchAll($pdo, "SELECT * FROM quick_links ORDER BY order_position ASC, id ASC");
$editC  = isset($_GET['edit_c'])  ? dbFetch($pdo,"SELECT * FROM commissions WHERE id=?", [(int)$_GET['edit_c']])  : null;
$editQL = isset($_GET['edit_ql']) ? dbFetch($pdo,"SELECT * FROM quick_links WHERE id=?", [(int)$_GET['edit_ql']]) : null;
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2">
        <i class="fas fa-users text-[#BF8D1A]"></i>Comissões & Links Rápidos
    </h2>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5">
    <?= $_GET['msg']==='saved'?'✅ Salvo com sucesso!':'🗑️ Excluído.' ?>
</div>
<?php endif; ?>

<!-- ══ COMISSÕES ══════════════════════════════════════════════════════════════ -->
<div class="card p-6 mb-8">
    <h3 class="font-bold text-[#001644] text-sm mb-5 flex items-center gap-2">
        <i class="fas fa-layer-group text-[#BF8D1A]"></i> Comissões em Destaque (Home)
    </h3>

    <!-- Form Comissão -->
    <form method="POST" class="bg-[#F8FAFC] rounded-xl p-5 mb-6 border border-[#001644]/5">
        <input type="hidden" name="form_commission" value="1">
        <input type="hidden" name="c_id" value="<?= $editC['id'] ?? 0 ?>">
        <h4 class="font-semibold text-[#001644] text-xs mb-4"><?= $editC ? '✏️ Editar Comissão' : '➕ Nova Comissão' ?></h4>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Título *</label>
                <input type="text" name="c_title" value="<?= htmlspecialchars($editC['title']??'') ?>" required class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Link da página</label>
                <input type="text" name="c_url" value="<?= htmlspecialchars($editC['link_url']??'') ?>" placeholder="/crcap/pages/comissoes.php" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-[#001644] mb-1">Descrição</label>
                <textarea name="c_desc" rows="2" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]"><?= htmlspecialchars($editC['description']??'') ?></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Ícone FontAwesome</label>
                <input type="text" name="c_icon" value="<?= htmlspecialchars($editC['icon']??'fa-users') ?>" placeholder="fa-users" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
                <p class="text-[10px] text-[#022E6B] mt-1">Ex: fa-users, fa-hand-heart, fa-venus</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Badge (etiqueta)</label>
                <div class="flex gap-2">
                    <input type="text" name="c_badge" value="<?= htmlspecialchars($editC['badge_text']??'') ?>" placeholder="Nova" class="flex-1 px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
                    <input type="color" name="c_badge_color" value="<?= $editC['badge_color']??'#BF8D1A' ?>" title="Cor do badge" class="w-10 h-9 rounded-lg border border-[#001644]/10 cursor-pointer">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Gradiente (cores do ícone)</label>
                <div class="flex gap-2 items-center">
                    <input type="color" name="c_grad_from" value="<?= $editC['gradient_from']??'#001644' ?>" title="Cor inicial" class="w-10 h-9 rounded-lg border border-[#001644]/10 cursor-pointer">
                    <span class="text-[10px] text-[#022E6B]">→</span>
                    <input type="color" name="c_grad_to" value="<?= $editC['gradient_to']??'#022E6B' ?>" title="Cor final" class="w-10 h-9 rounded-lg border border-[#001644]/10 cursor-pointer">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Info 1 (ex: membros)</label>
                <input type="text" name="c_info1" value="<?= htmlspecialchars($editC['info1']??'') ?>" placeholder="230 membros" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Info 2 (ex: frequência)</label>
                <input type="text" name="c_info2" value="<?= htmlspecialchars($editC['info2']??'') ?>" placeholder="Quinzenal" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Ordem</label>
                <input type="number" name="c_order" value="<?= $editC['order_position']??0 ?>" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Status</label>
                <select name="c_status" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
                    <option value="active"   <?= ($editC['status']??'active')==='active'  ?'selected':'' ?>>✅ Ativo</option>
                    <option value="inactive" <?= ($editC['status']??'')==='inactive'?'selected':'' ?>>⏸ Inativo</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button type="submit" class="btn-primary text-xs px-4 py-2"><i class="fas fa-save"></i><?= $editC ? 'Atualizar' : 'Salvar' ?></button>
            <?php if ($editC): ?><a href="/crcap/admin/commissions.php" class="btn-secondary text-xs px-4 py-2">Cancelar</a><?php endif; ?>
        </div>
    </form>

    <!-- Lista de Comissões -->
    <?php if (empty($commissions)): ?>
    <p class="text-xs text-[#022E6B] text-center py-4">Nenhuma comissão cadastrada.</p>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($commissions as $c): ?>
        <div class="flex items-center gap-4 bg-white border border-[#001644]/5 rounded-xl p-4 hover:border-[#BF8D1A]/30 transition">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm flex-shrink-0"
                 style="background: linear-gradient(135deg, <?= htmlspecialchars($c['gradient_from']) ?>, <?= htmlspecialchars($c['gradient_to']) ?>)">
                <i class="fas <?= htmlspecialchars($c['icon']) ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-[#001644] text-xs"><?= htmlspecialchars($c['title']) ?></span>
                    <?php if ($c['badge_text']): ?>
                    <span class="text-[9px] font-semibold px-2 py-0.5 rounded-full" style="background:<?= htmlspecialchars($c['badge_color']) ?>20;color:<?= htmlspecialchars($c['badge_color']) ?>"><?= htmlspecialchars($c['badge_text']) ?></span>
                    <?php endif; ?>
                    <span class="text-[9px] px-2 py-0.5 rounded-full <?= $c['status']==='active'?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500' ?>"><?= $c['status']==='active'?'Ativo':'Inativo' ?></span>
                </div>
                <p class="text-[10px] text-[#022E6B] mt-0.5 truncate"><?= htmlspecialchars(substr($c['description'],0,80)) ?>...</p>
                <p class="text-[10px] text-[#BF8D1A] mt-0.5"><?= htmlspecialchars($c['info1']) ?> · <?= htmlspecialchars($c['info2']) ?></p>
            </div>
            <div class="flex gap-1.5 flex-shrink-0">
                <a href="?edit_c=<?= $c['id'] ?>" class="w-8 h-8 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"><i class="fas fa-edit"></i></a>
                <a href="?toggle_c=<?= $c['id'] ?>" class="w-8 h-8 rounded-lg bg-[#006633]/10 hover:bg-[#006633] hover:text-white text-[#006633] flex items-center justify-center transition text-xs"><i class="fas <?= $c['status']==='active'?'fa-eye-slash':'fa-eye' ?>"></i></a>
                <a href="?delete_c=<?= $c['id'] ?>" onclick="return confirm('Excluir comissão?')" class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs"><i class="fas fa-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══ LINKS RÁPIDOS ══════════════════════════════════════════════════════════ -->
<div class="card p-6">
    <h3 class="font-bold text-[#001644] text-sm mb-5 flex items-center gap-2">
        <i class="fas fa-th text-[#BF8D1A]"></i> Links Rápidos (Grade da Sidebar)
    </h3>

    <!-- Form Quick Link -->
    <form method="POST" class="bg-[#F8FAFC] rounded-xl p-5 mb-6 border border-[#001644]/5">
        <input type="hidden" name="form_ql" value="1">
        <input type="hidden" name="ql_id" value="<?= $editQL['id'] ?? 0 ?>">
        <h4 class="font-semibold text-[#001644] text-xs mb-4"><?= $editQL ? '✏️ Editar Link' : '➕ Novo Link' ?></h4>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Título *</label>
                <input type="text" name="ql_title" value="<?= htmlspecialchars($editQL['title']??'') ?>" required class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">Ícone FontAwesome</label>
                <input type="text" name="ql_icon" value="<?= htmlspecialchars($editQL['icon']??'fa-link') ?>" placeholder="fa-link" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#001644] mb-1">URL *</label>
                <input type="text" name="ql_url" value="<?= htmlspecialchars($editQL['url']??'') ?>" required placeholder="/crcap/pages/..." class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-[#001644] mb-1">Ordem</label>
                    <input type="number" name="ql_order" value="<?= $editQL['order_position']??0 ?>" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-[#001644] mb-1">Status</label>
                    <select name="ql_status" class="w-full px-3 py-2 text-xs border border-[#001644]/10 rounded-lg focus:outline-none focus:border-[#BF8D1A]">
                        <option value="active"   <?= ($editQL['status']??'active')==='active'  ?'selected':'' ?>>Ativo</option>
                        <option value="inactive" <?= ($editQL['status']??'')==='inactive'?'selected':'' ?>>Inativo</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button type="submit" class="btn-primary text-xs px-4 py-2"><i class="fas fa-save"></i><?= $editQL ? 'Atualizar' : 'Salvar' ?></button>
            <?php if ($editQL): ?><a href="/crcap/admin/commissions.php" class="btn-secondary text-xs px-4 py-2">Cancelar</a><?php endif; ?>
        </div>
    </form>

    <!-- Lista Quick Links -->
    <?php if (empty($quickLinks)): ?>
    <p class="text-xs text-[#022E6B] text-center py-4">Nenhum link rápido cadastrado.</p>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($quickLinks as $ql): ?>
        <div class="flex items-center gap-3 bg-white border border-[#001644]/5 rounded-xl p-3 hover:border-[#BF8D1A]/30 transition">
            <div class="w-9 h-9 rounded-lg bg-[#001644]/5 flex items-center justify-center text-[#001644] text-sm flex-shrink-0">
                <i class="fas <?= htmlspecialchars($ql['icon']) ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-[#001644] text-xs"><?= htmlspecialchars($ql['title']) ?></p>
                <p class="text-[9px] text-[#022E6B] truncate"><?= htmlspecialchars($ql['url']) ?></p>
            </div>
            <div class="flex gap-1 flex-shrink-0">
                <a href="?edit_ql=<?= $ql['id'] ?>" class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-[10px]"><i class="fas fa-edit"></i></a>
                <a href="?toggle_ql=<?= $ql['id'] ?>" class="w-7 h-7 rounded-lg bg-[#006633]/10 hover:bg-[#006633] hover:text-white text-[#006633] flex items-center justify-center transition text-[10px]"><i class="fas <?= $ql['status']==='active'?'fa-eye-slash':'fa-eye' ?>"></i></a>
                <a href="?delete_ql=<?= $ql['id'] ?>" onclick="return confirm('Excluir?')" class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-[10px]"><i class="fas fa-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/admin_footer.php'; ?>
