<?php
$pageTitle = 'Menu · Admin CRCAP';
$activeAdm = 'menu';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// ── Ações rápidas ─────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    dbExec($pdo, "DELETE FROM menu_items WHERE id=?", [(int)$_GET['delete']]);
    header('Location: ?location='.($_GET['location']??'main').'&msg=deleted'); exit;
}
if (isset($_GET['toggle'])) {
    $cur = dbFetch($pdo, "SELECT status FROM menu_items WHERE id=?", [(int)$_GET['toggle']]);
    if ($cur) dbExec($pdo, "UPDATE menu_items SET status=? WHERE id=?",
        [$cur['status']==='active'?'inactive':'active', (int)$_GET['toggle']]);
    header('Location: ?location='.($_GET['location']??'main').'&msg=updated'); exit;
}
// Reordenar drag-drop (AJAX)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['reorder'])) {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (is_array($ids)) {
        foreach ($ids as $pos => $iid)
            dbExec($pdo, "UPDATE menu_items SET order_position=? WHERE id=?", [$pos+1, (int)$iid]);
    }
    echo json_encode(['ok'=>true]); exit;
}

// ── SAVE ──────────────────────────────────────────────────────────────────
$saveMsg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['form_menu'])) {
    $eid      = (int)($_POST['id'] ?? 0);
    $location = $_POST['menu_location'] ?? 'main';
    $data = [
        'menu_location' => $location,
        'title'         => trim($_POST['title']    ?? ''),
        'url'           => trim($_POST['url']      ?? '') ?: null,
        'icon'          => trim($_POST['icon']      ?? '') ?: null,
        'parent_id'     => $_POST['parent_id']  ? (int)$_POST['parent_id']  : null,
        'target'        => $_POST['target']      ?? '_self',
        'order_position'=> (int)($_POST['order_position'] ?? 0),
        'css_class'     => trim($_POST['css_class'] ?? '') ?: null,
        'status'        => $_POST['status']      ?? 'active',
    ];
    if ($data['title']) {
        try {
            if ($eid) {
                $sets = implode(',', array_map(fn($k)=>"`$k`=?", array_keys($data)));
                dbExec($pdo, "UPDATE menu_items SET $sets WHERE id=?", [...array_values($data), $eid]);
            } else {
                $keys = implode(',', array_map(fn($k)=>"`$k`", array_keys($data)));
                $phs  = implode(',', array_fill(0, count($data), '?'));
                dbExec($pdo, "INSERT INTO menu_items ($keys) VALUES ($phs)", array_values($data));
            }
            header('Location: ?location='.$location.'&msg=saved'); exit;
        } catch (Exception $e) { $saveMsg = 'Erro: '.$e->getMessage(); }
    } else { $saveMsg = 'Título é obrigatório.'; }
}

$location  = $_GET['location'] ?? 'main';
$editItem  = isset($_GET['edit']) ? dbFetch($pdo, "SELECT * FROM menu_items WHERE id=?", [(int)$_GET['edit']]) : null;
$locations = ['main'=>'Menu Principal','footer'=>'Rodapé','sidebar'=>'Barra Lateral'];

// Carrega todos os itens + pais para o select
$allItems  = dbFetchAll($pdo,
    "SELECT m.*, p.title AS parent_title
     FROM menu_items m
     LEFT JOIN menu_items p ON m.parent_id = p.id
     WHERE m.menu_location = ?
     ORDER BY m.order_position ASC, m.id ASC", [$location]);

// Montar árvore para preview e select
$roots = []; $children = [];
foreach ($allItems as $it) {
    if ($it['parent_id'] === null) $roots[] = $it;
    else $children[(int)$it['parent_id']][] = $it;
}

require_once __DIR__ . '/admin_header.php';

$msgMap = ['saved'=>['Item salvo com sucesso!','green'],'deleted'=>['Item excluído.','green'],'updated'=>['Status atualizado!','green']];
$msgGet = $msgMap[$_GET['msg'] ?? ''] ?? null;
?>

<!-- ── Header da página ──────────────────────────────────────────────── -->
<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2">
        <i class="fas fa-bars text-[#BF8D1A]"></i> Gerenciar Menu
    </h2>
    <div class="flex items-center gap-2 text-xs text-[#022E6B]/60">
        <i class="fas fa-info-circle text-[#BF8D1A]"></i>
        Alterações refletem imediatamente no site
    </div>
</div>

<?php if ($msgGet): ?>
<div class="bg-[#006633]/10 border border-[#006633]/30 text-[#001644] text-xs rounded-xl px-4 py-3 mb-5 flex items-center gap-2">
    <i class="fas fa-check-circle text-[#006633]"></i> <?= $msgGet[0] ?>
</div>
<?php endif; ?>
<?php if ($saveMsg): ?>
<div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5"><?= htmlspecialchars($saveMsg) ?></div>
<?php endif; ?>

<!-- ── Tabs de localização ────────────────────────────────────────────── -->
<div class="flex gap-2 mb-6">
    <?php foreach ($locations as $loc => $lbl): ?>
    <a href="?location=<?= $loc ?>"
       class="px-4 py-2 rounded-xl text-xs font-semibold transition
              <?= $location===$loc ? 'bg-[#001644] text-white' : 'bg-white text-[#001644] border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A]' ?>">
        <?= $lbl ?>
        <span class="ml-1.5 opacity-60">(<?= count(dbFetchAll($pdo,"SELECT id FROM menu_items WHERE menu_location=? AND status='active'",[$loc])) ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Grid principal ────────────────────────────────────────────────── -->
<div class="grid lg:grid-cols-5 gap-6">

    <!-- ── FORMULÁRIO ──────────────────────────────────────────────── -->
    <div class="lg:col-span-2 space-y-4">
        <div class="card p-6">
            <h3 class="font-bold text-[#001644] text-sm mb-5 flex items-center gap-2">
                <i class="fas <?= $editItem?'fa-edit':'fa-plus-circle' ?> text-[#BF8D1A]"></i>
                <?= $editItem ? 'Editar Item' : 'Novo Item de Menu' ?>
            </h3>
            <form method="POST" class="space-y-3.5" id="menuForm">
                <input type="hidden" name="form_menu" value="1">
                <?php if ($editItem): ?><input type="hidden" name="id" value="<?= $editItem['id'] ?>"><?php endif; ?>

                <!-- Localização -->
                <div>
                    <label class="form-label">Localização</label>
                    <select name="menu_location" class="form-input text-xs">
                        <?php foreach ($locations as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($editItem['menu_location']??$location)===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Título -->
                <div>
                    <label class="form-label">Título *</label>
                    <input type="text" name="title" value="<?= h($editItem['title']??'') ?>" required
                           class="form-input text-xs" placeholder="Ex: Sobre o CRCAP">
                </div>

                <!-- URL -->
                <div>
                    <label class="form-label">URL</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs">/</span>
                        <input type="text" name="url" value="<?= h($editItem['url']??'') ?>"
                               class="form-input text-xs pl-6" placeholder="crcap/pages/historico.php">
                    </div>
                    <p class="text-[9px] text-[#022E6B]/40 mt-1">URL relativa ou absoluta. Deixe vazio para dropdown-pai.</p>
                </div>

                <!-- Ícone -->
                <div>
                    <label class="form-label">Ícone <span class="text-[9px] font-normal text-[#022E6B]/40">(FontAwesome, sem "fa-")</span></label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span id="iconPreview" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#BF8D1A] text-xs">
                                <?php if($editItem['icon']??''): ?><i class="fas <?= h($editItem['icon']) ?>"></i><?php else: ?><i class="fas fa-tag opacity-30"></i><?php endif; ?>
                            </span>
                            <input type="text" name="icon" id="iconInput" value="<?= h(ltrim($editItem['icon']??'','fa-')) ?>"
                                   class="form-input text-xs pl-8" placeholder="home, users, shield-alt…"
                                   oninput="updateIcon(this.value)">
                        </div>
                    </div>
                    <!-- Ícones frequentes -->
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <?php foreach(['home','users','shield-alt','gavel','file-alt','calendar','graduation-cap','search','envelope','map-marker-alt','bars','star','book','cog'] as $ic): ?>
                        <button type="button" onclick="setIcon('<?= $ic ?>')"
                                class="w-7 h-7 rounded-lg bg-[#F8FAFC] border border-[#001644]/8 hover:border-[#BF8D1A] hover:bg-[#BF8D1A]/5 flex items-center justify-center text-[#001644]/60 hover:text-[#BF8D1A] transition text-xs"
                                title="<?= $ic ?>">
                            <i class="fas fa-<?= $ic ?>"></i>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Item pai -->
                <div>
                    <label class="form-label">Item Pai</label>
                    <select name="parent_id" class="form-input text-xs">
                        <option value="">— Menu raiz (nível 1) —</option>
                        <?php foreach ($roots as $r):
                            if ($editItem && $r['id'] === (int)$editItem['id']) continue; ?>
                        <option value="<?= $r['id'] ?>" <?= ($editItem['parent_id']??'')==$r['id']?'selected':'' ?>>
                            <?= h($r['title']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- css_class (para mega menu) -->
                <div>
                    <label class="form-label flex items-center gap-1.5">
                        Classe CSS
                        <span class="text-[9px] bg-[#BF8D1A]/10 text-[#BF8D1A] px-1.5 py-0.5 rounded font-bold">Mega Menu</span>
                    </label>
                    <select name="css_class" class="form-input text-xs">
                        <option value="" <?= !($editItem['css_class']??'')?'selected':'' ?>>— padrão —</option>
                        <option value="mega"  <?= ($editItem['css_class']??'')==='mega' ?'selected':'' ?>>mega — Ativa mega menu 3 colunas (usar no pai)</option>
                        <option value="col-1" <?= ($editItem['css_class']??'')==='col-1'?'selected':'' ?>>col-1 — Coluna 1: Transparência</option>
                        <option value="col-2" <?= ($editItem['css_class']??'')==='col-2'?'selected':'' ?>>col-2 — Coluna 2: Planejamento</option>
                        <option value="col-3" <?= ($editItem['css_class']??'')==='col-3'?'selected':'' ?>>col-3 — Coluna 3: Compliance</option>
                    </select>
                    <p class="text-[9px] text-[#022E6B]/40 mt-1">Para mega menu: marque o pai como <strong>mega</strong> e os filhos como <strong>col-1/2/3</strong>.</p>
                </div>

                <!-- Ordem + Target + Status -->
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="form-label">Ordem</label>
                        <input type="number" name="order_position" value="<?= $editItem['order_position']??0 ?>"
                               min="0" class="form-input text-xs">
                    </div>
                    <div>
                        <label class="form-label">Abertura</label>
                        <select name="target" class="form-input text-xs">
                            <option value="_self"  <?= ($editItem['target']??'_self')==='_self' ?'selected':'' ?>>Mesma aba</option>
                            <option value="_blank" <?= ($editItem['target']??'')==='_blank'?'selected':'' ?>>Nova aba</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input text-xs">
                            <option value="active"   <?= ($editItem['status']??'active')==='active'  ?'selected':'' ?>>Ativo</option>
                            <option value="inactive" <?= ($editItem['status']??'')==='inactive'?'selected':'' ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex gap-2 pt-1">
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fas fa-save"></i>
                        <?= $editItem ? 'Atualizar' : 'Adicionar' ?>
                    </button>
                    <?php if ($editItem): ?>
                    <a href="?location=<?= $location ?>"
                       class="px-4 py-2.5 border border-[#001644]/15 rounded-xl text-xs font-medium text-[#022E6B] hover:bg-[#F8FAFC] transition text-center">
                        Cancelar
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ── Aviso sobre css_class ─────────────────────────────────── -->
        <?php if ($location === 'main'): ?>
        <div class="card p-4 border-l-4 border-[#BF8D1A]">
            <h4 class="text-xs font-bold text-[#001644] mb-2 flex items-center gap-1.5">
                <i class="fas fa-magic text-[#BF8D1A]"></i> Mega Menu — Como funciona
            </h4>
            <ol class="text-[10px] text-[#022E6B]/70 space-y-1.5 list-decimal list-inside">
                <li>Edite o item <strong>Governança</strong> e defina classe <strong>mega</strong></li>
                <li>Edite cada subitem e defina a coluna: <strong>col-1</strong>, <strong>col-2</strong> ou <strong>col-3</strong></li>
                <li>As colunas aparecem com títulos: Transparência | Planejamento | Compliance</li>
            </ol>
            <div class="mt-3 flex gap-1.5">
                <span class="px-2 py-0.5 bg-[#001644]/8 rounded text-[9px] font-mono text-[#001644]">mega</span>
                <span class="px-2 py-0.5 bg-[#006633]/8 rounded text-[9px] font-mono text-[#006633]">col-1</span>
                <span class="px-2 py-0.5 bg-[#BF8D1A]/10 rounded text-[9px] font-mono text-[#BF8D1A]">col-2</span>
                <span class="px-2 py-0.5 bg-[#022E6B]/8 rounded text-[9px] font-mono text-[#022E6B]">col-3</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── LISTA / PREVIEW ─────────────────────────────────────────────── -->
    <div class="lg:col-span-3 space-y-4">

        <!-- Preview visual do menu atual -->
        <?php if ($location === 'main' && !empty($roots)): ?>
        <div class="card p-4">
            <h3 class="font-bold text-[#001644] text-xs mb-3 flex items-center gap-2">
                <i class="fas fa-eye text-[#BF8D1A]"></i> Preview do Menu
            </h3>
            <div class="flex flex-wrap gap-1.5 p-3 bg-[#F8FAFC] rounded-xl border border-[#001644]/5">
                <?php foreach ($roots as $r):
                    $hasK = !empty($children[(int)$r['id']]);
                    $isMega = str_contains($r['css_class']??'','mega');
                    $inactive = $r['status'] !== 'active';
                ?>
                <div class="relative group/preview">
                    <div class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[10px] font-semibold cursor-default
                                <?= $inactive ? 'opacity-30 line-through bg-red-50 text-red-400' : 'bg-white border border-[#001644]/8 text-[#001644] shadow-sm' ?>">
                        <?php if($r['icon']): ?><i class="fas <?= h($r['icon']) ?> text-[#BF8D1A] text-[8px]"></i><?php endif; ?>
                        <?= h($r['title']) ?>
                        <?php if ($hasK): ?><i class="fas fa-chevron-down text-[7px] opacity-40"></i><?php endif; ?>
                        <?php if ($isMega): ?><span class="ml-1 bg-[#BF8D1A] text-white text-[7px] px-1 py-0.5 rounded font-bold">MEGA</span><?php endif; ?>
                    </div>
                    <?php if ($hasK):
                        $kids = $children[(int)$r['id']]; ?>
                    <div class="absolute top-full left-0 mt-1 hidden group-hover/preview:block bg-white rounded-xl shadow-xl border border-[#001644]/8 z-20 min-w-[160px] py-1">
                        <?php foreach ($kids as $k): ?>
                        <div class="flex items-center gap-1.5 px-3 py-1.5 text-[9px] text-[#001644] <?= $k['status']!=='active'?'opacity-30 line-through':'' ?>">
                            <?php if ($isMega):
                                $cc = $k['css_class']??'';
                                $colBadge = match(true) {
                                    str_contains($cc,'col-3') => ['col-3','#022E6B'],
                                    str_contains($cc,'col-2') => ['col-2','#BF8D1A'],
                                    str_contains($cc,'col-1') => ['col-1','#006633'],
                                    default => ['?','#999'],
                                };
                            ?>
                            <span class="text-[7px] font-bold px-1 py-0.5 rounded" style="background:<?= $colBadge[1] ?>20;color:<?= $colBadge[1] ?>"><?= $colBadge[0] ?></span>
                            <?php endif; ?>
                            <?php if($k['icon']): ?><i class="fas <?= h($k['icon']) ?> text-[8px] text-[#BF8D1A]/60"></i><?php endif; ?>
                            <?= h($k['title']) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="text-[9px] text-[#022E6B]/40 mt-2"><i class="fas fa-mouse-pointer mr-1"></i>Passe o mouse nos itens para ver os submenus</p>
        </div>
        <?php endif; ?>

        <!-- Tabela de itens -->
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-[#001644]/5 flex items-center justify-between">
                <h3 class="font-bold text-[#001644] text-sm">
                    <?= $locations[$location] ?> <span class="text-xs font-normal text-[#022E6B]/50 ml-1"><?= count($allItems) ?> itens</span>
                </h3>
                <span class="text-[10px] text-[#022E6B]/40"><i class="fas fa-sort mr-1"></i>Ordenados por posição</span>
            </div>
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left">Título</th>
                        <th class="hidden md:table-cell text-left text-[10px]">URL</th>
                        <th class="text-center text-[10px]">Classe</th>
                        <th class="text-center text-[10px]">Status</th>
                        <th class="text-center text-[10px]">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($allItems)): ?>
                <tr><td colspan="5" class="text-center py-12 text-[#001644]/25">
                    <i class="fas fa-bars text-3xl mb-3 block"></i>Nenhum item neste menu
                </td></tr>
                <?php else: foreach ($allItems as $item): ?>
                <tr class="<?= $item['status']==='inactive'?'opacity-40':'' ?>">
                    <td>
                        <div class="flex items-center gap-2">
                            <!-- Indentação visual para filhos -->
                            <?php if ($item['parent_id']): ?>
                            <span class="text-[#022E6B]/25 text-xs ml-2">└</span>
                            <?php else: ?>
                            <div class="w-1 h-4 bg-[#BF8D1A]/40 rounded-full flex-shrink-0"></div>
                            <?php endif; ?>
                            <?php if ($item['icon']): ?>
                            <div class="w-6 h-6 rounded-lg bg-[#F8FAFC] flex items-center justify-center flex-shrink-0">
                                <i class="fas <?= h($item['icon']) ?> text-[#BF8D1A] text-[10px]"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <p class="font-semibold text-[#001644] text-xs"><?= h($item['title']) ?></p>
                                <?php if ($item['parent_title']): ?>
                                <p class="text-[9px] text-[#022E6B]/40">em: <?= h($item['parent_title']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="hidden md:table-cell">
                        <?php if ($item['url']): ?>
                        <span class="text-[9px] text-[#022E6B]/60 font-mono bg-[#F8FAFC] px-2 py-0.5 rounded truncate block max-w-[160px]">
                            <?= h(substr($item['url'],0,28)).(strlen($item['url'])>28?'…':'') ?>
                        </span>
                        <?php else: ?>
                        <span class="text-[9px] text-[#022E6B]/25 italic">dropdown</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($item['css_class']): ?>
                        <?php $ccColor = match($item['css_class']) {
                            'mega'  => 'bg-[#001644]/8 text-[#001644]',
                            'col-1' => 'bg-[#006633]/10 text-[#006633]',
                            'col-2' => 'bg-[#BF8D1A]/10 text-[#BF8D1A]',
                            'col-3' => 'bg-[#022E6B]/8 text-[#022E6B]',
                            default => 'bg-[#F8FAFC] text-[#022E6B]/60',
                        }; ?>
                        <span class="text-[9px] font-mono font-bold px-2 py-0.5 rounded <?= $ccColor ?>">
                            <?= h($item['css_class']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-[#001644]/15 text-[9px]">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $item['status']==='active'?'badge-green':'badge-gray' ?> text-[9px]">
                            <?= $item['status']==='active'?'Ativo':'Inativo' ?>
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="?edit=<?= $item['id'] ?>&location=<?= $location ?>"
                               class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"
                               title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?toggle=<?= $item['id'] ?>&location=<?= $location ?>"
                               class="w-7 h-7 rounded-lg bg-[#BF8D1A]/8 hover:bg-[#BF8D1A] hover:text-white text-[#BF8D1A] flex items-center justify-center transition text-xs"
                               title="<?= $item['status']==='active'?'Desativar':'Ativar' ?>">
                                <i class="fas fa-eye<?= $item['status']==='inactive'?'-slash':'' ?>"></i>
                            </a>
                            <?php if ($item['url']): ?>
                            <a href="<?= h($item['url']) ?>" target="_blank"
                               class="w-7 h-7 rounded-lg bg-[#006633]/8 hover:bg-[#006633] hover:text-white text-[#006633] flex items-center justify-center transition text-xs"
                               title="Abrir URL">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <?php endif; ?>
                            <a href="?delete=<?= $item['id'] ?>&location=<?= $location ?>"
                               onclick="return confirm('Excluir \'<?= addslashes($item['title']) ?>\'?')"
                               class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-400 flex items-center justify-center transition text-xs"
                               title="Excluir">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    </div><!-- /col-span-3 -->
</div><!-- /grid -->

<script>
function updateIcon(v) {
    const ic = document.getElementById('iconPreview');
    ic.innerHTML = v ? `<i class="fas fa-${v}"></i>` : `<i class="fas fa-tag opacity-30"></i>`;
}
function setIcon(v) {
    document.querySelector('[name="icon"]').value = v;
    updateIcon(v);
}
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>