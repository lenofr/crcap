<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle  = 'Política de Privacidade · CRCAP';
$activeMenu = 'geral';
$pageSlug   = 'privacidade';

// ── Conteúdo padrão original (fallback quando o BD não tem conteúdo) ─────
$pageDefaultContent = function() {
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 20% 80%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Política de Privacidade</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Política de Privacidade</h1>
                <p class="text-white/70 text-sm max-w-2xl">Política de privacidade e tratamento de dados pessoais pelo CRCAP.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl p-10 border border-[#001644]/5 shadow-sm text-center">
        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#001644]/5 to-[#BF8D1A]/10 flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-user-shield text-3xl text-[#BF8D1A]"></i>
        </div>
        <h2 class="text-xl font-bold text-[#001644] mb-3">Política de Privacidade</h2>
        <p class="text-sm text-[#022E6B]/55 max-w-md mx-auto leading-relaxed">
            Esta seção está em construção. Em breve o conteúdo completo estará disponível.
        </p>
        <div class="flex justify-center gap-3 mt-7">
            <a href="/crcap/pages/contato.php"
               class="inline-flex items-center gap-2 px-6 py-3 bg-[#001644] text-white rounded-xl text-sm font-semibold hover:bg-[#022E6B] transition">
                <i class="fas fa-envelope text-[10px]"></i>Fale Conosco
            </a>
            <a href="/crcap/index.php"
               class="inline-flex items-center gap-2 px-6 py-3 border border-[#001644]/15 text-[#001644] rounded-xl text-sm font-semibold hover:bg-[#F8FAFC] transition">
                <i class="fas fa-home text-[10px]"></i>Página Inicial
            </a>
        </div>
    </div>
</main>

<?php
}; // fim $pageDefaultContent

include '../includes/header.php';

// ── BD tem prioridade; fallback = conteúdo original acima ─────────────────
require __DIR__ . '/../includes/page-content.php';

include '../includes/footer.php';
