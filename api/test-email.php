<?php
/**
 * Ferramenta de diagnóstico de e-mail – CRCAP
 * Acesso: /crcap/api/test-email.php
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!isAdmin()) { http_response_code(403); die('Acesso negado'); }

$to     = trim($_GET['to'] ?? '');
$action = $_GET['action'] ?? 'info';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Diagnóstico de E-mail · CRCAP</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 p-6 font-sans text-sm">
<div class="max-w-3xl mx-auto space-y-6">

<div class="flex items-center gap-3 mb-2">
    <a href="/crcap/admin/newsletter.php" class="text-blue-600 hover:underline text-xs">← Voltar ao Newsletter</a>
</div>
<h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
    <i class="fas fa-stethoscope text-blue-600"></i> Diagnóstico de E-mail CRCAP
</h1>

<?php
// ── 1. Config SMTP atual ──────────────────────────────────────────────────────
$smtp = null;
try {
    $smtp = $pdo->query("SELECT * FROM smtp_settings WHERE is_active=1 ORDER BY is_default DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<div class="bg-white rounded-xl border p-5 space-y-3">
    <h2 class="font-bold text-gray-700 flex items-center gap-2"><i class="fas fa-server text-indigo-500"></i> 1. Configuração SMTP</h2>
    <?php if ($smtp): ?>
    <div class="grid grid-cols-2 gap-2 text-xs">
        <?php
        $fields = [
            'Host'       => $smtp['host'],
            'Porta'      => $smtp['port'],
            'Usuário'    => $smtp['username'],
            'Senha'      => str_repeat('*', min(8, strlen(base64_decode($smtp['password'] ?? '')))),
            'Encryption' => $smtp['encryption'],
            'De (nome)'  => $smtp['from_name'],
            'De (email)' => $smtp['from_email'],
            'Ativo'      => $smtp['is_active'] ? '✅ Sim' : '❌ Não',
            'Padrão'     => $smtp['is_default'] ? '✅ Sim' : 'Não',
        ];
        foreach ($fields as $k => $v): ?>
        <div class="flex justify-between border-b py-1">
            <span class="text-gray-500"><?= $k ?></span>
            <span class="font-mono font-semibold"><?= htmlspecialchars((string)$v) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-xs">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong>Nenhuma configuração SMTP ativa encontrada!</strong><br>
        O sistema está usando o <code>mail()</code> do PHP, que é frequentemente bloqueado em hospedagem compartilhada.<br>
        <a href="/crcap/admin/smtp.php" class="underline font-bold">→ Configure o SMTP agora</a>
    </div>
    <?php endif; ?>
</div>

<?php
// ── 2. Teste de conexão SMTP ──────────────────────────────────────────────────
$mailer = mailer();
$connTest = $mailer->testConnection();
?>
<div class="bg-white rounded-xl border p-5">
    <h2 class="font-bold text-gray-700 flex items-center gap-2 mb-3">
        <i class="fas fa-plug text-<?= $connTest['success'] ? 'green' : 'red' ?>-500"></i> 2. Teste de Conexão SMTP
    </h2>
    <div class="<?= $connTest['success'] ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?> border rounded-lg p-3 text-xs">
        <?= $connTest['success'] ? '✅' : '❌' ?> <?= htmlspecialchars($connTest['message']) ?>
    </div>
</div>

<?php
// ── 3. Logs da última campanha ────────────────────────────────────────────────
$lastLogs = [];
try {
    $lastLogs = $pdo->query("SELECT el.*, ec.name as camp_name FROM email_logs el LEFT JOIN email_campaigns ec ON el.campaign_id=ec.id ORDER BY el.sent_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<div class="bg-white rounded-xl border p-5">
    <h2 class="font-bold text-gray-700 flex items-center gap-2 mb-3">
        <i class="fas fa-list text-blue-500"></i> 3. Últimos 10 Logs de Envio
    </h2>
    <?php if (empty($lastLogs)): ?>
    <p class="text-xs text-gray-400">Nenhum log encontrado.</p>
    <?php else: ?>
    <table class="w-full text-xs">
        <thead class="bg-gray-100"><tr>
            <th class="text-left p-2">E-mail</th>
            <th class="text-left p-2">Campanha</th>
            <th class="text-center p-2">Status</th>
            <th class="text-left p-2">Erro</th>
            <th class="text-left p-2">Data</th>
        </tr></thead>
        <tbody>
        <?php foreach ($lastLogs as $log): ?>
        <tr class="border-t">
            <td class="p-2 font-mono"><?= htmlspecialchars($log['recipient_email']) ?></td>
            <td class="p-2"><?= htmlspecialchars($log['camp_name'] ?? '—') ?></td>
            <td class="p-2 text-center">
                <?php
                $sc = ['sent'=>'bg-green-100 text-green-700','failed'=>'bg-red-100 text-red-700','queued'=>'bg-yellow-100 text-yellow-700'];
                $s = $log['status'];
                ?>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $sc[$s] ?? 'bg-gray-100 text-gray-600' ?>"><?= $s ?></span>
            </td>
            <td class="p-2 text-red-600 max-w-xs truncate"><?= htmlspecialchars($log['error_message'] ?? '') ?></td>
            <td class="p-2 text-gray-500"><?= date('d/m H:i', strtotime($log['sent_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php
// ── 4. Enviar e-mail de teste ─────────────────────────────────────────────────
$testResult = null;
if ($to && filter_var($to, FILTER_VALIDATE_EMAIL) && $action === 'send') {
    $html = "
    <div style='font-family:Arial,sans-serif;max-width:500px;margin:0 auto;padding:32px;background:#fff;border-radius:12px'>
        <div style='background:#001644;padding:20px;border-radius:8px;text-align:center;margin-bottom:24px'>
            <span style='color:white;font-size:24px;font-weight:bold'>C</span>
            <span style='color:white;font-size:16px;margin-left:8px'>CRCAP</span>
        </div>
        <h2 style='color:#001644'>✅ E-mail de teste</h2>
        <p style='color:#022E6B'>Este é um e-mail de teste do sistema CRCAP.</p>
        <p style='color:#022E6B'>Se você recebeu este e-mail, o SMTP está <strong>funcionando corretamente</strong>.</p>
        <hr style='margin:24px 0;border-color:#e2e8f0'>
        <p style='color:#94a3b8;font-size:11px'>Enviado em: " . date('d/m/Y H:i:s') . "<br>Host SMTP: " . htmlspecialchars($smtp['host'] ?? 'php mail()') . "</p>
    </div>";

    $ok = $mailer->send($to, $to, '✅ Teste CRCAP – ' . date('d/m/Y H:i'), $html, 'Teste CRCAP funcionando!');
    $errors = $mailer->getErrors();
    $testResult = ['ok' => $ok, 'errors' => $errors];
}
?>

<div class="bg-white rounded-xl border p-5">
    <h2 class="font-bold text-gray-700 flex items-center gap-2 mb-3">
        <i class="fas fa-paper-plane text-blue-500"></i> 4. Enviar E-mail de Teste
    </h2>
    <form method="GET" class="flex gap-2 mb-4">
        <input type="hidden" name="action" value="send">
        <input type="email" name="to" value="<?= htmlspecialchars($to) ?>" required placeholder="seu@email.com"
               class="flex-1 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
            <i class="fas fa-paper-plane mr-1"></i>Enviar Teste
        </button>
    </form>
    <?php if ($testResult !== null): ?>
    <div class="<?= $testResult['ok'] ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?> border rounded-lg p-3 text-xs space-y-1">
        <?php if ($testResult['ok']): ?>
        <p><strong>✅ E-mail enviado com sucesso para <?= htmlspecialchars($to) ?></strong></p>
        <p>Verifique sua caixa de entrada (e o spam/lixo eletrônico).</p>
        <?php else: ?>
        <p><strong>❌ Falha no envio</strong></p>
        <?php foreach ($testResult['errors'] as $err): ?>
        <p class="font-mono bg-red-100 px-2 py-1 rounded"><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>
        <?php if (!$smtp): ?>
        <p class="mt-2">→ Sem SMTP configurado, o <code>mail()</code> do PHP foi usado e provavelmente foi bloqueado.<br>
        <a href="/crcap/admin/smtp.php" class="underline font-bold">Configure o SMTP aqui</a></p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php
// ── 5. Inscritos com e sem categoria ─────────────────────────────────────────
$subStats = [];
try {
    $subStats = $pdo->query("SELECT COALESCE(categoria,'(sem categoria)') as cat, COUNT(*) as n FROM newsletters WHERE status='subscribed' GROUP BY cat ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$lastCamp = null;
try {
    $lastCamp = $pdo->query("SELECT * FROM email_campaigns ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<div class="bg-white rounded-xl border p-5">
    <h2 class="font-bold text-gray-700 flex items-center gap-2 mb-3">
        <i class="fas fa-users text-purple-500"></i> 5. Inscritos por Categoria
    </h2>
    <?php if ($subStats): ?>
    <table class="w-full text-xs">
        <thead class="bg-gray-100"><tr>
            <th class="text-left p-2">Categoria</th>
            <th class="text-right p-2">Quantidade</th>
            <th class="text-left p-2 w-1/2">Barra</th>
        </tr></thead>
        <tbody>
        <?php $maxN = max(array_column($subStats,'n')) ?: 1; foreach ($subStats as $r): ?>
        <tr class="border-t">
            <td class="p-2 font-semibold"><?= htmlspecialchars($r['cat']) ?></td>
            <td class="p-2 text-right font-bold"><?= $r['n'] ?></td>
            <td class="p-2"><div class="h-3 bg-blue-500 rounded" style="width:<?= round($r['n']/$maxN*100) ?>%"></div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php if ($lastCamp): ?>
<div class="bg-white rounded-xl border p-5">
    <h2 class="font-bold text-gray-700 flex items-center gap-2 mb-2">
        <i class="fas fa-bullhorn text-yellow-500"></i> 6. Última Campanha
    </h2>
    <div class="grid grid-cols-2 gap-2 text-xs">
        <?php foreach (['name'=>'Nome','subject'=>'Assunto','status'=>'Status','sent_count'=>'Enviados','bounced_count'=>'Falhas','from_email'=>'De'] as $k=>$l): ?>
        <div class="flex justify-between border-b py-1">
            <span class="text-gray-500"><?= $l ?></span>
            <span class="font-semibold"><?= htmlspecialchars((string)($lastCamp[$k]??'')) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($lastCamp['status'] === 'sent' && ($lastCamp['bounced_count'] ?? 0) > 0): ?>
    <div class="mt-3 bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-700">
        ⚠️ <strong><?= $lastCamp['bounced_count'] ?> e-mail(s) falharam</strong> no último envio. Verifique os logs acima para ver os erros.
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-xs text-yellow-800">
    <p class="font-bold mb-1">💡 Dicas de entregabilidade:</p>
    <ul class="space-y-1 list-disc ml-4">
        <li>Configure SMTP com Gmail, SendGrid, Mailgun ou o SMTP da sua hospedagem</li>
        <li>Verifique se o domínio tem registros <strong>SPF, DKIM e DMARC</strong> configurados no DNS</li>
        <li>Evite palavras como "grátis", "clique aqui" no assunto</li>
        <li>Sempre inclua link de descadastro (<code>{{unsubscribe_url}}</code>)</li>
        <li>Teste primeiro com seu próprio e-mail antes de enviar para a lista</li>
    </ul>
</div>

<p class="text-center text-xs text-gray-400">
    <a href="/crcap/admin/smtp.php" class="underline">Configurar SMTP</a> · 
    <a href="/crcap/admin/newsletter.php" class="underline">Newsletter</a>
</p>

</div>
</body>
</html>