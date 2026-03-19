<?php
// crcap/sse-stream.php
if (ob_get_level()) ob_end_clean();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/includes/db.php';

$ultimoIdPost   = (int)($_GET['ultimo_post_id']   ?? 0);
$ultimoIdAgenda = (int)($_GET['ultimo_agenda_id'] ?? 0);
$limite = time() + 240;

function sseEvento(string $evento, array $dados): void {
    echo "event: {$evento}\n";
    echo "data: " . json_encode($dados, JSON_UNESCAPED_UNICODE) . "\n\n";
    ob_flush(); flush();
}

function gerarHtmlPost(array $p): string {
    $img   = htmlspecialchars($p['featured_image'] ?: 'https://images.unsplash.com/photo-1450101499163-c627a92ad1ab?w=100&h=100&fit=crop');
    $slug  = urlencode($p['slug']);
    $cat   = htmlspecialchars($p['cat_name'] ?? 'Geral');
    $data  = date('d/m', strtotime($p['published_at']));
    $title = htmlspecialchars($p['title']);
    $id    = (int)$p['id'];
    return '<a href="/crcap/pages/post.php?slug='.$slug.'" class="post-novo post-row flex gap-3 p-2.5 rounded-xl bg-[#F8FAFC] hover:bg-white border border-transparent hover:border-[#BF8D1A]/25 hover:shadow-sm group" data-post-id="'.$id.'" style="text-decoration:none;"><img src="'.$img.'" alt="" class="w-12 h-12 rounded-lg object-cover flex-shrink-0"><div class="min-w-0"><div class="flex items-center gap-1.5 mb-0.5"><span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#001644]/8 text-[#001644] truncate max-w-[80px]">'.$cat.'</span><span class="text-[9px] text-[#022E6B]/55 flex-shrink-0">'.$data.'</span></div><h4 class="text-xs font-semibold text-[#001644] line-clamp-2 group-hover:text-[#BF8D1A] transition leading-snug">'.$title.'</h4></div></a>';
}

function gerarHtmlAgenda(array $ev): string {
    $data  = date('d M', strtotime($ev['event_date']));
    $hora  = substr($ev['start_time'] ?? '00:00', 0, 5);
    $title = htmlspecialchars($ev['title'] ?? $ev['description'] ?? 'Novo evento');
    $id    = (int)$ev['id'];
    $statusMap = [
        'scheduled' => ['cor'=>'#BF8D1A','label'=>'Agendado', 'bg'=>'#fef9ee'],
        'confirmed' => ['cor'=>'#16a34a','label'=>'Confirmado','bg'=>'#dcfce7'],
        'cancelled' => ['cor'=>'#dc2626','label'=>'Cancelado', 'bg'=>'#fee2e2'],
    ];
    $s = $statusMap[$ev['status']] ?? $statusMap['scheduled'];
    return '<a href="/crcap/pages/agenda.php" class="tl-item agenda-novo block relative group" data-agenda-id="'.$id.'" style="text-decoration:none;"><div class="absolute -left-[1.4rem] w-2.5 h-2.5 rounded-full border-2 top-3 bg-white border-[#001644]"></div><div class="tl-card bg-[#F8FAFC] rounded-xl overflow-hidden"><div class="min-w-0 flex-1 p-2.5"><div class="flex items-center justify-between mb-0.5"><span class="text-[10px] font-bold uppercase tracking-wider text-[#022E6B]">'.$data.', '.$hora.'</span><span class="flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-bold" style="background:'.$s['bg'].';color:'.$s['cor'].'">'.$s['label'].'</span></div><h4 class="text-xs font-semibold text-[#001644] line-clamp-2 leading-snug">'.$title.'</h4></div></div></a>';
}

while (time() < $limite) {

    // 1) Novos posts
    $novosPosts = dbFetchAll($pdo,
        "SELECT p.*, c.name AS cat_name, c.color AS cat_color
         FROM posts p LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.status = 'published' AND p.id > ?
         ORDER BY p.published_at DESC LIMIT 5",
        [$ultimoIdPost]
    );
    if (!empty($novosPosts)) {
        sseEvento('novidade', [
            'tipo'     => 'post',
            'html'     => array_map('gerarHtmlPost', $novosPosts),
            'novo_max' => max(array_column($novosPosts, 'id')),
        ]);
        $ultimoIdPost = max(array_column($novosPosts, 'id'));
    }

    // 2) Nova agenda
    $novaAgenda = dbFetchAll($pdo,
        "SELECT * FROM president_schedule
         WHERE is_public = 1 AND id > ? AND status IN ('scheduled','confirmed','cancelled')
         ORDER BY event_date ASC, start_time ASC LIMIT 5",
        [$ultimoIdAgenda]
    );
    if (!empty($novaAgenda)) {
        sseEvento('novidade', [
            'tipo'     => 'agenda',
            'html'     => array_map('gerarHtmlAgenda', $novaAgenda),
            'novo_max' => max(array_column($novaAgenda, 'id')),
        ]);
        $ultimoIdAgenda = max(array_column($novaAgenda, 'id'));
    }

    // 3) SYNC — envia IDs ativos para JS detectar deleções/despublicações
    $idsPostsAtivos  = dbFetchAll($pdo,
        "SELECT id FROM posts WHERE status='published' ORDER BY published_at DESC LIMIT 4");
    $idsAgendaAtivos = dbFetchAll($pdo,
        "SELECT id FROM president_schedule
         WHERE is_public=1 AND event_date>=CURDATE() AND status IN ('scheduled','confirmed','cancelled')
         ORDER BY event_date ASC LIMIT 4");

    sseEvento('sync', [
        'post_ids'   => array_column($idsPostsAtivos,  'id'),
        'agenda_ids' => array_column($idsAgendaAtivos, 'id'),
    ]);

    echo ": heartbeat\n\n";
    ob_flush(); flush();
    sleep(15);
}

sseEvento('reconectar', ['msg' => 'ok']);