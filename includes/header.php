<?php
// includes/header.php — MENU 100% DINÂMICO via menu_items do BD
$pageTitle  = $pageTitle  ?? 'CRCAP · Conselho Regional';
$activeMenu = $activeMenu ?? '';

// ── Configurações do site ──────────────────────────────────────────────────
$siteSettings = []; $siteName = 'CRCAP'; $siteDescription = ''; $siteLogo = ''; $siteFavicon = '';
if (isset($pdo)) {
    foreach (dbFetchAll($pdo, "SELECT setting_key, setting_value FROM settings") as $c)
        $siteSettings[$c['setting_key']] = $c['setting_value'];
    $siteName        = $siteSettings['site_name']        ?? 'CRCAP';
    $siteDescription = $siteSettings['site_description'] ?? 'Conselho Regional de Contabilidade do Amapá';
    $siteLogo        = $siteSettings['site_logo']        ?? '';
    $siteFavicon     = $siteSettings['site_favicon']     ?? '';
}

// ── Carregar menu do BD e montar árvore ───────────────────────────────────
$menuRoots = []; $menuChildren = [];
if (isset($pdo)) {
    $allItems = dbFetchAll($pdo,
        "SELECT * FROM menu_items WHERE menu_location='main' AND status='active'
         ORDER BY order_position ASC, id ASC");
    foreach ($allItems as $item) {
        if ($item['parent_id'] === null) $menuRoots[] = $item;
        else $menuChildren[(int)$item['parent_id']][] = $item;
    }
}

// ── Colunas do mega menu Governança ───────────────────────────────────────
$megaColTitles = [
    'col-1' => ['label'=>'Transparência',  'icon'=>'fa-shield-alt'],
    'col-2' => ['label'=>'Planejamento',   'icon'=>'fa-calendar-check'],
    'col-3' => ['label'=>'Compliance',     'icon'=>'fa-gavel'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($siteDescription) ?>">
    <?php if ($siteFavicon): ?>
    <link rel="icon" href="<?= h($siteFavicon) ?>" type="image/<?= str_ends_with($siteFavicon,'.ico')?'x-icon':'png' ?>">
    <?php else: ?>
    <link rel="icon" href="/crcap/favicon.ico" type="image/x-icon">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: { primary:{DEFAULT:'#001644',light:'#022E6B'}, accent:{gold:'#BF8D1A',green:'#006633'}, surface:'#F8FAFC' },
                fontFamily: { sans:['Sora','sans-serif'], serif:['DM Serif Display','serif'] }
            }}
        }
    </script>
    <style>
        *{font-family:'Sora',sans-serif;}
        .font-serif{font-family:'DM Serif Display',serif;}
        body{background:#F8FAFC;color:#001644;}
        ::-webkit-scrollbar{width:4px;height:4px;}
        ::-webkit-scrollbar-track{background:#F8FAFC;}
        ::-webkit-scrollbar-thumb{background:#00164440;border-radius:2px;}
        .glass{background:rgba(255,255,255,0.97);backdrop-filter:blur(12px);border:1px solid rgba(0,22,68,0.06);}
        .hover-lift{transition:all .3s cubic-bezier(.4,0,.2,1);}
        .hover-lift:hover{transform:translateY(-4px);box-shadow:0 20px 25px -5px rgba(0,22,68,.10);}

        /* ── Dropdown ─────────────────────────────── */
        .nav-dd-menu{
            opacity:0;visibility:hidden;transform:translateY(-6px);
            transition:opacity .18s ease,transform .18s ease,visibility .18s;
            box-shadow:0 20px 30px -10px rgba(0,22,68,.18);
            border:1px solid rgba(191,141,26,.12);
        }
        .nav-dd:hover .nav-dd-menu{ opacity:1;visibility:visible;transform:translateY(0); }
        .nav-dd-btn::after{
            content:'';position:absolute;bottom:-2px;left:50%;transform:translateX(-50%);
            width:0;height:2px;background:#BF8D1A;transition:width .3s;
        }
        .nav-dd-btn{position:relative;}
        .nav-dd:hover .nav-dd-btn::after{width:80%;}

        /* ── Mobile ───────────────────────────────── */
        .mob-sub{max-height:0;overflow:hidden;transition:max-height .3s ease-out;}
        .mob-sub.open{max-height:3000px;}
        .mob-toggle i.chevron{transition:transform .3s;}
        .mob-toggle.open i.chevron{transform:rotate(180deg);}

        /* ── Pulse ────────────────────────────────── */
        @keyframes pulse-ring{0%{transform:scale(.8);opacity:.5;}100%{transform:scale(2);opacity:0;}}
        .pulse-dot::before{content:'';position:absolute;inset:-4px;border-radius:50%;
            background:#006633;animation:pulse-ring 2s cubic-bezier(.215,.61,.355,1) infinite;}
    </style>
</head>
<body class="bg-surface antialiased">

<!-- ── Top Bar ────────────────────────────────────────────────────────── -->
<div class="bg-[#001644] text-white border-b border-white/5">
    <div class="container mx-auto px-4 py-2 flex justify-between items-center">
        <div class="flex items-center gap-5">
            <a href="/crcap/index.php" class="flex items-center gap-1.5 text-white/70 hover:text-white text-[10px] uppercase tracking-wider font-medium transition group">
                <i class="fas fa-bolt text-[#BF8D1A] text-[9px] group-hover:rotate-12 transition"></i>Acesso rápido
            </a>
            <a href="/crcap/pages/servicos-online.php" class="flex items-center gap-1.5 text-white/70 hover:text-white text-[10px] uppercase tracking-wider font-medium transition">
                <i class="fas fa-globe text-[#BF8D1A] text-[9px]"></i>Serviços online
            </a>
        </div>
        <div class="flex items-center gap-5">
            <div class="flex items-center gap-1.5 text-white/70 text-[10px] uppercase tracking-wider">
                <div class="relative w-1.5 h-1.5 bg-[#006633] rounded-full pulse-dot"></div>
                <span>Sistema operacional</span>
            </div>
            <a href="/crcap/pages/contato.php" class="flex items-center gap-1.5 text-white/70 hover:text-white text-[10px] uppercase tracking-wider font-medium transition">
                <i class="fas fa-phone text-[#BF8D1A] text-[9px]"></i>Contato
            </a>
        </div>
    </div>
</div>

<!-- ── Header sticky ──────────────────────────────────────────────────── -->
<header class="sticky top-0 z-50 glass border-b border-[#001644]/5">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-20">

            <!-- Logo -->
            <a href="/crcap/index.php" class="flex items-center gap-3 group flex-shrink-0">
                <?php if ($siteLogo): ?>
                <img src="<?= h($siteLogo) ?>" alt="<?= h($siteName) ?>" class="h-10 max-w-[180px] object-contain group-hover:opacity-90 transition">
                <?php else: ?>
                <div class="w-10 h-10 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:scale-105 transition">C</div>
                <div>
                    <h1 class="text-base font-bold text-[#001644] leading-none"><?= h($siteName) ?></h1>
                    <p class="text-[9px] text-[#022E6B] font-medium tracking-wider uppercase">Conselho Regional</p>
                </div>
                <?php endif; ?>
            </a>

            <!-- ── Desktop Nav ─────────────────────────────────────────── -->
            <nav class="hidden xl:flex items-center gap-0.5">
            <?php
            $isFirst = true;
            foreach ($menuRoots as $root):
                $kids       = $menuChildren[(int)$root['id']] ?? [];
                $hasKids    = !empty($kids);
                $isMega     = str_contains($root['css_class'] ?? '', 'mega');
                $url        = $root['url'] ?: '#';
                // Detecta ativo: compara activeMenu com css_class do root ou URL
                $slug       = strtolower(preg_replace('/\s+/','-', preg_replace('/[^a-z0-9\s]/i','', $root['title'])));
                $isActive   = ($activeMenu && ($activeMenu === $slug || $activeMenu === ($root['css_class'] ?? '')))
                            || ($root['url'] && rtrim($root['url'],'/') === rtrim(strtok($_SERVER['REQUEST_URI']??'','?'),'/'));
            ?>

            <?php if (!$hasKids): /* ── Link simples ── */ ?>
            <a href="<?= h($url) ?>" target="<?= h($root['target']) ?>"
               class="nav-dd-btn px-3 py-2 text-xs font-medium rounded-lg transition
                      <?= $isActive?'bg-[#001644] text-white shadow-md':'text-[#001644] hover:bg-[#F0F4F8]' ?>">
                <?php if($root['icon']): ?><i class="fas <?= h($root['icon']) ?> mr-1 text-[10px]"></i><?php endif; ?>
                <?= h($root['title']) ?>
            </a>

            <?php elseif ($isMega): /* ── Mega Menu 3 colunas ── */
                $cols = ['col-1'=>[], 'col-2'=>[], 'col-3'=>[]];
                foreach ($kids as $k) {
                    $cc = $k['css_class'] ?? 'col-1';
                    if (str_contains($cc,'col-3'))     $cols['col-3'][] = $k;
                    elseif (str_contains($cc,'col-2')) $cols['col-2'][] = $k;
                    else                               $cols['col-1'][] = $k;
                }
                $hasAnyCols = !empty($cols['col-1']) || !empty($cols['col-2']) || !empty($cols['col-3']);
            ?>
            <div class="nav-dd relative">
                <button class="nav-dd-btn px-3 py-2 text-xs font-medium rounded-lg transition flex items-center gap-1
                               <?= $isActive?'bg-[#001644] text-white':'text-[#001644] hover:bg-[#F0F4F8]' ?>">
                    <?php if($root['icon']): ?><i class="fas <?= h($root['icon']) ?> mr-1 text-[10px]"></i><?php endif; ?>
                    <?= h($root['title']) ?> <i class="fas fa-chevron-down text-[8px] opacity-60"></i>
                </button>
                <div class="nav-dd-menu absolute top-full left-1/2 -translate-x-1/2 mt-1 bg-white rounded-2xl py-5 z-50
                            <?= $hasAnyCols ? 'min-w-[740px]' : 'min-w-[220px]' ?>">
                    <?php if ($hasAnyCols): ?>
                    <div class="grid grid-cols-3 gap-6 px-6">
                        <?php foreach (['col-1','col-2','col-3'] as $col):
                            if (empty($cols[$col])) continue;
                            $meta = $megaColTitles[$col] ?? ['label'=>'','icon'=>''];
                        ?>
                        <div>
                            <h4 class="text-[9px] font-bold text-[#BF8D1A] uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <i class="fas <?= $meta['icon'] ?> text-[10px]"></i><?= $meta['label'] ?>
                            </h4>
                            <ul class="space-y-1.5">
                            <?php foreach ($cols[$col] as $k): ?>
                                <li>
                                    <a href="<?= h($k['url']?:'#') ?>" target="<?= h($k['target']) ?>"
                                       class="flex items-center gap-1.5 text-xs text-[#001644] hover:text-[#BF8D1A] hover:translate-x-1 transition">
                                        <?php if($k['icon']): ?><i class="fas <?= h($k['icon']) ?> text-[9px] text-[#BF8D1A]/60 flex-shrink-0"></i><?php endif; ?>
                                        <?= h($k['title']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: /* fallback lista simples */ ?>
                    <div class="py-1">
                        <?php foreach ($kids as $k): ?>
                        <a href="<?= h($k['url']?:'#') ?>" target="<?= h($k['target']) ?>"
                           class="block px-4 py-2.5 text-xs text-[#001644] hover:text-[#BF8D1A] hover:bg-[#F8FAFC] hover:pl-6 transition">
                            <?php if($k['icon']): ?><i class="fas <?= h($k['icon']) ?> mr-1.5 text-[9px] text-[#BF8D1A]/70"></i><?php endif; ?>
                            <?= h($k['title']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: /* ── Dropdown simples ── */ ?>
            <div class="nav-dd relative">
                <button class="nav-dd-btn px-3 py-2 text-xs font-medium rounded-lg transition flex items-center gap-1
                               <?= $isActive?'bg-[#001644] text-white':'text-[#001644] hover:bg-[#F0F4F8]' ?>">
                    <?php if($root['icon']): ?><i class="fas <?= h($root['icon']) ?> mr-1 text-[10px]"></i><?php endif; ?>
                    <?= h($root['title']) ?> <i class="fas fa-chevron-down text-[8px] opacity-60"></i>
                </button>
                <div class="nav-dd-menu absolute top-full <?= $isFirst?'left-0':'right-0' ?> mt-1 w-60 bg-white rounded-xl py-1.5 z-50">
                    <?php foreach ($kids as $k): ?>
                    <a href="<?= h($k['url']?:'#') ?>" target="<?= h($k['target']) ?>"
                       class="flex items-center gap-2 px-4 py-2.5 text-xs text-[#001644] hover:text-[#BF8D1A] hover:bg-[#F8FAFC] hover:pl-6 transition">
                        <?php if($k['icon']): ?><i class="fas <?= h($k['icon']) ?> text-[9px] text-[#BF8D1A]/70 flex-shrink-0 w-3.5"></i><?php endif; ?>
                        <?= h($k['title']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php $isFirst = false; endforeach; ?>
            </nav>

            <!-- ── CTA + Search ─────────────────────────────────────────── -->
            <div class="hidden xl:flex items-center gap-2 flex-shrink-0">
                <!-- Busca -->
                <div class="relative" id="headerSearch">
                    <div class="flex items-center bg-[#F0F4F8] rounded-lg border border-[#001644]/5 hover:border-[#BF8D1A]/40 transition">
                        <i class="fas fa-search text-[#001644]/40 text-xs pl-3"></i>
                        <input type="text" id="hsi" placeholder="Buscar..." autocomplete="off"
                               class="w-0 focus:w-36 bg-transparent px-2 py-2 text-xs text-[#001644] placeholder-[#001644]/40 focus:outline-none transition-all duration-300">
                    </div>
                    <div id="hsr" class="hidden absolute top-full right-0 mt-1 w-80 bg-white rounded-xl shadow-2xl border border-[#001644]/5 z-50 overflow-hidden">
                        <div id="hsrl" class="max-h-80 overflow-y-auto"></div>
                        <a id="hsra" href="#" class="block px-4 py-2.5 text-center text-xs font-semibold text-[#BF8D1A] hover:bg-[#F8FAFC] border-t border-[#001644]/5 transition">Ver todos →</a>
                    </div>
                </div>

                <!-- Usuário -->
                <?php if (isset($pdo) && function_exists('isLogged') && isLogged()): $u = currentUser(); ?>
                <div class="relative group">
                    <button class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-[#F0F4F8] transition text-xs font-medium text-[#001644]">
                        <?php if (!empty($u['avatar'])): ?>
                        <img src="<?= h($u['avatar']) ?>" class="w-6 h-6 rounded-full object-cover">
                        <?php else: ?>
                        <div class="w-6 h-6 rounded-full bg-[#001644] text-white flex items-center justify-center text-[10px] font-bold"><?= strtoupper(substr($u['full_name']??$u['username'],0,1)) ?></div>
                        <?php endif; ?>
                        <?= h(explode(' ',$u['full_name']??$u['username'])[0]) ?>
                        <i class="fas fa-chevron-down text-[9px]"></i>
                    </button>
                    <div class="hidden group-hover:block absolute top-full right-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-[#001644]/5 py-1.5 z-50">
                        <a href="/crcap/usuario/perfil.php" class="block px-4 py-2 text-xs text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition"><i class="fas fa-user w-4 mr-1"></i>Meu Perfil</a>
                        <?php if (function_exists('isAdmin') && isAdmin()): ?>
                        <a href="/crcap/admin/index.php" class="block px-4 py-2 text-xs text-[#001644] hover:bg-[#F8FAFC] hover:text-[#BF8D1A] transition"><i class="fas fa-cog w-4 mr-1"></i>Painel Admin</a>
                        <?php endif; ?>
                        <div class="border-t border-[#001644]/5 my-1"></div>
                        <a href="/crcap/admin/logout.php" class="block px-4 py-2 text-xs text-red-500 hover:bg-red-50 transition"><i class="fas fa-sign-out-alt w-4 mr-1"></i>Sair</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="/crcap/pages/login.php" class="px-4 py-2 text-xs font-medium text-[#001644] hover:bg-[#F0F4F8] rounded-lg transition">Login</a>
                <a href="/crcap/usuario/perfil.php" class="px-4 py-2 text-xs font-semibold text-white bg-[#001644] rounded-lg hover:bg-[#022E6B] hover:shadow-lg hover:shadow-[#001644]/20 transition hover:-translate-y-0.5">Área do Profissional</a>
                <?php endif; ?>
            </div>

            <!-- Mobile hamburguer -->
            <button onclick="mobToggle()" id="mobBtn" class="xl:hidden w-10 h-10 flex items-center justify-center rounded-lg hover:bg-[#F0F4F8] text-[#001644] transition">
                <i class="fas fa-bars text-sm" id="mobIcon"></i>
            </button>
        </div>
    </div>

    <!-- ── Mobile Menu ──────────────────────────────────────────────────── -->
    <div id="mobileMenu" class="hidden xl:hidden bg-white border-t border-[#001644]/5 absolute w-full max-h-[85vh] overflow-y-auto shadow-xl z-40">
        <div class="container mx-auto px-4 py-3 space-y-0.5">
        <?php foreach ($menuRoots as $root):
            $kids    = $menuChildren[(int)$root['id']] ?? [];
            $hasKids = !empty($kids);
            $url     = $root['url'] ?: '#';
            $isActive = ($root['url'] && rtrim($root['url'],'/') === rtrim(strtok($_SERVER['REQUEST_URI']??'','?'),'/'));
        ?>
            <?php if (!$hasKids): ?>
            <a href="<?= h($url) ?>" target="<?= h($root['target']) ?>"
               class="flex items-center gap-2 px-4 py-3 text-xs font-medium rounded-xl transition
                      <?= $isActive?'bg-[#001644] text-white':'text-[#001644] hover:bg-[#F8FAFC]' ?>">
                <?php if($root['icon']): ?><i class="fas <?= h($root['icon']) ?> text-[10px] text-[#BF8D1A]"></i><?php endif; ?>
                <?= h($root['title']) ?>
            </a>
            <?php else: ?>
            <div class="rounded-xl overflow-hidden">
                <button onclick="mobSub(this)" class="mob-toggle w-full flex items-center justify-between px-4 py-3 text-xs font-medium text-[#001644] hover:bg-[#F8FAFC] transition">
                    <span class="flex items-center gap-2">
                        <?php if($root['icon']): ?><i class="fas <?= h($root['icon']) ?> text-[10px] text-[#BF8D1A]"></i><?php endif; ?>
                        <?= h($root['title']) ?>
                    </span>
                    <i class="fas fa-chevron-down text-[9px] text-[#022E6B]/40 chevron"></i>
                </button>
                <div class="mob-sub bg-[#F8FAFC] pl-4 space-y-0.5 px-2">
                <?php foreach ($kids as $k): ?>
                    <a href="<?= h($k['url']?:'#') ?>" target="<?= h($k['target']) ?>"
                       class="flex items-center gap-2 px-4 py-2.5 text-xs text-[#022E6B] hover:text-[#BF8D1A] hover:bg-white rounded-lg transition">
                        <?php if($k['icon']): ?><i class="fas <?= h($k['icon']) ?> text-[9px] text-[#BF8D1A]/60 w-3 flex-shrink-0"></i><?php endif; ?>
                        <?= h($k['title']) ?>
                    </a>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>

            <!-- Botões de acesso mobile -->
            <div class="pt-3 border-t border-[#001644]/8 space-y-2 pb-2">
                <?php if (isset($pdo) && function_exists('isLogged') && isLogged()): $u = currentUser(); ?>
                <div class="flex items-center gap-2 px-4 py-2.5 bg-[#F8FAFC] rounded-xl">
                    <div class="w-7 h-7 rounded-full bg-[#001644] text-white flex items-center justify-center text-[10px] font-bold flex-shrink-0"><?= strtoupper(substr($u['full_name']??$u['username'],0,1)) ?></div>
                    <span class="text-xs font-semibold text-[#001644] truncate"><?= h($u['full_name']??$u['username']) ?></span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="/crcap/usuario/perfil.php" class="py-2.5 text-xs font-semibold text-center text-[#001644] border border-[#001644]/15 rounded-xl hover:bg-[#F8FAFC] transition">Meu Perfil</a>
                    <?php if (function_exists('isAdmin') && isAdmin()): ?>
                    <a href="/crcap/admin/index.php" class="py-2.5 text-xs font-semibold text-center text-white bg-[#001644] rounded-xl">Admin</a>
                    <?php else: ?>
                    <a href="/crcap/admin/logout.php" class="py-2.5 text-xs font-semibold text-center text-red-500 border border-red-200 rounded-xl">Sair</a>
                    <?php endif; ?>
                </div>
                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <a href="/crcap/admin/logout.php" class="block w-full py-2.5 text-xs font-medium text-center text-red-500 border border-red-200 rounded-xl">Sair</a>
                <?php endif; ?>
                <?php else: ?>
                <a href="/crcap/pages/login.php"    class="block w-full py-3 text-xs font-semibold text-center text-[#001644] border border-[#001644]/20 rounded-xl hover:bg-[#F8FAFC] transition">Login</a>
                <a href="/crcap/usuario/perfil.php" class="block w-full py-3 text-xs font-semibold text-center text-white bg-[#001644] rounded-xl hover:bg-[#022E6B] transition">Área do Profissional</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
// ── Mobile toggle ─────────────────────────────────────────────────────────
function mobToggle(){
    const m=document.getElementById('mobileMenu');
    const i=document.getElementById('mobIcon');
    const open=!m.classList.contains('hidden');
    m.classList.toggle('hidden');
    i.className=open?'fas fa-bars text-sm':'fas fa-times text-sm';
}
function mobSub(btn){
    const sub=btn.nextElementSibling;
    const open=sub.classList.contains('open');
    document.querySelectorAll('.mob-sub').forEach(e=>e.classList.remove('open'));
    document.querySelectorAll('.mob-toggle').forEach(e=>e.classList.remove('open'));
    if(!open){sub.classList.add('open');btn.classList.add('open');}
}
// ── Live Search ───────────────────────────────────────────────────────────
(function(){
    const inp=document.getElementById('hsi');
    const box=document.getElementById('hsr');
    const lst=document.getElementById('hsrl');
    const all=document.getElementById('hsra');
    if(!inp)return;
    let t;
    inp.addEventListener('input',function(){
        clearTimeout(t);const q=this.value.trim();
        if(q.length<2){box.classList.add('hidden');return;}
        t=setTimeout(()=>doS(q),280);
    });
    inp.addEventListener('keydown',function(e){
        if(e.key==='Enter')window.location='/crcap/pages/busca.php?q='+encodeURIComponent(this.value.trim());
    });
    document.addEventListener('click',function(e){
        if(!document.getElementById('headerSearch').contains(e.target))box.classList.add('hidden');
    });
    async function doS(q){
        try{
            const r=await fetch('/crcap/api/search.php?q='+encodeURIComponent(q)+'&limit=5');
            const d=await r.json();
            if(!d.results?.length){lst.innerHTML='<p class="text-xs text-center py-4 text-[#022E6B]">Nenhum resultado</p>';}
            else{lst.innerHTML=d.results.map(r=>`<a href="${r.url}" class="flex items-start gap-3 px-4 py-3 hover:bg-[#F8FAFC] border-b border-[#001644]/3 last:border-0 transition"><div class="w-7 h-7 rounded-lg bg-[#001644]/5 flex items-center justify-center flex-shrink-0"><i class="fas ${r.type_icon} text-[10px] text-[#001644]"></i></div><div class="min-w-0"><p class="text-xs font-semibold text-[#001644] truncate">${r.title}</p><p class="text-[10px] text-[#022E6B]">${r.type}${r.date?' · '+r.date:''}</p></div></a>`).join('');}
            if(all)all.href='/crcap/pages/busca.php?q='+encodeURIComponent(q);
            box.classList.remove('hidden');
        }catch(e){console.error(e);}
    }
})();
</script>