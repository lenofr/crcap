<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (isLogged()) { header('Location: /crcap/usuario/perfil.php'); exit; }

$pageTitle  = 'Login · CRCAP';
$activeMenu = '';
$error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) { http_response_code(403); exit('CSRF token inválido.'); }
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($login && $pass) {
        $user = dbFetch($pdo,
            "SELECT * FROM users WHERE (username=? OR email=?) AND status='active' LIMIT 1",
            [$login, $login]);
        if ($user && password_verify($pass, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['avatar']    = $user['avatar'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['phone']     = $user['phone'] ?? '';
            dbExec($pdo, "UPDATE users SET last_login=NOW() WHERE id=?", [$user['id']]);
            $redirect = $user['role'] === 'admin' || $user['role'] === 'editor' ? '/admin/index.php' : '/usuario/perfil.php';
            header("Location: $redirect"); exit;
        } else {
            $error = 'Usuário/e-mail ou senha inválidos.';
        }
    } else {
        $error = 'Preencha todos os campos.';
    }
}

include '../includes/header.php';
?>

<main class="min-h-[70vh] flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl flex items-center justify-center text-white font-bold text-4xl shadow-2xl shadow-[#001644]/30 mx-auto mb-4">C</div>
            <h1 class="text-2xl font-bold text-[#001644]">Acesse sua conta</h1>
            <p class="text-sm text-[#022E6B] mt-1">Área do Profissional CRCAP</p>
        </div>

        <div class="bg-white rounded-2xl p-8 shadow-xl shadow-[#001644]/5 border border-[#001644]/5">
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-6 flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?= csrfField() ?>
                <div>
                    <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-2">Usuário ou E-mail</label>
                    <div class="relative">
                        <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-[#001644]/30 text-sm"></i>
                        <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required autofocus
                            class="w-full pl-11 pr-4 py-3.5 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 bg-[#F8FAFC] transition"
                            placeholder="seu@email.com">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[10px] font-bold text-[#022E6B] uppercase tracking-wider">Senha</label>
                        <a href="#" class="text-[10px] text-[#BF8D1A] hover:underline">Esqueci minha senha</a>
                    </div>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-[#001644]/30 text-sm"></i>
                        <input type="password" name="password" id="passwordInput" required
                            class="w-full pl-11 pr-11 py-3.5 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 bg-[#F8FAFC] transition"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePwd()" class="absolute right-4 top-1/2 -translate-y-1/2 text-[#001644]/30 hover:text-[#001644] transition">
                            <i class="fas fa-eye text-sm" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-[#001644]/20">
                        <span class="text-xs text-[#022E6B]">Lembrar de mim</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-4 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] hover:shadow-lg transition flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-sign-in-alt"></i>Entrar
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-[#001644]/10 text-center">
                <p class="text-xs text-[#022E6B]">Ainda não tem conta?
                    <a href="/crcap/pages/registro.php" class="text-[#BF8D1A] font-semibold hover:underline">Cadastre-se</a>
                </p>
                <div class="mt-4 flex items-center gap-2">
                    <div class="flex-1 h-px bg-[#001644]/10"></div>
                    <span class="text-[10px] text-[#022E6B]">ou acesse com</span>
                    <div class="flex-1 h-px bg-[#001644]/10"></div>
                </div>
                <div class="flex justify-center gap-3 mt-4">
                    <a href="#" class="flex items-center gap-2 px-4 py-2.5 border border-[#001644]/10 rounded-xl text-xs text-[#022E6B] hover:border-[#BF8D1A] hover:text-[#001644] transition">
                        <img src="https://www.google.com/favicon.ico" class="w-4 h-4"> Google
                    </a>
                    <a href="#" class="flex items-center gap-2 px-4 py-2.5 border border-[#001644]/10 rounded-xl text-xs text-[#022E6B] hover:border-[#BF8D1A] hover:text-[#001644] transition">
                        <i class="fab fa-facebook-f text-[#1877F2]"></i> Facebook
                    </a>
                </div>
            </div>
        </div>

        <p class="text-center text-[10px] text-[#022E6B]/60 mt-6">
            Ao entrar, você concorda com os <a href="/crcap/pages/termos.php" class="hover:text-[#BF8D1A] transition">Termos de Uso</a> e a <a href="/crcap/pages/privacidade.php" class="hover:text-[#BF8D1A] transition">Política de Privacidade</a>.
        </p>
    </div>
</main>

<script>
function togglePwd() {
    const i = document.getElementById('passwordInput');
    const e = document.getElementById('eyeIcon');
    i.type = i.type === 'password' ? 'text' : 'password';
    e.className = i.type === 'password' ? 'fas fa-eye text-sm' : 'fas fa-eye-slash text-sm';
}
</script>

<?php include '../includes/footer.php'; ?>
