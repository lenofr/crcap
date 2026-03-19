<?php
$pageTitle = 'Dashboard · Admin CRCAP';
$activeAdm = 'dashboard';
require_once __DIR__ . '/admin_header.php';

// ── Stats ────────────────────────────────────────────────────────────────────
$stats = [
    'posts'     => dbFetch($pdo, "SELECT COUNT(*) AS n FROM posts WHERE status='published'")['n'] ?? 0,
    'posts_d'   => dbFetch($pdo, "SELECT COUNT(*) AS n FROM posts WHERE status='draft'")['n'] ?? 0,
    'events'    => dbFetch($pdo, "SELECT COUNT(*) AS n FROM events WHERE status='published' AND event_date >= CURDATE()")['n'] ?? 0,
    'users'     => dbFetch($pdo, "SELECT COUNT(*) AS n FROM users WHERE status='active'")['n'] ?? 0,
    'contacts'  => dbFetch($pdo, "SELECT COUNT(*) AS n FROM contacts WHERE status='new'")['n'] ?? 0,
    'news_subs' => dbFetch($pdo, "SELECT COUNT(*) AS n FROM newsletters WHERE status='subscribed'")['n'] ?? 0,
    'docs'      => dbFetch($pdo, "SELECT COUNT(*) AS n FROM documents WHERE status='active'")['n'] ?? 0,
    'views'     => dbFetch($pdo, "SELECT COALESCE(SUM(views),0) AS n FROM posts")['n'] ?? 0,
];

// Views trend (last 7 days posts published)
$recentPosts = dbFetchAll($pdo,
    "SELECT title, slug, views, published_at, status FROM posts ORDER BY created_at DESC LIMIT 8");

// Upcoming events
$upcomingEvents = dbFetchAll($pdo,
    "SELECT title, event_date, start_time, location, status FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");

// Latest contacts
$latestContacts = dbFetchAll($pdo,
    "SELECT name, email, subject, status, created_at FROM contacts ORDER BY created_at DESC LIMIT 6");

// President schedule today + next 5 days
$schedule = dbFetchAll($pdo,
    "SELECT title, event_date, start_time, location, status FROM president_schedule WHERE event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(),INTERVAL 7 DAY) ORDER BY event_date, start_time LIMIT 5");

// Monthly posts chart data (last 6 months)
$chartData = dbFetchAll($pdo,
    "SELECT DATE_FORMAT(published_at,'%b') AS month, COUNT(*) AS total
     FROM posts WHERE published_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) AND status='published'
     GROUP BY DATE_FORMAT(published_at,'%Y-%m') ORDER BY published_at ASC");

// Latest newsletter subs
$latestSubs = dbFetchAll($pdo,
    "SELECT email, name, subscribed_at FROM newsletters WHERE status='subscribed' ORDER BY subscribed_at DESC LIMIT 5");

// Activity log
$actLogs = dbFetchAll($pdo,
    "SELECT al.action, al.entity_type, al.description, al.created_at, u.full_name, u.username
     FROM activity_logs al LEFT JOIN users u ON al.user_id=u.id
     ORDER BY al.created_at DESC LIMIT 8");
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-xl font-bold text-[#001644] flex items-center gap-2">
            <i class="fas fa-tachometer-alt text-[#BF8D1A]"></i>Dashboard
        </h2>
        <p class="text-xs text-[#022E6B] mt-0.5">Bem-vindo, <?= h($_SESSION['full_name'] ?? 'Admin') ?> · <?= date('d \d\e F \d\e Y') ?></p>
    </div>
    <div class="flex gap-2">
        <a href="/crcap/admin/posts.php?action=new" class="btn-primary"><i class="fas fa-plus"></i>Nova Notícia</a>
        <a href="/crcap/admin/events.php?action=new" class="btn-gold"><i class="fas fa-calendar-plus"></i>Novo Evento</a>
    </div>
</div>

<!-- ── KPI Cards ── -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php $kpis = [
        ['fa-newspaper','Notícias Publicadas',$stats['posts'],'badge-green','/admin/posts.php',$stats['posts_d'].' rascunhos'],
        ['fa-calendar-alt','Eventos Ativos',$stats['events'],'badge-blue','/admin/events.php','próximos'],
        ['fa-envelope','Mensagens Novas',$stats['contacts'],'badge-red','/admin/contacts.php','não lidas'],
        ['fa-users','Assinantes',number_format($stats['news_subs']),'badge-gold','/admin/newsletter.php','newsletter'],
    ];
    foreach ($kpis as $k): ?>
    <a href="<?= $k[4] ?>" class="card p-5 hover:-translate-y-1 hover:shadow-lg transition group">
        <div class="flex items-start justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center">
                <i class="fas <?= $k[0] ?> text-white text-sm"></i>
            </div>
            <span class="badge <?= $k[3] ?>"><?= $k[5] ?></span>
        </div>
        <p class="text-2xl font-black text-[#001644] mb-0.5 group-hover:text-[#BF8D1A] transition"><?= $k[2] ?></p>
        <p class="text-xs text-[#022E6B]"><?= $k[1] ?></p>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── Secondary stats row ── -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
    <?php $mini = [
        ['fa-eye', number_format($stats['views']), 'Visualizações totais'],
        ['fa-file-alt', $stats['docs'], 'Documentos ativos'],
        ['fa-user-cog', $stats['users'], 'Usuários ativos'],
        ['fa-photo-video', dbFetch($pdo,"SELECT COUNT(*) AS n FROM galleries WHERE status='published'")['n']??0, 'Galerias publicadas'],
    ]; foreach ($mini as $m): ?>
    <div class="bg-white rounded-xl border border-[#001644]/5 px-4 py-3 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-[#001644]/5 flex items-center justify-center flex-shrink-0">
            <i class="fas <?= $m[0] ?> text-xs text-[#001644]"></i>
        </div>
        <div>
            <p class="font-bold text-[#001644] text-sm"><?= $m[1] ?></p>
            <p class="text-[10px] text-[#022E6B]"><?= $m[2] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Main Grid ── -->
<div class="grid lg:grid-cols-3 gap-6 mb-6">

    <!-- Recent Posts -->
    <div class="lg:col-span-2 card">
        <div class="flex items-center justify-between p-5 border-b border-[#001644]/5">
            <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2"><i class="fas fa-newspaper text-[#BF8D1A]"></i>Últimas Notícias</h3>
            <a href="/crcap/admin/posts.php" class="text-xs text-[#BF8D1A] hover:underline font-semibold">Ver todas →</a>
        </div>
        <div class="divide-y divide-[#001644]/3">
            <?php if (empty($recentPosts)): ?>
            <p class="p-8 text-center text-xs text-[#022E6B]/50">Nenhuma notícia ainda.</p>
            <?php else: foreach ($recentPosts as $p): ?>
            <div class="flex items-center gap-4 px-5 py-3 hover:bg-[#F8FAFC] transition">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-[#001644] text-xs line-clamp-1"><?= h($p['title']) ?></p>
                    <p class="text-[10px] text-[#022E6B]"><?= $p['published_at'] ? date('d/m/Y', strtotime($p['published_at'])) : 'Sem data' ?></p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-[10px] text-[#022E6B] flex items-center gap-1"><i class="fas fa-eye text-[#001644]/30"></i><?= number_format($p['views']) ?></span>
                    <span class="badge <?= $p['status']==='published'?'badge-green':($p['status']==='draft'?'badge-gray':'badge-gold') ?>"><?= $p['status'] ?></span>
                    <a href="/crcap/admin/posts.php?action=edit&id=0&slug=<?= urlencode($p['slug']) ?>" class="w-7 h-7 rounded-lg border border-[#001644]/10 flex items-center justify-center hover:border-[#BF8D1A] hover:text-[#BF8D1A] transition text-[10px]"><i class="fas fa-pen"></i></a>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="p-4 border-t border-[#001644]/5">
            <a href="/crcap/admin/posts.php?action=new" class="btn-primary w-full justify-center py-2.5 text-xs"><i class="fas fa-plus"></i>Nova Notícia</a>
        </div>
    </div>

    <!-- Agenda do Presidente -->
    <div class="card">
        <div class="flex items-center justify-between p-5 border-b border-[#001644]/5">
            <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2"><i class="fas fa-calendar-check text-[#BF8D1A]"></i>Agenda (7 dias)</h3>
            <a href="/crcap/admin/agenda.php" class="text-xs text-[#BF8D1A] hover:underline font-semibold">Gerenciar →</a>
        </div>
        <div class="p-4 space-y-2">
            <?php if (empty($schedule)): ?>
            <p class="text-center text-xs text-[#022E6B]/50 py-6">Sem compromissos próximos.</p>
            <?php else: foreach ($schedule as $s):
                $isToday = date('Y-m-d') === $s['event_date'];
            ?>
            <div class="flex gap-3 p-3 rounded-xl <?= $isToday ? 'bg-[#BF8D1A]/10 border border-[#BF8D1A]/20' : 'bg-[#F8FAFC]' ?>">
                <div class="text-center flex-shrink-0 w-10">
                    <p class="text-xs font-black text-[#001644] leading-none"><?= date('d', strtotime($s['event_date'])) ?></p>
                    <p class="text-[9px] text-[#BF8D1A] font-semibold uppercase"><?= date('M', strtotime($s['event_date'])) ?></p>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-[#001644] text-[11px] line-clamp-1"><?= h($s['title']) ?></p>
                    <p class="text-[9px] text-[#022E6B]"><?= substr($s['start_time'],0,5) ?><?= $s['location']?' · '.h($s['location']):'' ?></p>
                </div>
                <?php if ($isToday): ?><span class="badge badge-gold self-start text-[9px]">Hoje</span><?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="p-4 border-t border-[#001644]/5">
            <a href="/crcap/admin/agenda.php?action=new" class="btn-primary w-full justify-center py-2.5 text-xs"><i class="fas fa-plus"></i>Novo Compromisso</a>
        </div>
    </div>
</div>

<!-- ── Bottom Grid ── -->
<div class="grid lg:grid-cols-3 gap-6">

    <!-- Latest contacts -->
    <div class="card">
        <div class="flex items-center justify-between p-5 border-b border-[#001644]/5">
            <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2">
                <i class="fas fa-envelope text-[#BF8D1A]"></i>Contatos Recentes
                <?php if ($stats['contacts']): ?><span class="badge badge-red"><?= $stats['contacts'] ?> novo<?= $stats['contacts']>1?'s':'' ?></span><?php endif; ?>
            </h3>
            <a href="/crcap/admin/contacts.php" class="text-xs text-[#BF8D1A] hover:underline font-semibold">Ver todos →</a>
        </div>
        <div class="divide-y divide-[#001644]/3">
            <?php if (empty($latestContacts)): ?>
            <p class="p-8 text-center text-xs text-[#022E6B]/50">Nenhuma mensagem.</p>
            <?php else: foreach ($latestContacts as $c): ?>
            <div class="px-5 py-3 hover:bg-[#F8FAFC] transition">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-[#001644] text-[11px] flex items-center gap-1.5">
                            <?php if ($c['status']==='new'): ?><span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span><?php endif; ?>
                            <?= h($c['name']) ?>
                        </p>
                        <p class="text-[10px] text-[#022E6B] line-clamp-1"><?= $c['subject'] ? h($c['subject']) : h($c['email']) ?></p>
                        <p class="text-[9px] text-[#022E6B]/50"><?= date('d/m H:i', strtotime($c['created_at'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Upcoming events -->
    <div class="card">
        <div class="flex items-center justify-between p-5 border-b border-[#001644]/5">
            <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2"><i class="fas fa-calendar-alt text-[#BF8D1A]"></i>Próximos Eventos</h3>
            <a href="/crcap/admin/events.php" class="text-xs text-[#BF8D1A] hover:underline font-semibold">Ver todos →</a>
        </div>
        <div class="divide-y divide-[#001644]/3">
            <?php if (empty($upcomingEvents)): ?>
            <p class="p-8 text-center text-xs text-[#022E6B]/50">Sem eventos próximos.</p>
            <?php else: foreach ($upcomingEvents as $e): ?>
            <div class="flex items-center gap-3 px-5 py-3 hover:bg-[#F8FAFC] transition">
                <div class="w-10 h-10 rounded-xl bg-[#001644] flex flex-col items-center justify-center flex-shrink-0">
                    <span class="text-white text-xs font-black leading-none"><?= date('d',strtotime($e['event_date'])) ?></span>
                    <span class="text-[#BF8D1A] text-[8px] font-bold uppercase"><?= date('M',strtotime($e['event_date'])) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-[#001644] text-[11px] line-clamp-1"><?= h($e['title']) ?></p>
                    <p class="text-[9px] text-[#022E6B]"><?= substr($e['start_time'],0,5) ?><?= $e['location']?' · '.h(mb_substr($e['location'],0,25)):'' ?></p>
                </div>
                <span class="badge <?= $e['status']==='published'?'badge-green':'badge-gray' ?> flex-shrink-0"><?= $e['status'] ?></span>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="p-4 border-t border-[#001644]/5">
            <a href="/crcap/admin/events.php?action=new" class="btn-primary w-full justify-center py-2.5 text-xs"><i class="fas fa-plus"></i>Novo Evento</a>
        </div>
    </div>

    <!-- Activity log -->
    <div class="card">
        <div class="flex items-center justify-between p-5 border-b border-[#001644]/5">
            <h3 class="font-bold text-[#001644] text-sm flex items-center gap-2"><i class="fas fa-history text-[#BF8D1A]"></i>Atividade Recente</h3>
            <a href="/crcap/admin/logs.php" class="text-xs text-[#BF8D1A] hover:underline font-semibold">Ver logs →</a>
        </div>
        <div class="divide-y divide-[#001644]/3">
            <?php if (empty($actLogs)): ?>
            <p class="p-8 text-center text-xs text-[#022E6B]/50">Nenhuma atividade registrada.</p>
            <?php else:
            $actionColors = ['create'=>'text-[#006633]','update'=>'text-[#BF8D1A]','delete'=>'text-red-500','login'=>'text-[#001644]'];
            foreach ($actLogs as $al):
                $actionType = explode('_', $al['action'])[0];
                $color = $actionColors[$actionType] ?? 'text-[#022E6B]';
            ?>
            <div class="px-5 py-3 hover:bg-[#F8FAFC] transition">
                <div class="flex items-start gap-2">
                    <div class="w-5 h-5 rounded-full bg-[#F8FAFC] border border-[#001644]/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-circle text-[6px] <?= $color ?>"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] text-[#001644] line-clamp-1"><?= h($al['description'] ?: $al['action']) ?></p>
                        <p class="text-[9px] text-[#022E6B]/50">
                            <?= h($al['full_name'] ?? $al['username'] ?? 'Sistema') ?> · <?= date('d/m H:i', strtotime($al['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Quick action links -->
<div class="mt-6 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
    <?php $quickLinks = [
        ['/admin/posts.php?action=new',   'fa-newspaper',     'Notícia'],
        ['/admin/events.php?action=new',  'fa-calendar-plus', 'Evento'],
        ['/admin/agenda.php?action=new',  'fa-calendar-check','Agenda'],
        ['/admin/documents.php?action=new','fa-file-upload',  'Documento'],
        ['/admin/sliders.php?action=new', 'fa-images',        'Slider'],
        ['/admin/pages.php?action=new',   'fa-file-alt',      'Página'],
        ['/admin/galleries.php?action=new','fa-photo-video',  'Galeria'],
        ['/admin/users.php?action=new',   'fa-user-plus',     'Usuário'],
    ];
    foreach ($quickLinks as $ql): ?>
    <a href="<?= $ql[0] ?>" class="flex flex-col items-center gap-1.5 p-3 bg-white rounded-xl border border-[#001644]/5 hover:border-[#BF8D1A] hover:shadow-md transition group text-center">
        <div class="w-8 h-8 rounded-lg bg-[#001644]/5 group-hover:bg-[#001644] flex items-center justify-center transition">
            <i class="fas <?= $ql[1] ?> text-xs text-[#001644] group-hover:text-white transition"></i>
        </div>
        <span class="text-[10px] font-semibold text-[#022E6B] group-hover:text-[#BF8D1A] transition"><?= $ql[2] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
