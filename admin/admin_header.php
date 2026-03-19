<?php
// admin/includes/admin_header.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$adminUser  = currentUser();
$pageTitle  = $pageTitle ?? 'Painel Administrativo · CRCAP';
$activeAdm  = $activeAdm ?? '';

// ── Permissões por módulo ─────────────────────────────────────────────────────
// Admin vê tudo. Editor vê apenas módulos liberados em user_permissions.
function canSeeModule(string $module): bool {
    global $pdo, $adminUser;
    if ($adminUser['role'] === 'admin') return true;
    // Migração: cria tabela se não existir
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_permissions (
            user_id INT UNSIGNED NOT NULL,
            module  VARCHAR(50) NOT NULL,
            PRIMARY KEY (user_id, module)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {}
    static $userPerms = null;
    if ($userPerms === null) {
        $rows = $pdo->prepare("SELECT module FROM user_permissions WHERE user_id=?");
        $rows->execute([$adminUser['id']]);
        $userPerms = $rows->fetchAll(PDO::FETCH_COLUMN);
    }
    return in_array($module, $userPerms);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{primary:{DEFAULT:'#001644',light:'#022E6B'},accent:{gold:'#BF8D1A',green:'#006633'}}}}}</script>
    <style>
        *{font-family:'Sora',sans-serif;}
        body{background:#F0F4F8;}
        .sidebar-link{display:flex;align-items:center;gap:0.75rem;padding:0.6rem 1rem;border-radius:0.75rem;font-size:0.75rem;font-weight:500;color:rgba(255,255,255,0.7);transition:all .2s;text-decoration:none;}
        .sidebar-link:hover,.sidebar-link.active{background:rgba(255,255,255,0.12);color:white;}
        .sidebar-link.active{background:rgba(191,141,26,0.25);color:#BF8D1A;}
        .sidebar-group{font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,0.3);padding:0.5rem 1rem;margin-top:1rem;}
        ::-webkit-scrollbar{width:4px;} ::-webkit-scrollbar-thumb{background:#00164440;border-radius:2px;}
        .card{background:white;border-radius:1.5rem;border:1px solid rgba(0,22,68,0.04);box-shadow:0 4px 20px rgba(0,22,68,0.03);}
        .btn-primary{background:#001644;color:white;padding:.6rem 1.25rem;border-radius:.75rem;font-size:.75rem;font-weight:600;transition:all .2s;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;}
        .btn-primary:hover{background:#022E6B;box-shadow:0 8px 20px rgba(0,22,68,.2);}
        .btn-gold{background:#BF8D1A;color:white;padding:.6rem 1.25rem;border-radius:.75rem;font-size:.75rem;font-weight:600;transition:all .2s;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;}
        .btn-gold:hover{background:#001644;}
        .btn-danger{background:#EF4444;color:white;padding:.6rem 1.25rem;border-radius:.75rem;font-size:.75rem;font-weight:600;transition:all .2s;border:none;cursor:pointer;}
        .btn-danger:hover{background:#DC2626;}
        .form-input{width:100%;padding:.75rem 1rem;border:1px solid rgba(0,22,68,.1);border-radius:.75rem;font-size:.8125rem;background:#F8FAFC;transition:all .2s;outline:none;}
        .form-input:focus{border-color:#BF8D1A;box-shadow:0 0 0 3px rgba(191,141,26,.1);}
        .form-label{display:block;font-size:.65rem;font-weight:700;color:#022E6B;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.4rem;}
        .badge{display:inline-flex;align-items:center;padding:.2rem .65rem;border-radius:9999px;font-size:.65rem;font-weight:700;}
        .badge-green{background:#006633;color:white;}
        .badge-gold{background:#BF8D1A;color:white;}
        .badge-blue{background:#001644;color:white;}
        .badge-red{background:#EF4444;color:white;}
        .badge-gray{background:#6B7280;color:white;}
        table thead{background:#001644;color:white;}
        table thead th{padding:.75rem 1rem;font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;}
        table tbody tr{border-bottom:1px solid rgba(0,22,68,.04);}
        table tbody tr:hover{background:#F8FAFC;}
        table tbody td{padding:.75rem 1rem;font-size:.75rem;color:#022E6B;}
    </style>
</head>
<body>
<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-60 bg-[#001644] flex flex-col overflow-y-auto flex-shrink-0">
        <!-- Logo -->
        <div class="p-5 border-b border-white/10">
            <a href="/crcap/index.php" class="flex items-center gap-3">
                <div class="w-9 h-9 bg-[#BF8D1A] rounded-xl flex items-center justify-center text-white font-bold text-lg">C</div>
                <div><p class="text-white font-bold text-sm leading-none">CRCAP</p><p class="text-white/40 text-[9px] uppercase tracking-wider">Painel Admin</p></div>
            </a>
        </div>

        <!-- Nav -->
        <nav class="p-3 flex-1 space-y-0.5">
            <?php if (canSeeModule('dashboard')): ?>
            <a href="/crcap/admin/index.php" class="sidebar-link <?= $activeAdm==='dashboard'?'active':'' ?>"><i class="fas fa-th-large w-4 text-center text-sm"></i>Dashboard</a>
            <?php endif; ?>

            <?php if (canSeeModule('posts')||canSeeModule('pages')||canSeeModule('sliders')||canSeeModule('media')||canSeeModule('documents')||canSeeModule('galleries')||canSeeModule('menu')): ?>
            <div class="sidebar-group">Conteúdo</div>
            <?php endif; ?>
            <?php if (canSeeModule('posts')): ?><a href="/crcap/admin/posts.php" class="sidebar-link <?= $activeAdm==='posts'?'active':'' ?>"><i class="fas fa-newspaper w-4 text-center text-sm"></i>Posts e Notícias</a><?php endif; ?>
            <?php if (canSeeModule('pages')): ?><a href="/crcap/admin/pages.php" class="sidebar-link <?= $activeAdm==='pages'?'active':'' ?>"><i class="fas fa-file-alt w-4 text-center text-sm"></i>Páginas Estáticas</a><?php endif; ?>
            <?php if (canSeeModule('sliders')): ?><a href="/crcap/admin/sliders.php" class="sidebar-link <?= $activeAdm==='sliders'?'active':'' ?>"><i class="fas fa-images w-4 text-center text-sm"></i>Slider da Home</a><?php endif; ?>
            <?php if (canSeeModule('media')): ?><a href="/crcap/admin/media.php" class="sidebar-link <?= $activeAdm==='media'?'active':'' ?>"><i class="fas fa-photo-video w-4 text-center text-sm"></i>Biblioteca de Mídia</a><?php endif; ?>
            <?php if (canSeeModule('documents')): ?><a href="/crcap/admin/documents.php" class="sidebar-link <?= $activeAdm==='documents'?'active':'' ?>"><i class="fas fa-folder-open w-4 text-center text-sm"></i>Documentos</a><?php endif; ?>
            <?php if (canSeeModule('galleries')): ?><a href="/crcap/admin/galleries.php" class="sidebar-link <?= $activeAdm==='galleries'?'active':'' ?>"><i class="fas fa-images w-4 text-center text-sm"></i>Galerias de Fotos</a><?php endif; ?>
            <?php if (canSeeModule('menu')): ?><a href="/crcap/admin/menu.php" class="sidebar-link <?= $activeAdm==='menu'?'active':'' ?>"><i class="fas fa-bars w-4 text-center text-sm"></i>Menu</a><?php endif; ?>

            <?php if (canSeeModule('agenda')||canSeeModule('events')||canSeeModule('newsletter')||canSeeModule('whatsapp')||canSeeModule('contacts')): ?>
            <div class="sidebar-group">Comunicação</div>
            <?php endif; ?>
            <?php if (canSeeModule('agenda')): ?><a href="/crcap/admin/agenda.php" class="sidebar-link <?= $activeAdm==='agenda'?'active':'' ?>"><i class="fas fa-calendar-alt w-4 text-center text-sm"></i>Agenda do Presidente</a><?php endif; ?>
            <?php if (canSeeModule('events')): ?><a href="/crcap/admin/events.php" class="sidebar-link <?= $activeAdm==='events'?'active':'' ?>"><i class="fas fa-calendar-check w-4 text-center text-sm"></i>Eventos</a><?php endif; ?>
            <?php if (canSeeModule('newsletter')): ?><a href="/crcap/admin/newsletter.php" class="sidebar-link <?= $activeAdm==='newsletter'?'active':'' ?>"><i class="fas fa-envelope w-4 text-center text-sm"></i>Newsletter</a><?php endif; ?>
            <?php if (canSeeModule('whatsapp')): ?><a href="/crcap/admin/whatsapp.php" class="sidebar-link <?= $activeAdm==='whatsapp'?'active':'' ?>"><i class="fab fa-whatsapp w-4 text-center text-sm"></i>WhatsApp</a><?php endif; ?>
            <?php if (canSeeModule('contacts')): ?><a href="/crcap/admin/contacts.php" class="sidebar-link <?= $activeAdm==='contacts'?'active':'' ?>"><i class="fas fa-inbox w-4 text-center text-sm"></i>Contatos Recebidos</a><?php endif; ?>

            <?php if (canSeeModule('users')||canSeeModule('settings')||canSeeModule('smtp')||canSeeModule('reports')||canSeeModule('logs')): ?>
            <div class="sidebar-group">Sistema</div>
            <?php endif; ?>
            <?php if (canSeeModule('users')): ?><a href="/crcap/admin/users.php" class="sidebar-link <?= $activeAdm==='users'?'active':'' ?>"><i class="fas fa-users w-4 text-center text-sm"></i>Usuários</a><?php endif; ?>
            <?php if (canSeeModule('settings')): ?><a href="/crcap/admin/settings.php" class="sidebar-link <?= $activeAdm==='settings'?'active':'' ?>"><i class="fas fa-cog w-4 text-center text-sm"></i>Configurações</a><?php endif; ?>
            <?php if (canSeeModule('smtp')): ?><a href="/crcap/admin/smtp.php" class="sidebar-link <?= $activeAdm==='smtp'?'active':'' ?>"><i class="fas fa-server w-4 text-center text-sm"></i>SMTP / Email</a><?php endif; ?>
            <?php if (canSeeModule('reports')): ?><a href="/crcap/admin/reports.php" class="sidebar-link <?= $activeAdm==='reports'?'active':'' ?>"><i class="fas fa-chart-bar w-4 text-center text-sm"></i>Relatórios</a><?php endif; ?>
            <?php if (canSeeModule('logs')): ?><a href="/crcap/admin/logs.php" class="sidebar-link <?= $activeAdm==='logs'?'active':'' ?>"><i class="fas fa-history w-4 text-center text-sm"></i>Logs de Atividade</a><?php endif; ?>
        </nav>

        <!-- User info -->
        <div class="p-3 border-t border-white/10">
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="w-8 h-8 rounded-full bg-[#BF8D1A] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    <?= strtoupper(substr($adminUser['full_name'] ?: $adminUser['username'], 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <p class="text-white text-xs font-semibold truncate"><?= htmlspecialchars($adminUser['full_name'] ?: $adminUser['username']) ?></p>
                    <p class="text-white/40 text-[9px] capitalize"><?= $adminUser['role'] ?></p>
                </div>
                <a href="/crcap/admin/logout.php" class="ml-auto text-white/30 hover:text-white transition text-xs" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </aside>

    <!-- Main content wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top bar -->
        <header class="bg-white border-b border-[#001644]/5 px-6 py-3 flex items-center justify-between flex-shrink-0">
            <div>
                <h1 class="text-sm font-bold text-[#001644]"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-[10px] text-[#022E6B]"><?= date('l, d \d\e F \d\e Y') ?></p>
            </div>
            <div class="flex items-center gap-3">
                <a href="/crcap/index.php" target="_blank" class="flex items-center gap-1.5 text-[10px] text-[#022E6B] hover:text-[#BF8D1A] transition font-medium">
                    <i class="fas fa-external-link-alt text-[9px]"></i>Ver site
                </a>
                <div class="w-px h-5 bg-[#001644]/10"></div>
                <a href="/crcap/admin/logout.php" class="flex items-center gap-1.5 text-[10px] text-red-500 hover:text-red-700 transition font-medium">
                    <i class="fas fa-sign-out-alt text-[9px]"></i>Sair
                </a>
            </div>
        </header>

        <!-- Content area -->
        <main class="flex-1 overflow-y-auto p-6">