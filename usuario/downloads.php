<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = currentUser();
$pageTitle = 'Downloads · CRCAP';
$activeMenu = '';

// Public documents available for download
$search = trim($_GET['q'] ?? '');
$cat    = $_GET['cat'] ?? '';
$pg     = max(1, (int)($_GET['p'] ?? 1));
$pp     = 15;
$off    = ($pg-1)*$pp;

$where  = ["status='active'", "is_public=1"];
$params = [];
if ($search) { $where[] = "(title LIKE ? OR description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($cat)    { $where[] = "category=?"; $params[] = $cat; }
$sql = implode(' AND ', $where);

$docs  = dbFetchAll($pdo, "SELECT * FROM documents WHERE $sql ORDER BY publication_date DESC LIMIT $pp OFFSET $off", $params);
$total = dbFetch($pdo, "SELECT COUNT(*) AS n FROM documents WHERE $sql", $params)['n'] ?? 0;
$pages = ceil($total/$pp);

$categories = dbFetchAll($pdo, "SELECT DISTINCT category FROM documents WHERE is_public=1 AND status='active' AND category IS NOT NULL ORDER BY category");

$catLabels = ['editais'=>'Editais','atas'=>'Atas','relatorios'=>'Relatórios','resolucoes'=>'Resoluções','formularios'=>'Formulários','outros'=>'Outros'];

include __DIR__ . '/../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] to-[#022E6B] text-white py-10">
    <div class="container mx-auto px-4">
        <nav class="flex items-center gap-2 text-white/50 text-xs mb-4">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <a href="/crcap/usuario/perfil.php" class="hover:text-white transition">Meu Perfil</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Downloads</span>
        </nav>
        <h1 class="font-serif text-2xl font-bold">Biblioteca de Documentos</h1>
        <p class="text-white/70 text-sm mt-1">Acesse e baixe documentos oficiais do CRCAP</p>
    </div>
</section>

<main class="container mx-auto px-4 py-10">
    <div class="grid lg:grid-cols-4 gap-8">
        <!-- Sidebar -->
        <aside class="space-y-4">
            <!-- User nav -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/5 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-xl font-bold">
                        <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-bold text-[#001644] text-sm"><?= h($user['full_name'] ?? $user['username']) ?></p>
                        <p class="text-[10px] text-[#022E6B]"><?= h($user['email']) ?></p>
                    </div>
                </div>
                <nav class="space-y-1">
                    <a href="/crcap/usuario/perfil.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition"><i class="fas fa-user w-4 text-center"></i>Meu Perfil</a>
                    <a href="/crcap/usuario/inscricoes.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition"><i class="fas fa-calendar-check w-4 text-center"></i>Minhas Inscrições</a>
                    <a href="/crcap/usuario/downloads.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold bg-[#001644] text-white"><i class="fas fa-download w-4 text-center"></i>Downloads</a>
                    <a href="/crcap/usuario/mensagens.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition"><i class="fas fa-envelope w-4 text-center"></i>Minhas Mensagens</a>
                </nav>
            </div>

            <!-- Category filter -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/5 shadow-sm">
                <h3 class="font-bold text-[#001644] text-xs mb-3">Categorias</h3>
                <nav class="space-y-1">
                    <a href="/crcap/usuario/downloads.php" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition <?= !$cat ? 'bg-[#001644] text-white' : 'text-[#022E6B] hover:bg-[#F8FAFC]' ?>">
                        <span>Todos</span>
                        <span class="<?= !$cat?'text-white/60':'text-[#022E6B]/40' ?>"><?= $total ?></span>
                    </a>
                    <?php foreach ($categories as $c):
                        $cCount = dbFetch($pdo,"SELECT COUNT(*) AS n FROM documents WHERE is_public=1 AND status='active' AND category=?",[$c['category']])['n']??0;
                    ?>
                    <a href="?cat=<?= urlencode($c['category']) ?>" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition <?= $cat===$c['category'] ? 'bg-[#001644] text-white' : 'text-[#022E6B] hover:bg-[#F8FAFC]' ?>">
                        <span><?= h($catLabels[$c['category']] ?? ucfirst($c['category'])) ?></span>
                        <span class="<?= $cat===$c['category']?'text-white/60':'text-[#022E6B]/40' ?>"><?= $cCount ?></span>
                    </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </aside>

        <!-- Documents list -->
        <div class="lg:col-span-3">
            <!-- Search -->
            <form method="GET" class="flex gap-2 mb-6">
                <?php if ($cat): ?><input type="hidden" name="cat" value="<?= h($cat) ?>"><?php endif; ?>
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-[#001644]/30 text-xs"></i>
                    <input type="text" name="q" value="<?= h($search) ?>" placeholder="Buscar documentos..."
                           class="w-full pl-9 pr-4 py-2.5 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-white">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#022E6B] transition">Buscar</button>
                <?php if ($search || $cat): ?>
                <a href="/crcap/usuario/downloads.php" class="px-4 py-2.5 border border-[#001644]/10 text-[#022E6B] rounded-xl text-xs hover:border-[#BF8D1A] transition flex items-center"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>

            <div class="flex items-center justify-between mb-4">
                <p class="text-xs text-[#022E6B]"><strong class="text-[#001644]"><?= number_format($total) ?></strong> documento<?= $total!==1?'s':'' ?></p>
                <?php if ($pg > 1): ?><p class="text-xs text-[#022E6B]">Página <?= $pg ?> de <?= $pages ?></p><?php endif; ?>
            </div>

            <?php if (empty($docs)): ?>
            <div class="bg-white rounded-2xl p-16 text-center border border-[#001644]/5 shadow-sm">
                <i class="fas fa-file-search text-4xl text-[#001644]/15 mb-4 block"></i>
                <p class="font-semibold text-[#001644] mb-1">Nenhum documento encontrado</p>
                <?php if ($search): ?>
                <p class="text-xs text-[#022E6B]">Tente outros termos de busca</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($docs as $doc):
                    $extColors = ['pdf'=>'bg-red-50 text-red-500','doc'=>'bg-blue-50 text-blue-500','docx'=>'bg-blue-50 text-blue-500','xls'=>'bg-green-50 text-green-500','xlsx'=>'bg-green-50 text-green-500'];
                    $extColor  = $extColors[strtolower($doc['file_type']??'')] ?? 'bg-gray-100 text-gray-500';
                    $extIcon   = in_array(strtolower($doc['file_type']??''),['pdf']) ? 'fa-file-pdf' : (in_array($doc['file_type'],['xls','xlsx'])? 'fa-file-excel':'fa-file-word');
                ?>
                <div class="flex gap-4 bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm hover:shadow-md hover:border-[#BF8D1A]/30 transition group">
                    <div class="w-12 h-12 rounded-xl <?= $extColor ?> flex items-center justify-center flex-shrink-0">
                        <i class="fas <?= $extIcon ?> text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-[#001644] text-sm mb-1 group-hover:text-[#BF8D1A] transition line-clamp-1"><?= h($doc['title']) ?></h3>
                        <?php if ($doc['description']): ?>
                        <p class="text-xs text-[#022E6B] line-clamp-1 mb-2"><?= h($doc['description']) ?></p>
                        <?php endif; ?>
                        <div class="flex flex-wrap gap-3 text-[10px] text-[#022E6B]">
                            <?php if ($doc['publication_date']): ?>
                            <span><i class="fas fa-calendar text-[#BF8D1A] mr-1"></i><?= date('d/m/Y', strtotime($doc['publication_date'])) ?></span>
                            <?php endif; ?>
                            <?php if ($doc['file_size']): ?>
                            <span><i class="fas fa-hdd text-[#BF8D1A] mr-1"></i><?= round($doc['file_size']/1024) ?> KB</span>
                            <?php endif; ?>
                            <span><i class="fas fa-download text-[#BF8D1A] mr-1"></i><?= number_format($doc['downloads']) ?> download<?= $doc['downloads']!==1?'s':'' ?></span>
                            <?php if ($doc['category']): ?>
                            <span class="px-2 py-0.5 bg-[#001644]/5 text-[#001644] rounded-full font-semibold"><?= h($catLabels[$doc['category']] ?? $doc['category']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="/crcap/pages/download.php?id=<?= $doc['id'] ?>"
                       class="flex-shrink-0 self-center px-4 py-2 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#BF8D1A] transition flex items-center gap-2">
                        <i class="fas fa-download"></i>Baixar
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="flex justify-center gap-2 mt-6">
                <?php if ($pg > 1): ?>
                <a href="?cat=<?= urlencode($cat) ?>&q=<?= urlencode($search) ?>&p=<?= $pg-1 ?>" class="w-9 h-9 rounded-xl bg-white border border-[#001644]/10 flex items-center justify-center hover:border-[#BF8D1A] transition text-xs"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i=max(1,$pg-2);$i<=min($pages,$pg+2);$i++): ?>
                <a href="?cat=<?= urlencode($cat) ?>&q=<?= urlencode($search) ?>&p=<?= $i ?>"
                   class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-semibold transition <?= $i===$pg?'bg-[#001644] text-white':'bg-white border border-[#001644]/10 text-[#001644] hover:border-[#BF8D1A]' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($pg < $pages): ?>
                <a href="?cat=<?= urlencode($cat) ?>&q=<?= urlencode($search) ?>&p=<?= $pg+1 ?>" class="w-9 h-9 rounded-xl bg-white border border-[#001644]/10 flex items-center justify-center hover:border-[#BF8D1A] transition text-xs"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
