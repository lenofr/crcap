<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método não permitido']); exit;
}

$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$name      = trim($_POST['name'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$action    = $_POST['action'] ?? 'subscribe';

// Garante colunas existam
try { $pdo->exec("ALTER TABLE newsletters ADD COLUMN full_name VARCHAR(255) NULL AFTER name"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE newsletters ADD COLUMN categoria VARCHAR(100) NULL AFTER full_name"); } catch (Exception $e) {}

if (!$email) {
    echo json_encode(['success'=>false,'message'=>'E-mail inválido']); exit;
}

if ($action === 'unsubscribe') {
    dbExec($pdo, "UPDATE newsletters SET status='unsubscribed', unsubscribed_at=NOW() WHERE email=?", [$email]);
    echo json_encode(['success'=>true,'message'=>'Você foi removido da lista.']); exit;
}

$existing = dbFetch($pdo, "SELECT id, status FROM newsletters WHERE email=?", [$email]);
if ($existing) {
    if ($existing['status'] === 'subscribed') {
        echo json_encode(['success'=>false,'message'=>'Este e-mail já está cadastrado.']);
    } else {
        dbExec($pdo, "UPDATE newsletters SET status='subscribed',unsubscribed_at=NULL,name=?,full_name=?,categoria=? WHERE email=?",
            [$name?:null,$full_name?:null,$categoria?:null,$email]);
        echo json_encode(['success'=>true,'message'=>'Você foi reinscrito com sucesso!']);
    }
    exit;
}

$token = bin2hex(random_bytes(16));
try {
    dbExec($pdo,
        "INSERT INTO newsletters (email,name,full_name,categoria,status,confirmed,subscription_ip,subscription_source,confirmation_token)
         VALUES (?,?,?,?,'subscribed',1,?,'website',?)",
        [$email,$name?:null,$full_name?:null,$categoria?:null,$_SERVER['REMOTE_ADDR']??'',$token]);
    echo json_encode(['success'=>true,'message'=>'Inscrição realizada! Obrigado por se cadastrar.']);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Erro ao salvar. Tente novamente.']);
}