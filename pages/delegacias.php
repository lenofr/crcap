<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle  = 'Delegacias e Representações · CRCAP';
$activeMenu = 'crcap';
$pageSlug   = 'delegacias';

// ── Conteúdo padrão original (fallback quando o BD não tem conteúdo) ─────
$pageDefaultContent = function() { ?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 20% 80%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span>CRCAP</span>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Delegacias e Representações</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-map-marked-alt"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Delegacias e Representações</h1>
                <p class="text-white/70 text-sm max-w-2xl">Unidades regionais do CRCAP para melhor atendimento dos profissionais no Amapá.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">

    <!-- Sede Principal -->
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-6">
            <span class="w-1 h-8 bg-[#BF8D1A] rounded-full"></span>
            <h2 class="text-xl font-bold text-[#001644]">Sede Principal</h2>
        </div>
        <div class="bg-white rounded-2xl p-6 border border-[#001644]/3 shadow-sm">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-xl shadow-lg"><i class="fas fa-building"></i></div>
                        <div>
                            <h3 class="font-bold text-[#001644]">CRCAP – Sede Macapá</h3>
                            <span class="text-[10px] font-semibold bg-[#006633]/10 text-[#006633] px-2 py-0.5 rounded-full">Sede Principal</span>
                        </div>
                    </div>
                    <div class="space-y-3 text-sm text-[#022E6B]">
                        <div class="flex items-start gap-3"><i class="fas fa-map-marker-alt text-[#BF8D1A] mt-0.5 w-4"></i><span>Av. Padre Júlio Maria Lombaerd, 1010 – Centro, Macapá – AP, CEP: 68900-070</span></div>
                        <div class="flex items-center gap-3"><i class="fas fa-phone text-[#BF8D1A] w-4"></i><span>(96) 3223-2600</span></div>
                        <div class="flex items-center gap-3"><i class="fas fa-envelope text-[#BF8D1A] w-4"></i><span>contato@crcap.org.br</span></div>
                        <div class="flex items-center gap-3"><i class="fas fa-clock text-[#BF8D1A] w-4"></i><span>Segunda a Sexta: 9h às 18h</span></div>
                        <div class="flex items-center gap-3"><i class="fas fa-globe text-[#BF8D1A] w-4"></i><span>www.crcap.org.br</span></div>
                    </div>
                    <div class="flex gap-3 mt-5">
                        <a href="https://maps.google.com" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-[#001644] text-white rounded-lg text-xs font-semibold hover:bg-[#022E6B] transition"><i class="fas fa-map"></i>Ver no mapa</a>
                        <a href="/crcap/pages/contato.php" class="flex items-center gap-2 px-4 py-2 border border-[#001644]/20 text-[#001644] rounded-lg text-xs font-semibold hover:bg-[#F8FAFC] transition"><i class="fas fa-envelope"></i>Fale conosco</a>
                    </div>
                </div>
                <div class="h-56 bg-[#F8FAFC] rounded-xl overflow-hidden border border-[#001644]/5">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3979.5!2d-51.0669!3d0.0356!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMMKwMDInMDQuNiJOIDUxwrAwNCcwMC44Ilc!5e0!3m2!1spt!2sbr!4v1234567890" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Delegacias Regionais -->
    <div>
        <div class="flex items-center gap-3 mb-6">
            <span class="w-1 h-8 bg-[#BF8D1A] rounded-full"></span>
            <h2 class="text-xl font-bold text-[#001644]">Delegacias Regionais</h2>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            <?php $delegacias = [
                ['Delegacia Regional de Santana','Santana – AP','Rua Principal, 500 – Centro, Santana – AP','(96) 3281-XXXX','delegacia.santana@crcap.org.br','Seg-Sex: 9h às 17h','fa-map-marker'],
                ['Delegacia Regional do Interior','Laranjal do Jari – AP','Av. Central, 200 – Laranjal do Jari – AP','(96) 99999-XXXX','delegacia.interior@crcap.org.br','Seg-Sex: 9h às 16h','fa-map-marker'],
            ]; foreach ($delegacias as $d): ?>
            <div class="bg-white rounded-2xl p-6 border border-[#001644]/3 shadow-sm hover:border-[#BF8D1A]/30 hover:shadow-md transition group">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#BF8D1A] to-[#001644] flex items-center justify-center text-white shadow-lg"><i class="fas <?= $d[6] ?> text-xl"></i></div>
                    <div>
                        <h3 class="font-bold text-[#001644] text-sm"><?= $d[0] ?></h3>
                        <span class="text-[10px] font-semibold bg-[#BF8D1A]/10 text-[#BF8D1A] px-2 py-0.5 rounded-full"><?= $d[1] ?></span>
                    </div>
                </div>
                <div class="space-y-2 text-xs text-[#022E6B]">
                    <div class="flex items-start gap-2"><i class="fas fa-map-marker-alt text-[#BF8D1A] mt-0.5 w-3"></i><span><?= $d[2] ?></span></div>
                    <div class="flex items-center gap-2"><i class="fas fa-phone text-[#BF8D1A] w-3"></i><span><?= $d[3] ?></span></div>
                    <div class="flex items-center gap-2"><i class="fas fa-envelope text-[#BF8D1A] w-3"></i><span><?= $d[4] ?></span></div>
                    <div class="flex items-center gap-2"><i class="fas fa-clock text-[#BF8D1A] w-3"></i><span><?= $d[5] ?></span></div>
                </div>
                <a href="https://maps.google.com" target="_blank" class="mt-4 flex items-center gap-2 px-4 py-2 bg-[#F8FAFC] text-[#001644] rounded-lg text-xs font-semibold hover:bg-[#001644] hover:text-white transition">
                    <i class="fas fa-map"></i>Ver no mapa
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Atendimento Online -->
    <div class="mt-12 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-8 text-white text-center relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 50% 0%, #BF8D1A 0%, transparent 60%)"></div>
        <div class="relative z-10">
            <i class="fas fa-laptop text-4xl mb-4 text-[#BF8D1A]"></i>
            <h3 class="font-serif text-2xl font-bold mb-3">Atendimento Online</h3>
            <p class="text-white/80 text-sm mb-6 max-w-lg mx-auto">Muitos serviços já estão disponíveis 100% online. Acesse o portal do profissional e resolva sem sair de casa.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="#" class="px-6 py-3 bg-[#BF8D1A] text-white font-semibold rounded-xl hover:bg-white hover:text-[#001644] transition text-sm">Acessar Portal Online</a>
                <a href="/crcap/pages/contato.php" class="px-6 py-3 bg-white/10 border border-white/20 text-white font-semibold rounded-xl hover:bg-white/20 transition text-sm">Atendimento Virtual</a>
            </div>
        </div>
    </div>

</main>

<?php }; // fim $pageDefaultContent

include '../includes/header.php';

// ── BD tem prioridade; fallback = conteúdo original acima ─────────────────
require __DIR__ . '/../includes/page-content.php';

include '../includes/footer.php'; ?>
