<?php
$pageTitle = 'Agenda do Presidente · Admin CRCAP';
$activeAdm = 'agenda';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$adminUser = currentUser();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// Delete
if ($action === 'delete' && $id) {
    dbExec($pdo, "DELETE FROM president_schedule WHERE id=?", [$id]);
    header('Location: /crcap/admin/agenda.php?msg=deleted'); exit;
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_agenda'])) {
    $sid       = (int)($_POST['sid'] ?? 0);
    $title     = trim($_POST['title'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $evType    = $_POST['event_type'] ?? 'meeting';
    $location  = trim($_POST['location'] ?? '');
    $evDate    = $_POST['event_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime   = trim($_POST['end_time'] ?? '') ?: null;
    $status    = $_POST['status'] ?? 'scheduled';
    $priority  = $_POST['priority'] ?? 'medium';
    $isPublic  = isset($_POST['is_public']) ? 1 : 0;
    $image     = trim($_POST['image'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    if ($title && $evDate && $startTime) {
        if ($sid) {
            dbExec($pdo,
                "UPDATE president_schedule SET title=?,description=?,event_type=?,location=?,event_date=?,start_time=?,end_time=?,status=?,priority=?,is_public=?,image=?,notes=? WHERE id=?",
                [$title,$desc,$evType,$location,$evDate,$startTime,$endTime,$status,$priority,$isPublic,$image,$notes,$sid]);
        } else {
            dbExec($pdo,
                "INSERT INTO president_schedule (title,description,event_type,location,event_date,start_time,end_time,status,priority,is_public,image,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$title,$desc,$evType,$location,$evDate,$startTime,$endTime,$status,$priority,$isPublic,$image,$notes,$adminUser['id']]);
        }
        header('Location: /crcap/admin/agenda.php?msg=saved'); exit;
    }
    $saveError = 'Preencha Título, Data e Horário de início.';
}

if ($action === 'edit' && $id) $sched = dbFetch($pdo, "SELECT * FROM president_schedule WHERE id=?", [$id]);
if ($action === 'new')        $sched = ['id'=>0,'title'=>'','description'=>'','event_type'=>'meeting','location'=>'','event_date'=>date('Y-m-d'),'start_time'=>'09:00','end_time'=>'','status'=>'scheduled','priority'=>'medium','is_public'=>1,'image'=>'','notes'=>''];

require_once __DIR__ . '/admin_header.php';
?>

<?php if ($action === 'edit' || $action === 'new'): ?>
<?php $img = $sched['image'] ?? ''; ?>

<div class="flex items-center gap-3 mb-6">
    <a href="/crcap/admin/agenda.php" class="text-[#022E6B] hover:text-[#BF8D1A] transition text-sm"><i class="fas fa-arrow-left"></i></a>
    <h2 class="text-lg font-bold text-[#001644]"><?= $id ? 'Editar Compromisso' : 'Novo Compromisso' ?></h2>
</div>

<?php if (!empty($saveError)): ?>
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl"><?= $saveError ?></div>
<?php endif; ?>

<form method="POST" id="agendaForm" class="grid lg:grid-cols-3 gap-6">
    <input type="hidden" name="form_agenda" value="1">
    <input type="hidden" name="sid" value="<?= $sched['id'] ?>">

    <div class="lg:col-span-2 space-y-5">
        <div class="card p-6 space-y-4">

            <div>
                <label class="form-label">Título *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($sched['title']) ?>" required class="form-input" placeholder="Título do compromisso">
            </div>

            <div>
                <label class="form-label">Descrição</label>
                <textarea name="description" rows="4" class="form-input resize-none"><?= htmlspecialchars($sched['description']) ?></textarea>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div><label class="form-label">Data *</label><input type="date" name="event_date" value="<?= $sched['event_date'] ?>" required class="form-input"></div>
                <div><label class="form-label">Início *</label><input type="time" name="start_time" value="<?= substr($sched['start_time'],0,5) ?>" required class="form-input"></div>
                <div><label class="form-label">Término</label><input type="time" name="end_time" value="<?= substr($sched['end_time']??'',0,5) ?>" class="form-input"></div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Tipo</label>
                    <select name="event_type" class="form-input">
                        <?php foreach(['meeting'=>'Reunião','visit'=>'Visita','ceremony'=>'Cerimônia','conference'=>'Conferência','trip'=>'Viagem','other'=>'Outro'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $sched['event_type']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label">Local</label><input type="text" name="location" value="<?= htmlspecialchars($sched['location']) ?>" class="form-input" placeholder="Local do evento"></div>
            </div>

            <!-- Imagem de Destaque -->
            <div>
                <label class="form-label">Imagem de Destaque</label>
                <div class="border-2 border-dashed border-[#001644]/15 rounded-xl overflow-hidden bg-[#F8FAFC]">

                    <!-- Preview -->
                    <div id="imgPreview" class="<?= $img ? '' : 'hidden' ?> relative">
                        <img id="imgThumb" src="<?= htmlspecialchars($img) ?>" alt="Preview" class="w-full h-52 object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                        <button type="button" onclick="imgRemove()"
                                class="absolute bottom-3 right-3 flex items-center gap-1.5 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-lg shadow-lg transition">
                            <i class="fas fa-trash text-[10px]"></i> Remover imagem
                        </button>
                    </div>

                    <!-- Placeholder -->
                    <div id="imgPlaceholder" class="<?= $img ? 'hidden' : '' ?> py-10 text-center">
                        <i class="fas fa-image text-5xl text-[#001644]/10 mb-3 block"></i>
                        <p class="text-xs text-[#022E6B]/40 mb-1">Nenhuma imagem selecionada</p>
                        <p class="text-[10px] text-[#022E6B]/30">JPG, PNG ou WEBP · máx 5MB</p>
                    </div>

                    <!-- Spinner -->
                    <div id="imgSpinner" class="hidden py-10 text-center">
                        <i class="fas fa-spinner fa-spin text-[#BF8D1A] text-3xl mb-2 block"></i>
                        <p class="text-xs text-[#022E6B]">Enviando…</p>
                    </div>

                    <!-- Toolbar -->
                    <div class="flex gap-2 p-3 border-t border-[#001644]/8 bg-white">
                        <!-- Upload -->
                        <label class="flex-1 cursor-pointer">
                            <span class="flex items-center justify-center gap-2 py-2 bg-[#001644] hover:bg-[#022E6B] text-white text-xs font-semibold rounded-lg transition">
                                <i class="fas fa-upload text-[10px]"></i> Enviar foto
                            </span>
                            <input type="file" id="imgFile" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="imgUpload(this)">
                        </label>
                        <!-- URL toggle -->
                        <button type="button" onclick="imgToggleUrl()"
                                class="px-3 py-2 text-xs font-semibold border-2 border-[#001644]/15 rounded-lg text-[#022E6B] hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition whitespace-nowrap">
                            <i class="fas fa-link mr-1"></i> Colar URL
                        </button>
                    </div>

                    <!-- URL row -->
                    <div id="imgUrlRow" class="hidden p-3 pt-0 bg-white border-t border-[#001644]/5">
                        <div class="flex gap-2">
                            <input type="text" id="imgUrlInput" placeholder="https://exemplo.com/imagem.jpg"
                                   class="form-input flex-1 text-xs" onkeydown="if(event.key==='Enter'){event.preventDefault();imgApplyUrl();}">
                            <button type="button" onclick="imgApplyUrl()"
                                    class="px-4 py-2 bg-[#BF8D1A] hover:bg-[#001644] text-white text-xs font-bold rounded-lg transition">
                                OK
                            </button>
                        </div>
                    </div>

                    <!-- Erro -->
                    <div id="imgError" class="hidden px-3 pb-3 bg-white">
                        <p id="imgErrorMsg" class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2"></p>
                    </div>

                    <!-- Hidden field enviado com o form -->
                    <input type="hidden" name="image" id="imgVal" value="<?= htmlspecialchars($img) ?>">
                </div>
            </div>

            <div>
                <label class="form-label">Notas internas</label>
                <textarea name="notes" rows="3" class="form-input resize-none"><?= htmlspecialchars($sched['notes']) ?></textarea>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card p-5 space-y-4">
            <h3 class="text-sm font-bold text-[#001644]">Opções</h3>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <?php foreach(['scheduled'=>'Agendado','confirmed'=>'Confirmado','in_progress'=>'Em andamento','completed'=>'Concluído','cancelled'=>'Cancelado'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $sched['status']===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Prioridade</label>
                <select name="priority" class="form-input">
                    <?php foreach(['low'=>'Baixa','medium'=>'Média','high'=>'Alta','urgent'=>'Urgente'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $sched['priority']===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_public" <?= $sched['is_public']?'checked':'' ?>>
                <span class="text-xs text-[#022E6B] font-medium">Exibir no site público</span>
            </label>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn-primary flex-1 justify-center"><i class="fas fa-save"></i>Salvar</button>
                <a href="/crcap/admin/agenda.php" class="px-4 py-2.5 border border-[#001644]/10 rounded-xl text-xs font-medium text-[#022E6B] hover:bg-[#F8FAFC] transition">Cancelar</a>
            </div>
        </div>
    </div>
</form>

<script>
// Funções de gerenciamento de imagem — agenda
function $(id){ return document.getElementById(id); }

function imgSet(url) {
    $('imgVal').value   = url;
    $('imgThumb').src   = url;
    $('imgPreview').classList.remove('hidden');
    $('imgPlaceholder').classList.add('hidden');
    $('imgSpinner').classList.add('hidden');
    $('imgUrlRow').classList.add('hidden');
    $('imgError').classList.add('hidden');
}

function imgRemove() {
    $('imgVal').value   = '';
    $('imgThumb').src   = '';
    $('imgFile').value  = '';
    $('imgPreview').classList.add('hidden');
    $('imgPlaceholder').classList.remove('hidden');
}

function imgToggleUrl() {
    $('imgUrlRow').classList.toggle('hidden');
    if (!$('imgUrlRow').classList.contains('hidden')) {
        $('imgUrlInput').focus();
    }
}

function imgApplyUrl() {
    const url = $('imgUrlInput').value.trim();
    if (!url.startsWith('http')) { imgErr('URL inválida. Deve começar com http://'); return; }
    imgErr(''); // limpa erro
    // seta direto — sem validar onload para evitar CORS
    imgSet(url);
}

function imgErr(msg) {
    if (!msg) { $('imgError').classList.add('hidden'); return; }
    $('imgErrorMsg').textContent = msg;
    $('imgError').classList.remove('hidden');
    setTimeout(() => $('imgError').classList.add('hidden'), 5000);
}

async function imgUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    // Validação
    if (!['image/jpeg','image/png','image/webp'].includes(file.type)) {
        imgErr('Formato inválido. Use JPG, PNG ou WEBP.'); input.value=''; return;
    }
    if (file.size > 5*1024*1024) {
        imgErr('Arquivo muito grande. Máximo 5MB.'); input.value=''; return;
    }

    // Preview imediato local
    const reader = new FileReader();
    reader.onload = e => {
        $('imgThumb').src = e.target.result;
        $('imgPreview').classList.remove('hidden');
        $('imgPlaceholder').classList.add('hidden');
    };
    reader.readAsDataURL(file);

    // Upload
    $('imgPlaceholder').classList.add('hidden');
    $('imgSpinner').classList.remove('hidden');

    const fd = new FormData();
    fd.append('file', file);
    fd.append('type', 'image');

    try {
        const resp = await fetch('/crcap/api/upload.php', { method: 'POST', body: fd });
        const text = await resp.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            imgErr('Resposta inválida do servidor: ' + text.substring(0,100));
            $('imgSpinner').classList.add('hidden');
            return;
        }
        if (data.success && data.url) {
            imgSet(data.url);
        } else {
            imgErr('Erro: ' + (data.message || 'Falha desconhecida'));
            $('imgSpinner').classList.add('hidden');
        }
    } catch(e) {
        imgErr('Erro de rede: ' + e.message);
        $('imgSpinner').classList.add('hidden');
    }
}
</script>

<?php else: // LIST VIEW ?>

<?php
$mes    = $_GET['mes'] ?? date('Y-m');
$scheds = dbFetchAll($pdo, "SELECT * FROM president_schedule WHERE DATE_FORMAT(event_date,'%Y-%m')=? ORDER BY event_date ASC, start_time ASC", [$mes]);
$msgMap = ['saved'=>'Compromisso salvo com sucesso!','deleted'=>'Compromisso excluído.'];
?>

<?php if ($m = $msgMap[$_GET['msg'] ?? ''] ?? null): ?>
<div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3 mb-5 font-semibold"><?= $m ?></div>
<?php endif; ?>

<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-3">
        <?php
        $prevMes = date('Y-m', strtotime($mes.'-01 -1 month'));
        $nextMes = date('Y-m', strtotime($mes.'-01 +1 month'));
        ?>
        <a href="?mes=<?= $prevMes ?>" class="w-8 h-8 rounded-lg bg-white border border-[#001644]/10 flex items-center justify-center text-xs hover:border-[#BF8D1A] transition"><i class="fas fa-chevron-left"></i></a>
        <h2 class="text-sm font-bold text-[#001644]"><?= strftime('%B %Y', strtotime($mes.'-01')) ?></h2>
        <a href="?mes=<?= $nextMes ?>" class="w-8 h-8 rounded-lg bg-white border border-[#001644]/10 flex items-center justify-center text-xs hover:border-[#BF8D1A] transition"><i class="fas fa-chevron-right"></i></a>
        <a href="?mes=<?= date('Y-m') ?>" class="px-3 py-1.5 text-xs text-[#022E6B] bg-white border border-[#001644]/10 rounded-lg hover:border-[#BF8D1A] transition">Hoje</a>
    </div>
    <a href="/crcap/admin/agenda.php?action=new" class="btn-gold"><i class="fas fa-plus"></i>Novo Compromisso</a>
</div>

<div class="space-y-3">
    <?php if (empty($scheds)): ?>
    <div class="card p-12 text-center text-[#001644]/30">
        <i class="fas fa-calendar-check text-4xl mb-3 block"></i>
        <p class="font-semibold">Nenhum compromisso neste mês</p>
        <a href="/crcap/admin/agenda.php?action=new" class="btn-primary mt-4 inline-flex"><i class="fas fa-plus"></i>Adicionar compromisso</a>
    </div>
    <?php else: foreach ($scheds as $s):
        $d = new DateTime($s['event_date']);
        $isToday = $d->format('Y-m-d') === date('Y-m-d');
    ?>
    <div class="card p-4 flex items-center gap-4 hover:shadow-md transition <?= $isToday?'ring-2 ring-[#BF8D1A]':'' ?>">
        <?php if ($s['image']): ?>
        <img src="<?= htmlspecialchars($s['image']) ?>" alt="" class="w-14 h-14 rounded-xl object-cover flex-shrink-0">
        <?php else: ?>
        <div class="w-14 h-14 bg-gradient-to-br <?= $isToday?'from-[#BF8D1A] to-[#001644]':'from-[#001644] to-[#022E6B]' ?> rounded-xl flex flex-col items-center justify-center text-white flex-shrink-0 text-center">
            <span class="text-xl font-bold font-serif leading-none"><?= $d->format('d') ?></span>
            <span class="text-[9px] uppercase"><?= $d->format('D') ?></span>
        </div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <h3 class="font-bold text-[#001644] text-sm truncate"><?= htmlspecialchars($s['title']) ?></h3>
                <span class="badge <?= $s['status']==='confirmed'?'badge-green':($s['status']==='cancelled'?'badge-red':'badge-gold') ?>"><?= $s['status'] ?></span>
                <?php if (!$s['is_public']): ?><span class="badge badge-gray">Privado</span><?php endif; ?>
            </div>
            <div class="flex items-center gap-3 text-[10px] text-[#022E6B] flex-wrap">
                <span><i class="fas fa-calendar text-[#BF8D1A]"></i> <?= $d->format('d/m/Y') ?></span>
                <span><i class="fas fa-clock text-[#BF8D1A]"></i> <?= substr($s['start_time'],0,5) ?><?= $s['end_time'] ? '–'.substr($s['end_time'],0,5) : '' ?></span>
                <?php if ($s['location']): ?><span><i class="fas fa-map-marker-alt text-[#BF8D1A]"></i> <?= htmlspecialchars(substr($s['location'],0,40)) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="flex gap-1 flex-shrink-0">
            <a href="/crcap/admin/agenda.php?action=edit&id=<?= $s['id'] ?>" class="w-8 h-8 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"><i class="fas fa-edit"></i></a>
            <a href="/crcap/admin/agenda.php?action=delete&id=<?= $s['id'] ?>" onclick="return confirm('Excluir este compromisso?')" class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs"><i class="fas fa-trash"></i></a>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>