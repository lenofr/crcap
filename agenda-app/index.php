<?php
// /crcap/agenda-app/index.php — PWA Agenda do Presidente
ob_start(); // Captura qualquer output acidental (warnings do db.php, etc)
session_start();

// Suprimir warnings para não contaminar JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // Erros vão para o catch, não para output

require_once __DIR__ . '/../includes/db.php';

// ─── Helpers ───────────────────────────────────────────────────────────────
function jsonOut(array $d): void {
    ob_clean(); // Limpa qualquer output acidental antes do JSON
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache');
    echo json_encode($d);
    exit;
}

function isAppLogged(): bool {
    return !empty($_SESSION['app_user_id']);
}

// ─── AJAX handlers ─────────────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache');

    // Login
    if ($_GET['ajax'] === 'login') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $row = dbFetch($pdo, "SELECT id,username,full_name,password,role FROM users WHERE (username=? OR email=?) AND status='active' LIMIT 1", [$u,$u]);
        if ($row && password_verify($p, $row['password']) && in_array($row['role'], ['admin','editor','author'])) {
            $_SESSION['app_user_id']   = $row['id'];
            $_SESSION['app_user_name'] = $row['full_name'] ?: $row['username'];
            $_SESSION['app_user_role'] = $row['role'];
            jsonOut(['ok' => true, 'name' => $_SESSION['app_user_name']]);
        }
        jsonOut(['ok' => false, 'msg' => 'Usuário ou senha inválidos.']);
    }

    // Logout
    if ($_GET['ajax'] === 'logout') {
        session_destroy();
        jsonOut(['ok' => true]);
    }

    // Guard remaining routes
    if (!isAppLogged()) jsonOut(['ok' => false, 'msg' => 'Não autenticado.']);

    // Wrap all DB operations in try/catch to return proper JSON on error
    try {

    // List agenda (month)
    if ($_GET['ajax'] === 'list') {
        $mes = $_GET['mes'] ?? date('Y-m');
        
        // Parse year and month from '2026-03' format
        $parts = explode('-', $mes);
        $ano = isset($parts[0]) ? (int)$parts[0] : (int)date('Y');
        $mesNum = isset($parts[1]) ? (int)$parts[1] : (int)date('m');
        
        if (!isset($pdo) || !$pdo) {
            jsonOut(['ok' => false, 'msg' => 'Sem conexão com banco de dados.']);
        }
        
        $rows = dbFetchAll($pdo,
            "SELECT id, title, description, event_type, location,
                    event_date, start_time, end_time, status, priority, is_public, image
             FROM president_schedule
             WHERE YEAR(event_date) = ? AND MONTH(event_date) = ?
             ORDER BY event_date ASC, start_time ASC",
            [$ano, $mesNum]);
        
        jsonOut(['ok' => true, 'data' => $rows ?? [], 'mes' => $mes, 'ano' => $ano, 'mesNum' => $mesNum]);
    }

    // Get single record
    if ($_GET['ajax'] === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $row = $id ? dbFetch($pdo, "SELECT * FROM president_schedule WHERE id=?", [$id]) : null;
        jsonOut(['ok' => (bool)$row, 'data' => $row]);
    }

    // Save (insert or update)
    if ($_GET['ajax'] === 'save') {
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
        $isPublic  = (int)($_POST['is_public'] ?? 1);
        $image     = trim($_POST['image'] ?? '');
        $notes     = trim($_POST['notes'] ?? '');

        if (!$title || !$evDate || !$startTime) jsonOut(['ok' => false, 'msg' => 'Preencha título, data e hora.']);

        if ($sid) {
            dbExec($pdo, "UPDATE president_schedule SET title=?,description=?,event_type=?,location=?,event_date=?,start_time=?,end_time=?,status=?,priority=?,is_public=?,image=?,notes=? WHERE id=?",
                [$title,$desc,$evType,$location,$evDate,$startTime,$endTime,$status,$priority,$isPublic,$image,$notes,$sid]);
            jsonOut(['ok' => true, 'msg' => 'Compromisso atualizado!', 'id' => $sid]);
        } else {
            dbExec($pdo, "INSERT INTO president_schedule (title,description,event_type,location,event_date,start_time,end_time,status,priority,is_public,image,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$title,$desc,$evType,$location,$evDate,$startTime,$endTime,$status,$priority,$isPublic,$image,$notes,$_SESSION['app_user_id']]);
            jsonOut(['ok' => true, 'msg' => 'Compromisso criado!', 'id' => $pdo->lastInsertId()]);
        }
    }

    // Delete
    if ($_GET['ajax'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) dbExec($pdo, "DELETE FROM president_schedule WHERE id=?", [$id]);
        jsonOut(['ok' => true]);
    }

    // Image upload
    if ($_GET['ajax'] === 'upload') {
        if (empty($_FILES['file']['tmp_name'])) jsonOut(['ok' => false, 'msg' => 'Nenhum arquivo.']);
        $dir = __DIR__ . '/../uploads/agenda/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) jsonOut(['ok' => false, 'msg' => 'Formato inválido.']);
        $name = 'agenda_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . $name)) {
            jsonOut(['ok' => true, 'url' => '/crcap/uploads/agenda/' . $name]);
        }
        jsonOut(['ok' => false, 'msg' => 'Falha no upload.']);
    }

    jsonOut(['ok' => false, 'msg' => 'Ação desconhecida.']);

    } catch (Throwable $e) {
        jsonOut(['ok' => false, 'msg' => 'Erro interno: ' . $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Agenda">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" sizes="192x192" href="/crcap/agenda-app/icon-192.png">
<link rel="apple-touch-icon" sizes="512x512" href="/crcap/agenda-app/icon-512.png">
<meta name="msapplication-TileImage" content="/crcap/agenda-app/icon-192.png">
<meta name="msapplication-TileColor" content="#001644">
<meta name="theme-color" content="#001644">
<title>CRCAP Agenda</title>
<link rel="manifest" href="/crcap/agenda-app/manifest.json">
<link rel="apple-touch-icon" href="/crcap/agenda-app/icon-192.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --primary: #001644;
    --primary-light: #022E6B;
    --gold: #BF8D1A;
    --green: #006633;
    --surface: #F8FAFC;
    --border: rgba(0,22,68,.07);
    --safe-top: env(safe-area-inset-top, 0px);
    --safe-bot: env(safe-area-inset-bottom, 0px);
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; overflow: hidden; background: #001644; font-family: 'Inter', sans-serif; color: var(--primary); -webkit-font-smoothing: antialiased; }
  #listScreen, #formScreen { background: #F8FAFC; }

  /* ── App Shell ── */
  #app { display: flex; flex-direction: column; height: 100%; padding-top: var(--safe-top); }

  /* ── Screens ── */
  .screen { display: none; flex-direction: column; flex: 1; overflow: hidden; }
  .screen.active { display: flex; }

  /* ── Top Bar ── */
  .topbar {
    background: var(--primary);
    color: white;
    padding: 14px 16px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
    box-shadow: 0 2px 12px rgba(0,22,68,.3);
  }
  .topbar-logo-img {
    width: 42px; height: 42px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .topbar-logo-img img {
    width: 42px; height: 42px;
    object-fit: contain;
    opacity: 0;
    transition: opacity 0.3s;
    filter: brightness(0) invert(1);
  }
  .topbar-logo-fallback {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #BF8D1A, #8a6510);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; font-size: 20px;
  }
  .topbar-logo {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, var(--gold), #a07315);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 16px; color: white;
    flex-shrink: 0;
  }
  .topbar-title { flex: 1; }
  .topbar-title h1 { font-size: 15px; font-weight: 700; line-height: 1; }
  .topbar-title p  { font-size: 10px; opacity: .65; margin-top: 2px; }
  .topbar-btn {
    width: 36px; height: 36px;
    background: rgba(255,255,255,.1);
    border: none; border-radius: 10px;
    color: white; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .2s;
    flex-shrink: 0;
  }
  .topbar-btn:active { background: rgba(255,255,255,.2); }
  #refreshBtn { font-size: 20px; font-weight: 300; }

  /* ── Month nav ── */
  .month-bar {
    background: white;
    border-bottom: 1px solid var(--border);
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }
  .month-bar h2 { flex: 1; text-align: center; font-size: 14px; font-weight: 700; text-transform: capitalize; }
  .nav-btn {
    width: 32px; height: 32px;
    border: 1.5px solid var(--border);
    background: var(--surface);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; color: var(--primary);
    cursor: pointer; transition: all .2s;
  }
  .nav-btn:active { border-color: var(--gold); color: var(--gold); }
  .today-chip {
    padding: 4px 10px;
    border: 1.5px solid var(--border);
    background: var(--surface);
    border-radius: 20px;
    font-size: 11px; font-weight: 600; color: var(--primary);
    cursor: pointer;
  }
  .today-chip:active { border-color: var(--gold); }

  /* ── Scroll area ── */
  .scroll-area { flex: 1; overflow-y: auto; padding: 12px 16px; padding-bottom: calc(80px + var(--safe-bot)); -webkit-overflow-scrolling: touch; }

  /* ── Event Card ── */
  .event-card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--border);
    margin-bottom: 10px;
    overflow: hidden;
    transition: box-shadow .2s;
    cursor: pointer;
    display: flex;
    gap: 0;
  }
  .event-card:active { box-shadow: 0 4px 20px rgba(0,22,68,.12); }
  .event-date-col {
    width: 56px;
    background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 12px 6px;
    flex-shrink: 0;
    text-align: center;
  }
  .event-card.today .event-date-col { background: linear-gradient(180deg, var(--gold) 0%, #a07315 100%); }
  .event-date-col .day   { font-size: 22px; font-weight: 800; line-height: 1; }
  .event-date-col .month { font-size: 9px;  font-weight: 600; opacity: .85; text-transform: uppercase; margin-top: 2px; }
  .event-date-col .time  { font-size: 9px;  opacity: .75; margin-top: 4px; }
  .event-body { flex: 1; padding: 12px 14px; min-width: 0; }
  .event-title { font-size: 13px; font-weight: 600; line-height: 1.3; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .event-meta  { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
  .event-location { font-size: 11px; color: #5a6a8a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
  .event-type-badge {
    font-size: 9px; font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    background: rgba(0,22,68,.06);
    color: var(--primary);
    text-transform: uppercase;
    letter-spacing: .04em;
    flex-shrink: 0;
  }
  .event-thumb { width: 60px; height: 60px; object-fit: cover; align-self: center; margin-right: 12px; border-radius: 10px; flex-shrink: 0; }

  /* Priority dots */
  .pri-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
  .pri-urgent { background: #ef4444; }
  .pri-high    { background: #f97316; }
  .pri-medium  { background: var(--gold); }
  .pri-low     { background: #94a3b8; }

  /* Status chip */
  .status-chip { font-size: 9px; font-weight: 600; padding: 2px 7px; border-radius: 20px; }
  .status-scheduled  { background: rgba(0,22,68,.06); color: var(--primary); }
  .status-confirmed  { background: rgba(0,102,51,.1); color: var(--green); }
  .status-in_progress{ background: rgba(191,141,26,.1); color: var(--gold); }
  .status-completed  { background: #f0fdf4; color: #166534; }
  .status-cancelled  { background: #fef2f2; color: #dc2626; }

  /* ── FAB ── */
  .fab {
    position: fixed;
    bottom: calc(20px + var(--safe-bot));
    right: 20px;
    width: 56px; height: 56px;
    background: linear-gradient(135deg, var(--gold), #a07315);
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 22px;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(191,141,26,.5);
    display: flex; align-items: center; justify-content: center;
    transition: transform .2s;
    z-index: 100;
  }
  .fab:active { transform: scale(.92); }

  /* ── Empty state ── */
  .empty-state { text-align: center; padding: 60px 20px; }
  .empty-state i { font-size: 48px; color: rgba(0,22,68,.15); display: block; margin-bottom: 12px; }
  .empty-state h3 { font-size: 15px; font-weight: 600; color: #001644; }
  .empty-state p  { font-size: 12px; color: #5a6a8a; margin-top: 4px; }

  /* ── Login Screen ── */
  #loginScreen { background: var(--surface); align-items: center; justify-content: center; padding: 32px 24px; }
  .login-card {
    width: 100%; max-width: 380px;
    background: white;
    border-radius: 24px;
    padding: 32px 24px;
    box-shadow: 0 20px 60px rgba(0,22,68,.12);
    border: 1px solid var(--border);
  }
  .login-logo {
    width: 100px; height: 100px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
  }
  .login-title { text-align: center; margin-bottom: 28px; }
  .login-title h2 { font-size: 20px; font-weight: 800; }
  .login-title p  { font-size: 12px; color: #5a6a8a; margin-top: 4px; }
  .form-group { margin-bottom: 16px; }
  .form-group label { display: block; font-size: 11px; font-weight: 600; color: #5a6a8a; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
  .form-input {
    width: 100%; padding: 13px 14px;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    font-size: 14px; font-family: inherit; color: var(--primary);
    background: var(--surface);
    outline: none; transition: border-color .2s;
  }
  .form-input:focus { border-color: var(--primary); background: white; }
  .input-icon { position: relative; }
  .input-icon .form-input { padding-left: 42px; }
  .input-icon i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; }
  .btn-primary {
    width: 100%; padding: 14px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white; border: none; border-radius: 12px;
    font-size: 14px; font-weight: 700; font-family: inherit;
    cursor: pointer; transition: opacity .2s;
    box-shadow: 0 4px 16px rgba(0,22,68,.3);
  }
  .btn-primary:active { opacity: .85; }
  .btn-primary:disabled { opacity: .5; cursor: not-allowed; }
  .login-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 10px; padding: 10px 14px; font-size: 12px; margin-bottom: 16px; display: none; }

  /* ── Form Screen ── */
  #formScreen { background: var(--surface); }
  .form-topbar {
    background: white;
    border-bottom: 1px solid var(--border);
    padding: 14px 16px;
    display: flex; align-items: center; gap: 10px;
    flex-shrink: 0;
  }
  .form-topbar-title { flex: 1; font-size: 15px; font-weight: 700; }
  .back-btn {
    width: 36px; height: 36px;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 10px; color: var(--primary);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 13px;
  }
  .save-btn {
    padding: 8px 18px;
    background: linear-gradient(135deg, var(--gold), #a07315);
    color: white; border: none; border-radius: 10px;
    font-size: 12px; font-weight: 700; font-family: inherit;
    cursor: pointer; box-shadow: 0 3px 12px rgba(191,141,26,.4);
  }
  .save-btn:active { opacity: .85; }
  .save-btn:disabled { opacity: .5; }
  .form-scroll { flex: 1; overflow-y: auto; padding: 16px; padding-bottom: calc(24px + var(--safe-bot)); }
  .form-section { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 16px; margin-bottom: 12px; }
  .form-section h3 { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #5a6a8a; margin-bottom: 14px; }
  .form-group-sm { margin-bottom: 12px; }
  .form-label { font-size: 11px; font-weight: 600; color: #5a6a8a; display: block; margin-bottom: 5px; }
  .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
  select.form-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23001644' d='M1 1l5 5 5-5'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }
  textarea.form-input { resize: none; }
  .toggle-row { display: flex; align-items: center; justify-content: space-between; }
  .toggle-label { font-size: 13px; font-weight: 500; }
  .toggle { width: 46px; height: 26px; background: #e2e8f0; border-radius: 13px; position: relative; cursor: pointer; transition: background .2s; flex-shrink: 0; }
  .toggle.on { background: var(--green); }
  .toggle::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: white; border-radius: 10px; transition: transform .2s; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
  .toggle.on::after { transform: translateX(20px); }

  /* Image upload */
  .img-upload-area {
    border: 2px dashed rgba(0,22,68,.15);
    border-radius: 14px;
    overflow: hidden;
    background: var(--surface);
  }
  .img-preview { position: relative; display: none; }
  .img-preview img { width: 100%; height: 160px; object-fit: cover; display: block; }
  .img-remove-btn {
    position: absolute; top: 8px; right: 8px;
    padding: 5px 10px;
    background: rgba(239,68,68,.9);
    color: white; border: none; border-radius: 8px;
    font-size: 11px; font-weight: 600; cursor: pointer;
  }
  .img-placeholder { text-align: center; padding: 28px 16px; }
  .img-placeholder i { font-size: 32px; color: rgba(0,22,68,.15); display: block; margin-bottom: 8px; }
  .img-placeholder p { font-size: 11px; color: rgba(0,22,68,.35); }
  .img-actions { display: flex; gap: 8px; padding: 10px; }
  .img-btn-upload {
    flex: 1; padding: 11px;
    background: var(--primary);
    color: white; border: none; border-radius: 10px;
    font-size: 12px; font-weight: 700; font-family: inherit;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
  }
  .img-btn-url {
    padding: 11px 14px;
    border: 1.5px solid var(--border);
    background: white; border-radius: 10px;
    font-size: 12px; font-weight: 600; color: var(--primary);
    cursor: pointer; font-family: inherit;
  }
  .img-spinner { text-align: center; padding: 20px; display: none; }
  .img-url-row { padding: 0 10px 10px; display: none; }
  .img-url-row .form-input { font-size: 12px; }

  /* ── Detail Sheet ── */
  .sheet-overlay {
    position: fixed; inset: 0;
    background: rgba(0,22,68,.4);
    z-index: 200;
    opacity: 0; pointer-events: none;
    transition: opacity .3s;
  }
  .sheet-overlay.open { opacity: 1; pointer-events: auto; }
  .sheet {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    background: white;
    border-radius: 24px 24px 0 0;
    padding: 0 0 calc(24px + var(--safe-bot));
    z-index: 201;
    transform: translateY(100%);
    transition: transform .35s cubic-bezier(.25,.46,.45,.94);
    max-height: 88vh;
    overflow-y: auto;
  }
  .sheet.open { transform: translateY(0); }
  .sheet-handle { width: 40px; height: 4px; background: #e2e8f0; border-radius: 2px; margin: 14px auto 0; }
  .sheet-img { width: 100%; height: 180px; object-fit: cover; display: none; }
  .sheet-content { padding: 16px 20px; }
  .sheet-type-badge {
    display: inline-block;
    font-size: 10px; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
    background: rgba(0,22,68,.06); color: var(--primary);
    text-transform: uppercase; letter-spacing: .06em;
    margin-bottom: 10px;
  }
  .sheet-title { font-size: 18px; font-weight: 800; line-height: 1.3; margin-bottom: 12px; }
  .sheet-meta-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 13px; color: #5a6a8a; }
  .sheet-meta-row i { width: 16px; color: var(--gold); font-size: 12px; }
  .sheet-desc { font-size: 13px; color: #5a6a8a; line-height: 1.6; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }
  .sheet-actions { display: flex; gap: 10px; margin-top: 18px; }
  .sheet-btn-edit {
    flex: 1; padding: 13px;
    background: var(--primary);
    color: white; border: none; border-radius: 12px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    cursor: pointer;
  }
  .sheet-btn-del {
    padding: 13px 18px;
    background: #fef2f2;
    color: #dc2626; border: none; border-radius: 12px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    cursor: pointer;
  }

  /* ── Toast ── */
  .toast {
    position: fixed;
    bottom: calc(90px + var(--safe-bot));
    left: 50%; transform: translateX(-50%) translateY(20px);
    background: var(--primary);
    color: white;
    padding: 11px 20px;
    border-radius: 20px;
    font-size: 13px; font-weight: 600;
    box-shadow: 0 6px 24px rgba(0,22,68,.3);
    opacity: 0;
    pointer-events: none;
    transition: all .3s;
    white-space: nowrap;
    z-index: 300;
  }
  .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
  .toast.success { background: var(--green); }
  .toast.error   { background: #dc2626; }

  /* ── Loader ── */
  .loader { text-align: center; padding: 40px; }
  .loader i { font-size: 24px; color: var(--gold); }

  /* ── Install banner ── */
  .install-banner {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    color: white;
    padding: 10px 16px;
    display: flex; align-items: center; gap: 10px;
    flex-shrink: 0;
    display: none;
  }
  .install-banner p { flex: 1; font-size: 12px; font-weight: 500; }
  .install-btn {
    padding: 6px 14px;
    background: var(--gold);
    border: none; border-radius: 8px;
    color: white; font-size: 11px; font-weight: 700;
    cursor: pointer; font-family: inherit; white-space: nowrap;
  }
  .install-dismiss { background: none; border: none; color: rgba(255,255,255,.6); cursor: pointer; font-size: 16px; padding: 4px; }

  .spin-ring {
    width: 36px; height: 36px;
    border: 3px solid rgba(191,141,26,.2);
    border-top-color: #BF8D1A;
    border-radius: 50%;
    animation: spin 0.9s linear infinite;
    margin: 0 auto;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .retry-btn {
    margin-top: 16px; padding: 10px 24px;
    background: var(--primary); color: white;
    border: none; border-radius: 12px;
    font-size: 13px; font-weight: 700; cursor: pointer;
    font-family: inherit;
  }

</style>
</head>
<body>
<div id="app">

  <!-- ══ LOGIN SCREEN ══════════════════════════════════════════════════════ -->
  <div id="loginScreen" class="screen">
    <div class="login-card">
      <div class="login-logo" style="background:none;box-shadow:none">
        <img src="data:image/png;base64,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" alt="CRCAP" style="width:90px;height:90px;object-fit:contain;border-radius:16px">
      </div>
      <div class="login-title">
        <h2>CRCAP Agenda</h2>
        <p>Agenda do Presidente · Acesso restrito</p>
      </div>
      <div id="loginError" class="login-error"></div>
      <div class="form-group">
        <label>Usuário ou e-mail</label>
        <div class="input-icon">
          <i class="fas fa-user"></i>
          <input id="loginUser" type="text" class="form-input" placeholder="Digite seu usuário" autocomplete="username" autocapitalize="none">
        </div>
      </div>
      <div class="form-group">
        <label>Senha</label>
        <div class="input-icon">
          <i class="fas fa-lock"></i>
          <input id="loginPass" type="password" class="form-input" placeholder="Digite sua senha" autocomplete="current-password">
        </div>
      </div>
      <button id="loginBtn" class="btn-primary" onclick="doLogin()">Entrar</button>
    </div>
  </div>

  <!-- ══ LIST SCREEN ═══════════════════════════════════════════════════════ -->
  <div id="listScreen" class="screen">
    <!-- Install banner -->
    <div id="installBanner" class="install-banner">
      <p>📱 Instale o app na sua tela inicial!</p>
      <button class="install-btn" onclick="doInstall()">Instalar</button>
      <button class="install-dismiss" onclick="document.getElementById('installBanner').style.display='none'">×</button>
    </div>

    <!-- Top bar -->
    <div class="topbar">
      <div class="topbar-logo" style="background:none;padding:2px;width:40px;height:40px;border-radius:10px;overflow:hidden">
        <img src="data:image/png;base64,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" alt="CRCAP" style="width:36px;height:36px;object-fit:contain">
      </div>
      <button class="topbar-btn" onclick="loadList()" title="Atualizar" id="refreshBtn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
      </button>
      <button class="topbar-btn" onclick="doLogout()" title="Sair">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </button>
    </div>

    <!-- Month nav -->
    <div class="month-bar">
      <button class="nav-btn" onclick="changeMonth(-1)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
      <h2 id="monthLabel">—</h2>
      <button class="nav-btn" onclick="changeMonth(1)"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
      <button class="today-chip" onclick="goToday()">Hoje</button>
    </div>

    <!-- List -->
    <div class="scroll-area" id="eventList">
      <div class="loader"><i class="fas fa-spinner fa-spin"></i></div>
    </div>

    <!-- FAB -->
    <button class="fab" onclick="openForm(null)" title="Novo compromisso">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </button>
  </div>

  <!-- ══ FORM SCREEN ════════════════════════════════════════════════════════ -->
  <div id="formScreen" class="screen">
    <div class="form-topbar">
      <button class="back-btn" onclick="closeForm()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg></button>
      <span class="form-topbar-title" id="formTitle">Novo Compromisso</span>
      <button class="save-btn" id="saveBtn" onclick="saveForm()"><i class="fas fa-check"></i> Salvar</button>
    </div>
    <div class="form-scroll">
      <input type="hidden" id="fSid" value="0">

      <!-- Dados principais -->
      <div class="form-section">
        <h3>Dados do Compromisso</h3>
        <div class="form-group-sm">
          <label class="form-label">Título *</label>
          <input id="fTitle" type="text" class="form-input" placeholder="Título do compromisso">
        </div>
        <div class="form-group-sm">
          <label class="form-label">Descrição</label>
          <textarea id="fDesc" class="form-input" rows="3" placeholder="Detalhes do evento…"></textarea>
        </div>
        <div class="form-grid-3">
          <div class="form-group-sm">
            <label class="form-label">Data *</label>
            <input id="fDate" type="date" class="form-input">
          </div>
          <div class="form-group-sm">
            <label class="form-label">Início *</label>
            <input id="fStart" type="time" class="form-input">
          </div>
          <div class="form-group-sm">
            <label class="form-label">Término</label>
            <input id="fEnd" type="time" class="form-input">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group-sm">
            <label class="form-label">Tipo</label>
            <select id="fType" class="form-input">
              <option value="meeting">Reunião</option>
              <option value="visit">Visita</option>
              <option value="ceremony">Cerimônia</option>
              <option value="conference">Conferência</option>
              <option value="trip">Viagem</option>
              <option value="other">Outro</option>
            </select>
          </div>
          <div class="form-group-sm">
            <label class="form-label">Local</label>
            <input id="fLocation" type="text" class="form-input" placeholder="Local do evento">
          </div>
        </div>
      </div>

      <!-- Foto -->
      <div class="form-section">
        <h3>Foto do Evento</h3>
        <div class="img-upload-area" id="imgArea">
          <div class="img-preview" id="imgPreview">
            <img id="imgThumb" src="" alt="Preview">
            <button class="img-remove-btn" onclick="removeImg()"><i class="fas fa-trash"></i> Remover</button>
          </div>
          <div class="img-placeholder" id="imgPlaceholder">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            <p>Nenhuma foto selecionada</p>
          </div>
          <div class="img-spinner" id="imgSpinner">
            <i class="fas fa-spinner fa-spin" style="font-size:24px;color:var(--gold)"></i>
            <p style="font-size:11px;color:#5a6a8a;margin-top:8px">Enviando foto…</p>
          </div>
          <div class="img-actions">
            <label style="flex:1">
              <div class="img-btn-upload">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Câmera / Galeria
              </div>
              <input type="file" id="imgFile" accept="image/*" capture="environment" style="display:none" onchange="uploadImg(this)">
            </label>
            <button class="img-btn-url" onclick="toggleUrlRow()">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </button>
          </div>
          <div class="img-url-row" id="imgUrlRow">
            <div style="display:flex;gap:6px">
              <input type="text" id="imgUrlInput" class="form-input" placeholder="Cole URL da imagem…" style="font-size:12px">
              <button onclick="applyUrl()" style="padding:0 14px;background:var(--gold);color:white;border:none;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer">OK</button>
            </div>
          </div>
          <input type="hidden" id="fImage" value="">
        </div>
      </div>

      <!-- Opções -->
      <div class="form-section">
        <h3>Opções</h3>
        <div class="form-grid-2" style="margin-bottom:12px">
          <div class="form-group-sm">
            <label class="form-label">Status</label>
            <select id="fStatus" class="form-input">
              <option value="scheduled">Agendado</option>
              <option value="confirmed">Confirmado</option>
              <option value="in_progress">Em andamento</option>
              <option value="completed">Concluído</option>
              <option value="cancelled">Cancelado</option>
            </select>
          </div>
          <div class="form-group-sm">
            <label class="form-label">Prioridade</label>
            <select id="fPriority" class="form-input">
              <option value="low">Baixa</option>
              <option value="medium" selected>Média</option>
              <option value="high">Alta</option>
              <option value="urgent">Urgente</option>
            </select>
          </div>
        </div>
        <div class="toggle-row">
          <span class="toggle-label">Exibir no site público</span>
          <div class="toggle on" id="fPublicToggle" onclick="togglePublic()"></div>
        </div>
        <div class="form-group-sm" style="margin-top:12px">
          <label class="form-label">Notas internas</label>
          <textarea id="fNotes" class="form-input" rows="2" placeholder="Observações privadas…"></textarea>
        </div>
      </div>
    </div>
  </div>

</div><!-- #app -->

<!-- ── Detail Sheet ──────────────────────────────────────────────────────── -->
<div class="sheet-overlay" id="sheetOverlay" onclick="closeSheet()"></div>
<div class="sheet" id="detailSheet">
  <div class="sheet-handle"></div>
  <img id="sheetImg" class="sheet-img" src="" alt="">
  <div class="sheet-content">
    <div id="sheetBadge" class="sheet-type-badge"></div>
    <h2 id="sheetTitle" class="sheet-title"></h2>
    <div class="sheet-meta-row"><i class="fas fa-calendar-day"></i><span id="sheetDate"></span></div>
    <div class="sheet-meta-row"><i class="fas fa-clock"></i><span id="sheetTime"></span></div>
    <div id="sheetLocRow" class="sheet-meta-row"><i class="fas fa-map-marker-alt"></i><span id="sheetLoc"></span></div>
    <div id="sheetDesc" class="sheet-desc"></div>
    <div class="sheet-actions">
      <button class="sheet-btn-edit" id="sheetEditBtn" onclick="editFromSheet()"><i class="fas fa-edit"></i> Editar</button>
      <button class="sheet-btn-del" id="sheetDelBtn" onclick="deleteFromSheet()"><i class="fas fa-trash"></i></button>
    </div>
  </div>
</div>

<!-- ── Toast ─────────────────────────────────────────────────────────────── -->
<div class="toast" id="toast"></div>

<script>
// ══ State ════════════════════════════════════════════════════════════════════
const BASE = '/crcap/agenda-app/';
let curMes = new Date().toISOString().slice(0,7);
let curSheet = null;
let deferredInstall = null;

// ══ Utils ════════════════════════════════════════════════════════════════════
const $ = id => document.getElementById(id);

async function api(action, params = {}, isFile = false) {
    const url = BASE + '?ajax=' + action;
    let body;
    if (isFile) { body = params; }
    else if (Object.keys(params).length) { body = new URLSearchParams(params); }
    const r = await fetch(url, { method: body ? 'POST' : 'GET', body });
    return r.json();
}

function showScreen(id) {
    document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
    $(id).classList.add('active');
    // background: login=navy, others=surface
    document.body.style.background = (id === 'loginScreen') ? '#001644' : '#F8FAFC';
}

function toast(msg, type = '') {
    const t = $('toast');
    t.textContent = msg;
    t.className = 'toast show ' + type;
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 2800);
}

const MONTH_PT = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const TYPE_PT  = { meeting:'Reunião', visit:'Visita', ceremony:'Cerimônia', conference:'Conferência', trip:'Viagem', other:'Outro' };
const MON_SHORT= ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ══ Login ════════════════════════════════════════════════════════════════════
async function doLogin() {
    const btn = $('loginBtn');
    const u = $('loginUser').value.trim();
    const p = $('loginPass').value;
    if (!u || !p) { showLoginError('Preencha todos os campos.'); return; }
    btn.disabled = true;
    btn.textContent = 'Entrando…';
    const d = await api('login', { username: u, password: p });
    btn.disabled = false;
    btn.textContent = 'Entrar';
    if (d.ok) {
        $('topbarUser').textContent = d.name;
        showScreen('listScreen');
        loadList();
    } else {
        showLoginError(d.msg || 'Erro ao entrar.');
    }
}
function showLoginError(msg) {
    const el = $('loginError');
    el.textContent = msg;
    el.style.display = 'block';
}
document.addEventListener('DOMContentLoaded', () => {
    $('loginPass').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
});

async function doLogout() {
    await api('logout');
    showScreen('loginScreen');
    $('loginUser').value = '';
    $('loginPass').value = '';
    $('loginError').style.display = 'none';
}

// ══ List ═════════════════════════════════════════════════════════════════════
async function loadList() {
    const el = $('eventList');
    el.innerHTML = '<div class="loader"><div class="spin-ring"></div></div>';
    const rb = $('refreshBtn');
    if (rb) { rb.style.animation = 'spin 0.7s linear infinite'; rb.style.display = 'inline-flex'; }
    const [y, m] = curMes.split('-').map(Number);
    $('monthLabel').textContent = MONTH_PT[m-1] + ' ' + y;
    try {
        const r = await fetch(BASE + '?ajax=list&mes=' + encodeURIComponent(curMes));
        const data = await r.json();
        if (data.ok === false) {
            if (data.msg && data.msg.includes('autenticado')) {
                showScreen('loginScreen'); return;
            }
        }
        renderList(data.data || []);
        if ($('refreshBtn')) $('refreshBtn').style.animation = '';
    } catch(e) {
        if ($('refreshBtn')) $('refreshBtn').style.animation = '';
        el.innerHTML = '<div class="empty-state"><div style="font-size:36px;margin-bottom:12px">⚠️</div><h3>Erro ao carregar</h3><p>' + (e.message||'Verifique sua conexão') + '</p><button onclick="loadList()" class="retry-btn">Tentar novamente</button><br><br><button onclick="diagAjax()" style="margin-top:8px;padding:8px 16px;background:#BF8D1A;color:white;border:none;border-radius:8px;font-size:11px;cursor:pointer">Ver diagnóstico</button></div>';
    }
}

function renderList(items) {
    const el = $('eventList');
    if (!items.length) {
        el.innerHTML = '<div class="empty-state"><div style="font-size:48px;margin-bottom:12px">📅</div><h3>Nenhum compromisso</h3><p>Toque em + para adicionar</p></div>';
        return;
    }
    const today = new Date().toISOString().slice(0,10);
    el.innerHTML = items.map(s => {
        const d = new Date(s.event_date + 'T00:00:00');
        const isToday = s.event_date === today;
        const priClass = 'pri-' + (s.priority || 'medium');
        const statusClass = 'status-' + (s.status || 'scheduled');
        const statusLabel = { scheduled:'Agendado', confirmed:'Confirmado', in_progress:'Em curso', completed:'Concluído', cancelled:'Cancelado' }[s.status] || s.status;
        const thumb = s.image ? `<img src="${s.image}" alt="" class="event-thumb" loading="lazy">` : '';
        const locHtml = s.location ? `<div class="event-location">📍 ${esc(s.location)}</div>` : '';
        return `<div class="event-card ${isToday?'today':''}" onclick="openSheet(${s.id})">
          <div class="event-date-col">
            <span class="day">${String(d.getDate()).padStart(2,'0')}</span>
            <span class="month">${MON_SHORT[d.getMonth()]}</span>
            <span class="time">${s.start_time.slice(0,5)}</span>
          </div>
          <div class="event-body">
            <div class="event-title">${esc(s.title)}</div>
            <div class="event-meta">
              <div class="pri-dot ${priClass}"></div>
              <span class="event-type-badge">${TYPE_PT[s.event_type]||s.event_type}</span>
              <span class="status-chip ${statusClass}">${statusLabel}</span>
            </div>
            ${locHtml}
          </div>
          ${thumb}
        </div>`;
    }).join('');
}

function changeMonth(dir) {
    const [y,m] = curMes.split('-').map(Number);
    const nd = new Date(y, m-1+dir, 1);
    curMes = nd.getFullYear() + '-' + String(nd.getMonth()+1).padStart(2,'0');
    loadList();
}
function goToday() {
    curMes = new Date().toISOString().slice(0,7);
    loadList();
}

// ══ Detail Sheet ═════════════════════════════════════════════════════════════
async function openSheet(id) {
    const r = await fetch(BASE + '?ajax=get&id=' + id);
    const d = await r.json();
    if (!d.ok) return;
    curSheet = d.data;
    const s = d.data;
    const dt = new Date(s.event_date + 'T00:00:00');
    const dateStr = dt.toLocaleDateString('pt-BR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    $('sheetBadge').textContent = TYPE_PT[s.event_type] || s.event_type;
    $('sheetTitle').textContent = s.title;
    $('sheetDate').textContent  = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
    $('sheetTime').textContent  = s.start_time.slice(0,5) + (s.end_time ? ' – ' + s.end_time.slice(0,5) : '');
    const locRow = $('sheetLocRow');
    if (s.location) { $('sheetLoc').textContent = s.location; locRow.style.display='flex'; } else { locRow.style.display='none'; }
    const descEl = $('sheetDesc');
    descEl.style.display = s.description ? 'block' : 'none';
    descEl.textContent = s.description || '';
    const img = $('sheetImg');
    if (s.image) { img.src = s.image; img.style.display = 'block'; } else { img.style.display = 'none'; }
    $('sheetOverlay').classList.add('open');
    $('detailSheet').classList.add('open');
}
function closeSheet() {
    $('sheetOverlay').classList.remove('open');
    $('detailSheet').classList.remove('open');
    curSheet = null;
}
function editFromSheet() {
    const saved = curSheet;
    closeSheet();
    openForm(saved);
}
async function deleteFromSheet() {
    if (!curSheet) return;
    if (!confirm('Excluir "' + curSheet.title + '"?')) return;
    await api('delete', { id: curSheet.id });
    closeSheet();
    toast('Compromisso excluído.', 'error');
    loadList();
}

// ══ Form Screen ═══════════════════════════════════════════════════════════════
let fPublic = 1;
function openForm(data) {
    $('fSid').value       = data?.id || 0;
    $('fTitle').value     = data?.title || '';
    $('fDesc').value      = data?.description || '';
    $('fDate').value      = data?.event_date || new Date().toISOString().slice(0,10);
    $('fStart').value     = data?.start_time?.slice(0,5) || '09:00';
    $('fEnd').value       = data?.end_time?.slice(0,5) || '';
    $('fType').value      = data?.event_type || 'meeting';
    $('fLocation').value  = data?.location || '';
    $('fStatus').value    = data?.status || 'scheduled';
    $('fPriority').value  = data?.priority || 'medium';
    $('fNotes').value     = data?.notes || '';
    fPublic = data?.is_public !== undefined ? parseInt(data.is_public) : 1;
    $('fPublicToggle').className = 'toggle ' + (fPublic ? 'on' : '');
    setImg(data?.image || '');
    $('imgUrlRow').style.display = 'none';
    $('formTitle').textContent = data?.id ? 'Editar Compromisso' : 'Novo Compromisso';
    showScreen('formScreen');
}
function closeForm() { showScreen('listScreen'); }
function togglePublic() {
    fPublic = fPublic ? 0 : 1;
    $('fPublicToggle').className = 'toggle ' + (fPublic ? 'on' : '');
}
async function saveForm() {
    const title = $('fTitle').value.trim();
    const date  = $('fDate').value;
    const start = $('fStart').value;
    if (!title || !date || !start) { toast('Preencha título, data e hora.', 'error'); return; }
    const btn = $('saveBtn');
    btn.disabled = true;
    btn.textContent = 'Salvando…';
    const d = await api('save', {
        sid: $('fSid').value, title, description: $('fDesc').value,
        event_type: $('fType').value, location: $('fLocation').value,
        event_date: date, start_time: start, end_time: $('fEnd').value,
        status: $('fStatus').value, priority: $('fPriority').value,
        is_public: fPublic, image: $('fImage').value, notes: $('fNotes').value,
    });
    btn.disabled = false;
    btn.textContent = 'Salvar';
    if (d.ok) {
        curMes = date.slice(0,7);
        showScreen('listScreen');
        loadList();
        toast(d.msg || 'Salvo!', 'success');
    } else {
        toast(d.msg || 'Erro ao salvar.', 'error');
    }
}

// ══ Image ═════════════════════════════════════════════════════════════════════
function setImg(url) {
    $('fImage').value = url;
    $('imgPreview').style.display = url ? 'block' : 'none';
    $('imgPlaceholder').style.display = url ? 'none' : 'block';
    if (url) $('imgThumb').src = url;
}
function removeImg() { setImg(''); $('imgFile').value = ''; }
function toggleUrlRow() {
    const row = $('imgUrlRow');
    row.style.display = row.style.display === 'none' ? 'block' : 'none';
    if (row.style.display !== 'none') $('imgUrlInput').focus();
}
function applyUrl() {
    const url = $('imgUrlInput').value.trim();
    if (url.startsWith('http')) { setImg(url); $('imgUrlRow').style.display = 'none'; $('imgUrlInput').value = ''; }
    else toast('URL inválida.', 'error');
}
async function uploadImg(input) {
    if (!input.files[0]) return;
    $('imgSpinner').style.display = 'block';
    $('imgPlaceholder').style.display = 'none';
    $('imgPreview').style.display = 'none';
    const fd = new FormData();
    fd.append('file', input.files[0]);
    try {
        const d = await api('upload', fd, true);
        $('imgSpinner').style.display = 'none';
        if (d.ok && d.url) { setImg(d.url); }
        else { $('imgPlaceholder').style.display = 'block'; toast(d.msg || 'Erro no upload.', 'error'); }
    } catch(e) {
        $('imgSpinner').style.display = 'none';
        $('imgPlaceholder').style.display = 'block';
        toast('Erro de conexão.', 'error');
    }
}

// ══ PWA Install ═══════════════════════════════════════════════════════════════
window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredInstall = e;
    $('installBanner').style.display = 'flex';
});
async function doInstall() {
    if (!deferredInstall) return;
    deferredInstall.prompt();
    const { outcome } = await deferredInstall.userChoice;
    if (outcome === 'accepted') $('installBanner').style.display = 'none';
    deferredInstall = null;
}

// ══ Diagnóstico ═══════════════════════════════════════════════════════════════
async function diagAjax() {
    const url = BASE + '?ajax=list&mes=' + encodeURIComponent(curMes);
    try {
        const r = await fetch(url);
        const text = await r.text();
        const win = window.open('', '_blank');
        win.document.write('<pre style="font-size:12px;word-break:break-all;">' + 
            'URL: ' + url + '\n' +
            'Status: ' + r.status + '\n' +
            'Content-Type: ' + r.headers.get('content-type') + '\n\n' +
            text.substring(0,2000) + '</pre>');
    } catch(e) {
        alert('Fetch error: ' + e.message + '\nURL: ' + url);
    }
}

// ══ Push ══════════════════════════════════════════════════════════════════════
async function requestPush() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
        const p = await Notification.requestPermission();
        if (p === 'granted') toast('Notificações ativadas! ✓', 'success');
    }
}

// ══ Service Worker ════════════════════════════════════════════════════════════
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/crcap/agenda-app/sw.js')
        .catch(e => console.warn('SW:', e));
}

// ══ Boot — mantém sessão ao recarregar ════════════════════════════════════════
(async () => {
    try {
        const r = await fetch(BASE + '?ajax=list&mes=' + encodeURIComponent(curMes));
        const data = await r.json();
        if (data.ok !== false) {
            // Sessão ativa - vai direto para lista
            const [y, m] = curMes.split('-').map(Number);
            $('monthLabel').textContent = MONTH_PT[m-1] + ' ' + y;
            showScreen('listScreen');  // sets bg to #F8FAFC
            renderList(data.data || []);
            setTimeout(requestPush, 4000);
        } else {
            // Sem sessão - mostra login
            showScreen('loginScreen'); // sets bg to #001644
        }
    } catch(e) {
        // Erro de rede - mostra login
        showScreen('loginScreen');
        console.warn('Boot error:', e);
    }
    // Hide splash regardless
    const splash = document.getElementById('splashScreen');
    if (splash) { splash.style.opacity='0'; setTimeout(()=>splash.remove(),350); }
})();
</script>
</body>
</html>