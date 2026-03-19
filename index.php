<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle  = 'CRCAP · Conselho Regional de Administração do Amapá';
$activeMenu = 'inicio';

$sliders      = dbFetchAll($pdo, "SELECT * FROM sliders WHERE status='active' AND (show_from IS NULL OR show_from<=CURDATE()) AND (show_until IS NULL OR show_until>=CURDATE()) ORDER BY order_position ASC LIMIT 5");
$featuredPost = dbFetch($pdo, "SELECT p.*,c.name AS cat_name,c.color AS cat_color FROM posts p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status='published' AND p.is_featured=1 ORDER BY p.published_at DESC LIMIT 1");
$recentPosts  = dbFetchAll($pdo, "SELECT p.*,c.name AS cat_name,c.color AS cat_color FROM posts p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status='published' ORDER BY p.published_at DESC LIMIT 4");
$schedule     = dbFetchAll($pdo, "SELECT * FROM president_schedule WHERE is_public=1 AND event_date>=CURDATE() AND status IN('scheduled','confirmed','cancelled') ORDER BY event_date ASC, start_time ASC LIMIT 4");
$events       = dbFetchAll($pdo, "SELECT * FROM events WHERE status='published' AND visibility='public' AND event_date>=CURDATE() ORDER BY event_date ASC LIMIT 4");
$commissions  = dbFetchAll($pdo, "SELECT * FROM commissions WHERE status='active' ORDER BY order_position ASC LIMIT 3");
$sidebarLinks = dbFetchAll($pdo, "SELECT * FROM quick_links WHERE status='active' ORDER BY order_position ASC LIMIT 6");

// Live: busca post com transmissão ativa (apenas is_live=1, sem filtro live_ended_at)
$livePost = dbFetch($pdo,
    "SELECT * FROM posts WHERE is_live=1 AND status='published'
     ORDER BY live_started_at DESC LIMIT 1"
);

$newsletterMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    $email  = filter_var(trim($_POST['newsletter_email']), FILTER_VALIDATE_EMAIL);
    $nlName = trim($_POST['newsletter_name'] ?? '');
    $nlProf = trim($_POST['newsletter_profession'] ?? '');

    // Mapeia valor do select para label legível (usado no e-mail e como categoria no BD)
    $profLabels = [
        'contador'  => 'Contador',
        'tecnico'   => 'Técnico em Contabilidade',
        'estudante' => 'Estudante',
        'outro'     => 'Outro',
    ];
    $validProfs = array_keys($profLabels);
    $nlProf     = in_array($nlProf, $validProfs) ? $nlProf : '';
    $categoria  = $nlProf ? ($profLabels[$nlProf]) : null; // label salvo no BD

    if ($email) {
        try {
            // Verifica se já estava inscrito antes
            $alreadyExists = dbFetch($pdo, "SELECT id, status FROM newsletters WHERE email=?", [$email]);

            // ── INSERT/UPDATE correto: salva name, full_name, categoria ──────
            dbExec($pdo,
                "INSERT INTO newsletters
                    (email, name, full_name, categoria, status, confirmed, subscription_ip, subscription_source)
                 VALUES (?,?,?,?,'subscribed',1,?,'home')
                 ON DUPLICATE KEY UPDATE
                    name            = VALUES(name),
                    full_name       = VALUES(full_name),
                    categoria       = VALUES(categoria),
                    subscription_source = VALUES(subscription_source),
                    status          = 'subscribed',
                    unsubscribed_at = NULL",
                [$email, $nlName ?: null, $nlName ?: null, $categoria, $_SERVER['REMOTE_ADDR'] ?? '']
            );

            // ── Envia e-mail usando template da newsletter_pages ─────────────
            if (!$alreadyExists || $alreadyExists['status'] !== 'subscribed') {
                $pageKey = (!$alreadyExists) ? 'welcome' : 'reactivation';

                // Carrega template do BD
                $nlPage = dbFetch($pdo, "SELECT subject, html_content FROM newsletter_pages WHERE page_key=? LIMIT 1", [$pageKey]);

                if ($nlPage) {
                    // Substitui variáveis
                    $unsubUrl = 'https://artemidiaweb.com.br/crcap/unsubscribe.php?email=' . urlencode($email);
                    $htmlTpl  = str_replace(
                        ['{{nome}}', '{{email}}', '{{categoria}}', '{{unsubscribe_url}}'],
                        [
                            htmlspecialchars($nlName ?: 'Prezado(a)'),
                            htmlspecialchars($email),
                            htmlspecialchars($categoria ?? ''),
                            $unsubUrl,
                        ],
                        $nlPage['html_content']
                    );
                    mailer()->send($email, $nlName ?: '', $nlPage['subject'], $htmlTpl);
                } else {
                    // Fallback: template inline se BD não tiver a página
                    $profLine = $categoria ? "<p>Perfil: <strong>" . htmlspecialchars($categoria) . "</strong></p>" : '';
                    $html = mailer()->wrapTemplate(
                        'Inscrição confirmada!',
                        "Olá, <strong>" . htmlspecialchars($nlName ?: 'Prezado(a)') . "</strong>!
                         <p>Você está inscrito(a) na newsletter do <strong>CRCAP</strong>.</p>
                         {$profLine}",
                        'Acessar o Portal CRCAP',
                        'https://artemidiaweb.com.br/crcap/'
                    );
                    mailer()->send($email, $nlName ?: '', 'Bem-vindo(a) à Newsletter CRCAP!', $html);
                }
            }

            $newsletterMsg = 'success';
        } catch (Exception $e) { $newsletterMsg = 'error'; }
    }
}

include __DIR__ . '/includes/header.php';
?>
<style>
/* ═══════════════════════════════════════════
   SLIDER
═══════════════════════════════════════════ */
#sliderWrap{position:relative;width:100%;overflow:hidden;background:#001644;display:block;}

/* Imagem preenche 100% largura, altura proporcional ao aspecto real 2560×709 */
.slide-img{
  display:block;
  width:100%;
  height:auto;
  aspect-ratio:2560/709;
  object-fit:cover;
  object-position:center top;
}
/* mobile: altura mínima de 200px para não ficar tímido */
@media(max-width:640px){
  .slide-img{aspect-ratio:unset;height:200px;object-fit:cover;}
}

.slide{position:absolute;inset:0;opacity:0;z-index:1;transition:opacity .7s ease-in-out;}
.slide.active{opacity:1;z-index:2;}

/* garante que o wrapper tenha a altura do aspecto da imagem */
#sliderInner{
  position:relative;
  width:100%;
  aspect-ratio:2560/709;
  min-height:140px;
}
@media(max-width:640px){#sliderInner{aspect-ratio:unset;height:200px;}}

/* Overlay gradiente escuro em baixo para legibilidade do texto */
.slide-overlay{
  position:absolute;inset:0;z-index:3;
  background:linear-gradient(to top,
    rgba(0,22,68,.92) 0%,
    rgba(0,22,68,.35) 45%,
    transparent 100%);
}

/* Conteúdo do slide: texto + badge, alinhado à esquerda embaixo */
.slide-content{
  position:absolute;inset:0;z-index:4;
  display:flex;align-items:flex-end;
  padding-bottom:clamp(.6rem,3vw,2rem);
}

/* Botões prev/next — sobrepostos, 30% transparentes */
.sl-btn{
  position:absolute;top:50%;transform:translateY(-50%);z-index:10;
  width:2.2rem;height:2.2rem;border-radius:50%;cursor:pointer;
  background:rgba(255,255,255,.30);
  border:1px solid rgba(255,255,255,.22);
  backdrop-filter:blur(3px);
  color:#fff;font-size:.7rem;
  display:flex;align-items:center;justify-content:center;
  transition:background .2s;outline:none;
}
.sl-btn:hover{background:rgba(255,255,255,.52);}
.sl-btn.prev{left:.7rem;}
.sl-btn.next{right:.7rem;}

/* Dots */
.sl-dots{
  position:absolute;bottom:.5rem;left:50%;
  transform:translateX(-50%);z-index:10;
  display:flex;gap:.35rem;
}
.sl-dot{
  width:1.6rem;height:.2rem;border-radius:9999px;padding:0;
  background:rgba(255,255,255,.35);border:none;cursor:pointer;
  transition:background .2s,width .2s;
}
.sl-dot.on{background:#fff;width:2.4rem;}

/* ═══════════════════════════════════════════
   CARDS / UTILS
═══════════════════════════════════════════ */
.ql-card{transition:transform .22s,box-shadow .22s;}
.ql-card:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(0,22,68,.10);}

.post-row{transition:transform .18s,border-color .18s;}
.post-row:hover{transform:translateX(4px);}

.ev-card{transition:transform .22s,box-shadow .22s;}
.ev-card:hover{transform:translateY(-4px);box-shadow:0 16px 28px rgba(0,22,68,.12);}

.comm-card{transition:transform .22s,box-shadow .22s;}
.comm-card:hover{transform:translateY(-4px);box-shadow:0 16px 28px rgba(0,22,68,.12);}

.tl-item{transition:transform .18s;}
.tl-item:hover{transform:translateX(3px);}
.tl-card{transition:background .18s,box-shadow .18s;}
.tl-item:hover .tl-card{background:#fff;box-shadow:0 4px 16px rgba(0,22,68,.08);}

/* ═══════════════════════════════════════════
   LIVE PLAYER
═══════════════════════════════════════════ */
@keyframes pulse-live {
  0%,100%{opacity:1;transform:scale(1);}
  50%{opacity:.4;transform:scale(1.15);}
}
.live-dot{
  display:inline-block;
  width:.55rem;height:.55rem;
  border-radius:50%;
  background:#ef4444;
  animation:pulse-live 1.2s ease-in-out infinite;
}
.live-badge{
  display:inline-flex;align-items:center;gap:.35rem;
  background:#ef4444;color:#fff;
  font-size:.6rem;font-weight:800;
  letter-spacing:.08em;text-transform:uppercase;
  padding:.25rem .6rem;border-radius:9999px;
}
</style>
<!-- ════════════════════════════════════════
     LIVE PLAYER / SLIDER
════════════════════════════════════════ -->
<?php if ($livePost): ?>
<?php
// Build embed URL server-side
function buildLiveEmbedUrl($url, $platform) {
    if (!$url) return '';
    if ($platform === 'youtube' || strpos($url,'youtube') !== false || strpos($url,'youtu.be') !== false) {
        $vid = '';
        if      (preg_match('/[?&]v=([^&#]+)/', $url, $m))      $vid = $m[1]; // watch?v=
        elseif  (preg_match('/youtu\.be\/([^?#]+)/', $url, $m)) $vid = $m[1]; // youtu.be/
        elseif  (preg_match('/\/live\/([^?#]+)/', $url, $m))   $vid = $m[1]; // /live/
        elseif  (preg_match('/\/embed\/([^?#]+)/', $url, $m))  $vid = $m[1]; // already embed
        elseif  (preg_match('/\/shorts\/([^?#]+)/', $url, $m)) $vid = $m[1]; // shorts
        return $vid ? "https://www.youtube.com/embed/{$vid}?autoplay=1&mute=1&rel=0&modestbranding=1" : $url;
    }
    if ($platform === 'twitch' || strpos($url,'twitch.tv') !== false) {
        $ch = basename(parse_url($url, PHP_URL_PATH));
        return "https://player.twitch.tv/?channel={$ch}&parent=" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "&autoplay=true";
    }
    if ($platform === 'facebook' || strpos($url,'facebook') !== false) {
        return "https://www.facebook.com/plugins/video.php?href=" . urlencode($url) . "&autoplay=true&show_text=false";
    }
    return $url;
}
$embedUrl = buildLiveEmbedUrl($livePost['live_url'], $livePost['live_platform'] ?? '');
?>
<div id="liveWrap" class="w-full bg-black relative" style="min-height:260px;">
  <!-- Live badge piscando -->
  <div class="absolute top-3 left-3 z-20 flex items-center gap-2">
    <span class="live-badge">
      <span class="live-dot"></span> Ao Vivo
    </span>
    <?php if (!empty($livePost['cat_name'])): ?>
    <span class="inline-block px-2.5 py-1 bg-white/15 backdrop-blur-sm text-white text-[9px] font-bold rounded-full border border-white/20">
      <?= htmlspecialchars($livePost['cat_name'] ?? '') ?>
    </span>
    <?php endif; ?>
  </div>

  <!-- Iframe do player -->
  <div class="w-full" style="aspect-ratio:16/7;max-height:480px;">
    <?php if ($embedUrl): ?>
    <iframe src="<?= htmlspecialchars($embedUrl) ?>"
            class="w-full h-full border-0"
            allow="autoplay; fullscreen; picture-in-picture"
            allowfullscreen
            style="min-height:220px;display:block;"></iframe>
    <?php else: ?>
    <!-- Fallback: link clicável se não for possível embed -->
    <a href="<?= htmlspecialchars($livePost['live_url']) ?>" target="_blank"
       class="flex items-center justify-center w-full h-full bg-gradient-to-br from-[#001644] to-[#22004d]"
       style="min-height:220px;text-decoration:none;">
      <?php if ($livePost['featured_image']): ?>
      <img src="<?= htmlspecialchars($livePost['featured_image']) ?>"
           class="absolute inset-0 w-full h-full object-cover opacity-30">
      <?php endif; ?>
      <div class="relative z-10 text-center text-white">
        <div class="w-16 h-16 rounded-full bg-red-500/80 flex items-center justify-center mx-auto mb-3 hover:bg-red-500 transition">
          <i class="fas fa-play text-2xl ml-1"></i>
        </div>
        <p class="text-sm font-bold">Assistir ao Vivo</p>
        <p class="text-xs text-white/60 mt-1"><?= htmlspecialchars($livePost['title']) ?></p>
      </div>
    </a>
    <?php endif; ?>
  </div>

  <!-- Título da live na parte inferior -->
  <div class="absolute bottom-0 left-0 right-0 z-10 px-4 py-3"
       style="background:linear-gradient(to top,rgba(0,0,0,.7) 0%,transparent 100%)">
    <p class="text-white font-bold text-sm md:text-base leading-snug line-clamp-1">
      <?= htmlspecialchars($livePost['title']) ?>
    </p>
    <?php if (!empty($livePost['excerpt'])): ?>
    <p class="text-white/65 text-xs mt-0.5 line-clamp-1 hidden sm:block">
      <?= htmlspecialchars($livePost['excerpt']) ?>
    </p>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>
<div id="sliderWrap">
  <div id="sliderInner">
    <?php
    $fallback = [
      ['img'=>'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=2560&h=709&fit=crop','badge'=>'Destaque Institucional','badge_color'=>'#BF8D1A','title'=>'Assembleia Geral Ordinária 2026','desc'=>'Participe das discussões sobre as diretrizes do próximo exercício.','link'=>'#'],
      ['img'=>'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=2560&h=709&fit=crop','badge'=>'Novidade','badge_color'=>'#006633','title'=>'Portal do Profissional 2.0','desc'=>'Acesse sua carteira digital, certidões e histórico profissional.','link'=>'#'],
      ['img'=>'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=2560&h=709&fit=crop','badge'=>'Importante','badge_color'=>'#BF8D1A','title'=>'Programa de Regularização 2026','desc'=>'Aproveite condições especiais para regularização de anuidades.','link'=>'#'],
    ];
    $useDB = !empty($sliders);
    $total_slides = $useDB ? count($sliders) : count($fallback);
    if ($useDB): foreach ($sliders as $i => $sl): ?>
    <?php
    $hasContent = !empty($sl['title']) || !empty($sl['description']) || !empty($sl['link_url']);
    $hasText    = !empty($sl['title']) || !empty($sl['description']);
    ?>
    <a href="<?= htmlspecialchars($sl['link_url'] ?: '#') ?>"
       target="<?= htmlspecialchars($sl['link_target'] ?? '_self') ?>"
       class="slide <?= $i===0?'active':'' ?>"
       style="text-decoration:none;cursor:<?= $sl['link_url']?'pointer':'default' ?>;">
      <img src="<?= htmlspecialchars($sl['image']) ?>"
           alt="<?= htmlspecialchars($sl['title']) ?>"
           class="slide-img">
      <?php if ($hasContent): ?>
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <div class="w-full px-4 md:px-8 lg:px-12">
          <div class="max-w-2xl">
            <?php if (!empty($sl['subtitle'])): ?>
            <span class="inline-block px-3 py-1 mb-2 text-[10px] font-bold text-white rounded-full uppercase tracking-widest"
                  style="background:<?= htmlspecialchars($sl['subtitle_color'] ?? '#BF8D1A') ?>">
              <?= htmlspecialchars($sl['subtitle']) ?>
            </span><br>
            <?php endif; ?>
            <?php if (!empty($sl['title'])): ?>
            <h2 class="inline font-serif text-lg sm:text-2xl md:text-3xl font-bold text-white leading-tight drop-shadow">
              <?= htmlspecialchars($sl['title']) ?>
            </h2>
            <?php endif; ?>
            <?php if (!empty($sl['description'])): ?>
            <p class="text-white/70 text-xs sm:text-sm mt-1 hidden sm:block"><?= htmlspecialchars($sl['description']) ?></p>
            <?php endif; ?>
            <?php if (!empty($sl['link_url']) && !empty($sl['link_text'])): ?>
            <div class="mt-3">
              <span class="inline-block px-5 py-2 bg-[#BF8D1A] text-white text-xs font-bold rounded-lg hover:bg-white hover:text-[#001644] transition">
                <?= htmlspecialchars($sl['link_text']) ?> &rarr;
              </span>
            </div>
            <?php elseif (!empty($sl['link_url'])): ?>
            <div class="mt-3">
              <span class="inline-block px-5 py-2 bg-[#BF8D1A] text-white text-xs font-bold rounded-lg hover:bg-white hover:text-[#001644] transition">
                Saiba mais &rarr;
              </span>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </a>
    <?php endforeach; else: foreach ($fallback as $i => $sl): ?>
    <a href="<?= $sl['link'] ?>"
       class="slide <?= $i===0?'active':'' ?>"
       style="text-decoration:none;">
      <img src="<?= $sl['img'] ?>"
           alt="<?= htmlspecialchars($sl['title']) ?>"
           class="slide-img">
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <div class="w-full px-4 md:px-8 lg:px-12">
          <div class="max-w-2xl">
            <span class="inline-block px-3 py-1 mb-2 text-[10px] font-bold text-white rounded-full uppercase tracking-widest"
                  style="background:<?= $sl['badge_color'] ?>">
              <?= $sl['badge'] ?>
            </span><br>
            <h2 class="inline font-serif text-lg sm:text-2xl md:text-3xl font-bold text-white leading-tight drop-shadow">
              <?= htmlspecialchars($sl['title']) ?>
            </h2>
            <p class="text-white/70 text-xs sm:text-sm mt-1 hidden sm:block"><?= $sl['desc'] ?></p>
            <div class="mt-3">
              <span class="inline-block px-5 py-2 bg-[#BF8D1A] text-white text-xs font-bold rounded-lg">
                Saiba mais &rarr;
              </span>
            </div>
          </div>
        </div>
      </div>
    </a>
    <?php endforeach; endif; ?>
  </div><!-- /sliderInner -->

  <!-- Prev / Next — sobrepostos, 30% transparente -->
  <button class="sl-btn prev" onclick="slPrev()" aria-label="Anterior">
    <i class="fas fa-chevron-left"></i>
  </button>
  <button class="sl-btn next" onclick="slNext()" aria-label="Próximo">
    <i class="fas fa-chevron-right"></i>
  </button>

  <!-- Dots indicadores -->
  <div class="sl-dots">
    <?php for ($i=0;$i<$total_slides;$i++): ?>
    <button class="sl-dot <?= $i===0?'on':'' ?>"
            onclick="slGo(<?= $i ?>)"
            aria-label="Slide <?= $i+1 ?>"></button>
    <?php endfor; ?>
  </div>
</div><!-- /sliderWrap -->
<?php endif; // end live/slider toggle ?>
<!-- ════════════════════════════════════════
     QUICK ACCESS — flutua sobre o slider (normal) ou abaixo do player (live)
════════════════════════════════════════ -->
<section class="relative mb-8 <?= $livePost ? 'mt-3' : 'z-20 -mt-5' ?>">
  <div class="container mx-auto px-4 md:px-6">
    <div class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-5 gap-2 md:gap-3">
      <?php
      $quickLinks = [
        ['fa-file-invoice-dollar', 'Anuidade PF',    'https://web.crcap.org.br/spw/PagamentoAvulso/AnuidadeRegistrado_CRC_PF_2026.htm?_gl=1*pk9coz*_ga*MTE5OTQ4NTQ0Ny4xNzcwMDM4NDk5*_ga_KLY1YSWQ7P*czE3NzI5ODc0OTckbzM2JGcxJHQxNzcyOTg3NDk3JGo2MCRsMCRoMA..&_ga=2.167101566.1908067252.1772941103-1199485447.1770038499'],
        ['fa-building',            'Anuidade PJ',    'https://web.crcap.org.br/spw/PagamentoAvulso/AnuidadeRegistrado_CRC_PJ_2026.htm?_gl=1*cdbg30*_ga*MTE5OTQ4NTQ0Ny4xNzcwMDM4NDk5*_ga_KLY1YSWQ7P*czE3NzI5ODc0OTckbzM2JGcxJHQxNzcyOTg3NDk3JGo2MCRsMCRoMA..&_ga=2.166700542.1908067252.1772941103-1199485447.1770038499'],
        ['fa-globe',               'Serviços Online','https://artemidiaweb.com.br/crcap/pages/servicos-online.php'],
        ['fa-laptop',              'Acesso SEI',     'https://sip.cfc.org.br/sip/login.php?sigla_orgao_sistema=CFC&sigla_sistema=SEI&infra_url=L3NlaS9jb250cm9sYWRvci5waHA/'],
        ['fa-info-circle',         'Transparência',  'https://www3.cfc.org.br/spw/PortalTransparencia/Consulta.aspx?CS=iGmQhbP9t4s='],
      ];
      foreach ($quickLinks as $qi => $ql):
        $isTransp = ($ql[1] === 'Transparência');
      ?>
      <a href="<?= $ql[2] ?>"
         class="ql-card bg-white border border-[#001644]/5 rounded-xl p-3 md:p-4 text-center group shadow-md shadow-[#001644]/5"
         style="text-decoration:none;"
         <?= (strpos($ql[2],'http')===0 && $ql[1]!=='Serviços Online') ? 'target="_blank" rel="noopener"' : '' ?>>
        <div class="w-9 h-9 md:w-11 md:h-11 mx-auto mb-1.5 rounded-lg flex items-center justify-center transition
                    <?= $isTransp
                        ? 'bg-[#F5C300] text-[#006633] group-hover:bg-[#e6b800] group-hover:text-[#004d00] border-2 border-[#e6b800]'
                        : 'bg-[#F8FAFC] text-[#001644] group-hover:bg-[#001644] group-hover:text-white' ?>">
          <i class="fas <?= $ql[0] ?> text-base md:text-lg <?= $isTransp ? 'text-[#006633]' : '' ?>"></i>
        </div>
        <span class="text-[9px] md:text-[10px] font-semibold transition leading-tight block
                     <?= $isTransp ? 'text-[#BF8D1A] group-hover:text-[#e6b800]' : 'text-[#001644] group-hover:text-[#BF8D1A]' ?>">
          <?= $ql[1] ?>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<!-- ════════ CONTEÚDO PRINCIPAL ════════ -->
<main class="container mx-auto px-4 md:px-6 pb-14">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
    <!-- COLUNA 2/3 -->
    <div class="lg:col-span-2 space-y-10">
<!-- ════════ ÚLTIMAS POSTAGENS ════════ -->
<section>
  <div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-[#001644]/5 flex items-center justify-center text-[#001644]">
        <i class="fas fa-newspaper text-sm"></i>
      </div>
      <div>
        <h2 class="text-base font-bold text-[#001644]">Últimas Postagens</h2>
        <p class="text-[10px] text-[#022E6B]">Notícias e comunicados oficiais</p>
      </div>
    </div>
    <a href="/crcap/pages/noticias.php"
       class="text-xs text-[#BF8D1A] font-semibold hover:underline flex items-center gap-1">
      Ver todas <i class="fas fa-arrow-right text-[9px]"></i>
    </a>
  </div>

  <div class="bg-white rounded-2xl border border-[#001644]/4 shadow-sm overflow-hidden">
    <div class="flex flex-col md:flex-row">

      <!-- Post destaque — SLIDESHOW automático (esquerda) -->
      <div class="md:w-[55%] flex-shrink-0 relative" style="min-height:220px;">
        <?php
        $slidePostsSrc = !empty($recentPosts) ? array_slice($recentPosts, 0, 4) : ($featuredPost ? [$featuredPost] : []);
        if (!empty($slidePostsSrc)): ?>
        <div id="featSlider" class="absolute inset-0 overflow-hidden rounded-none">
          <?php foreach ($slidePostsSrc as $fsi => $fsp): ?>
          <a href="/crcap/pages/post.php?slug=<?= urlencode($fsp['slug']) ?>"
             class="feat-slide absolute inset-0 group"
             style="text-decoration:none;opacity:<?= $fsi===0?'1':'0' ?>;transition:opacity .7s ease;pointer-events:<?= $fsi===0?'auto':'none' ?>;">
            <img src="<?= htmlspecialchars($fsp['featured_image'] ?: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=800&h=400&fit=crop') ?>"
                 alt="<?= htmlspecialchars($fsp['title']) ?>"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-700"
                 style="min-height:220px;">
            <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,22,68,.95) 0%,rgba(0,22,68,.2) 65%,transparent 100%)"></div>
            <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
              <div class="flex items-center gap-2 mb-1.5">
                <span class="px-2 py-0.5 text-[9px] font-bold rounded-full"
                      style="background:<?= htmlspecialchars($fsp['cat_color'] ?? '#BF8D1A') ?>">
                  <?= htmlspecialchars($fsp['cat_name'] ?? 'Destaque') ?>
                </span>
                <span class="text-[9px] text-white/60">
                  <?= date('d M Y', strtotime($fsp['published_at'])) ?>
                </span>
              </div>
              <h3 class="font-serif text-base md:text-lg font-bold leading-snug mb-1">
                <?= htmlspecialchars($fsp['title']) ?>
              </h3>
              <?php if (!empty($fsp['excerpt'])): ?>
              <p class="text-[10px] text-white/70 line-clamp-2 hidden md:block">
                <?= htmlspecialchars($fsp['excerpt']) ?>
              </p>
              <?php endif; ?>
            </div>
          </a>
          <?php endforeach; ?>
          <!-- Dots -->
          <div class="absolute bottom-2 right-3 flex gap-1 z-10">
            <?php foreach ($slidePostsSrc as $fsi => $fsp): ?>
            <span class="feat-dot block rounded-full transition-all"
                  style="width:<?= $fsi===0?'1.4rem':'.4rem' ?>;height:.22rem;background:<?= $fsi===0?'rgba(255,255,255,.9)':'rgba(255,255,255,.35)' ?>;"></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="h-full bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center" style="min-height:220px;">
          <div class="text-center text-white/30">
            <i class="fas fa-newspaper text-4xl mb-2 block"></i>
            <p class="text-xs">Nenhum post em destaque</p>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Posts recentes — miniaturas fixas (direita) -->
      <div id="sse-lista-posts" class="flex-1 flex flex-col p-3 gap-2">
        <?php foreach (array_slice($recentPosts, 0, 4) as $p): ?>
        <a href="/crcap/pages/post.php?slug=<?= urlencode($p['slug']) ?>"
           class="post-row flex gap-3 p-2.5 rounded-xl bg-[#F8FAFC] hover:bg-white border border-transparent hover:border-[#BF8D1A]/25 hover:shadow-sm group"
           data-post-id="<?= (int)$p['id'] ?>"
           style="text-decoration:none;">
          <img src="<?= htmlspecialchars($p['featured_image'] ?: 'https://images.unsplash.com/photo-1450101499163-c627a92ad1ab?w=100&h=100&fit=crop') ?>"
               alt="" class="w-12 h-12 rounded-lg object-cover flex-shrink-0">
          <div class="min-w-0">
            <div class="flex items-center gap-1.5 mb-0.5">
              <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-[#001644]/8 text-[#001644] truncate max-w-[80px]">
                <?= htmlspecialchars($p['cat_name'] ?? 'Geral') ?>
              </span>
              <span class="text-[9px] text-[#022E6B]/55 flex-shrink-0">
                <?= date('d/m', strtotime($p['published_at'])) ?>
              </span>
            </div>
            <h4 class="text-xs font-semibold text-[#001644] line-clamp-2 group-hover:text-[#BF8D1A] transition leading-snug">
              <?= htmlspecialchars($p['title']) ?>
            </h4>
          </div>
        </a>
        <?php endforeach; ?>
        <?php if (empty($recentPosts)): ?>
        <div class="flex-1 flex items-center justify-center text-[#001644]/30 text-xs">
          <i class="fas fa-inbox mr-2"></i>Sem postagens
        </div>
        <?php endif; ?>
        <a href="/crcap/pages/noticias.php"
           class="mt-auto flex items-center justify-center gap-2 py-2.5 text-[10px] font-bold text-[#001644] bg-[#F8FAFC] hover:bg-[#001644] hover:text-white rounded-xl transition group"
           style="text-decoration:none;">
          Ver mais postagens
          <i class="fas fa-arrow-right text-[9px] group-hover:translate-x-1 transition"></i>
        </a>
      </div>

    </div>
  </div>
</section>
<!-- ════════ PRÓXIMOS EVENTOS ════════ -->
<section>
  <div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-[#001644]/5 flex items-center justify-center text-[#001644]">
        <i class="fas fa-calendar-alt text-sm"></i>
      </div>
      <div>
        <h2 class="text-base font-bold text-[#001644]">Próximos Eventos</h2>
        <p class="text-[10px] text-[#022E6B]">Agenda de cursos, palestras e encontros</p>
      </div>
    </div>
    <a href="/crcap/pages/eventos.php"
       class="text-xs text-[#BF8D1A] font-semibold hover:underline flex items-center gap-1">
      Ver todos <i class="fas fa-arrow-right text-[9px]"></i>
    </a>
  </div>

  <?php if (!empty($events)): ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <?php foreach (array_slice($events, 0, 4) as $ev):
      $evDate = new DateTime($ev['event_date']); ?>
    <div class="ev-card bg-white rounded-2xl overflow-hidden border border-[#001644]/4 shadow-sm">
      <div class="relative h-40 overflow-hidden">
        <img src="<?= htmlspecialchars($ev['featured_image'] ?: 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600&h=300&fit=crop') ?>"
             alt="<?= htmlspecialchars($ev['title']) ?>"
             class="w-full h-full object-cover">
        <div class="absolute top-2.5 left-2.5 bg-white/95 backdrop-blur-sm px-2.5 py-1.5 rounded-lg text-center shadow">
          <span class="block text-sm font-bold text-[#001644] leading-none"><?= $evDate->format('d') ?></span>
          <span class="text-[9px] text-[#BF8D1A] font-bold uppercase"><?= $evDate->format('M') ?></span>
        </div>
        <?php if ($ev['is_free']): ?>
        <span class="absolute top-2.5 right-2.5 bg-[#006633] text-white text-[9px] font-bold px-2 py-0.5 rounded-full">GRATUITO</span>
        <?php endif; ?>
      </div>
      <div class="p-4">
        <h3 class="font-semibold text-[#001644] text-xs mb-2 line-clamp-2 leading-snug">
          <?= htmlspecialchars($ev['title']) ?>
        </h3>
        <div class="space-y-1 mb-3">
          <div class="flex items-center gap-1.5 text-[10px] text-[#022E6B]">
            <i class="fas fa-clock text-[#BF8D1A] w-3"></i>
            <span><?= substr($ev['start_time'],0,5) ?><?= $ev['end_time']?' – '.substr($ev['end_time'],0,5):'' ?></span>
          </div>
          <?php if ($ev['location']): ?>
          <div class="flex items-center gap-1.5 text-[10px] text-[#022E6B]">
            <i class="fas fa-map-marker-alt text-[#BF8D1A] w-3"></i>
            <span class="truncate"><?= htmlspecialchars($ev['location']) ?></span>
          </div>
          <?php endif; ?>
        </div>
        <a href="/crcap/pages/evento.php?slug=<?= urlencode($ev['slug']) ?>"
           class="block w-full py-2 text-[10px] font-bold text-center text-[#001644] border border-[#001644]/20 rounded-xl hover:bg-[#001644] hover:text-white transition"
           style="text-decoration:none;">
          <?= $ev['registration_required'] ? 'Inscrever-se' : 'Ver detalhes' ?>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="bg-white rounded-2xl p-10 text-center border border-[#001644]/4">
    <i class="fas fa-calendar-times text-3xl text-[#001644]/20 mb-2 block"></i>
    <p class="text-sm text-[#001644]/40">Nenhum evento próximo agendado</p>
  </div>
  <?php endif; ?>
</section>
<!-- ════════ COMISSÕES ════════ -->
<section>
  <div class="flex items-center gap-3 mb-4">
    <div class="w-9 h-9 rounded-xl bg-[#001644]/5 flex items-center justify-center text-[#001644]">
      <i class="fas fa-users text-sm"></i>
    </div>
    <div>
      <h2 class="text-base font-bold text-[#001644]">Comissões em Destaque</h2>
      <p class="text-[10px] text-[#022E6B]">Grupos de trabalho e iniciativas</p>
    </div>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <?php if (!empty($commissions)): foreach ($commissions as $c): ?>
    <a href="<?= htmlspecialchars($c['link_url'] ?? '#') ?>"
       class="comm-card block bg-white rounded-2xl p-5 border border-[#001644]/4 shadow-sm relative overflow-hidden group"
       style="text-decoration:none;">
      <div class="absolute top-0 left-0 right-0 h-0.5 opacity-0 group-hover:opacity-100 transition"
           style="background:linear-gradient(90deg,<?= htmlspecialchars($c['gradient_from'] ?? '#001644') ?>,<?= htmlspecialchars($c['gradient_to'] ?? '#022E6B') ?>)"></div>
      <div class="flex items-start justify-between mb-3">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow"
             style="background:linear-gradient(135deg,<?= htmlspecialchars($c['gradient_from'] ?? '#001644') ?>,<?= htmlspecialchars($c['gradient_to'] ?? '#022E6B') ?>)">
          <i class="fas <?= htmlspecialchars($c['icon'] ?? 'fa-users') ?>"></i>
        </div>
        <?php if (!empty($c['badge_text'])): ?>
        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full"
              style="color:<?= htmlspecialchars($c['badge_color'] ?? '#BF8D1A') ?>;background:<?= htmlspecialchars($c['badge_color'] ?? '#BF8D1A') ?>18">
          <?= htmlspecialchars($c['badge_text']) ?>
        </span>
        <?php endif; ?>
      </div>
      <h3 class="text-sm font-bold text-[#001644] mb-1.5 group-hover:text-[#BF8D1A] transition">
        <?= htmlspecialchars($c['title']) ?>
      </h3>
      <p class="text-[10px] text-[#022E6B] leading-relaxed line-clamp-3">
        <?= htmlspecialchars($c['description']) ?>
      </p>
    </a>
    <?php endforeach; else:
    $fc = [
      ['fa-users','CRCAP Jovem','Espaço dedicado aos jovens profissionais com mentorias e networking.','#001644','#022E6B','Nova','#BF8D1A','#'],
      ['fa-hands-helping','Voluntariado','Transforme vidas por meio do conhecimento em projetos sociais.','#006633','#022E6B','Voluntário','#006633','#'],
      ['fa-venus','Mulher Contabilista','Valorização e desenvolvimento da mulher na contabilidade.','#BF8D1A','#022E6B','Destaque','#BF8D1A','#'],
    ];
    foreach ($fc as $c): ?>
    <a href="<?= $c[7] ?>"
       class="comm-card block bg-white rounded-2xl p-5 border border-[#001644]/4 shadow-sm relative overflow-hidden group"
       style="text-decoration:none;">
      <div class="absolute top-0 left-0 right-0 h-0.5 opacity-0 group-hover:opacity-100 transition"
           style="background:linear-gradient(90deg,<?= $c[3] ?>,<?= $c[4] ?>)"></div>
      <div class="flex items-start justify-between mb-3">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow"
             style="background:linear-gradient(135deg,<?= $c[3] ?>,<?= $c[4] ?>)">
          <i class="fas <?= $c[0] ?>"></i>
        </div>
        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full"
              style="color:<?= $c[6] ?>;background:<?= $c[6] ?>18"><?= $c[5] ?></span>
      </div>
      <h3 class="text-sm font-bold text-[#001644] mb-1.5 group-hover:text-[#BF8D1A] transition"><?= $c[1] ?></h3>
      <p class="text-[10px] text-[#022E6B] leading-relaxed"><?= $c[2] ?></p>
    </a>
    <?php endforeach; endif; ?>
  </div>
</section>
    </div><!-- /col 2/3 -->
<!-- ════════ SIDEBAR ════════ -->
<aside class="space-y-5">

  <!-- AGENDA DO PRESIDENTE -->
  <div class="bg-white rounded-2xl p-5 border border-[#001644]/4 shadow-sm">
    <div class="flex items-center gap-2.5 mb-4">
      <div class="w-9 h-9 rounded-xl bg-[#001644]/5 flex items-center justify-center text-[#001644]">
        <i class="fas fa-calendar-alt text-sm"></i>
      </div>
      <div>
        <h3 class="text-sm font-bold text-[#001644]">Agenda do Presidente</h3>
        <p class="text-[10px] text-[#022E6B]">Compromissos oficiais</p>
      </div>
    </div>

    <div id="sse-lista-agenda" class="relative pl-5 border-l-2 border-[#001644]/10 space-y-3">
      <?php
      if (empty($schedule)):  ?>
      <div class="flex flex-col items-center justify-center py-8 text-center">
        <div class="w-12 h-12 rounded-xl bg-[#001644]/5 flex items-center justify-center text-[#001644]/30 mb-3">
          <i class="fas fa-calendar-check text-xl"></i>
        </div>
        <p class="text-xs font-semibold text-[#001644]/40">Nenhum compromisso agendado</p>
        <p class="text-[10px] text-[#001644]/25 mt-1">Agenda vazia para os próximos dias</p>
      </div>
      <?php else: foreach ($schedule as $ev):
        $evDate  = new DateTime($ev['event_date']);
        $isToday = $evDate->format('Y-m-d') === date('Y-m-d');
        $imp     = in_array($ev['priority'] ?? '', ['high','urgent']);
      ?>
      <a href="/crcap/pages/agenda.php"
         class="tl-item block relative group"
         data-agenda-id="<?= (int)$ev['id'] ?>"
         style="text-decoration:none;">
        <div class="absolute -left-[1.4rem] w-2.5 h-2.5 rounded-full border-2 top-3 transition
                    <?= $imp ? 'bg-[#BF8D1A] border-[#BF8D1A]' : 'bg-white border-[#001644] group-hover:bg-[#BF8D1A] group-hover:border-[#BF8D1A]' ?>"></div>
        <div class="tl-card bg-[#F8FAFC] rounded-xl overflow-hidden">
          <div class="flex items-stretch">
            <?php if (!empty($ev['image'])): ?>
            <img src="<?= htmlspecialchars($ev['image']) ?>"
                 style="width:126px;min-width:126px;height:100%;min-height:90px;"
                 class="object-cover flex-shrink-0 rounded-l-xl">
            <?php else: ?>
            <div style="width:126px;min-width:126px;min-height:90px;"
                 class="bg-gradient-to-br from-[#001644] to-[#022E6B] flex items-center justify-center text-white text-2xl flex-shrink-0 rounded-l-xl">
              <i class="fas fa-calendar-check"></i>
            </div>
            <?php endif; ?>
            <div class="min-w-0 flex-1 p-2.5 flex flex-col justify-center">
              <div class="flex items-center justify-between mb-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider
                             <?= $imp ? 'text-[#BF8D1A]' : 'text-[#022E6B]' ?>">
                  <?= $isToday ? 'Hoje' : $evDate->format('d M') ?>, <?= substr($ev['start_time'],0,5) ?>
                </span>
                <?php if (($ev['status'] ?? '') === 'cancelled'): ?>
                <span title="Cancelado"
                      class="flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-bold uppercase tracking-wide"
                      style="background:#fee2e2;color:#dc2626;">
                  <i class="fas fa-ban" style="font-size:8px;"></i> Cancelado
                </span>
                <?php elseif (($ev['status'] ?? '') === 'confirmed'): ?>
                <span title="Confirmado"
                      class="flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[8px] font-bold uppercase tracking-wide"
                      style="background:#dcfce7;color:#16a34a;">
                  <i class="fas fa-check-circle" style="font-size:8px;"></i> Confirmado
                </span>
                <?php endif; ?>
              </div>
              <h4 class="text-xs font-semibold line-clamp-2 leading-snug
                         <?= ($ev['status'] ?? '') === 'cancelled' ? 'text-[#001644]/50 line-through decoration-red-400' : 'text-[#001644]' ?>">
                <?= htmlspecialchars($ev['title']) ?>
              </h4>
              <?php if (!empty($ev['location'])): ?>
              <p class="text-[9px] text-[#022E6B]/65 mt-1 truncate">
                <i class="fas fa-map-marker-alt text-[#BF8D1A] mr-1 text-[8px]"></i>
                <?= htmlspecialchars($ev['location']) ?>
              </p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </a>
      <?php endforeach; endif; ?>
    </div>

    <a href="/crcap/pages/agenda.php"
       class="block w-full mt-4 py-2.5 text-[10px] font-bold text-center text-[#001644] border border-[#001644]/15 rounded-xl hover:bg-[#001644] hover:text-white transition"
       style="text-decoration:none;">
      Ver agenda completa
    </a>
  </div>

  <!-- NEWSLETTER -->
  <div class="bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-5 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-28 h-28 bg-white/5 rounded-full -mr-14 -mt-14 blur-2xl"></div>
    <div class="absolute bottom-0 left-0 w-20 h-20 bg-[#BF8D1A]/15 rounded-full -ml-10 -mb-10 blur-xl"></div>
    <div class="relative z-10">
      <div class="w-10 h-10 rounded-xl bg-[#BF8D1A]/20 flex items-center justify-center mb-3">
        <i class="fas fa-envelope"></i>
      </div>
      <h3 class="font-serif text-base font-bold mb-1">Newsletter</h3>
      <p class="text-[10px] text-white/75 mb-4">Receba notícias e comunicados oficiais no seu e-mail.</p>
      <?php if ($newsletterMsg === 'success'): ?>
      <div class="bg-[#006633]/30 border border-[#006633]/50 rounded-xl p-3 text-center">
        <i class="fas fa-check-circle text-green-400 text-lg mb-1 block"></i>
        <p class="text-xs font-semibold">Inscrição realizada!</p>
      </div>
      <?php else: ?>
      <form method="POST" class="space-y-2">
        <?php if (function_exists('csrfField')) echo csrfField(); ?>
        <?php if ($newsletterMsg === 'error'): ?>
        <p class="text-red-300 text-[10px]">E-mail inválido ou já cadastrado.</p>
        <?php endif; ?>
        <!-- Nome -->
        <input type="text" name="newsletter_name" placeholder="Seu nome (opcional)"
               class="w-full px-3 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/45 focus:outline-none focus:border-[#BF8D1A] text-xs">
        <!-- E-mail -->
        <input type="email" name="newsletter_email" placeholder="Seu melhor e-mail" required
               class="w-full px-3 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-white/45 focus:outline-none focus:border-[#BF8D1A] text-xs">
        <!-- Profissão -->
        <select name="newsletter_profession"
                class="w-full px-3 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white focus:outline-none focus:border-[#BF8D1A] text-xs">
          <option value="" class="text-[#001644]">Sou... (opcional)</option>
          <option value="contador" class="text-[#001644]">Contador</option>
          <option value="tecnico" class="text-[#001644]">Técnico em Contabilidade</option>
          <option value="estudante" class="text-[#001644]">Estudante</option>
          <option value="outro" class="text-[#001644]">Outro</option>
        </select>
        <button type="submit"
                class="w-full py-2.5 bg-[#BF8D1A] text-white font-bold rounded-xl hover:bg-white hover:text-[#001644] transition text-xs">
          Inscrever-se grátis
        </button>
      </form>
      <?php endif; ?>
      <p class="text-[9px] text-white/35 mt-2 text-center">Política de privacidade aplicável.</p>
    </div>
  </div>

  <!-- QUICK GRID (estatuto, regimento…) -->
  <div class="grid grid-cols-2 gap-2.5">
    <?php
    $qlSide = !empty($sidebarLinks) ? $sidebarLinks : [
      ['url'=>'#','icon'=>'fa-file-pdf',       'title'=>'Estatuto'],
      ['url'=>'#','icon'=>'fa-file-alt',        'title'=>'Regimento'],
      ['url'=>'#','icon'=>'fa-phone',            'title'=>'Fale Conosco'],
      ['url'=>'#','icon'=>'fa-question-circle',  'title'=>'FAQ'],
    ];
    foreach ($qlSide as $q): ?>
    <a href="<?= htmlspecialchars($q['url'] ?? '#') ?>"
       class="bg-white border border-[#001644]/5 hover:border-[#BF8D1A] rounded-xl p-3.5 flex flex-col items-center gap-2 hover:-translate-y-1 hover:shadow-lg transition group"
       style="text-decoration:none;">
      <div class="w-9 h-9 rounded-xl bg-[#F8FAFC] flex items-center justify-center text-[#001644] group-hover:bg-[#BF8D1A] group-hover:text-white transition">
        <i class="fas <?= htmlspecialchars($q['icon'] ?? 'fa-link') ?>"></i>
      </div>
      <span class="text-[9px] font-semibold text-[#001644] text-center leading-tight">
        <?= htmlspecialchars($q['title'] ?? '') ?>
      </span>
    </a>
    <?php endforeach; ?>
  </div>

</aside>
  </div><!-- /grid -->
</main>
<script>
(function () {
  const wrap   = document.getElementById('sliderWrap');
  if (!wrap) return;
  const slides = wrap.querySelectorAll('.slide');
  const dots   = wrap.querySelectorAll('.sl-dot');
  const inner  = document.getElementById('sliderInner');
  const n      = slides.length;
  if (!n) return;
  let cur = 0;

  // Sincroniza a altura do wrapper com a primeira imagem (aspect-ratio)
  function syncHeight() {
    const img = slides[0] && slides[0].querySelector('.slide-img');
    if (img && img.naturalHeight) {
      inner.style.height = img.offsetHeight + 'px';
    }
  }
  window.addEventListener('resize', syncHeight);
  const firstImg = slides[0] && slides[0].querySelector('.slide-img');
  if (firstImg) {
    firstImg.addEventListener('load', syncHeight);
    syncHeight();
  }

  function go(i) {
    slides[cur].classList.remove('active');
    if (dots[cur]) dots[cur].classList.remove('on');
    cur = ((i % n) + n) % n;
    slides[cur].classList.add('active');
    if (dots[cur]) dots[cur].classList.add('on');
  }

  window.slGo   = i  => { go(i);     reset(); };
  window.slNext = () => { go(cur+1); reset(); };
  window.slPrev = () => { go(cur-1); reset(); };

  let t = setInterval(() => go(cur + 1), 6000);
  function reset() { clearInterval(t); t = setInterval(() => go(cur + 1), 6000); }

  // Swipe touch
  let sx = 0;
  wrap.addEventListener('touchstart', e => { sx = e.changedTouches[0].screenX; }, { passive: true });
  wrap.addEventListener('touchend',   e => {
    const dx = e.changedTouches[0].screenX - sx;
    if (dx < -50) window.slNext();
    if (dx >  50) window.slPrev();
  });
})();

// ── Featured post slideshow ──────────────────────
(function () {
  const wrap   = document.getElementById('featSlider');
  if (!wrap) return;
  const slides = wrap.querySelectorAll('.feat-slide');
  const dots   = wrap.querySelectorAll('.feat-dot');
  const n = slides.length;
  if (n < 2) return;
  let cur = 0;

  function go(i) {
    slides[cur].style.opacity = '0';
    slides[cur].style.pointerEvents = 'none';
    dots[cur] && (dots[cur].style.width = '.4rem', dots[cur].style.background = 'rgba(255,255,255,.35)');
    cur = (i + n) % n;
    slides[cur].style.opacity = '1';
    slides[cur].style.pointerEvents = 'auto';
    dots[cur] && (dots[cur].style.width = '1.4rem', dots[cur].style.background = 'rgba(255,255,255,.9)');
  }

  setInterval(() => go(cur + 1), 5000);
})();
</script>

<script>
// ── Service Worker + Push (Live CRCAP) ───────────────
(function () {
  if (!('serviceWorker' in navigator)) return;

  navigator.serviceWorker.register('/crcap/sw-live.js', { scope: '/crcap/' })
    .then(reg => {
      console.log('[CRCAP Live] SW registrado:', reg.scope);

      // Solicitar permissão de notificação
      if (Notification.permission === 'default') {
        // Mostra prompt suave após 3s
        setTimeout(() => {
          if (document.getElementById('pushPrompt')) return;
          const bar = document.createElement('div');
          bar.id = 'pushPrompt';
          bar.style.cssText = 'position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);z-index:9999;' +
            'background:#001644;color:#fff;border-radius:1rem;padding:.85rem 1.25rem;' +
            'display:flex;align-items:center;gap:.75rem;font-size:.75rem;box-shadow:0 8px 24px rgba(0,22,68,.35);' +
            'max-width:calc(100vw - 2rem);';
          bar.innerHTML = `
            <i class="fas fa-bell" style="color:#BF8D1A;font-size:1rem;flex-shrink:0;"></i>
            <span>Receber alertas de transmissão ao vivo?</span>
            <button onclick="enablePush()" style="background:#BF8D1A;border:none;color:#fff;
              padding:.35rem .9rem;border-radius:.5rem;font-weight:700;cursor:pointer;font-size:.7rem;flex-shrink:0;">
              Ativar
            </button>
            <button onclick="this.closest('#pushPrompt').remove()" style="background:transparent;border:none;
              color:rgba(255,255,255,.5);cursor:pointer;font-size:1rem;line-height:1;padding:0 .2rem;">
              ✕
            </button>`;
          document.body.appendChild(bar);
        }, 3000);
      }
    })
    .catch(e => console.warn('[CRCAP Live] SW erro:', e));
})();

async function enablePush() {
  const bar = document.getElementById('pushPrompt');
  try {
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') { bar?.remove(); return; }
    const reg  = await navigator.serviceWorker.ready;
    // VAPID public key placeholder — substitua pela sua chave VAPID pública
    // Gere em: https://web-push-codelab.glitch.me/
    const VAPID_PUBLIC = 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjZEetF3YRu58ny3v7CuB01A8B6os';
    let sub;
    try {
      sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC)
      });
    } catch(e) {
      // Fallback sem VAPID (alguns navegadores)
      sub = await reg.pushManager.subscribe({ userVisibleOnly: true });
    }
    await fetch('/crcap/api/subscribe-live.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(sub.toJSON())
    });
    bar?.remove();
    // Mostra confirmação
    const ok = document.createElement('div');
    ok.style.cssText = 'position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);z-index:9999;' +
      'background:#006633;color:#fff;border-radius:1rem;padding:.7rem 1.25rem;font-size:.75rem;' +
      'box-shadow:0 8px 24px rgba(0,102,51,.3);';
    ok.innerHTML = '✅ Notificações ativadas! Você será avisado quando houver live.';
    document.body.appendChild(ok);
    setTimeout(() => ok.remove(), 4000);
  } catch(e) {
    console.error('Push subscription error:', e);
    bar?.remove();
  }
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = window.atob(base64);
  return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

// ── Polling: verifica live a cada 60s ───────────────
<?php if (!$livePost): ?>
setInterval(async () => {
  try {
    const r = await fetch('/crcap/api/check-live.php');
    const d = await r.json();
    if (d.live) location.reload(); // live iniciou: recarrega para mostrar player
  } catch(e) {}
}, 60000);
<?php endif; ?>
</script>

<?php
// ── SSE: IDs já carregados nesta página ──────────────────
$maxPostId   = !empty($recentPosts) ? max(array_column($recentPosts, 'id')) : 0;
$maxAgendaId = !empty($schedule)    ? max(array_column($schedule,    'id')) : 0;
?>

<!-- ── SSE: Atualização em tempo real (inclui detecção de deleção) ── -->
<style>
@keyframes sse-fadeIn {
  from { opacity:0; transform:translateY(-8px); }
  to   { opacity:1; transform:translateY(0); }
}
@keyframes sse-fadeOut {
  from { opacity:1; transform:translateX(0); max-height:80px; margin-bottom:0; }
  to   { opacity:0; transform:translateX(20px); max-height:0;  margin-bottom:-8px; }
}
.post-novo   { animation: sse-fadeIn  .5s ease forwards; }
.agenda-novo { animation: sse-fadeIn  .5s ease forwards; }
.sse-removendo { animation: sse-fadeOut .4s ease forwards; pointer-events:none; overflow:hidden; }

#sse-toast {
  position:fixed;top:4.5rem;left:50%;transform:translateX(-50%);
  z-index:9999;
  background:#001644;color:#fff;
  border-radius:9999px;
  padding:.5rem 1.2rem;
  font-size:.72rem;font-weight:700;
  box-shadow:0 8px 24px rgba(0,22,68,.4);
  display:flex;align-items:center;gap:.5rem;
  opacity:0;pointer-events:none;
  transition:opacity .3s;white-space:nowrap;
}
#sse-toast.show { opacity:1; }
</style>

<div id="sse-toast">
  <span id="sse-toast-dot" style="color:#BF8D1A;">&#9679;</span>
  <span id="sse-toast-msg">Conteúdo atualizado</span>
</div>

<script>
(function () {
  var ultimoPostId   = <?= (int)$maxPostId ?>;
  var ultimoAgendaId = <?= (int)$maxAgendaId ?>;

  var listaPostsEl  = document.getElementById('sse-lista-posts');
  var listaAgendaEl = document.getElementById('sse-lista-agenda');
  var toast         = document.getElementById('sse-toast');
  var toastDot      = document.getElementById('sse-toast-dot');
  var toastMsg      = document.getElementById('sse-toast-msg');
  var toastTimer    = null;

  function mostrarToast(msg, cor) {
    toastMsg.textContent = msg;
    toastDot.style.color = cor || '#BF8D1A';
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function() { toast.classList.remove('show'); }, 4500);
  }

  // Remove elemento com animação de saída
  function removerEl(el) {
    el.classList.add('sse-removendo');
    setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 420);
  }

  // ── Sincroniza posts: remove do DOM os que não estão mais publicados ──
  function sincronizarPosts(idsAtivos) {
    if (!listaPostsEl) return;
    var cards = listaPostsEl.querySelectorAll('[data-post-id]');
    var removidos = 0;
    cards.forEach(function(card) {
      var id = parseInt(card.getAttribute('data-post-id'), 10);
      if (idsAtivos.indexOf(id) === -1) {
        removerEl(card);
        removidos++;
      }
    });
    if (removidos > 0) {
      mostrarToast('🗑️ Postagem removida', '#DC2626');
    }
  }

  // ── Sincroniza agenda: remove do DOM os que foram deletados ──
  function sincronizarAgenda(idsAtivos) {
    if (!listaAgendaEl) return;
    var itens = listaAgendaEl.querySelectorAll('[data-agenda-id]');
    var removidos = 0;
    itens.forEach(function(item) {
      var id = parseInt(item.getAttribute('data-agenda-id'), 10);
      if (idsAtivos.indexOf(id) === -1) {
        removerEl(item);
        removidos++;
      }
    });
    if (removidos > 0) {
      mostrarToast('🗑️ Agenda atualizada', '#DC2626');
    }
  }

  function conectar() {
    var url = '/crcap/sse-stream.php?ultimo_post_id=' + ultimoPostId + '&ultimo_agenda_id=' + ultimoAgendaId;
    var es  = new EventSource(url);

    // Evento: nova postagem ou agenda
    es.addEventListener('novidade', function(e) {
      try {
        var d = JSON.parse(e.data);

        if (d.tipo === 'post' && listaPostsEl) {
          d.html.slice().reverse().forEach(function(html) {
            listaPostsEl.insertAdjacentHTML('afterbegin', html);
          });
          // Mantém máximo de 4 cards
          var itens = listaPostsEl.querySelectorAll('a.post-row');
          for (var i = 4; i < itens.length; i++) itens[i].remove();
          ultimoPostId = d.novo_max;
          mostrarToast('📰 Nova postagem publicada!', '#BF8D1A');
        }

        if (d.tipo === 'agenda' && listaAgendaEl) {
          d.html.slice().reverse().forEach(function(html) {
            listaAgendaEl.insertAdjacentHTML('afterbegin', html);
          });
          var itensAg = listaAgendaEl.querySelectorAll('.tl-item');
          for (var j = 4; j < itensAg.length; j++) itensAg[j].remove();
          ultimoAgendaId = d.novo_max;
          mostrarToast('📅 Agenda atualizada!', '#BF8D1A');
        }

      } catch(err) { console.warn('[SSE] novidade parse error', err); }
    });

    // Evento: sync periódico — detecta deleções/despublicações
    es.addEventListener('sync', function(e) {
      try {
        var d = JSON.parse(e.data);
        if (d.post_ids)   sincronizarPosts(d.post_ids);
        if (d.agenda_ids) sincronizarAgenda(d.agenda_ids);
      } catch(err) { console.warn('[SSE] sync parse error', err); }
    });

    // Servidor pede reconexão após 4 min
    es.addEventListener('reconectar', function() {
      es.close();
      conectar();
    });

    es.onerror = function() {
      es.close();
      setTimeout(conectar, 30000);
    };
  }

  conectar();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>