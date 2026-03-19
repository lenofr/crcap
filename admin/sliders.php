<?php
// ── Ações com redirect ANTES de qualquer output ───────────────────────────────
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$msg    = '';

if ($action === 'delete' && $id) {
    dbExec($pdo,"DELETE FROM sliders WHERE id=?",[$id]);
    header('Location: /crcap/admin/sliders.php?msg=deleted'); exit;
}

// ── Migração: adiciona colunas extras se não existirem ────────────────────────
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN image_path VARCHAR(500) NULL AFTER image"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN btn1_text VARCHAR(255) NULL"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN btn1_url  VARCHAR(1000) NULL"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN btn1_target ENUM('_self','_blank') DEFAULT '_self'"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN btn1_active TINYINT(1) DEFAULT 1"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN btn2_text VARCHAR(255) NULL"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN btn2_url  VARCHAR(1000) NULL"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN btn2_target ENUM('_self','_blank') DEFAULT '_self'"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN btn2_active TINYINT(1) DEFAULT 0"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN image_link_url VARCHAR(1000) NULL"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE sliders ADD COLUMN image_link_target ENUM('_self','_blank') DEFAULT '_self'"); } catch(Exception $e){}

// ── Salvar slide ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_slider'])) {

    // ── Imagem: prioridade upload > URL digitada > imagem atual no BD ────────
    $imagePath = '';
    // 1) Upload de arquivo
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__).'/uploads/sliders/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $fname = 'slide_'.uniqid().'.'.$ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir.$fname))
                $imagePath = '/crcap/uploads/sliders/'.$fname;
        }
    }
    // 2) URL digitada no campo de texto
    if ($imagePath === '') {
        $urlField = trim($_POST['image'] ?? '');
        if ($urlField !== '') $imagePath = $urlField;
    }
    // 3) Campo oculto image_current (preserva imagem ao editar sem trocar)
    if ($imagePath === '') {
        $imagePath = trim($_POST['image_current'] ?? '');
    }
    // 4) Último recurso: busca no BD
    if ($imagePath === '' && $id) {
        $existing  = dbFetch($pdo, "SELECT image FROM sliders WHERE id=?", [$id]);
        $imagePath = $existing['image'] ?? '';
    }

    $d = [
        'title'            => trim($_POST['title'] ?? ''),
        'subtitle'         => trim($_POST['subtitle'] ?? ''),
        'description'      => trim($_POST['description'] ?? ''),
        'image'            => $imagePath,
        'link_url'         => trim($_POST['link_url'] ?? ''),
        'link_text'        => trim($_POST['link_text'] ?? ''),
        'link_target'      => $_POST['link_target'] ?? '_self',
        'order_position'   => (int)($_POST['order_position'] ?? 0),
        'status'           => $_POST['status'] ?? 'active',
        'text_alignment'   => $_POST['text_alignment'] ?? 'left',
        'show_from'        => $_POST['show_from'] ?: null,
        'show_until'       => $_POST['show_until'] ?: null,
        // Botão 1 (ex: "Saiba mais")
        'btn1_text'        => trim($_POST['btn1_text'] ?? ''),
        'btn1_url'         => trim($_POST['btn1_url'] ?? ''),
        'btn1_target'      => $_POST['btn1_target'] ?? '_self',
        'btn1_active'      => isset($_POST['btn1_active']) ? 1 : 0,
        // Botão 2
        'btn2_text'        => trim($_POST['btn2_text'] ?? ''),
        'btn2_url'         => trim($_POST['btn2_url'] ?? ''),
        'btn2_target'      => $_POST['btn2_target'] ?? '_self',
        'btn2_active'      => isset($_POST['btn2_active']) ? 1 : 0,
        // Link na imagem inteira
        'image_link_url'    => trim($_POST['image_link_url'] ?? ''),
        'image_link_target' => $_POST['image_link_target'] ?? '_self',
    ];

    try {
        if ($id) {
            $sets = implode(',', array_map(fn($k) => "`$k`=?", array_keys($d)));
            dbExec($pdo,"UPDATE sliders SET $sets WHERE id=?", [...array_values($d), $id]);
            header('Location: /crcap/admin/sliders.php?action=edit&id='.$id.'&msg=updated');
            exit;
        } else {
            $keys = implode(',', array_map(fn($k) => "`$k`", array_keys($d)));
            $phs  = implode(',', array_fill(0, count($d), '?'));
            dbExec($pdo,"INSERT INTO sliders ($keys) VALUES ($phs)", array_values($d));
            $id  = (int)$pdo->lastInsertId();
            header('Location: /crcap/admin/sliders.php?action=edit&id='.$id.'&msg=created');
            exit;
        }
    } catch(Exception $e) { $msg = 'error: '.$e->getMessage(); }
}

// ═════════════════════════════════════════════════════════════════════════════
// FORMULÁRIO
// ═════════════════════════════════════════════════════════════════════════════
if ($action === 'new' || $action === 'edit') {
    $s = $id ? dbFetch($pdo,"SELECT * FROM sliders WHERE id=?",[$id]) : [];
$pageTitle = 'Slider da Home · Admin CRCAP';
$activeAdm = 'sliders';
require_once __DIR__ . '/admin_header.php';
?>
<div class="flex items-center gap-3 mb-6">
    <a href="/crcap/admin/sliders.php" class="w-8 h-8 rounded-lg bg-white border border-[#001644]/10 flex items-center justify-center hover:border-[#BF8D1A] transition">
        <i class="fas fa-arrow-left text-xs text-[#001644]"></i>
    </a>
    <h2 class="text-lg font-bold text-[#001644]"><?= $id ? 'Editar' : 'Novo' ?> Slide</h2>
</div>

<?php $msgShow = $msg ?: ($_GET['msg'] ?? ''); ?>
<?php if ($msgShow === 'created' || $msgShow === 'updated'): ?>
<div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5">
    <i class="fas fa-check-circle mr-1"></i> Slide <?= $msgShow === 'created' ? 'criado' : 'atualizado' ?> com sucesso!
</div>
<?php endif; ?>
<?php if (str_starts_with($msgShow,'error')): ?>
<div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5"><?= htmlspecialchars($msgShow) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="grid lg:grid-cols-3 gap-6">
    <input type="hidden" name="form_slider" value="1">
    <input type="hidden" name="image_current" value="<?= htmlspecialchars($s['image'] ?? '') ?>">

    <!-- ── Coluna principal ──────────────────────────────────────────────── -->
    <div class="lg:col-span-2 space-y-5">

        <!-- Conteúdo textual (tudo opcional) -->
        <div class="card p-6 space-y-4">
            <div class="flex items-center gap-2 mb-1">
                <i class="fas fa-font text-[#BF8D1A] text-xs"></i>
                <span class="text-xs font-bold text-[#001644] uppercase tracking-wider">Texto do Slide</span>
                <span class="text-[9px] text-[#022E6B]/50">(todos opcionais — slide pode ser só imagem)</span>
            </div>
            <div>
                <label class="form-label">Título</label>
                <input type="text" name="title" value="<?= htmlspecialchars($s['title'] ?? '') ?>"
                       class="form-input text-base font-semibold" placeholder="Deixe vazio para slide só com imagem">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Subtítulo</label>
                    <input type="text" name="subtitle" value="<?= htmlspecialchars($s['subtitle'] ?? '') ?>" class="form-input">
                </div>
                <div>
                    <label class="form-label">Alinhamento do texto</label>
                    <select name="text_alignment" class="form-input">
                        <?php foreach(['left'=>'Esquerda','center'=>'Centro','right'=>'Direita'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($s['text_alignment']??'left')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Descrição / Texto auxiliar</label>
                <textarea name="description" rows="2" class="form-input" placeholder="Texto de apoio opcional"><?= htmlspecialchars($s['description'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Imagem -->
        <div class="card p-6 space-y-4">
            <div class="flex items-center gap-2 mb-1">
                <i class="fas fa-image text-[#BF8D1A] text-xs"></i>
                <span class="text-xs font-bold text-[#001644] uppercase tracking-wider">Imagem do Slide *</span>
            </div>

            <!-- Preview atual -->
            <?php $curImg = $s['image'] ?? ''; ?>
            <div id="slidePreviewWrap" class="<?= $curImg ? '' : 'hidden' ?> relative rounded-xl overflow-hidden h-44 bg-[#F8FAFC] group">
                <img id="slidePreviewEl" src="<?= htmlspecialchars($curImg) ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                    <span class="text-white text-xs font-semibold bg-black/30 px-3 py-1.5 rounded-lg">Clique abaixo para trocar</span>
                </div>
            </div>

            <!-- Tabs: Upload ou URL -->
            <div class="flex rounded-xl border border-[#001644]/10 overflow-hidden text-xs w-fit">
                <button type="button" id="tab-upload" onclick="switchTab('upload')"
                        class="px-4 py-2 font-semibold transition bg-[#001644] text-white flex items-center gap-1.5">
                    <i class="fas fa-upload text-[10px]"></i> Upload
                </button>
                <button type="button" id="tab-url" onclick="switchTab('url')"
                        class="px-4 py-2 font-semibold transition text-[#022E6B] hover:bg-[#F8FAFC] flex items-center gap-1.5">
                    <i class="fas fa-link text-[10px]"></i> URL externa
                </button>
            </div>

            <!-- Upload de arquivo -->
            <div id="panel-upload">
                <div class="border-2 border-dashed border-[#001644]/20 rounded-xl p-6 text-center cursor-pointer hover:border-[#BF8D1A]/60 transition"
                     onclick="document.getElementById('imageFileInput').click()"
                     ondragover="event.preventDefault();this.classList.add('!border-[#BF8D1A]')"
                     ondrop="handleImgDrop(event)">
                    <i class="fas fa-cloud-upload-alt text-3xl text-[#001644]/25 mb-2 block"></i>
                    <p class="text-sm font-medium text-[#001644]">Clique ou arraste a imagem aqui</p>
                    <p class="text-[10px] text-[#022E6B]/50 mt-1">JPG, PNG, WEBP, GIF (recomendado: 1920×600px)</p>
                    <input type="file" id="imageFileInput" name="image_file" class="hidden"
                           accept="image/*" onchange="previewUpload(this)">
                </div>
                <p id="uploadFileName" class="text-xs text-[#006633] mt-2 hidden"></p>
            </div>

            <!-- URL externa -->
            <div id="panel-url" class="hidden">
                <input type="text" name="image" id="slideImgUrl"
                       value="<?= htmlspecialchars($curImg) ?>"
                       class="form-input" placeholder="https://exemplo.com/imagem.jpg"
                       oninput="previewUrl(this.value)">
                <p class="text-[9px] text-[#022E6B]/50 mt-1">Cole a URL de uma imagem externa</p>
            </div>

            <!-- Link na imagem inteira -->
            <div class="border border-[#001644]/8 rounded-xl p-4 bg-[#F8FAFC] space-y-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-mouse-pointer text-[#022E6B]/40 text-xs"></i>
                    <span class="text-xs font-semibold text-[#001644]">Link ao clicar na imagem inteira</span>
                    <span class="text-[9px] text-[#022E6B]/40">(opcional)</span>
                </div>
                <div class="grid sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <input type="text" name="image_link_url"
                               value="<?= htmlspecialchars($s['image_link_url'] ?? '') ?>"
                               class="form-input text-xs" placeholder="https://...">
                    </div>
                    <div>
                        <select name="image_link_target" class="form-input text-xs">
                            <option value="_self"  <?= ($s['image_link_target']??'_self')==='_self' ?'selected':'' ?>>Mesma aba</option>
                            <option value="_blank" <?= ($s['image_link_target']??'')==='_blank'?'selected':'' ?>>Nova aba</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de ação do slide -->
        <div class="card p-6 space-y-4">
            <div class="flex items-center gap-2 mb-1">
                <i class="fas fa-hand-pointer text-[#BF8D1A] text-xs"></i>
                <span class="text-xs font-bold text-[#001644] uppercase tracking-wider">Botões de Ação</span>
                <span class="text-[9px] text-[#022E6B]/50">(ligue/desligue cada botão)</span>
            </div>

            <!-- Botão 1 -->
            <div class="border border-[#001644]/8 rounded-xl p-4 space-y-3" id="btn1-panel">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded-md bg-[#BF8D1A] flex items-center justify-center text-white text-[9px] font-bold">1</div>
                        <span class="text-xs font-semibold text-[#001644]">Botão Principal</span>
                    </div>
                    <!-- Toggle ligar/desligar -->
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="btn1_active" id="btn1_active"
                               <?= ($s['btn1_active'] ?? 1) ? 'checked' : '' ?>
                               class="sr-only peer" onchange="toggleBtn(1,this.checked)">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#001644]"></div>
                        <span class="ml-2 text-[10px] font-semibold text-[#022E6B]" id="btn1_lbl"><?= ($s['btn1_active']??1) ? 'Ativo' : 'Inativo' ?></span>
                    </label>
                </div>
                <div id="btn1-fields" class="grid sm:grid-cols-3 gap-3 <?= ($s['btn1_active']??1) ? '' : 'opacity-40 pointer-events-none' ?>">
                    <div>
                        <label class="form-label text-[10px]">Texto do botão</label>
                        <input type="text" name="btn1_text"
                               value="<?= htmlspecialchars($s['btn1_text'] ?? ($s['link_text'] ?? 'Saiba mais')) ?>"
                               class="form-input text-xs" placeholder="Saiba mais">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">URL</label>
                        <input type="text" name="btn1_url"
                               value="<?= htmlspecialchars($s['btn1_url'] ?? ($s['link_url'] ?? '')) ?>"
                               class="form-input text-xs" placeholder="https://...">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">Abrir em</label>
                        <select name="btn1_target" class="form-input text-xs">
                            <option value="_self"  <?= ($s['btn1_target']??'_self')==='_self' ?'selected':'' ?>>Mesma aba</option>
                            <option value="_blank" <?= ($s['btn1_target']??'')==='_blank'?'selected':'' ?>>Nova aba</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Botão 2 -->
            <div class="border border-[#001644]/8 rounded-xl p-4 space-y-3" id="btn2-panel">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-5 h-5 rounded-md bg-[#022E6B] flex items-center justify-center text-white text-[9px] font-bold">2</div>
                        <span class="text-xs font-semibold text-[#001644]">Botão Secundário</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="btn2_active" id="btn2_active"
                               <?= ($s['btn2_active'] ?? 0) ? 'checked' : '' ?>
                               class="sr-only peer" onchange="toggleBtn(2,this.checked)">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#001644]"></div>
                        <span class="ml-2 text-[10px] font-semibold text-[#022E6B]" id="btn2_lbl"><?= ($s['btn2_active']??0) ? 'Ativo' : 'Inativo' ?></span>
                    </label>
                </div>
                <div id="btn2-fields" class="grid sm:grid-cols-3 gap-3 <?= ($s['btn2_active']??0) ? '' : 'opacity-40 pointer-events-none' ?>">
                    <div>
                        <label class="form-label text-[10px]">Texto do botão</label>
                        <input type="text" name="btn2_text"
                               value="<?= htmlspecialchars($s['btn2_text'] ?? '') ?>"
                               class="form-input text-xs" placeholder="Ver mais">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">URL</label>
                        <input type="text" name="btn2_url"
                               value="<?= htmlspecialchars($s['btn2_url'] ?? '') ?>"
                               class="form-input text-xs" placeholder="https://...">
                    </div>
                    <div>
                        <label class="form-label text-[10px]">Abrir em</label>
                        <select name="btn2_target" class="form-input text-xs">
                            <option value="_self"  <?= ($s['btn2_target']??'_self')==='_self' ?'selected':'' ?>>Mesma aba</option>
                            <option value="_blank" <?= ($s['btn2_target']??'')==='_blank'?'selected':'' ?>>Nova aba</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Legado: campos originais ocultos para compatibilidade -->
            <input type="hidden" name="link_text" value="">
            <input type="hidden" name="link_url"  value="">
            <input type="hidden" name="link_target" value="_self">
        </div>
    </div>

    <!-- ── Sidebar ────────────────────────────────────────────────────────── -->
    <div class="space-y-5">
        <div class="card p-5 space-y-3">
            <p class="text-xs font-bold text-[#001644] uppercase tracking-wider mb-2">Configurações</p>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="active"   <?= ($s['status']??'active')==='active'  ?'selected':'' ?>>✅ Ativo</option>
                    <option value="inactive" <?= ($s['status']??'')==='inactive'?'selected':'' ?>>⏸ Inativo</option>
                </select>
            </div>
            <div>
                <label class="form-label">Ordem de exibição</label>
                <input type="number" name="order_position" value="<?= $s['order_position'] ?? 0 ?>" min="0" class="form-input">
            </div>
            <div>
                <label class="form-label">Exibir de</label>
                <input type="date" name="show_from" value="<?= $s['show_from'] ?? '' ?>" class="form-input">
            </div>
            <div>
                <label class="form-label">Exibir até</label>
                <input type="date" name="show_until" value="<?= $s['show_until'] ?? '' ?>" class="form-input">
            </div>
        </div>

        <!-- Preview mini -->
        <div class="card p-4">
            <p class="text-xs font-bold text-[#001644] uppercase tracking-wider mb-3">Preview</p>
            <div class="relative rounded-xl overflow-hidden h-28 bg-[#001644]">
                <img id="previewMiniImg" src="<?= htmlspecialchars($curImg) ?>"
                     class="w-full h-full object-cover opacity-80" onerror="this.style.display='none'">
                <div class="absolute inset-0 bg-gradient-to-t from-[#001644]/80 to-transparent flex items-end p-3">
                    <div>
                        <p id="previewTitle"   class="text-white text-[10px] font-bold line-clamp-1"><?= htmlspecialchars($s['title'] ?? 'Título do slide') ?></p>
                        <p id="previewSubtitle" class="text-white/70 text-[8px] line-clamp-1"><?= htmlspecialchars($s['subtitle'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-primary flex-1 justify-center"><i class="fas fa-save"></i>Salvar</button>
            <a href="/crcap/admin/sliders.php" class="flex-1 py-2.5 text-xs font-semibold text-[#001644] border-2 border-[#001644]/20 rounded-xl hover:border-[#BF8D1A] transition text-center">Cancelar</a>
        </div>
        <?php if ($id): ?>
        <a href="?action=delete&id=<?= $id ?>" onclick="return confirm('Excluir este slide?')"
           class="btn-danger w-full justify-center" style="display:flex">
            <i class="fas fa-trash"></i>Excluir slide
        </a>
        <?php endif; ?>
    </div>
</form>

<script>
// ── Tabs Upload / URL ─────────────────────────────────────────────────────────
function switchTab(tab) {
    const isUpload = tab === 'upload';
    document.getElementById('panel-upload').classList.toggle('hidden', !isUpload);
    document.getElementById('panel-url').classList.toggle('hidden', isUpload);
    document.getElementById('tab-upload').className = 'px-4 py-2 font-semibold transition flex items-center gap-1.5 text-xs '
        + (isUpload ? 'bg-[#001644] text-white' : 'text-[#022E6B] hover:bg-[#F8FAFC]');
    document.getElementById('tab-url').className = 'px-4 py-2 font-semibold transition flex items-center gap-1.5 text-xs '
        + (!isUpload ? 'bg-[#001644] text-white' : 'text-[#022E6B] hover:bg-[#F8FAFC]');
}

// ── Preview ao fazer upload ───────────────────────────────────────────────────
function previewUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = e => setPreview(e.target.result);
    reader.readAsDataURL(file);
    document.getElementById('uploadFileName').textContent = '✅ ' + file.name;
    document.getElementById('uploadFileName').classList.remove('hidden');
}

function previewUrl(url) {
    if (url) setPreview(url);
}

function setPreview(src) {
    document.getElementById('slidePreviewEl').src    = src;
    document.getElementById('previewMiniImg').src    = src;
    document.getElementById('slidePreviewWrap').classList.remove('hidden');
}

// ── Drag & drop imagem ────────────────────────────────────────────────────────
function handleImgDrop(e) {
    e.preventDefault();
    const fi = document.getElementById('imageFileInput');
    fi.files = e.dataTransfer.files;
    previewUpload(fi);
}

// ── Toggle botões ligar/desligar ──────────────────────────────────────────────
function toggleBtn(n, on) {
    const fields = document.getElementById('btn'+n+'-fields');
    const lbl    = document.getElementById('btn'+n+'_lbl');
    fields.classList.toggle('opacity-40', !on);
    fields.classList.toggle('pointer-events-none', !on);
    lbl.textContent = on ? 'Ativo' : 'Inativo';
}

// ── Preview live do título ────────────────────────────────────────────────────
document.querySelector('[name="title"]').addEventListener('input', function() {
    document.getElementById('previewTitle').textContent = this.value || 'Título do slide';
});
document.querySelector('[name="subtitle"]').addEventListener('input', function() {
    document.getElementById('previewSubtitle').textContent = this.value;
});

// Init: detecta tipo de imagem e ativa tab correta
const curImg = <?= json_encode($s['image'] ?? '') ?>;
if (curImg) {
    if (curImg.startsWith('http') || curImg.startsWith('//')) {
        switchTab('url');
        document.getElementById('slideImgUrl').value = curImg;
    } else {
        switchTab('upload');
        setPreview(curImg);
    }
}
</script>

<?php

// ═════════════════════════════════════════════════════════════════════════════
// LISTA
// ═════════════════════════════════════════════════════════════════════════════
} else {
    $sliders = dbFetchAll($pdo,"SELECT * FROM sliders ORDER BY order_position ASC, created_at DESC");
$pageTitle = 'Slider da Home · Admin CRCAP';
$activeAdm = 'sliders';
require_once __DIR__ . '/admin_header.php';
?>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2">
        <i class="fas fa-images text-[#BF8D1A]"></i>Slider da Home
        <span class="text-xs font-normal text-[#022E6B]/50"><?= count($sliders) ?> slide(s)</span>
    </h2>
    <a href="?action=new" class="btn-primary"><i class="fas fa-plus"></i>Novo Slide</a>
</div>

<?php if (($_GET['msg'] ?? '') === 'deleted'): ?>
<div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5">Slide excluído.</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php if (empty($sliders)): ?>
    <div class="col-span-3 card p-12 text-center text-[#001644]/30">
        <i class="fas fa-images text-4xl mb-3 block"></i>Nenhum slide criado
    </div>
    <?php else: foreach ($sliders as $sl): ?>
    <div class="card overflow-hidden group">
        <div class="relative h-40 overflow-hidden bg-[#001644]">
            <img src="<?= htmlspecialchars($sl['image'] ?? '') ?>" alt=""
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                 onerror="this.style.opacity='0.2'">
            <div class="absolute inset-0 bg-gradient-to-t from-[#001644]/80 to-transparent"></div>
            <div class="absolute top-2 right-2 flex gap-1">
                <span class="badge <?= $sl['status']==='active'?'badge-green':'badge-gray' ?>"><?= $sl['status'] ?></span>
            </div>
            <div class="absolute bottom-2 left-3 right-3">
                <p class="text-white text-xs font-bold line-clamp-1"><?= htmlspecialchars($sl['title'] ?: '(sem título)') ?></p>
                <?php
                $btns = [];
                if ($sl['btn1_active'] ?? 1) $btns[] = $sl['btn1_text'] ?: $sl['link_text'] ?: '';
                if ($sl['btn2_active'] ?? 0) $btns[] = $sl['btn2_text'] ?? '';
                $btns = array_filter($btns);
                if ($btns):
                ?>
                <p class="text-white/60 text-[9px] mt-0.5">Botões: <?= htmlspecialchars(implode(' · ', $btns)) ?></p>
                <?php endif; ?>
            </div>
            <div class="absolute top-2 left-2 bg-black/40 text-white text-[9px] px-2 py-0.5 rounded-md font-bold">
                #<?= $sl['order_position'] ?>
            </div>
        </div>
        <div class="p-4 flex gap-2">
            <a href="?action=edit&id=<?= $sl['id'] ?>" class="btn-primary flex-1 justify-center py-2">
                <i class="fas fa-edit"></i>Editar
            </a>
            <a href="?action=delete&id=<?= $sl['id'] ?>" onclick="return confirm('Excluir?')"
               class="w-9 h-9 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs">
                <i class="fas fa-trash"></i>
            </a>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>
<?php } ?>
<?php require_once __DIR__ . '/admin_footer.php'; ?>