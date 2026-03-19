<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
$pageTitle = 'Denúncia · Fiscalização · CRCAP';
$activeMenu = 'fiscalizacao';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_denuncia'])) {
    if (!csrfVerify()) { http_response_code(403); exit('Token de segurança inválido. Recarregue a página.'); }

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = 'Denúncia de Exercício Ilegal';
    $message = "Tipo: ".($_POST['tipo']??'')."\n\nDenunciado: ".($_POST['denunciado']??'')."\nCNPJ/CPF: ".($_POST['cnpj']??'')."\nEndereço: ".($_POST['endereco']??'')."\n\nDescrição: ".($_POST['descricao']??'');
    try {
        dbExec($pdo,"INSERT INTO contacts (name,email,subject,message,department,ip_address) VALUES (?,?,?,?,?,?)",
            [$name,$email,$subject,$message,'fiscalizacao',$_SERVER['REMOTE_ADDR']]);
        $msg = 'success';
    } catch (Exception $e) { $msg = 'error'; }
}

include __DIR__ . '/../../includes/header.php';
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <a href="/crcap/pages/fiscalizacao/o-que-e.php" class="hover:text-white">Fiscalização</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Denúncia</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Formulário de Denúncia</h1>
                <p class="text-white/70 text-sm max-w-2xl">Denuncie o exercício ilegal ou irregular da profissão de Administrador.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <?php if ($msg === 'success'): ?>
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-[#006633] text-5xl mb-4 block"></i>
                    <h3 class="font-bold text-[#006633] text-xl mb-2">Denúncia registrada!</h3>
                    <p class="text-sm text-[#022E6B] mb-4">Sua denúncia foi recebida e será analisada pela equipe de fiscalização. Garantimos sigilo absoluto das informações.</p>
                    <a href="/crcap/pages/fiscalizacao/denuncia.php" class="px-6 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#022E6B] transition">Nova Denúncia</a>
                </div>
                <?php else: ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                    <p class="text-xs text-blue-700 flex items-start gap-2"><i class="fas fa-shield-alt mt-0.5 flex-shrink-0"></i><span>Todas as informações são tratadas com <strong>sigilo absoluto</strong>. A denúncia anônima é permitida, porém dificulta o andamento da apuração.</span></p>
                </div>
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_denuncia" value="1">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-2">Tipo de Irregularidade</label>
                        <div class="grid sm:grid-cols-2 gap-2">
                            <?php $tipos = [['fa-user-slash','Exercício ilegal da profissão'],['fa-building','Empresa sem responsável técnico'],['fa-file-invoice','Assinatura indevida de documentos'],['fa-ban','Conduta antiética']] ; foreach ($tipos as $t): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="tipo" value="<?= $t[1] ?>" class="hidden peer">
                                <div class="flex items-center gap-2 p-3 rounded-xl border-2 border-[#001644]/10 peer-checked:border-[#BF8D1A] peer-checked:bg-[#BF8D1A]/5 hover:border-[#BF8D1A]/50 transition text-xs text-[#001644] font-medium">
                                    <i class="fas <?= $t[0] ?> text-[#BF8D1A]"></i><?= $t[1] ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Nome do Denunciado</label><input type="text" name="denunciado" class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="Pessoa ou empresa"></div>
                        <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">CPF / CNPJ</label><input type="text" name="cnpj" class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="Se souber"></div>
                    </div>

                    <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Endereço / Local</label><input type="text" name="endereco" class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="Cidade, bairro, endereço"></div>

                    <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Descrição da Irregularidade *</label>
                    <textarea name="descricao" rows="5" required class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC] resize-none" placeholder="Descreva detalhadamente a irregularidade encontrada..."></textarea></div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Seu Nome (opcional)</label><input type="text" name="name" value="Anônimo" class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]"></div>
                        <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Seu E-mail (opcional)</label><input type="email" name="email" class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="Para retorno"></div>
                    </div>

                    <button type="submit" class="w-full py-4 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#BF8D1A] transition flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>Enviar Denúncia
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <aside class="space-y-5">
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Fiscalização</h3>
                <nav class="space-y-1">
                    <a href="/crcap/pages/fiscalizacao/o-que-e.php" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">O que é?</a>
                    <a href="/crcap/pages/fiscalizacao/denuncia.php" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold bg-[#001644] text-white">Denúncia</a>
                    <a href="/crcap/pages/fiscalizacao/fiscalizacao-eletronica.php" class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs text-[#022E6B] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition">Fiscalização Eletrônica</a>
                </nav>
            </div>
            <div class="bg-[#F8FAFC] rounded-2xl p-5 border border-[#001644]/5">
                <h3 class="font-bold text-[#001644] text-sm mb-3 flex items-center gap-2"><i class="fas fa-phone text-[#BF8D1A]"></i>Contato Direto</h3>
                <p class="text-xs text-[#022E6B] mb-1"><strong>Tel:</strong> (96) 3223-2600</p>
                <p class="text-xs text-[#022E6B] mb-1"><strong>E-mail:</strong> fiscalizacao@crcap.org.br</p>
                <p class="text-xs text-[#022E6B]"><strong>Horário:</strong> Seg–Sex 9h–18h</p>
            </div>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
