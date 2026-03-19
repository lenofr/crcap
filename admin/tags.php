<?php
$pageTitle = 'Tags · Admin CRCAP';
$activeAdm = 'posts';
require_once __DIR__ . '/admin_header.php';

$msg = '';

// ── Delete ────────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $tid = (int)$_GET['delete'];
    dbExec($pdo, "DELETE FROM post_tags WHERE tag_id=?", [$tid]);
    dbExec($pdo, "DELETE FROM tags WHERE id=?", [$tid]);
    $msg = 'deleted';
}

// ── Save (inline form) ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) { http_response_code(403); exit; }

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: slugify($name);
    $id   = (int)($_POST['id'] ?? 0);

    if ($name) {
        try {
            if ($id) {
                dbExec($pdo, "UPDATE tags SET name=?, slug=? WHERE id=?", [$name, $slug, $id]);
                $msg = 'updated';
            } else {
                dbExec($pdo, "INSERT INTO tags (name,slug) VALUES (?,?)", [$name, $slug]);
                $msg = 'created';
            }
        } catch (\Exception $e) {
            $msg = str_contains($e->getMessage(), 'Duplicate') ? 'error_dup' : 'error';
        }
    }
}

function slugify(string $s): string {
    $s = mb_strtolower($s);
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
    return preg_replace('/[^a-z0-9]+/', '-', $s);
}

$search  = trim($_GET['q'] ?? '');
$pg      = max(1, (int)($_GET['p'] ?? 1));
$pp      = 30;
$off     = ($pg - 1) * $pp;

$where   = $search ? "WHERE (t.name LIKE ? OR t.slug LIKE ?)" : "";
$params  = $search ? ["%$search%", "%$search%"] : [];

$tags  = dbFetchAll($pdo,
    "SELECT t.*, COUNT(pt.post_id) AS post_count
     FROM tags t LEFT JOIN post_tags pt ON pt.tag_id=t.id
     $where GROUP BY t.id ORDER BY post_count DESC, t.name ASC
     LIMIT $pp OFFSET $off",
    $params);

$total = dbFetch($pdo, "SELECT COUNT(*) AS n FROM tags $where", $params)['n'] ?? 0;
$pages = ceil($total / $pp);

$editTag = isset($_GET['edit']) ? dbFetch($pdo, "SELECT * FROM tags WHERE id=?", [(int)$_GET['edit']]) : null;
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2">
            <i class="fas fa-hashtag text-[#BF8D1A]"></i>Tags
        </h2>
        <p class="text-xs text-[#022E6B] mt-0.5"><strong><?= number_format($total) ?></strong> tags cadastradas</p>
    </div>
    <a href="/crcap/admin/posts.php" class="btn-gold"><i class="fas fa-newspaper"></i>Ver Posts</a>
</div>

<?php if ($msg === 'created'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5"><i class="fas fa-check-circle mr-2"></i>Tag criada!</div><?php endif; ?>
<?php if ($msg === 'updated'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5"><i class="fas fa-check-circle mr-2"></i>Tag atualizada!</div><?php endif; ?>
<?php if ($msg === 'deleted'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5"><i class="fas fa-trash mr-2"></i>Tag excluída.</div><?php endif; ?>
<?php if ($msg === 'error_dup'): ?><div class="bg-yellow-50 border border-yellow-200 text-yellow-700 text-xs rounded-xl px-4 py-3 mb-5">Essa tag já existe.</div><?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">

    <!-- Form -->
    <div class="space-y-4">
        <div class="card p-5">
            <h3 class="font-bold text-[#001644] text-sm mb-4"><?= $editTag ? 'Editar Tag' : 'Nova Tag' ?></h3>
            <form method="POST" class="space-y-3">
                <?= csrfField() ?>
                <?php if ($editTag): ?><input type="hidden" name="id" value="<?= $editTag['id'] ?>"><?php endif; ?>
                <div>
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" id="tagName" value="<?= h($editTag['name'] ?? '') ?>"
                           required class="form-input" placeholder="Ex: fiscalização">
                </div>
                <div>
                    <label class="form-label">Slug</label>
                    <div class="flex gap-2">
                        <input type="text" name="slug" id="tagSlug" value="<?= h($editTag['slug'] ?? '') ?>"
                               class="form-input flex-1 font-mono text-xs" placeholder="fiscalizacao">
                        <button type="button" onclick="genTagSlug()"
                                class="btn-gold px-3 py-2 text-xs">Auto</button>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fas fa-save"></i><?= $editTag ? 'Salvar' : 'Criar Tag' ?>
                    </button>
                    <?php if ($editTag): ?>
                    <a href="/crcap/admin/tags.php" class="px-4 py-2.5 text-xs font-semibold text-[#001644] border-2 border-[#001644]/20 rounded-xl hover:border-[#BF8D1A] transition">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Bulk create -->
        <div class="card p-5">
            <h3 class="font-bold text-[#001644] text-sm mb-3">Criar Múltiplas</h3>
            <p class="text-xs text-[#022E6B] mb-3">Separe as tags com vírgula</p>
            <form method="POST" id="bulkForm">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="0">
                <textarea name="bulk_tags" rows="3" class="form-input resize-none text-xs"
                          placeholder="palavra-chave, contabilidade, anuidade, registro" id="bulkInput"></textarea>
                <button type="button" onclick="bulkCreate()"
                        class="btn-primary w-full justify-center mt-3">
                    <i class="fas fa-plus-circle"></i>Criar Todas
                </button>
            </form>
        </div>

        <!-- Stats card -->
        <div class="card p-5 space-y-3">
            <h3 class="font-bold text-[#001644] text-sm mb-1">Mais usadas</h3>
            <?php
            $topTags = dbFetchAll($pdo,
                "SELECT t.name, t.slug, COUNT(pt.post_id) AS n
                 FROM tags t LEFT JOIN post_tags pt ON pt.tag_id=t.id
                 GROUP BY t.id ORDER BY n DESC LIMIT 8");
            foreach ($topTags as $tt): ?>
            <div class="flex items-center justify-between">
                <a href="/crcap/admin/posts.php?tag=<?= urlencode($tt['slug']) ?>"
                   class="text-xs text-[#001644] hover:text-[#BF8D1A] transition font-mono">#<?= h($tt['name']) ?></a>
                <span class="text-[10px] font-bold text-[#022E6B] bg-[#001644]/5 rounded-full px-2 py-0.5"><?= $tt['n'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tags grid -->
    <div class="lg:col-span-2">
        <!-- Search -->
        <form method="GET" class="flex gap-2 mb-5">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                <input type="text" name="q" value="<?= h($search) ?>" placeholder="Buscar tags..."
                       class="w-full pl-9 pr-4 py-2.5 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-white">
            </div>
            <button type="submit" class="btn-primary px-4">Buscar</button>
            <?php if ($search): ?><a href="/crcap/admin/tags.php" class="btn-gold px-3"><i class="fas fa-times"></i></a><?php endif; ?>
        </form>

        <!-- Tag cloud + table -->
        <?php if (empty($tags)): ?>
        <div class="card p-16 text-center text-[#001644]/30">
            <i class="fas fa-hashtag text-4xl mb-3 block"></i>
            <p>Nenhuma tag encontrada</p>
        </div>
        <?php else: ?>

        <!-- Visual cloud -->
        <div class="card p-5 mb-5">
            <h3 class="font-bold text-[#001644] text-xs mb-4 uppercase tracking-wider">Nuvem de Tags</h3>
            <div class="flex flex-wrap gap-2">
                <?php
                $maxCount = max(1, max(array_column($tags, 'post_count')));
                foreach ($tags as $t):
                    $size  = max(10, min(16, 10 + (int)(($t['post_count'] / $maxCount) * 6)));
                    $alpha = max(30, min(100, 30 + (int)(($t['post_count'] / $maxCount) * 70)));
                ?>
                <a href="/crcap/admin/tags.php?edit=<?= $t['id'] ?>"
                   class="px-3 py-1.5 rounded-full border transition hover:border-[#BF8D1A] hover:bg-[#BF8D1A]/10"
                   style="font-size:<?= $size ?>px; border-color: rgba(0,22,68,0.<?= $alpha ?>); color: rgba(0,22,68,0.<?= $alpha ?>)">
                    #<?= h($t['name']) ?>
                    <?php if ($t['post_count']): ?><sup class="text-[9px] text-[#BF8D1A]"><?= $t['post_count'] ?></sup><?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#001644]/5 bg-[#F8FAFC]">
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-[#022E6B] uppercase tracking-wider">Tag</th>
                        <th class="px-5 py-3 text-left text-[10px] font-bold text-[#022E6B] uppercase tracking-wider">Slug</th>
                        <th class="px-3 py-3 text-center text-[10px] font-bold text-[#022E6B] uppercase tracking-wider">Posts</th>
                        <th class="px-5 py-3 text-right text-[10px] font-bold text-[#022E6B] uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#001644]/3">
                    <?php foreach ($tags as $t): ?>
                    <tr class="hover:bg-[#F8FAFC] transition group">
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#001644]/5 text-[#001644] rounded-full text-xs font-semibold">
                                <i class="fas fa-hashtag text-[9px]"></i><?= h($t['name']) ?>
                            </span>
                        </td>
                        <td class="px-5 py-3 font-mono text-[10px] text-[#022E6B]"><?= h($t['slug']) ?></td>
                        <td class="px-3 py-3 text-center">
                            <?php if ($t['post_count']): ?>
                            <a href="/crcap/admin/posts.php?tag=<?= urlencode($t['slug']) ?>"
                               class="text-xs font-bold text-[#001644] hover:text-[#BF8D1A] transition"><?= $t['post_count'] ?></a>
                            <?php else: ?>
                            <span class="text-xs text-[#022E6B]/40">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition">
                                <a href="/crcap/admin/tags.php?edit=<?= $t['id'] ?>"
                                   class="w-7 h-7 rounded-lg border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A] flex items-center justify-center text-[10px] transition">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="?delete=<?= $t['id'] ?>" onclick="return confirm('Excluir tag #<?= h($t['name']) ?>?')"
                                   class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center text-[10px] transition">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($pages > 1): ?>
            <div class="flex justify-center gap-2 p-4 border-t border-[#001644]/5">
                <?php for ($i=1;$i<=$pages;$i++): ?>
                <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>"
                   class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-semibold transition
                          <?= $i===$pg ? 'bg-[#001644] text-white' : 'bg-[#F8FAFC] text-[#001644] hover:bg-[#001644]/10' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function genTagSlug() {
    const v = document.getElementById('tagName')?.value || '';
    document.getElementById('tagSlug').value = v.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/[^a-z0-9\s]/g,'').trim().replace(/\s+/g,'-');
}
document.getElementById('tagName')?.addEventListener('blur', () => {
    if (!document.getElementById('tagSlug').value) genTagSlug();
});

async function bulkCreate() {
    const raw  = document.getElementById('bulkInput').value;
    const tags = raw.split(',').map(t => t.trim()).filter(Boolean);
    if (!tags.length) return;

    let created = 0;
    for (const tag of tags) {
        const fd = new FormData();
        fd.append('name', tag);
        fd.append('id', '0');
        fd.append('<?= CSRF_TOKEN_NAME ?>', '<?= csrfToken() ?>');
        const r = await fetch('/admin/tags.php', { method:'POST', body:fd });
        if (r.ok) created++;
    }
    alert(created + ' tag(s) criada(s)!');
    location.reload();
}
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
