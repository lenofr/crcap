<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$pageTitle  = 'Fale Conosco · CRCAP';
$activeMenu = '';

// ── Carrega configurações do banco ────────────────────────────────────────────
$settingsRaw = dbFetchAll($pdo, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN (
    'site_name','site_email','site_phone','whatsapp',
    'facebook_url','instagram_url','twitter_url','linkedin_url','youtube_url',
    'endereco_logradouro','endereco_bairro','endereco_cidade','endereco_cep','endereco_complemento',
    'horario_funcionamento','maps_embed_url','site_region'
)");
$cfg = array_column($settingsRaw, 'setting_value', 'setting_key');

// Helper
function c(array $cfg, string $key, string $default = ''): string {
    return htmlspecialchars($cfg[$key] ?? $default);
}

// Monta endereço completo
$logradouro   = $cfg['endereco_logradouro']  ?? '';
$bairro       = $cfg['endereco_bairro']      ?? '';
$cidade       = $cfg['endereco_cidade']      ?? 'Macapá – AP';
$cep          = $cfg['endereco_cep']         ?? '';
$complemento  = $cfg['endereco_complemento'] ?? '';
$telefone     = $cfg['site_phone']           ?? '';
$whatsapp     = $cfg['whatsapp']             ?? '';
$email        = $cfg['site_email']           ?? '';
$horario      = $cfg['horario_funcionamento'] ?? 'Segunda a Sexta: 9h às 18h';
$mapsEmbed    = $cfg['maps_embed_url']       ?? '';
$mapsLink     = 'https://maps.google.com/?q='.urlencode($logradouro.', '.$bairro.', '.$cidade);

// Redes sociais do banco
$redesBanco = [
    'facebook_url'  => ['fab fa-facebook-f',  'Facebook',  '#1877F2'],
    'instagram_url' => ['fab fa-instagram',   'Instagram', '#E1306C'],
    'youtube_url'   => ['fab fa-youtube',     'YouTube',   '#FF0000'],
    'linkedin_url'  => ['fab fa-linkedin-in', 'LinkedIn',  '#0077B5'],
    'twitter_url'   => ['fab fa-twitter',     'Twitter',   '#1DA1F2'],
    'whatsapp'      => ['fab fa-whatsapp',    'WhatsApp',  '#25D366'],
];
// Filtra só os que têm URL preenchida
$redesAtivas = [];
foreach ($redesBanco as $key => [$icon, $label, $color]) {
    $val = $cfg[$key] ?? '';
    if (!$val) continue;
    // whatsapp pode ser só número — monta URL
    if ($key === 'whatsapp') {
        $num = preg_replace('/\D/', '', $val);
        $url = "https://wa.me/55$num";
    } else {
        $url = (str_starts_with($val,'http') ? $val : 'https://'.$val);
    }
    $redesAtivas[] = compact('icon','label','color','url');
}

// ── Processar formulário ──────────────────────────────────────────────────────
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_contact'])) {
    if (!csrfVerify()) { http_response_code(403); exit('Token de segurança inválido. Recarregue a página.'); }

    $name    = trim($_POST['name'] ?? '');
    $emailF  = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $dept    = trim($_POST['department'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && filter_var($emailF, FILTER_VALIDATE_EMAIL) && $message) {
        try {
            dbExec($pdo,
                "INSERT INTO contacts (name,email,phone,subject,department,message,ip_address,user_agent) VALUES (?,?,?,?,?,?,?,?)",
                [$name,$emailF,$phone,$subject,$dept,$message,$_SERVER['REMOTE_ADDR'],$_SERVER['HTTP_USER_AGENT']??'']);
            $msg = 'success';
            try {
                require_once __DIR__ . '/../includes/mailer.php';
                mailer()->sendContactReply($name, $emailF, $subject ?: 'Contato via Portal');
            } catch (\Throwable $e) { /* fail silently */ }
        } catch (Exception $e) { $msg = 'error'; }
    } else { $msg = 'validation'; }
}

include '../includes/header.php';
?>

<!-- Hero -->
<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 50% 50%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Fale Conosco</span>
        </div>
        <div class="flex items-start gap-6">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0">
                <i class="fas fa-headset"></i>
            </div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Fale Conosco</h1>
                <p class="text-white/70 text-sm max-w-2xl">Entre em contato com o <?= c($cfg,'site_name','CRCAP') ?>. Nossa equipe está pronta para atendê-lo.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-3 gap-8">

        <!-- ── Formulário (sem alterações) ─────────────────────────────────── -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-envelope text-[#BF8D1A]"></i>Enviar Mensagem
                </h2>

                <?php if ($msg === 'success'): ?>
                <div class="bg-[#006633]/10 border border-[#006633]/30 rounded-2xl p-8 text-center">
                    <i class="fas fa-check-circle text-[#006633] text-5xl mb-4 block"></i>
                    <h3 class="font-bold text-[#006633] text-xl mb-2">Mensagem enviada!</h3>
                    <p class="text-sm text-[#022E6B] mb-4">Recebemos sua mensagem e retornaremos em até 3 dias úteis no e-mail informado.</p>
                    <a href="/crcap/pages/contato.php" class="px-6 py-2.5 bg-[#001644] text-white rounded-xl text-xs font-semibold hover:bg-[#022E6B] transition">Enviar outra mensagem</a>
                </div>
                <?php else: ?>
                <?php if ($msg === 'validation'): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl px-4 py-3 mb-5 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>Preencha todos os campos obrigatórios com informações válidas.
                </div>
                <?php endif; ?>
                <?php if ($msg === 'error'): ?>
                <div class="bg-orange-50 border border-orange-200 text-orange-700 text-xs rounded-xl px-4 py-3 mb-5 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i>Ocorreu um erro. Tente novamente ou entre em contato por telefone.
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_contact" value="1">

                    <!-- Departamento -->
                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-2">Departamento</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <?php $depts = [
                                ['fa-info-circle','Informações Gerais','geral'],
                                ['fa-id-card','Registro','registro'],
                                ['fa-search','Fiscalização','fiscalizacao'],
                                ['fa-graduation-cap','Cursos e Eventos','cursos'],
                                ['fa-dollar-sign','Financeiro','financeiro'],
                                ['fa-comment','Ouvidoria','ouvidoria'],
                            ]; foreach ($depts as $d): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="department" value="<?= $d[2] ?>" class="hidden peer" <?= ($_POST['department']??'')===$d[2]?'checked':'' ?>>
                                <div class="flex items-center gap-2 p-2.5 rounded-xl border-2 border-[#001644]/10 peer-checked:border-[#BF8D1A] peer-checked:bg-[#BF8D1A]/5 hover:border-[#BF8D1A]/50 transition text-xs text-[#001644] font-medium">
                                    <i class="fas <?= $d[0] ?> text-[#BF8D1A] text-sm"></i><?= $d[1] ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Nome Completo *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name']??'') ?>" required
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 bg-[#F8FAFC] transition" placeholder="Seu nome">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">E-mail *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" required
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 bg-[#F8FAFC] transition" placeholder="seu@email.com">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Telefone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone']??'') ?>"
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC] transition" placeholder="(96) 9xxxx-xxxx">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Assunto *</label>
                            <input type="text" name="subject" value="<?= htmlspecialchars($_POST['subject']??'') ?>" required
                                class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] bg-[#F8FAFC] transition" placeholder="Assunto da mensagem">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-[#022E6B] uppercase tracking-wider mb-1.5">Mensagem *</label>
                        <textarea name="message" rows="5" required
                            class="w-full px-4 py-3 border border-[#001644]/10 rounded-xl text-sm focus:outline-none focus:border-[#BF8D1A] focus:ring-2 focus:ring-[#BF8D1A]/10 bg-[#F8FAFC] resize-none transition" placeholder="Descreva sua dúvida, solicitação ou comentário..."><?= htmlspecialchars($_POST['message']??'') ?></textarea>
                        <p class="text-[10px] text-[#022E6B]/60 mt-1">Mínimo de 20 caracteres.</p>
                    </div>

                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" required class="mt-0.5 rounded border-[#001644]/20">
                        <span class="text-[10px] text-[#022E6B]">Li e aceito a <a href="/crcap/pages/privacidade.php" class="text-[#BF8D1A] hover:underline">Política de Privacidade</a> e autorizo o tratamento dos meus dados pessoais para resposta a esta mensagem.</span>
                    </label>

                    <button type="submit" class="w-full py-4 bg-[#001644] text-white font-bold rounded-xl hover:bg-[#022E6B] hover:shadow-xl hover:shadow-[#001644]/20 transition flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>Enviar Mensagem
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Sidebar dinâmica ─────────────────────────────────────────────── -->
        <aside class="space-y-5">

            <!-- Informações de Contato — dados do banco -->
            <div class="bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-6 text-white">
                <h3 class="font-bold text-sm mb-5 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-[#BF8D1A]"></i>Sede Principal
                </h3>
                <div class="space-y-4 text-xs">

                    <?php if ($logradouro || $bairro || $cidade): ?>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt text-[#BF8D1A] mt-0.5 w-4 text-center flex-shrink-0"></i>
                        <div>
                            <p class="font-semibold">Endereço</p>
                            <p class="text-white/70 mt-0.5">
                                <?php
                                $partes = array_filter([$logradouro, $complemento, $bairro]);
                                echo implode(', ', array_map('htmlspecialchars', $partes));
                                ?>
                                <?php if ($cidade): ?><br><?= htmlspecialchars($cidade) ?><?php endif; ?>
                                <?php if ($cep): ?><br>CEP: <?= htmlspecialchars($cep) ?><?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($telefone || $whatsapp): ?>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-phone text-[#BF8D1A] mt-0.5 w-4 text-center flex-shrink-0"></i>
                        <div>
                            <p class="font-semibold">Telefone</p>
                            <?php if ($telefone): ?>
                            <p class="text-white/70 mt-0.5">
                                <a href="tel:<?= preg_replace('/\D/','',$telefone) ?>" class="hover:text-[#BF8D1A] transition">
                                    <?= htmlspecialchars($telefone) ?>
                                </a>
                            </p>
                            <?php endif; ?>
                            <?php if ($whatsapp): ?>
                            <?php $waNum = preg_replace('/\D/','',$whatsapp); ?>
                            <p class="text-white/70 mt-0.5">
                                <a href="https://wa.me/55<?= $waNum ?>" target="_blank" class="hover:text-[#BF8D1A] transition flex items-center gap-1.5">
                                    <i class="fab fa-whatsapp text-[#25D366]"></i>WhatsApp: <?= htmlspecialchars($whatsapp) ?>
                                </a>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($email): ?>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-envelope text-[#BF8D1A] mt-0.5 w-4 text-center flex-shrink-0"></i>
                        <div>
                            <p class="font-semibold">E-mail</p>
                            <p class="text-white/70 mt-0.5">
                                <a href="mailto:<?= htmlspecialchars($email) ?>" class="hover:text-[#BF8D1A] transition">
                                    <?= htmlspecialchars($email) ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($horario): ?>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-clock text-[#BF8D1A] mt-0.5 w-4 text-center flex-shrink-0"></i>
                        <div>
                            <p class="font-semibold">Horário de Atendimento</p>
                            <p class="text-white/70 mt-0.5"><?= nl2br(htmlspecialchars($horario)) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Mapa — embed do banco ou fallback estático -->
            <div class="bg-white rounded-2xl overflow-hidden border border-[#001644]/3 shadow-sm">
                <?php if ($mapsEmbed && str_contains($mapsEmbed, 'google.com/maps/embed')): ?>
                <div class="relative">
                    <iframe src="<?= htmlspecialchars($mapsEmbed) ?>"
                            width="100%" height="220" style="border:0;"
                            allowfullscreen loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <?php else: ?>
                <!-- Fallback: mapa estático via OpenStreetMap -->
                <?php
                    // Tenta extrair lat/lng do embed ou usa coordenadas de Macapá
                    $lat = '-0.0349'; $lng = '-51.0694';
                    if (preg_match('/!3d([\-\d\.]+)/', $mapsEmbed, $m)) $lat = $m[1];
                    if (preg_match('/!2d([\-\d\.]+)/', $mapsEmbed, $m)) $lng = $m[1];
                    $osmUrl = "https://www.openstreetmap.org/export/embed.html?bbox="
                        .($lng-0.005).",".($lat-0.003).",".($lng+0.005).",".($lat+0.003)
                        ."&amp;layer=mapnik&amp;marker=$lat,$lng";
                ?>
                <iframe src="<?= $osmUrl ?>"
                        width="100%" height="220" style="border:0;"
                        loading="lazy"></iframe>
                <?php endif; ?>
                <div class="p-4 border-t border-[#001644]/5">
                    <a href="<?= htmlspecialchars($mapsLink) ?>" target="_blank" rel="noopener"
                       class="flex items-center justify-center gap-2 text-xs text-[#001644] font-semibold hover:text-[#BF8D1A] transition">
                        <i class="fas fa-map text-[#BF8D1A]"></i>Ver no Google Maps
                    </a>
                </div>
            </div>

            <!-- Redes Sociais — do banco -->
            <?php if (!empty($redesAtivas)): ?>
            <div class="bg-white rounded-2xl p-5 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Nossas Redes Sociais</h3>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($redesAtivas as $r): ?>
                    <a href="<?= htmlspecialchars($r['url']) ?>" target="_blank" rel="noopener"
                       class="flex items-center gap-2 p-3 rounded-xl border border-[#001644]/5 hover:border-[#BF8D1A]/30 hover:shadow-sm transition group">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs flex-shrink-0"
                             style="background:<?= htmlspecialchars($r['color']) ?>">
                            <i class="<?= htmlspecialchars($r['icon']) ?>"></i>
                        </div>
                        <span class="text-xs font-medium text-[#001644] group-hover:text-[#BF8D1A] transition truncate">
                            <?= htmlspecialchars($r['label']) ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- FAQ — mantido fixo -->
            <div class="bg-[#BF8D1A]/10 border border-[#BF8D1A]/30 rounded-2xl p-5">
                <h3 class="font-bold text-[#001644] text-sm mb-3 flex items-center gap-2">
                    <i class="fas fa-question-circle text-[#BF8D1A]"></i>Perguntas Frequentes
                </h3>
                <p class="text-xs text-[#022E6B] mb-3">Sua dúvida pode já estar respondida em nosso FAQ.</p>
                <a href="/crcap/pages/faq.php"
                   class="flex items-center justify-center gap-2 py-2.5 bg-[#BF8D1A] text-white rounded-xl text-xs font-bold hover:bg-[#001644] transition">
                    Acessar FAQ <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

        </aside>
    </div>
</main>

<?php include '../includes/footer.php'; ?>