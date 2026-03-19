<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle  = 'Ouvidoria · CRCAP';
$activeMenu = 'governanca';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_ouvidoria'])) {
    if (!csrfVerify()) { http_response_code(403); exit('Token de segurança inválido. Recarregue a página.'); }

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $dept    = trim($_POST['department'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name && $email && $message) {
        try {
            dbExec($pdo, "INSERT INTO contacts (name,email,phone,subject,department,message,ip_address) VALUES (?,?,?,?,?,?,?)",
                [$name,$email,$phone,$subject,$dept,$message,$_SERVER['REMOTE_ADDR']]);
            $msg = 'success';
        } catch (Exception $e) { $msg = 'error'; }
    } else { $msg = 'validation'; }
}

include '../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 50% 50%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Ouvidoria</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-comment-alt"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Ouvidoria CRCAP</h1>
                <p class="text-white/70 text-sm max-w-2xl">Canal oficial para sugestões, reclamações, denúncias, elogios e solicitações de informações.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-3 gap-8">

        <!-- Formulário -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-paper-plane text-[#BF8D1A]"></i>Envie sua Manifestação
                </h2>

                <?php if ($msg === 'success'): ?>
                <div class="bg-[#006633]/10 border border-[#006633]/30 rounded-2xl p-6 text-center">
                    <i class="fas fa-check-circle text-[#006633] text-4xl mb-3 block"></i>
                    <h3 class="font-bold text-[#006633] text-lg mb-2">Manifestação recebida!</h3>
                    <p class="text-sm text-[#022E6B] mb-4">Sua mensagem foi registrada com sucesso. Você receberá uma resposta em até 5 dias úteis no e-mail informado.</p>
                    <a href="/crcap/pages/ouvidoria.php" class="px-6 py-2 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#022E6B] transition">Nova manifestação</a>
                </div>
                <?php else: ?>
                <?php if ($msg === 'validation'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5">Preencha todos os campos obrigatórios.</div><?php endif; ?>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_ouvidoria" value="1">

                    <!-- Tipo de Manifestação -->
                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-2">Tipo de Manifestação *</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <?php $tipos = [
                                ['fa-thumbs-up','Elogio','elogio','#006633'],
                                ['fa-lightbulb','Sugestão','sugestao','#BF8D1A'],
                                ['fa-exclamation-triangle','Reclamação','reclamacao','#001644'],
                                ['fa-search','Solicitação','solicitacao','#022E6B'],
                                ['fa-user-secret','Denúncia','denuncia','#6B2D2D'],
                                ['fa-question-circle','Dúvida','duvida','#022E6B'],
                            ]; foreach ($tipos as $tipo): ?>
                            <label class="tipo-label cursor-pointer">
                                <input type="radio" name="department" value="<?= $tipo[2] ?>" class="hidden tipo-radio">
                                <div class="flex items-center gap-2 p-3 rounded-xl border-2 border-[#001644]/10 hover:border-[#BF8D1A] transition select-none tipo-card" data-color="<?= $tipo[3] ?>">
                                    <i class="fas <?= $tipo[0] ?> text-sm" style="color:<?= $tipo[3] ?>"></i>
                                    <span class="text-xs font-medium text-[#001644]"><?= $tipo[1] ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Nome Completo *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name']??'') ?>" required
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="Seu nome">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">E-mail *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" required
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="seu@email.com">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Telefone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone']??'') ?>"
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="(96) 9xxxx-xxxx">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Assunto *</label>
                            <input type="text" name="subject" value="<?= htmlspecialchars($_POST['subject']??'') ?>" required
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="Assunto da manifestação">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Mensagem * <span class="font-normal normal-case">(mínimo 50 caracteres)</span></label>
                        <textarea name="message" rows="5" required minlength="50"
                            class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC] resize-none" placeholder="Descreva sua manifestação detalhadamente..."><?= htmlspecialchars($_POST['message']??'') ?></textarea>
                    </div>

                    <!-- Identificação -->
                    <div class="bg-[#F8FAFC] rounded-xl p-4">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="anonimo" class="mt-0.5">
                            <div>
                                <span class="text-xs font-semibold text-[#001644]">Manifestação anônima</span>
                                <p class="text-[10px] text-[#022E6B] mt-0.5">Ao marcar esta opção, seus dados de identificação não serão associados à manifestação. A possibilidade de retorno poderá ser limitada.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Privacidade -->
                    <div>
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" required class="mt-0.5">
                            <span class="text-[10px] text-[#022E6B]">Li e concordo com a <a href="/crcap/pages/privacidade.php" class="text-[#BF8D1A] hover:underline">Política de Privacidade</a> e autorizo o tratamento dos meus dados pessoais para atendimento desta manifestação, conforme a LGPD.</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-4 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] hover:shadow-lg transition flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>Enviar Manifestação
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Ouvidoria -->
        <aside class="space-y-5">
            <!-- Como Funciona -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Como funciona?</h3>
                <div class="space-y-3">
                    <?php $steps = [
                        ['1','Envie sua manifestação','fa-paper-plane','#001644'],
                        ['2','Protocolo gerado','fa-receipt','#BF8D1A'],
                        ['3','Análise pela equipe','fa-search','#006633'],
                        ['4','Resposta em até 5 dias','fa-check-circle','#001644'],
                    ]; foreach ($steps as $s): ?>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:<?= $s[3] ?>"><?= $s[0] ?></div>
                        <span class="text-xs text-[#022E6B]"><?= $s[1] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contato Direto -->
            <div class="bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-5 text-white">
                <h3 class="font-bold text-sm mb-4"><i class="fas fa-headset text-[#BF8D1A] mr-2"></i>Contato Direto</h3>
                <div class="space-y-3 text-xs">
                    <div class="flex items-center gap-3"><i class="fas fa-phone text-[#BF8D1A]"></i><span>(96) 3223-2600 – Ramal 15</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-envelope text-[#BF8D1A]"></i><span>ouvidoria@crcap.org.br</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-clock text-[#BF8D1A]"></i><span>Seg-Sex: 9h às 17h</span></div>
                    <div class="flex items-center gap-3 pt-2 border-t border-white/10">
                        <i class="fas fa-map-marker-alt text-[#BF8D1A]"></i><span>Av. Padre Júlio Maria Lombaerd, 1010 · Sala 205</span>
                    </div>
                </div>
            </div>

            <!-- Acompanhamento -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-3">Acompanhar Protocolo</h3>
                <p class="text-[10px] text-[#022E6B] mb-3">Digite o número do protocolo para acompanhar o status da sua manifestação.</p>
                <form class="flex gap-2">
                    <input type="text" placeholder="Ex: OUV-2026-0001" class="flex-1 px-3 py-2.5 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]">
                    <button type="submit" class="px-4 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#022E6B] transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Transparência Ouvidoria -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Relatórios da Ouvidoria</h3>
                <div class="space-y-2 text-[10px] text-[#022E6B]">
                    <div class="flex justify-between"><span>Manifestações 2025:</span><strong class="text-[#001644]">342</strong></div>
                    <div class="flex justify-between"><span>Respondidas:</span><strong class="text-[#006633]">98%</strong></div>
                    <div class="flex justify-between"><span>Tempo médio:</span><strong class="text-[#001644]">3,2 dias</strong></div>
                </div>
                <a href="#" class="block mt-3 text-center text-xs text-[#BF8D1A] font-semibold hover:underline">Ver relatório completo</a>
            </div>
        </aside>
    </div>
</main>

<script>
document.querySelectorAll('.tipo-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.tipo-card').forEach(c => {
            c.classList.remove('border-[#BF8D1A]','bg-[#BF8D1A]/5');
            c.classList.add('border-[#001644]/10');
        });
        if (radio.checked) {
            const card = radio.nextElementSibling;
            card.classList.remove('border-[#001644]/10');
            card.classList.add('border-[#BF8D1A]','bg-[#BF8D1A]/5');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
