<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (isLogged()) { header('Location: /crcap/usuario/perfil.php'); exit; }

$pageTitle  = 'Recuperar Senha · CRCAP';
$step       = $_GET['step'] ?? 'email'; // email | sent | reset | done
$msg        = '';
$errors     = [];

// ── Passo 1: enviar e-mail ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_request'])) {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $errors[] = 'Informe um e-mail válido.';
    } else {
        $user = dbFetch($pdo, "SELECT id, email, full_name FROM users WHERE email=? AND status='active'", [$email]);
        if ($user) {
            // Gerar token único
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
            // Armazenar na tabela settings (reutilizando; em prod usar tabela dedicada)
            $tokenKey = 'pwd_reset_'.$token;
            $tokenVal = json_encode(['user_id'=>$user['id'], 'email'=>$user['email'], 'expires'=>$expires]);
            $exists = dbFetch($pdo, "SELECT id FROM settings WHERE setting_key=?", [$tokenKey]);
            if ($exists) {
                dbExec($pdo, "UPDATE settings SET setting_value=? WHERE setting_key=?", [$tokenVal, $tokenKey]);
            } else {
                dbExec($pdo, "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?,?,'pwd_reset')", [$tokenKey, $tokenVal]);
            }
            // Em produção: enviar email com link. Aqui mostramos o link para demo.
            $resetLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].'/pages/recuperar-senha.php?step=reset&token='.$token;
            $_SESSION['reset_link_demo'] = $resetLink; // só para demonstração
        }
        // Sempre mostrar "sent" por segurança (não revelar se email existe)
        header('Location: /crcap/pages/recuperar-senha.php?step=sent');
        exit;
    }
}

// ── Passo 3: redefinir senha ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_reset'])) {
    $token   = trim($_POST['token'] ?? '');
    $pwd     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($pwd) < 8)         $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($pwd !== $confirm)        $errors[] = 'As senhas não coincidem.';

    if (empty($errors)) {
        $tokenKey = 'pwd_reset_'.$token;
        $row      = dbFetch($pdo, "SELECT setting_value FROM settings WHERE setting_key=?", [$tokenKey]);
        if (!$row) {
            $errors[] = 'Link inválido ou expirado.';
        } else {
            $data = json_decode($row['setting_value'], true);
            if (!$data || $data['expires'] < date('Y-m-d H:i:s')) {
                $errors[] = 'Link expirado. Solicite um novo.';
                dbExec($pdo, "DELETE FROM settings WHERE setting_key=?", [$tokenKey]);
            } else {
                dbExec($pdo, "UPDATE users SET password=? WHERE id=?",
                    [password_hash($pwd, PASSWORD_BCRYPT), $data['user_id']]);
                dbExec($pdo, "DELETE FROM settings WHERE setting_key=?", [$tokenKey]);
                header('Location: /crcap/pages/recuperar-senha.php?step=done');
                exit;
            }
        }
    }
}

include '../includes/header.php';
?>

<section class="min-h-screen bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] flex items-center py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto">

            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="/crcap/index.php" class="inline-flex items-center gap-3">
                    <div class="w-14 h-14 bg-gradient-to-br from-[#BF8D1A] to-[#022E6B] rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-xl">C</div>
                    <div class="text-left">
                        <p class="text-white font-black text-xl leading-none">CRCAP</p>
                        <p class="text-white/50 text-xs font-medium">Conselho Regional</p>
                    </div>
                </a>
            </div>

            <!-- ── E-MAIL ── -->
            <?php if ($step === 'email'): ?>
            <div class="bg-white rounded-3xl p-8 shadow-2xl shadow-[#001644]/40">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-[#BF8D1A]/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-key text-[#BF8D1A] text-2xl"></i>
                    </div>
                    <h1 class="text-xl font-bold text-[#001644]">Recuperar senha</h1>
                    <p class="text-xs text-[#022E6B] mt-1">Informe seu e-mail e enviaremos as instruções.</p>
                </div>

                <?php foreach($errors as $e): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-xs text-red-600 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i><?= h($e) ?>
                </div>
                <?php endforeach; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="form_request" value="1">
                    <div>
                        <label class="block text-xs font-semibold text-[#001644] mb-1.5">E-mail cadastrado</label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                            <input type="email" name="email" required placeholder="seu@email.com"
                                   class="w-full pl-9 pr-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 transition">
                        </div>
                    </div>
                    <button type="submit" class="w-full py-3.5 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] transition text-sm">
                        <i class="fas fa-paper-plane mr-2"></i>Enviar instruções
                    </button>
                </form>
                <div class="text-center mt-5">
                    <a href="/crcap/pages/login.php" class="text-xs text-[#BF8D1A] hover:underline font-semibold">
                        <i class="fas fa-arrow-left mr-1"></i>Voltar ao login
                    </a>
                </div>
            </div>

            <!-- ── ENVIADO ── -->
            <?php elseif ($step === 'sent'): ?>
            <div class="bg-white rounded-3xl p-8 shadow-2xl shadow-[#001644]/40 text-center">
                <div class="w-20 h-20 mx-auto mb-5 bg-[#006633]/10 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope-open-text text-[#006633] text-3xl"></i>
                </div>
                <h2 class="text-xl font-bold text-[#001644] mb-2">Verifique seu e-mail</h2>
                <p class="text-xs text-[#022E6B] leading-relaxed mb-6">
                    Se esse e-mail estiver cadastrado, você receberá um link de recuperação em instantes.
                    Verifique também a pasta de spam.
                </p>
                <?php if (!empty($_SESSION['reset_link_demo'])): ?>
                <div class="bg-[#F8FAFC] border border-[#001644]/10 rounded-xl p-4 mb-5 text-left">
                    <p class="text-[10px] text-[#BF8D1A] font-bold mb-1">🔧 Modo Demo — link de reset:</p>
                    <a href="<?= h($_SESSION['reset_link_demo']) ?>" class="text-[10px] text-[#001644] break-all hover:text-[#BF8D1A]">
                        <?= h($_SESSION['reset_link_demo']) ?>
                    </a>
                </div>
                <?php unset($_SESSION['reset_link_demo']); ?>
                <?php endif; ?>
                <a href="/crcap/pages/login.php" class="inline-flex items-center gap-2 px-6 py-3 bg-[#001644] text-white text-sm font-bold rounded-xl hover:bg-[#022E6B] transition">
                    <i class="fas fa-sign-in-alt"></i>Ir para o login
                </a>
            </div>

            <!-- ── REDEFINIR SENHA ── -->
            <?php elseif ($step === 'reset'): ?>
            <?php $token = $_GET['token'] ?? ''; ?>
            <div class="bg-white rounded-3xl p-8 shadow-2xl shadow-[#001644]/40">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-[#001644]/5 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-lock text-[#001644] text-2xl"></i>
                    </div>
                    <h1 class="text-xl font-bold text-[#001644]">Nova senha</h1>
                    <p class="text-xs text-[#022E6B] mt-1">Digite e confirme sua nova senha.</p>
                </div>

                <?php foreach($errors as $e): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-xs text-red-600 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i><?= h($e) ?>
                </div>
                <?php endforeach; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="form_reset" value="1">
                    <input type="hidden" name="token" value="<?= h($token) ?>">
                    <div>
                        <label class="block text-xs font-semibold text-[#001644] mb-1.5">Nova senha</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                            <input type="password" name="new_password" id="newPwd" required minlength="8"
                                   placeholder="Mínimo 8 caracteres"
                                   class="w-full pl-9 pr-10 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] transition">
                            <button type="button" onclick="togglePwd('newPwd','eye1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#001644]/30 hover:text-[#001644] transition">
                                <i id="eye1" class="fas fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#001644] mb-1.5">Confirmar nova senha</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                            <input type="password" name="confirm_password" required
                                   placeholder="Repita a senha"
                                   class="w-full pl-9 pr-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] transition">
                        </div>
                    </div>
                    <button type="submit" class="w-full py-3.5 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] transition text-sm">
                        <i class="fas fa-check mr-2"></i>Redefinir senha
                    </button>
                </form>
            </div>

            <!-- ── CONCLUÍDO ── -->
            <?php elseif ($step === 'done'): ?>
            <div class="bg-white rounded-3xl p-8 shadow-2xl shadow-[#001644]/40 text-center">
                <div class="w-20 h-20 mx-auto mb-5 bg-[#006633]/10 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-[#006633] text-4xl"></i>
                </div>
                <h2 class="text-xl font-bold text-[#001644] mb-2">Senha redefinida!</h2>
                <p class="text-xs text-[#022E6B] mb-6">Sua senha foi atualizada com sucesso. Faça login com a nova senha.</p>
                <a href="/crcap/pages/login.php" class="inline-flex items-center gap-2 px-6 py-3 bg-[#001644] text-white text-sm font-bold rounded-xl hover:bg-[#022E6B] transition">
                    <i class="fas fa-sign-in-alt"></i>Fazer login
                </a>
            </div>
            <?php endif; ?>

            <p class="text-center text-white/30 text-xs mt-6">&copy; <?= date('Y') ?> CRCAP</p>
        </div>
    </div>
</section>

<script>
function togglePwd(id, iconId) {
    const inp = document.getElementById(id);
    const ico = document.getElementById(iconId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.className = inp.type === 'text' ? 'fas fa-eye-slash text-xs' : 'fas fa-eye text-xs';
}
</script>

<?php include '../includes/footer.php'; ?>
