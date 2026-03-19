<?php
// ── Includes — ANTES de qualquer HTML ─────────────────────────────────────
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLogged() || !in_array($_SESSION['role'] ?? '', ['admin','editor'])) {
    header('Location: ../login.php'); exit;
}

$msg  = '';
$tab  = $_GET['tab'] ?? 'geral';

// Handle logo/favicon upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo_file'])) {
    $file = $_FILES['logo_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png','jpg','jpeg','svg','webp'];
        if (in_array($ext, $allowed)) {
            $fname = 'logo.' . $ext;
            $dest  = dirname(__DIR__) . '/uploads/images/' . $fname;
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
            move_uploaded_file($file['tmp_name'], $dest);
            $val = '/uploads/images/' . $fname;
            $exists = dbFetch($pdo, "SELECT id FROM settings WHERE setting_key='site_logo'");
            if ($exists) dbExec($pdo, "UPDATE settings SET setting_value=? WHERE setting_key='site_logo'", [$val]);
            else         dbExec($pdo, "INSERT INTO settings (setting_key,setting_value,setting_group) VALUES ('site_logo',?,'general')", [$val]);
            $msg = 'Logo atualizado com sucesso!';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['favicon_file'])) {
    $file = $_FILES['favicon_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['ico','png','svg'])) {
            $fname = 'favicon.' . $ext;
            $dest  = dirname(__DIR__) . '/uploads/images/' . $fname;
            move_uploaded_file($file['tmp_name'], $dest);
            $val = '/uploads/images/' . $fname;
            $exists = dbFetch($pdo, "SELECT id FROM settings WHERE setting_key='site_favicon'");
            if ($exists) dbExec($pdo, "UPDATE settings SET setting_value=? WHERE setting_key='site_favicon'", [$val]);
            else         dbExec($pdo, "INSERT INTO settings (setting_key,setting_value,setting_group) VALUES ('site_favicon',?,'general')", [$val]);
            $msg = 'Favicon atualizado com sucesso!';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_settings'])) {
    foreach ($_POST as $k => $v) {
        if ($k === 'form_settings') continue;
        $v = trim($v);
        $exists = dbFetch($pdo, "SELECT id FROM settings WHERE setting_key=?", [$k]);
        if ($exists) dbExec($pdo, "UPDATE settings SET setting_value=? WHERE setting_key=?", [$v, $k]);
        else         dbExec($pdo, "INSERT INTO settings (setting_key,setting_value) VALUES (?,?)", [$k, $v]);

        // Se alterou o caminho base, atualizar .htaccess automaticamente
        if ($k === 'app_basepath') {
            $newBase = rtrim($v, '/');
            $htPath  = dirname(__DIR__) . '/.htaccess';
            if (file_exists($htPath) && is_writable($htPath)) {
                $ht = file_get_contents($htPath);
                // Atualiza RewriteBase
                $ht = preg_replace('/RewriteBase\s+\S+/', 'RewriteBase ' . ($newBase ?: '/'), $ht);
                file_put_contents($htPath, $ht);
            }
        }
    }
    $msg = 'Configurações salvas com sucesso!';
}

$settingsRaw = dbFetchAll($pdo, "SELECT setting_key, setting_value FROM settings");
$settings    = array_column($settingsRaw, 'setting_value', 'setting_key');
function cfg($settings, $key, $default='') { return htmlspecialchars($settings[$key] ?? $default); }

// ── Inclui header HTML (após todo processamento POST) ──────────────────────
$pageTitle = 'Configurações · Admin CRCAP';
$activeAdm = 'settings';
require_once __DIR__ . '/admin_header.php';
?>

<?php if ($msg): ?>
<div class="bg-[#006633]/10 border border-[#006633]/30 text-xs rounded-xl px-4 py-3 mb-5 text-[#001644] flex items-center gap-2">
    <i class="fas fa-check-circle text-[#006633]"></i><?= h($msg) ?>
</div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-bold text-[#001644] flex items-center gap-2"><i class="fas fa-cog text-[#BF8D1A]"></i>Configurações do Site</h2>
</div>

<!-- Tabs -->
<div class="flex gap-1 bg-white border border-[#001644]/5 rounded-xl p-1 mb-6 shadow-sm overflow-x-auto">
    <?php foreach (['geral'=>'Geral','identidade'=>'Identidade Visual','contato'=>'Contato & Endereço','social'=>'Redes Sociais','seo'=>'SEO & Analytics','sistema'=>'Sistema'] as $t=>$l): ?>
    <a href="?tab=<?= $t ?>" class="flex-shrink-0 px-4 py-2 rounded-lg text-xs font-semibold transition whitespace-nowrap
       <?= $tab===$t ? 'bg-[#001644] text-white shadow-sm' : 'text-[#022E6B] hover:text-[#001644]' ?>">
        <?= $l ?>
    </a>
    <?php endforeach; ?>
</div>

<form method="POST" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="form_settings" value="1">

    <!-- ── GERAL ── -->
    <?php if ($tab === 'geral'): ?>
    <div class="card p-6">
        <h3 class="text-sm font-bold text-[#001644] mb-5 flex items-center gap-2"><i class="fas fa-globe text-[#BF8D1A]"></i>Informações Gerais</h3>

        <!-- ── Caminho Base do Site ─────────────────────────────── -->
        <div class="mb-6 p-4 rounded-xl border-2 border-[#BF8D1A]/30 bg-[#BF8D1A]/5">
            <div class="flex items-start gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-[#BF8D1A] flex items-center justify-center text-white flex-shrink-0">
                    <i class="fas fa-folder-open text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-[#001644]">Caminho Base do Site</p>
                    <p class="text-[10px] text-[#022E6B]/60 mt-0.5">
                        Use <code class="bg-white px-1 rounded">/crcap</code> enquanto testa em subdiretório.
                        Mude para <code class="bg-white px-1 rounded">/</code> ou deixe <strong>vazio</strong> ao publicar em <strong>crcap.org.br</strong>.
                    </p>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-3 items-end">
                <div>
                    <label class="form-label">Diretório base</label>
                    <div class="flex gap-2">
                        <input type="text" name="app_basepath" id="appBasepath"
                               value="<?= cfg($settings,'app_basepath','/crcap') ?>"
                               placeholder="/ ou /crcap"
                               class="form-input font-mono flex-1">
                        <button type="button" onclick="setBasepath('')"
                                class="px-3 py-2 text-xs font-bold bg-[#006633] text-white rounded-xl hover:bg-[#001644] transition whitespace-nowrap">
                            Raiz /
                        </button>
                        <button type="button" onclick="setBasepath('/crcap')"
                                class="px-3 py-2 text-xs font-bold bg-white border border-[#001644]/20 rounded-xl hover:bg-[#BF8D1A] hover:text-white transition whitespace-nowrap">
                            /crcap
                        </button>
                    </div>
                </div>
                <div class="text-xs text-[#022E6B]/60 space-y-1.5 bg-white rounded-xl p-3">
                    <p><i class="fas fa-map-marker-alt text-[#BF8D1A] mr-1"></i>Ativo agora: <code class="bg-[#F0F4F8] px-1.5 py-0.5 rounded font-mono text-[#001644] font-bold"><?= appBase() ?: '/' ?></code></p>
                    <p><i class="fas fa-check-circle text-[#006633] mr-1"></i>Após salvar, todos os links e redirects se adaptam automaticamente.</p>
                    <p><i class="fas fa-exclamation-triangle text-[#BF8D1A] mr-1"></i>Atualize também o <code class="bg-[#F0F4F8] px-1 rounded">RewriteBase</code> no <code class="bg-[#F0F4F8] px-1 rounded">.htaccess</code>.</p>
                </div>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="form-label">Nome do Site <span class="text-red-500">*</span></label>
                <input type="text" name="site_name" value="<?= cfg($settings,'site_name','CRCAP - Conselho Regional') ?>" class="form-input" required>
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Descrição Resumida</label>
                <textarea name="site_description" rows="2" class="form-input resize-none"><?= cfg($settings,'site_description','Conselho Regional de Administração') ?></textarea>
                <p class="text-[10px] text-[#022E6B]/60 mt-1">Aparece em meta tags e resultados de busca.</p>
            </div>
            <div>
                <label class="form-label">CNPJ</label>
                <input type="text" name="site_cnpj" value="<?= cfg($settings,'site_cnpj') ?>" class="form-input" placeholder="00.000.000/0000-00">
            </div>
            <div>
                <label class="form-label">Posts por Página</label>
                <input type="number" name="posts_per_page" value="<?= cfg($settings,'posts_per_page','10') ?>" min="1" max="50" class="form-input">
            </div>
            <div>
                <label class="form-label">Horário de Funcionamento</label>
                <input type="text" name="horario_funcionamento" value="<?= cfg($settings,'horario_funcionamento','Seg–Sex: 9h às 18h') ?>" class="form-input">
            </div>
            <div>
                <label class="form-label">Região de Atuação</label>
                <input type="text" name="site_region" value="<?= cfg($settings,'site_region','Amapá – AP') ?>" class="form-input">
            </div>
        </div>
    </div>

    <!-- ── IDENTIDADE VISUAL ── -->
    <?php elseif ($tab === 'identidade'): ?>
    <div class="grid sm:grid-cols-2 gap-6">
        <!-- Logo -->
        <div class="card p-6">
            <h3 class="text-sm font-bold text-[#001644] mb-4 flex items-center gap-2"><i class="fas fa-image text-[#BF8D1A]"></i>Logo do Site</h3>
            <?php $logo = $settings['site_logo'] ?? ''; ?>
            <?php if ($logo): ?>
            <div class="mb-4 p-4 bg-[#F8FAFC] rounded-xl flex items-center justify-center">
                <img src="<?= h($logo) ?>" alt="Logo atual" class="max-h-20 max-w-full object-contain">
            </div>
            <?php endif; ?>
            <label class="block text-xs font-semibold text-[#001644] mb-2">Novo Logo (PNG, SVG, JPG)</label>
            <div class="border-2 border-dashed border-[#001644]/15 rounded-xl p-5 text-center cursor-pointer hover:border-[#BF8D1A]/50 transition" onclick="document.getElementById('logoFile').click()">
                <i class="fas fa-cloud-upload-alt text-2xl text-[#001644]/20 mb-2 block"></i>
                <p class="text-xs text-[#022E6B]">Clique para selecionar</p>
                <p class="text-[10px] text-[#022E6B]/50 mt-1">Recomendado: PNG com fundo transparente, 300×100px</p>
            </div>
            <input type="file" id="logoFile" name="logo_file" accept="image/*" class="hidden" onchange="previewImg(this,'logoPreview')">
            <img id="logoPreview" class="hidden mt-3 max-h-16 rounded-lg mx-auto">
            <button type="submit" class="btn-primary w-full justify-center mt-4"><i class="fas fa-upload"></i>Enviar Logo</button>
        </div>

        <!-- Favicon -->
        <div class="card p-6">
            <h3 class="text-sm font-bold text-[#001644] mb-4 flex items-center gap-2"><i class="fas fa-star text-[#BF8D1A]"></i>Favicon</h3>
            <?php $fav = $settings['site_favicon'] ?? ''; ?>
            <?php if ($fav): ?>
            <div class="mb-4 p-4 bg-[#F8FAFC] rounded-xl flex items-center justify-center">
                <img src="<?= h($fav) ?>" alt="Favicon atual" class="w-16 h-16 object-contain">
            </div>
            <?php endif; ?>
            <label class="block text-xs font-semibold text-[#001644] mb-2">Novo Favicon (ICO, PNG)</label>
            <div class="border-2 border-dashed border-[#001644]/15 rounded-xl p-5 text-center cursor-pointer hover:border-[#BF8D1A]/50 transition" onclick="document.getElementById('faviconFile').click()">
                <i class="fas fa-thumbtack text-2xl text-[#001644]/20 mb-2 block"></i>
                <p class="text-xs text-[#022E6B]">Clique para selecionar</p>
                <p class="text-[10px] text-[#022E6B]/50 mt-1">Recomendado: 32×32px ou 64×64px</p>
            </div>
            <input type="file" id="faviconFile" name="favicon_file" accept=".ico,.png,.svg" class="hidden" onchange="previewImg(this,'favPreview')">
            <img id="favPreview" class="hidden mt-3 w-16 h-16 rounded-lg mx-auto object-contain">
            <button type="submit" class="btn-primary w-full justify-center mt-4"><i class="fas fa-upload"></i>Enviar Favicon</button>
        </div>
    </div>

    <div class="card p-6">
        <h3 class="text-sm font-bold text-[#001644] mb-5 flex items-center gap-2"><i class="fas fa-palette text-[#BF8D1A]"></i>Cores do Tema</h3>
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="form-label">Cor Primária</label>
                <div class="flex gap-2">
                    <input type="color" name="color_primary" value="<?= cfg($settings,'color_primary','#001644') ?>" class="w-12 h-10 rounded-lg border border-[#001644]/10 cursor-pointer p-1">
                    <input type="text" name="color_primary_text" value="<?= cfg($settings,'color_primary','#001644') ?>" class="form-input flex-1 font-mono text-xs" placeholder="#001644">
                </div>
            </div>
            <div>
                <label class="form-label">Cor de Destaque</label>
                <div class="flex gap-2">
                    <input type="color" name="color_accent" value="<?= cfg($settings,'color_accent','#BF8D1A') ?>" class="w-12 h-10 rounded-lg border border-[#001644]/10 cursor-pointer p-1">
                    <input type="text" name="color_accent_text" value="<?= cfg($settings,'color_accent','#BF8D1A') ?>" class="form-input flex-1 font-mono text-xs" placeholder="#BF8D1A">
                </div>
            </div>
            <div>
                <label class="form-label">Cor Secundária</label>
                <div class="flex gap-2">
                    <input type="color" name="color_secondary" value="<?= cfg($settings,'color_secondary','#006633') ?>" class="w-12 h-10 rounded-lg border border-[#001644]/10 cursor-pointer p-1">
                    <input type="text" name="color_secondary_text" value="<?= cfg($settings,'color_secondary','#006633') ?>" class="form-input flex-1 font-mono text-xs" placeholder="#006633">
                </div>
            </div>
        </div>
    </div>

    <!-- ── CONTATO & ENDEREÇO ── -->
    <?php elseif ($tab === 'contato'): ?>
    <div class="card p-6">
        <h3 class="text-sm font-bold text-[#001644] mb-5 flex items-center gap-2"><i class="fas fa-phone text-[#BF8D1A]"></i>Dados de Contato</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label">E-mail Principal</label>
                <input type="email" name="site_email" value="<?= cfg($settings,'site_email','contato@crcap.org.br') ?>" class="form-input">
            </div>
            <div>
                <label class="form-label">E-mail de Ouvidoria</label>
                <input type="email" name="ouvidoria_email" value="<?= cfg($settings,'ouvidoria_email') ?>" class="form-input" placeholder="ouvidoria@crcap.org.br">
            </div>
            <div>
                <label class="form-label">Telefone Principal</label>
                <input type="text" name="site_phone" value="<?= cfg($settings,'site_phone') ?>" class="form-input" placeholder="(96) 3224-0000">
            </div>
            <div>
                <label class="form-label">WhatsApp</label>
                <input type="text" name="whatsapp" value="<?= cfg($settings,'whatsapp') ?>" class="form-input" placeholder="(96) 99999-0000">
            </div>
        </div>
    </div>
    <div class="card p-6">
        <h3 class="text-sm font-bold text-[#001644] mb-5 flex items-center gap-2"><i class="fas fa-map-marker-alt text-[#BF8D1A]"></i>Endereço</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="form-label">Logradouro</label>
                <input type="text" name="endereco_logradouro" value="<?= cfg($settings,'endereco_logradouro') ?>" class="form-input" placeholder="Av. Mendonça Furtado, 3345">
            </div>
            <div>
                <label class="form-label">Bairro</label>
                <input type="text" name="endereco_bairro" value="<?= cfg($settings,'endereco_bairro') ?>" class="form-input" placeholder="Boné Azul">
            </div>
            <div>
                <label class="form-label">Cidade / Estado</label>
                <input type="text" name="endereco_cidade" value="<?= cfg($settings,'endereco_cidade','Macapá / AP') ?>" class="form-input">
            </div>
            <div>
                <label class="form-label">CEP</label>
                <input type="text" name="endereco_cep" value="<?= cfg($settings,'endereco_cep') ?>" class="form-input" placeholder="68906-450">
            </div>
            <div>
                <label class="form-label">Sala / Complemento</label>
                <input type="text" name="endereco_complemento" value="<?= cfg($settings,'endereco_complemento') ?>" class="form-input" placeholder="Sala 101">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Link Google Maps (URL embed)</label>
                <input type="url" name="maps_embed_url" value="<?= cfg($settings,'maps_embed_url') ?>" class="form-input" placeholder="https://maps.google.com/maps?...">
            </div>
        </div>
    </div>

    <!-- ── REDES SOCIAIS ── -->
    <?php elseif ($tab === 'social'): ?>
    <div class="card p-6">
        <h3 class="text-sm font-bold text-[#001644] mb-5 flex items-center gap-2"><i class="fas fa-share-alt text-[#BF8D1A]"></i>Redes Sociais</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <?php $sociais = [
                'facebook_url'  => ['fab fa-facebook-f','Facebook','https://facebook.com/'],
                'instagram_url' => ['fab fa-instagram','Instagram','https://instagram.com/'],
                'twitter_url'   => ['fab fa-twitter','Twitter / X','https://twitter.com/'],
                'linkedin_url'  => ['fab fa-linkedin-in','LinkedIn','https://linkedin.com/company/'],
                'youtube_url'   => ['fab fa-youtube','YouTube','https://youtube.com/'],
                'tiktok_url'    => ['fab fa-tiktok','TikTok','https://tiktok.com/@'],
            ]; ?>
            <?php foreach ($sociais as $key => [$icon, $label, $placeholder]): ?>
            <div>
                <label class="form-label"><i class="<?= $icon ?> text-[#BF8D1A] mr-1.5"></i><?= $label ?></label>
                <input type="url" name="<?= $key ?>" value="<?= cfg($settings,$key) ?>" class="form-input" placeholder="<?= $placeholder ?>">
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── SEO ── -->
    <?php elseif ($tab === 'seo'): ?>
    <div class="card p-6">
        <h3 class="text-sm font-bold text-[#001644] mb-5 flex items-center gap-2"><i class="fas fa-search text-[#BF8D1A]"></i>SEO e Rastreamento</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Google Analytics (GA4)</label>
                <input type="text" name="google_analytics" value="<?= cfg($settings,'google_analytics') ?>" class="form-input" placeholder="G-XXXXXXXXXX">
            </div>
            <div>
                <label class="form-label">Google Tag Manager</label>
                <input type="text" name="google_tag_manager" value="<?= cfg($settings,'google_tag_manager') ?>" class="form-input" placeholder="GTM-XXXXXXX">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Google Search Console (meta tag verification)</label>
                <input type="text" name="google_search_console" value="<?= cfg($settings,'google_search_console') ?>" class="form-input" placeholder="google-site-verification=...">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Meta Keywords Padrão</label>
                <input type="text" name="default_keywords" value="<?= cfg($settings,'default_keywords','CRCAP, conselho regional, administração, Amapá') ?>" class="form-input" placeholder="separadas por vírgula">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Scripts no &lt;head&gt; (pixels, chatbot, etc.)</label>
                <textarea name="head_scripts" rows="4" class="form-input resize-none font-mono text-xs"><?= cfg($settings,'head_scripts') ?></textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Scripts antes de &lt;/body&gt;</label>
                <textarea name="body_scripts" rows="4" class="form-input resize-none font-mono text-xs"><?= cfg($settings,'body_scripts') ?></textarea>
            </div>
        </div>
    </div>

    <!-- ── SISTEMA ── -->
    <?php elseif ($tab === 'sistema'): ?>
    <div class="card p-6">
        <h3 class="text-sm font-bold text-[#001644] mb-5 flex items-center gap-2"><i class="fas fa-server text-[#BF8D1A]"></i>Sistema</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Modo Manutenção</label>
                <select name="maintenance_mode" class="form-input">
                    <option value="0" <?= !($settings['maintenance_mode']??0)?'selected':'' ?>>✅ Desativado (site online)</option>
                    <option value="1" <?= ($settings['maintenance_mode']??0)?'selected':'' ?>>🔧 Ativado (site offline)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Comentários em Posts</label>
                <select name="enable_comments" class="form-input">
                    <option value="1" <?= ($settings['enable_comments']??1)?'selected':'' ?>>Habilitado</option>
                    <option value="0" <?= !($settings['enable_comments']??1)?'selected':'' ?>>Desabilitado</option>
                </select>
            </div>
            <div>
                <label class="form-label">Registro de Novos Usuários</label>
                <select name="allow_registration" class="form-input">
                    <option value="1" <?= ($settings['allow_registration']??1)?'selected':'' ?>>Permitido</option>
                    <option value="0" <?= !($settings['allow_registration']??1)?'selected':'' ?>>Bloqueado</option>
                </select>
            </div>
            <div>
                <label class="form-label">Posts por Página</label>
                <input type="number" name="posts_per_page" value="<?= cfg($settings,'posts_per_page','10') ?>" min="1" max="50" class="form-input">
            </div>
            <div class="sm:col-span-2">
                <label class="form-label">Mensagem de Manutenção</label>
                <textarea name="maintenance_message" rows="2" class="form-input resize-none"><?= cfg($settings,'maintenance_message','Site em manutenção. Voltaremos em breve.') ?></textarea>
            </div>
        </div>
    </div>
    <!-- System Info -->
    <div class="card p-6">
        <h3 class="text-sm font-bold text-[#001644] mb-4 flex items-center gap-2"><i class="fas fa-info-circle text-[#BF8D1A]"></i>Informações do Sistema</h3>
        <div class="grid sm:grid-cols-3 gap-3 text-xs">
            <?php $sysInfo = [
                ['PHP Version', phpversion()],
                ['MySQL', $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)],
                ['Data/Hora Servidor', date('d/m/Y H:i')],
                ['Timezone', date_default_timezone_get()],
                ['Upload Max', ini_get('upload_max_filesize')],
                ['Memory Limit', ini_get('memory_limit')],
            ]; ?>
            <?php foreach ($sysInfo as [$k,$v]): ?>
            <div class="bg-[#F8FAFC] rounded-xl p-3">
                <p class="text-[#022E6B]/60 text-[10px] mb-0.5"><?= $k ?></p>
                <p class="font-semibold text-[#001644]"><?= h($v) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($tab !== 'identidade'): ?>
    <div class="flex justify-end pt-2">
        <button type="submit" class="btn-primary text-sm px-8 py-3">
            <i class="fas fa-save"></i>Salvar Configurações
        </button>
    </div>
    <?php endif; ?>
</form>

<script>
function previewImg(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.classList.remove('hidden'); };
        reader.readAsDataURL(input.files[0]);
    }
}
// Sync color pickers with text inputs
document.querySelectorAll('input[type="color"]').forEach(picker => {
    const textKey = picker.name + '_text';
    const text = document.querySelector(`[name="${textKey}"]`);
    if (text) {
        picker.addEventListener('input', () => text.value = picker.value);
        text.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(text.value)) picker.value = text.value; });
    }
});
</script>

<script>
function setBasepath(val) {
    document.getElementById('appBasepath').value = val;
    document.getElementById('currentBase').textContent = val || '/';
}
</script>
<?php require_once __DIR__ . '/admin_footer.php'; ?>