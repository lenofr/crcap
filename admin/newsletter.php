<?php
// ── Handlers que fazem redirect/header ANTES de qualquer output HTML ──────────
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
requireAdmin();

// ── Migrações automáticas ─────────────────────────────────────────────────────
try { $pdo->exec("ALTER TABLE newsletters ADD COLUMN full_name VARCHAR(255) NULL AFTER name"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE newsletters ADD COLUMN categoria VARCHAR(100) NULL AFTER full_name"); } catch (Exception $e) {}
try { $pdo->exec("UPDATE newsletters SET confirmed=1 WHERE status='subscribed' AND confirmed=0"); } catch (Exception $e) {}

// Cria tabela de páginas de resposta se não existir
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `newsletter_pages` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `page_key` VARCHAR(50) NOT NULL,
        `page_label` VARCHAR(100) NOT NULL,
        `subject` VARCHAR(255) NOT NULL,
        `html_content` LONGTEXT NOT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `page_key` (`page_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {}

// Insere defaults se ainda não existirem
$defaultPages = [
    'welcome' => [
        'label'   => 'Boas-vindas (novo inscrito)',
        'subject' => 'Bem-vindo(a) à Newsletter do CRCAP!',
        'html'    => '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC"><tr><td align="center" style="padding:32px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,22,68,.07)">
  <tr><td style="background:linear-gradient(135deg,#001644,#022E6B);padding:28px 32px">
    <span style="color:#fff;font-size:20px;font-weight:700">CRCAP – Conselho Regional</span>
  </td></tr>
  <tr><td style="padding:32px">
    <h2 style="color:#001644;font-size:22px;margin:0 0 16px">Olá, {{nome}}! 🎉</h2>
    <p style="color:#022E6B;font-size:14px;line-height:1.7">Sua inscrição na newsletter do CRCAP foi confirmada com sucesso.</p>
    <p style="color:#022E6B;font-size:14px;line-height:1.7">Você receberá em primeira mão:</p>
    <ul style="color:#022E6B;font-size:14px;line-height:2">
      <li>📰 Notícias e comunicados oficiais</li>
      <li>📅 Agenda de eventos e cursos</li>
      <li>📄 Editais e publicações</li>
      <li>🎓 Oportunidades de desenvolvimento profissional</li>
    </ul>
    <div style="text-align:center;margin:28px 0">
      <a href="https://artemidiaweb.com.br/crcap" style="background:#001644;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:bold;font-size:14px">Acessar o Portal CRCAP</a>
    </div>
    <p style="color:#94a3b8;font-size:12px">Categoria: {{categoria}}</p>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0">
    <p style="color:#94a3b8;font-size:11px;text-align:center">
      Para cancelar sua inscrição, <a href="{{unsubscribe_url}}" style="color:#BF8D1A">clique aqui</a>.
    </p>
  </td></tr>
  <tr><td style="background:#F8FAFC;padding:16px 32px;border-top:1px solid #e2e8f0">
    <p style="color:#94a3b8;font-size:11px;margin:0;text-align:center">© ' . date('Y') . ' CRCAP – Todos os direitos reservados</p>
  </td></tr>
</table></td></tr></table></body></html>',
    ],
    'reactivation' => [
        'label'   => 'Reinscrição (voltou a se inscrever)',
        'subject' => 'Sua inscrição foi reativada – CRCAP',
        'html'    => '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC"><tr><td align="center" style="padding:32px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,22,68,.07)">
  <tr><td style="background:linear-gradient(135deg,#001644,#022E6B);padding:28px 32px">
    <span style="color:#fff;font-size:20px;font-weight:700">CRCAP – Conselho Regional</span>
  </td></tr>
  <tr><td style="padding:32px">
    <h2 style="color:#001644;font-size:22px;margin:0 0 16px">Que bom ter você de volta, {{nome}}!</h2>
    <p style="color:#022E6B;font-size:14px;line-height:1.7">Sua inscrição na newsletter do CRCAP foi <strong>reativada com sucesso</strong>.</p>
    <p style="color:#022E6B;font-size:14px;line-height:1.7">Você voltará a receber nossas novidades, eventos e comunicados oficiais.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="https://artemidiaweb.com.br/crcap" style="background:#006633;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:bold;font-size:14px">Acessar o Portal CRCAP</a>
    </div>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0">
    <p style="color:#94a3b8;font-size:11px;text-align:center">
      Para cancelar novamente, <a href="{{unsubscribe_url}}" style="color:#BF8D1A">clique aqui</a>.
    </p>
  </td></tr>
  <tr><td style="background:#F8FAFC;padding:16px 32px;border-top:1px solid #e2e8f0">
    <p style="color:#94a3b8;font-size:11px;margin:0;text-align:center">© ' . date('Y') . ' CRCAP – Todos os direitos reservados</p>
  </td></tr>
</table></td></tr></table></body></html>',
    ],
    'unsubscribe' => [
        'label'   => 'Cancelamento (descadastro)',
        'subject' => 'Sua inscrição foi cancelada – CRCAP',
        'html'    => '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC"><tr><td align="center" style="padding:32px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,22,68,.07)">
  <tr><td style="background:linear-gradient(135deg,#001644,#022E6B);padding:28px 32px">
    <span style="color:#fff;font-size:20px;font-weight:700">CRCAP – Conselho Regional</span>
  </td></tr>
  <tr><td style="padding:32px">
    <h2 style="color:#001644;font-size:22px;margin:0 0 16px">Inscrição cancelada</h2>
    <p style="color:#022E6B;font-size:14px;line-height:1.7">Olá, {{nome}}. Confirmamos que seu e-mail <strong>{{email}}</strong> foi removido da nossa lista.</p>
    <p style="color:#022E6B;font-size:14px;line-height:1.7">Lamentamos sua saída. Se mudar de ideia, você pode se inscrever novamente a qualquer momento pelo nosso portal.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="https://artemidiaweb.com.br/crcap" style="background:#BF8D1A;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:bold;font-size:14px">Inscrever-se novamente</a>
    </div>
  </td></tr>
  <tr><td style="background:#F8FAFC;padding:16px 32px;border-top:1px solid #e2e8f0">
    <p style="color:#94a3b8;font-size:11px;margin:0;text-align:center">© ' . date('Y') . ' CRCAP – Todos os direitos reservados</p>
  </td></tr>
</table></td></tr></table></body></html>',
    ],
    'already_subscribed' => [
        'label'   => 'Já inscrito (e-mail duplicado)',
        'subject' => 'Você já está inscrito na Newsletter do CRCAP',
        'html'    => '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#F8FAFC;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC"><tr><td align="center" style="padding:32px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,22,68,.07)">
  <tr><td style="background:linear-gradient(135deg,#001644,#022E6B);padding:28px 32px">
    <span style="color:#fff;font-size:20px;font-weight:700">CRCAP – Conselho Regional</span>
  </td></tr>
  <tr><td style="padding:32px">
    <h2 style="color:#001644;font-size:22px;margin:0 0 16px">Você já está inscrito! ✅</h2>
    <p style="color:#022E6B;font-size:14px;line-height:1.7">O e-mail <strong>{{email}}</strong> já está cadastrado em nossa lista e continua ativo.</p>
    <p style="color:#022E6B;font-size:14px;line-height:1.7">Fique tranquilo(a) — você não perderá nenhuma novidade do CRCAP.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="https://artemidiaweb.com.br/crcap" style="background:#001644;color:#fff;padding:14px 32px;border-radius:10px;text-decoration:none;font-weight:bold;font-size:14px">Acessar o Portal CRCAP</a>
    </div>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0">
    <p style="color:#94a3b8;font-size:11px;text-align:center">
      Deseja cancelar? <a href="{{unsubscribe_url}}" style="color:#BF8D1A">Clique aqui</a>.
    </p>
  </td></tr>
  <tr><td style="background:#F8FAFC;padding:16px 32px;border-top:1px solid #e2e8f0">
    <p style="color:#94a3b8;font-size:11px;margin:0;text-align:center">© ' . date('Y') . ' CRCAP – Todos os direitos reservados</p>
  </td></tr>
</table></td></tr></table></body></html>',
    ],
];

foreach ($defaultPages as $key => $data) {
    try {
        $exists = $pdo->prepare("SELECT id FROM newsletter_pages WHERE page_key=?")->execute([$key]);
        $row    = dbFetch($pdo, "SELECT id FROM newsletter_pages WHERE page_key=?", [$key]);
        if (!$row) {
            dbExec($pdo,
                "INSERT INTO newsletter_pages (page_key, page_label, subject, html_content) VALUES (?,?,?,?)",
                [$key, $data['label'], $data['subject'], $data['html']]
            );
        }
    } catch (Exception $e) {}
}

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$tab    = $_GET['tab'] ?? 'subscribers'; // subscribers | campaigns | pages

// ── Exportar CSV ──────────────────────────────────────────────────────────────
if ($action === 'export') {
    $subs = dbFetchAll($pdo, "SELECT email,name,full_name,categoria,status,subscribed_at FROM newsletters ORDER BY subscribed_at DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=newsletter-'.date('Y-m-d').'.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Email','Nome','Nome Completo','Categoria','Status','Inscrito em']);
    foreach ($subs as $s) fputcsv($out, [$s['email'],$s['name'],$s['full_name'],$s['categoria'],$s['status'],date('d/m/Y H:i',strtotime($s['subscribed_at']))]);
    fclose($out); exit;
}

// ── Deletar inscrição ─────────────────────────────────────────────────────────
if ($action === 'delete' && $id) {
    dbExec($pdo, "DELETE FROM newsletters WHERE id=?", [$id]);
    header('Location: /crcap/admin/newsletter.php?tab=subscribers&msg=deleted'); exit;
}

// ── Deletar campanha ──────────────────────────────────────────────────────────
if ($action === 'delete_campaign' && $id) {
    dbExec($pdo, "DELETE FROM email_campaigns WHERE id=?", [$id]);
    header('Location: /crcap/admin/newsletter.php?tab=campaigns&msg=campaign_deleted'); exit;
}

// ── Resetar campanha ──────────────────────────────────────────────────────────
if ($action === 'resend_campaign' && $id) {
    dbExec($pdo, "UPDATE email_campaigns SET status='draft',sent_count=0,opened_count=0,clicked_count=0,bounced_count=0,sent_at=NULL WHERE id=?", [$id]);
    header('Location: /crcap/admin/newsletter.php?tab=campaigns&msg=campaign_reset'); exit;
}

// ── Salvar inscrição (POST) ───────────────────────────────────────────────────
$subError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'save_subscriber') {
    $sid       = (int)($_POST['sub_id'] ?? 0);
    $email     = trim($_POST['email'] ?? '');
    $name      = trim($_POST['name'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $status    = $_POST['status'] ?? 'subscribed';
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($sid) {
            dbExec($pdo, "UPDATE newsletters SET email=?,name=?,full_name=?,categoria=?,status=? WHERE id=?",
                [$email,$name,$full_name,$categoria,$status,$sid]);
            header('Location: /crcap/admin/newsletter.php?tab=subscribers&msg=sub_saved'); exit;
        } else {
            try {
                dbExec($pdo, "INSERT INTO newsletters (email,name,full_name,categoria,status,confirmed,subscription_source) VALUES (?,?,?,?,'subscribed',1,'admin')",
                    [$email,$name,$full_name,$categoria]);
                header('Location: /crcap/admin/newsletter.php?tab=subscribers&msg=sub_saved'); exit;
            } catch (Exception $e) { $subError = 'E-mail já cadastrado.'; }
        }
    } else { $subError = 'E-mail inválido.'; }
}

// ── Criar/editar campanha (POST) ──────────────────────────────────────────────
$campError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'save_campaign') {
    $cid        = (int)($_POST['camp_id'] ?? 0);
    $n          = trim($_POST['campaign_name'] ?? '');
    $s          = trim($_POST['campaign_subject'] ?? '');
    $h          = trim($_POST['campaign_html'] ?? '');
    $blocksJson = trim($_POST['campaign_blocks_json'] ?? ''); // JSON serializado dos blocos
    $fe         = trim($_POST['from_email'] ?? '');
    $fn         = trim($_POST['from_name'] ?? '');
    $cats       = implode(',', array_filter(array_map('trim', (array)($_POST['camp_categorias'] ?? []))));
    if ($n && $s && $h) {
        try {
            $textContent = $blocksJson ?: strip_tags($h); // salva JSON de blocos em content_text
            if ($cid) {
                dbExec($pdo, "UPDATE email_campaigns SET name=?,subject=?,from_name=?,from_email=?,content_html=?,content_text=?,segment_filter=? WHERE id=?",
                    [$n,$s,$fn,$fe,$h,$textContent,$cats?json_encode(['categorias'=>explode(',',$cats)]):null,$cid]);
                header('Location: /crcap/admin/newsletter.php?tab=campaigns&msg=campaign_saved'); exit;
            } else {
                dbExec($pdo, "INSERT INTO email_campaigns (name,subject,from_name,from_email,content_html,content_text,segment_filter,status,created_by) VALUES (?,?,?,?,?,?,?,?,?)",
                    [$n,$s,$fn,$fe,$h,$textContent,$cats?json_encode(['categorias'=>explode(',',$cats)]):null,'draft',$_SESSION['user_id']??1]);
                header('Location: /crcap/admin/newsletter.php?tab=campaigns&msg=campaign_created'); exit;
            }
        } catch (Exception $e) { $campError = $e->getMessage(); }
    }
}

// ── Salvar Página de Resposta (POST) ──────────────────────────────────────────
$pageError   = '';
$pageSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'save_newsletter_page') {
    $pageKey  = trim($_POST['page_key'] ?? '');
    $subject  = trim($_POST['page_subject'] ?? '');
    $htmlBody = trim($_POST['page_html'] ?? '');
    if ($pageKey && $subject && $htmlBody) {
        try {
            $exists = dbFetch($pdo, "SELECT id FROM newsletter_pages WHERE page_key=?", [$pageKey]);
            if ($exists) {
                dbExec($pdo, "UPDATE newsletter_pages SET subject=?, html_content=? WHERE page_key=?",
                    [$subject, $htmlBody, $pageKey]);
            } else {
                $label = $_POST['page_label'] ?? $pageKey;
                dbExec($pdo, "INSERT INTO newsletter_pages (page_key, page_label, subject, html_content) VALUES (?,?,?,?)",
                    [$pageKey, $label, $subject, $htmlBody]);
            }
            header('Location: /crcap/admin/newsletter.php?tab=pages&msg=page_saved&edited='.$pageKey); exit;
        } catch (Exception $e) { $pageError = $e->getMessage(); }
    } else {
        $pageError = 'Preencha todos os campos obrigatórios.';
    }
}

// ── Enviar e-mail de teste para página de resposta ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'test_newsletter_page') {
    $pageKey   = trim($_POST['page_key'] ?? '');
    $testEmail = trim($_POST['test_email'] ?? '');
    if ($pageKey && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $page = dbFetch($pdo, "SELECT * FROM newsletter_pages WHERE page_key=?", [$pageKey]);
        if ($page) {
            $unsubUrl   = 'https://artemidiaweb.com.br/crcap/unsubscribe.php?email=' . urlencode($testEmail);
            $htmlToSend = str_replace(
                ['{{nome}}','{{email}}','{{categoria}}','{{unsubscribe_url}}'],
                ['Usuário Teste', $testEmail, 'Teste', $unsubUrl],
                $page['html_content']
            );
            $mailer = new CrcapMailer($pdo);
            $ok = $mailer->send($testEmail, 'Usuário Teste', '[TESTE] ' . $page['subject'], $htmlToSend);
            header('Location: /crcap/admin/newsletter.php?tab=pages&msg='.($ok?'test_sent':'test_failed').'&edited='.$pageKey); exit;
        }
    }
    header('Location: /crcap/admin/newsletter.php?tab=pages&msg=test_failed&edited='.$pageKey); exit;
}

// ── Carrega HTML ──────────────────────────────────────────────────────────────
// Categorias sincronizadas com registro.php e com o que está gravado no BD
$categorias = ['Contador', 'Técnico em Contabilidade', 'Estudante de Ciências Contábeis', 'Outro'];

$pageTitle = 'Newsletter · Admin CRCAP';
$activeAdm = 'newsletter';
require_once __DIR__ . '/admin_header.php';

// ── Métricas ──────────────────────────────────────────────────────────────────
$counts = [
    'subscribed'   => dbFetch($pdo,"SELECT COUNT(*) AS n FROM newsletters WHERE status='subscribed'")['n'] ?? 0,
    'unsubscribed' => dbFetch($pdo,"SELECT COUNT(*) AS n FROM newsletters WHERE status='unsubscribed'")['n'] ?? 0,
    'bounced'      => dbFetch($pdo,"SELECT COUNT(*) AS n FROM newsletters WHERE status='bounced'")['n'] ?? 0,
    'today'        => dbFetch($pdo,"SELECT COUNT(*) AS n FROM newsletters WHERE DATE(subscribed_at)=CURDATE()")['n'] ?? 0,
];
$catCounts = [];
foreach ($categorias as $cat) {
    $catCounts[$cat] = dbFetch($pdo,"SELECT COUNT(*) AS n FROM newsletters WHERE categoria=? AND status='subscribed'",[$cat])['n'] ?? 0;
}

$page_n = max(1,(int)($_GET['p'] ?? 1));
$perP   = 20; $off = ($page_n-1)*$perP;
$filter = $_GET['filter'] ?? 'subscribed';
$catFlt = $_GET['cat'] ?? '';
$busca  = trim($_GET['busca'] ?? '');

$where = ['1=1']; $params = [];
if ($filter) { $where[] = 'status=?'; $params[] = $filter; }
if ($catFlt) { $where[] = 'categoria=?'; $params[] = $catFlt; }
if ($busca)  { $where[] = '(email LIKE ? OR name LIKE ? OR full_name LIKE ?)'; $params[] = "%$busca%"; $params[] = "%$busca%"; $params[] = "%$busca%"; }

$subs      = dbFetchAll($pdo, "SELECT * FROM newsletters WHERE ".implode(' AND ',$where)." ORDER BY subscribed_at DESC LIMIT $perP OFFSET $off", $params);
$total     = dbFetch($pdo, "SELECT COUNT(*) AS n FROM newsletters WHERE ".implode(' AND ',$where), $params)['n'] ?? 0;
$pages     = ceil($total/$perP);
$campaigns = dbFetchAll($pdo, "SELECT * FROM email_campaigns ORDER BY created_at DESC");

// ── Stats por campanha (email_logs — usa opened_at/clicked_at para rastreamento real) ─
$campStats = [];
foreach ($campaigns as $c) {
    $cid = (int)$c['id'];
    $row = dbFetch($pdo,
        "SELECT
            SUM(status IN ('sent','opened','clicked')) AS delivered,
            SUM(opened_at IS NOT NULL)                 AS opened,
            SUM(clicked_at IS NOT NULL)                AS clicked,
            SUM(status IN ('failed','bounced'))        AS failed
         FROM email_logs WHERE campaign_id=?", [$cid]);
    if (!$row || (int)($row['delivered'] ?? 0) === 0) {
        // Fallback: usa colunas da própria campanha (compatibilidade)
        $campStats[$cid] = [
            'delivered' => (int)$c['sent_count'],
            'opened'    => (int)$c['opened_count'],
            'clicked'   => (int)$c['clicked_count'],
            'failed'    => (int)$c['bounced_count'],
        ];
    } else {
        $campStats[$cid] = [
            'delivered' => (int)($row['delivered'] ?? 0),
            'opened'    => (int)($row['opened']    ?? 0),
            'clicked'   => (int)($row['clicked']   ?? 0),
            'failed'    => (int)($row['failed']     ?? 0),
        ];
    }
}

// ── E-mails inativos (unsubscribed + bounced) ─────────────────────────────────
$inactiveEmails = dbFetchAll($pdo,
    "SELECT n.email, n.name, n.full_name, n.categoria, n.status,
            n.unsubscribed_at, n.subscribed_at,
            COUNT(el.id) AS total_received,
            MAX(el.sent_at) AS last_email
     FROM newsletters n
     LEFT JOIN email_logs el ON el.recipient_email = n.email
     WHERE n.status IN ('unsubscribed','bounced')
     GROUP BY n.id
     ORDER BY n.unsubscribed_at DESC, n.subscribed_at DESC
     LIMIT 200");
$totalInactive = dbFetch($pdo,
    "SELECT COUNT(*) AS n FROM newsletters WHERE status IN ('unsubscribed','bounced')")['n'] ?? 0;

// Páginas de resposta
$nlPages   = [];
foreach (dbFetchAll($pdo, "SELECT * FROM newsletter_pages ORDER BY id") as $p) {
    $nlPages[$p['page_key']] = $p;
}
$editedKey = $_GET['edited'] ?? array_key_first($nlPages) ?? 'welcome';
$editingPage = $nlPages[$editedKey] ?? null;

$msgMap = [
    'sub_saved'       => ['green','Inscrito salvo com sucesso!'],
    'deleted'         => ['green','Inscrito removido.'],
    'campaign_created'=> ['green','Campanha criada!'],
    'campaign_saved'  => ['green','Campanha atualizada!'],
    'campaign_deleted'=> ['red',  'Campanha excluída.'],
    'campaign_reset'  => ['green','Campanha resetada para reenvio.'],
    'page_saved'      => ['green','Página de resposta salva!'],
    'test_sent'       => ['green','E-mail de teste enviado!'],
    'test_failed'     => ['red',  'Falha ao enviar e-mail de teste. Verifique as configurações SMTP.'],
];
$gm = $msgMap[$_GET['msg'] ?? ''] ?? null;
?>

<!-- Mensagem de feedback -->
<?php if ($gm): ?>
<div class="mb-5 px-4 py-3 rounded-xl text-xs font-semibold border flex items-center gap-2
    <?= $gm[0]==='green' ? 'bg-[#006633]/10 border-[#006633]/30 text-[#006633]' : 'bg-red-50 border-red-200 text-red-700' ?>">
    <i class="fas <?= $gm[0]==='green' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
    <?= $gm[1] ?>
</div>
<?php endif; ?>
<?php if ($campError || $subError || $pageError): ?>
<div class="mb-5 px-4 py-3 rounded-xl text-xs font-semibold border bg-red-50 border-red-200 text-red-700">
    <i class="fas fa-exclamation-circle mr-1"></i><?= htmlspecialchars($campError ?: $subError ?: $pageError) ?>
</div>
<?php endif; ?>

<!-- ── Estatísticas ────────────────────────────────────────────────────────── -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php $cards = [
        ['fas fa-users',             'Inscritos',  'subscribed',   $counts['subscribed'],   'from-[#006633] to-[#022E6B]'],
        ['fas fa-user-times',        'Cancelados', 'unsubscribed', $counts['unsubscribed'], 'from-[#EF4444] to-[#001644]'],
        ['fas fa-exclamation-triangle','Bounced',  'bounced',      $counts['bounced'],      'from-[#BF8D1A] to-[#022E6B]'],
        ['fas fa-user-plus',         'Hoje',       'all',          $counts['today'],        'from-[#001644] to-[#022E6B]'],
    ]; foreach ($cards as [$icon,$label,$flt,$count,$grad]): ?>
    <a href="?tab=subscribers&filter=<?= $flt ?>" class="card p-5 hover:-translate-y-1 hover:shadow-lg transition <?= ($tab==='subscribers'&&$filter===$flt)?'ring-2 ring-[#BF8D1A]':'' ?>">
        <div class="w-10 h-10 bg-gradient-to-br <?= $grad ?> rounded-xl flex items-center justify-center text-white mb-3">
            <i class="fas <?= $icon ?> text-sm"></i>
        </div>
        <p class="text-2xl font-bold text-[#001644]"><?= number_format($count) ?></p>
        <p class="text-[10px] text-[#022E6B] font-medium mt-1"><?= $label ?></p>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Abas ───────────────────────────────────────────────────────────────── -->
<div class="flex gap-1 mb-6 border-b border-[#001644]/5 pb-0">
    <?php
    $tabs = [
        'subscribers' => ['fas fa-users',      'Inscritos'],
        'campaigns'   => ['fas fa-bullhorn',    'Campanhas'],
        'pages'       => ['fas fa-envelope-open-text', 'Páginas de Resposta'],
    ];
    foreach ($tabs as $k => [$ico,$lbl]):
    ?>
    <a href="?tab=<?= $k ?>&filter=<?= urlencode($filter) ?>"
       class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-t-xl border border-b-0 transition -mb-px
              <?= $tab===$k ? 'bg-white border-[#001644]/10 text-[#001644] shadow-sm' : 'bg-transparent border-transparent text-[#022E6B]/60 hover:text-[#001644]' ?>">
        <i class="fas <?= $ico ?> text-[#BF8D1A]"></i><?= $lbl ?>
        <?php if ($k==='pages'): ?>
        <span class="px-1.5 py-0.5 bg-[#BF8D1A] text-white text-[9px] rounded-full"><?= count($nlPages) ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA: INSCRITOS
     ═══════════════════════════════════════════════════════════════════════════ -->
<?php if ($tab === 'subscribers'): ?>

<!-- Categorias -->
<div class="card p-5 mb-5">
    <h3 class="text-xs font-bold text-[#001644] mb-3 flex items-center gap-2">
        <i class="fas fa-tags text-[#BF8D1A]"></i>Por Categoria
    </h3>
    <div class="grid grid-cols-3 gap-3">
        <?php foreach ($categorias as $cat): ?>
        <a href="?tab=subscribers&filter=subscribed&cat=<?= urlencode($cat) ?>"
           class="flex items-center justify-between p-3 rounded-xl border <?= $catFlt===$cat?'border-[#BF8D1A] bg-[#BF8D1A]/5':'border-[#001644]/5 hover:border-[#BF8D1A]/50' ?> transition">
            <p class="text-xs font-bold text-[#001644]"><?= htmlspecialchars($cat) ?></p>
            <span class="text-lg font-bold text-[#BF8D1A]"><?= $catCounts[$cat] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php if ($catFlt): ?>
    <a href="?tab=subscribers&filter=<?= $filter ?>" class="inline-flex items-center gap-1 mt-2 text-[10px] text-[#BF8D1A] hover:underline">
        <i class="fas fa-times"></i>Limpar filtro
    </a>
    <?php endif; ?>
</div>

<!-- Toolbar -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <form method="GET" class="flex gap-2 flex-wrap">
        <input type="hidden" name="tab" value="subscribers">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <input type="hidden" name="cat" value="<?= htmlspecialchars($catFlt) ?>">
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#022E6B]/30 text-xs"></i>
            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar e-mail ou nome..." class="form-input pl-8 w-56 py-2 text-xs">
        </div>
        <button class="btn-primary py-2 px-3 text-xs"><i class="fas fa-filter"></i></button>
    </form>
    <div class="flex gap-2">
        <button onclick="openSubModal()" class="btn-gold text-xs"><i class="fas fa-user-plus"></i>Novo Inscrito</button>
        <a href="?action=export" class="btn-primary text-xs"><i class="fas fa-download"></i>CSV</a>
    </div>
</div>

<!-- Status tabs -->
<div class="flex gap-1 mb-4">
    <?php foreach(['subscribed'=>'Inscritos','unsubscribed'=>'Cancelados','bounced'=>'Bounced'] as $v=>$l): ?>
    <a href="?tab=subscribers&filter=<?= $v ?>&busca=<?= urlencode($busca) ?>&cat=<?= urlencode($catFlt) ?>"
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $filter===$v?'bg-[#001644] text-white':'bg-white text-[#022E6B] border border-[#001644]/10 hover:bg-[#001644]/5' ?>">
        <?= $l ?> (<?= number_format($counts[$v]) ?>)
    </a>
    <?php endforeach; ?>
</div>

<!-- Tabela -->
<div class="card overflow-hidden mb-8">
    <table class="w-full">
        <thead><tr>
            <th class="text-left">E-mail / Nome</th>
            <th class="text-left hidden lg:table-cell">Nome Completo</th>
            <th class="text-center hidden md:table-cell">Categoria</th>
            <th class="text-center hidden lg:table-cell">Status</th>
            <th class="text-center hidden md:table-cell">Inscrito em</th>
            <th class="text-center">Ações</th>
        </tr></thead>
        <tbody>
            <?php if (empty($subs)): ?>
            <tr><td colspan="6" class="text-center py-12 text-[#001644]/30">
                <i class="fas fa-envelope text-4xl mb-3 block"></i>Nenhum inscrito encontrado
            </td></tr>
            <?php else: foreach ($subs as $s): ?>
            <tr>
                <td>
                    <p class="font-semibold text-[#001644] text-xs"><?= htmlspecialchars($s['email']) ?></p>
                    <?php if ($s['name']): ?><p class="text-[10px] text-[#022E6B]"><?= htmlspecialchars($s['name']) ?></p><?php endif; ?>
                </td>
                <td class="hidden lg:table-cell text-xs text-[#022E6B]"><?= htmlspecialchars($s['full_name'] ?? '—') ?></td>
                <td class="text-center hidden md:table-cell">
                    <?php if ($s['categoria']): ?>
                    <span class="px-2 py-0.5 bg-[#001644]/5 text-[#001644] text-[9px] font-semibold rounded-full"><?= htmlspecialchars($s['categoria']) ?></span>
                    <?php else: ?><span class="text-[#001644]/20 text-[10px]">—</span><?php endif; ?>
                </td>
                <td class="text-center hidden lg:table-cell">
                    <span class="badge <?= $s['status']==='subscribed'?'badge-green':($s['status']==='bounced'?'badge-red':'badge-gray') ?>"><?= $s['status'] ?></span>
                </td>
                <td class="text-center hidden md:table-cell text-xs text-[#022E6B]"><?= date('d/m/Y', strtotime($s['subscribed_at'])) ?></td>
                <td class="text-center">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="openSubModal(<?= htmlspecialchars(json_encode($s)) ?>)"
                                class="w-7 h-7 rounded-lg bg-[#001644]/5 hover:bg-[#001644] hover:text-white text-[#001644] flex items-center justify-center transition text-xs" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?tab=subscribers&action=delete&id=<?= $s['id'] ?>" onclick="return confirm('Remover inscrição?')"
                           class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-500 hover:text-white text-red-500 flex items-center justify-center transition text-xs">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <div class="flex justify-center gap-2 p-4 border-t border-[#001644]/5">
        <?php for ($i=1;$i<=$pages;$i++): ?>
        <a href="?tab=subscribers&filter=<?= $filter ?>&p=<?= $i ?>&busca=<?= urlencode($busca) ?>&cat=<?= urlencode($catFlt) ?>"
           class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-semibold transition <?= $i===$page_n?'bg-[#001644] text-white':'bg-[#F8FAFC] text-[#001644] hover:bg-[#001644]/10' ?>">
            <?= $i ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; // end subscribers tab ?>


<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA: CAMPANHAS
     ═══════════════════════════════════════════════════════════════════════════ -->
<?php if ($tab === 'campaigns'): ?>

<?php
$statusColors = ['draft'=>'badge-gray','scheduled'=>'badge-gold','sending'=>'badge-blue','sent'=>'badge-green','cancelled'=>'badge-red'];
$statusLabels = ['draft'=>'Rascunho','scheduled'=>'Agendada','sending'=>'Enviando','sent'=>'Enviada','cancelled'=>'Cancelada'];

// Monta arrays para o gráfico — só campanhas com algum envio
$chartCamps  = array_filter($campaigns, fn($c) => (int)$c['sent_count'] > 0 || (int)$c['total_recipients'] > 0 || !empty($campStats[(int)$c['id']]['delivered']));
$chartNames  = [];
$chartSent   = []; $chartOpen = []; $chartClick = []; $chartFail = [];
foreach (array_slice(array_values($chartCamps), 0, 10) as $c) {
    $cid = (int)$c['id'];
    $st  = $campStats[$cid] ?? ['delivered'=>0,'opened'=>0,'clicked'=>0,'failed'=>0];
    $shortName = mb_strlen($c['name']) > 22 ? mb_substr($c['name'],0,20).'…' : $c['name'];
    $chartNames[]  = $shortName;
    $chartSent[]   = $st['delivered'];
    $chartOpen[]   = $st['opened'];
    $chartClick[]  = $st['clicked'];
    $chartFail[]   = $st['failed'];
}
?>

<!-- ── Gráfico de desempenho por campanha ──────────────────────────────────── -->
<div class="card p-5 mb-4">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2">
            <i class="fas fa-chart-bar text-[#BF8D1A]"></i>Desempenho por Campanha
        </h3>
        <div class="flex items-center gap-3 text-[10px] font-semibold flex-wrap">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block" style="background:#001644"></span>Enviados</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block" style="background:#006633"></span>Abertos</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block" style="background:#BF8D1A"></span>Cliques</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block" style="background:#DC2626"></span>Falhas</span>
        </div>
    </div>
    <?php if (empty($chartNames)): ?>
    <div class="text-center py-12 text-[#001644]/25 text-sm">
        <i class="fas fa-chart-bar text-3xl block mb-2"></i>Nenhuma campanha enviada ainda
    </div>
    <?php else: ?>
    <div style="position:relative;height:260px">
        <canvas id="campChart"></canvas>
    </div>
    <!-- Tabela resumo abaixo do gráfico -->
    <div class="mt-4 overflow-x-auto">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b border-[#001644]/08">
                <th class="text-left py-1.5 px-2 text-[#001644]/50 font-semibold">Campanha</th>
                <th class="text-center py-1.5 px-2 text-[#001644]/50 font-semibold">Destinatários</th>
                <th class="text-center py-1.5 px-2 font-semibold" style="color:#001644">Enviados</th>
                <th class="text-center py-1.5 px-2 font-semibold" style="color:#006633">Abertos</th>
                <th class="text-center py-1.5 px-2 font-semibold" style="color:#BF8D1A">Cliques</th>
                <th class="text-center py-1.5 px-2 font-semibold" style="color:#DC2626">Falhas</th>
                <th class="text-center py-1.5 px-2 text-[#001644]/50 font-semibold">Taxa Abertura</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (array_slice(array_values($chartCamps), 0, 10) as $c):
            $cid = (int)$c['id'];
            $st  = $campStats[$cid] ?? ['delivered'=>0,'opened'=>0,'clicked'=>0,'failed'=>0];
            $openRate = $st['delivered'] > 0 ? round($st['opened'] / $st['delivered'] * 100, 1) : 0;
        ?>
        <tr class="border-b border-[#001644]/04 hover:bg-[#F8FAFC]">
            <td class="py-2 px-2 font-semibold text-[#001644]">
                <?= htmlspecialchars(mb_substr($c['name'],0,30)) ?>
                <span class="ml-1 badge <?= $statusColors[$c['status']] ?? 'badge-gray' ?> text-[8px]"><?= $statusLabels[$c['status']] ?? $c['status'] ?></span>
            </td>
            <td class="py-2 px-2 text-center text-[#022E6B]/60"><?= number_format($c['total_recipients']) ?></td>
            <td class="py-2 px-2 text-center font-bold" style="color:#001644"><?= number_format($st['delivered']) ?></td>
            <td class="py-2 px-2 text-center font-bold" style="color:#006633"><?= number_format($st['opened']) ?></td>
            <td class="py-2 px-2 text-center font-bold" style="color:#BF8D1A"><?= number_format($st['clicked']) ?></td>
            <td class="py-2 px-2 text-center font-bold" style="color:#DC2626"><?= number_format($st['failed']) ?></td>
            <td class="py-2 px-2 text-center">
                <div class="flex items-center justify-center gap-2">
                    <div class="w-16 h-1.5 bg-[#001644]/08 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width:<?= $openRate ?>%;background:#006633"></div>
                    </div>
                    <span class="text-[#006633] font-semibold"><?= $openRate ?>%</span>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Lista de campanhas ──────────────────────────────────────────────────── -->
<div class="card overflow-hidden mb-4">
    <div class="flex items-center justify-between p-5 border-b border-[#001644]/5">
        <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2">
            <i class="fas fa-bullhorn text-[#BF8D1A]"></i>Campanhas de E-mail
        </h3>
        <button onclick="openCampModal()" class="btn-primary text-xs"><i class="fas fa-plus"></i>Nova Campanha</button>
    </div>

    <?php if (empty($campaigns)): ?>
    <div class="p-16 text-center text-[#001644]/30">
        <i class="fas fa-paper-plane text-4xl mb-3 block"></i>
        <p class="text-sm">Nenhuma campanha criada</p>
    </div>
    <?php else: ?>
    <div class="divide-y divide-[#001644]/5">
        <?php foreach ($campaigns as $camp):
            $cid = (int)$camp['id'];
            $st  = $campStats[$cid] ?? ['delivered'=>0,'opened'=>0,'clicked'=>0,'failed'=>0];
            $openRate  = $st['delivered'] > 0 ? round($st['opened']  / $st['delivered'] * 100) : 0;
            $clickRate = $st['delivered'] > 0 ? round($st['clicked'] / $st['delivered'] * 100) : 0;
            $campFilter = null;
            if ($camp['segment_filter']) {
                $sf = json_decode($camp['segment_filter'], true);
                $campFilter = isset($sf['categorias']) ? implode(', ', $sf['categorias']) : null;
            }
        ?>
        <div class="px-5 py-4 hover:bg-[#F8FAFC] transition">
            <!-- Linha superior: nome + badges + ações -->
            <div class="flex items-start gap-3 mb-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                        <h4 class="font-bold text-[#001644] text-sm"><?= htmlspecialchars($camp['name']) ?></h4>
                        <span class="badge <?= $statusColors[$camp['status']] ?? 'badge-gray' ?>"><?= $statusLabels[$camp['status']] ?? $camp['status'] ?></span>
                        <?php if ($campFilter): ?>
                        <span class="px-2 py-0.5 bg-[#BF8D1A]/10 text-[#BF8D1A] text-[9px] font-semibold rounded-full">
                            <i class="fas fa-filter mr-0.5"></i><?= htmlspecialchars($campFilter) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[10px] text-[#022E6B]/60">📧 <?= htmlspecialchars($camp['subject']) ?> &nbsp;·&nbsp; <?= date('d/m/Y', strtotime($camp['created_at'])) ?></p>
                </div>
                <div class="flex gap-1.5 flex-shrink-0 flex-wrap justify-end">
                    <?php if (in_array($camp['status'], ['draft','scheduled'])): ?>
                    <button onclick="sendCampaign(<?= $cid ?>, '<?= htmlspecialchars(addslashes($camp['name'])) ?>', <?= htmlspecialchars(json_encode($camp['segment_filter']), ENT_QUOTES) ?>)"
                            class="px-3 py-1.5 text-[10px] font-semibold bg-[#006633] text-white rounded-xl hover:bg-[#001644] transition flex items-center gap-1">
                        <i class="fas fa-paper-plane"></i>Enviar
                    </button>
                    <?php endif; ?>
                    <a href="?tab=campaigns&action=resend_campaign&id=<?= $cid ?>" onclick="return confirm('Resetar status e reenviar a campanha?')"
                       class="px-3 py-1.5 text-[10px] font-semibold bg-[#BF8D1A]/10 text-[#BF8D1A] hover:bg-[#BF8D1A] hover:text-white rounded-xl transition flex items-center gap-1">
                        <i class="fas fa-redo"></i>Reenviar
                    </a>
                    <button onclick="openCampModal(<?= htmlspecialchars(json_encode($camp)) ?>)"
                            class="px-3 py-1.5 text-[10px] font-semibold bg-[#001644]/5 text-[#001644] hover:bg-[#001644] hover:text-white rounded-xl transition flex items-center gap-1">
                        <i class="fas fa-edit"></i>Editar
                    </button>
                    <a href="?tab=campaigns&action=delete_campaign&id=<?= $cid ?>" onclick="return confirm('Excluir campanha?')"
                       class="px-3 py-1.5 text-[10px] font-semibold bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition flex items-center gap-1">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
            <!-- Linha de métricas visuais -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <!-- Enviados -->
                <div class="bg-[#001644]/03 rounded-xl px-3 py-2 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#001644]/08 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-paper-plane text-[10px] text-[#001644]"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-[#001644]"><?= number_format($st['delivered']) ?></div>
                        <div class="text-[9px] text-[#022E6B]/50 leading-tight">Enviados</div>
                    </div>
                </div>
                <!-- Abertos -->
                <div class="bg-[#006633]/04 rounded-xl px-3 py-2 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#006633]/10 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-envelope-open text-[10px] text-[#006633]"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-[#006633]"><?= number_format($st['opened']) ?></div>
                        <div class="text-[9px] text-[#022E6B]/50 leading-tight">Abertos · <span class="font-semibold"><?= $openRate ?>%</span></div>
                    </div>
                </div>
                <!-- Cliques -->
                <div class="bg-[#BF8D1A]/04 rounded-xl px-3 py-2 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#BF8D1A]/10 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-mouse-pointer text-[10px] text-[#BF8D1A]"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-[#BF8D1A]"><?= number_format($st['clicked']) ?></div>
                        <div class="text-[9px] text-[#022E6B]/50 leading-tight">Cliques · <span class="font-semibold"><?= $clickRate ?>%</span></div>
                    </div>
                </div>
                <!-- Falhas -->
                <div class="bg-red-50 rounded-xl px-3 py-2 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-[10px] text-red-500"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-red-500"><?= number_format($st['failed']) ?></div>
                        <div class="text-[9px] text-[#022E6B]/50 leading-tight">Falhas/Bounce</div>
                    </div>
                </div>
            </div>
            <!-- Mini barra de progresso -->
            <?php if ($st['delivered'] > 0): ?>
            <div class="mt-2.5 flex gap-0.5 h-1.5 rounded-full overflow-hidden bg-[#001644]/05">
                <?php
                $tot = $st['delivered'];
                $wO  = round($st['opened']  / $tot * 100);
                $wC  = round($st['clicked'] / $tot * 100);
                $wF  = round($st['failed']  / $tot * 100);
                $wD  = max(0, 100 - $wO - $wC - $wF);
                ?>
                <div style="width:<?= $wO ?>%;background:#006633" title="Abertos"></div>
                <div style="width:<?= $wC ?>%;background:#BF8D1A" title="Cliques"></div>
                <div style="width:<?= $wF ?>%;background:#DC2626" title="Falhas"></div>
                <div style="width:<?= $wD ?>%;background:#001644;opacity:.15" title="Entregues s/ abertura"></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ── Painel: E-mails inativos ────────────────────────────────────────────── -->
<div class="card overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b border-[#001644]/5">
        <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2">
            <i class="fas fa-user-slash text-red-400"></i>E-mails Inativos
            <span class="px-2 py-0.5 bg-red-50 text-red-500 text-[10px] font-bold rounded-full"><?= number_format($totalInactive) ?></span>
        </h3>
        <span class="text-[10px] text-[#022E6B]/50">Cancelados e bounced · últimos 200</span>
    </div>

    <?php if (empty($inactiveEmails)): ?>
    <div class="p-12 text-center text-[#001644]/25 text-sm">
        <i class="fas fa-check-circle text-3xl block mb-2 text-green-400"></i>Nenhum e-mail inativo
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
    <table class="w-full text-xs">
        <thead>
            <tr class="bg-[#F8FAFC] border-b border-[#001644]/08">
                <th class="text-left py-2.5 px-4 text-[#001644]/50 font-semibold">E-mail</th>
                <th class="text-left py-2.5 px-3 text-[#001644]/50 font-semibold">Nome</th>
                <th class="text-left py-2.5 px-3 text-[#001644]/50 font-semibold">Categoria</th>
                <th class="text-center py-2.5 px-3 text-[#001644]/50 font-semibold">Status</th>
                <th class="text-center py-2.5 px-3 text-[#001644]/50 font-semibold">E-mails rcb.</th>
                <th class="text-center py-2.5 px-3 text-[#001644]/50 font-semibold">Último e-mail</th>
                <th class="text-center py-2.5 px-3 text-[#001644]/50 font-semibold">Inativo desde</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#001644]/04">
        <?php foreach ($inactiveEmails as $ie):
            $nome = trim($ie['full_name'] ?: $ie['name'] ?: '—');
            $isBounced = $ie['status'] === 'bounced';
            $inactiveSince = $ie['unsubscribed_at'] ?: $ie['subscribed_at'];
        ?>
        <tr class="hover:bg-[#F8FAFC] transition">
            <td class="py-2.5 px-4 font-mono text-[10px] text-[#001644]"><?= htmlspecialchars($ie['email']) ?></td>
            <td class="py-2.5 px-3 text-[#022E6B]/70"><?= htmlspecialchars($nome) ?></td>
            <td class="py-2.5 px-3 text-[#022E6B]/50"><?= $ie['categoria'] ? htmlspecialchars($ie['categoria']) : '—' ?></td>
            <td class="py-2.5 px-3 text-center">
                <?php if ($isBounced): ?>
                <span class="px-2 py-0.5 bg-orange-50 text-orange-600 text-[9px] font-bold rounded-full">
                    <i class="fas fa-exclamation-triangle mr-0.5"></i>Bounce
                </span>
                <?php else: ?>
                <span class="px-2 py-0.5 bg-red-50 text-red-500 text-[9px] font-bold rounded-full">
                    <i class="fas fa-times mr-0.5"></i>Cancelado
                </span>
                <?php endif; ?>
            </td>
            <td class="py-2.5 px-3 text-center text-[#022E6B]/60 font-medium"><?= number_format($ie['total_received']) ?></td>
            <td class="py-2.5 px-3 text-center text-[#022E6B]/50">
                <?= $ie['last_email'] ? date('d/m/Y', strtotime($ie['last_email'])) : '—' ?>
            </td>
            <td class="py-2.5 px-3 text-center text-[#022E6B]/50">
                <?= $inactiveSince ? date('d/m/Y', strtotime($inactiveSince)) : '—' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if ($totalInactive > 200): ?>
    <p class="text-[10px] text-[#022E6B]/40 text-center py-3">
        Exibindo 200 de <?= number_format($totalInactive) ?> e-mails inativos
    </p>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Chart.js para gráfico de campanhas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function(){
    const ctx = document.getElementById('campChart');
    if (!ctx) return;
    const labels = <?= json_encode($chartNames, JSON_UNESCAPED_UNICODE) ?>;
    if (!labels.length) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Enviados',
                    data: <?= json_encode($chartSent) ?>,
                    backgroundColor: 'rgba(0,22,68,0.85)',
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Abertos',
                    data: <?= json_encode($chartOpen) ?>,
                    backgroundColor: 'rgba(0,102,51,0.85)',
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Cliques',
                    data: <?= json_encode($chartClick) ?>,
                    backgroundColor: 'rgba(191,141,26,0.85)',
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Falhas',
                    data: <?= json_encode($chartFail) ?>,
                    backgroundColor: 'rgba(220,38,38,0.75)',
                    borderRadius: 4,
                    borderSkipped: false,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        afterBody: function(items) {
                            const sent = items[0]?.chart.data.datasets[0].data[items[0].dataIndex] || 0;
                            const opened = items[0]?.chart.data.datasets[1].data[items[0].dataIndex] || 0;
                            if (sent > 0) return `Taxa abertura: ${Math.round(opened/sent*100)}%`;
                            return '';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' },
                     ticks: { precision: 0, font: { size: 10 } } }
            }
        }
    });
})();
</script>

<?php endif; // end campaigns tab ?>


<!-- ═══════════════════════════════════════════════════════════════════════════
     ABA: PÁGINAS DE RESPOSTA
     ═══════════════════════════════════════════════════════════════════════════ -->
<?php if ($tab === 'pages'): ?>

<div class="grid lg:grid-cols-4 gap-6">

    <!-- Coluna lateral: lista de páginas -->
    <div class="lg:col-span-1 space-y-2">
        <p class="text-[10px] font-bold text-[#022E6B]/60 uppercase tracking-wider mb-3 px-1">
            E-mails automáticos
        </p>
        <?php foreach ($nlPages as $key => $pg): ?>
        <a href="?tab=pages&edited=<?= $key ?>"
           class="flex items-start gap-3 p-3 rounded-xl border transition cursor-pointer
                  <?= $editedKey===$key
                        ? 'bg-[#001644] text-white border-[#001644] shadow-md'
                        : 'bg-white border-[#001644]/5 hover:border-[#BF8D1A]/40 text-[#001644]' ?>">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5
                        <?= $editedKey===$key ? 'bg-white/20' : 'bg-[#001644]/5' ?>">
                <?php
                $icons = [
                    'welcome'           => 'fa-hand-wave',
                    'reactivation'      => 'fa-redo',
                    'unsubscribe'       => 'fa-user-minus',
                    'already_subscribed'=> 'fa-check-double',
                ];
                ?>
                <i class="fas <?= $icons[$key] ?? 'fa-envelope' ?> text-xs
                   <?= $editedKey===$key ? 'text-[#BF8D1A]' : 'text-[#001644]' ?>"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold leading-tight"><?= htmlspecialchars($pg['page_label']) ?></p>
                <p class="text-[9px] mt-0.5 truncate <?= $editedKey===$key ? 'text-white/60' : 'text-[#022E6B]/50' ?>">
                    <?= htmlspecialchars($pg['subject']) ?>
                </p>
            </div>
        </a>
        <?php endforeach; ?>

        <!-- Info sobre variáveis -->
        <div class="mt-4 p-3 bg-[#BF8D1A]/5 border border-[#BF8D1A]/20 rounded-xl">
            <p class="text-[10px] font-bold text-[#BF8D1A] mb-2">
                <i class="fas fa-code mr-1"></i>Variáveis disponíveis
            </p>
            <div class="space-y-1">
                <?php foreach (['{{nome}}','{{email}}','{{categoria}}','{{unsubscribe_url}}'] as $var): ?>
                <code class="block text-[10px] bg-white px-2 py-1 rounded-lg border border-[#BF8D1A]/20 text-[#001644] font-mono"><?= $var ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Coluna principal: editor -->
    <div class="lg:col-span-3">
        <?php if ($editingPage): ?>
        <div class="card overflow-hidden">
            <!-- Header do editor -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#001644]/5 bg-gradient-to-r from-[#001644]/3 to-transparent">
                <div>
                    <h3 class="font-bold text-[#001644] text-sm"><?= htmlspecialchars($editingPage['page_label']) ?></h3>
                    <p class="text-[10px] text-[#022E6B]/60 mt-0.5">Editando e-mail automático · chave: <code class="bg-[#001644]/5 px-1 rounded"><?= $editedKey ?></code></p>
                </div>
                <!-- Botão preview -->
                <button onclick="togglePagePreview()" class="px-3 py-1.5 text-[10px] font-semibold text-[#001644] border border-[#001644]/10 rounded-xl hover:bg-[#001644] hover:text-white transition flex items-center gap-1.5">
                    <i class="fas fa-eye"></i>Preview
                </button>
            </div>

            <!-- Preview (oculto por padrão) -->
            <div id="pagePreviewWrapper" class="hidden border-b border-[#001644]/5">
                <div class="flex items-center justify-between px-6 py-3 bg-[#F8FAFC]">
                    <p class="text-[10px] font-bold text-[#022E6B]"><i class="fas fa-eye text-[#BF8D1A] mr-1"></i>Preview com dados de exemplo</p>
                    <button onclick="refreshPagePreview()" class="text-[10px] text-[#BF8D1A] hover:underline"><i class="fas fa-sync mr-1"></i>Atualizar</button>
                </div>
                <iframe id="pagePreviewFrame" class="w-full" style="height:450px;border:none;background:#F8FAFC;"></iframe>
            </div>

            <form method="POST" action="/crcap/admin/newsletter.php?tab=pages&edited=<?= $editedKey ?>" class="p-6 space-y-5" id="pageEditForm">
                <input type="hidden" name="form_action" value="save_newsletter_page">
                <input type="hidden" name="page_key" value="<?= htmlspecialchars($editedKey) ?>">
                <input type="hidden" name="page_label" value="<?= htmlspecialchars($editingPage['page_label']) ?>">

                <!-- Assunto -->
                <div>
                    <label class="form-label">Assunto do e-mail *</label>
                    <input type="text" name="page_subject" required class="form-input"
                           value="<?= htmlspecialchars($editingPage['subject']) ?>"
                           placeholder="Ex: Bem-vindo(a) à Newsletter do CRCAP!">
                </div>

                <!-- Editor HTML -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="form-label mb-0">Conteúdo HTML do e-mail *</label>
                        <div class="flex gap-2">
                            <button type="button" onclick="togglePageEditorMode()"
                                    class="flex items-center gap-1 text-[10px] text-[#022E6B] border border-[#001644]/10 px-2 py-1 rounded-lg hover:bg-[#001644] hover:text-white transition" id="pageEditorModeBtn">
                                <i class="fas fa-code"></i> Modo HTML
                            </button>
                        </div>
                    </div>

                    <!-- Editor visual (Quill) -->
                    <div id="pageQuillWrapper" class="border border-[#001644]/10 rounded-xl overflow-hidden bg-white">
                        <div id="pageQuillEditor" style="min-height:380px;font-size:13px;"></div>
                    </div>

                    <!-- Editor HTML raw (oculto por padrão) -->
                    <textarea id="pageHtmlRaw"
                              class="hidden w-full p-4 font-mono text-xs border border-[#BF8D1A] rounded-xl focus:outline-none resize-y"
                              rows="20"
                              placeholder="Cole seu HTML aqui..."><?= htmlspecialchars($editingPage['html_content']) ?></textarea>

                    <!-- Hidden field preenchido pelo JS (nao escapado) -->
                    <textarea id="pageHtmlHidden" name="page_html" class="hidden"></textarea>

                    <!-- Dados do template passados com seguranca ao JS -->
                    <script id="pageInitData" type="application/json"><?= json_encode([
                        'html'    => $editingPage['html_content'],
                        'subject' => $editingPage['subject'],
                    ], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
                </div>

                <!-- Ações do formulário -->
                <div class="flex flex-wrap gap-3 pt-2 border-t border-[#001644]/5">
                    <button type="submit" class="btn-primary flex-1 justify-center min-w-[160px]">
                        <i class="fas fa-save"></i>Salvar Alterações
                    </button>

                    <!-- Enviar teste -->
                    <button type="button" onclick="document.getElementById('testEmailPanel').classList.toggle('hidden')"
                            class="px-4 py-2.5 text-xs font-semibold text-[#BF8D1A] border border-[#BF8D1A]/30 rounded-xl hover:bg-[#BF8D1A] hover:text-white transition flex items-center gap-2">
                        <i class="fas fa-paper-plane"></i>Enviar teste
                    </button>

                    <!-- Restaurar padrão -->
                    <button type="button" onclick="if(confirm('Restaurar o conteúdo padrão? Suas alterações serão perdidas.')) restoreDefault('<?= $editedKey ?>')"
                            class="px-4 py-2.5 text-xs font-semibold text-[#022E6B]/60 border border-[#001644]/10 rounded-xl hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition flex items-center gap-2">
                        <i class="fas fa-history"></i>Restaurar padrão
                    </button>
                </div>
            </form>

            <!-- Painel de teste de e-mail -->
            <div id="testEmailPanel" class="hidden px-6 pb-6">
                <div class="bg-[#F8FAFC] border border-[#001644]/10 rounded-xl p-4">
                    <p class="text-xs font-bold text-[#001644] mb-3 flex items-center gap-2">
                        <i class="fas fa-flask text-[#BF8D1A]"></i>Enviar e-mail de teste
                    </p>
                    <form method="POST" action="/crcap/admin/newsletter.php?tab=pages&edited=<?= $editedKey ?>" class="flex gap-3">
                        <input type="hidden" name="form_action" value="test_newsletter_page">
                        <input type="hidden" name="page_key" value="<?= htmlspecialchars($editedKey) ?>">
                        <input type="email" name="test_email" required class="form-input flex-1 text-xs"
                               placeholder="seuemail@exemplo.com" value="<?= htmlspecialchars($adminUser['email'] ?? '') ?>">
                        <button type="submit" class="btn-gold text-xs flex-shrink-0">
                            <i class="fas fa-paper-plane"></i>Enviar teste
                        </button>
                    </form>
                    <p class="text-[10px] text-[#022E6B]/50 mt-2">
                        O e-mail será enviado com dados fictícios para testar a aparência.
                    </p>
                </div>
            </div>
        </div>

        <!-- Info sobre quando este e-mail é disparado -->
        <div class="mt-4 p-4 bg-white border border-[#001644]/5 rounded-xl">
            <p class="text-[10px] font-bold text-[#001644] mb-2 flex items-center gap-2">
                <i class="fas fa-info-circle text-[#BF8D1A]"></i>Quando este e-mail é disparado
            </p>
            <?php
            $triggerInfo = [
                'welcome'           => 'Enviado automaticamente quando alguém se inscreve pela primeira vez no formulário da home ou via API.',
                'reactivation'      => 'Enviado quando um usuário que havia cancelado a inscrição volta a se inscrever.',
                'unsubscribe'       => 'Enviado ao usuário que solicita o cancelamento via link de descadastro ou API.',
                'already_subscribed'=> 'Enviado quando alguém tenta se inscrever com um e-mail que já está ativo na lista.',
            ];
            ?>
            <p class="text-[10px] text-[#022E6B]/70"><?= $triggerInfo[$editedKey] ?? 'Disparado automaticamente pelo sistema.' ?></p>
        </div>

        <?php else: ?>
        <div class="card p-12 text-center text-[#001644]/30">
            <i class="fas fa-envelope-open-text text-4xl mb-3 block"></i>
            <p class="text-sm">Selecione uma página de resposta para editar</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; // end pages tab ?>


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAIS GLOBAIS (presentes em todas as abas)
     ═══════════════════════════════════════════════════════════════════════════ -->

<!-- Modal: Inscrito -->
<div id="subModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-[#001644]/5">
            <h3 class="font-bold text-[#001644] text-sm" id="subModalTitle">Novo Inscrito</h3>
            <button onclick="closeSubModal()" class="text-[#001644]/30 hover:text-[#001644] transition"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="save_subscriber">
            <input type="hidden" name="sub_id" id="sub_id" value="0">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">E-mail *</label>
                    <input type="email" name="email" id="sub_email" required class="form-input" placeholder="email@exemplo.com">
                </div>
                <div>
                    <label class="form-label">Nome / Apelido</label>
                    <input type="text" name="name" id="sub_name" class="form-input" placeholder="Como prefere ser chamado">
                </div>
            </div>
            <div>
                <label class="form-label">Nome Completo</label>
                <input type="text" name="full_name" id="sub_full_name" class="form-input" placeholder="Nome completo">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Categoria</label>
                    <select name="categoria" id="sub_categoria" class="form-input">
                        <option value="">Não informado</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" id="sub_status" class="form-input">
                        <option value="subscribed">Inscrito</option>
                        <option value="unsubscribed">Cancelado</option>
                        <option value="bounced">Bounced</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary flex-1 justify-center"><i class="fas fa-save"></i>Salvar</button>
                <button type="button" onclick="closeSubModal()" class="flex-1 py-2.5 text-xs border border-[#001644]/10 rounded-xl hover:bg-[#F8FAFC] transition">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Input de upload de imagem FORA de qualquer form para não conflitar com submit -->
<input type="file" id="campImgFileInput" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden"
       onchange="campUploadImg(this)">

<!-- ══ MODAL CAMPANHA (editor de e-mail em blocos) ═════════════════════════════ -->
<div id="campModal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-2">
  <div class="bg-white rounded-2xl shadow-2xl flex flex-col w-full" style="max-width:1100px;height:96vh">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-3 border-b border-[#001644]/08 flex-shrink-0 bg-[#001644] rounded-t-2xl">
      <div class="flex items-center gap-3">
        <i class="fas fa-envelope-open-text text-[#BF8D1A]"></i>
        <h3 class="font-bold text-white text-sm" id="campModalTitle">Nova Campanha</h3>
      </div>
      <div class="flex items-center gap-2">
        <button onclick="campTogglePreview()" id="campPreviewBtn"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/10 hover:bg-white/20 text-white transition">
          <i class="fas fa-eye text-[10px]"></i>Preview
        </button>
        <button onclick="campToggleHtml()" id="campHtmlBtn"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white/10 hover:bg-white/20 text-white transition">
          <i class="fas fa-code text-[10px]"></i>HTML
        </button>
        <button onclick="closeCampModal()" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/10 hover:bg-red-500/80 text-white transition">
          <i class="fas fa-times text-sm"></i>
        </button>
      </div>
    </div>

    <!-- Body: sidebar config + área editor -->
    <div class="flex flex-1 overflow-hidden">

      <!-- ── Sidebar configurações ── -->
      <form method="POST" action="/crcap/admin/newsletter.php?tab=campaigns"
            id="campForm" class="w-72 flex-shrink-0 flex flex-col border-r border-[#001644]/08 overflow-y-auto bg-[#F8FAFC]">
        <input type="hidden" name="form_action" value="save_campaign">
        <input type="hidden" name="camp_id" id="camp_id" value="0">
        <input type="hidden" name="campaign_html" id="camp_html_hidden">
        <input type="hidden" name="campaign_blocks_json" id="camp_blocks_json_hidden">

        <div class="p-4 space-y-3">
          <p class="text-[9px] font-bold text-[#001644]/40 uppercase tracking-widest">Configurações</p>

          <div>
            <label class="form-label">Nome da Campanha *</label>
            <input type="text" name="campaign_name" id="camp_name" required class="form-input text-xs" placeholder="Newsletter Março 2026">
          </div>
          <div>
            <label class="form-label">Assunto do E-mail *</label>
            <input type="text" name="campaign_subject" id="camp_subject" required class="form-input text-xs" placeholder="Novidades do CRCAP">
          </div>
          <div>
            <label class="form-label">Remetente</label>
            <input type="text" name="from_name" id="camp_from_name" value="CRCAP" class="form-input text-xs" placeholder="Nome">
          </div>
          <div>
            <label class="form-label">E-mail Remetente</label>
            <input type="email" name="from_email" id="camp_from_email" value="noticias@crcap.org.br" class="form-input text-xs">
          </div>

          <div>
            <label class="form-label">Segmento (opcional)</label>
            <p class="text-[9px] text-[#022E6B]/50 mb-1.5">Deixe vazio para enviar a todos.</p>
            <div class="space-y-1">
              <?php foreach ($categorias as $cat): ?>
              <label class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-[#001644]/08 hover:border-[#BF8D1A]/50 cursor-pointer bg-white text-xs">
                <input type="checkbox" name="camp_categorias[]" class="camp-cat-check accent-[#001644]" value="<?= htmlspecialchars($cat) ?>">
                <?= htmlspecialchars($cat) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <hr class="border-[#001644]/08">

          <!-- Blocos disponíveis -->
          <p class="text-[9px] font-bold text-[#001644]/40 uppercase tracking-widest">Adicionar Bloco</p>
          <div class="grid grid-cols-2 gap-1.5">
            <?php
            $blocks = [
              ['id'=>'header',  'icon'=>'fa-heading',      'label'=>'Cabeçalho'],
              ['id'=>'text',    'icon'=>'fa-align-left',   'label'=>'Texto'],
              ['id'=>'image',   'icon'=>'fa-image',        'label'=>'Imagem'],
              ['id'=>'button',  'icon'=>'fa-hand-pointer', 'label'=>'Botão'],
              ['id'=>'divider', 'icon'=>'fa-minus',        'label'=>'Divisor'],
              ['id'=>'footer',  'icon'=>'fa-shoe-prints',  'label'=>'Rodapé'],
            ];
            foreach ($blocks as $b):
            ?>
            <button type="button" onclick="campAddBlock('<?= $b['id'] ?>')"
                    class="flex flex-col items-center gap-1 p-2 rounded-xl border-2 border-dashed border-[#001644]/15 hover:border-[#BF8D1A]/60 hover:bg-[#BF8D1A]/05 text-[#001644]/50 hover:text-[#001644] transition text-[10px] font-semibold">
              <i class="fas <?= $b['icon'] ?> text-sm text-[#BF8D1A]"></i>
              <?= $b['label'] ?>
            </button>
            <?php endforeach; ?>
          </div>

          <hr class="border-[#001644]/08">

          <!-- Variáveis -->
          <p class="text-[9px] font-bold text-[#001644]/40 uppercase tracking-widest">Variáveis</p>
          <div class="flex flex-wrap gap-1">
            <?php foreach (['{{nome}}','{{email}}','{{categoria}}','{{unsubscribe_url}}'] as $v): ?>
            <button type="button" onclick="campCopyVar('<?= $v ?>')"
                    title="Copiar variável"
                    class="text-[9px] px-2 py-1 bg-white border border-[#001644]/10 hover:border-[#BF8D1A]/50 hover:bg-[#BF8D1A]/05 text-[#001644]/60 rounded-lg font-mono transition">
              <?= $v ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Botão salvar fixo no fundo -->
        <div class="mt-auto p-4 border-t border-[#001644]/08 bg-[#F8FAFC]">
          <button type="submit" class="btn-primary w-full justify-center text-xs">
            <i class="fas fa-save"></i>Salvar Campanha
          </button>
        </div>
      </form>

      <!-- ── Área central: editor de blocos ── -->
      <div class="flex-1 overflow-y-auto bg-[#E8ECF0] relative" id="campEditorArea">

        <!-- Canvas do e-mail -->
        <div class="py-6 px-4 min-h-full">
          <div id="campEmailCanvas"
               class="mx-auto bg-white shadow-lg"
               style="width:600px;min-height:400px;font-family:Arial,sans-serif">
            <!-- blocos renderizados aqui -->
          </div>
        </div>

        <!-- Painel HTML raw (oculto por padrão) -->
        <div id="campHtmlPanel" class="hidden absolute inset-0 bg-[#1e1e2e] p-4 overflow-y-auto">
          <textarea id="campHtmlRaw"
                    class="w-full h-full bg-transparent text-green-300 font-mono text-xs resize-none focus:outline-none"
                    spellcheck="false"
                    placeholder="<!-- HTML do e-mail -->"></textarea>
        </div>

        <!-- Painel Preview (oculto por padrão) -->
        <div id="campPreviewPanel" class="hidden absolute inset-0 bg-[#E8ECF0] p-4 overflow-y-auto">
          <div class="text-center mb-3">
            <span class="text-[10px] text-[#001644]/50 bg-white border border-[#001644]/10 px-3 py-1 rounded-full">
              <i class="fas fa-eye text-[#BF8D1A] mr-1"></i>Pré-visualização — variáveis substituídas por dados de exemplo
            </span>
          </div>
          <div class="mx-auto bg-white shadow-lg rounded-lg overflow-hidden" style="width:600px">
            <iframe id="campPreviewFrame" src="about:blank" class="w-full border-0" style="height:700px"></iframe>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>

<!-- Sub-modal: editar bloco -->
<div id="campBlockModal" class="hidden fixed inset-0 bg-black/40 z-[60] flex items-center justify-center p-4"
     onclick="if(event.target===this)closeCampBlockModal()">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#001644]/08 flex-shrink-0">
      <h4 class="font-bold text-[#001644] text-sm" id="campBlockModalTitle">Editar Bloco</h4>
      <button onclick="closeCampBlockModal()" class="text-[#001644]/30 hover:text-red-400 transition text-lg">&times;</button>
    </div>
    <div id="campBlockModalBody" class="p-5 overflow-y-auto flex-1 space-y-3"></div>
    <div class="px-5 pb-5 flex gap-2 flex-shrink-0">
      <button type="button" onclick="campBlockApply()"
              class="btn-primary flex-1 justify-center text-xs"><i class="fas fa-check"></i>Aplicar</button>
      <button type="button" onclick="closeCampBlockModal()"
              class="flex-1 py-2.5 text-xs border border-[#001644]/10 rounded-xl hover:bg-[#F8FAFC] text-[#001644]/60 font-semibold transition">Cancelar</button>
    </div>
  </div>
</div>

<!-- Modal: Enviar campanha -->
<div id="sendModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="font-bold text-[#001644] text-base mb-2" id="modalTitle">Enviar Campanha</h3>
        <p class="text-xs text-[#022E6B] mb-3">Esta ação enviará e-mails para os assinantes confirmados.</p>
        <div id="sendFilterInfo" class="hidden"></div>
        <div id="sendProgress" class="hidden mb-4">
            <div class="flex items-center gap-2 text-xs text-[#022E6B] mb-2">
                <i class="fas fa-spinner fa-spin text-[#BF8D1A]"></i>
                <span id="progressText">Iniciando envio…</span>
            </div>
            <div class="h-2 bg-[#F8FAFC] rounded-full overflow-hidden">
                <div id="progressBar" class="h-full bg-[#BF8D1A] rounded-full transition-all" style="width:0%"></div>
            </div>
        </div>
        <div id="sendResult" class="hidden text-xs rounded-lg px-3 py-2 mb-4"></div>
        <div class="flex gap-3" id="modalActions">
            <button onclick="confirmSend()" class="btn-primary flex-1 justify-center">
                <i class="fas fa-paper-plane"></i>Confirmar Envio
            </button>
            <button onclick="closeSendModal()" class="flex-1 py-2.5 text-xs font-semibold border-2 border-[#001644]/20 rounded-xl hover:border-red-300 transition">
                Cancelar
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════════════════════ -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
// ── Modal Inscritos ───────────────────────────────────────────────────────────
function openSubModal(data) {
    const m = document.getElementById('subModal');
    if (data) {
        document.getElementById('subModalTitle').textContent = 'Editar Inscrito';
        document.getElementById('sub_id').value        = data.id || 0;
        document.getElementById('sub_email').value     = data.email || '';
        document.getElementById('sub_name').value      = data.name || '';
        document.getElementById('sub_full_name').value = data.full_name || '';
        document.getElementById('sub_categoria').value = data.categoria || '';
        document.getElementById('sub_status').value    = data.status || 'subscribed';
    } else {
        document.getElementById('subModalTitle').textContent = 'Novo Inscrito';
        document.getElementById('sub_id').value = 0;
        ['sub_email','sub_name','sub_full_name'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('sub_categoria').value = '';
        document.getElementById('sub_status').value = 'subscribed';
    }
    m.classList.remove('hidden');
}
function closeSubModal() { document.getElementById('subModal').classList.add('hidden'); }

// ══════════════════════════════════════════════════════════════════════════════
// EDITOR DE E-MAIL EM BLOCOS — gera HTML tabular limpo (compatível com Gmail/Outlook)
// ══════════════════════════════════════════════════════════════════════════════
const BASE_URL = 'https://artemidiaweb.com.br/crcap';
let _campBlocks   = [];   // array de objetos bloco
let _campEditIdx  = null; // índice do bloco sendo editado
let _campHtmlMode = false;
let _campPrevMode = false;

// ── Definições de bloco ───────────────────────────────────────────────────────
const BLOCK_DEFAULTS = {
    header: {
        bgColor: '#001644', textColor: '#ffffff', logoUrl: '',
        title: 'CRCAP – Conselho Regional', subtitle: ''
    },
    text: {
        bgColor: '#ffffff', textColor: '#022E6B',
        content: '<p style="margin:0 0 12px 0">Olá <strong>{{nome}}</strong>,</p><p style="margin:0">Escreva seu conteúdo aqui.</p>',
        fontSize: '14', lineHeight: '1.7'
    },
    image: {
        src: '', alt: 'Imagem', link: '', width: '100%',
        align: 'center', bgColor: '#ffffff', caption: ''
    },
    button: {
        label: 'Acessar o Portal CRCAP', url: BASE_URL+'/index.php',
        bgColor: '#001644', textColor: '#ffffff', align: 'center',
        borderRadius: '10', containerBg: '#ffffff'
    },
    divider: { color: '#e2e8f0', thickness: '1', marginV: '8', bgColor: '#ffffff' },
    footer: {
        bgColor: '#F8FAFC', textColor: '#94a3b8',
        text: '© <?= date("Y") ?> CRCAP – Todos os direitos reservados.<br>Para cancelar sua inscrição, <a href="{{unsubscribe_url}}" style="color:#BF8D1A">clique aqui</a>.'
    }
};

// ── Renderizar bloco → HTML inline para e-mail ────────────────────────────────
function campBlockToHtml(b) {
    const W = 600;
    switch(b.type) {
        case 'header': {
            const logoHtml = b.logoUrl
                ? `<img src="${b.logoUrl}" alt="Logo" style="height:40px;display:block;margin:0 auto 10px auto">`
                : '';
            return `<table width="${W}" cellpadding="0" cellspacing="0" style="border:0;background:${b.bgColor}"><tr>
<td align="center" style="padding:28px 32px;background:${b.bgColor}">
${logoHtml}<span style="color:${b.textColor};font-size:20px;font-weight:700;font-family:Arial,sans-serif">${b.title}</span>
${b.subtitle ? `<div style="color:${b.textColor};opacity:.7;font-size:13px;margin-top:6px;font-family:Arial,sans-serif">${b.subtitle}</div>` : ''}
</td></tr></table>`;
        }
        case 'text': {
            return `<table width="${W}" cellpadding="0" cellspacing="0" style="border:0;background:${b.bgColor}"><tr>
<td style="padding:24px 32px;background:${b.bgColor};color:${b.textColor};font-size:${b.fontSize}px;line-height:${b.lineHeight};font-family:Arial,sans-serif">
${b.content}
</td></tr></table>`;
        }
        case 'image': {
            if (!b.src) return `<table width="${W}" cellpadding="0" cellspacing="0" style="border:0;background:${b.bgColor}"><tr><td align="center" style="padding:16px;background:${b.bgColor};color:#94a3b8;font-size:12px;font-family:Arial,sans-serif"><i>[imagem não definida]</i></td></tr></table>`;
            const absUrl = b.src.startsWith('http') ? b.src : `${BASE_URL}/${b.src.replace(/^\//,'')}`;
            let img = `<img src="${absUrl}" alt="${b.alt||''}" style="display:block;border:0;max-width:100%;width:${b.width}">`;
            if (b.link) img = `<a href="${b.link}" target="_blank" style="text-decoration:none">${img}</a>`;
            const capHtml = b.caption ? `<div style="color:#64748b;font-size:11px;margin-top:6px;font-family:Arial,sans-serif">${b.caption}</div>` : '';
            return `<table width="${W}" cellpadding="0" cellspacing="0" style="border:0;background:${b.bgColor}"><tr>
<td align="${b.align||'center'}" style="padding:16px 32px;background:${b.bgColor}">
${img}${capHtml}
</td></tr></table>`;
        }
        case 'button': {
            const btn = `<a href="${b.url}" target="_blank"
style="display:inline-block;background:${b.bgColor};color:${b.textColor};padding:14px 32px;border-radius:${b.borderRadius}px;text-decoration:none;font-weight:bold;font-size:14px;font-family:Arial,sans-serif">${b.label}</a>`;
            return `<table width="${W}" cellpadding="0" cellspacing="0" style="border:0;background:${b.containerBg}"><tr>
<td align="${b.align||'center'}" style="padding:20px 32px;background:${b.containerBg}">${btn}</td></tr></table>`;
        }
        case 'divider': {
            return `<table width="${W}" cellpadding="0" cellspacing="0" style="border:0;background:${b.bgColor}"><tr>
<td style="padding:${b.marginV}px 32px;background:${b.bgColor}">
<div style="border-top:${b.thickness}px solid ${b.color}"></div>
</td></tr></table>`;
        }
        case 'footer': {
            return `<table width="${W}" cellpadding="0" cellspacing="0" style="border:0;background:${b.bgColor};border-top:1px solid #e2e8f0"><tr>
<td align="center" style="padding:16px 32px;background:${b.bgColor};color:${b.textColor};font-size:11px;font-family:Arial,sans-serif;line-height:1.6">
${b.text}
</td></tr></table>`;
        }
        default: return '';
    }
}

// ── Gerar HTML completo do e-mail ─────────────────────────────────────────────
function campBuildFullHtml(blocks) {
    const inner = blocks.map(campBlockToHtml).join('\n');
    return `<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>E-mail CRCAP</title></head>
<body style="margin:0;padding:0;background:#E8ECF0;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#E8ECF0">
<tr><td align="center" style="padding:24px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,22,68,.1)">
<tr><td>
${inner}
</td></tr>
</table>
</td></tr></table>
</body></html>`;
}

// ── Render canvas (editor visual) ─────────────────────────────────────────────
function campRenderCanvas() {
    const canvas = document.getElementById('campEmailCanvas');
    if (!canvas) return;
    canvas.innerHTML = '';

    if (_campBlocks.length === 0) {
        canvas.innerHTML = `<div style="padding:48px;text-align:center;color:#94a3b8;font-size:13px">
            <i class="fas fa-envelope" style="font-size:32px;display:block;margin-bottom:12px;color:#cbd5e1"></i>
            Use os botões à esquerda para adicionar blocos ao e-mail.</div>`;
        return;
    }

    _campBlocks.forEach((b, idx) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'camp-block-wrapper';
        wrapper.style.cssText = 'position:relative;cursor:pointer';
        wrapper.innerHTML = campBlockToHtml(b);

        // Overlay de controle
        const ctrl = document.createElement('div');
        ctrl.className = 'camp-block-ctrl';
        ctrl.style.cssText = 'display:none;position:absolute;top:4px;right:4px;z-index:10;gap:4px;flex-direction:row';
        ctrl.innerHTML = `
            <button onclick="campEditBlock(${idx})" title="Editar"
                    style="background:#001644;color:#BF8D1A;border:0;border-radius:6px;width:28px;height:28px;cursor:pointer;font-size:12px">
                <i class="fas fa-pen"></i></button>
            <button onclick="campMoveBlock(${idx},-1)" title="Mover para cima" ${idx===0?'disabled style="opacity:.3"':''}
                    style="background:#001644;color:#fff;border:0;border-radius:6px;width:28px;height:28px;cursor:pointer;font-size:11px">
                <i class="fas fa-chevron-up"></i></button>
            <button onclick="campMoveBlock(${idx},1)" title="Mover para baixo" ${idx===_campBlocks.length-1?'disabled style="opacity:.3"':''}
                    style="background:#001644;color:#fff;border:0;border-radius:6px;width:28px;height:28px;cursor:pointer;font-size:11px">
                <i class="fas fa-chevron-down"></i></button>
            <button onclick="campRemoveBlock(${idx})" title="Remover"
                    style="background:#ef4444;color:#fff;border:0;border-radius:6px;width:28px;height:28px;cursor:pointer;font-size:12px">
                <i class="fas fa-trash"></i></button>`;

        wrapper.addEventListener('mouseenter', () => { ctrl.style.display='flex'; wrapper.style.outline='2px solid #BF8D1A'; });
        wrapper.addEventListener('mouseleave', () => { ctrl.style.display='none'; wrapper.style.outline='none'; });
        wrapper.appendChild(ctrl);
        canvas.appendChild(wrapper);
    });

    // Sincroniza hidden field
    const html = campBuildFullHtml(_campBlocks);
    document.getElementById('camp_html_hidden').value = html;
    document.getElementById('campHtmlRaw').value       = html;
}

// ── Adicionar / mover / remover blocos ────────────────────────────────────────
function campAddBlock(type) {
    const b = Object.assign({ type }, JSON.parse(JSON.stringify(BLOCK_DEFAULTS[type] || {})));
    _campBlocks.push(b);
    campRenderCanvas();
    // Scroll para o fundo do canvas
    const area = document.getElementById('campEditorArea');
    if (area) area.scrollTop = area.scrollHeight;
}
function campMoveBlock(idx, dir) {
    const to = idx + dir;
    if (to < 0 || to >= _campBlocks.length) return;
    [_campBlocks[idx], _campBlocks[to]] = [_campBlocks[to], _campBlocks[idx]];
    campRenderCanvas();
}
function campRemoveBlock(idx) {
    if (!confirm('Remover este bloco?')) return;
    _campBlocks.splice(idx, 1);
    campRenderCanvas();
}

// ── Editor de bloco (sub-modal) ───────────────────────────────────────────────
function campEditBlock(idx) {
    _campEditIdx = idx;
    const b = _campBlocks[idx];
    const labels = {header:'Cabeçalho',text:'Texto',image:'Imagem',button:'Botão',divider:'Divisor',footer:'Rodapé'};
    document.getElementById('campBlockModalTitle').textContent = 'Editar — ' + (labels[b.type]||b.type);

    const body = document.getElementById('campBlockModalBody');
    body.innerHTML = '';

    const field = (label, key, type='text', extra='') => {
        const id = `cbf_${key}`;
        const val = (b[key]??'').toString().replace(/"/g,'&quot;');
        if (type === 'textarea') {
            return `<div><label class="form-label">${label}</label>
<textarea id="${id}" class="form-input text-xs font-mono resize-y" rows="5">${(b[key]??'')}</textarea></div>`;
        }
        if (type === 'select') {
            return `<div><label class="form-label">${label}</label><select id="${id}" class="form-input text-xs">${extra}</select></div>`;
        }
        return `<div><label class="form-label">${label}</label>
<input type="${type}" id="${id}" value="${val}" class="form-input text-xs" ${extra}></div>`;
    };
    const colorRow = (label, key) =>
        `<div class="flex items-center gap-2"><label class="form-label mb-0 flex-1">${label}</label>
<input type="color" id="cbf_${key}" value="${b[key]||'#ffffff'}" class="w-10 h-8 rounded border border-[#001644]/10 cursor-pointer p-0.5">
<input type="text" id="cbf_${key}_txt" value="${b[key]||'#ffffff'}" class="form-input text-xs w-24" oninput="document.getElementById('cbf_${key}').value=this.value">
</div>`;

    const alignOpts = `<option value="left" ${b.align==='left'?'selected':''}>Esquerda</option>
<option value="center" ${b.align==='center'||!b.align?'selected':''}>Centro</option>
<option value="right" ${b.align==='right'?'selected':''}>Direita</option>`;

    let html = '';
    switch (b.type) {
        case 'header':
            html = field('Título','title') +
                   field('Subtítulo (opcional)','subtitle') +
                   field('URL do Logo (opcional)','logoUrl','url') +
                   colorRow('Cor de fundo','bgColor') +
                   colorRow('Cor do texto','textColor');
            break;
        case 'text':
            html = field('Conteúdo HTML','content','textarea') +
                   field('Tamanho da fonte (px)','fontSize','number','min="10" max="32"') +
                   field('Altura da linha','lineHeight','number','min="1" max="3" step="0.1"') +
                   colorRow('Cor de fundo','bgColor') +
                   colorRow('Cor do texto','textColor');
            break;
        case 'image':
            html = `<div><label class="form-label">URL da Imagem *</label>
<div class="flex gap-2">
<input type="url" id="cbf_src" value="${(b.src||'').replace(/"/g,'&quot;')}" class="form-input text-xs flex-1" placeholder="https://...">
<button type="button" onclick="campTriggerUpload()"
        class="flex items-center gap-1.5 px-3 py-2 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition flex-shrink-0">
    <i class="fas fa-upload text-[10px]"></i>Upload
</button>
</div>
<p id="cbf_upload_status" class="text-[10px] text-[#022E6B]/50 mt-1 hidden"></p>
</div>` +
                   field('Texto alternativo (alt)','alt') +
                   field('Link ao clicar (opcional)','link','url') +
                   field('Largura (ex: 100%, 480px)','width') +
                   field('Legenda (opcional)','caption') +
                   field('Alinhamento','align','select', alignOpts) +
                   colorRow('Cor de fundo','bgColor');
            break;
        case 'button':
            html = field('Texto do botão','label') +
                   field('URL do botão','url','url') +
                   field('Alinhamento','align','select', alignOpts) +
                   field('Raio da borda (px)','borderRadius','number','min="0" max="50"') +
                   colorRow('Cor do botão','bgColor') +
                   colorRow('Cor do texto','textColor') +
                   colorRow('Cor de fundo do bloco','containerBg');
            break;
        case 'divider':
            html = colorRow('Cor da linha','color') +
                   field('Espessura (px)','thickness','number','min="1" max="10"') +
                   field('Espaço vertical (px)','marginV','number','min="0" max="40"') +
                   colorRow('Cor de fundo','bgColor');
            break;
        case 'footer':
            html = field('Conteúdo HTML','text','textarea') +
                   colorRow('Cor de fundo','bgColor') +
                   colorRow('Cor do texto','textColor');
            break;
    }
    body.innerHTML = html;

    // Sincronizar inputs color ↔ text
    body.querySelectorAll('input[type="color"]').forEach(ci => {
        const ti = document.getElementById(ci.id+'_txt');
        if(ti) ci.addEventListener('input', () => { ti.value = ci.value; });
    });

    document.getElementById('campBlockModal').classList.remove('hidden');
}

function campTriggerUpload() {
    document.getElementById('campImgFileInput').value = '';
    document.getElementById('campImgFileInput').click();
}

async function campUploadImg(input) {
    const file = input.files[0];
    if (!file) return;
    const status = document.getElementById('cbf_upload_status');
    if (status) { status.textContent = '⏳ Enviando...'; status.classList.remove('hidden'); }
    const fd = new FormData();
    fd.append('file', file);
    fd.append('type', 'image');
    try {
        const r = await fetch('/crcap/api/upload.php', { method: 'POST', body: fd });
        const text = await r.text();
        let d;
        try { d = JSON.parse(text); } catch(e) {
            if (status) { status.textContent = '❌ Erro: resposta inválida do servidor'; }
            return;
        }
        if (d.success) {
            // Garante URL absoluta para funcionar em e-mails
            let url = d.url || d.path || '';
            if (url && !url.startsWith('http')) {
                url = 'https://artemidiaweb.com.br' + (url.startsWith('/') ? '' : '/') + url;
            }
            const srcInput = document.getElementById('cbf_src');
            if (srcInput) srcInput.value = url;
            if (status) { status.textContent = '✅ Upload concluído: ' + url; status.classList.remove('hidden'); }
        } else {
            if (status) { status.textContent = '❌ Erro: ' + (d.message || d.error || 'falha no upload'); status.classList.remove('hidden'); }
        }
    } catch(e) {
        if (status) { status.textContent = '❌ Erro de conexão: ' + e.message; status.classList.remove('hidden'); }
    }
}

function campBlockApply() {
    if (_campEditIdx === null) return;
    const b = _campBlocks[_campEditIdx];
    document.querySelectorAll('#campBlockModalBody [id^="cbf_"]').forEach(el => {
        if (el.id.endsWith('_txt')) return; // skip color text helpers
        const key = el.id.replace('cbf_','');
        b[key] = el.value;
    });
    closeCampBlockModal();
    campRenderCanvas();
}
function closeCampBlockModal() {
    document.getElementById('campBlockModal').classList.add('hidden');
    _campEditIdx = null;
}

// ── Toggle HTML mode ──────────────────────────────────────────────────────────
function campToggleHtml() {
    _campPrevMode = false;
    _campHtmlMode = !_campHtmlMode;
    document.getElementById('campHtmlPanel').classList.toggle('hidden',    !_campHtmlMode);
    document.getElementById('campPreviewPanel').classList.add('hidden');
    document.getElementById('campEditorArea').querySelector('#campEmailCanvas').closest('.py-6').style.display = _campHtmlMode ? 'none' : '';
    const btn = document.getElementById('campHtmlBtn');
    btn.classList.toggle('bg-[#BF8D1A]/80', _campHtmlMode);
    if (_campHtmlMode) {
        document.getElementById('campHtmlRaw').value = campBuildFullHtml(_campBlocks);
    } else {
        // Ao voltar do HTML: atualiza o hidden field com o que foi editado
        document.getElementById('camp_html_hidden').value = document.getElementById('campHtmlRaw').value;
    }
}

// ── Toggle preview mode ───────────────────────────────────────────────────────
function campTogglePreview() {
    _campHtmlMode = false;
    _campPrevMode = !_campPrevMode;
    document.getElementById('campHtmlPanel').classList.add('hidden');
    document.getElementById('campPreviewPanel').classList.toggle('hidden', !_campPrevMode);
    document.getElementById('campEditorArea').querySelector('#campEmailCanvas').closest('.py-6').style.display = _campPrevMode ? 'none' : '';
    const btn = document.getElementById('campPreviewBtn');
    btn.classList.toggle('bg-[#BF8D1A]/80', _campPrevMode);
    if (_campPrevMode) {
        let html = campBuildFullHtml(_campBlocks);
        html = html.replace(/\{\{nome\}\}/g,'João Silva')
                   .replace(/\{\{email\}\}/g,'joao@exemplo.com')
                   .replace(/\{\{categoria\}\}/g,'Contador')
                   .replace(/\{\{unsubscribe_url\}\}/g,'#');
        document.getElementById('campPreviewFrame').srcdoc = html;
    }
}

// ── Copiar variável ───────────────────────────────────────────────────────────
function campCopyVar(v) {
    navigator.clipboard?.writeText(v).then(() => {
        // Toast rápido
        const t = document.createElement('div');
        t.textContent = v + ' copiado!';
        t.style.cssText='position:fixed;bottom:20px;right:20px;background:#001644;color:#fff;padding:8px 14px;border-radius:10px;font-size:11px;z-index:9999;font-family:Arial,sans-serif;opacity:1;transition:opacity .4s';
        document.body.appendChild(t);
        setTimeout(()=>{ t.style.opacity='0'; setTimeout(()=>t.remove(),400); }, 1800);
    });
}

// ── carregar HTML salvo no BD de volta para blocos ─────────────────────────────
function campLoadHtmlAsRaw(html) {
    // Se o HTML já foi gerado pelo nosso builder, tentamos extrair blocos via comentários futuros
    // Por agora: carrega como bloco único de texto raw para não perder conteúdo
    if (!html || html.trim() === '') return;
    // Detecta se é HTML do nosso builder (tem nosso wrapper)
    // Limpa blocos e adiciona um bloco de texto com o HTML completo para o usuário ajustar
    _campBlocks = [{ type:'text', bgColor:'#ffffff', textColor:'#022E6B', content: html, fontSize:'14', lineHeight:'1.7' }];
}

// ── Modal Campanha ────────────────────────────────────────────────────────────
function openCampModal(data) {
    const m = document.getElementById('campModal');
    document.querySelectorAll('.camp-cat-check').forEach(c => c.checked = false);

    // Reset modos
    _campHtmlMode = false; _campPrevMode = false;
    document.getElementById('campHtmlPanel').classList.add('hidden');
    document.getElementById('campPreviewPanel').classList.add('hidden');
    const canvasWrap = document.querySelector('#campEditorArea .py-6');
    if (canvasWrap) canvasWrap.style.display = '';
    document.getElementById('campHtmlBtn').classList.remove('bg-[#BF8D1A]/80');
    document.getElementById('campPreviewBtn').classList.remove('bg-[#BF8D1A]/80');

    if (data) {
        document.getElementById('campModalTitle').textContent = 'Editar Campanha';
        document.getElementById('camp_id').value         = data.id || 0;
        document.getElementById('camp_name').value       = data.name || '';
        document.getElementById('camp_subject').value    = data.subject || '';
        document.getElementById('camp_from_name').value  = data.from_name || 'CRCAP';
        document.getElementById('camp_from_email').value = data.from_email || 'noticias@crcap.org.br';

        const existingHtml   = data.content_html || '';
        const blocksJsonText = data.content_text  || '';
        document.getElementById('camp_html_hidden').value        = existingHtml;
        document.getElementById('camp_blocks_json_hidden').value = '';
        document.getElementById('campHtmlRaw').value             = existingHtml;

        // ── Tenta restaurar blocos estruturados ──────────────────────────────
        _campBlocks = [];
        let restored = false;

        // 1) content_text começa com '[' → é o JSON dos blocos que salvamos
        if (blocksJsonText && blocksJsonText.trim().startsWith('[')) {
            try {
                const parsed = JSON.parse(blocksJsonText);
                if (Array.isArray(parsed) && parsed.length > 0 && parsed[0].type) {
                    _campBlocks = parsed;
                    restored = true;
                }
            } catch(e) { /* segue para fallback */ }
        }

        // 2) Fallback: HTML não vazio → wrap em bloco raw para não perder conteúdo
        if (!restored) {
            if (existingHtml.trim()) {
                _campBlocks = [{
                    type: 'text',
                    bgColor: '#ffffff',
                    textColor: '#022E6B',
                    content: existingHtml,
                    fontSize: '14',
                    lineHeight: '1.7'
                }];
            } else {
                // Campanha nova/vazia → template padrão
                _campBlocks = [
                    Object.assign({type:'header'}, JSON.parse(JSON.stringify(BLOCK_DEFAULTS.header))),
                    Object.assign({type:'text'},   JSON.parse(JSON.stringify(BLOCK_DEFAULTS.text))),
                    Object.assign({type:'footer'}, JSON.parse(JSON.stringify(BLOCK_DEFAULTS.footer))),
                ];
            }
        }

        if (data.segment_filter) {
            try {
                const sf = JSON.parse(data.segment_filter);
                if (sf.categorias) document.querySelectorAll('.camp-cat-check').forEach(c => { c.checked = sf.categorias.includes(c.value); });
            } catch(e) {}
        }
    } else {
        document.getElementById('campModalTitle').textContent = 'Nova Campanha';
        document.getElementById('camp_id').value = 0;
        ['camp_name','camp_subject'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('camp_from_name').value  = 'CRCAP';
        document.getElementById('camp_from_email').value = 'noticias@crcap.org.br';
        document.getElementById('camp_html_hidden').value        = '';
        document.getElementById('camp_blocks_json_hidden').value = '';
        document.getElementById('campHtmlRaw').value = '';
        // Template inicial com header + texto + footer
        _campBlocks = [
            Object.assign({type:'header'}, JSON.parse(JSON.stringify(BLOCK_DEFAULTS.header))),
            Object.assign({type:'text'},   JSON.parse(JSON.stringify(BLOCK_DEFAULTS.text))),
            Object.assign({type:'footer'}, JSON.parse(JSON.stringify(BLOCK_DEFAULTS.footer))),
        ];
    }

    campRenderCanvas();
    m.classList.remove('hidden');
}

function closeCampModal() {
    document.getElementById('campModal').classList.add('hidden');
    closeCampBlockModal();
}

// Submit: garante que o HTML está atualizado
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('campForm')?.addEventListener('submit', function(e) {
        let html;
        if (_campHtmlMode) {
            html = document.getElementById('campHtmlRaw').value;
            // No modo HTML raw não temos blocos estruturados — salva string vazia
            document.getElementById('camp_blocks_json_hidden').value = '';
        } else {
            html = campBuildFullHtml(_campBlocks);
            // Serializa os blocos para reidratação futura
            document.getElementById('camp_blocks_json_hidden').value = JSON.stringify(_campBlocks);
        }
        if (!html.trim()) { e.preventDefault(); alert('Adicione pelo menos um bloco ao e-mail.'); return; }
        document.getElementById('camp_html_hidden').value = html;
    });
});

// ── Modal Envio ───────────────────────────────────────────────────────────────
let _campaignId = 0;
function sendCampaign(id, name, segmentFilter) {
    _campaignId = id;
    document.getElementById('modalTitle').textContent = 'Enviar: ' + name;
    document.getElementById('sendModal').classList.remove('hidden');
    document.getElementById('sendProgress').classList.add('hidden');
    document.getElementById('sendResult').classList.add('hidden');
    document.getElementById('modalActions').classList.remove('hidden');
    document.getElementById('progressBar').style.width = '0%';

    // Mostra filtro ativo
    const filterInfo = document.getElementById('sendFilterInfo');
    if (segmentFilter) {
        try {
            const sf = JSON.parse(segmentFilter);
            if (sf.categorias && sf.categorias.length) {
                filterInfo.textContent = 'Filtro ativo: ' + sf.categorias.join(', ');
                filterInfo.className = 'text-[10px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 mb-3';
            } else { filterInfo.className = 'hidden'; }
        } catch(e) { filterInfo.className = 'hidden'; }
    } else {
        filterInfo.textContent = 'Enviando para todos os inscritos';
        filterInfo.className = 'text-[10px] text-[#022E6B] bg-[#F8FAFC] border border-[#001644]/10 rounded-lg px-3 py-1.5 mb-3';
    }
}
function closeSendModal() {
    document.getElementById('sendModal').classList.add('hidden');
    if (document.getElementById('sendResult').textContent.includes('concluído')) location.reload();
}
async function confirmSend() {
    document.getElementById('modalActions').classList.add('hidden');
    document.getElementById('sendProgress').classList.remove('hidden');
    document.getElementById('sendResult').classList.add('hidden');
    let offset = 0, done = false;
    while (!done) {
        try {
            const r = await fetch('/crcap/api/send-campaign.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({campaign_id: _campaignId, offset: offset, batch_size: 50})
            });
            const rawText = await r.text();
            let data;
            try { data = JSON.parse(rawText); }
            catch(e) { showSendResult(false, 'Erro do servidor: ' + rawText.substring(0, 300)); break; }
            document.getElementById('progressText').textContent = data.message || '...';
            document.getElementById('progressBar').style.width = (data.progress || 0) + '%';
            if (!data.success) { showSendResult(false, data.message, data.errors); break; }
            if (data.done) { done = true; showSendResult(true, data.message, data.errors, data.batch_failed); }
            else { offset = data.next_offset; }
        } catch(e) { showSendResult(false, 'Erro de conexão: ' + e.message); break; }
    }
}
function showSendResult(ok, msg, errors, failCount) {
    const el = document.getElementById('sendResult');
    let html = (ok ? '✅ ' : '❌ ') + (msg || '');
    if (errors && errors.length > 0) {
        html += '<div class="mt-2 space-y-1">';
        errors.forEach(e => { html += '<div class="font-mono bg-red-100 px-2 py-1 rounded text-[10px]">' + e + '</div>'; });
        html += '</div>';
    }
    el.className = 'text-xs rounded-lg px-3 py-2 mb-4 ' +
        (ok && !failCount ? 'bg-green-50 text-green-700 border border-green-200'
                          : ok ? 'bg-yellow-50 text-yellow-700 border border-yellow-200'
                               : 'bg-red-50 text-red-700 border border-red-200');
    el.innerHTML = html;
    el.classList.remove('hidden');
    document.getElementById('sendProgress').classList.add('hidden');
    document.getElementById('modalActions').innerHTML =
        '<button onclick="closeSendModal()" class="btn-primary w-full justify-center"><i class="fas fa-check"></i>Fechar</button>';
    document.getElementById('modalActions').classList.remove('hidden');
}

// ── Editor de Páginas de Resposta ─────────────────────────────────────────────
<?php if ($tab === 'pages' && $editingPage): ?>
let pageQuill = null;
let pageHtmlMode = false;

// Inicializa Quill
document.addEventListener('DOMContentLoaded', function() {
    pageQuill = new Quill('#pageQuillEditor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{'header': [1,2,3,false]}],
                ['bold','italic','underline'],
                [{'color': []},{'background': []}],
                [{'align': []}],
                [{'list': 'ordered'},{'list': 'bullet'}],
                ['link','image'],
                ['clean']
            ]
        },
        placeholder: 'Conteúdo HTML do e-mail...'
    });

    // Carrega conteudo inicial via JSON seguro (evita problemas com htmlspecialchars)
    let _initData = {};
    try {
        const initEl = document.getElementById('pageInitData');
        if (initEl) _initData = JSON.parse(initEl.textContent);
    } catch(e) {}

    const savedHtml = _initData.html || '';
    if (savedHtml) {
        // Usa clipboard.dangerouslyPasteHTML para interpretar HTML real
        pageQuill.clipboard.dangerouslyPasteHTML(0, savedHtml);
    }
    // Inicializa o hidden field e o textarea raw com o HTML real
    document.getElementById('pageHtmlHidden').value = savedHtml;
    document.getElementById('pageHtmlRaw').value    = savedHtml;
    pageQuill.root.style.minHeight = '380px';

    // Upload de imagem via Quill
    pageQuill.getModule('toolbar').addHandler('image', function() {
        const inp = document.createElement('input');
        inp.type = 'file'; inp.accept = 'image/*';
        inp.onchange = async function() {
            const fd = new FormData();
            fd.append('file', this.files[0]);
            fd.append('type', 'image');
            try {
                const r = await fetch('/crcap/api/upload.php', {method:'POST', body:fd});
                const d = await r.json();
                if (d.success) {
                    const rng = pageQuill.getSelection(true);
                    pageQuill.insertEmbed(rng.index, 'image', d.url);
                }
            } catch(e) {}
        };
        inp.click();
    });

    // Ao submeter o formulario, copia HTML atualizado para o hidden field
    document.getElementById('pageEditForm').addEventListener('submit', function(e) {
        let finalHtml = '';
        if (!pageHtmlMode) {
            // Pega HTML do Quill; usa dangerouslyPasteHTML method para serializar
            finalHtml = pageQuill ? pageQuill.root.innerHTML : '';
        } else {
            finalHtml = document.getElementById('pageHtmlRaw').value;
        }
        document.getElementById('pageHtmlHidden').value = finalHtml;

        // Validacao basica
        if (!finalHtml.trim()) {
            e.preventDefault();
            alert('O conteudo do e-mail nao pode estar vazio.');
        }
    });
});

function togglePageEditorMode() {
    const raw     = document.getElementById('pageHtmlRaw');
    const wrapper = document.getElementById('pageQuillWrapper');
    const btn     = document.getElementById('pageEditorModeBtn');
    pageHtmlMode  = !pageHtmlMode;

    if (pageHtmlMode) {
        // Quill -> HTML raw: serializa o HTML atual
        const currentHtml = pageQuill ? pageQuill.root.innerHTML : document.getElementById('pageHtmlHidden').value;
        raw.value = currentHtml;
        raw.classList.remove('hidden');
        wrapper.classList.add('hidden');
        btn.innerHTML = '<i class="fas fa-magic"></i> Modo Visual';
        raw.oninput = function() {
            document.getElementById('pageHtmlHidden').value = this.value;
        };
    } else {
        // HTML raw -> Quill: carrega com dangerouslyPasteHTML
        const rawHtml = raw.value;
        if (pageQuill) {
            pageQuill.setContents([]);
            pageQuill.clipboard.dangerouslyPasteHTML(0, rawHtml);
        }
        document.getElementById('pageHtmlHidden').value = rawHtml;
        raw.classList.add('hidden');
        wrapper.classList.remove('hidden');
        btn.innerHTML = '<i class="fas fa-code"></i> Modo HTML';
    }
}

function togglePagePreview() {
    const wrapper = document.getElementById('pagePreviewWrapper');
    wrapper.classList.toggle('hidden');
    if (!wrapper.classList.contains('hidden')) {
        refreshPagePreview();
    }
}

function refreshPagePreview() {
    // Sincroniza hidden antes do preview
    if (!pageHtmlMode && pageQuill) {
        document.getElementById('pageHtmlHidden').value = pageQuill.root.innerHTML;
    }
    const html = document.getElementById('pageHtmlHidden').value ||
                 (pageQuill ? pageQuill.root.innerHTML : '');

    // Substitui variáveis por dados de exemplo
    const preview = html
        .replace(/\{\{nome\}\}/g, 'João Silva')
        .replace(/\{\{email\}\}/g, 'joao.silva@exemplo.com')
        .replace(/\{\{categoria\}\}/g, 'Contador')
        .replace(/\{\{unsubscribe_url\}\}/g, '#');

    const frame = document.getElementById('pagePreviewFrame');
    frame.srcdoc = preview;
}

// Restaurar padrão via AJAX
async function restoreDefault(key) {
    const defaults = <?= json_encode(array_map(fn($d) => ['subject'=>$d['subject'],'html'=>$d['html']], $defaultPages)) ?>;
    if (defaults[key]) {
        if (pageQuill) pageQuill.root.innerHTML = defaults[key].html;
        document.getElementById('pageHtmlHidden').value = defaults[key].html;
        document.getElementById('pageHtmlRaw').value    = defaults[key].html;
        // Atualiza campo de assunto
        document.querySelector('input[name="page_subject"]').value = defaults[key].subject;
    }
}
<?php endif; ?>

// Fechar modais com ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeSubModal();
        closeCampModal();
        closeSendModal();
    }
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>