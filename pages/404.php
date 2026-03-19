<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
http_response_code(404);
$pageTitle = 'Página não encontrada · CRCAP';
$activeMenu = '';

// Fetch latest posts for suggestions
$suggestions = dbFetchAll($pdo,
    "SELECT title, slug FROM posts WHERE status='published' ORDER BY views DESC LIMIT 4") ?: [];
$exSuggestions = [
    ['title'=>'Últimas Notícias','slug'=>'noticias'],
    ['title'=>'Próximos Eventos','slug'=>'eventos'],
    ['title'=>'Editais','slug'=>'editais'],
    ['title'=>'Fale Conosco','slug'=>'contato'],
];
include __DIR__ . '/../includes/header.php';
?>

<main class="min-h-[60vh] flex items-center justify-center py-20">
    <div class="container mx-auto px-4 text-center">
        <!-- Animated 404 -->
        <div class="relative inline-block mb-8">
            <div class="text-[10rem] font-black text-[#001644]/5 leading-none select-none">404</div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center shadow-2xl shadow-[#001644]/20">
                    <i class="fas fa-search text-4xl text-white"></i>
                </div>
            </div>
        </div>

        <h1 class="font-serif text-3xl md:text-4xl font-bold text-[#001644] mb-3">
            Página não encontrada
        </h1>
        <p class="text-[#022E6B] text-sm mb-8 max-w-md mx-auto leading-relaxed">
            A página que você está procurando pode ter sido movida, renomeada ou não existe.
            Verifique o endereço digitado ou explore as opções abaixo.
        </p>

        <!-- Search -->
        <form method="GET" action="/crcap/pages/busca.php" class="max-w-sm mx-auto mb-10">
            <div class="flex gap-2">
                <input type="text" name="q" placeholder="Buscar no site..."
                       class="flex-1 px-4 py-2.5 rounded-xl border border-[#001644]/10 text-sm focus:outline-none focus:border-[#BF8D1A] bg-white">
                <button type="submit" class="px-4 py-2.5 bg-[#001644] text-white rounded-xl hover:bg-[#BF8D1A] transition text-sm font-semibold">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        <!-- Quick links -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 max-w-2xl mx-auto mb-10">
            <?php $links = !empty($suggestions) ? $suggestions : $exSuggestions;
            $icons = ['fa-newspaper','fa-calendar','fa-file-alt','fa-headset'];
            foreach (array_slice($links, 0, 4) as $i => $link): ?>
            <a href="/crcap/pages/<?= h($link['slug']) ?>.php"
               class="flex items-center gap-3 p-4 bg-white rounded-xl border border-[#001644]/5 hover:border-[#BF8D1A] hover:shadow-md transition group text-left">
                <div class="w-9 h-9 rounded-lg bg-[#001644]/5 flex items-center justify-center group-hover:bg-[#001644] group-hover:text-white transition flex-shrink-0">
                    <i class="fas <?= $icons[$i] ?> text-sm text-[#001644] group-hover:text-white"></i>
                </div>
                <span class="text-xs font-semibold text-[#001644] group-hover:text-[#BF8D1A] transition line-clamp-1">
                    <?= h($link['title']) ?>
                </span>
            </a>
            <?php endforeach; ?>
        </div>

        <a href="/crcap/index.php" class="inline-flex items-center gap-2 px-8 py-3 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#BF8D1A] transition">
            <i class="fas fa-home"></i>Voltar para o Início
        </a>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
