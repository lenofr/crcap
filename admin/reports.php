<?php
$pageTitle = 'Relatórios & Estatísticas · Admin CRCAP';
$activeAdm = 'reports';
require_once __DIR__ . '/admin_header.php';

$period = (int)($_GET['periodo'] ?? 30);
$from   = date('Y-m-d', strtotime("-{$period} days"));

// ══════════════════════════════════════════════════════════════════════════════
// HELPER: safe max para arrays de totais
// ══════════════════════════════════════════════════════════════════════════════
function safeMax(array $rows, string $col = 'total', int $floor = 1): int {
    $vals = array_column($rows, $col);
    return max($floor, empty($vals) ? $floor : (int)max($vals));
}
function safeJson(array $rows, string $col): string {
    return json_encode(array_map(fn($r) => (int)($r[$col] ?? 0), $rows));
}
function safeLabels(array $rows, string $col): string {
    return json_encode(array_column($rows, $col));
}

// ══════════════════════════════════════════════════════════════════════════════
// QUERIES — dados em tempo real
// ══════════════════════════════════════════════════════════════════════════════

// ── KPIs globais ─────────────────────────────────────────────────────────────
$kpi = [
    'posts'       => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM posts WHERE status='published'")['n']??0),
    'views'       => (int)(dbFetch($pdo,"SELECT COALESCE(SUM(views),0) n FROM posts")['n']??0),
    'events'      => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM events WHERE status='published'")['n']??0),
    'registros'   => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM event_registrations")['n']??0),
    'users'       => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM users WHERE status='active'")['n']??0),
    'subs'        => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM newsletters WHERE status='subscribed'")['n']??0),
    'contacts'    => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM contacts")['n']??0),
    'docs'        => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM documents WHERE status='active'")['n']??0),
    'doc_dl'      => (int)(dbFetch($pdo,"SELECT COALESCE(SUM(downloads),0) n FROM documents")['n']??0),
    'pages'       => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM pages WHERE status='published'")['n']??0),
    'galerias'    => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM galleries WHERE status='published'")['n']??0),
    'media'       => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM media")['n']??0),
    'wpp_contacts'=> (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM whatsapp_contacts WHERE status='active'")['n']??0),
    'campaigns'   => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM email_campaigns")['n']??0),
    'emails_sent' => (int)(dbFetch($pdo,"SELECT COALESCE(SUM(sent_count),0) n FROM email_campaigns WHERE status='sent'")['n']??0),
];

// ── Período selecionado ───────────────────────────────────────────────────────
$kpiPeriod = [
    'new_posts'   => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM posts WHERE created_at>=? AND status='published'",[$from])['n']??0),
    'new_users'   => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM users WHERE created_at>=?",[$from])['n']??0),
    'new_subs'    => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM newsletters WHERE subscribed_at>=? AND status='subscribed'",[$from])['n']??0),
    'new_contacts'=> (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM contacts WHERE created_at>=?",[$from])['n']??0),
    'new_regs'    => (int)(dbFetch($pdo,"SELECT COUNT(*) n FROM event_registrations WHERE registered_at>=?",[$from])['n']??0),
    'views_period'=> (int)(dbFetch($pdo,"SELECT COALESCE(SUM(views),0) n FROM posts WHERE published_at>=?",[$from])['n']??0),
];

// ── Posts por mês (12 meses) ──────────────────────────────────────────────────
$postsByMonth = dbFetchAll($pdo,
    "SELECT DATE_FORMAT(published_at,'%b/%y') AS label, COUNT(*) AS total
     FROM posts WHERE published_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND status='published'
     GROUP BY DATE_FORMAT(published_at,'%Y-%m') ORDER BY MIN(published_at) ASC");

// ── Views por mês (posts) ─────────────────────────────────────────────────────
$viewsByMonth = dbFetchAll($pdo,
    "SELECT DATE_FORMAT(published_at,'%b/%y') AS label, COALESCE(SUM(views),0) AS total
     FROM posts WHERE published_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND status='published'
     GROUP BY DATE_FORMAT(published_at,'%Y-%m') ORDER BY MIN(published_at) ASC");

// ── Newsletter por mês ────────────────────────────────────────────────────────
$subsByMonth = dbFetchAll($pdo,
    "SELECT DATE_FORMAT(subscribed_at,'%b/%y') AS label, COUNT(*) AS total
     FROM newsletters WHERE subscribed_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(subscribed_at,'%Y-%m') ORDER BY MIN(subscribed_at) ASC");

// ── Inscrições em eventos por mês ─────────────────────────────────────────────
$regsByMonth = dbFetchAll($pdo,
    "SELECT DATE_FORMAT(registered_at,'%b/%y') AS label, COUNT(*) AS total
     FROM event_registrations WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(registered_at,'%Y-%m') ORDER BY MIN(registered_at) ASC");

// ── Contatos por mês ──────────────────────────────────────────────────────────
$contactsByMonth = dbFetchAll($pdo,
    "SELECT DATE_FORMAT(created_at,'%b/%y') AS label, COUNT(*) AS total
     FROM contacts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) ASC");

// ── Top 10 posts por views ────────────────────────────────────────────────────
$topPosts = dbFetchAll($pdo,
    "SELECT p.title, p.views, DATE_FORMAT(p.published_at,'%d/%m/%Y') AS pub, c.name AS cat
     FROM posts p LEFT JOIN categories c ON p.category_id=c.id
     WHERE p.status='published' ORDER BY p.views DESC LIMIT 10");

// ── Posts por categoria ───────────────────────────────────────────────────────
$postsByCat = dbFetchAll($pdo,
    "SELECT COALESCE(c.name,'Sem categoria') AS label, COUNT(p.id) AS total
     FROM posts p LEFT JOIN categories c ON p.category_id=c.id
     WHERE p.status='published'
     GROUP BY c.id ORDER BY total DESC LIMIT 8");

// ── Status newsletter ─────────────────────────────────────────────────────────
$subStatus = dbFetchAll($pdo,
    "SELECT status AS label, COUNT(*) AS total FROM newsletters GROUP BY status");

// ── Categorias newsletter ─────────────────────────────────────────────────────
$subCat = dbFetchAll($pdo,
    "SELECT COALESCE(NULLIF(categoria,''),'Não informado') AS label, COUNT(*) AS total
     FROM newsletters WHERE status='subscribed' GROUP BY label ORDER BY total DESC");

// ── Top eventos por inscrição ─────────────────────────────────────────────────
$topEvents = dbFetchAll($pdo,
    "SELECT e.title AS label, COUNT(er.id) AS total
     FROM events e LEFT JOIN event_registrations er ON er.event_id=e.id
     GROUP BY e.id ORDER BY total DESC LIMIT 8");

// ── Status contatos ───────────────────────────────────────────────────────────
$contactStatus = dbFetchAll($pdo,
    "SELECT COALESCE(status,'pendente') AS label, COUNT(*) AS total FROM contacts GROUP BY status");

// ── Usuários por role ─────────────────────────────────────────────────────────
$userRoles = dbFetchAll($pdo,
    "SELECT role AS label, COUNT(*) AS total FROM users WHERE status='active' GROUP BY role");

// ── Documentos por categoria ──────────────────────────────────────────────────
$docsByCat = dbFetchAll($pdo,
    "SELECT COALESCE(NULLIF(category,''),'Outros') AS label, COUNT(*) AS total,
            COALESCE(SUM(downloads),0) AS downloads
     FROM documents WHERE status='active' GROUP BY category ORDER BY total DESC LIMIT 8");

// ── Campanhas email por status ────────────────────────────────────────────────
$campStatus = dbFetchAll($pdo,
    "SELECT status AS label, COUNT(*) AS total FROM email_campaigns GROUP BY status");

// ── Logs de atividade por ação (últimos 30 dias) ──────────────────────────────
$activityLog = dbFetchAll($pdo,
    "SELECT DATE_FORMAT(created_at,'%d/%m') AS label, COUNT(*) AS total
     FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC");

// ── Galerias por categoria ────────────────────────────────────────────────────
$galCat = dbFetchAll($pdo,
    "SELECT COALESCE(NULLIF(category,''),'Sem categoria') AS label, COUNT(*) AS total,
            COALESCE(SUM(views),0) AS views
     FROM galleries WHERE status='published' GROUP BY category ORDER BY total DESC LIMIT 6");

// Timestamp de atualização
$updatedAt = date('d/m/Y H:i:s');
?>

<style>
.stat-card { background:#fff; border-radius:16px; border:1px solid rgba(0,22,68,.06); box-shadow:0 1px 8px rgba(0,22,68,.05); }
.chart-card { background:#fff; border-radius:16px; border:1px solid rgba(0,22,68,.06); box-shadow:0 1px 8px rgba(0,22,68,.05); padding:20px; }
.kpi-value { font-size:28px; font-weight:800; color:#001644; line-height:1; }
.kpi-label { font-size:11px; color:rgba(2,46,107,.55); margin-top:4px; }
.kpi-delta { font-size:10px; font-weight:600; }
.section-title { font-size:13px; font-weight:700; color:#001644; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
</style>

<!-- Header ────────────────────────────────────────────────────────────────── -->
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2">
            <i class="fas fa-chart-bar text-[#BF8D1A]"></i>Relatórios & Estatísticas
        </h2>
        <p class="text-xs text-[#022E6B]/60 mt-0.5">
            Visão geral em tempo real · Atualizado em <span class="font-semibold"><?= $updatedAt ?></span>
        </p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-[#022E6B]/50 mr-1">Período:</span>
        <?php foreach ([7=>'7d',30=>'30d',90=>'90d',365=>'1 ano'] as $d=>$l): ?>
        <a href="?periodo=<?= $d ?>"
           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition
                  <?= $period==$d ? 'bg-[#001644] text-white' : 'bg-[#F8FAFC] text-[#001644]/60 hover:bg-[#001644]/10 border border-[#001644]/08' ?>">
            <?= $l ?>
        </a>
        <?php endforeach; ?>
        <button onclick="window.print()"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#F8FAFC] text-[#001644]/60 hover:bg-[#001644]/10 border border-[#001644]/08 transition">
            <i class="fas fa-print text-[10px]"></i>Imprimir
        </button>
    </div>
</div>

<!-- ══ KPIs GLOBAIS ════════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
<?php
$kpiCards = [
    ['Posts publicados',   $kpi['posts'],        $kpiPeriod['new_posts'],   'fa-newspaper',      '#001644', '#E8F0FE'],
    ['Visualizações',      $kpi['views'],         $kpiPeriod['views_period'],'fa-eye',            '#006633', '#E8F5E9'],
    ['Eventos ativos',     $kpi['events'],        $kpiPeriod['new_regs'],    'fa-calendar-check', '#BF8D1A', '#FFF8E1'],
    ['Inscrições eventos', $kpi['registros'],     $kpiPeriod['new_regs'],    'fa-user-check',     '#7C3AED', '#F3E8FF'],
    ['Usuários ativos',    $kpi['users'],         $kpiPeriod['new_users'],   'fa-users',          '#0891B2', '#E0F7FA'],
    ['Newsletter',         $kpi['subs'],          $kpiPeriod['new_subs'],    'fa-envelope',       '#D97706', '#FFF3E0'],
    ['Contatos recebidos', $kpi['contacts'],      $kpiPeriod['new_contacts'],'fa-comments',       '#DC2626', '#FEE2E2'],
    ['Documentos',         $kpi['docs'],          $kpi['doc_dl'],            'fa-file-alt',       '#059669', '#D1FAE5'],
    ['Galerias',           $kpi['galerias'],      0,                         'fa-images',         '#DB2777', '#FCE7F3'],
    ['E-mails enviados',   $kpi['emails_sent'],   0,                         'fa-paper-plane',    '#4F46E5', '#EEF2FF'],
];
foreach ($kpiCards as [$label, $val, $delta, $icon, $color, $bg]): ?>
<div class="stat-card p-4 flex flex-col gap-2">
    <div class="flex items-center justify-between">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background:<?= $bg ?>">
            <i class="fas <?= $icon ?> text-sm" style="color:<?= $color ?>"></i>
        </div>
        <?php if ($delta > 0): ?>
        <span class="kpi-delta text-green-600 bg-green-50 px-1.5 py-0.5 rounded-lg">
            +<?= number_format($delta) ?>
        </span>
        <?php endif; ?>
    </div>
    <div>
        <div class="kpi-value"><?= number_format($val) ?></div>
        <div class="kpi-label"><?= $label ?></div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- ══ LINHA 1: Posts + Views (linha do tempo) ════════════════════════════════ -->
<div class="grid lg:grid-cols-2 gap-4 mb-4">

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-newspaper text-[#BF8D1A]"></i>Publicações por Mês</div>
        <canvas id="chartPostsMonth" height="200"></canvas>
    </div>

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-eye text-[#006633]"></i>Visualizações por Mês</div>
        <canvas id="chartViewsMonth" height="200"></canvas>
    </div>

</div>

<!-- ══ LINHA 2: Newsletter + Inscrições Eventos ═══════════════════════════════ -->
<div class="grid lg:grid-cols-2 gap-4 mb-4">

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-envelope text-[#D97706]"></i>Novos Assinantes por Mês</div>
        <canvas id="chartSubsMonth" height="200"></canvas>
    </div>

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-user-check text-[#7C3AED]"></i>Inscrições em Eventos por Mês</div>
        <canvas id="chartRegsMonth" height="200"></canvas>
    </div>

</div>

<!-- ══ LINHA 3: Categorias Posts + Status Newsletter ══════════════════════════ -->
<div class="grid lg:grid-cols-3 gap-4 mb-4">

    <div class="chart-card lg:col-span-2">
        <div class="section-title"><i class="fas fa-tags text-[#0891B2]"></i>Posts por Categoria</div>
        <canvas id="chartPostsCat" height="180"></canvas>
    </div>

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-chart-pie text-[#D97706]"></i>Status Newsletter</div>
        <canvas id="chartSubStatus" height="180"></canvas>
    </div>

</div>

<!-- ══ LINHA 4: Categorias Newsletter + Usuários por Role ═════════════════════ -->
<div class="grid lg:grid-cols-3 gap-4 mb-4">

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-layer-group text-[#D97706]"></i>Assinantes por Categoria</div>
        <canvas id="chartSubCat" height="220"></canvas>
    </div>

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-user-tag text-[#0891B2]"></i>Usuários por Perfil</div>
        <canvas id="chartUserRoles" height="220"></canvas>
    </div>

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-headset text-[#DC2626]"></i>Status dos Contatos</div>
        <canvas id="chartContactStatus" height="220"></canvas>
    </div>

</div>

<!-- ══ LINHA 5: Contatos por Mês + Atividades ═════════════════════════════════ -->
<div class="grid lg:grid-cols-2 gap-4 mb-4">

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-comments text-[#DC2626]"></i>Contatos por Mês</div>
        <canvas id="chartContactsMonth" height="200"></canvas>
    </div>

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-history text-[#4F46E5]"></i>Atividades no Sistema (30 dias)</div>
        <?php if (empty($activityLog)): ?>
        <div class="text-center py-12 text-[#022E6B]/30 text-xs">Nenhuma atividade registrada</div>
        <?php else: ?>
        <canvas id="chartActivity" height="200"></canvas>
        <?php endif; ?>
    </div>

</div>

<!-- ══ LINHA 6: Top Eventos + Documentos ══════════════════════════════════════ -->
<div class="grid lg:grid-cols-2 gap-4 mb-4">

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-calendar-check text-[#7C3AED]"></i>Top Eventos por Inscrições</div>
        <canvas id="chartTopEvents" height="220"></canvas>
    </div>

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-file-alt text-[#059669]"></i>Documentos por Categoria</div>
        <canvas id="chartDocsCat" height="220"></canvas>
    </div>

</div>

<!-- ══ LINHA 7: Campanhas + Galerias ══════════════════════════════════════════ -->
<div class="grid lg:grid-cols-2 gap-4 mb-6">

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-paper-plane text-[#4F46E5]"></i>Campanhas de E-mail por Status</div>
        <canvas id="chartCampStatus" height="220"></canvas>
    </div>

    <div class="chart-card">
        <div class="section-title"><i class="fas fa-images text-[#DB2777]"></i>Galerias por Categoria</div>
        <?php if (empty($galCat)): ?>
        <div class="text-center py-12 text-[#022E6B]/30 text-xs">Nenhuma galeria publicada</div>
        <?php else: ?>
        <canvas id="chartGalCat" height="220"></canvas>
        <?php endif; ?>
    </div>

</div>

<!-- ══ TABELA: Top 10 Posts ════════════════════════════════════════════════════ -->
<div class="chart-card mb-6">
    <div class="section-title"><i class="fas fa-trophy text-[#BF8D1A]"></i>Top 10 Posts — Mais Visualizados</div>
    <?php if (empty($topPosts)): ?>
    <p class="text-xs text-center py-8 text-[#022E6B]/30">Nenhum post publicado ainda</p>
    <?php else: ?>
    <div class="overflow-x-auto">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b border-[#001644]/08">
                <th class="text-left py-2 px-3 text-[#ffffff]/50 font-semibold">#</th>
                <th class="text-left py-2 px-3 text-[#ffffff]/50 font-semibold">Título</th>
                <th class="text-left py-2 px-3 text-[#ffffff]/50 font-semibold">Categoria</th>
                <th class="text-left py-2 px-3 text-[#ffffff]/50 font-semibold">Publicado</th>
                <th class="text-right py-2 px-3 text-[#ffffff]/50 font-semibold">Views</th>
                <th class="text-right py-2 px-3 text-[#ffffff]/50 font-semibold">%</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $maxViews = safeMax($topPosts, 'views');
        foreach ($topPosts as $i => $p):
            $pct = $maxViews > 0 ? round($p['views'] / $maxViews * 100) : 0;
        ?>
            <tr class="border-b border-[#001644]/04 hover:bg-[#F8FAFC] transition">
                <td class="py-2.5 px-3 font-bold text-[#001644]/30"><?= $i+1 ?></td>
                <td class="py-2.5 px-3">
                    <span class="font-semibold text-[#001644] line-clamp-1"><?= htmlspecialchars($p['title']) ?></span>
                </td>
                <td class="py-2.5 px-3">
                    <?php if ($p['cat']): ?>
                    <span class="px-2 py-0.5 bg-[#001644]/05 text-[#001644]/60 rounded-full"><?= htmlspecialchars($p['cat']) ?></span>
                    <?php else: ?><span class="text-[#022E6B]/30">—</span><?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-[#022E6B]/60"><?= $p['pub'] ?></td>
                <td class="py-2.5 px-3 text-right font-bold text-[#001644]"><?= number_format($p['views']) ?></td>
                <td class="py-2.5 px-3 text-right w-28">
                    <div class="flex items-center justify-end gap-2">
                        <div class="flex-1 h-1.5 bg-[#001644]/08 rounded-full overflow-hidden" style="max-width:60px">
                            <div class="h-full bg-[#BF8D1A] rounded-full" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="text-[#022E6B]/50 w-8 text-right"><?= $pct ?>%</span>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- ══ TABELA: Documentos com Downloads ═══════════════════════════════════════ -->
<div class="chart-card mb-6">
    <div class="section-title"><i class="fas fa-download text-[#059669]"></i>Downloads por Categoria de Documento</div>
    <?php if (empty($docsByCat)): ?>
    <p class="text-xs text-center py-8 text-[#022E6B]/30">Nenhum documento cadastrado</p>
    <?php else: ?>
    <div class="overflow-x-auto">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b border-[#001644]/08">
                <th class="text-left py-2 px-3 text-[#ffffff]/50 font-semibold">Categoria</th>
                <th class="text-center py-2 px-3 text-[#ffffff]/50 font-semibold">Documentos</th>
                <th class="text-right py-2 px-3 text-[#ffffff]/50 font-semibold">Downloads</th>
                <th class="text-right py-2 px-3 text-[#ffffff]/50 font-semibold">Barra</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $maxDl = safeMax($docsByCat, 'downloads');
        foreach ($docsByCat as $d):
            $pct = $maxDl > 0 ? round((int)$d['downloads'] / $maxDl * 100) : 0;
        ?>
            <tr class="border-b border-[#001644]/04 hover:bg-[#F8FAFC] transition">
                <td class="py-2.5 px-3 font-semibold text-[#001644]"><?= htmlspecialchars($d['label']) ?></td>
                <td class="py-2.5 px-3 text-center text-[#022E6B]/70"><?= number_format($d['total']) ?></td>
                <td class="py-2.5 px-3 text-right font-bold text-[#059669]"><?= number_format($d['downloads']) ?></td>
                <td class="py-2.5 px-3 w-32">
                    <div class="h-2 bg-[#001644]/08 rounded-full overflow-hidden">
                        <div class="h-full bg-[#059669] rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- ══ SCRIPTS — Chart.js ══════════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ── Paleta CRCAP ──────────────────────────────────────────────────────────────
const C = {
    navy:   '#001644', gold:    '#BF8D1A', green:  '#006633',
    purple: '#7C3AED', teal:    '#0891B2', red:    '#DC2626',
    orange: '#D97706', indigo:  '#4F46E5', pink:   '#DB2777',
    emerald:'#059669',
    palette: ['#001644','#BF8D1A','#006633','#7C3AED','#0891B2','#DC2626','#D97706','#4F46E5','#DB2777','#059669','#9CA3AF','#F59E0B']
};

Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#64748b';
Chart.defaults.plugins.legend.labels.boxWidth = 12;
Chart.defaults.plugins.legend.labels.padding  = 14;

function lineChart(id, labels, data, color, label) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label,
                data,
                borderColor: color,
                backgroundColor: color + '18',
                borderWidth: 2.5,
                pointBackgroundColor: color,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' },
                     ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
}

function barChart(id, labels, data, colors, label) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    const bg = Array.isArray(colors) ? colors : data.map((_,i) => C.palette[i % C.palette.length]);
    new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label, data, backgroundColor: bg, borderRadius: 6 }] },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
}

function horizBar(id, labels, data, color) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ data, backgroundColor: data.map((_,i)=>C.palette[i%C.palette.length]), borderRadius:4 }] },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero:true, grid:{color:'#f1f5f9'}, ticks:{precision:0} },
                y: { grid:{display:false}, ticks:{font:{size:10}} }
            }
        }
    });
}

function doughnut(id, labels, data) {
    const ctx = document.getElementById(id);
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data, backgroundColor: C.palette.slice(0,data.length),
                         borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true,
            cutout: '60%',
            plugins: { legend: { position: 'bottom', labels: { font:{size:10} } } }
        }
    });
}

// ── Dados PHP → JS (JSON seguro) ──────────────────────────────────────────────
const data = {
    postsByMonth:    { labels: <?= safeLabels($postsByMonth,'label') ?>,   vals: <?= safeJson($postsByMonth,'total') ?> },
    viewsByMonth:    { labels: <?= safeLabels($viewsByMonth,'label') ?>,   vals: <?= safeJson($viewsByMonth,'total') ?> },
    subsByMonth:     { labels: <?= safeLabels($subsByMonth,'label') ?>,    vals: <?= safeJson($subsByMonth,'total') ?> },
    regsByMonth:     { labels: <?= safeLabels($regsByMonth,'label') ?>,    vals: <?= safeJson($regsByMonth,'total') ?> },
    contactsByMonth: { labels: <?= safeLabels($contactsByMonth,'label') ?>,vals: <?= safeJson($contactsByMonth,'total') ?> },
    postsByCat:      { labels: <?= safeLabels($postsByCat,'label') ?>,     vals: <?= safeJson($postsByCat,'total') ?> },
    subStatus:       { labels: <?= safeLabels($subStatus,'label') ?>,      vals: <?= safeJson($subStatus,'total') ?> },
    subCat:          { labels: <?= safeLabels($subCat,'label') ?>,         vals: <?= safeJson($subCat,'total') ?> },
    topEvents:       { labels: <?= safeLabels($topEvents,'label') ?>,      vals: <?= safeJson($topEvents,'total') ?> },
    contactStatus:   { labels: <?= safeLabels($contactStatus,'label') ?>,  vals: <?= safeJson($contactStatus,'total') ?> },
    userRoles:       { labels: <?= safeLabels($userRoles,'label') ?>,      vals: <?= safeJson($userRoles,'total') ?> },
    docsByCat:       { labels: <?= safeLabels($docsByCat,'label') ?>,      vals: <?= safeJson($docsByCat,'total') ?> },
    campStatus:      { labels: <?= safeLabels($campStatus,'label') ?>,     vals: <?= safeJson($campStatus,'total') ?> },
    activityLog:     { labels: <?= safeLabels($activityLog,'label') ?>,    vals: <?= safeJson($activityLog,'total') ?> },
    galCat:          { labels: <?= safeLabels($galCat,'label') ?>,         vals: <?= safeJson($galCat,'total') ?> },
};

// ── Render dos gráficos ───────────────────────────────────────────────────────
lineChart('chartPostsMonth',    data.postsByMonth.labels,    data.postsByMonth.vals,    C.navy,   'Publicações');
lineChart('chartViewsMonth',    data.viewsByMonth.labels,    data.viewsByMonth.vals,    C.green,  'Visualizações');
lineChart('chartSubsMonth',     data.subsByMonth.labels,     data.subsByMonth.vals,     C.orange, 'Novos assinantes');
lineChart('chartRegsMonth',     data.regsByMonth.labels,     data.regsByMonth.vals,     C.purple, 'Inscrições');
lineChart('chartContactsMonth', data.contactsByMonth.labels, data.contactsByMonth.vals, C.red,    'Contatos');
lineChart('chartActivity',      data.activityLog.labels,     data.activityLog.vals,     C.indigo, 'Atividades');

barChart('chartPostsCat',   data.postsByCat.labels,    data.postsByCat.vals);
horizBar('chartTopEvents',  data.topEvents.labels,     data.topEvents.vals);
horizBar('chartDocsCat',    data.docsByCat.labels,     data.docsByCat.vals);

doughnut('chartSubStatus',     data.subStatus.labels,     data.subStatus.vals);
doughnut('chartSubCat',        data.subCat.labels,        data.subCat.vals);
doughnut('chartUserRoles',     data.userRoles.labels,     data.userRoles.vals);
doughnut('chartContactStatus', data.contactStatus.labels, data.contactStatus.vals);
doughnut('chartCampStatus',    data.campStatus.labels,    data.campStatus.vals);
doughnut('chartGalCat',        data.galCat.labels,        data.galCat.vals);
</script>