<?php
$pageTitle = 'Usuários · Admin CRCAP';
$activeAdm = 'users';
require_once __DIR__ . '/admin_header.php';

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'delete' && $id && $id !== $adminUser['id']) {
    dbExec($pdo, "DELETE FROM users WHERE id=?", [$id]);
    header('Location: /crcap/admin/users.php?msg=deleted'); exit;
}

if ($action === 'toggle' && $id) {
    $cur = dbFetch($pdo, "SELECT status FROM users WHERE id=?", [$id]);
    $new = $cur['status'] === 'active' ? 'suspended' : 'active';
    dbExec($pdo, "UPDATE users SET status=? WHERE id=?", [$new, $id]);
    header('Location: /crcap/admin/users.php?msg=updated'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_user'])) {
    $uid      = (int)($_POST['uid'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role     = $_POST['role'] ?? 'viewer';
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $status   = $_POST['status'] ?? 'active';

    if ($username && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            if ($uid) {
                $upd = "UPDATE users SET username=?,email=?,full_name=?,role=?,phone=?,status=?";
                $p   = [$username,$email,$fullName,$role,$phone,$status];
                if ($password) { $upd .= ",password=?"; $p[] = password_hash($password, PASSWORD_DEFAULT); }
                $p[] = $uid;
                dbExec($pdo, "$upd WHERE id=?", $p);
            } else {
                if (!$password) { $msg = 'Senha obrigatória para novo usuário.'; goto endSave; }
                dbExec($pdo, "INSERT INTO users (username,email,password,full_name,role,phone,status) VALUES (?,?,?,?,?,?,?)",
                    [$username,$email,password_hash($password,PASSWORD_DEFAULT),$fullName,$role,$phone,$status]);
            }
            // Save permissions for editors
            if ($role === 'editor' || ($uid && $role !== 'admin')) {
                $targetId = $uid ?: (int)$pdo->lastInsertId();
                $pdo->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$targetId]);
                $mods = $_POST['permissions'] ?? [];
                if (is_array($mods)) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO user_permissions (user_id, module) VALUES (?,?)");
                    foreach ($mods as $mod) {
                        $mod = preg_replace('/[^a-z_]/', '', $mod);
                        if ($mod) $ins->execute([$targetId, $mod]);
                    }
                }
            }
            header('Location: /crcap/admin/users.php?msg=saved'); exit;
        } catch (Exception $e) { $msg = 'Erro: '.$e->getMessage(); }
    } else { $msg = 'Preencha os campos obrigatórios.'; }
    endSave:;
}

if ($action === 'edit' && $id) $user = dbFetch($pdo, "SELECT * FROM users WHERE id=?", [$id]);
if ($action === 'new') $user = ['id'=>0,'username'=>'','email'=>'','full_name'=>'','role'=>'viewer','phone'=>'','status'=>'active'];

// Load current permissions for edit
$currentPerms = [];
if ($id && ($user['role'] ?? '') !== 'admin') {
    // Ensure table exists
    try { $pdo->exec("CREATE TABLE IF NOT EXISTS user_permissions (user_id INT UNSIGNED NOT NULL, module VARCHAR(50) NOT NULL, PRIMARY KEY (user_id, module)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch (Exception $e) {}
    $rows = $pdo->prepare("SELECT module FROM user_permissions WHERE user_id=?");
    $rows->execute([$id]);
    $currentPerms = $rows->fetchAll(PDO::FETCH_COLUMN);
}

// All available modules
$allModules = [
    'geral'       => ['dashboard' => 'Dashboard'],
    'Conteúdo'    => ['posts' => 'Posts e Notícias', 'pages' => 'Páginas Estáticas', 'sliders' => 'Slider da Home', 'media' => 'Biblioteca de Mídia', 'documents' => 'Documentos', 'galleries' => 'Galerias de Fotos', 'menu' => 'Menu'],
    'Comunicação' => ['agenda' => 'Agenda do Presidente', 'events' => 'Eventos', 'newsletter' => 'Newsletter', 'whatsapp' => 'WhatsApp', 'contacts' => 'Contatos Recebidos'],
    'Sistema'     => ['users' => 'Usuários', 'settings' => 'Configurações', 'smtp' => 'SMTP / Email', 'reports' => 'Relatórios', 'logs' => 'Logs de Atividade'],
];

if ($action === 'edit' || $action === 'new'): ?>
<div class="flex items-center gap-3 mb-6">
    <a href="/crcap/admin/users.php" class="text-[#022E6B] hover:text-[#BF8D1A] transition text-sm"><i class="fas fa-arrow-left"></i></a>
    <h2 class="text-lg font-bold text-[#001644]"><?= $id ? 'Editar Usuário' : 'Novo Usuário' ?></h2>
</div>
<?php if (!empty($msg)): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="POST" class="grid lg:grid-cols-3 gap-6">
    <input type="hidden" name="form_user" value="1">
    <input type="hidden" name="uid" value="<?= $user['id'] ?>">
    <div class="lg:col-span-2 card p-6 space-y-4">
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="form-label">Nome de Usuário *</label><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="form-input"></div>
            <div><label class="form-label">E-mail *</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="form-input"></div>
            <div><label class="form-label">Nome Completo</label><input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" class="form-input"></div>
            <div><label class="form-label">Telefone</label><input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="form-input"></div>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="form-label">Perfil / Role</label>
                <select name="role" class="form-input"><?php foreach(['admin'=>'Administrador','editor'=>'Editor','author'=>'Autor','viewer'=>'Visualizador'] as $v=>$l): ?><option value="<?= $v ?>" <?= $user['role']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select>
            </div>
            <div><label class="form-label">Status</label>
                <select name="status" class="form-input"><?php foreach(['active'=>'Ativo','inactive'=>'Inativo','suspended'=>'Suspenso'] as $v=>$l): ?><option value="<?= $v ?>" <?= $user['status']===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select>
            </div>
        </div>
        <div><label class="form-label">Senha<?= $id ? ' (deixe em branco para manter)' : ' *' ?></label><input type="password" name="password" class="form-input" placeholder="Mínimo 8 caracteres"></div>
        <div class="flex gap-2 pt-2">
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i>Salvar</button>
            <a href="/crcap/admin/users.php" class="px-4 py-2.5 border border-[#001644]/10 rounded-xl text-xs font-medium text-[#022E6B] hover:bg-[#F8FAFC] transition">Cancelar</a>
        </div>
    </div>

    <!-- Sidebar direita: avatar + permissões -->
    <div class="space-y-5">
        <div class="card p-5">
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4">
            <?= strtoupper(substr($user['full_name'] ?: $user['username'] ?: 'U', 0, 1)) ?>
        </div>
        <p class="text-center text-xs font-semibold text-[#001644]"><?= htmlspecialchars($user['full_name'] ?: $user['username'] ?: 'Novo usuário') ?></p>
        <p class="text-center text-[10px] text-[#022E6B] capitalize mt-1"><?= $user['role'] ?></p>
        <?php if ($user['last_login'] ?? null): ?><p class="text-center text-[9px] text-[#022E6B]/60 mt-2">Último acesso: <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?></p><?php endif; ?>
        </div><!-- end avatar card -->

        <!-- Permissões de Módulos -->
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xs font-bold text-[#001644] flex items-center gap-2">
                        <i class="fas fa-shield-alt text-[#BF8D1A]"></i> Benefícios / Módulos
                    </h3>
                    <p class="text-[9px] text-[#022E6B]/60 mt-0.5">Visível apenas para Editor</p>
                </div>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <span class="text-[9px] text-[#006633] font-bold bg-[#006633]/10 px-2 py-1 rounded-full">Acesso total</span>
                <?php endif; ?>
            </div>

            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <p class="text-[10px] text-[#022E6B]/50 text-center py-4">Administradores têm acesso a todos os módulos.</p>
            <?php else: ?>

            <!-- Botões rápidos -->
            <div class="flex gap-2 mb-4">
                <button type="button" onclick="toggleAllPerms(true)"
                        class="flex-1 py-1.5 text-[10px] font-semibold bg-[#006633]/10 text-[#006633] rounded-lg hover:bg-[#006633]/20 transition">
                    <i class="fas fa-check-square mr-1"></i>Todos
                </button>
                <button type="button" onclick="toggleAllPerms(false)"
                        class="flex-1 py-1.5 text-[10px] font-semibold bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">
                    <i class="fas fa-square mr-1"></i>Nenhum
                </button>
            </div>

            <?php foreach ($allModules as $groupName => $mods): ?>
            <div class="mb-3">
                <p class="text-[9px] font-bold uppercase tracking-widest text-[#022E6B]/40 mb-1.5"><?= $groupName ?></p>
                <div class="space-y-1">
                <?php foreach ($mods as $key => $label): ?>
                <label class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-[#F8FAFC] cursor-pointer group transition">
                    <input type="checkbox" name="permissions[]" value="<?= $key ?>"
                           class="perm-check w-3.5 h-3.5 accent-[#001644] cursor-pointer"
                           <?= in_array($key, $currentPerms) ? 'checked' : '' ?>>
                    <span class="text-[11px] text-[#001644] group-hover:text-[#BF8D1A] transition font-medium"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div><!-- end permissões card -->
    </div><!-- end sidebar direita -->
</form>

<script>
function toggleAllPerms(check) {
    document.querySelectorAll('.perm-check').forEach(c => c.checked = check);
}
// Show/hide permissions panel based on role select
const roleSelect = document.querySelector('select[name="role"]');
const permsCard  = document.querySelector('.card:last-of-type');
if (roleSelect) {
    roleSelect.addEventListener('change', function() {
        const isAdmin = this.value === 'admin';
        if (permsCard) permsCard.style.opacity = isAdmin ? '0.5' : '1';
    });
}
</script>

<?php else: // LIST
$users = dbFetchAll($pdo, "SELECT * FROM users ORDER BY created_at DESC");
$msgMap = ['saved'=>'Salvo!','deleted'=>'Excluído.','updated'=>'Atualizado!'];
?>
<?php if ($m = $msgMap[$_GET['msg'] ?? ''] ?? null): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-xs rounded-xl px-4 py-3 mb-5"><?= $m ?></div><?php endif; ?>
<div class="flex justify-end mb-5"><a href="/crcap/admin/users.php?action=new" class="btn-gold"><i class="fas fa-user-plus"></i>Novo Usuário</a></div>
<div class="card overflow-hidden">
    <table class="w-full">
        <thead><tr><th class="text-left">Usuário</th><th class="text-center hidden md:table-cell">Role</th><th class="text-center hidden lg:table-cell">Status</th><th class="text-center hidden lg:table-cell">Último Acesso</th><th class="text-center">Ações</th></tr></thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-xs font-bold flex-shrink-0"><?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?></div>
                        <div><p class="font-semibold text-[#001644] text-xs"><?= htmlspecialchars($u['full_name'] ?: $u['username']) ?></p><p class="text-[9px] text-[#022E6B]"><?= htmlspecialchars($u['email']) ?></p></div>
                    </div>
                </td>
                <td class="text-center hidden md:table-cell"><span class="badge badge-<?= $u['role']==='admin'?'red':($u['role']==='editor'?'gold':'blue') ?>"><?= $u['role'] ?></span></td>
                <td class="text-center hidden lg:table-cell"><span class="badge badge-<?= $u['status']==='active'?'green':($u['status']==='suspended'?'red':'gray') ?>"><?= $u['status'] ?></span></td>
                <td class="text-center hidden lg:table-cell text-xs text-[#022E6B]"><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Nunca' ?></td>
                <td class="text-center">
                    <div class="flex items-center justify-center gap-1">
                        <a href="/crcap/admin/users.php?action=edit&id=<?= $u['id'] ?>" class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs"><i class="fas fa-edit"></i></a>
                        <?php if ($u['id'] !== $adminUser['id']): ?>
                        <a href="/crcap/admin/users.php?action=toggle&id=<?= $u['id'] ?>" class="w-7 h-7 rounded-lg bg-orange-50 hover:bg-orange-500 hover:text-white text-orange-500 flex items-center justify-center transition text-xs" title="<?= $u['status']==='active'?'Suspender':'Ativar' ?>"><i class="fas fa-<?= $u['status']==='active'?'ban':'check' ?>"></i></a>
                        <a href="/crcap/admin/users.php?action=delete&id=<?= $u['id'] ?>" onclick="return confirm('Excluir este usuário?')" class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs"><i class="fas fa-trash"></i></a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; require_once __DIR__ . '/admin_footer.php'; ?>