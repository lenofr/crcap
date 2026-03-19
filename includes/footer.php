<?php
// includes/footer.php — 100% dinâmico via BD (settings + menu_items location='footer')
if (!isset($pdo)) require_once __DIR__ . '/db.php';

// ── Settings dinâmicas ────────────────────────────────────────────────────
$fs = [];
foreach (dbFetchAll($pdo, "SELECT setting_key, setting_value FROM settings WHERE setting_key IN
    ('site_name','site_email','site_phone','site_cnpj','whatsapp',
     'facebook_url','instagram_url','twitter_url','linkedin_url','youtube_url',
     'endereco_logradouro','endereco_bairro','endereco_cidade','horario_funcionamento',
     'site_logo')") as $r) { $fs[$r['setting_key']] = $r['setting_value']; }

$siteName = $fs['site_name'] ?? 'CRCAP';

// ── Menu do rodapé (location='footer') ────────────────────────────────────
$footerItems = dbFetchAll($pdo,
    "SELECT * FROM menu_items WHERE menu_location='footer' AND status='active'
     ORDER BY order_position ASC, id ASC");

// Montar grupos: parent_id=NULL → cabeçalho de coluna; demais → filhos
$footerGroups = []; $footerChildren = [];
foreach ($footerItems as $fi) {
    if ($fi['parent_id'] === null) $footerGroups[] = $fi;
    else $footerChildren[(int)$fi['parent_id']][] = $fi;
}
?>
<footer class="bg-[#001644] text-white relative overflow-hidden mt-16">
    <!-- padrão de fundo -->
    <div class="absolute inset-0 pointer-events-none opacity-20"
         style="background-image:url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E\");">
    </div>

    <div class="container mx-auto px-4 py-12 relative z-10">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">

            <!-- ── Coluna 1: Marca + Redes ─────────────────────────────── -->
            <div class="space-y-5">
                <a href="/crcap/index.php" class="flex items-center gap-3 group">
                    <?php if (!empty($fs['site_logo'])): ?>
                    <img src="<?= h($fs['site_logo']) ?>" alt="<?= h($siteName) ?>" class="h-9 object-contain brightness-0 invert opacity-90">
                    <?php else: ?>
                    <div class="w-11 h-11 bg-gradient-to-br from-[#BF8D1A] to-[#022E6B] rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg">C</div>
                    <div>
                        <h2 class="text-lg font-bold"><?= h($siteName) ?></h2>
                        <p class="text-[9px] text-white/60 uppercase tracking-wider">Conselho Regional</p>
                    </div>
                    <?php endif; ?>
                </a>
                <p class="text-xs text-white/60 leading-relaxed">Órgão fiscalizador responsável pelo controle e auditoria das atividades contábeis profissionais no Amapá.</p>

                <!-- Redes sociais dinâmicas -->
                <div class="flex flex-wrap gap-2">
                    <?php foreach ([
                        'facebook_url'  => 'fab fa-facebook-f',
                        'instagram_url' => 'fab fa-instagram',
                        'twitter_url'   => 'fab fa-twitter',
                        'linkedin_url'  => 'fab fa-linkedin-in',
                        'youtube_url'   => 'fab fa-youtube',
                    ] as $key => $icon):
                        if (empty($fs[$key])) continue; ?>
                    <a href="<?= h($fs[$key]) ?>" target="_blank" rel="noopener"
                       class="w-9 h-9 rounded-xl bg-white/8 hover:bg-[#BF8D1A] flex items-center justify-center text-white/60 hover:text-white transition text-xs">
                        <i class="<?= $icon ?>"></i>
                    </a>
                    <?php endforeach; ?>
                    <?php if (!empty($fs['whatsapp'])): ?>
                    <a href="https://wa.me/55<?= preg_replace('/\D/','',$fs['whatsapp']) ?>" target="_blank" rel="noopener"
                       class="w-9 h-9 rounded-xl bg-white/8 hover:bg-[#25D366] flex items-center justify-center text-white/60 hover:text-white transition text-xs">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Colunas dinâmicas do BD ─────────────────────────────── -->
            <?php
            $colCount = 0;
            foreach ($footerGroups as $group):
                $kids = $footerChildren[(int)$group['id']] ?? [];
                if (empty($kids)) continue; // pula grupos vazios
                $colCount++;
                if ($colCount > 3) break; // máximo 3 colunas dinâmicas
            ?>
            <div>
                <h4 class="text-sm font-semibold mb-4 flex items-center gap-2">
                    <?php if ($group['icon']): ?><i class="fas <?= h($group['icon']) ?> text-[#BF8D1A] text-xs"></i><?php endif; ?>
                    <?= h($group['title']) ?>
                </h4>
                <ul class="space-y-2.5">
                <?php foreach ($kids as $k): ?>
                    <li>
                        <a href="<?= h($k['url'] ?: '#') ?>" target="<?= h($k['target']) ?>"
                           class="flex items-center gap-2 text-xs text-white/65 hover:text-[#BF8D1A] transition group">
                            <i class="fas fa-chevron-right text-[#BF8D1A] text-[8px] group-hover:translate-x-0.5 transition"></i>
                            <?= h($k['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>

            <!-- ── Contato dinâmico (sempre mostra) ───────────────────── -->
            <?php if ($colCount < 3): // só mostra se sobrou espaço ?>
            <div>
                <h4 class="text-sm font-semibold mb-4 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-[#BF8D1A] text-xs"></i>Contato
                </h4>
                <ul class="space-y-3.5 text-xs text-white/65">
                    <?php if (!empty($fs['endereco_logradouro'])): ?>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt text-[#BF8D1A] mt-0.5 flex-shrink-0 text-[11px]"></i>
                        <span class="leading-relaxed"><?= h($fs['endereco_logradouro']) ?><?= !empty($fs['endereco_bairro']) ? ', '.$fs['endereco_bairro'] : '' ?><br><?= h($fs['endereco_cidade'] ?? 'Macapá – AP') ?></span>
                    </li>
                    <?php else: ?>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt text-[#BF8D1A] mt-0.5 flex-shrink-0 text-[11px]"></i>
                        <span>Av. Padre Júlio Maria Lombaerd, 1010<br>Centro, Macapá – AP</span>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($fs['site_phone'])): ?>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-phone text-[#BF8D1A] flex-shrink-0 text-[11px]"></i>
                        <a href="tel:<?= preg_replace('/\D','',$fs['site_phone']) ?>" class="hover:text-white transition"><?= h($fs['site_phone']) ?></a>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($fs['whatsapp'])): ?>
                    <li class="flex items-center gap-3">
                        <i class="fab fa-whatsapp text-[#25D366] flex-shrink-0 text-[11px]"></i>
                        <a href="https://wa.me/55<?= preg_replace('/\D/','',$fs['whatsapp']) ?>" target="_blank" class="hover:text-white transition"><?= h($fs['whatsapp']) ?></a>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($fs['site_email'])): ?>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-envelope text-[#BF8D1A] flex-shrink-0 text-[11px]"></i>
                        <a href="mailto:<?= h($fs['site_email']) ?>" class="hover:text-white transition"><?= h($fs['site_email']) ?></a>
                    </li>
                    <?php endif; ?>

                    <li class="flex items-center gap-3">
                        <i class="fas fa-clock text-[#BF8D1A] flex-shrink-0 text-[11px]"></i>
                        <span><?= h($fs['horario_funcionamento'] ?? 'Seg-Sex: 9h às 18h') ?></span>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Rodapé inferior ─────────────────────────────────────────── -->
        <div class="border-t border-white/10 pt-6 flex flex-col md:flex-row justify-between items-center gap-3">
            <p class="text-[10px] text-white/40">
                &copy; <?= date('Y') ?> <?= h($siteName) ?> – Todos os direitos reservados.
                <?php if (!empty($fs['site_cnpj'])): ?> &nbsp;|&nbsp; CNPJ: <?= h($fs['site_cnpj']) ?><?php endif; ?>
            </p>
            <div class="flex flex-wrap justify-center gap-x-5 gap-y-1 text-[10px] text-white/40">
                <a href="/crcap/pages/privacidade.php"   class="hover:text-white transition">Privacidade</a>
                <a href="/crcap/pages/termos.php"        class="hover:text-white transition">Termos</a>
                <a href="/crcap/pages/acessibilidade.php" class="hover:text-white transition">Acessibilidade</a>
                <a href="/crcap/sitemap.xml"             class="hover:text-white transition">Sitemap</a>
                <?php if (isset($pdo) && function_exists('isLogged') && isLogged() && function_exists('isAdmin') && isAdmin()): ?>
                <a href="/crcap/admin/index.php" class="hover:text-white transition"><i class="fas fa-lock mr-1"></i>Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>
</body>
</html>