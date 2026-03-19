<?php
$pageTitle = 'Logs de Atividade · Admin CRCAP';
$activeAdm = 'logs';
require_once __DIR__ . '/admin_header.php';

$page_num = max(1,(int)($_GET['p']??1));
$perPage  = 30;
$offset   = ($page_num-1)*$perPage;
$user_filter = (int)($_GET['user']??0);
$action_filter = $_GET['action_filter']??'';

$where  = ['1=1'];
$params = [];
if ($user_filter) {$where[]='l.user_id=?';$params[]=$user_filter;}
if ($action_filter){$where[]='l.action=?';$params[]=$action_filter;}

$logs  = dbFetchAll($pdo,"SELECT l.*,u.username,u.full_name FROM activity_logs l LEFT JOIN users u ON l.user_id=u.id WHERE ".implode(' AND ',$where)." ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset",$params);
$total = dbFetch($pdo,"SELECT COUNT(*) AS n FROM activity_logs l WHERE ".implode(' AND ',$where),$params)['n']??0;
$pages = ceil($total/$perPage);
$actions = dbFetchAll($pdo,"SELECT DISTINCT action FROM activity_logs ORDER BY action");
?>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2"><i class="fas fa-history text-[#BF8D1A]"></i>Logs de Atividade</h2>
    <span class="text-xs text-[#022E6B]"><?= number_format($total) ?> registros</span>
</div>

<div class="card p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div><label class="form-label">Ação</label>
        <select name="action_filter" class="form-input">
            <option value="">Todas</option>
            <?php foreach($actions as $a): ?><option value="<?= htmlspecialchars($a['action']) ?>" <?= $action_filter===$a['action']?'selected':'' ?>><?= htmlspecialchars($a['action']) ?></option><?php endforeach; ?>
        </select></div>
        <button type="submit" class="btn-primary"><i class="fas fa-search"></i>Filtrar</button>
        <a href="/crcap/admin/logs.php" class="btn-gold"><i class="fas fa-times"></i>Limpar</a>
    </form>
</div>

<div class="card overflow-hidden">
    <table class="w-full">
        <thead><tr><th class="text-left">Usuário</th><th class="text-left">Ação</th><th class="hidden md:table-cell text-left">Entidade</th><th class="hidden lg:table-cell text-left">IP</th><th class="text-right">Data/Hora</th></tr></thead>
        <tbody>
            <?php if(empty($logs)): ?>
            <tr><td colspan="5" class="text-center py-12 text-[#001644]/30"><i class="fas fa-clipboard-list text-3xl mb-3 block"></i>Nenhum log encontrado</td></tr>
            <?php else: foreach($logs as $l): ?>
            <tr>
                <td><p class="font-semibold text-[#001644] text-xs"><?= htmlspecialchars($l['full_name']??$l['username']??'Sistema') ?></p></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($l['action']) ?></span></td>
                <td class="hidden md:table-cell text-xs"><?= htmlspecialchars($l['entity_type']??'') ?><?= $l['entity_id']?' #'.$l['entity_id']:'' ?><br><span class="text-[9px] text-[#022E6B]/60 line-clamp-1"><?= htmlspecialchars(substr($l['description']??'',0,50)) ?></span></td>
                <td class="hidden lg:table-cell text-xs font-mono"><?= htmlspecialchars($l['ip_address']??'—') ?></td>
                <td class="text-right text-xs"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
            </tr>
            <?php endforeach;endif;?>
        </tbody>
    </table>
</div>

<?php if($pages>1): ?>
<div class="flex justify-center gap-2 mt-5">
    <?php if($page_num>1): ?><a href="?p=<?= $page_num-1 ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs border border-[#001644]/10 hover:border-[#BF8D1A] bg-white transition"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
    <?php for($i=1;$i<=$pages;$i++): ?><a href="?p=<?= $i ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs font-semibold transition <?= $i===$page_num?'bg-[#001644] text-white':'bg-white text-[#001644] border border-[#001644]/10 hover:border-[#BF8D1A]' ?>"><?= $i ?></a><?php endfor; ?>
    <?php if($page_num<$pages): ?><a href="?p=<?= $page_num+1 ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-xs border border-[#001644]/10 hover:border-[#BF8D1A] bg-white transition"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/admin_footer.php'; ?>
