<?php
// ── Ações com redirect ANTES de qualquer output ───────────────────────────────
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$msg    = '';

if ($action === 'delete' && isset($_GET['id'])) {
    $m = dbFetch($pdo,"SELECT file_path FROM media WHERE id=?",[(int)$_GET['id']]);
    if ($m && file_exists(dirname(__DIR__).'/'.$m['file_path'])) @unlink(dirname(__DIR__).'/'.$m['file_path']);
    dbExec($pdo,"DELETE FROM media WHERE id=?",[(int)$_GET['id']]);
    header('Location: /crcap/admin/media.php?msg=deleted'); exit;
}

// ── Agora carrega o HTML do admin ─────────────────────────────────────────────
$pageTitle = 'Biblioteca de Mídia · Admin CRCAP';
$activeAdm = 'media';
require_once __DIR__ . '/admin_header.php';

// Upload (POST, após HTML iniciado — sem redirect, usa $msg)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $uploaded  = 0;
    $uploadDir = dirname(__DIR__).'/uploads/media/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $allowed = ['jpg','jpeg','png','gif','webp','svg','pdf','doc','docx','xls','xlsx'];
    foreach ($_FILES['files']['name'] as $i => $origName) {
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed) || $_FILES['files']['error'][$i] !== 0) continue;
        $fname    = uniqid().'.'.$ext;
        $filePath = $uploadDir.$fname;
        if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $filePath)) {
            $mime = $_FILES['files']['type'][$i];
            $size = $_FILES['files']['size'][$i];
            $type = in_array($ext,['jpg','jpeg','png','gif','webp','svg']) ? 'image' : 'document';
            $w = $h = null;
            if ($type === 'image' && function_exists('getimagesize')) {
                $dims = @getimagesize($filePath);
                if ($dims) { $w = $dims[0]; $h = $dims[1]; }
            }
            try {
                dbExec($pdo,
                    "INSERT INTO media (file_path,file_name,file_size,file_type,mime_type,width,height,uploaded_by) VALUES (?,?,?,?,?,?,?,?)",
                    ['uploads/media/'.$fname, $origName, $size, $type, $mime, $w, $h, $_SESSION['user_id']]);
                $uploaded++;
            } catch (Exception $e) {}
        }
    }
    $msg = "upload:$uploaded";
}

$type_filter = $_GET['type'] ?? '';
$search      = trim($_GET['q'] ?? '');
$page_num    = max(1,(int)($_GET['p'] ?? 1));
$perPage     = 30;
$offset      = ($page_num-1)*$perPage;

$where  = ['1=1'];
$params = [];
if ($type_filter) { $where[] = "file_type=?"; $params[] = $type_filter; }
if ($search)      { $where[] = "(file_name LIKE ? OR title LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$media = dbFetchAll($pdo,
    "SELECT * FROM media WHERE $whereStr ORDER BY created_at DESC LIMIT $perPage OFFSET $offset",
    $params);
$total = (int)(dbFetch($pdo,"SELECT COUNT(*) AS n FROM media WHERE $whereStr",$params)['n'] ?? 0);
$pages = max(1, (int)ceil($total/$perPage));

// Helpers
function fileIcon(string $ext): array {
    return match(strtolower($ext)) {
        'pdf'           => ['fa-file-pdf',   'text-red-500',   'bg-red-50'],
        'doc','docx'    => ['fa-file-word',  'text-blue-600',  'bg-blue-50'],
        'xls','xlsx'    => ['fa-file-excel', 'text-green-600', 'bg-green-50'],
        'svg'           => ['fa-file-code',  'text-purple-500','bg-purple-50'],
        default         => ['fa-file-alt',   'text-gray-500',  'bg-gray-50'],
    };
}
function fmtSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes/1048576,1).' MB';
    if ($bytes >= 1024)    return round($bytes/1024).' KB';
    return $bytes.' B';
}
?>

<!-- ── Cabeçalho ──────────────────────────────────────────────────────────── -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2">
            <i class="fas fa-photo-video text-[#BF8D1A]"></i>Biblioteca de Mídia
        </h2>
        <p class="text-[10px] text-[#022E6B]/50 mt-0.5"><?= $total ?> arquivo(s) no total</p>
    </div>
    <div class="flex gap-2">
        <div class="flex rounded-xl border border-[#001644]/10 overflow-hidden text-xs font-semibold">
            <button onclick="setView('grid')" id="btn-grid"
                class="px-3 py-2 flex items-center gap-1.5 transition bg-[#001644] text-white">
                <i class="fas fa-th text-[10px]"></i>Grade
            </button>
            <button onclick="setView('list')" id="btn-list"
                class="px-3 py-2 flex items-center gap-1.5 transition text-[#022E6B] hover:bg-[#F8FAFC]">
                <i class="fas fa-list text-[10px]"></i>Lista
            </button>
        </div>
        <button onclick="document.getElementById('uploadArea').classList.toggle('hidden')" class="btn-primary">
            <i class="fas fa-upload"></i>Enviar Arquivos
        </button>
    </div>
</div>

<?php if (str_starts_with($msg,'upload:')): $n=substr($msg,7); ?>
<div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5">
    <i class="fas fa-check-circle mr-1"></i><?= $n ?> arquivo(s) enviado(s) com sucesso!
</div>
<?php endif; ?>
<?php if (($_GET['msg']??'') === 'deleted'): ?>
<div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5">Arquivo excluído.</div>
<?php endif; ?>

<!-- ── Área de upload ──────────────────────────────────────────────────────── -->
<div id="uploadArea" class="hidden card p-6 mb-6">
    <form method="POST" enctype="multipart/form-data">
        <div class="border-2 border-dashed border-[#001644]/20 rounded-xl p-10 text-center cursor-pointer hover:border-[#BF8D1A]/50 transition"
             onclick="document.getElementById('mediaFiles').click()"
             ondragover="event.preventDefault();this.classList.add('border-[#BF8D1A]')"
             ondrop="handleDrop(event)">
            <i class="fas fa-cloud-upload-alt text-4xl text-[#001644]/30 mb-3 block"></i>
            <p class="font-semibold text-[#001644] text-sm mb-1">Clique ou arraste os arquivos aqui</p>
            <p class="text-xs text-[#022E6B]/60">JPG, PNG, GIF, WEBP, SVG, PDF, DOC, XLS (máx. 10MB cada)</p>
            <input type="file" id="mediaFiles" name="files[]" multiple class="hidden"
                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" onchange="updateUploadList(this)">
        </div>
        <div id="uploadList" class="mt-3 space-y-1"></div>
        <div class="flex justify-end mt-4 gap-2">
            <button type="button" onclick="document.getElementById('uploadArea').classList.add('hidden')"
                    class="px-4 py-2 text-xs border border-[#001644]/10 rounded-xl hover:bg-[#F8FAFC] transition text-[#022E6B]">
                Cancelar
            </button>
            <button type="submit" class="btn-primary"><i class="fas fa-upload"></i>Enviar</button>
        </div>
    </form>
</div>

<!-- ── Filtros ─────────────────────────────────────────────────────────────── -->
<form method="GET" class="flex flex-wrap gap-2 items-center mb-5">
    <div class="relative flex-1 min-w-[180px] max-w-xs">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#022E6B]/30 text-xs"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Buscar nome do arquivo..."
               class="form-input pl-8 py-2 text-xs w-full">
    </div>
    <div class="flex rounded-xl border border-[#001644]/10 overflow-hidden text-xs">
        <?php
        $typeOpts = [''=> 'Todos','image'=>'Imagens','document'=>'Documentos'];
        foreach ($typeOpts as $v => $l):
            $active = ($type_filter === $v);
        ?>
        <a href="?type=<?= urlencode($v) ?>&q=<?= urlencode($search) ?>"
           class="px-3 py-2 font-semibold transition <?= $active ? 'bg-[#001644] text-white' : 'bg-white text-[#022E6B] hover:bg-[#F8FAFC]' ?>">
            <?= $l ?>
        </a>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="btn-primary py-2 px-3 text-xs"><i class="fas fa-filter"></i></button>
    <?php if ($search || $type_filter): ?>
    <a href="/crcap/admin/media.php" class="py-2 px-3 text-xs border border-[#001644]/10 rounded-xl hover:bg-red-50 hover:border-red-300 transition text-[#022E6B]">
        <i class="fas fa-times"></i> Limpar
    </a>
    <?php endif; ?>
</form>

<?php if (empty($media)): ?>
<div class="card p-16 text-center text-[#001644]/30">
    <i class="fas fa-photo-video text-4xl mb-3 block"></i>
    <?= $search ? 'Nenhum arquivo encontrado para "'.htmlspecialchars($search).'"' : 'Nenhum arquivo enviado ainda' ?>
</div>
<?php else: ?>

<!-- ── VIEW: GRADE ─────────────────────────────────────────────────────────── -->
<div id="view-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
    <?php foreach ($media as $m):
        $isImg  = ($m['file_type'] === 'image');
        $ext    = strtolower(pathinfo($m['file_name'], PATHINFO_EXTENSION));
        [$ico,$icoColor,$icoBg] = fileIcon($ext);
        $url    = '/crcap/'.$m['file_path'];
        $mid    = (int)$m['id'];
        $fname  = htmlspecialchars($m['file_name']);
        $title  = htmlspecialchars($m['title'] ?: $m['file_name']);
        $size   = $m['file_size'] ? fmtSize((int)$m['file_size']) : '';
        $dims   = ($m['width'] && $m['height']) ? $m['width'].'×'.$m['height'] : '';
    ?>
    <div class="bg-white rounded-xl overflow-hidden border border-[#001644]/5 group hover:border-[#BF8D1A]/40 hover:shadow-lg transition cursor-pointer"
         onclick="openModal(<?= $mid ?>)"
         data-id="<?= $mid ?>"
         data-img="<?= $isImg ? '1' : '0' ?>"
         data-url="<?= htmlspecialchars($url) ?>"
         data-name="<?= $fname ?>"
         data-size="<?= htmlspecialchars($size) ?>"
         data-dims="<?= htmlspecialchars($dims) ?>"
         data-ext="<?= htmlspecialchars(strtoupper($ext)) ?>"
         data-mime="<?= htmlspecialchars($m['mime_type'] ?? '') ?>"
         data-date="<?= $m['created_at'] ? date('d/m/Y H:i', strtotime($m['created_at'])) : '' ?>">

        <!-- Thumbnail -->
        <div class="relative h-24 <?= $isImg ? '' : $icoBg ?> flex items-center justify-center overflow-hidden">
            <?php if ($isImg): ?>
                <img src="<?= htmlspecialchars($url) ?>"
                     alt="<?= $fname ?>"
                     loading="lazy"
                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
            <?php else: ?>
                <i class="fas <?= $ico ?> text-4xl <?= $icoColor ?>"></i>
            <?php endif; ?>

            <!-- Badge tipo -->
            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded-md text-[8px] font-bold uppercase tracking-wider
                         <?= $isImg ? 'bg-black/40 text-white' : 'bg-white/90 text-gray-600' ?>">
                <?= strtoupper($ext) ?>
            </span>

            <!-- Overlay hover -->
            <div class="absolute inset-0 bg-[#001644]/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-white/20 hover:bg-[#BF8D1A] text-white flex items-center justify-center text-xs"
                      title="Visualizar"><i class="fas fa-eye"></i></span>
            </div>
        </div>

        <!-- Info -->
        <div class="p-2">
            <p class="text-[9px] font-medium text-[#001644] truncate leading-tight" title="<?= $fname ?>"><?= $fname ?></p>
            <p class="text-[8px] text-[#022E6B]/50 mt-0.5"><?= $size ?><?= $dims ? ' · '.$dims : '' ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── VIEW: LISTA ─────────────────────────────────────────────────────────── -->
<div id="view-list" class="hidden card overflow-hidden mb-6">
    <table class="w-full">
        <thead>
            <tr>
                <th class="text-left">Arquivo</th>
                <th class="hidden md:table-cell text-left">Tipo</th>
                <th class="hidden lg:table-cell text-left">Dimensões</th>
                <th class="hidden sm:table-cell text-right">Tamanho</th>
                <th class="hidden lg:table-cell text-center">Enviado em</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($media as $m):
            $isImg  = ($m['file_type'] === 'image');
            $ext    = strtolower(pathinfo($m['file_name'], PATHINFO_EXTENSION));
            [$ico,$icoColor,$icoBg] = fileIcon($ext);
            $url    = '/crcap/'.$m['file_path'];
            $mid    = (int)$m['id'];
            $fname  = htmlspecialchars($m['file_name']);
            $size   = $m['file_size'] ? fmtSize((int)$m['file_size']) : '—';
            $dims   = ($m['width'] && $m['height']) ? $m['width'].'×'.$m['height'] : '—';
        ?>
        <tr class="hover:bg-[#F8FAFC] transition cursor-pointer" onclick="openModal(<?= $mid ?>)">
            <td class="px-4 py-2">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 <?= $isImg ? '' : $icoBg.' flex items-center justify-center' ?>">
                        <?php if ($isImg): ?>
                        <img src="<?= htmlspecialchars($url) ?>" alt="<?= $fname ?>" loading="lazy" class="w-full h-full object-cover">
                        <?php else: ?>
                        <i class="fas <?= $ico ?> <?= $icoColor ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-[#001644] line-clamp-1"><?= $fname ?></p>
                        <p class="text-[9px] text-[#022E6B]/50"><?= strtoupper($ext) ?></p>
                    </div>
                </div>
            </td>
            <td class="hidden md:table-cell px-3 py-2">
                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold <?= $isImg ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' ?>">
                    <?= $isImg ? 'Imagem' : 'Documento' ?>
                </span>
            </td>
            <td class="hidden lg:table-cell px-3 py-2 text-xs text-[#022E6B]"><?= $dims ?></td>
            <td class="hidden sm:table-cell px-3 py-2 text-xs text-right text-[#022E6B]"><?= $size ?></td>
            <td class="hidden lg:table-cell px-3 py-2 text-xs text-center text-[#022E6B]">
                <?= $m['created_at'] ? date('d/m/Y H:i', strtotime($m['created_at'])) : '—' ?>
            </td>
            <td class="px-3 py-2" onclick="event.stopPropagation()">
                <div class="flex items-center justify-center gap-1">
                    <button onclick="openModal(<?= $mid ?>)"
                            class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"
                            title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="copyUrl('<?= htmlspecialchars($url) ?>')"
                            class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#BF8D1A] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"
                            title="Copiar URL">
                        <i class="fas fa-copy"></i>
                    </button>
                    <a href="?action=delete&id=<?= $mid ?>" onclick="return confirm('Excluir este arquivo?')"
                       class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs"
                       title="Excluir">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ── Paginação ───────────────────────────────────────────────────────────── -->
<?php if ($pages > 1): ?>
<div class="flex justify-center items-center gap-1.5 mb-4">
    <?php if ($page_num > 1): ?>
    <a href="?p=<?= $page_num-1 ?>&type=<?= urlencode($type_filter) ?>&q=<?= urlencode($search) ?>"
       class="w-9 h-9 rounded-lg flex items-center justify-center text-xs border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition bg-white">
        <i class="fas fa-chevron-left"></i>
    </a>
    <?php endif; ?>
    <?php
    $start = max(1, $page_num - 2);
    $end   = min($pages, $page_num + 2);
    for ($i = $start; $i <= $end; $i++):
    ?>
    <a href="?p=<?= $i ?>&type=<?= urlencode($type_filter) ?>&q=<?= urlencode($search) ?>"
       class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold transition
              <?= $i === $page_num ? 'bg-[#001644] text-white' : 'bg-white text-[#001644] border border-[#001644]/10 hover:border-[#BF8D1A]' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
    <?php if ($page_num < $pages): ?>
    <a href="?p=<?= $page_num+1 ?>&type=<?= urlencode($type_filter) ?>&q=<?= urlencode($search) ?>"
       class="w-9 h-9 rounded-lg flex items-center justify-center text-xs border border-[#001644]/10 hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition bg-white">
        <i class="fas fa-chevron-right"></i>
    </a>
    <?php endif; ?>
    <span class="text-[10px] text-[#022E6B]/50 ml-2"><?= $page_num ?> / <?= $pages ?></span>
</div>
<?php endif; ?>

<?php endif; // not empty ?>

<!-- ════════════════════════════════════════════════════════════════════════════
     MODAL DE PREVIEW
════════════════════════════════════════════════════════════════════════════ -->
<div id="mediaModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal()"></div>

    <!-- Panel -->
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col pointer-events-auto overflow-hidden">

            <!-- Header -->
            <div class="flex items-center gap-3 px-5 py-4 border-b border-[#001644]/5 flex-shrink-0">
                <div id="modal-icon" class="w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <p id="modal-name" class="font-bold text-[#001644] text-sm truncate"></p>
                    <p id="modal-meta" class="text-[10px] text-[#022E6B]/60 mt-0.5"></p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button id="modal-copy-btn" onclick="copyModalUrl()"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#001644] bg-[#F8FAFC] hover:bg-[#BF8D1A] hover:text-white rounded-xl transition">
                        <i class="fas fa-copy"></i><span class="hidden sm:inline">Copiar URL</span>
                    </button>
                    <a id="modal-download-btn" href="#" target="_blank"
                       class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-[#001644] hover:bg-[#022E6B] rounded-xl transition">
                        <i class="fas fa-external-link-alt"></i><span class="hidden sm:inline">Abrir</span>
                    </a>
                    <a id="modal-delete-btn" href="#"
                       class="w-8 h-8 rounded-xl bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs"
                       title="Excluir" onclick="return confirm('Excluir este arquivo permanentemente?')">
                        <i class="fas fa-trash"></i>
                    </a>
                    <button onclick="closeModal()"
                            class="w-8 h-8 rounded-xl bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Body: preview -->
            <div class="flex-1 overflow-auto min-h-0 bg-[#F0F2F5]">
                <!-- Imagem -->
                <div id="modal-img-wrap" class="hidden h-full flex items-center justify-center p-4">
                    <img id="modal-img" src="" alt="" class="max-w-full max-h-[60vh] object-contain rounded-xl shadow-lg">
                </div>

                <!-- PDF embed -->
                <div id="modal-pdf-wrap" class="hidden h-full">
                    <iframe id="modal-pdf" src="" class="w-full h-full min-h-[60vh] border-0 rounded-b-2xl"></iframe>
                </div>

                <!-- Documento genérico (doc/xls) -->
                <div id="modal-doc-wrap" class="hidden flex items-center justify-center p-12">
                    <div class="text-center">
                        <div id="modal-doc-icon" class="w-24 h-24 rounded-2xl mx-auto flex items-center justify-center mb-4 text-5xl"></div>
                        <p class="font-bold text-[#001644] text-base mb-2" id="modal-doc-name"></p>
                        <p class="text-xs text-[#022E6B]/60 mb-6" id="modal-doc-meta"></p>
                        <a id="modal-doc-open" href="#" target="_blank"
                           class="inline-flex items-center gap-2 px-5 py-3 bg-[#001644] text-white rounded-xl text-sm font-semibold hover:bg-[#022E6B] transition">
                            <i class="fas fa-external-link-alt"></i> Abrir arquivo
                        </a>
                        <p class="text-[10px] text-[#022E6B]/40 mt-3">Arquivos DOC/XLS não podem ser visualizados no navegador sem Google Docs</p>
                    </div>
                </div>
            </div>

            <!-- Footer: URL copiável -->
            <div class="px-5 py-3 border-t border-[#001644]/5 bg-[#F8FAFC] flex-shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-[9px] text-[#022E6B]/50 uppercase tracking-wider font-semibold">URL</span>
                    <code id="modal-url-display" class="flex-1 text-[10px] font-mono text-[#001644] bg-white border border-[#001644]/10 rounded-lg px-3 py-1.5 truncate cursor-pointer hover:border-[#BF8D1A] transition"
                          onclick="copyModalUrl()"></code>
                    <button onclick="copyModalUrl()" class="flex-shrink-0 text-xs px-2 py-1.5 bg-[#001644] text-white rounded-lg hover:bg-[#BF8D1A] transition font-semibold">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div id="copy-toast" class="hidden text-[10px] text-[#006633] mt-1">
                    <i class="fas fa-check-circle mr-1"></i>URL copiada!
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── Dados dos itens (passados do PHP para JS) ─────────────────────────────────
const mediaItems = {
<?php foreach ($media as $m):
    $isImg = ($m['file_type'] === 'image');
    $ext   = strtolower(pathinfo($m['file_name'], PATHINFO_EXTENSION));
    $url   = '/crcap/'.$m['file_path'];
    $size  = $m['file_size'] ? fmtSize((int)$m['file_size']) : '';
    $dims  = ($m['width'] && $m['height']) ? $m['width'].'×'.$m['height'] : '';
    $date  = $m['created_at'] ? date('d/m/Y H:i', strtotime($m['created_at'])) : '';
    $mid   = (int)$m['id'];
?>
    <?= $mid ?>: {
        id:    <?= $mid ?>,
        url:   <?= json_encode($url) ?>,
        name:  <?= json_encode($m['file_name']) ?>,
        ext:   <?= json_encode(strtoupper($ext)) ?>,
        isImg: <?= $isImg ? 'true' : 'false' ?>,
        isPdf: <?= ($ext === 'pdf') ? 'true' : 'false' ?>,
        size:  <?= json_encode($size) ?>,
        dims:  <?= json_encode($dims) ?>,
        date:  <?= json_encode($date) ?>,
        mime:  <?= json_encode($m['mime_type'] ?? '') ?>,
    },
<?php endforeach; ?>
};

// ── Ícones por extensão ───────────────────────────────────────────────────────
function extIcon(ext) {
    const e = ext.toLowerCase();
    if (e === 'pdf')              return {ic:'fa-file-pdf',   cl:'text-red-500',   bg:'bg-red-50'};
    if (e==='doc'||e==='docx')   return {ic:'fa-file-word',  cl:'text-blue-600',  bg:'bg-blue-50'};
    if (e==='xls'||e==='xlsx')   return {ic:'fa-file-excel', cl:'text-green-600', bg:'bg-green-50'};
    if (e==='svg')               return {ic:'fa-file-code',  cl:'text-purple-500',bg:'bg-purple-50'};
    return {ic:'fa-file-alt', cl:'text-gray-500', bg:'bg-gray-50'};
}

let _currentUrl = '';

function openModal(id) {
    const m = mediaItems[id];
    if (!m) return;
    _currentUrl = window.location.origin + m.url;

    // Preenche header
    const iconEl = document.getElementById('modal-icon');
    if (m.isImg) {
        iconEl.innerHTML = '<i class="fas fa-image text-blue-500"></i>';
        iconEl.className = 'w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0 bg-blue-50';
    } else {
        const inf = extIcon(m.ext);
        iconEl.innerHTML = '<i class="fas ' + inf.ic + ' ' + inf.cl + '"></i>';
        iconEl.className = 'w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0 ' + inf.bg;
    }
    document.getElementById('modal-name').textContent = m.name;
    const metaParts = [m.ext];
    if (m.size) metaParts.push(m.size);
    if (m.dims) metaParts.push(m.dims);
    if (m.date) metaParts.push(m.date);
    document.getElementById('modal-meta').textContent = metaParts.join(' · ');

    // Botões
    document.getElementById('modal-download-btn').href = m.url;
    document.getElementById('modal-delete-btn').href   = '?action=delete&id=' + m.id;
    document.getElementById('modal-url-display').textContent = _currentUrl;
    document.getElementById('copy-toast').classList.add('hidden');

    // Oculta todos os wraps
    ['img','pdf','doc'].forEach(t => document.getElementById('modal-'+t+'-wrap').classList.add('hidden'));

    // Mostra o wrap correto
    if (m.isImg) {
        const img = document.getElementById('modal-img');
        img.src = m.url;
        img.alt = m.name;
        document.getElementById('modal-img-wrap').classList.remove('hidden');
    } else if (m.isPdf) {
        document.getElementById('modal-pdf').src = m.url;
        document.getElementById('modal-pdf-wrap').classList.remove('hidden');
    } else {
        // Generic doc
        const inf = extIcon(m.ext);
        const iconBox = document.getElementById('modal-doc-icon');
        iconBox.innerHTML = '<i class="fas ' + inf.ic + ' ' + inf.cl + '"></i>';
        iconBox.className = 'w-24 h-24 rounded-2xl mx-auto flex items-center justify-center mb-4 text-5xl ' + inf.bg;
        document.getElementById('modal-doc-name').textContent = m.name;
        document.getElementById('modal-doc-meta').textContent = metaParts.join(' · ');
        document.getElementById('modal-doc-open').href = m.url;
        document.getElementById('modal-doc-wrap').classList.remove('hidden');
    }

    document.getElementById('mediaModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('mediaModal').classList.add('hidden');
    document.body.style.overflow = '';
    // Limpa iframe PDF para parar o carregamento
    document.getElementById('modal-pdf').src = '';
}

function copyModalUrl() {
    navigator.clipboard.writeText(_currentUrl).then(() => {
        const t = document.getElementById('copy-toast');
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 2500);
    });
}

function copyUrl(url) {
    navigator.clipboard.writeText(window.location.origin + url).then(() => {
        const btn = event.currentTarget;
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('bg-green-500','text-white');
        setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('bg-green-500','text-white'); }, 1500);
    });
}

// ── Tecla ESC fecha modal ─────────────────────────────────────────────────────
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── Troca de view (grade / lista) ─────────────────────────────────────────────
function setView(v) {
    const isGrid = (v === 'grid');
    document.getElementById('view-grid').classList.toggle('hidden', !isGrid);
    document.getElementById('view-list').classList.toggle('hidden', isGrid);
    document.getElementById('btn-grid').className = 'px-3 py-2 flex items-center gap-1.5 transition text-xs font-semibold '
        + (isGrid ? 'bg-[#001644] text-white' : 'text-[#022E6B] hover:bg-[#F8FAFC]');
    document.getElementById('btn-list').className = 'px-3 py-2 flex items-center gap-1.5 transition text-xs font-semibold '
        + (!isGrid ? 'bg-[#001644] text-white' : 'text-[#022E6B] hover:bg-[#F8FAFC]');
    localStorage.setItem('mediaView', v);
}

// Restaura preferência de view
const savedView = localStorage.getItem('mediaView') || 'grid';
if (savedView === 'list') setView('list');

// ── Upload com drag & drop ────────────────────────────────────────────────────
function updateUploadList(input) {
    const list = document.getElementById('uploadList');
    list.innerHTML = '';
    [...input.files].forEach(f => {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 text-xs text-[#022E6B] bg-[#F8FAFC] rounded-lg px-3 py-2';
        div.innerHTML = '<i class="fas fa-file text-[#BF8D1A]"></i>'
            + '<span class="flex-1 truncate">' + f.name + '</span>'
            + '<span class="text-[#022E6B]/60">' + Math.round(f.size/1024) + ' KB</span>';
        list.appendChild(div);
    });
}
function handleDrop(e) {
    e.preventDefault();
    const dt = e.dataTransfer;
    const fi = document.getElementById('mediaFiles');
    fi.files = dt.files;
    updateUploadList(fi);
    document.getElementById('uploadArea').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>