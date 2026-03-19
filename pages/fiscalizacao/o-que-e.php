<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle = 'Fiscalização · CRCAP';
$activeMenu = 'fiscalizacao';
include __DIR__ . '/../../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 70% 30%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Fiscalização</span>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span>O que é?</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-search"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">O que é a Fiscalização?</h1>
                <p class="text-white/70 text-sm max-w-2xl">Conheça o processo de fiscalização do exercício profissional realizado pelo CRCAP.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-xl mb-4 flex items-center gap-2"><i class="fas fa-info-circle text-[#BF8D1A]"></i>Sobre a Fiscalização</h2>
                <p class="text-sm text-[#022E6B] leading-relaxed mb-4">A fiscalização é uma das atividades precípuas do CRCAP, sendo instrumento de preservação da sociedade, dos profissionais de administração e da própria imagem do Conselho.</p>
                <p class="text-sm text-[#022E6B] leading-relaxed mb-4">O exercício ilegal da profissão constitui crime tipificado no art. 47 das Contravenções Penais (Decreto-Lei 3688/1941) e no art. 3º da Lei nº 6.839/80. Portanto, o Conselho Regional tem o dever legal de coibir e apurar tais irregularidades.</p>
                <p class="text-sm text-[#022E6B] leading-relaxed">A fiscalização do exercício profissional objetiva verificar se os serviços de administração estão sendo executados por profissional habilitado e regularmente inscrito no CRCAP.</p>
            </div>

            <!-- Objetivos -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-xl mb-6">Objetivos da Fiscalização</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <?php $objetivos = [
                        ['fa-shield-alt','Proteger a sociedade','Garantir que os serviços sejam prestados por profissionais devidamente habilitados.'],
                        ['fa-certificate','Valorizar a profissão','Combater o exercício ilegal e irregular da profissão de Administrador.'],
                        ['fa-balance-scale','Aplicar a legislação','Fazer cumprir as normas que regulamentam o exercício profissional.'],
                        ['fa-users','Orientar profissionais','Esclarecer dúvidas e orientar sobre obrigações legais e éticas.'],
                    ]; foreach ($objetivos as $o): ?>
                    <div class="bg-[#F8FAFC] rounded-xl p-4 border border-[#001644]/5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white mb-3"><i class="fas <?= $o[0] ?> text-sm"></i></div>
                        <h3 class="font-bold text-[#001644] text-sm mb-1"><?= $o[1] ?></h3>
                        <p class="text-xs text-[#022E6B] leading-relaxed"><?= $o[2] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Como funciona -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-xl mb-6">Como Funciona</h2>
                <div class="space-y-4">
                    <?php $etapas = [
                        ['1','Planejamento','A equipe de fiscalização elabora um plano de ação com base em setores econômicos e demandas recebidas.'],
                        ['2','Visita Fiscal','O fiscal credenciado visita empresas e órgãos para verificar a regularidade do exercício profissional.'],
                        ['3','Autuação','Caso encontradas irregularidades, é lavrado o Auto de Infração com notificação ao infrator.'],
                        ['4','Defesa e Julgamento','O infrator tem prazo para apresentar defesa, que é analisada pela Câmara de Fiscalização.'],
                        ['5','Penalidades','Após julgamento, podem ser aplicadas advertências, multas ou cassação de registro profissional.'],
                    ]; foreach ($etapas as $e): ?>
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-[#BF8D1A] flex items-center justify-center text-white font-bold text-sm flex-shrink-0"><?= $e[0] ?></div>
                        <div class="border-l-2 border-[#001644]/10 pl-4 flex-1">
                            <h3 class="font-bold text-[#001644] text-sm mb-1"><?= $e[1] ?></h3>
                            <p class="text-xs text-[#022E6B] leading-relaxed"><?= $e[2] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="space-y-5">
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Fiscalização</h3>
                <nav class="space-y-1">
                    <a href="/crcap/pages/fiscalizacao/o-que-e.php" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold bg-[#001644] text-white">O que é?</a>
                    <a href="/crcap/pages/fiscalizacao/denuncia.php" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">Denúncia</a>
                    <a href="/crcap/pages/fiscalizacao/fiscalizacao-eletronica.php" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">Fiscalização Eletrônica</a>
                </nav>
            </div>
            <div class="bg-[#001644] text-white rounded-2xl p-5">
                <i class="fas fa-exclamation-triangle text-[#BF8D1A] text-2xl mb-3 block"></i>
                <h3 class="font-bold text-sm mb-2">Denuncie o exercício irregular</h3>
                <p class="text-[10px] text-white/70 mb-4">Se você tiver conhecimento de exercício ilegal da profissão, faça sua denúncia.</p>
                <a href="/crcap/pages/fiscalizacao/denuncia.php" class="block py-2.5 bg-[#BF8D1A] text-white text-center text-xs font-bold rounded-xl hover:bg-white hover:text-[#001644] transition">Fazer Denúncia</a>
            </div>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
