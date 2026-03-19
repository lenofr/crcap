<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle = 'Fiscalização Eletrônica · CRCAP';
$activeMenu = 'fiscalizacao';
include __DIR__ . '/../../includes/header.php';
?>
<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14">
    <div class="container mx-auto px-4">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white">Início</a><i class="fas fa-chevron-right text-[9px]"></i>
            <a href="/crcap/pages/fiscalizacao/o-que-e.php" class="hover:text-white">Fiscalização</a><i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Fiscalização Eletrônica</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-laptop-code"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Fiscalização Eletrônica</h1>
                <p class="text-white/70 text-sm max-w-2xl">Verificação de registro e regularidade de profissionais online.</p>
            </div>
        </div>
    </div>
</section>
<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-xl mb-4">Consulta de Profissional</h2>
                <p class="text-sm text-[#022E6B] leading-relaxed mb-6">Verifique a regularidade de um profissional de administração ou empresa perante o CRCAP. Consulte o número de registro, situação cadastral e eventuais restrições.</p>
                <div class="bg-[#F8FAFC] rounded-xl p-6 border border-[#001644]/5">
                    <div class="flex gap-3">
                        <input type="text" class="flex-1 px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-white" placeholder="Digite o número de registro (ex: 00000-AP)">
                        <button class="px-6 py-3 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#BF8D1A] transition text-sm flex items-center gap-2"><i class="fas fa-search"></i>Consultar</button>
                    </div>
                    <p class="text-[10px] text-[#022E6B]/60 mt-2">Esta consulta está integrada ao sistema CFA – Conselho Federal de Administração.</p>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <?php $servs = [
                    ['fa-id-card','Verificar Registro','Consulte se o profissional está registrado no CRCAP.','/'],
                    ['fa-building','Cadastro de Empresas','Verifique o registro de organizações no Conselho.','/'],
                    ['fa-certificate','Emitir Certidão','Solicite certidão de regularidade online.','/'],
                ]; foreach ($servs as $s): ?>
                <a href="<?= $s[3] ?>" class="bg-white rounded-2xl p-5 border border-[#001644]/3 hover:-translate-y-1 hover:shadow-md transition text-center group">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-xl flex items-center justify-center text-white mx-auto mb-3"><i class="fas <?= $s[0] ?>"></i></div>
                    <h3 class="font-bold text-[#001644] text-sm mb-1 group-hover:text-[#BF8D1A] transition"><?= $s[1] ?></h3>
                    <p class="text-[10px] text-[#022E6B]"><?= $s[2] ?></p>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-lg mb-4">Sistemas Integrados</h2>
                <div class="space-y-3">
                    <?php $sistemas = [
                        ['CFA – Portal de Serviços','Sistema nacional do Conselho Federal de Administração','https://cfa.org.br','fa-globe'],
                        ['SEI – Sistema Eletrônico de Informações','Protocolo e acompanhamento de processos online','#','fa-folder-open'],
                        ['Portal de Transparência CRCAP','Dados abertos e prestação de contas','#','fa-chart-bar'],
                    ]; foreach ($sistemas as $s): ?>
                    <a href="<?= $s[2] ?>" target="_blank" class="flex items-center gap-4 p-4 bg-[#F8FAFC] rounded-xl border border-[#001644]/5 hover:border-[#BF8D1A]/30 hover:shadow-sm transition group">
                        <div class="w-10 h-10 rounded-xl bg-white border border-[#001644]/10 flex items-center justify-center text-[#001644] group-hover:bg-[#BF8D1A] group-hover:text-white group-hover:border-[#BF8D1A] transition"><i class="fas <?= $s[3] ?> text-sm"></i></div>
                        <div><p class="font-semibold text-[#001644] text-sm group-hover:text-[#BF8D1A] transition"><?= $s[0] ?></p><p class="text-[10px] text-[#022E6B]"><?= $s[1] ?></p></div>
                        <i class="fas fa-external-link-alt text-[#001644]/30 ml-auto text-xs"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <aside class="space-y-5">
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Fiscalização</h3>
                <nav class="space-y-1">
                    <a href="/crcap/pages/fiscalizacao/o-que-e.php" class="block px-3 py-2 rounded-xl text-xs text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">O que é?</a>
                    <a href="/crcap/pages/fiscalizacao/denuncia.php" class="block px-3 py-2 rounded-xl text-xs text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">Denúncia</a>
                    <a href="/crcap/pages/fiscalizacao/fiscalizacao-eletronica.php" class="block px-3 py-2 rounded-xl text-xs font-semibold bg-[#001644] text-white">Fiscalização Eletrônica</a>
                </nav>
            </div>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
