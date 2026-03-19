<?php
$pageTitle = 'Categorias · Admin CRCAP';
$activeAdm = 'posts';
require_once __DIR__ . '/admin_header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';

// ── Delete ────────────────────────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    $cnt = dbFetch($pdo, "SELECT COUNT(*) AS n FROM posts WHERE category_id=?", [$id])['n'] ?? 0;
    if ($cnt > 0) {
        $msg = 'has_posts';
    } else {
        dbExec($pdo, "DELETE FROM categories WHERE id=?", [$id]);
        $msg = 'deleted';
    }
    $action = 'list';
}

// ── Save ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_cat'])) {
    if (!csrfVerify()) { http_response_code(403); exit; }

    $name     = trim($_POST['name'] ?? '');
    $slug     = trim($_POST['slug'] ?? '') ?: slugify($name);
    $desc     = trim($_POST['description'] ?? '');
    $icon     = trim($_POST['icon'] ?? '');
    $color    = trim($_POST['color'] ?? '#001644');
    $parentId = (int)$_POST['parent_id'] ?: null;
    $order    = (int)($_POST['order_position'] ?? 0);
    $status   = $_POST['status'] ?? 'active';

    if (!$name) { $msg = 'error_name'; }
    else {
        try {
            if ($id) {
                dbExec($pdo,
                    "UPDATE categories SET name=?,slug=?,description=?,icon=?,color=?,parent_id=?,order_position=?,status=? WHERE id=?",
                    [$name, $slug, $desc, $icon, $color, $parentId, $order, $status, $id]);
                $msg = 'updated';
            } else {
                dbExec($pdo,
                    "INSERT INTO categories (name,slug,description,icon,color,parent_id,order_position,status) VALUES (?,?,?,?,?,?,?,?)",
                    [$name, $slug, $desc, $icon, $color, $parentId, $order, $status]);
                $msg = 'created';
                $id  = (int)$pdo->lastInsertId();
            }
        } catch (\Exception $e) {
            $msg = str_contains($e->getMessage(), 'Duplicate') ? 'error_slug' : 'error';
        }
    }
    $action = 'list';
}

// ── Edit data ─────────────────────────────────────────────────────────────────
$cat = $id ? dbFetch($pdo, "SELECT * FROM categories WHERE id=?", [$id]) : null;

// ── All categories (for parent select + list) ─────────────────────────────────
$allCats = dbFetchAll($pdo, "SELECT c.*, (SELECT COUNT(*) FROM posts WHERE category_id=c.id) AS post_count FROM categories c ORDER BY c.parent_id IS NOT NULL, c.order_position, c.name");

// Build tree
$roots = array_filter($allCats, fn($c) => !$c['parent_id']);
$children = [];
foreach ($allCats as $c) {
    if ($c['parent_id']) $children[$c['parent_id']][] = $c;
}

function slugify(string $s): string {
    $s = mb_strtolower($s);
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
    return preg_replace('/[^a-z0-9]+/', '-', $s);
}
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2">
            <i class="fas fa-tags text-[#BF8D1A]"></i>Categorias
        </h2>
        <p class="text-xs text-[#022E6B] mt-0.5">Organize notícias por categorias</p>
    </div>
    <div class="flex gap-2">
        <a href="/crcap/admin/posts.php" class="btn-gold"><i class="fas fa-newspaper"></i>Ver Posts</a>
        <button onclick="document.getElementById('formCard').classList.toggle('hidden')" class="btn-primary">
            <i class="fas fa-plus"></i>Nova Categoria
        </button>
    </div>
</div>

<!-- Alerts -->
<?php if ($msg === 'created'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5"><i class="fas fa-check-circle mr-2"></i>Categoria criada com sucesso!</div><?php endif; ?>
<?php if ($msg === 'updated'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5"><i class="fas fa-check-circle mr-2"></i>Categoria atualizada!</div><?php endif; ?>
<?php if ($msg === 'deleted'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5"><i class="fas fa-trash mr-2"></i>Categoria excluída.</div><?php endif; ?>
<?php if ($msg === 'has_posts'): ?><div class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-xs rounded-xl px-4 py-3 mb-5"><i class="fas fa-exclamation-triangle mr-2"></i>Não é possível excluir: existem posts vinculados a esta categoria.</div><?php endif; ?>
<?php if ($msg === 'error_slug'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5"><i class="fas fa-times-circle mr-2"></i>Slug já existe. Escolha outro.</div><?php endif; ?>

<!-- Form Card -->
<div id="formCard" class="card p-6 mb-6 <?= $cat ? '' : 'hidden' ?>">
    <h3 class="font-bold text-[#001644] text-sm mb-5"><?= $cat ? 'Editar: ' . h($cat['name']) : 'Nova Categoria' ?></h3>
    <form method="POST" id="catForm">
        <?= csrfField() ?>
        <input type="hidden" name="form_cat" value="1">
        <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"> <?php endif; ?>

        <div class="grid md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="form-label">Nome *</label>
                <input type="text" name="name" value="<?= h($cat['name'] ?? '') ?>" required
                       class="form-input" placeholder="Ex: Notícias" id="catName">
            </div>
            <div>
                <label class="form-label">Slug (URL)</label>
                <div class="flex gap-2">
                    <input type="text" name="slug" value="<?= h($cat['slug'] ?? '') ?>"
                           class="form-input flex-1 font-mono text-xs" placeholder="ex: noticias" id="catSlug">
                    <button type="button" onclick="genSlug()" class="btn-gold px-3 py-2 text-xs">Auto</button>
                </div>
            </div>
            <div>
                <label class="form-label">Ícone (Font Awesome)</label>
                <div class="flex gap-2 items-center">
                    <input type="text" name="icon" value="<?= h($cat['icon'] ?? '') ?>"
                           class="form-input flex-1" placeholder="fa-newspaper" id="iconInput">
                    <div id="iconPreview" class="w-9 h-9 rounded-lg bg-[#001644]/5 flex items-center justify-center text-[#001644]">
                        <i class="fas <?= h($cat['icon'] ?? 'fa-tag') ?> text-sm"></i>
                    </div>
                </div>
            </div>
            <div>
                <label class="form-label">Cor</label>
                <div class="flex gap-2 items-center">
                    <input type="color" name="color" value="<?= h($cat['color'] ?? '#001644') ?>"
                           class="w-10 h-10 rounded-lg border border-[#001644]/10 cursor-pointer p-1">
                    <input type="text" id="colorHex" value="<?= h($cat['color'] ?? '#001644') ?>"
                           class="form-input flex-1 font-mono text-xs" maxlength="7">
                </div>
            </div>
            <div>
                <label class="form-label">Categoria Pai</label>
                <select name="parent_id" class="form-input">
                    <option value="">– Nenhuma (categoria raiz) –</option>
                    <?php foreach ($allCats as $c):
                        if ($c['id'] === $id) continue; // Prevent self-reference
                    ?>
                    <option value="<?= $c['id'] ?>" <?= ($cat['parent_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
                        <?= $c['parent_id'] ? '↳ ' : '' ?><?= h($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Ordem de Exibição</label>
                <input type="number" name="order_position" value="<?= (int)($cat['order_position'] ?? 0) ?>"
                       class="form-input" min="0" placeholder="0">
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Descrição</label>
                <textarea name="description" rows="2" class="form-input resize-none"
                          placeholder="Breve descrição da categoria"><?= h($cat['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="active" <?= ($cat['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativa</option>
                    <option value="inactive" <?= ($cat['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativa</option>
                </select>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i>Salvar Categoria</button>
            <button type="button" onclick="document.getElementById('formCard').classList.add('hidden')"
                    class="px-4 py-2.5 text-xs font-semibold text-[#001644] border-2 border-[#001644]/20 rounded-xl hover:border-[#BF8D1A] transition">
                Cancelar
            </button>
            <?php if ($id): ?>
            <a href="?action=delete&id=<?= $id ?>" onclick="return confirm('Excluir categoria?')"
               class="btn-danger ml-auto"><i class="fas fa-trash"></i>Excluir</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Categories Tree List -->
<div class="card overflow-hidden">
    <div class="p-5 border-b border-[#001644]/5 flex items-center justify-between">
        <h3 class="font-bold text-[#001644] text-sm"><?= count($allCats) ?> categorias cadastradas</h3>
        <span class="text-xs text-[#022E6B]/60">Hierarquia de categorias</span>
    </div>

    <?php if (empty($allCats)): ?>
    <div class="p-16 text-center text-[#001644]/30">
        <i class="fas fa-tags text-4xl mb-3 block"></i>
        <p class="text-sm">Nenhuma categoria cadastrada</p>
    </div>
    <?php else: ?>
    <div class="divide-y divide-[#001644]/3">
        <?php
        function renderCatRow(array $cat, int $depth, array $children): void {
            $indent = $depth > 0 ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . '↳ ' : '';
        ?>
        <div class="flex items-center gap-4 px-5 py-3 hover:bg-[#F8FAFC] transition group">
            <!-- Color + Icon -->
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white flex-shrink-0"
                 style="background:<?= h($cat['color'] ?? '#001644') ?>">
                <i class="fas <?= h($cat['icon'] ?? 'fa-tag') ?> text-xs"></i>
            </div>
            <!-- Name -->
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-[#001644]">
                    <?= $indent ?><?= h($cat['name']) ?>
                </p>
                <p class="text-[10px] text-[#022E6B]">
                    <span class="font-mono">/<?= h($cat['slug']) ?></span>
                    <?php if ($cat['description']): ?>
                    · <?= h(mb_substr($cat['description'], 0, 50)) ?><?= mb_strlen($cat['description']) > 50 ? '…' : '' ?>
                    <?php endif; ?>
                </p>
            </div>
            <!-- Posts count -->
            <div class="text-center flex-shrink-0 w-14">
                <p class="text-sm font-bold text-[#001644]"><?= number_format($cat['post_count']) ?></p>
                <p class="text-[9px] text-[#022E6B]">post<?= $cat['post_count'] != 1 ? 's' : '' ?></p>
            </div>
            <!-- Status -->
            <span class="badge <?= $cat['status'] === 'active' ? 'badge-green' : 'badge-gray' ?> flex-shrink-0">
                <?= $cat['status'] === 'active' ? 'Ativa' : 'Inativa' ?>
            </span>
            <!-- Actions -->
            <div class="flex gap-1.5 flex-shrink-0 opacity-0 group-hover:opacity-100 transition">
                <a href="?action=edit&id=<?= $cat['id'] ?>"
                   onclick="document.getElementById('formCard').classList.remove('hidden')"
                   class="w-7 h-7 rounded-lg border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A] flex items-center justify-center transition text-[10px]">
                    <i class="fas fa-pen"></i>
                </a>
                <a href="/crcap/admin/posts.php?categoria=<?= $cat['id'] ?>"
                   class="w-7 h-7 rounded-lg border border-[#001644]/10 hover:border-[#022E6B] flex items-center justify-center transition text-[10px]" title="Ver posts">
                    <i class="fas fa-newspaper text-[#022E6B]"></i>
                </a>
                <?php if (!$cat['post_count']): ?>
                <a href="?action=delete&id=<?= $cat['id'] ?>" onclick="return confirm('Excluir?')"
                   class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-[10px]">
                    <i class="fas fa-trash"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
            // Render children
            if (isset($children[$cat['id']])) {
                foreach ($children[$cat['id']] as $child) {
                    renderCatRow($child, $depth + 1, $children);
                }
            }
        }
        foreach ($roots as $root) {
            renderCatRow($root, 0, $children);
        }
        ?>
    </div>
    <?php endif; ?>
</div>

<script>
function genSlug() {
    const name = document.getElementById('catName').value;
    const slug = name.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim().replace(/\s+/g, '-');
    document.getElementById('catSlug').value = slug;
}
document.getElementById('catName')?.addEventListener('blur', function() {
    if (!document.getElementById('catSlug').value) genSlug();
});

// Icon preview
document.getElementById('iconInput')?.addEventListener('input', function() {
    document.getElementById('iconPreview').innerHTML =
        '<i class="fas ' + this.value + ' text-sm"></i>';
});

// Color sync
const colorPicker = document.querySelector('input[type=color]');
const colorHex    = document.getElementById('colorHex');
colorPicker?.addEventListener('input', e => { colorHex.value = e.target.value; });
colorHex?.addEventListener('input', e => {
    if (/^#[0-9a-f]{6}$/i.test(e.target.value)) colorPicker.value = e.target.value;
});

// Open form if editing
<?php if ($cat): ?>
document.getElementById('formCard')?.classList.remove('hidden');
document.getElementById('formCard')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
