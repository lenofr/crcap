<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
$pageTitle  = 'Acessibilidade · CRCAP';
$activeMenu = '';
include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 50% 50%, #BF8D1A 0%,transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Acessibilidade</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-universal-access"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Acessibilidade</h1>
                <p class="text-white/70 text-sm max-w-2xl">O CRCAP está comprometido em oferecer um ambiente digital acessível a todas as pessoas.</p>
            </div>
        </div>
    </div>
</section>

<!-- Barra de acessibilidade -->
<div class="bg-[#001644]/5 border-b border-[#001644]/10 py-3">
    <div class="container mx-auto px-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold text-[#001644]">Ajustar visualização:</span>
            <button onclick="changeFont(2)" class="px-3 py-1.5 bg-white border border-[#001644]/10 rounded-lg text-xs font-semibold hover:border-[#BF8D1A] transition">A+</button>
            <button onclick="changeFont(-2)" class="px-3 py-1.5 bg-white border border-[#001644]/10 rounded-lg text-xs font-semibold hover:border-[#BF8D1A] transition">A-</button>
            <button onclick="toggleContrast()" class="px-3 py-1.5 bg-white border border-[#001644]/10 rounded-lg text-xs font-semibold hover:border-[#BF8D1A] transition flex items-center gap-2">
                <i class="fas fa-adjust"></i>Alto Contraste
            </button>
            <button onclick="resetFont()" class="px-3 py-1.5 bg-white border border-[#001644]/10 rounded-lg text-xs font-semibold hover:border-[#BF8D1A] transition">Restaurar</button>
        </div>
    </div>
</div>

<main class="container mx-auto px-4 py-12">
    <div class="max-w-4xl mx-auto space-y-8">

        <!-- Conformidade -->
        <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-[#006633]/10 flex items-center justify-center flex-shrink-0"><i class="fas fa-check-circle text-[#006633] text-xl"></i></div>
                <div>
                    <h2 class="font-bold text-[#001644] text-lg mb-3">Nível de Conformidade</h2>
                    <p class="text-sm text-[#022E6B] leading-relaxed mb-3">Este portal segue as diretrizes de acessibilidade estabelecidas pelas WCAG 2.1 (Web Content Accessibility Guidelines) e pelo e-MAG (Modelo de Acessibilidade em Governo Eletrônico), buscando atingir o nível AA de conformidade.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-3 py-1 bg-[#006633] text-white text-[10px] font-bold rounded-full">WCAG 2.1</span>
                        <span class="px-3 py-1 bg-[#001644] text-white text-[10px] font-bold rounded-full">e-MAG 3.1</span>
                        <span class="px-3 py-1 bg-[#BF8D1A] text-white text-[10px] font-bold rounded-full">Nível AA</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recursos de Acessibilidade -->
        <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
            <h2 class="font-bold text-[#001644] text-lg mb-6 flex items-center gap-2"><i class="fas fa-tools text-[#BF8D1A]"></i>Recursos de Acessibilidade Disponíveis</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php $recursos = [
                    ['fa-text-height','Ajuste de Tamanho de Fonte','Utilize os botões A+ e A- para aumentar ou diminuir o tamanho do texto conforme sua necessidade.'],
                    ['fa-adjust','Alto Contraste','Ative o modo de alto contraste para melhorar a legibilidade do conteúdo.'],
                    ['fa-keyboard','Navegação por Teclado','Todos os recursos do portal podem ser acessados via teclado (Tab, Enter, Esc, setas).'],
                    ['fa-volume-up','Compatibilidade com Leitores de Tela','O portal é compatível com NVDA, JAWS e VoiceOver.'],
                    ['fa-link','Links Descritivos','Todos os links possuem textos descritivos que indicam claramente seu destino.'],
                    ['fa-image','Textos Alternativos','Imagens possuem descrições em texto alternativo (atributo alt).'],
                    ['fa-closed-captioning','Legendas em Vídeos','Vídeos publicados no portal possuem legendas ou transcrições disponíveis.'],
                    ['fa-sitemap','Estrutura Semântica','O HTML utiliza tags semânticas para facilitar a navegação com tecnologias assistivas.'],
                ]; foreach ($recursos as $r): ?>
                <div class="flex gap-3 p-4 bg-[#F8FAFC] rounded-xl border border-[#001644]/5">
                    <div class="w-10 h-10 rounded-xl bg-[#001644]/5 flex items-center justify-center flex-shrink-0"><i class="fas <?= $r[0] ?> text-[#001644] text-sm"></i></div>
                    <div>
                        <h3 class="font-semibold text-[#001644] text-sm mb-1"><?= $r[1] ?></h3>
                        <p class="text-xs text-[#022E6B] leading-relaxed"><?= $r[2] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Atalhos de Teclado -->
        <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
            <h2 class="font-bold text-[#001644] text-lg mb-6 flex items-center gap-2"><i class="fas fa-keyboard text-[#BF8D1A]"></i>Atalhos de Teclado</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left">
                        <tr class="border-b-2 border-[#001644]/10">
                            <th class="pb-3 text-[10px] font-bold text-[#022E6B] uppercase tracking-wider">Atalho</th>
                            <th class="pb-3 text-[10px] font-bold text-[#022E6B] uppercase tracking-wider">Função</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#001644]/5">
                        <?php $atalhos = [
                            ['Alt + 1','Ir para o conteúdo principal'],
                            ['Alt + 2','Ir para o menu de navegação'],
                            ['Alt + 3','Ir para o rodapé'],
                            ['Tab','Navegar entre elementos interativos'],
                            ['Shift + Tab','Navegar em sentido contrário'],
                            ['Enter / Espaço','Ativar elemento selecionado'],
                            ['Esc','Fechar menus e modais'],
                            ['Setas','Navegar em menus e sliders'],
                        ]; foreach ($atalhos as $a): ?>
                        <tr class="hover:bg-[#F8FAFC] transition">
                            <td class="py-3"><code class="bg-[#001644] text-white px-2 py-1 rounded text-[10px] font-mono"><?= $a[0] ?></code></td>
                            <td class="py-3 text-xs text-[#022E6B]"><?= $a[1] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Limitações e Contato -->
        <div class="grid sm:grid-cols-2 gap-6">
            <div class="bg-orange-50 border border-orange-200 rounded-2xl p-6">
                <h3 class="font-bold text-orange-800 text-sm mb-3 flex items-center gap-2"><i class="fas fa-exclamation-triangle text-orange-500"></i>Limitações Conhecidas</h3>
                <ul class="text-xs text-orange-700 space-y-2">
                    <li>• Documentos PDF de terceiros podem não ter acessibilidade completa</li>
                    <li>• Alguns conteúdos de vídeo externos (YouTube) dependem das legendas da plataforma</li>
                    <li>• Mapas integrados possuem acessibilidade limitada</li>
                </ul>
            </div>
            <div class="bg-[#001644] text-white rounded-2xl p-6">
                <h3 class="font-bold text-sm mb-3 flex items-center gap-2"><i class="fas fa-headset text-[#BF8D1A]"></i>Encontrou um problema?</h3>
                <p class="text-xs text-white/70 mb-4">Se você encontrar barreiras de acessibilidade neste portal, entre em contato conosco.</p>
                <a href="/crcap/pages/contato.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#BF8D1A] text-white rounded-xl text-xs font-bold hover:bg-white hover:text-[#001644] transition">
                    <i class="fas fa-envelope"></i>Reportar problema
                </a>
            </div>
        </div>

        <!-- Atualização -->
        <p class="text-center text-[10px] text-[#022E6B]/50">Última revisão desta declaração: <?= date('d/m/Y') ?></p>
    </div>
</main>

<script>
let baseFontSize = 16;
function changeFont(delta){ baseFontSize = Math.max(12,Math.min(24,baseFontSize+delta)); document.documentElement.style.fontSize=baseFontSize+'px'; localStorage.setItem('crcap_font',baseFontSize); }
function resetFont(){ baseFontSize=16; document.documentElement.style.fontSize='16px'; localStorage.removeItem('crcap_font'); document.body.classList.remove('high-contrast'); localStorage.removeItem('crcap_contrast'); }
function toggleContrast(){ document.body.classList.toggle('high-contrast'); localStorage.setItem('crcap_contrast', document.body.classList.contains('high-contrast')?'1':''); }
const savedFont = localStorage.getItem('crcap_font'); if(savedFont) { baseFontSize=+savedFont; document.documentElement.style.fontSize=baseFontSize+'px'; }
if(localStorage.getItem('crcap_contrast')==='1') document.body.classList.add('high-contrast');
</script>
<style>.high-contrast{filter:contrast(2) grayscale(.3);background:black!important;color:white!important;}</style>

<?php include '../includes/footer.php'; ?>
