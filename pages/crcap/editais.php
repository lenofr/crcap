<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle = 'Editais';
$activePage = 'crcap-editais';

$search = trim($_GET['q'] ?? '');
$tipo   = trim($_GET['tipo'] ?? '');
$ano    = (int)($_GET['ano'] ?? 0);
$pg     = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($pg - 1) * $perPage;

$where = ["d.status = 'active'", "d.is_public = 1", "d.category = 'editais'"];
$params = [];

if ($search) { $where[] = "(d.title LIKE ? OR d.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($tipo)   { $where[] = "d.document_type = ?"; $params[] = $tipo; }
if ($ano)    { $where[] = "YEAR(d.publication_date) = ?"; $params[] = $ano; }

$whereSQL = implode(' AND ', $where);

$total = dbQueryOne("SELECT COUNT(*) as c FROM documents d WHERE $whereSQL", $params)['c'];
$docs  = dbQuery("SELECT * FROM documents d WHERE $whereSQL ORDER BY d.publication_date DESC LIMIT $perPage OFFSET $offset", $params);
$tipos = dbQuery("SELECT DISTINCT document_type FROM documents WHERE category='editais' AND document_type IS NOT NULL AND status='active'");
$anos  = dbQuery("SELECT DISTINCT YEAR(publication_date) as ano FROM documents WHERE category='editais' AND publication_date IS NOT NULL AND status='active' ORDER BY ano DESC");
$pages = ceil($total / $perPage);

include __DIR__ . '/../../includes/header.php';
$bannerTitle = 'Editais';
$bannerSubtitle = 'Editais, chamamentos públicos e documentos oficiais do CRCAP';
$bannerIcon = 'fa-file-alt';
$bannerBreadcrumb = [['Home','/index.php'],['CRCAP',null],['Editais',null]];
include __DIR__ . '/../../includes/page_banner.php';
?>
<main class="container mx-auto px-4 py-12">
<div class="grid lg:grid-cols-4 gap-8">
    <aside class="lg:col-span-1 space-y-4">
        <div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden">
            <div class="bg-[#001644] px-5 py-4"><h3 class="text-sm font-bold text-white"><i class="fas fa-landmark mr-2"></i>CRCAP</h3></div>
            <nav class="p-2">
                <?php foreach ([['historico.php','fa-book-open','Histórico',false],['organograma.php','fa-sitemap','Organograma',false],['delegacias.php','fa-map-marker-alt','Delegacias',false],['composicao.php','fa-users','Composição',false],['editais.php','fa-file-alt','Editais',true],['concurso.php','fa-trophy','Concurso',false]] as $l): ?>
                <a href="/crcap/pages/crcap/<?= $l[0] ?>" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[11px] font-semibold transition mb-0.5 <?= $l[3] ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                    <i class="fas <?= $l[1] ?> w-4 text-center text-[#BF8D1A]"></i><?= $l[2] ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Filters -->
        <form method="GET" class="bg-white border border-[#001644]/5 rounded-2xl p-5 space-y-4">
            <h3 class="text-xs font-bold text-[#001644] uppercase tracking-wider">Filtros</h3>
            <div>
                <label class="text-[10px] font-semibold text-[#001644]/60 uppercase tracking-wider block mb-1.5">Busca</label>
                <input type="text" name="q" value="<?= h($search) ?>" placeholder="Pesquisar edital..."
                       class="w-full px-3 py-2 text-xs border border-[#001644]/15 rounded-xl focus:outline-none focus:border-[#BF8D1A] text-[#001644]">
            </div>
            <?php if (!empty($tipos)): ?>
            <div>
                <label class="text-[10px] font-semibold text-[#001644]/60 uppercase tracking-wider block mb-1.5">Tipo</label>
                <select name="tipo" class="w-full px-3 py-2 text-xs border border-[#001644]/15 rounded-xl focus:outline-none focus:border-[#BF8D1A] text-[#001644]">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= h($t['document_type']) ?>" <?= $tipo===$t['document_type']?'selected':'' ?>><?= h(ucfirst($t['document_type'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (!empty($anos)): ?>
            <div>
                <label class="text-[10px] font-semibold text-[#001644]/60 uppercase tracking-wider block mb-1.5">Ano</label>
                <select name="ano" class="w-full px-3 py-2 text-xs border border-[#001644]/15 rounded-xl focus:outline-none focus:border-[#BF8D1A] text-[#001644]">
                    <option value="">Todos</option>
                    <?php foreach ($anos as $a): ?>
                    <option value="<?= $a['ano'] ?>" <?= $ano===$a['ano']?'selected':'' ?>><?= $a['ano'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="w-full py-2 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition">Filtrar</button>
            <?php if ($search || $tipo || $ano): ?>
            <a href="/crcap/pages/crcap/editais.php" class="block text-center text-[10px] text-[#BF8D1A] hover:underline">Limpar filtros</a>
            <?php endif; ?>
        </form>
    </aside>

    <div class="lg:col-span-3">
        <div class="flex items-center justify-between mb-6">
            <p class="text-xs text-[#001644]/60">
                <span class="font-bold text-[#001644]"><?= $total ?></span> edital<?= $total !== 1 ? 'is' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?>
                <?= $search ? ' para "'.h($search).'"' : '' ?>
            </p>
        </div>

        <?php if (!empty($docs)): ?>
        <div class="space-y-3">
            <?php foreach ($docs as $doc): ?>
            <?php
            $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
            $extColors = ['pdf'=>'#ef4444','doc'=>'#3b82f6','docx'=>'#3b82f6','xls'=>'#22c55e','xlsx'=>'#22c55e','zip'=>'#f59e0b'];
            $extColor = $extColors[$ext] ?? '#6b7280';
            $isExpired = $doc['expiry_date'] && strtotime($doc['expiry_date']) < time();
            ?>
            <div class="bg-white border border-[#001644]/5 rounded-2xl p-5 hover:shadow-lg hover:border-[#BF8D1A]/20 transition group <?= $isExpired ? 'opacity-60' : '' ?>">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-[10px] flex-shrink-0 shadow" style="background:<?= $extColor ?>">
                        <?= strtoupper($ext) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <?php if ($doc['reference_number']): ?>
                                    <span class="text-[9px] font-bold bg-[#001644]/8 text-[#001644] px-2 py-0.5 rounded-full"><?= h($doc['reference_number']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($doc['document_type']): ?>
                                    <span class="text-[9px] font-bold text-[#BF8D1A] bg-[#BF8D1A]/10 px-2 py-0.5 rounded-full"><?= h(ucfirst($doc['document_type'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($isExpired): ?>
                                    <span class="text-[9px] font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded-full">Encerrado</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="font-semibold text-[#001644] text-sm group-hover:text-[#BF8D1A] transition line-clamp-2"><?= h($doc['title']) ?></h3>
                                <?php if ($doc['description']): ?>
                                <p class="text-[10px] text-[#022E6B]/60 mt-1 line-clamp-2"><?= h($doc['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!$isExpired): ?>
                            <a href="/crcap/pages/download.php?id=<?= $doc['id'] ?>"
                               class="flex items-center gap-1.5 px-4 py-2 bg-[#001644] text-white text-[10px] font-semibold rounded-xl hover:bg-[#BF8D1A] transition flex-shrink-0">
                                <i class="fas fa-download"></i> Baixar
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-4 mt-3 text-[10px] text-[#001644]/50">
                            <?php if ($doc['publication_date']): ?>
                            <span class="flex items-center gap-1"><i class="fas fa-calendar text-[#BF8D1A]"></i> Publicado: <?= date('d/m/Y', strtotime($doc['publication_date'])) ?></span>
                            <?php endif; ?>
                            <?php if ($doc['expiry_date']): ?>
                            <span class="flex items-center gap-1"><i class="fas fa-calendar-times text-red-400"></i> Encerra: <?= date('d/m/Y', strtotime($doc['expiry_date'])) ?></span>
                            <?php endif; ?>
                            <?php if ($doc['file_size']): ?>
                            <span class="flex items-center gap-1"><i class="fas fa-hdd"></i> <?= round($doc['file_size']/1024/1024, 1) ?> MB</span>
                            <?php endif; ?>
                            <span class="flex items-center gap-1"><i class="fas fa-download"></i> <?= $doc['downloads'] ?> downloads</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <?php if ($pages > 1): ?>
        <div class="flex justify-center items-center gap-2 mt-8">
            <?php if ($pg > 1): ?>
            <a href="?page=<?= $pg-1 ?>&q=<?= urlencode($search) ?>&tipo=<?= urlencode($tipo) ?>&ano=<?= $ano ?>"
               class="w-9 h-9 rounded-xl border border-[#001644]/20 flex items-center justify-center text-[#001644] hover:bg-[#001644] hover:text-white transition text-xs">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            <?php for ($i = max(1,$pg-2); $i <= min($pages, $pg+2); $i++): ?>
            <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&tipo=<?= urlencode($tipo) ?>&ano=<?= $ano ?>"
               class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-semibold transition <?= $i===$pg ? 'bg-[#001644] text-white' : 'border border-[#001644]/20 text-[#001644] hover:bg-[#F8FAFC]' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
            <?php if ($pg < $pages): ?>
            <a href="?page=<?= $pg+1 ?>&q=<?= urlencode($search) ?>&tipo=<?= urlencode($tipo) ?>&ano=<?= $ano ?>"
               class="w-9 h-9 rounded-xl border border-[#001644]/20 flex items-center justify-center text-[#001644] hover:bg-[#001644] hover:text-white transition text-xs">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="bg-white rounded-2xl border border-[#001644]/5 p-16 text-center">
            <i class="fas fa-search text-5xl text-[#001644]/20 mb-4 block"></i>
            <h3 class="font-bold text-[#001644] text-lg mb-2">Nenhum edital encontrado</h3>
            <p class="text-sm text-[#001644]/50">
                <?= $search ? 'Tente outros termos de busca.' : 'Nenhum edital publicado no momento.' ?>
            </p>
            <?php if ($search || $tipo || $ano): ?>
            <a href="/crcap/pages/crcap/editais.php" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-[#001644] text-white text-xs font-semibold rounded-xl hover:bg-[#022E6B] transition">
                <i class="fas fa-times"></i> Limpar filtros
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
