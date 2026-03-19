<?php
$pageTitle = 'Documentos · Admin CRCAP';
$activeAdm = 'documents';
require_once __DIR__ . '/admin_header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';

// ── Helpers inline (usam echo, sem retorno de string) ─────────────────────────
function docRowHtml(array $d): void {
    $ext  = strtolower($d['file_type'] ?? '');
    $icon = 'fa-file-alt';
    $bg   = 'bg-gray-100 text-gray-500';
    if ($ext === 'pdf')                      { $icon = 'fa-file-pdf';   $bg = 'bg-red-100 text-red-600'; }
    elseif ($ext === 'doc' || $ext === 'docx') { $icon = 'fa-file-word';  $bg = 'bg-blue-100 text-blue-600'; }
    elseif ($ext === 'xls' || $ext === 'xlsx') { $icon = 'fa-file-excel'; $bg = 'bg-green-100 text-green-600'; }
    $name = htmlspecialchars($d['title'] ?? '');
    $file = htmlspecialchars($d['file_name'] ?? '');
    echo '<div class="flex items-center gap-3 py-0.5">';
    echo '<div class="w-8 h-8 rounded-lg ' . $bg . ' flex items-center justify-center text-xs flex-shrink-0">';
    echo '<i class="fas ' . $icon . '"></i></div>';
    echo '<div><p class="font-semibold text-[#001644] text-xs line-clamp-1">' . $name . '</p>';
    echo '<p class="text-[9px] text-[#022E6B]/60">' . $file . '</p></div></div>';
}
function docActionsHtml(array $d): void {
    $id = (int)$d['id'];
    echo '<div class="flex items-center justify-center gap-1 py-1">';
    echo '<a href="?action=edit&id=' . $id . '" class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"><i class="fas fa-edit"></i></a>';
    if (!empty($d['file_path'])) {
        echo '<a href="/crcap/download.php?id=' . $id . '" class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#BF8D1A] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"><i class="fas fa-download"></i></a>';
    }
    echo "<a href='?action=delete&id=" . $id . "' onclick='return confirm(\"Excluir?\")' class='w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs'><i class='fas fa-trash'></i></a>";
    echo '</div>';
}


if ($action === 'delete' && $id) {
    $doc = dbFetch($pdo,"SELECT file_path FROM documents WHERE id=?",[$id]);
    if ($doc && file_exists(dirname(__DIR__).'/'.$doc['file_path'])) @unlink(dirname(__DIR__).'/'.$doc['file_path']);
    dbExec($pdo,"DELETE FROM documents WHERE id=?",[$id]);
    $msg = 'deleted'; $action = 'list';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_doc'])) {
    $d = [
        'title'            => trim($_POST['title']??''),
        'description'      => trim($_POST['description']??''),
        'category'         => $_POST['category']??'',
        'document_type'    => $_POST['document_type']??'',
        'reference_number' => trim($_POST['reference_number']??''),
        'publication_date' => $_POST['publication_date']?:null,
        'expiry_date'      => $_POST['expiry_date']?:null,
        'is_public'        => isset($_POST['is_public'])?1:0,
        'status'           => $_POST['status']??'active',
        'uploaded_by'      => $_SESSION['user_id'],
    ];
    if (!empty($_FILES['file']['name'])) {
        $uploadDir = dirname(__DIR__).'/uploads/documents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $fname = uniqid().'.'.$ext;
        move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir.$fname);
        $d['file_path']  = 'uploads/documents/'.$fname;
        $d['file_name']  = $_FILES['file']['name'];
        $d['file_size']  = $_FILES['file']['size'];
        $d['file_type']  = $ext;
    } elseif ($id) {
        $existing = dbFetch($pdo,"SELECT file_path,file_name,file_size,file_type FROM documents WHERE id=?",[$id]);
        $d = array_merge($d, $existing ?: []);
    }
    try {
        if ($id) {
            $sets = implode(',', array_map(fn($k)=>"`$k`=?", array_keys($d)));
            dbExec($pdo,"UPDATE documents SET $sets WHERE id=?",[...array_values($d),$id]);
            $msg='updated';
        } else {
            if (empty($d['file_path'])) { $msg='no_file'; goto show_form; }
            $keys = implode(',',array_map(fn($k)=>"`$k`",array_keys($d)));
            $phs  = implode(',',array_fill(0,count($d),'?'));
            dbExec($pdo,"INSERT INTO documents ($keys) VALUES ($phs)",array_values($d));
            $id=(int)$pdo->lastInsertId(); $msg='created';
        }
        $action='list';
    } catch(Exception $e){$msg='error: '.$e->getMessage();}
}

if ($action === 'new' || $action === 'edit') {
    show_form:
    $doc = $id ? dbFetch($pdo,"SELECT * FROM documents WHERE id=?",[$id]) : [];
?>
<div class="flex items-center gap-3 mb-6">
    <a href="/crcap/admin/documents.php" class="w-8 h-8 rounded-lg bg-white border border-[#001644]/10 flex items-center justify-center text-[#001644] hover:border-[#BF8D1A] transition"><i class="fas fa-arrow-left text-xs"></i></a>
    <h2 class="text-lg font-bold text-[#001644]"><?= $id?'Editar':'Novo' ?> Documento</h2>
</div>
<?php if ($msg==='created'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5">Documento criado!</div><?php endif; ?>
<?php if ($msg==='updated'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5">Documento atualizado!</div><?php endif; ?>
<?php if ($msg==='no_file'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5">Selecione um arquivo.</div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="grid lg:grid-cols-3 gap-6">
    <input type="hidden" name="form_doc" value="1">
    <div class="lg:col-span-2 space-y-5">
        <div class="card p-6 space-y-4">
            <div><label class="form-label">Título *</label><input type="text" name="title" value="<?= htmlspecialchars($doc['title']??'') ?>" required class="form-input"></div>
            <div><label class="form-label">Descrição</label><textarea name="description" rows="3" class="form-input"><?= htmlspecialchars($doc['description']??'') ?></textarea></div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Seção / Publicação em</label>
                    <?php
                    $curCat   = $doc['category']     ?? '';
                    $curType  = $doc['document_type'] ?? '';
                    $curCombo = $curCat ? ($curCat === 'atas' ? 'atas|'.$curType : $curCat.'|') : '';
                    ?>
                    <select id="docSectionSel" class="form-input" onchange="applyDocSection(this.value)">
                        <option value="">Sem categoria</option>
                        <optgroup label="── Documentos ──">
                            <option value="editais|"   <?= $curCombo==='editais|'?'selected':''   ?>>Editais</option>
                            <option value="relatorios|" <?= $curCombo==='relatorios|'?'selected':'' ?>>Relatórios</option>
                            <option value="contratos|"  <?= $curCombo==='contratos|'?'selected':''  ?>>Contratos</option>
                            <option value="outros|"     <?= $curCombo==='outros|'?'selected':''     ?>>Outros</option>
                        </optgroup>
                        <optgroup label="── Atas das Câmaras ──">
                            <option value="atas|desenvolvimento"  <?= $curCombo==='atas|desenvolvimento'?'selected':''  ?>>Ata · Desenvolvimento Profissional</option>
                            <option value="atas|administrativa"   <?= $curCombo==='atas|administrativa'?'selected':''   ?>>Ata · Administrativa</option>
                            <option value="atas|fiscalizacao"     <?= $curCombo==='atas|fiscalizacao'?'selected':''     ?>>Ata · Fiscalização</option>
                            <option value="atas|registro"         <?= $curCombo==='atas|registro'?'selected':''         ?>>Ata · Registro</option>
                            <option value="atas|controle-interno" <?= $curCombo==='atas|controle-interno'?'selected':'' ?>>Ata · Controle Interno</option>
                        </optgroup>
                    </select>
                    <input type="hidden" name="category"      id="f_category"      value="<?= htmlspecialchars($curCat) ?>">
                    <input type="hidden" name="document_type" id="f_document_type" value="<?= htmlspecialchars($curType) ?>">
                    <p class="text-[9px] text-[#022E6B]/50 mt-1">Determina em qual página pública o documento aparecerá</p>
                </div>
                <div><label class="form-label">Número de Referência</label><input type="text" name="reference_number" value="<?= htmlspecialchars($doc['reference_number']??'') ?>" class="form-input" placeholder="Ex: Ata 001/2026"></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="form-label">Data de Publicação</label><input type="date" name="publication_date" value="<?= $doc['publication_date']??'' ?>" class="form-input"></div>
                <div><label class="form-label">Data de Validade</label><input type="date" name="expiry_date" value="<?= $doc['expiry_date']??'' ?>" class="form-input"></div>
            </div>
        </div>
        <div class="card p-6">
            <label class="form-label">Upload de Arquivo <?= $id?'(deixe em branco para manter o atual)':'' ?></label>
            <div class="border-2 border-dashed border-[#001644]/20 rounded-xl p-8 text-center hover:border-[#BF8D1A]/50 transition cursor-pointer" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-cloud-upload-alt text-3xl text-[#001644]/30 mb-2 block"></i>
                <p class="text-sm text-[#022E6B] font-medium">Arraste ou clique para enviar</p>
                <p class="text-xs text-[#022E6B]/60 mt-1">PDF, DOC, XLS (máx. 10MB)</p>
                <input type="file" id="fileInput" name="file" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx" onchange="updateFileName(this)">
            </div>
            <p id="fileName" class="text-xs text-[#006633] mt-2 hidden"></p>
            <?php if ($doc['file_name']??''): ?><p class="text-xs text-[#022E6B] mt-2">Arquivo atual: <strong><?= htmlspecialchars($doc['file_name']) ?></strong></p><?php endif; ?>
        </div>
    </div>
    <div class="space-y-5">
        <div class="card p-5 space-y-4">
            <div><label class="form-label">Status</label>
            <select name="status" class="form-input">
                <?php foreach(['active'=>'Ativo','archived'=>'Arquivado','expired'=>'Expirado'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= ($doc['status']??'active')===$v?'selected':'' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select></div>
            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="is_public" <?= ($doc['is_public']??1)?'checked':'' ?>><span class="text-xs font-semibold text-[#001644]">Documento público</span></label>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary flex-1 justify-center"><i class="fas fa-save"></i>Salvar</button>
            <a href="/crcap/admin/documents.php" class="flex-1 py-2.5 text-xs font-semibold text-[#001644] border-2 border-[#001644]/20 rounded-xl hover:border-[#BF8D1A] transition text-center">Cancelar</a>
        </div>
        <?php if ($id): ?>
        <a href="?action=delete&id=<?= $id ?>" onclick="return confirm('Excluir?')" class="btn-danger w-full justify-center" style="display:flex"><i class="fas fa-trash"></i>Excluir</a>
        <?php endif; ?>
    </div>
</form>
<script>
function updateFileName(i){
    const p=document.getElementById('fileName');
    p.textContent='Selecionado: '+i.files[0].name;
    p.classList.remove('hidden');
}
function applyDocSection(val) {
    const parts = val.split('|');
    document.getElementById('f_category').value      = parts[0] || '';
    document.getElementById('f_document_type').value = parts[1] || '';
}
applyDocSection(document.getElementById('docSectionSel').value);
</script>
<?php

} else {

// ── Labels ────────────────────────────────────────────────────────────────────
$catLabels  = ['editais'=>'Editais','relatorios'=>'Relatórios','contratos'=>'Contratos','atas'=>'Atas das Câmaras','outros'=>'Outros'];
$typeLabels = ['desenvolvimento'=>'Desenvolvimento Profissional','administrativa'=>'Administrativa','fiscalizacao'=>'Fiscalização','registro'=>'Registro','controle-interno'=>'Controle Interno'];

// ── Filtros da URL ────────────────────────────────────────────────────────────
$tabCat  = $_GET['cat']   ?? 'todos';
$tabType = $_GET['type']  ?? '';
$tabYear = $_GET['year']  ?? '';
$busca   = trim($_GET['busca'] ?? '');

// ── Contagem por categoria (badges das abas) ──────────────────────────────────
$catCounts = [];
$catCounts['todos'] = (int)(dbFetch($pdo,"SELECT COUNT(*) AS n FROM documents")['n'] ?? 0);
foreach ($catLabels as $k => $l) {
    $catCounts[$k] = (int)(dbFetch($pdo,"SELECT COUNT(*) AS n FROM documents WHERE category=?",[$k])['n'] ?? 0);
}

// ── Anos disponíveis ──────────────────────────────────────────────────────────
$years = dbFetchAll($pdo,"SELECT DISTINCT YEAR(publication_date) AS y FROM documents WHERE publication_date IS NOT NULL ORDER BY y DESC");
$years = array_column($years,'y');

// ── Contagem de atas por câmara ───────────────────────────────────────────────
$ataCounts = [];
foreach (array_keys($typeLabels) as $t) {
    $ataCounts[$t] = (int)(dbFetch($pdo,"SELECT COUNT(*) AS n FROM documents WHERE category='atas' AND document_type=?",[$t])['n'] ?? 0);
}

// ── Query principal ───────────────────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
if ($tabCat !== 'todos') { $where[] = 'd.category=?';       $params[] = $tabCat; }
if ($tabType)             { $where[] = 'd.document_type=?';  $params[] = $tabType; }
if ($tabYear)             { $where[] = 'YEAR(d.publication_date)=?'; $params[] = $tabYear; }
if ($busca)               {
    $where[]  = '(d.title LIKE ? OR d.description LIKE ? OR d.reference_number LIKE ?)';
    $params[] = "%$busca%"; $params[] = "%$busca%"; $params[] = "%$busca%";
}
$whereStr = implode(' AND ', $where);
$docs = dbFetchAll($pdo,
    "SELECT d.*, u.full_name AS uploader
     FROM documents d LEFT JOIN users u ON d.uploaded_by=u.id
     WHERE $whereStr
     ORDER BY d.publication_date DESC, d.created_at DESC",
    $params);

?>

<!-- ── Cabeçalho ──────────────────────────────────────────────────────────── -->
<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2">
        <i class="fas fa-folder-open text-[#BF8D1A]"></i>Documentos
        <span class="text-xs font-normal text-[#022E6B]/50"><?= $catCounts['todos'] ?> no total</span>
    </h2>
    <a href="?action=new" class="btn-primary"><i class="fas fa-upload"></i>Enviar Documento</a>
</div>
<?php if ($msg==='deleted'): ?>
<div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5">Documento excluído.</div>
<?php endif; ?>

<!-- ── Abas de categoria ───────────────────────────────────────────────────── -->
<?php
$tabIcons  = ['todos'=>'fa-border-all','editais'=>'fa-file-contract','relatorios'=>'fa-chart-bar','contratos'=>'fa-handshake','atas'=>'fa-book','outros'=>'fa-folder'];
$tabColors = ['todos'=>'#001644','editais'=>'#BF8D1A','relatorios'=>'#006633','contratos'=>'#022E6B','atas'=>'#7C3AED','outros'=>'#6B7280'];
$allTabs   = array_merge(['todos'=>'Todos'], $catLabels);
?>
<div class="flex flex-wrap gap-1.5 mb-4">
<?php foreach ($allTabs as $k => $l):
    $active = ($tabCat === $k);
    $url    = '?cat='.$k.($tabYear ? '&year='.$tabYear : '').($busca ? '&busca='.urlencode($busca) : '');
    $cnt    = $catCounts[$k] ?? 0;
    $color  = $tabColors[$k] ?? '#001644';
?>
    <a href="<?= $url ?>"
       style="<?= $active ? 'background:'.$color.';border-color:'.$color.';color:#fff' : '' ?>"
       class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition <?= $active ? 'shadow-md' : 'bg-white text-[#022E6B] border-[#001644]/10 hover:border-[#BF8D1A]/50' ?>">
        <i class="fas <?= $tabIcons[$k] ?? 'fa-folder' ?> text-[10px]"></i>
        <?= $l ?>
        <span class="px-1.5 py-0.5 rounded-full text-[9px] <?= $active ? 'bg-white/20' : 'bg-[#001644]/8' ?>"><?= $cnt ?></span>
    </a>
<?php endforeach; ?>
</div>

<!-- ── Sub-abas de câmara (só na aba Atas) ────────────────────────────────── -->
<?php if ($tabCat === 'atas'): ?>
<div class="flex flex-wrap gap-1.5 mb-4 pl-3 border-l-4 border-[#7C3AED]/30">
    <a href="?cat=atas<?= $tabYear ? '&year='.$tabYear : '' ?>"
       class="px-3 py-1 rounded-lg text-[11px] font-semibold border transition <?= !$tabType ? 'bg-[#7C3AED] text-white border-[#7C3AED]' : 'bg-white text-[#022E6B] border-[#001644]/10 hover:border-[#7C3AED]/40' ?>">
        <i class="fas fa-layer-group text-[9px] mr-1"></i>Todas
        <span class="opacity-70 text-[9px] ml-0.5">(<?= $catCounts['atas'] ?>)</span>
    </a>
    <?php foreach ($typeLabels as $tk => $tl): ?>
    <a href="?cat=atas&type=<?= $tk ?><?= $tabYear ? '&year='.$tabYear : '' ?>"
       class="px-3 py-1 rounded-lg text-[11px] font-semibold border transition <?= $tabType===$tk ? 'bg-[#7C3AED] text-white border-[#7C3AED]' : 'bg-white text-[#022E6B] border-[#001644]/10 hover:border-[#7C3AED]/40' ?>">
        <?= $tl ?>
        <span class="opacity-70 text-[9px] ml-0.5">(<?= $ataCounts[$tk] ?? 0 ?>)</span>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Busca + Ano ─────────────────────────────────────────────────────────── -->
<form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
    <input type="hidden" name="cat"  value="<?= htmlspecialchars($tabCat) ?>">
    <input type="hidden" name="type" value="<?= htmlspecialchars($tabType) ?>">
    <div class="relative flex-1 min-w-[180px] max-w-xs">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#022E6B]/30 text-xs"></i>
        <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>"
               placeholder="Buscar título, referência..."
               class="form-input pl-8 py-2 text-xs w-full">
    </div>
    <select name="year" onchange="this.form.submit()" class="form-input py-2 text-xs">
        <option value="">Todos os anos</option>
        <?php foreach ($years as $y): ?>
        <option value="<?= $y ?>" <?= $tabYear == $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn-primary py-2 px-3 text-xs"><i class="fas fa-filter"></i></button>
    <?php if ($busca || $tabYear): ?>
    <a href="?cat=<?= $tabCat ?><?= $tabType ? '&type='.$tabType : '' ?>"
       class="py-2 px-3 text-xs border border-[#001644]/10 rounded-xl hover:bg-red-50 hover:border-red-300 transition text-[#022E6B]">
        <i class="fas fa-times"></i> Limpar
    </a>
    <?php endif; ?>
    <span class="text-[10px] text-[#022E6B]/40 ml-auto"><?= count($docs) ?> resultado(s)</span>
</form>

<?php
// ══════════════════════════════════════════════════════════════════════════════
// RENDERIZAÇÃO: Atas agrupadas vs tabela simples
// ══════════════════════════════════════════════════════════════════════════════
if ($tabCat === 'atas' && !empty($docs)):

    if ($tabType) {
        // Sub-aba selecionada → agrupar só por ANO
        $byYear = [];
        foreach ($docs as $d) {
            $ano = $d['publication_date'] ? date('Y', strtotime($d['publication_date'])) : 'Sem data';
            $byYear[$ano][] = $d;
        }
        krsort($byYear);
?>
    <div class="space-y-4">
    <?php foreach ($byYear as $ano => $aDocs): ?>
        <div class="card overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3 bg-[#7C3AED]/5 border-b border-[#7C3AED]/10">
                <i class="fas fa-calendar-alt text-[#7C3AED] text-xs"></i>
                <span class="font-bold text-[#001644] text-sm"><?= $ano ?></span>
                <span class="text-[10px] text-[#022E6B]/50"><?= count($aDocs) ?> documento(s)</span>
            </div>
            <table class="w-full">
                <tbody>
                <?php foreach ($aDocs as $d): ?>
                <tr class="border-t border-[#001644]/5 hover:bg-[#F8FAFC] transition">
                    <td class="px-4 py-2">
                        <?php docRowHtml($d); ?>
                    </td>
                    <td class="hidden md:table-cell px-3 py-2 text-xs text-[#022E6B]"><?= htmlspecialchars($d['reference_number'] ?: '—') ?></td>
                    <td class="hidden lg:table-cell px-3 py-2 text-xs text-center"><?= $d['publication_date'] ? date('d/m/Y', strtotime($d['publication_date'])) : '—' ?></td>
                    <td class="px-3 py-2 text-center"><span class="badge <?= $d['status']==='active'?'badge-green':'badge-gray' ?>"><?= $d['status'] ?></span></td>
                    <td class="hidden sm:table-cell px-3 py-2 text-xs text-right"><?= number_format($d['downloads']) ?></td>
                    <td class="px-3 py-2"><?php docActionsHtml($d); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
    </div>

<?php
    } else {
        // Sem sub-aba → agrupar por CÂMARA → ANO
        $byCamara = [];
        foreach ($docs as $d) {
            $tipo = $d['document_type'] ?: 'outros';
            $ano  = $d['publication_date'] ? date('Y', strtotime($d['publication_date'])) : 'Sem data';
            $byCamara[$tipo][$ano][] = $d;
        }
?>
    <div class="space-y-5">
    <?php foreach ($byCamara as $tipo => $anos):
        krsort($anos);
        $tipoLabel = $typeLabels[$tipo] ?? ucfirst($tipo);
        $tipoTotal = array_sum(array_map('count', $anos));
    ?>
        <div class="card overflow-hidden">
            <!-- Header câmara (clicável) -->
            <div class="flex items-center gap-3 px-5 py-3 bg-[#7C3AED]/5 border-b border-[#7C3AED]/10 cursor-pointer select-none"
                 onclick="toggleCamara('cam_<?= $tipo ?>', 'ico_<?= $tipo ?>')">
                <div class="w-8 h-8 rounded-lg bg-[#7C3AED] flex items-center justify-center text-white text-xs flex-shrink-0">
                    <i class="fas fa-book"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-[#001644] text-sm">Câmara de <?= htmlspecialchars($tipoLabel) ?></p>
                    <p class="text-[9px] text-[#022E6B]/50"><?= $tipoTotal ?> documento(s) · <?= count($anos) ?> ano(s)</p>
                </div>
                <i class="fas fa-chevron-down text-[#7C3AED] text-xs transition-transform duration-200" id="ico_<?= $tipo ?>"></i>
            </div>
            <!-- Conteúdo câmara -->
            <div id="cam_<?= $tipo ?>" class="hidden">
            <?php foreach ($anos as $ano => $aDocs): ?>
                <!-- Header ano (clicável) -->
                <div class="flex items-center gap-2 px-6 py-2.5 bg-[#F8FAFC] border-b border-[#001644]/5 cursor-pointer hover:bg-[#F0F4F8] transition"
                     onclick="toggleYear('yr_<?= $tipo ?>_<?= $ano ?>', 'yico_<?= $tipo ?>_<?= $ano ?>')">
                    <i class="fas fa-calendar text-[#BF8D1A] text-[10px]"></i>
                    <span class="text-xs font-semibold text-[#001644]"><?= $ano ?></span>
                    <span class="text-[9px] text-[#022E6B]/50"><?= count($aDocs) ?> ata(s)</span>
                    <i class="fas fa-chevron-right text-[10px] text-[#022E6B]/30 ml-auto transition-transform duration-200" id="yico_<?= $tipo ?>_<?= $ano ?>"></i>
                </div>
                <!-- Documentos do ano -->
                <div id="yr_<?= $tipo ?>_<?= $ano ?>" class="hidden">
                    <table class="w-full">
                        <tbody>
                        <?php foreach ($aDocs as $d): ?>
                        <tr class="border-t border-[#001644]/5 hover:bg-[#FAFBFF] transition">
                            <td class="pl-10 pr-3 py-2">
                                <?php docRowHtml($d); ?>
                            </td>
                            <td class="hidden md:table-cell px-3 py-2 text-xs text-[#022E6B]"><?= htmlspecialchars($d['reference_number'] ?: '—') ?></td>
                            <td class="hidden lg:table-cell px-3 py-2 text-xs text-center"><?= $d['publication_date'] ? date('d/m/Y', strtotime($d['publication_date'])) : '—' ?></td>
                            <td class="px-3 py-2 text-center"><span class="badge <?= $d['status']==='active'?'badge-green':'badge-gray' ?>"><?= $d['status'] ?></span></td>
                            <td class="hidden sm:table-cell px-3 py-2 text-xs text-right"><?= number_format($d['downloads']) ?></td>
                            <td class="px-3 py-2"><?php docActionsHtml($d); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
            </div><!-- /cam -->
        </div>
    <?php endforeach; ?>
    </div>
<?php
    } // end else (sem sub-aba)

else: // ── Tabela simples (não-atas ou atas vazia) ──────────────────────────
?>
<div class="card overflow-hidden">
    <table class="w-full">
        <thead>
            <tr>
                <th class="text-left">Documento</th>
                <th class="hidden md:table-cell text-left">Categoria</th>
                <th class="hidden md:table-cell text-left">Referência</th>
                <th class="hidden lg:table-cell text-center">Publicação</th>
                <th class="text-center">Status</th>
                <th class="hidden sm:table-cell text-right">Downloads</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($docs)): ?>
            <tr><td colspan="7" class="text-center py-12 text-[#001644]/30">
                <i class="fas fa-folder-open text-3xl mb-3 block"></i>
                <?= $busca ? 'Nenhum resultado para "'.htmlspecialchars($busca).'"' : 'Nenhum documento nesta categoria' ?>
            </td></tr>
        <?php else: foreach ($docs as $d):
            $cl = $catLabels[$d['category']??''] ?? ($d['category'] ?: '—');
            if ($d['category']==='atas' && $d['document_type']) $cl = 'Ata · '.($typeLabels[$d['document_type']] ?? $d['document_type']);
        ?>
            <tr class="hover:bg-[#F8FAFC] transition">
                <td class="px-4 py-2"><?php docRowHtml($d); ?></td>
                <td class="hidden md:table-cell px-3 py-2">
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-[#001644]/5 text-[#001644]"><?= htmlspecialchars($cl) ?></span>
                </td>
                <td class="hidden md:table-cell px-3 py-2 text-xs text-[#022E6B]"><?= htmlspecialchars($d['reference_number'] ?: '—') ?></td>
                <td class="hidden lg:table-cell px-3 py-2 text-xs text-center"><?= $d['publication_date'] ? date('d/m/Y',strtotime($d['publication_date'])) : '—' ?></td>
                <td class="px-3 py-2 text-center"><span class="badge <?= $d['status']==='active'?'badge-green':'badge-gray' ?>"><?= $d['status'] ?></span></td>
                <td class="hidden sm:table-cell px-3 py-2 text-xs text-right"><?= number_format($d['downloads']) ?></td>
                <td class="px-3 py-2"><?php docActionsHtml($d); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
function toggleCamara(secId, icoId) {
    var el  = document.getElementById(secId);
    var ico = document.getElementById(icoId);
    el.classList.toggle('hidden');
    if (ico) ico.classList.toggle('rotate-180');
}
function toggleYear(secId, icoId) {
    var el  = document.getElementById(secId);
    var ico = document.getElementById(icoId);
    el.classList.toggle('hidden');
    if (ico) {
        ico.classList.toggle('rotate-90');
        ico.classList.toggle('text-[#BF8D1A]');
    }
}
</script>

<?php } // end else (list) ?>
<?php require_once __DIR__ . '/admin_footer.php'; ?>