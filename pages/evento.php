<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: /crcap/pages/eventos.php'); exit; }

$event = dbFetch($pdo, "SELECT * FROM events WHERE slug=? AND status='published' LIMIT 1", [$slug]);
if (!$event) { http_response_code(404); $pageTitle='Evento não encontrado · CRCAP'; $activeMenu='desenv'; include '../includes/header.php'; ?>
<main class="container mx-auto px-4 py-24 text-center"><i class="fas fa-calendar-times text-5xl text-[#001644]/20 mb-4 block"></i><h1 class="text-2xl font-bold text-[#001644] mb-4">Evento não encontrado</h1><a href="/crcap/pages/eventos.php" class="px-6 py-3 bg-[#001644] text-white rounded-xl text-sm font-semibold hover:bg-[#022E6B] transition">Ver todos os eventos</a></main>
<?php include '../includes/footer.php'; exit; }

// Inscrição
$regMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_inscricao'])) {
    if (!csrfVerify()) { http_response_code(403); exit('Token de segurança inválido. Recarregue a página.'); }

    $rName  = trim($_POST['reg_name'] ?? '');
    $rEmail = trim($_POST['reg_email'] ?? '');
    $rPhone = trim($_POST['reg_phone'] ?? '');
    $rCpf   = trim($_POST['reg_cpf'] ?? '');
    if ($rName && filter_var($rEmail, FILTER_VALIDATE_EMAIL)) {
        try {
            $code = strtoupper(substr(md5(uniqid()), 0, 8));
            dbExec($pdo, "INSERT INTO event_registrations (event_id,name,email,phone,cpf,confirmation_code) VALUES (?,?,?,?,?,?)",
                [$event['id'],$rName,$rEmail,$rPhone,$rCpf,$code]);
            dbExec($pdo, "UPDATE events SET current_participants=current_participants+1 WHERE id=?", [$event['id']]);
            $regMsg  = 'success';
            $regCode = $code;
            // Send confirmation email
            try {
                require_once __DIR__ . '/../includes/mailer.php';
                $evDate = date('d/m/Y', strtotime($event['event_date'])) . ' às ' . substr($event['start_time'], 0, 5);
                mailer()->sendEventConfirmation($rName, $rEmail, $event['title'], $evDate, $code);
            } catch (\Throwable $e) { /* fail silently */ }
        } catch (Exception $e) {
            $regMsg = str_contains($e->getMessage(), 'Duplicate') ? 'duplicate' : 'error';
        }
    } else { $regMsg = 'validation'; }
}

dbExec($pdo, "UPDATE events SET views=views+1 WHERE id=?", [$event['id']]);

$evDate    = new DateTime($event['event_date']);
$isPast    = $evDate < new DateTime();
$isFull    = $event['max_participants'] && $event['current_participants'] >= $event['max_participants'];
$isDeadline= $event['registration_deadline'] && strtotime($event['registration_deadline']) < time();
$canRegister = $event['registration_required'] && !$isPast && !$isFull && !$isDeadline;

$pageTitle  = htmlspecialchars($event['title']).' · CRCAP';
$activeMenu = 'desenv';

include '../includes/header.php';
?>

<div class="bg-[#F8FAFC] border-b border-[#001644]/5 py-3">
    <div class="container mx-auto px-4 flex items-center gap-2 text-xs text-[#022E6B]">
        <a href="/crcap/index.php" class="hover:text-[#BF8D1A] transition">Início</a>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <a href="/crcap/pages/eventos.php" class="hover:text-[#BF8D1A] transition">Eventos</a>
        <i class="fas fa-chevron-right text-[9px]"></i>
        <span class="text-[#001644] font-medium truncate max-w-xs"><?= htmlspecialchars($event['title']) ?></span>
    </div>
</div>

<!-- Hero do Evento -->
<section class="relative h-72 overflow-hidden">
    <img src="<?= htmlspecialchars($event['featured_image'] ?: 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=1920&h=400&fit=crop') ?>" alt="<?= htmlspecialchars($event['title']) ?>" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-[#001644] via-[#001644]/60 to-transparent"></div>
    <div class="absolute bottom-0 left-0 right-0 p-8">
        <div class="container mx-auto">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <?php if ($event['event_type']): ?><span class="bg-[#BF8D1A] text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase"><?= htmlspecialchars($event['event_type']) ?></span><?php endif; ?>
                <?php if ($event['is_free']): ?><span class="bg-[#006633] text-white text-[10px] font-bold px-3 py-1 rounded-full">GRATUITO</span><?php endif; ?>
                <?php if ($isPast): ?><span class="bg-red-500 text-white text-[10px] font-bold px-3 py-1 rounded-full">ENCERRADO</span><?php endif; ?>
                <?php if ($isFull): ?><span class="bg-orange-500 text-white text-[10px] font-bold px-3 py-1 rounded-full">VAGAS ESGOTADAS</span><?php endif; ?>
            </div>
            <h1 class="font-serif text-3xl md:text-4xl font-bold text-white leading-tight"><?= htmlspecialchars($event['title']) ?></h1>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-10">
    <div class="grid lg:grid-cols-3 gap-8">

        <!-- Conteúdo -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Informações rápidas -->
            <div class="bg-white rounded-2xl p-6 border border-[#001644]/3 shadow-sm">
                <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex flex-col items-center text-center p-3 bg-[#F8FAFC] rounded-xl">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-xl flex items-center justify-center text-white mb-2"><i class="fas fa-calendar text-sm"></i></div>
                        <span class="text-[10px] text-[#022E6B] font-semibold uppercase tracking-wider">Data</span>
                        <span class="text-sm font-bold text-[#001644] mt-1"><?= $evDate->format('d/m/Y') ?></span>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 bg-[#F8FAFC] rounded-xl">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#BF8D1A] to-[#001644] rounded-xl flex items-center justify-center text-white mb-2"><i class="fas fa-clock text-sm"></i></div>
                        <span class="text-[10px] text-[#022E6B] font-semibold uppercase tracking-wider">Horário</span>
                        <span class="text-sm font-bold text-[#001644] mt-1"><?= substr($event['start_time'],0,5) ?><?= $event['end_time'] ? '–'.substr($event['end_time'],0,5) : '' ?></span>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 bg-[#F8FAFC] rounded-xl">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#006633] to-[#022E6B] rounded-xl flex items-center justify-center text-white mb-2"><i class="fas fa-map-marker-alt text-sm"></i></div>
                        <span class="text-[10px] text-[#022E6B] font-semibold uppercase tracking-wider">Local</span>
                        <span class="text-xs font-bold text-[#001644] mt-1 text-center line-clamp-2"><?= htmlspecialchars($event['location'] ?: 'A definir') ?></span>
                    </div>
                    <div class="flex flex-col items-center text-center p-3 bg-[#F8FAFC] rounded-xl">
                        <div class="w-10 h-10 bg-gradient-to-br from-[#022E6B] to-[#001644] rounded-xl flex items-center justify-center text-white mb-2"><i class="fas fa-users text-sm"></i></div>
                        <span class="text-[10px] text-[#022E6B] font-semibold uppercase tracking-wider">Vagas</span>
                        <span class="text-sm font-bold text-[#001644] mt-1">
                            <?php if ($event['max_participants']): ?>
                            <?= $event['current_participants'] ?>/<?= $event['max_participants'] ?>
                            <?php else: ?>Ilimitado<?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Descrição -->
            <?php if ($event['content']): ?>
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-lg mb-5 flex items-center gap-2"><i class="fas fa-info-circle text-[#BF8D1A]"></i>Sobre o Evento</h2>
                <div class="prose prose-sm max-w-none text-[#022E6B] leading-relaxed"><?= $event['content'] ?></div>
            </div>
            <?php elseif ($event['description']): ?>
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-lg mb-5 flex items-center gap-2"><i class="fas fa-info-circle text-[#BF8D1A]"></i>Sobre o Evento</h2>
                <p class="text-sm text-[#022E6B] leading-relaxed"><?= htmlspecialchars($event['description']) ?></p>
            </div>
            <?php endif; ?>

            <!-- Formulário de Inscrição -->
            <?php if ($event['registration_required']): ?>
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm" id="inscricao">
                <h2 class="font-bold text-[#001644] text-lg mb-5 flex items-center gap-2"><i class="fas fa-pen-alt text-[#BF8D1A]"></i>Inscrição</h2>

                <?php if ($regMsg === 'success'): ?>
                <div class="bg-[#006633]/10 border border-[#006633]/30 rounded-2xl p-6 text-center">
                    <i class="fas fa-check-circle text-[#006633] text-4xl mb-3 block"></i>
                    <h3 class="font-bold text-[#006633] text-lg mb-2">Inscrição realizada!</h3>
                    <p class="text-sm text-[#022E6B] mb-2">Seu código de confirmação: <strong class="font-mono text-[#001644] bg-[#F8FAFC] px-2 py-0.5 rounded"><?= htmlspecialchars($regCode ?? '') ?></strong></p>
                    <p class="text-xs text-[#022E6B]">Guarde este código. Uma confirmação será enviada para seu e-mail.</p>
                </div>
                <?php elseif (!$canRegister): ?>
                <div class="bg-orange-50 border border-orange-200 rounded-2xl p-6 text-center">
                    <i class="fas fa-exclamation-triangle text-orange-500 text-3xl mb-2 block"></i>
                    <p class="font-semibold text-orange-700 text-sm">
                        <?php if ($isPast): ?>Este evento já foi realizado.
                        <?php elseif ($isFull): ?>Vagas esgotadas para este evento.
                        <?php elseif ($isDeadline): ?>O prazo de inscrições foi encerrado.
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                <?php if ($regMsg === 'duplicate'): ?><div class="bg-orange-50 border border-orange-200 text-orange-700 text-xs rounded-xl px-4 py-3 mb-5">Este e-mail já está inscrito neste evento.</div><?php endif; ?>
                <?php if ($regMsg === 'validation'): ?><div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5">Preencha todos os campos obrigatórios.</div><?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_inscricao" value="1">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Nome completo *</label><input type="text" name="reg_name" required class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="Seu nome completo"></div>
                        <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">E-mail *</label><input type="email" name="reg_email" required class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="seu@email.com"></div>
                        <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Telefone</label><input type="tel" name="reg_phone" class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="(96) 9xxxx-xxxx"></div>
                        <div><label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">CPF</label><input type="text" name="reg_cpf" class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-xs focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC]" placeholder="000.000.000-00"></div>
                    </div>
                    <label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" required class="mt-0.5"><span class="text-[10px] text-[#022E6B]">Concordo com os termos de participação e autorizo o uso dos meus dados para fins de organização deste evento.</span></label>
                    <button type="submit" class="w-full py-4 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#BF8D1A] transition flex items-center justify-center gap-2"><i class="fas fa-check-circle"></i>Confirmar Inscrição</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar -->
        <aside class="space-y-5">
            <!-- Card resumo -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm sticky top-24">
                <div class="text-center mb-5 pb-5 border-b border-[#001644]/10">
                    <div class="w-20 h-20 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl flex flex-col items-center justify-center text-white mx-auto mb-3 shadow-lg">
                        <span class="text-3xl font-bold font-serif leading-none"><?= $evDate->format('d') ?></span>
                        <span class="text-xs uppercase font-semibold"><?= $evDate->format('M Y') ?></span>
                    </div>
                    <?php if ($event['is_free']): ?>
                    <span class="text-2xl font-bold text-[#006633]">Gratuito</span>
                    <?php elseif ($event['price'] > 0): ?>
                    <span class="text-2xl font-bold text-[#001644]">R$ <?= number_format($event['price'], 2, ',', '.') ?></span>
                    <?php endif; ?>
                </div>

                <div class="space-y-3 text-xs text-[#022E6B] mb-5">
                    <div class="flex items-center gap-2"><i class="fas fa-calendar text-[#BF8D1A] w-4 text-center"></i><span><?= $evDate->format('d/m/Y') ?></span></div>
                    <div class="flex items-center gap-2"><i class="fas fa-clock text-[#BF8D1A] w-4 text-center"></i><span><?= substr($event['start_time'],0,5) ?><?= $event['end_time'] ? ' às '.substr($event['end_time'],0,5) : '' ?></span></div>
                    <?php if ($event['location']): ?><div class="flex items-start gap-2"><i class="fas fa-map-marker-alt text-[#BF8D1A] w-4 text-center mt-0.5"></i><span><?= htmlspecialchars($event['location']) ?></span></div><?php endif; ?>
                    <?php if ($event['organizer']): ?><div class="flex items-center gap-2"><i class="fas fa-user text-[#BF8D1A] w-4 text-center"></i><span><?= htmlspecialchars($event['organizer']) ?></span></div><?php endif; ?>
                    <?php if ($event['max_participants']): ?>
                    <div class="flex items-center gap-2"><i class="fas fa-users text-[#BF8D1A] w-4 text-center"></i>
                        <div class="flex-1">
                            <div class="flex justify-between text-[9px] mb-1"><span><?= $event['current_participants'] ?> inscritos</span><span><?= $event['max_participants'] ?> vagas</span></div>
                            <div class="h-1.5 bg-[#001644]/10 rounded-full overflow-hidden">
                                <div class="h-full bg-[#BF8D1A] rounded-full" style="width:<?= min(100, round($event['current_participants']/$event['max_participants']*100)) ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($event['registration_deadline']): ?><div class="flex items-center gap-2 text-orange-600"><i class="fas fa-exclamation-circle w-4 text-center"></i><span>Prazo: <?= date('d/m/Y H:i', strtotime($event['registration_deadline'])) ?></span></div><?php endif; ?>
                </div>

                <?php if ($canRegister): ?>
                <a href="#inscricao" class="block w-full py-3 bg-[#001644] text-white font-bold rounded-xl text-center text-sm hover:bg-[#BF8D1A] transition">Inscreva-se agora</a>
                <?php elseif ($event['external_link']): ?>
                <a href="<?= htmlspecialchars($event['external_link']) ?>" target="_blank" class="block w-full py-3 bg-[#001644] text-white font-bold rounded-xl text-center text-sm hover:bg-[#022E6B] transition">Acessar evento <i class="fas fa-external-link-alt ml-1"></i></a>
                <?php elseif ($event['registration_link']): ?>
                <a href="<?= htmlspecialchars($event['registration_link']) ?>" target="_blank" class="block w-full py-3 bg-[#BF8D1A] text-white font-bold rounded-xl text-center text-sm hover:bg-[#001644] transition">Inscrição externa <i class="fas fa-external-link-alt ml-1"></i></a>
                <?php endif; ?>

                <?php if ($event['contact_email'] || $event['contact_phone']): ?>
                <div class="mt-4 pt-4 border-t border-[#001644]/10">
                    <p class="text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-2">Contato</p>
                    <?php if ($event['contact_email']): ?><div class="flex items-center gap-2 text-xs text-[#022E6B] mb-1"><i class="fas fa-envelope text-[#BF8D1A] text-[10px]"></i><?= htmlspecialchars($event['contact_email']) ?></div><?php endif; ?>
                    <?php if ($event['contact_phone']): ?><div class="flex items-center gap-2 text-xs text-[#022E6B]"><i class="fas fa-phone text-[#BF8D1A] text-[10px]"></i><?= htmlspecialchars($event['contact_phone']) ?></div><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Compartilhar -->
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm text-center">
                <p class="text-xs font-bold text-[#001644] mb-3">Compartilhar evento</p>
                <div class="flex justify-center gap-2">
                    <a href="https://facebook.com/sharer/sharer.php?u=<?= urlencode('https://crcap.org.br/pages/evento.php?slug='.$slug) ?>" target="_blank" class="w-10 h-10 rounded-xl bg-[#1877F2] text-white flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-facebook-f text-sm"></i></a>
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode($event['title'].' - https://crcap.org.br/pages/evento.php?slug='.$slug) ?>" target="_blank" class="w-10 h-10 rounded-xl bg-[#25D366] text-white flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-whatsapp text-sm"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://crcap.org.br/pages/evento.php?slug='.$slug) ?>&text=<?= urlencode($event['title']) ?>" target="_blank" class="w-10 h-10 rounded-xl bg-[#1DA1F2] text-white flex items-center justify-center hover:opacity-80 transition"><i class="fab fa-twitter text-sm"></i></a>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
