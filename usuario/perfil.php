<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

// Garante que a coluna reply_message existe
try { $pdo->exec("ALTER TABLE contacts ADD COLUMN reply_message TEXT NULL AFTER replied_by"); } catch (Exception $e) {}

$user   = currentUser();
$pageTitle = 'Meu Perfil · CRCAP';
$activeMenu = '';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_perfil'])) {
    if (!csrfVerify()) { http_response_code(403); exit('Token de segurança inválido. Recarregue a página.'); }

    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $avatar    = $user['avatar'] ?? '';

    // Handle avatar file upload
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            $fname = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
            $dir   = dirname(__DIR__) . '/uploads/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dir . $fname)) {
                // Delete old avatar if exists and is local
                if ($user['avatar'] && str_starts_with($user['avatar'], '/uploads/')) {
                    @unlink(dirname(__DIR__) . $user['avatar']);
                }
                $avatar = '/uploads/avatars/' . $fname;
            }
        }
    } elseif (isset($_POST['avatar'])) {
        $avatar = trim($_POST['avatar']);
    }

    dbExec($pdo, "UPDATE users SET full_name=?, phone=?, avatar=? WHERE id=?",
        [$full_name, $phone, $avatar, $user['id']]);
    $_SESSION['full_name'] = $full_name;
    $_SESSION['avatar']    = $avatar;
    $user = array_merge($user, ['full_name'=>$full_name,'phone'=>$phone,'avatar'=>$avatar]);
    $msg = 'updated';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_senha'])) {
    if (!csrfVerify()) { http_response_code(403); exit('Token de segurança inválido. Recarregue a página.'); }

    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    if (!password_verify($current, $user['password'])) { $msg = 'wrong_password'; }
    elseif ($new !== $confirm) { $msg = 'password_mismatch'; }
    elseif (strlen($new) < 6)  { $msg = 'password_short'; }
    else {
        dbExec($pdo, "UPDATE users SET password=? WHERE id=?", [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        $msg = 'password_updated';
    }
}

// Inscrições do usuário
$inscricoes = dbFetchAll($pdo,
    "SELECT er.*, e.title AS event_title, e.event_date, e.location FROM event_registrations er
     JOIN events e ON er.event_id=e.id WHERE er.email=? ORDER BY er.registered_at DESC LIMIT 10",
    [$user['email']]);

// Mensagens de contato enviadas pelo usuário (vincula por email)
$userContacts = dbFetchAll($pdo,
    "SELECT id, subject, message, status, reply_message, replied_at, created_at
     FROM contacts WHERE email=? ORDER BY created_at DESC LIMIT 20",
    [$user['email']]);

include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] py-10 text-white">
    <div class="container mx-auto px-4">
        <div class="flex items-center gap-5">
            <div class="w-20 h-20 rounded-2xl bg-[#BF8D1A] flex items-center justify-center text-white text-3xl font-bold shadow-xl flex-shrink-0">
                <?php if ($user['avatar']): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" class="w-full h-full rounded-2xl object-cover">
                <?php else: ?>
                <?= strtoupper(substr($user['full_name'] ?: $user['username'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="text-2xl font-bold"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h1>
                <p class="text-white/70 text-sm"><?= htmlspecialchars($user['email']) ?></p>
                <span class="inline-block mt-1 px-3 py-0.5 bg-[#BF8D1A] text-white text-[10px] font-bold uppercase rounded-full"><?= $user['role'] ?></span>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-10">
    <div class="grid lg:grid-cols-4 gap-8">

        <!-- Sidebar Menu -->
        <aside>
            <div class="bg-white rounded-2xl p-4 border border-[#001644]/3 shadow-sm">
                <nav class="space-y-1">
                    <?php $userNav = [
                        ['#perfil','fa-user','Meu Perfil'],
                        ['#inscricoes','fa-calendar-check','Minhas Inscrições'],
                        ['#senha','fa-lock','Alterar Senha'],
                        ['#mensagens','fa-inbox','Minhas Mensagens'],
                        ['#newsletter','fa-envelope','Newsletter'],
                    ]; foreach ($userNav as $n): ?>
                    <a href="<?= $n[0] ?>" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-xs text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition font-medium">
                        <i class="fas <?= $n[1] ?> text-[#BF8D1A] w-4 text-center"></i><?= $n[2] ?>
                    </a>
                    <?php endforeach; ?>
                    <div class="pt-2 border-t border-[#001644]/10 mt-2">
                        <a href="/crcap/pages/login.php?action=logout" class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-xs text-red-500 hover:bg-red-50 transition font-medium">
                            <i class="fas fa-sign-out-alt w-4 text-center"></i>Sair
                        </a>
                    </div>
                </nav>
            </div>
            <?php if ($user['role'] === 'admin' || $user['role'] === 'editor'): ?>
            <a href="/crcap/admin/index.php" class="flex items-center justify-center gap-2 mt-3 py-3 bg-[#001644] text-white text-xs font-bold rounded-xl hover:bg-[#022E6B] transition">
                <i class="fas fa-cog"></i>Painel Admin
            </a>
            <?php endif; ?>
        </aside>

        <!-- Conteúdo -->
        <div class="lg:col-span-3 space-y-8">

            <!-- Alertas -->
            <?php if ($msg === 'updated'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3">Perfil atualizado com sucesso!</div><?php endif; ?>
            <?php if ($msg === 'password_updated'): ?><div class="bg-[#006633]/10 border border-[#006633]/30 text-[#006633] text-xs rounded-xl px-4 py-3">Senha alterada com sucesso!</div><?php endif; ?>
            <?php if ($msg === 'wrong_password'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3">Senha atual incorreta.</div><?php endif; ?>
            <?php if ($msg === 'password_mismatch'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3">As novas senhas não coincidem.</div><?php endif; ?>
            <?php if ($msg === 'password_short'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3">A nova senha deve ter no mínimo 6 caracteres.</div><?php endif; ?>

            <!-- Perfil -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm" id="perfil">
                <h2 class="font-bold text-[#001644] text-lg mb-6 flex items-center gap-2"><i class="fas fa-user text-[#BF8D1A]"></i>Informações do Perfil</h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_perfil" value="1">

                    <!-- Welcome message for new users -->
                    <?php if (isset($_GET['welcome'])): ?>
                    <div class="bg-[#006633]/10 border border-[#006633]/30 rounded-xl p-4 flex items-center gap-3">
                        <i class="fas fa-party-horn text-[#006633] text-lg"></i>
                        <div>
                            <p class="font-bold text-[#001644] text-sm">Bem-vindo ao CRCAP!</p>
                            <p class="text-xs text-[#022E6B]">Sua conta foi criada com sucesso. Complete seu perfil abaixo.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Nome Completo</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']??'') ?>"
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Usuário</label>
                            <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm bg-[#F0F4F8] text-[#022E6B]/60">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">E-mail</label>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm bg-[#F0F4F8] text-[#022E6B]/60">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Telefone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']??'') ?>"
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="(96) 9xxxx-xxxx">
                        </div>
                    </div>
                    <!-- Avatar upload -->
                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Foto de Perfil</label>
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-[#001644] flex items-center justify-center text-white text-2xl font-bold flex-shrink-0 overflow-hidden" id="avatarPreviewWrap">
                                <?php if ($user['avatar']): ?>
                                <img src="<?= h($user['avatar']) ?>" id="avatarImg" class="w-full h-full object-cover">
                                <?php else: ?>
                                <span id="avatarInitial"><?= strtoupper(substr($user['full_name']?:$user['username'],0,1)) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <label for="avatarFile" class="flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-[#001644]/15 rounded-xl text-xs font-semibold text-[#022E6B] cursor-pointer hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition">
                                    <i class="fas fa-camera text-[#BF8D1A]"></i>Selecionar foto
                                </label>
                                <input type="file" id="avatarFile" name="avatar_file" accept="image/*" class="hidden"
                                       onchange="previewAvatar(this)">
                                <p class="text-[10px] text-[#022E6B]/50 mt-1">JPG, PNG, WebP — máx 5MB</p>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="px-8 py-3 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] transition text-sm flex items-center gap-2"><i class="fas fa-save"></i>Salvar alterações</button>
                </form>
            </div>

            <!-- Inscrições -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm" id="inscricoes">
                <h2 class="font-bold text-[#001644] text-lg mb-6 flex items-center gap-2"><i class="fas fa-calendar-check text-[#BF8D1A]"></i>Minhas Inscrições em Eventos</h2>
                <?php if (empty($inscricoes)): ?>
                <div class="text-center py-8 text-[#001644]/30">
                    <i class="fas fa-calendar-times text-3xl mb-3 block"></i>
                    <p class="text-sm">Você ainda não se inscreveu em nenhum evento.</p>
                    <a href="/crcap/pages/eventos.php" class="inline-block mt-3 px-6 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#022E6B] transition">Ver eventos disponíveis</a>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-[#001644] text-white">
                            <tr><th class="text-left px-4 py-2.5">Evento</th><th class="text-center px-4 py-2.5">Data</th><th class="text-center px-4 py-2.5">Status</th><th class="text-center px-4 py-2.5">Código</th></tr>
                        </thead>
                        <tbody class="divide-y divide-[#001644]/5">
                            <?php foreach ($inscricoes as $insc): ?>
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-4 py-3 font-semibold text-[#001644]"><?= htmlspecialchars($insc['event_title']) ?></td>
                                <td class="px-4 py-3 text-center text-[#022E6B]"><?= date('d/m/Y', strtotime($insc['event_date'])) ?></td>
                                <td class="px-4 py-3 text-center"><span class="badge <?= $insc['status']==='confirmed'?'badge-green':($insc['status']==='attended'?'badge-blue':'badge-gold') ?>"><?= $insc['status'] ?></span></td>
                                <td class="px-4 py-3 text-center font-mono text-[#001644]"><?= htmlspecialchars($insc['confirmation_code']??'—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Alterar Senha -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm" id="senha">
                <h2 class="font-bold text-[#001644] text-lg mb-6 flex items-center gap-2"><i class="fas fa-lock text-[#BF8D1A]"></i>Alterar Senha</h2>
                <form method="POST" class="space-y-4 max-w-md">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_senha" value="1">
                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Senha Atual</label>
                        <input type="password" name="current_password" required class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Nova Senha</label>
                        <input type="password" name="new_password" required class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Confirmar Nova Senha</label>
                        <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]">
                    </div>
                    <button type="submit" class="px-8 py-3 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] transition text-sm flex items-center gap-2"><i class="fas fa-key"></i>Alterar senha</button>
                </form>
            </div>

            <!-- Minhas Mensagens / Contatos -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm" id="mensagens">
                <h2 class="font-bold text-[#001644] text-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-inbox text-[#BF8D1A]"></i>Minhas Mensagens
                    <?php if (!empty($userContacts)): ?>
                    <span class="ml-auto text-[10px] font-normal text-[#022E6B]/50"><?= count($userContacts) ?> mensagem(ns)</span>
                    <?php endif; ?>
                </h2>

                <?php if (empty($userContacts)): ?>
                <div class="text-center py-8 text-[#001644]/30">
                    <i class="fas fa-inbox text-3xl mb-3 block"></i>
                    <p class="text-sm">Você ainda não enviou nenhuma mensagem.</p>
                    <a href="/crcap/pages/contato.php" class="inline-block mt-3 px-6 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#022E6B] transition">
                        <i class="fas fa-paper-plane mr-1"></i>Enviar mensagem
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($userContacts as $msg): ?>
                    <div class="border border-[#001644]/8 rounded-xl overflow-hidden"
                         data-msg-id="<?= (int)$msg['id'] ?>"
                         data-msg-status="<?= h($msg['status']) ?>">
                        <!-- Cabeçalho da mensagem -->
                        <div class="flex items-center justify-between px-4 py-3 bg-[#F8FAFC] border-b border-[#001644]/5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs
                                    <?= $msg['status']==='replied' ? 'bg-[#006633]' : ($msg['status']==='read' ? 'bg-[#022E6B]' : 'bg-[#BF8D1A]') ?>">
                                    <i class="fas <?= $msg['status']==='replied' ? 'fa-check-double' : ($msg['status']==='read' ? 'fa-envelope-open' : 'fa-envelope') ?>"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#001644]"><?= htmlspecialchars($msg['subject'] ?: 'Mensagem sem assunto') ?></p>
                                    <p class="text-[10px] text-[#022E6B]/50"><?= date('d/m/Y \à\s H:i', strtotime($msg['created_at'])) ?></p>
                                </div>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold
                                <?= $msg['status']==='replied' ? 'bg-[#006633]/10 text-[#006633]' : ($msg['status']==='read' ? 'bg-[#022E6B]/10 text-[#022E6B]' : 'bg-[#BF8D1A]/10 text-[#BF8D1A]') ?>">
                                <?= $msg['status']==='replied' ? 'Respondido' : ($msg['status']==='read' ? 'Lido' : ($msg['status']==='new' ? 'Enviado' : ucfirst($msg['status']))) ?>
                            </span>
                        </div>

                        <!-- Corpo da mensagem -->
                        <div class="px-4 py-3">
                            <p class="text-xs text-[#022E6B] leading-relaxed"><?= nl2br(htmlspecialchars(mb_substr($msg['message'], 0, 300))) ?><?= mb_strlen($msg['message']) > 300 ? '…' : '' ?></p>
                        </div>

                        <!-- Resposta do CRCAP (se houver) -->
                        <?php if (!empty($msg['reply_message'])): ?>
                        <div class="px-4 py-3 bg-[#006633]/5 border-t border-[#006633]/10">
                            <p class="text-[10px] font-bold text-[#006633] mb-1.5 flex items-center gap-1">
                                <i class="fas fa-reply"></i> Resposta do CRCAP
                                <span class="font-normal text-[#022E6B]/50 ml-1"><?= date('d/m/Y \à\s H:i', strtotime($msg['replied_at'])) ?></span>
                            </p>
                            <p class="text-xs text-[#022E6B] leading-relaxed"><?= nl2br(htmlspecialchars($msg['reply_message'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-5 text-center">
                    <a href="/crcap/pages/contato.php" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#022E6B] transition">
                        <i class="fas fa-paper-plane"></i>Enviar nova mensagem
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Newsletter -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm" id="newsletter">
                <h2 class="font-bold text-[#001644] text-lg mb-4 flex items-center gap-2"><i class="fas fa-envelope text-[#BF8D1A]"></i>Preferências de Newsletter</h2>
                <?php $sub = dbFetch($pdo,"SELECT * FROM newsletters WHERE email=?",[$user['email']]); ?>
                <div class="bg-[#F8FAFC] rounded-xl p-5 border border-[#001644]/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-[#001644] text-sm"><?= htmlspecialchars($user['email']) ?></p>
                            <p class="text-xs text-[#022E6B] mt-0.5">Status: <span class="font-semibold <?= ($sub&&$sub['status']==='subscribed')?'text-[#006633]':'text-red-500' ?>"><?= $sub ? ucfirst($sub['status']) : 'Não inscrito' ?></span></p>
                        </div>
                        <?php if ($sub && $sub['status'] === 'subscribed'): ?>
                        <a href="/crcap/pages/newsletter-action.php?action=unsubscribe&email=<?= urlencode($user['email']) ?>" class="px-4 py-2 bg-red-50 text-red-600 border border-red-200 rounded-xl text-xs font-semibold hover:bg-red-100 transition">Cancelar inscrição</a>
                        <?php else: ?>
                        <a href="/crcap/pages/newsletter-action.php?action=subscribe&email=<?= urlencode($user['email']) ?>" class="px-4 py-2 bg-[#006633] text-white rounded-xl text-xs font-semibold hover:bg-[#001644] transition">Inscrever-se</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const wrap = document.getElementById('avatarPreviewWrap');
            wrap.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<!-- ── SSE Notificação de Mensagens / Inscrições ─────────────────── -->
<style>
/* Janela de notificação */
#sse-notif {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  width: 320px;
  background: #fff;
  border-radius: 1rem;
  box-shadow: 0 20px 60px rgba(0,22,68,.18), 0 4px 16px rgba(0,22,68,.10);
  border: 1px solid rgba(0,22,68,.08);
  overflow: hidden;
  transform: translateY(120%);
  opacity: 0;
  transition: transform .4s cubic-bezier(.34,1.56,.64,1), opacity .3s ease;
  pointer-events: none;
}
#sse-notif.show {
  transform: translateY(0);
  opacity: 1;
  pointer-events: auto;
}

/* Faixa colorida no topo */
#sse-notif-bar {
  height: 4px;
  background: linear-gradient(90deg, #001644, #BF8D1A);
}

#sse-notif-body {
  padding: 1rem 1rem .85rem;
  display: flex;
  gap: .75rem;
  align-items: flex-start;
}

#sse-notif-icon {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: .65rem;
  background: #006633;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: .9rem;
  flex-shrink: 0;
}

#sse-notif-text { flex: 1; min-width: 0; }
#sse-notif-title {
  font-size: .72rem;
  font-weight: 800;
  color: #001644;
  margin-bottom: .2rem;
}
#sse-notif-msg {
  font-size: .68rem;
  color: #022E6B;
  line-height: 1.5;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

#sse-notif-actions {
  display: flex;
  gap: .5rem;
  padding: 0 1rem .85rem;
}
#sse-notif-btn {
  flex: 1;
  padding: .45rem;
  background: #001644;
  color: #fff;
  border: none;
  border-radius: .5rem;
  font-size: .68rem;
  font-weight: 700;
  cursor: pointer;
  text-align: center;
  text-decoration: none;
  display: block;
  transition: background .2s;
}
#sse-notif-btn:hover { background: #BF8D1A; color: #fff; }
#sse-notif-close {
  padding: .45rem .75rem;
  background: #F8FAFC;
  border: 1px solid rgba(0,22,68,.08);
  border-radius: .5rem;
  font-size: .68rem;
  font-weight: 600;
  color: #022E6B;
  cursor: pointer;
  transition: background .2s;
}
#sse-notif-close:hover { background: #e8edf4; }

/* Ponto pulsante no ícone quando nova mensagem */
@keyframes sse-pulse {
  0%,100% { box-shadow: 0 0 0 0 rgba(0,102,51,.4); }
  50%      { box-shadow: 0 0 0 6px rgba(0,102,51,0); }
}
#sse-notif-icon.pulse { animation: sse-pulse 1.5s ease infinite; }
</style>

<!-- Janela de notificação -->
<div id="sse-notif">
  <div id="sse-notif-bar"></div>
  <div id="sse-notif-body">
    <div id="sse-notif-icon"><i id="sse-notif-icon-i" class="fas fa-reply"></i></div>
    <div id="sse-notif-text">
      <div id="sse-notif-title">Nova resposta do CRCAP</div>
      <div id="sse-notif-msg">Sua mensagem foi respondida.</div>
    </div>
  </div>
  <div id="sse-notif-actions">
    <a id="sse-notif-btn" href="#mensagens">Ver resposta</a>
    <button id="sse-notif-close" onclick="fecharNotif()">Fechar</button>
  </div>
</div>

<script>
(function () {
  // Estado local: guarda status de cada msg/inscrição como estava no carregamento
  var estadoMsgs = {};
  var estadoRegs = {};

  // Lê estado inicial do DOM
  document.querySelectorAll('[data-msg-id]').forEach(function(el) {
    estadoMsgs[el.dataset.msgId] = el.dataset.msgStatus;
  });

  var notifEl    = document.getElementById('sse-notif');
  var notifIcon  = document.getElementById('sse-notif-icon');
  var notifIconI = document.getElementById('sse-notif-icon-i');
  var notifBar   = document.getElementById('sse-notif-bar');
  var notifTitle = document.getElementById('sse-notif-title');
  var notifMsg   = document.getElementById('sse-notif-msg');
  var notifBtn   = document.getElementById('sse-notif-btn');
  var notifTimer = null;

  window.fecharNotif = function() {
    notifEl.classList.remove('show');
    notifIcon.classList.remove('pulse');
  };

  function mostrarNotif(tipo, titulo, msg, href, corIcon, corBar) {
    notifBar.style.background   = corBar  || 'linear-gradient(90deg,#001644,#BF8D1A)';
    notifIcon.style.background  = corIcon || '#006633';
    notifIconI.className = tipo === 'inscricao'
      ? 'fas fa-calendar-check'
      : 'fas fa-reply';
    notifTitle.textContent = titulo;
    notifMsg.textContent   = msg;
    notifBtn.href          = href || '#mensagens';
    notifBtn.textContent   = tipo === 'inscricao' ? 'Ver inscrição' : 'Ver resposta';

    notifIcon.classList.add('pulse');
    notifEl.classList.add('show');

    // Fecha sozinho após 12s
    clearTimeout(notifTimer);
    notifTimer = setTimeout(window.fecharNotif, 12000);

    // Toca som suave se permitido
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain); gain.connect(ctx.destination);
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0, ctx.currentTime);
      gain.gain.linearRampToValueAtTime(.08, ctx.currentTime + .05);
      gain.gain.linearRampToValueAtTime(0,   ctx.currentTime + .4);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + .4);
    } catch(e) {}
  }

  // ── Atualiza o badge de status da mensagem no DOM ──
  function atualizarBadgeMsg(el, status) {
    var badge = el.querySelector('span.rounded-full');
    if (!badge) return;
    var labels = { replied:'Respondido', read:'Lido', new:'Enviado', archived:'Arquivado' };
    var cores   = {
      replied: 'bg-[#006633]/10 text-[#006633]',
      read:    'bg-[#022E6B]/10 text-[#022E6B]',
      new:     'bg-[#BF8D1A]/10 text-[#BF8D1A]',
      archived:'bg-gray-100 text-gray-500',
    };
    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold ' + (cores[status] || cores['new']);
    badge.textContent = labels[status] || status;

    // Atualiza ícone do avatar de status
    var iconDiv = el.querySelector('.w-8.h-8');
    if (iconDiv) {
      var iconTag = iconDiv.querySelector('i');
      if (status === 'replied') {
        iconDiv.className = iconDiv.className.replace(/bg-\[#[^\]]+\]/g, 'bg-[#006633]');
        if (iconTag) iconTag.className = 'fas fa-check-double';
      } else if (status === 'read') {
        iconDiv.className = iconDiv.className.replace(/bg-\[#[^\]]+\]/g, 'bg-[#022E6B]');
        if (iconTag) iconTag.className = 'fas fa-envelope-open';
      }
    }
  }

  // ── Injeta bloco de resposta no card da mensagem ──
  function injetarResposta(el, replyMsg, repliedAt) {
    if (!replyMsg) return;
    if (el.querySelector('.reply-block')) return; // já existe

    var data = repliedAt
      ? new Date(repliedAt).toLocaleDateString('pt-BR') + ' às ' + new Date(repliedAt).toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})
      : '';

    var html = '<div class="reply-block px-4 py-3 bg-[#006633]/5 border-t border-[#006633]/10" style="animation:sse-fadeIn .5s ease">'
      + '<p class="text-[10px] font-bold text-[#006633] mb-1.5 flex items-center gap-1">'
      + '<i class="fas fa-reply"></i> Resposta do CRCAP'
      + (data ? '<span class="font-normal text-[#022E6B]/50 ml-1">' + data + '</span>' : '')
      + '</p>'
      + '<p class="text-xs text-[#022E6B] leading-relaxed">' + replyMsg.replace(/\n/g,'<br>') + '</p>'
      + '</div>';

    el.insertAdjacentHTML('beforeend', html);
  }

  // ── Labels de status de inscrição ──
  var regLabels = {
    confirmed: 'Confirmada',
    attended:  'Presente',
    cancelled: 'Cancelada',
    pending:   'Pendente',
  };

  function conectar() {
    var es = new EventSource('/crcap/usuario/sse-usuario.php');

    // ── Evento: sync de mensagens ──
    es.addEventListener('sync_msgs', function(e) {
      try {
        var d = JSON.parse(e.data);
        (d.mensagens || []).forEach(function(m) {
          var id     = String(m.id);
          var status = m.status;
          var prev   = estadoMsgs[id];

          // Detecta mudança de status
          if (prev !== undefined && prev !== status) {
            estadoMsgs[id] = status;
            var el = document.querySelector('[data-msg-id="' + id + '"]');
            if (el) {
              el.dataset.msgStatus = status;
              atualizarBadgeMsg(el, status);
            }

            // Notifica se foi respondida
            if (status === 'replied') {
              var assunto = el ? (el.querySelector('.text-xs.font-semibold')?.textContent?.trim() || '') : '';
              var trecho  = m.reply_message ? m.reply_message.substring(0, 80) + (m.reply_message.length > 80 ? '…' : '') : 'Sua mensagem foi respondida pelo CRCAP.';

              if (el) injetarResposta(el, m.reply_message, m.replied_at);

              mostrarNotif(
                'mensagem',
                '📬 Nova resposta do CRCAP!',
                assunto ? '"' + assunto + '": ' + trecho : trecho,
                '#mensagens',
                '#006633',
                'linear-gradient(90deg,#006633,#004d26)'
              );
            }
          }
        });
      } catch(err) { console.warn('[SSE usuario] sync_msgs error', err); }
    });

    // ── Evento: sync de inscrições ──
    es.addEventListener('sync_regs', function(e) {
      try {
        var d = JSON.parse(e.data);
        (d.inscricoes || []).forEach(function(r) {
          var id     = String(r.id);
          var status = r.status;
          var prev   = estadoRegs[id];

          if (prev === undefined) { estadoRegs[id] = status; return; }

          if (prev !== status) {
            estadoRegs[id] = status;

            if (status === 'confirmed') {
              mostrarNotif(
                'inscricao',
                '✅ Inscrição confirmada!',
                'Sua inscrição em um evento foi confirmada.',
                '#inscricoes',
                '#BF8D1A',
                'linear-gradient(90deg,#001644,#BF8D1A)'
              );
            } else if (status === 'cancelled') {
              mostrarNotif(
                'inscricao',
                '❌ Inscrição cancelada',
                'Uma inscrição sua foi cancelada pelo CRCAP.',
                '#inscricoes',
                '#DC2626',
                'linear-gradient(90deg,#DC2626,#b91c1c)'
              );
            }
          }
        });
      } catch(err) { console.warn('[SSE usuario] sync_regs error', err); }
    });

    es.addEventListener('reconectar', function() { es.close(); conectar(); });
    es.onerror = function() { es.close(); setTimeout(conectar, 30000); };
  }

  conectar();
})();
</script>

<?php include '../includes/footer.php'; ?>