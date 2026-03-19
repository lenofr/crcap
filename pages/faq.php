<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
$pageTitle  = 'Perguntas Frequentes · CRCAP';
$activeMenu = '';
include '../includes/header.php';

$faqs = [
    ['Como me registro no CRCAP?', 'O registro profissional pode ser feito presencialmente na sede do CRCAP ou pelo Portal de Serviços online. São necessários: diploma de curso superior em Administração, documentos pessoais (RG, CPF), comprovante de residência e recolhimento das taxas de inscrição.', 'registro'],
    ['Qual o prazo para pagamento da anuidade?', 'A anuidade deve ser paga até 31 de março de cada exercício. Após esta data, são aplicados juros e multas conforme tabela vigente. Verifique as condições do Programa de Regularização para descontos especiais.', 'anuidade'],
    ['Como obter a Certidão Negativa?', 'A Certidão Negativa de Débito pode ser solicitada pelo Portal de Serviços, gratuitamente, para profissionais em dia com suas obrigações. O documento tem validade de 90 dias e é emitido em formato digital.', 'certidao'],
    ['O que é o Conselho Regional de Administração?', 'O CRCAP é o órgão responsável pelo controle, orientação e disciplina do exercício da profissão de Administrador no estado do Amapá. Vinculado ao CFA – Conselho Federal de Administração, atua na fiscalização e valorização da profissão.', 'institucional'],
    ['Como solicitar a transferência de registro?', 'A transferência de registro entre CRAs pode ser solicitada quando o profissional muda de estado. O processo é realizado pelo Portal CFA e requer que o profissional esteja com suas obrigações em dia nos dois conselhos.', 'registro'],
    ['Quais os benefícios do registro?', 'Profissionais registrados têm acesso a: emissão de certidões, participação em cursos com desconto, direito a assinar documentos técnicos, serviços de apoio jurídico, convênios com instituições parceiras e participação na gestão do Conselho.', 'beneficios'],
    ['Como participar das comissões e câmaras?', 'Profissionais com registro ativo podem manifestar interesse em participar das comissões através de candidatura ou indicação. Acesse o site para verificar os processos de seleção abertos ou entre em contato com a secretaria.', 'participacao'],
    ['O que fazer em caso de exercício ilegal da profissão?', 'Caso você identifique o exercício ilegal ou irregular da profissão de Administrador, utilize nosso formulário de Denúncia disponível na seção Fiscalização. Todas as denúncias são tratadas com sigilo absoluto.', 'fiscalizacao'],
    ['Como atualizar meus dados cadastrais?', 'Seus dados podem ser atualizados pelo Portal do Profissional, acessando a área do usuário com seu login e senha. Para alterações de documentos ou dados relevantes, é necessária apresentação presencial com documentação.', 'cadastro'],
    ['Quais cursos o CRCAP oferece?', 'O CRCAP oferece cursos de extensão, atualização e especialização, tanto presenciais quanto EAD. A programação é publicada na seção Desenvolvimento Profissional. Profissionais registrados têm desconto especial.', 'cursos'],
];
?>

<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 50% 100%, #BF8D1A 0%, transparent 50%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white">Início</a><i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Perguntas Frequentes</span>
        </div>
        <div class="text-center max-w-2xl mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-question-circle"></i></div>
            <h1 class="font-serif text-3xl md:text-4xl font-bold mb-3">Perguntas Frequentes</h1>
            <p class="text-white/70 text-sm">Encontre respostas para as dúvidas mais comuns sobre o CRCAP e os serviços oferecidos.</p>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <!-- Filtros por categoria -->
    <div class="flex flex-wrap gap-2 justify-center mb-10">
        <?php $cats = [''=>'Todas','registro'=>'Registro','anuidade'=>'Anuidade','certidao'=>'Certidões','institucional'=>'Institucional','fiscalizacao'=>'Fiscalização','cursos'=>'Cursos','beneficios'=>'Benefícios'];
        foreach ($cats as $v=>$l): ?>
        <button onclick="filterFaq('<?= $v ?>')" class="faq-filter px-4 py-2 rounded-xl text-xs font-semibold border-2 transition <?= $v===''?'bg-[#001644] text-white border-[#001644]':'bg-white text-[#022E6B] border-[#001644]/10 hover:border-[#BF8D1A]' ?>" data-cat="<?= $v ?>">
            <?= $l ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Accordion -->
    <div class="max-w-3xl mx-auto space-y-3" id="faqList">
        <?php foreach ($faqs as $i => $faq): ?>
        <div class="bg-white rounded-2xl border border-[#001644]/5 shadow-sm overflow-hidden faq-item" data-cat="<?= $faq[2] ?>">
            <button onclick="toggleFaq(<?= $i ?>)" class="w-full flex items-center justify-between p-5 text-left group">
                <span class="font-semibold text-[#001644] text-sm group-hover:text-[#BF8D1A] transition flex-1 pr-4"><?= htmlspecialchars($faq[0]) ?></span>
                <div class="w-8 h-8 rounded-xl bg-[#001644]/5 flex items-center justify-center text-[#001644] flex-shrink-0 transition group-hover:bg-[#BF8D1A] group-hover:text-white" id="faq-icon-<?= $i ?>">
                    <i class="fas fa-plus text-xs"></i>
                </div>
            </button>
            <div id="faq-body-<?= $i ?>" class="hidden px-5 pb-5">
                <p class="text-sm text-[#022E6B] leading-relaxed"><?= htmlspecialchars($faq[1]) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CTA -->
    <div class="max-w-3xl mx-auto mt-12 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-8 text-white text-center">
        <i class="fas fa-headset text-[#BF8D1A] text-4xl mb-4 block"></i>
        <h3 class="font-bold text-xl mb-2">Não encontrou o que procurava?</h3>
        <p class="text-sm text-white/70 mb-6">Nossa equipe está pronta para ajudar. Entre em contato pelo formulário ou pelos nossos canais de atendimento.</p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="/crcap/pages/contato.php" class="px-6 py-3 bg-[#BF8D1A] text-white font-bold rounded-xl hover:bg-white hover:text-[#001644] transition text-sm"><i class="fas fa-envelope mr-2"></i>Fale Conosco</a>
            <a href="https://api.whatsapp.com/send?phone=5596999990000" target="_blank" class="px-6 py-3 bg-[#25D366] text-white font-bold rounded-xl hover:opacity-80 transition text-sm"><i class="fab fa-whatsapp mr-2"></i>WhatsApp</a>
        </div>
    </div>
</main>

<script>
function toggleFaq(i) {
    const body = document.getElementById('faq-body-'+i);
    const icon = document.getElementById('faq-icon-'+i);
    const isOpen = !body.classList.contains('hidden');
    document.querySelectorAll('[id^="faq-body-"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="faq-icon-"] i').forEach(el => { el.className='fas fa-plus text-xs'; });
    document.querySelectorAll('[id^="faq-icon-"]').forEach(el => { el.classList.remove('bg-[#BF8D1A]','text-white'); el.classList.add('bg-[#001644]/5','text-[#001644]'); });
    if (!isOpen) {
        body.classList.remove('hidden');
        icon.querySelector('i').className = 'fas fa-minus text-xs';
        icon.classList.add('bg-[#BF8D1A]','text-white');
        icon.classList.remove('bg-[#001644]/5','text-[#001644]');
    }
}
function filterFaq(cat) {
    document.querySelectorAll('.faq-filter').forEach(btn => {
        const active = btn.dataset.cat === cat;
        btn.className = 'faq-filter px-4 py-2 rounded-xl text-xs font-semibold border-2 transition ' + (active ? 'bg-[#001644] text-white border-[#001644]' : 'bg-white text-[#022E6B] border-[#001644]/10 hover:border-[#BF8D1A]');
    });
    document.querySelectorAll('.faq-item').forEach(item => {
        item.style.display = (!cat || item.dataset.cat === cat) ? '' : 'none';
    });
}
</script>

<?php include '../includes/footer.php'; ?>
