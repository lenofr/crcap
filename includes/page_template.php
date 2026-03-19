<?php
// includes/page_template.php
// Helper para páginas estáticas com breadcrumb + hero + conteúdo

function renderPageHero(string $icon, string $label, string $title, string $subtitle, string $color = '#001644'): void {
    echo <<<HTML
<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 80% 50%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">$label</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0">
                <i class="fas $icon"></i>
            </div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">$title</h1>
                <p class="text-white/70 text-sm max-w-2xl">$subtitle</p>
            </div>
        </div>
    </div>
</section>
HTML;
}

function renderStaticContent(string $content): void {
    echo '<div class="prose prose-sm max-w-none text-[#022E6B] leading-relaxed">';
    echo $content;
    echo '</div>';
}
?>
