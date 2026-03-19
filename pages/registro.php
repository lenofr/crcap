<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

if (isLogged()) { header('Location: /crcap/usuario/perfil.php'); exit; }

$pageTitle  = 'Criar Conta · CRCAP';
$activeMenu = '';
$errors     = [];
$success    = false;

// ── Categorias de perfil (sincronizadas com newsletter) ───────────────────────
$categorias = [
    'Contador'                  => 'Contador(a)',
    'Técnico em Contabilidade'  => 'Técnico(a) em Contabilidade',
    'Estudante'                 => 'Estudante de Ciências Contábeis',
    'Outro'                     => 'Outro',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $errors[] = 'Token de segurança inválido. Recarregue a página.';
    } else {
        $nome      = trim($_POST['full_name']         ?? '');
        $email     = trim($_POST['email']             ?? '');
        $username  = trim($_POST['username']          ?? '');
        $phone     = trim($_POST['phone']             ?? '');
        $categoria = trim($_POST['categoria']         ?? '');
        $pwd       = $_POST['password']               ?? '';
        $pwdConf   = $_POST['password_confirm']       ?? '';
        $terms     = isset($_POST['terms']);
        $nlOptIn   = isset($_POST['newsletter_optin']); // checkbox newsletter

        // ── Validações ────────────────────────────────────────────────────────
        if (!$nome)                                             $errors[] = 'Nome completo é obrigatório.';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
        if (!$username || strlen($username) < 4)               $errors[] = 'Nome de usuário deve ter pelo menos 4 caracteres.';
        if (!preg_match('/^[a-z0-9_]+$/i', $username))         $errors[] = 'Nome de usuário: apenas letras, números e _.';
        if (strlen($pwd) < 8)                                  $errors[] = 'Senha deve ter pelo menos 8 caracteres.';
        if ($pwd !== $pwdConf)                                 $errors[] = 'As senhas não coincidem.';
        if (!$terms)                                           $errors[] = 'Você deve aceitar os Termos de Uso.';

        // Categoria válida?
        if ($categoria && !array_key_exists($categoria, $categorias)) {
            $categoria = '';
        }

        if (empty($errors)) {
            $emailExists    = dbFetch($pdo, "SELECT id FROM users WHERE email=?",    [$email]);
            $usernameExists = dbFetch($pdo, "SELECT id FROM users WHERE username=?", [$username]);
            if ($emailExists)    $errors[] = 'Este e-mail já está cadastrado.';
            if ($usernameExists) $errors[] = 'Este nome de usuário já está em uso.';
        }

        if (empty($errors)) {
            // ── 1. Cria o usuário ─────────────────────────────────────────────
            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            dbExec($pdo,
                "INSERT INTO users (username, email, password, full_name, phone, role, status)
                 VALUES (?,?,?,?,?,'viewer','active')",
                [$username, $email, $hash, $nome, $phone ?: null]
            );
            $userId = $pdo->lastInsertId();

            // ── 2. Vincula / atualiza newsletter ──────────────────────────────
            // Verifica se o e-mail já existe na newsletter
            $nlExisting = dbFetch($pdo, "SELECT id, status FROM newsletters WHERE email=?", [$email]);
            $nlAction   = null; // 'new' | 'reactivated' | 'updated' | null

            if ($nlOptIn) {
                // Usuário optou por receber newsletter
                $primeiroNome = explode(' ', $nome)[0]; // pega primeiro nome para o e-mail

                if (!$nlExisting) {
                    // Novo inscrito
                    dbExec($pdo,
                        "INSERT INTO newsletters
                            (email, name, full_name, categoria, status, confirmed, subscription_ip, subscription_source)
                         VALUES (?,?,?,?,'subscribed',1,?,'registro')",
                        [$email, $primeiroNome, $nome, $categoria ?: null, $_SERVER['REMOTE_ADDR'] ?? '']
                    );
                    $nlAction = 'new';
                } elseif ($nlExisting['status'] !== 'subscribed') {
                    // Reativa inscrição cancelada/bounced
                    dbExec($pdo,
                        "UPDATE newsletters
                         SET status='subscribed', name=?, full_name=?, categoria=?,
                             subscription_source='registro', unsubscribed_at=NULL, confirmed=1
                         WHERE email=?",
                        [$primeiroNome, $nome, $categoria ?: null, $email]
                    );
                    $nlAction = 'reactivated';
                } else {
                    // Já inscrito — apenas atualiza nome e categoria se vieram vazios
                    dbExec($pdo,
                        "UPDATE newsletters
                         SET name=COALESCE(NULLIF(name,''), ?),
                             full_name=COALESCE(NULLIF(full_name,''), ?),
                             categoria=COALESCE(NULLIF(categoria,''), ?)
                         WHERE email=?",
                        [$primeiroNome, $nome, $categoria ?: null, $email]
                    );
                    $nlAction = 'updated';
                }
            } else {
                // Usuário NÃO optou — mas se já estava inscrito, mantém e atualiza dados
                if ($nlExisting && $nlExisting['status'] === 'subscribed') {
                    $primeiroNome = explode(' ', $nome)[0];
                    dbExec($pdo,
                        "UPDATE newsletters
                         SET full_name=COALESCE(NULLIF(full_name,''), ?),
                             name=COALESCE(NULLIF(name,''), ?)
                         WHERE email=?",
                        [$nome, $primeiroNome, $email]
                    );
                }
            }

            // ── 3. Auto login ─────────────────────────────────────────────────
            $_SESSION['user_id']   = $userId;
            $_SESSION['username']  = $username;
            $_SESSION['full_name'] = $nome;
            $_SESSION['role']      = 'viewer';
            if (!empty($categoria)) $_SESSION['categoria'] = $categoria;

            // ── 4. E-mail de boas-vindas ──────────────────────────────────────
            _enviarEmailBoasVindas($pdo, $nome, $email, $username, $categoria, $nlAction);

            header('Location: /crcap/usuario/perfil.php?welcome=1');
            exit;
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Helper: envia e-mail de boas-vindas usando template do BD ou fallback
// ─────────────────────────────────────────────────────────────────────────────
function _enviarEmailBoasVindas(PDO $pdo, string $nome, string $email, string $username, string $categoria, ?string $nlAction): void
{
    try {
        $mailer       = new CrcapMailer($pdo);
        $primeiroNome = explode(' ', $nome)[0];
        $catLabel     = $categoria ?: '';
        $unsubUrl     = 'https://artemidiaweb.com.br/crcap/unsubscribe.php?email=' . urlencode($email);
        $portalUrl    = 'https://artemidiaweb.com.br/crcap/usuario/perfil.php';

        // Tenta carregar template 'welcome' do BD
        $tpl = null;
        try {
            $tpl = $pdo->prepare("SELECT subject, html_content FROM newsletter_pages WHERE page_key='welcome' LIMIT 1");
            $tpl->execute();
            $tpl = $tpl->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) { $tpl = null; }

        if ($tpl && !empty($tpl['html_content'])) {
            // Usa template do BD com variáveis substituídas
            $html = str_replace(
                ['{{nome}}', '{{email}}', '{{categoria}}', '{{unsubscribe_url}}', '{{username}}'],
                [
                    htmlspecialchars($primeiroNome),
                    htmlspecialchars($email),
                    htmlspecialchars($catLabel),
                    $unsubUrl,
                    htmlspecialchars($username),
                ],
                $tpl['html_content']
            );
            // Injeta bloco de conta criada no template do BD
            $blocoRegistro = _blocoRegistro($username, $portalUrl, $catLabel);
            // Insere antes do </body> se existir, senão concatena
            if (strpos($html, '</body>') !== false) {
                $html = str_replace('</body>', $blocoRegistro . '</body>', $html);
            } else {
                $html .= $blocoRegistro;
            }
            $subject = $tpl['subject'];
        } else {
            // Fallback: template inline completo
            $nlNote = match($nlAction) {
                'new'         => '<p style="background:#dcfce7;border-radius:8px;padding:10px 14px;font-size:13px;color:#166534;margin:16px 0">✅ Você também foi inscrito(a) na nossa newsletter!</p>',
                'reactivated' => '<p style="background:#dbeafe;border-radius:8px;padding:10px 14px;font-size:13px;color:#1e40af;margin:16px 0">🔄 Sua newsletter foi reativada junto com a conta.</p>',
                default       => '',
            };
            $catBlock = $catLabel ? "<p style='color:#022E6B;font-size:14px'>Perfil: <strong style='color:#001644'>{$catLabel}</strong></p>" : '';
            $html = $mailer->wrapTemplate(
                "Olá, {$primeiroNome}! Sua conta foi criada 🎉",
                "{$catBlock}
                 <p style='color:#022E6B;font-size:14px;line-height:1.7'>
                   Bem-vindo(a) ao portal do <strong>CRCAP – Conselho Regional de Contabilidade do Amapá</strong>.
                   Seu cadastro foi realizado com sucesso.
                 </p>
                 <table style='width:100%;border-collapse:collapse;margin:16px 0;font-size:13px'>
                   <tr>
                     <td style='padding:8px 12px;background:#F8FAFC;border:1px solid #e2e8f0;color:#001644;font-weight:bold;width:40%'>Usuário</td>
                     <td style='padding:8px 12px;background:#fff;border:1px solid #e2e8f0;color:#022E6B'>{$username}</td>
                   </tr>
                   <tr>
                     <td style='padding:8px 12px;background:#F8FAFC;border:1px solid #e2e8f0;color:#001644;font-weight:bold'>E-mail</td>
                     <td style='padding:8px 12px;background:#fff;border:1px solid #e2e8f0;color:#022E6B'>{$email}</td>
                   </tr>" .
                   ($catLabel ? "<tr>
                     <td style='padding:8px 12px;background:#F8FAFC;border:1px solid #e2e8f0;color:#001644;font-weight:bold'>Perfil</td>
                     <td style='padding:8px 12px;background:#fff;border:1px solid #e2e8f0;color:#022E6B'>{$catLabel}</td>
                   </tr>" : "") .
                 "</table>
                 {$nlNote}
                 <p style='color:#022E6B;font-size:13px;line-height:1.7'>
                   Agora você pode acessar serviços exclusivos, acompanhar eventos, fazer downloads de documentos e muito mais.
                 </p>",
                'Acessar minha conta',
                $portalUrl
            );
            $subject = "Bem-vindo(a) ao CRCAP, {$primeiroNome}!";
        }

        $mailer->send($email, $nome, $subject, $html);
    } catch (Exception $e) {
        // Silencia erro de e-mail — não bloqueia o cadastro
    }
}

// Bloco HTML injetado no template do BD para informar sobre a conta criada
function _blocoRegistro(string $username, string $portalUrl, string $catLabel): string
{
    $catRow = $catLabel
        ? "<tr><td style='padding:7px 12px;background:#F8FAFC;border:1px solid #e2e8f0;font-size:12px;color:#001644;font-weight:bold'>Perfil</td><td style='padding:7px 12px;border:1px solid #e2e8f0;font-size:12px;color:#022E6B'>{$catLabel}</td></tr>"
        : '';
    return "
    <table width='100%' cellpadding='0' cellspacing='0' style='margin-top:8px'>
    <tr><td style='padding:0 32px 24px'>
      <div style='background:#F8FAFC;border-radius:12px;padding:16px 20px;border:1px solid #e2e8f0'>
        <p style='color:#001644;font-weight:700;font-size:13px;margin:0 0 10px'>🔐 Dados da sua conta</p>
        <table style='width:100%;border-collapse:collapse'>
          <tr>
            <td style='padding:7px 12px;background:#F8FAFC;border:1px solid #e2e8f0;font-size:12px;color:#001644;font-weight:bold;width:38%'>Usuário</td>
            <td style='padding:7px 12px;border:1px solid #e2e8f0;font-size:12px;color:#022E6B'>{$username}</td>
          </tr>
          {$catRow}
        </table>
        <div style='text-align:center;margin-top:14px'>
          <a href='{$portalUrl}' style='background:#BF8D1A;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:12px'>Acessar minha conta</a>
        </div>
      </div>
    </td></tr>
    </table>";
}

include '../includes/header.php';
?>

<section class="min-h-screen bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] flex items-center py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-lg mx-auto">

            <!-- Logo -->
            <div class="text-center mb-8">
                <a href="/crcap/index.php" class="inline-flex items-center gap-3 group">
                    <div class="w-14 h-14 bg-gradient-to-br from-[#BF8D1A] to-[#022E6B] rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-xl">C</div>
                    <div class="text-left">
                        <p class="text-white font-black text-xl leading-none">CRCAP</p>
                        <p class="text-white/50 text-xs font-medium">Conselho Regional</p>
                    </div>
                </a>
                <h1 class="text-white font-bold text-2xl mt-6 mb-1">Criar sua conta</h1>
                <p class="text-white/60 text-sm">Acesse serviços exclusivos do CRCAP</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-3xl p-8 shadow-2xl shadow-[#001644]/40">

                <!-- Erros -->
                <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                    <?php foreach ($errors as $e): ?>
                    <p class="text-xs text-red-600 flex items-center gap-2 mb-1 last:mb-0">
                        <i class="fas fa-exclamation-circle flex-shrink-0"></i><?= htmlspecialchars($e) ?>
                    </p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4" autocomplete="off">
                    <?= csrfField() ?>

                    <!-- Nome completo -->
                    <div>
                        <label class="block text-xs font-semibold text-[#001644] mb-1.5">
                            Nome completo <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                            <input type="text" name="full_name" required
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                   placeholder="Seu nome completo"
                                   class="w-full pl-9 pr-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 transition">
                        </div>
                    </div>

                    <!-- E-mail -->
                    <div>
                        <label class="block text-xs font-semibold text-[#001644] mb-1.5">
                            E-mail <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                            <input type="email" name="email" required id="emailInput"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   placeholder="seu@email.com"
                                   class="w-full pl-9 pr-9 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 transition">
                            <!-- Ícone de verificação de newsletter -->
                            <span id="nlIndicator" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-xs" title=""></span>
                        </div>
                        <!-- Aviso newsletter vinculada -->
                        <div id="nlNotice" class="hidden mt-1.5 px-3 py-2 rounded-lg text-[10px] font-semibold flex items-center gap-2"></div>
                    </div>

                    <!-- Usuário + Telefone -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#001644] mb-1.5">
                                Usuário <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i class="fas fa-at absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                                <input type="text" name="username" required
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                       placeholder="seu_usuario"
                                       pattern="[a-zA-Z0-9_]+"
                                       class="w-full pl-9 pr-3 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#001644] mb-1.5">Telefone</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                                <input type="text" name="phone"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       placeholder="(00) 00000-0000"
                                       class="w-full pl-9 pr-3 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 transition">
                            </div>
                        </div>
                    </div>

                    <!-- ── Perfil Profissional (sincronizado com newsletter) ── -->
                    <div>
                        <label class="block text-xs font-semibold text-[#001644] mb-1.5">
                            <i class="fas fa-id-badge text-[#BF8D1A] mr-1"></i>Perfil profissional
                            <span class="ml-1 text-[9px] font-normal" style="color:rgba(2,46,107,.5)">(usado na newsletter)</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2" id="catGrid">
                            <?php foreach ($categorias as $val => $label):
                                $sel = ($_POST['categoria'] ?? '') === $val;
                            ?>
                            <label class="cat-option flex items-center gap-2.5 px-3 py-2.5 rounded-xl border cursor-pointer transition
                                          <?= $sel ? 'border-[#001644] bg-[#001644]/5 ring-2 ring-[#001644]/10' : 'border-[#001644]/10 hover:border-[#BF8D1A]/50' ?>">
                                <input type="radio" name="categoria" value="<?= htmlspecialchars($val) ?>"
                                       class="accent-[#001644] flex-shrink-0" <?= $sel ? 'checked' : '' ?>>
                                <span class="text-[11px] font-semibold text-[#001644] leading-tight"><?= htmlspecialchars($label) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-[9px] mt-1.5 ml-1" style="color:rgba(2,46,107,.5);">
                            <i class="fas fa-info-circle mr-0.5"></i>
                            Se você já tem uma inscrição na newsletter, sua categoria será atualizada automaticamente.
                        </p>
                    </div>

                    <!-- Senha -->
                    <div>
                        <label class="block text-xs font-semibold text-[#001644] mb-1.5">
                            Senha <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                            <input type="password" name="password" required id="pwdInput"
                                   minlength="8" placeholder="Mínimo 8 caracteres"
                                   class="w-full pl-9 pr-10 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 transition">
                            <button type="button" onclick="togglePwd('pwdInput','eyeIcon1')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-[#001644]/30 hover:text-[#001644] transition text-xs">
                                <i id="eyeIcon1" class="fas fa-eye"></i>
                            </button>
                        </div>
                        <!-- Barra de força -->
                        <div class="flex gap-1 mt-1.5">
                            <?php for ($i=1;$i<=4;$i++): ?>
                            <div class="h-1 flex-1 rounded-full bg-[#001644]/10 transition" id="bar<?= $i ?>"></div>
                            <?php endfor; ?>
                        </div>
                        <p id="pwdStrengthLabel" class="text-[10px] text-[#022E6B]/50 mt-1 ml-0.5 h-3"></p>
                    </div>

                    <!-- Confirmar senha -->
                    <div>
                        <label class="block text-xs font-semibold text-[#001644] mb-1.5">
                            Confirmar senha <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                            <input type="password" name="password_confirm" required id="pwdConfirm"
                                   placeholder="Repita a senha"
                                   class="w-full pl-9 pr-10 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 transition">
                            <button type="button" onclick="togglePwd('pwdConfirm','eyeIcon2')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-[#001644]/30 hover:text-[#001644] transition text-xs">
                                <i id="eyeIcon2" class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p id="pwdMatchLabel" class="text-[10px] mt-1 ml-0.5 h-3"></p>
                    </div>

                    <!-- ── Newsletter opt-in ──────────────────────────────────── -->
                    <div style="background:linear-gradient(to right,rgba(0,22,68,.06),rgba(191,141,26,.08));border:1px solid rgba(0,22,68,.12);"
                         class="rounded-xl p-3.5 flex items-start gap-3">
                        <input type="checkbox" name="newsletter_optin" id="nlOptin"
                               class="mt-0.5 flex-shrink-0 w-4 h-4"
                               style="accent-color:#001644;"
                               <?= isset($_POST['newsletter_optin']) || !isset($_POST['email']) ? 'checked' : '' ?>>
                        <label for="nlOptin" class="cursor-pointer">
                            <p class="text-xs font-semibold flex items-center gap-1.5" style="color:#001644;">
                                <i class="fas fa-envelope" style="color:#BF8D1A;"></i>
                                Receber a newsletter do CRCAP
                            </p>
                            <p class="text-[10px] mt-0.5 leading-relaxed" style="color:rgba(2,46,107,.65);">
                                Notícias, editais, eventos e comunicados oficiais direto no seu e-mail. Cancele quando quiser.
                            </p>
                        </label>
                    </div>

                    <!-- Termos -->
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="terms" class="mt-0.5 accent-[#001644]"
                               <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                        <span class="text-xs text-[#022E6B]">
                            Li e aceito os
                            <a href="/crcap/pages/termos.php" target="_blank" class="text-[#BF8D1A] font-semibold hover:underline">Termos de Uso</a>
                            e a
                            <a href="/crcap/pages/privacidade.php" target="_blank" class="text-[#BF8D1A] font-semibold hover:underline">Política de Privacidade</a>
                            do CRCAP. <span class="text-red-500">*</span>
                        </span>
                    </label>

                    <button type="submit"
                            class="w-full py-3.5 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] hover:shadow-xl hover:shadow-[#001644]/20 transition transform hover:-translate-y-0.5 text-sm">
                        <i class="fas fa-user-plus mr-2"></i>Criar minha conta
                    </button>
                </form>

                <!-- Divisor -->
                <div class="relative my-5">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-[#001644]/10"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-[10px] font-medium" style="color:rgba(2,46,107,.5)">JÁ TEM CONTA?</span>
                    </div>
                </div>

                <a href="/crcap/pages/login.php"
                   class="w-full flex items-center justify-center gap-2 py-3 border-2 border-[#001644]/15 text-[#001644] font-semibold rounded-xl hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition text-sm"
                   style="text-decoration:none;">
                    <i class="fas fa-sign-in-alt"></i>Fazer login
                </a>
            </div>

            <p class="text-center text-white/30 text-xs mt-6">
                &copy; <?= date('Y') ?> CRCAP · Todos os direitos reservados
            </p>
        </div>
    </div>
</section>

<script>
// ── Mostrar/ocultar senha ─────────────────────────────────────────────────────
function togglePwd(inputId, iconId) {
    const inp  = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    icon.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

// ── Força da senha ────────────────────────────────────────────────────────────
document.getElementById('pwdInput').addEventListener('input', function () {
    const pwd = this.value;
    let score = 0;
    if (pwd.length >= 8)            score++;
    if (/[A-Z]/.test(pwd))         score++;
    if (/[0-9]/.test(pwd))         score++;
    if (/[^A-Za-z0-9]/.test(pwd))  score++;

    const colors = ['', 'bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-green-500'];
    const labels = ['', 'Fraca', 'Regular', 'Boa', 'Forte'];
    for (let i = 1; i <= 4; i++) {
        document.getElementById('bar' + i).className =
            'h-1 flex-1 rounded-full transition ' + (i <= score ? colors[score] : 'bg-[#001644]/10');
    }
    const lbl = document.getElementById('pwdStrengthLabel');
    lbl.textContent = pwd.length ? 'Força: ' + (labels[score] || '') : '';
    lbl.className   = 'text-[10px] mt-1 ml-0.5 h-3 ' + (score <= 1 ? 'text-red-400' : score === 2 ? 'text-orange-400' : score === 3 ? 'text-yellow-500' : 'text-green-600');

    checkMatch();
});

// ── Verificar coincidência de senhas ─────────────────────────────────────────
document.getElementById('pwdConfirm').addEventListener('input', checkMatch);
function checkMatch() {
    const a   = document.getElementById('pwdInput').value;
    const b   = document.getElementById('pwdConfirm').value;
    const lbl = document.getElementById('pwdMatchLabel');
    if (!b) { lbl.textContent = ''; return; }
    if (a === b) {
        lbl.textContent = '✓ Senhas coincidem';
        lbl.className   = 'text-[10px] mt-1 ml-0.5 h-3 text-green-600';
    } else {
        lbl.textContent = '✗ Senhas não coincidem';
        lbl.className   = 'text-[10px] mt-1 ml-0.5 h-3 text-red-500';
    }
}

// ── Formatar username automaticamente ────────────────────────────────────────
document.querySelector('[name="username"]').addEventListener('input', function () {
    this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
});

// ── Seleção visual de categorias ─────────────────────────────────────────────
document.querySelectorAll('.cat-option input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', function () {
        document.querySelectorAll('.cat-option').forEach(lbl => {
            lbl.classList.remove('border-[#001644]', 'bg-[#001644]/5', 'ring-2', 'ring-[#001644]/10');
            lbl.classList.add('border-[#001644]/10');
        });
        this.closest('.cat-option').classList.add('border-[#001644]', 'bg-[#001644]/5', 'ring-2', 'ring-[#001644]/10');
        this.closest('.cat-option').classList.remove('border-[#001644]/10');
    });
});

// ── Verificação assíncrona de newsletter ao sair do campo e-mail ─────────────
let nlCheckTimer = null;
document.getElementById('emailInput').addEventListener('blur', function () {
    clearTimeout(nlCheckTimer);
    const email = this.value.trim();
    if (!email || !email.includes('@')) return;
    nlCheckTimer = setTimeout(() => checkNewsletterStatus(email), 300);
});

async function checkNewsletterStatus(email) {
    try {
        const resp = await fetch('/crcap/api/check-newsletter.php?email=' + encodeURIComponent(email));
        const data = await resp.json();
        const notice = document.getElementById('nlNotice');
        const indicator = document.getElementById('nlIndicator');
        const nlCb = document.getElementById('nlOptin');

        if (data.subscribed) {
            // Já inscrito na newsletter
            notice.className = 'mt-1.5 px-3 py-2 rounded-lg text-[10px] font-semibold flex items-center gap-2 bg-green-50 border border-green-200 text-green-700';
            notice.innerHTML = '<i class="fas fa-check-circle text-green-500"></i> E-mail já inscrito na newsletter. Seus dados serão vinculados automaticamente.';
            notice.classList.remove('hidden');
            indicator.innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
            indicator.title = 'Inscrito na newsletter';
            indicator.classList.remove('hidden');
            nlCb.checked = true;
            // Pré-seleciona categoria se o BD tiver
            if (data.categoria) {
                const radios = document.querySelectorAll('.cat-option input[type="radio"]');
                radios.forEach(r => {
                    if (r.value === data.categoria) {
                        r.checked = true;
                        r.dispatchEvent(new Event('change'));
                    }
                });
            }
        } else if (data.unsubscribed) {
            notice.className = 'mt-1.5 px-3 py-2 rounded-lg text-[10px] font-semibold flex items-center gap-2 bg-yellow-50 border border-yellow-200 text-yellow-700';
            notice.innerHTML = '<i class="fas fa-redo text-yellow-500"></i> E-mail estava cancelado. Marque a newsletter para reativar.';
            notice.classList.remove('hidden');
            indicator.innerHTML = '<i class="fas fa-exclamation-circle text-yellow-500"></i>';
            indicator.classList.remove('hidden');
        } else {
            notice.classList.add('hidden');
            indicator.classList.add('hidden');
        }
    } catch (e) { /* silencioso */ }
}
</script>

<?php include '../includes/footer.php'; ?>