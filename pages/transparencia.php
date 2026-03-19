<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle  = 'Transparência e Prestação de Contas · CRCAP';
$activeMenu = 'governanca';

// Buscar documentos de relatórios
$relatorios = dbFetchAll($pdo, "SELECT * FROM documents WHERE category='relatorios' AND is_public=1 AND status='active' ORDER BY publication_date DESC LIMIT 20");

include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 40% 60%, #006633 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <a href="/crcap/pages/sobre-governanca.php">Governança</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Transparência</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-eye"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Transparência e Prestação de Contas</h1>
                <p class="text-white/70 text-sm max-w-2xl">Acesso a informações financeiras, relatórios de gestão e documentos de prestação de contas do CRCAP.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">

    <!-- Indicadores Financeiros -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <?php $indicadores = [
            ['R$ 2,8M','Orçamento Anual 2026','fa-dollar-sign','#001644'],
            ['94%','Execução Orçamentária','fa-chart-pie','#006633'],
            ['127','Documentos Publicados','fa-file-alt','#BF8D1A'],
            ['100%','Adimplência ao CFA','fa-check-circle','#022E6B'],
        ]; foreach ($indicadores as $ind): ?>
        <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm text-center hover:-translate-y-1 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3 text-white" style="background:<?= $ind[3] ?>"><i class="fas <?= $ind[2] ?> text-xl"></i></div>
            <span class="block text-2xl font-bold font-serif text-[#001644]"><?= $ind[0] ?></span>
            <span class="text-[10px] text-[#022E6B] font-medium"><?= $ind[1] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabs de categorias -->
    <div class="bg-white rounded-2xl border border-[#001644]/3 shadow-sm overflow-hidden">
        <div class="flex overflow-x-auto border-b border-[#001644]/10">
            <?php $tabs = [
                ['Todos','all',true],
                ['Orçamento','orcamento',false],
                ['Balanço Financeiro','balanco',false],
                ['Relatórios de Gestão','gestao',false],
                ['Licitações','licitacoes',false],
                ['Contratos','contratos',false],
                ['Folha de Pagamento','folha',false],
            ]; foreach ($tabs as $tab): ?>
            <button onclick="filterDocs('<?= $tab[1] ?>')" class="tab-btn flex-shrink-0 px-5 py-3.5 text-xs font-semibold transition border-b-2 <?= $tab[2]?'border-[#BF8D1A] text-[#BF8D1A] bg-[#BF8D1A]/5':'border-transparent text-[#022E6B] hover:text-[#001644]' ?>" data-tab="<?= $tab[1] ?>">
                <?= $tab[0] ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="p-6">
            <!-- Documentos Exemplo -->
            <?php if (empty($relatorios)): ?>
            <div class="space-y-3">
                <?php $exDocs = [
                    ['Relatório de Gestão 2025','gestao','2026-01-31','PDF','3.2MB','annual'],
                    ['Balanço Patrimonial 2025','balanco','2026-02-15','PDF','1.8MB','annual'],
                    ['Orçamento Aprovado 2026','orcamento','2025-12-15','PDF','892KB','annual'],
                    ['Balancete Mensal – Dez/2025','balanco','2026-01-10','PDF','450KB','monthly'],
                    ['Balancete Mensal – Jan/2026','balanco','2026-02-10','PDF','430KB','monthly'],
                    ['Folha de Pagamento – Jan/2026','folha','2026-02-05','PDF','310KB','monthly'],
                    ['Contratos Firmados – 2025','contratos','2026-01-20','PDF','2.1MB','annual'],
                    ['Licitações 1º Trimestre/2026','licitacoes','2026-02-01','PDF','670KB','quarter'],
                    ['Relatório de Execução Orçamentária – 4T2025','orcamento','2026-01-25','PDF','1.1MB','quarter'],
                ]; foreach ($exDocs as $d): ?>
                <div class="doc-item flex items-center gap-4 p-4 bg-[#F8FAFC] rounded-xl hover:bg-white hover:shadow-sm hover:border-[#BF8D1A]/20 border border-transparent transition group" data-cat="<?= $d[1] ?>">
                    <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-500 text-xl flex-shrink-0 group-hover:bg-red-100 transition">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-[#001644] text-xs group-hover:text-[#BF8D1A] transition mb-1"><?= $d[0] ?></h3>
                        <div class="flex items-center gap-3 text-[9px] text-[#022E6B]">
                            <span class="flex items-center gap-1"><i class="fas fa-calendar text-[#BF8D1A]"></i><?= date('d/m/Y', strtotime($d[2])) ?></span>
                            <span><?= $d[3] ?> · <?= $d[4] ?></span>
                        </div>
                    </div>
                    <a href="#" class="flex-shrink-0 w-9 h-9 rounded-lg bg-[#001644] text-white flex items-center justify-center hover:bg-[#BF8D1A] transition text-xs">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($relatorios as $doc): ?>
                <div class="flex items-center gap-4 p-4 bg-[#F8FAFC] rounded-xl hover:bg-white hover:shadow-sm transition group">
                    <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-500 text-xl flex-shrink-0"><i class="fas fa-file-pdf"></i></div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-[#001644] text-xs group-hover:text-[#BF8D1A] transition mb-1"><?= htmlspecialchars($doc['title']) ?></h3>
                        <div class="flex items-center gap-3 text-[9px] text-[#022E6B]">
                            <?php if ($doc['publication_date']): ?><span><?= date('d/m/Y', strtotime($doc['publication_date'])) ?></span><?php endif; ?>
                            <?php if ($doc['file_size']): ?><span><?= round($doc['file_size']/1024) ?>KB</span><?php endif; ?>
                        </div>
                    </div>
                    <a href="/crcap/download.php?id=<?= $doc['id'] ?>" class="flex-shrink-0 w-9 h-9 rounded-lg bg-[#001644] text-white flex items-center justify-center hover:bg-[#BF8D1A] transition text-xs"><i class="fas fa-download"></i></a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Portal da Transparência info -->
    <div class="mt-10 bg-gradient-to-r from-[#006633] to-[#001644] rounded-2xl p-8 text-white flex flex-col md:flex-row items-center gap-6">
        <div class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center text-4xl flex-shrink-0"><i class="fas fa-globe"></i></div>
        <div class="flex-1">
            <h3 class="font-serif text-2xl font-bold mb-2">Portal de Transparência</h3>
            <p class="text-white/80 text-sm">Acesse o Portal de Transparência do CFA para informações consolidadas de todo o sistema CFA/CRAs.</p>
        </div>
        <a href="https://cfa.org.br/transparencia" target="_blank" class="flex-shrink-0 px-6 py-3 bg-white text-[#001644] font-bold rounded-xl hover:shadow-lg transition text-sm">
            Acessar Portal <i class="fas fa-external-link-alt ml-2"></i>
        </a>
    </div>
</main>

<script>
function filterDocs(cat) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        const isActive = btn.dataset.tab === cat;
        btn.classList.toggle('border-[#BF8D1A]', isActive);
        btn.classList.toggle('text-[#BF8D1A]', isActive);
        btn.classList.toggle('bg-[#BF8D1A]/5', isActive);
        btn.classList.toggle('border-transparent', !isActive);
        btn.classList.toggle('text-[#022E6B]', !isActive);
    });
    document.querySelectorAll('.doc-item').forEach(item => {
        if (cat === 'all' || item.dataset.cat === cat) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
