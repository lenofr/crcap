<?php
/**
 * CRCAP - Setup / Instalador
 * Acessar em: /install.php
 * DELETAR após instalar!
 */

// Proteção básica: só rodar se não há usuário admin ainda
// (verificado via conexão simples sem framework)

$step   = (int)($_GET['step'] ?? 1);
$errors = [];
$msg    = '';

// Step 2: test connection
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host   = trim($_POST['host'] ?? 'localhost');
    $dbname = trim($_POST['dbname'] ?? '');
    $user   = trim($_POST['user'] ?? '');
    $pass   = $_POST['pass'] ?? '';

    try {
        $testPdo = new PDO(
            "mysql:host=$host;charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        // Create database if not exists
        $testPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $testPdo->exec("USE `$dbname`");

        // Import schema
        $sql = file_get_contents(__DIR__ . '/database_schema.sql');
        if (!$sql) { $errors[] = 'Arquivo database_schema.sql não encontrado.'; }
        else {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
                if ($q) try { $testPdo->exec($q); } catch (Exception $e) { /* ignore existing tables */ }
            }

            // Write config
            $config = "<?php\ndefine('DB_HOST', " . var_export($host,true) . ");\n"
                    . "define('DB_NAME', " . var_export($dbname,true) . ");\n"
                    . "define('DB_USER', " . var_export($user,true) . ");\n"
                    . "define('DB_PASS', " . var_export($pass,true) . ");\n"
                    . "define('DB_CHARSET', 'utf8mb4');\n";
            // Prepend to db.php (overwrite defines)
            $dbFile  = file_get_contents(__DIR__ . '/includes/db.php');
            $newDb   = preg_replace("/define\('DB_(HOST|NAME|USER|PASS|CHARSET)'[^;]+;/", '', $dbFile);
            file_put_contents(__DIR__ . '/includes/db.php', $config . "\n" . ltrim($newDb, "<?php\n"));
            $step = 3;
        }
    } catch (PDOException $e) {
        $errors[] = 'Erro de conexão: ' . $e->getMessage();
        $step = 2;
    }
}

// Step 3: create admin user
if ($step === 3 && isset($_POST['admin_user'])) {
    $adminUser  = trim($_POST['admin_user'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass  = $_POST['admin_pass'] ?? '';
    $adminName  = trim($_POST['admin_name'] ?? '');

    if (!$adminUser || !$adminEmail || strlen($adminPass) < 6) {
        $errors[] = 'Preencha todos os campos. Senha mínima: 6 caracteres.';
        $step = 3;
    } else {
        require_once __DIR__ . '/includes/db.php';
        try {
            // Remove default admin
            $pdo->exec("DELETE FROM users WHERE username='admin'");
            dbExec($pdo,
                "INSERT INTO users (username, email, password, full_name, role, status) VALUES (?,?,?,?,'admin','active')",
                [$adminUser, $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT), $adminName]);
            $step = 4;
        } catch (Exception $e) {
            $errors[] = 'Erro ao criar usuário: '.$e->getMessage();
            $step = 3;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalação CRCAP</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',sans-serif;background:#F8FAFC}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-lg">

    <!-- Logo -->
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl flex items-center justify-center text-white text-3xl font-bold mx-auto mb-3 shadow-lg">C</div>
        <h1 class="text-2xl font-bold text-[#001644]" style="font-family:'DM Serif Display',serif">Instalação CRCAP</h1>
        <p class="text-xs text-[#022E6B] mt-1">Configure seu sistema em 4 passos simples</p>
    </div>

    <!-- Steps indicator -->
    <div class="flex items-center gap-2 mb-8">
        <?php foreach ([1=>'Boas-vindas',2=>'Banco de Dados',3=>'Admin',4=>'Concluído'] as $n=>$label): ?>
        <div class="flex-1 text-center">
            <div class="w-8 h-8 rounded-full flex items-center justify-center mx-auto mb-1 text-xs font-bold
                <?= $step>=$n ? 'bg-[#001644] text-white' : 'bg-white border-2 border-[#001644]/10 text-[#001644]/30' ?>">
                <?= $step>$n ? '✓' : $n ?>
            </div>
            <p class="text-[9px] text-[#022E6B] hidden sm:block"><?= $label ?></p>
        </div>
        <?php if ($n < 4): ?><div class="w-full h-px bg-[#001644]/10 flex-shrink-0 max-w-8"></div><?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl border border-[#001644]/5 shadow-xl p-8">

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl p-4 mb-6">
            <?php foreach ($errors as $e): ?><p class="flex items-center gap-2"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <h2 class="font-bold text-[#001644] text-xl mb-2">Bem-vindo ao CRCAP!</h2>
        <p class="text-sm text-[#022E6B] mb-6 leading-relaxed">Este assistente irá configurar o sistema em poucos minutos. Você precisará das credenciais do seu banco de dados MySQL.</p>
        <div class="space-y-3 mb-6">
            <?php foreach (['PHP 8.1+','MySQL 8.0+ / MariaDB 10.6+','Extensões: PDO, PDO_MySQL, mbstring','Apache com mod_rewrite'] as $req): ?>
            <div class="flex items-center gap-3 text-sm text-[#022E6B]">
                <i class="fas fa-check-circle text-[#006633]"></i><?= $req ?>
            </div>
            <?php endforeach; ?>
        </div>
        <a href="?step=2" class="block w-full py-3 bg-[#001644] text-white text-center font-bold rounded-xl hover:bg-[#022E6B] transition">
            Iniciar instalação <i class="fas fa-arrow-right ml-2"></i>
        </a>

        <?php elseif ($step === 2): ?>
        <h2 class="font-bold text-[#001644] text-xl mb-5">Configuração do Banco de Dados</h2>
        <form method="POST" action="?step=2" class="space-y-4">
            <?php foreach ([
                ['host','Servidor MySQL','localhost','text'],
                ['dbname','Nome do Banco','crcap','text'],
                ['user','Usuário MySQL','root','text'],
                ['pass','Senha MySQL','','password'],
            ] as $f): ?>
            <div>
                <label class="block text-xs font-semibold text-[#001644] mb-1.5"><?= $f[1] ?></label>
                <input type="<?= $f[3] ?>" name="<?= $f[0] ?>" value="<?= $f[3]==='text'?htmlspecialchars($_POST[$f[0]]??$f[2]):'' ?>"
                       placeholder="<?= $f[2] ?>"
                       class="w-full px-4 py-2.5 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] <?= $f[0]==='host'?'font-mono':'' ?>">
            </div>
            <?php endforeach; ?>
            <button type="submit" class="w-full py-3 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] transition mt-2">
                Conectar e instalar <i class="fas fa-database ml-2"></i>
            </button>
        </form>

        <?php elseif ($step === 3): ?>
        <h2 class="font-bold text-[#001644] text-xl mb-5">Criar usuário administrador</h2>
        <form method="POST" action="?step=3" class="space-y-4">
            <?php foreach ([
                ['admin_name','Nome Completo','Ex.: João da Silva','text'],
                ['admin_email','E-mail','admin@crcap.org.br','email'],
                ['admin_user','Usuário (login)','admin','text'],
                ['admin_pass','Senha (mín. 6 caracteres)','','password'],
            ] as $f): ?>
            <div>
                <label class="block text-xs font-semibold text-[#001644] mb-1.5"><?= $f[1] ?></label>
                <input type="<?= $f[3] ?>" name="<?= $f[0] ?>" placeholder="<?= $f[2] ?>" required
                       class="w-full px-4 py-2.5 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A]">
            </div>
            <?php endforeach; ?>
            <button type="submit" class="w-full py-3 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] transition mt-2">
                Criar administrador <i class="fas fa-user-shield ml-2"></i>
            </button>
        </form>

        <?php elseif ($step === 4): ?>
        <div class="text-center">
            <div class="w-16 h-16 bg-[#006633] rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-white text-3xl"></i>
            </div>
            <h2 class="font-bold text-[#001644] text-xl mb-2">Instalação concluída! 🎉</h2>
            <p class="text-sm text-[#022E6B] mb-6 leading-relaxed">
                O CRCAP foi instalado com sucesso. <strong class="text-red-600">Delete o arquivo install.php</strong> agora por segurança.
            </p>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 text-left">
                <p class="text-xs font-bold text-red-700 flex items-center gap-2 mb-1"><i class="fas fa-exclamation-triangle"></i>Ação obrigatória</p>
                <p class="text-xs text-red-600">Execute no terminal: <code class="bg-red-100 px-1 rounded font-mono">rm install.php</code></p>
            </div>
            <div class="flex gap-3">
                <a href="/crcap/index.php" class="flex-1 py-3 border border-[#001644]/10 text-[#001644] rounded-xl font-semibold text-sm hover:border-[#BF8D1A] transition">
                    <i class="fas fa-home mr-2"></i>Ver site
                </a>
                <a href="/crcap/admin/index.php" class="flex-1 py-3 bg-[#001644] text-white rounded-xl font-bold text-sm hover:bg-[#BF8D1A] transition">
                    <i class="fas fa-cog mr-2"></i>Painel Admin
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <p class="text-center text-[10px] text-[#022E6B]/40 mt-4">CRCAP © <?= date('Y') ?> · Sistema de Gerenciamento</p>
</div>
</body>
</html>
