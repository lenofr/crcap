<?php
/**
 * /crcap/includes/sidebar-menu.php
 * Sidebar lateral — lê menu_items do BD, ícones por mapa interno
 * Usar com: include (não require) para permitir múltiplas inclusões
 */
if (!isset($pdo)) return;

// ── Mapa: $activeMenu → título do item pai no BD ──────────────────────────
$_sbMap = [
    'crcap'        => 'CRCAP',
    'governanca'   => 'Governança',
    'atas'         => 'Atas das Câmaras',
    'desenv'       => 'Desenvolvimento Profissional',
    'fiscalizacao' => 'Fiscalização',
    'ouvidoria'    => 'Ouvidoria',
];

$_sbKey   = $activeMenu ?? '';
$_sbTitle = $_sbMap[$_sbKey] ?? null;
if (!$_sbTitle) return;

// ── Ícones por título de item (BD não tem ícones preenchidos) ─────────────
$_sbIconMap = [
    // CRCAP
    'Histórico'                          => 'fas fa-landmark',
    'Organograma CRCAP'                  => 'fas fa-sitemap',
    'Delegacias / Representações'        => 'fas fa-map-marker-alt',
    'Composição CRCAP'                   => 'fas fa-users',
    'Editais'                            => 'fas fa-file-alt',
    'Concurso'                           => 'fas fa-trophy',
    // Governança col-1
    'Sobre a Governança'                 => 'fas fa-shield-alt',
    'Dados Abertos'                      => 'fas fa-database',
    'Transparência e Prestação de Contas'=> 'fas fa-chart-bar',
    'Auditoria'                          => 'fas fa-search-dollar',
    // Governança col-2
    'Calendário 2026 CRCAP'              => 'fas fa-calendar-alt',
    'Relato Integrado CRCAP'             => 'fas fa-book',
    'Carta de Serviço'                   => 'fas fa-scroll',
    'Cadeia de Valor'                    => 'fas fa-project-diagram',
    'Plano Anual de Contratação'         => 'fas fa-shopping-cart',
    'Plano de Desenvolvimento de Líderes'=> 'fas fa-users-cog',
    // Governança col-3
    'Ouvidoria'                          => 'fas fa-microphone-alt',
    'Comitê de Integridade'              => 'fas fa-handshake',
    'Comissão de Conduta'                => 'fas fa-gavel',
    'Gestão de Risco'                    => 'fas fa-exclamation-triangle',
    'LGPD'                               => 'fas fa-lock',
    'Segurança da Informação'            => 'fas fa-user-shield',
    'Governança Digital'                 => 'fas fa-laptop-code',
    'Logística Sustentável'              => 'fas fa-leaf',
    // Atas
    'Desenvolvimento Profissional'       => 'fas fa-graduation-cap',
    'Administrativa'                     => 'fas fa-building',
    'Fiscalização'                       => 'fas fa-search',
    'Registro'                           => 'fas fa-file-signature',
    'Controle Interno'                   => 'fas fa-clipboard-check',
    // Desenvolvimento
    'Agenda de Lives'                    => 'fas fa-video',
    'Cursos a Distância'                 => 'fas fa-laptop',
    'O que é?'                           => 'fas fa-question-circle',
    'Cadastro de Palestrante'            => 'fas fa-microphone',
    'Educação Continuada'                => 'fas fa-book-open',
    'Sistema de Evento'                  => 'fas fa-calendar-plus',
    'Cursos Credenciados EPC'            => 'fas fa-certificate',
    // Fiscalização
    'Denúncia'                           => 'fas fa-exclamation-circle',
    'Fiscalização Eletrônica'            => 'fas fa-desktop',
];
$_sbGetIcon = fn(string $t): string => $_sbIconMap[$t] ?? 'fas fa-circle';

// ── Ícone do título do grupo ──────────────────────────────────────────────
$_sbGroupIcon = [
    'crcap'        => 'fas fa-building',
    'governanca'   => 'fas fa-shield-alt',
    'atas'         => 'fas fa-file-signature',
    'desenv'       => 'fas fa-graduation-cap',
    'fiscalizacao' => 'fas fa-search',
    'ouvidoria'    => 'fas fa-comment-dots',
];
$_sbTitleIcon = $_sbGroupIcon[$_sbKey] ?? 'fas fa-bars';

// ── Buscar pai ────────────────────────────────────────────────────────────
$_sbParent = dbFetch($pdo,
    "SELECT id, title FROM menu_items
     WHERE menu_location='main' AND status='active' AND title=? AND parent_id IS NULL
     LIMIT 1",
    [$_sbTitle]
);
if (!$_sbParent) return;

// ── Buscar filhos ─────────────────────────────────────────────────────────
$_sbItems = dbFetchAll($pdo,
    "SELECT id, title, url, css_class FROM menu_items
     WHERE menu_location='main' AND status='active' AND parent_id=?
     ORDER BY order_position ASC, id ASC",
    [(int)$_sbParent['id']]
);
if (!$_sbItems) return;

// ── Detectar item ativo ───────────────────────────────────────────────────
$_sbCurrent = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$_sbIsActive = function(array $item) use ($_sbCurrent, $pageSlug): bool {
    if (!empty($item['url'])) {
        $p = rtrim(parse_url($item['url'], PHP_URL_PATH) ?? '', '/');
        if ($p && $p === $_sbCurrent) return true;
        $base = basename(str_replace('.php', '', $item['url']));
        if ($base && $base === ($pageSlug ?? '')) return true;
    }
    return false;
};

// ── Agrupar por col-1/col-2/col-3 ────────────────────────────────────────
$_sbColLabels = ['col-1' => 'Transparência', 'col-2' => 'Planejamento', 'col-3' => 'Compliance'];
$_sbGrouped   = ['col-1' => [], 'col-2' => [], 'col-3' => [], '__flat' => []];
$_sbHasCols   = false;
foreach ($_sbItems as $_si) {
    $cls = trim($_si['css_class'] ?? '');
    if (isset($_sbGrouped[$cls]) && $cls !== '__flat') {
        $_sbGrouped[$cls][] = $_si;
        $_sbHasCols = true;
    } else {
        $_sbGrouped['__flat'][] = $_si;
    }
}
?>

<!-- ══ SIDEBAR ════════════════════════════════════════════════════════════ -->
<div class="bg-white border border-[#001644]/5 rounded-2xl overflow-hidden shadow-sm">

    <!-- Cabeçalho do grupo -->
    <div class="bg-[#001644] px-5 py-4">
        <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <i class="<?= $_sbTitleIcon ?>"></i>
            <?= htmlspecialchars($_sbParent['title']) ?>
        </h3>
    </div>

    <nav class="p-2">

    <?php if ($_sbHasCols): ?>
        <!-- Modo com subgrupos (Governança) -->
        <?php foreach (['col-1','col-2','col-3'] as $_col):
            if (empty($_sbGrouped[$_col])) continue; ?>
        <div class="mb-2">
            <p class="px-3 py-1 text-[9px] font-bold text-[#001644]/40 uppercase tracking-widest">
                <?= $_sbColLabels[$_col] ?>
            </p>
            <?php foreach ($_sbGrouped[$_col] as $_si):
                $active = $_sbIsActive($_si);
                $ico = $_sbGetIcon($_si['title']); ?>
            <a href="<?= htmlspecialchars($_si['url'] ?? '#') ?>"
               class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-[11px] font-semibold transition mb-0.5
                      <?= $active ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                <i class="<?= $ico ?> w-4 text-center text-[#BF8D1A] text-[9px]"></i>
                <span><?= htmlspecialchars($_si['title']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php if (!empty($_sbGrouped['__flat'])): ?>
        <div class="mb-2">
            <?php foreach ($_sbGrouped['__flat'] as $_si):
                $active = $_sbIsActive($_si);
                $ico = $_sbGetIcon($_si['title']); ?>
            <a href="<?= htmlspecialchars($_si['url'] ?? '#') ?>"
               class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-[11px] font-semibold transition mb-0.5
                      <?= $active ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                <i class="<?= $ico ?> w-4 text-center text-[#BF8D1A] text-[9px]"></i>
                <span><?= htmlspecialchars($_si['title']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Modo simples (lista plana) -->
        <div class="mb-2">
            <?php foreach ($_sbGrouped['__flat'] ?: $_sbItems as $_si):
                $active = $_sbIsActive($_si);
                $ico = $_sbGetIcon($_si['title']); ?>
            <a href="<?= htmlspecialchars($_si['url'] ?? '#') ?>"
               class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-[11px] font-semibold transition mb-0.5
                      <?= $active ? 'bg-[#001644] text-white' : 'text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A]' ?>">
                <i class="<?= $ico ?> w-4 text-center text-[#BF8D1A] text-[9px]"></i>
                <span><?= htmlspecialchars($_si['title']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    </nav>
</div>

<?php
unset($_sbMap,$_sbKey,$_sbTitle,$_sbIconMap,$_sbGetIcon,$_sbGroupIcon,$_sbTitleIcon,
      $_sbParent,$_sbItems,$_sbCurrent,$_sbIsActive,$_sbColLabels,$_sbGrouped,
      $_sbHasCols,$_col,$_si,$active,$ico,$cls);