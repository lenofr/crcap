<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Comissões · CRCAP';
$activeMenu = 'crcap';

$slug = $_GET['comissao'] ?? '';

// Comissões definidas (podem vir do BD via pages table)
$comissoes = [
    'crcap-jovem' => [
        'title'    => 'CRCAP Jovem',
        'subtitle' => 'Espaço dedicado ao desenvolvimento dos jovens profissionais',
        'icon'     => 'fa-users',
        'color'    => 'from-[#001644] to-[#022E6B]',
        'badge'    => ['Nova','bg-[#BF8D1A]/10 text-[#BF8D1A]'],
        'members'  => 230,
        'freq'     => 'Quinzenal',
        'about'    => '<p>O <strong>CRCAP Jovem</strong> é uma iniciativa do Conselho Regional de Administração voltada para os profissionais com até 35 anos que atuam nas áreas de Administração, Gestão Pública e afins.</p>
<p>O programa oferece um espaço de troca de experiências, desenvolvimento de liderança, oportunidades de networking e mentoria com profissionais seniores da área.</p>
<h3>Objetivos</h3>
<ul>
<li>Fomentar a integração entre os jovens administradores</li>
<li>Desenvolver competências de liderança e empreendedorismo</li>
<li>Conectar jovens profissionais ao mercado de trabalho</li>
<li>Promover eventos, palestras e workshops exclusivos</li>
</ul>',
        'activities' => [
            ['fa-chalkboard-teacher','Mentorias mensais com líderes do setor'],
            ['fa-handshake','Eventos de networking e integração'],
            ['fa-trophy','Prêmio Jovem Administrador Destaque'],
            ['fa-book-open','Grupos de estudo e capacitação'],
            ['fa-laptop','Hackathons e desafios de inovação'],
        ],
        'contact' => 'crcapjovem@crcap.org.br',
        'next_event' => 'Workshop de Liderança — 15/03/2026',
        'image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&h=400&fit=crop',
    ],
    'voluntariado' => [
        'title'    => 'Programa de Voluntariado',
        'subtitle' => 'Transformando comunidades pelo conhecimento',
        'icon'     => 'fa-hand-heart',
        'color'    => 'from-[#006633] to-[#022E6B]',
        'badge'    => ['Voluntário','bg-[#006633]/10 text-[#006633]'],
        'members'  => 45,
        'freq'     => '3 projetos ativos',
        'about'    => '<p>O <strong>Programa de Voluntariado do CRCAP</strong> conecta profissionais de administração a organizações sociais, escolas públicas e pequenos negócios que necessitam de orientação profissional gratuita.</p>
<p>Nossos voluntários atuam em diversas frentes: consultoria empresarial para microempreendedores, educação financeira em escolas, apoio à gestão de ONGs e muito mais.</p>
<h3>Como participar</h3>
<ul>
<li>Ser profissional registrado no CRCAP</li>
<li>Disponibilizar pelo menos 4 horas mensais</li>
<li>Preencher o cadastro de voluntário</li>
</ul>',
        'activities' => [
            ['fa-store','Consultoria para microempreendedores'],
            ['fa-school','Educação financeira em escolas públicas'],
            ['fa-building','Apoio à gestão de ONGs e associações'],
            ['fa-seedling','Incubação de negócios sociais'],
            ['fa-file-contract','Orientação jurídica-administrativa'],
        ],
        'contact' => 'voluntariado@crcap.org.br',
        'next_event' => 'Encontro de Voluntários — 22/03/2026',
        'image' => 'https://images.unsplash.com/photo-1593113646773-028c64a8f1b8?w=800&h=400&fit=crop',
    ],
    'mulher-contabilista' => [
        'title'    => 'Mulher Contabilista',
        'subtitle' => 'Valorizando e fortalecendo a mulher na administração',
        'icon'     => 'fa-venus',
        'color'    => 'from-[#BF8D1A] to-[#022E6B]',
        'badge'    => ['Destaque','bg-[#BF8D1A]/10 text-[#BF8D1A]'],
        'members'  => 580,
        'freq'     => 'Mensal',
        'about'    => '<p>A comissão <strong>Mulher Contabilista</strong> nasceu da necessidade de criar um espaço seguro e inspirador para as profissionais que atuam na área de administração e gestão.</p>
<p>Promovemos ações voltadas à equidade de gênero no mercado de trabalho, ao desenvolvimento pessoal e profissional, e ao reconhecimento das conquistas femininas na área administrativa.</p>
<h3>Nossos pilares</h3>
<ul>
<li><strong>Empoderamento:</strong> palestras e rodas de conversa sobre liderança feminina</li>
<li><strong>Networking:</strong> conexão entre mulheres da área</li>
<li><strong>Capacitação:</strong> cursos e mentorias exclusivas</li>
<li><strong>Reconhecimento:</strong> prêmio anual Mulher Destaque</li>
</ul>',
        'activities' => [
            ['fa-trophy','Prêmio Mulher Destaque — anual'],
            ['fa-microphone','Ciclo de palestras mensais'],
            ['fa-users','Rodas de conversa e mentoria coletiva'],
            ['fa-calendar-star','Semana da Mulher Administradora (março)'],
            ['fa-book','Clube de leitura — gestão e liderança'],
        ],
        'contact' => 'mulher@crcap.org.br',
        'next_event' => 'Semana da Mulher Administradora — 08/03/2026',
        'image' => 'https://images.unsplash.com/photo-1573164713347-df18c9c76d74?w=800&h=400&fit=crop',
    ],
];

include __DIR__ . '/../includes/header.php';
?>

<?php
$com = ($slug && isset($comissoes[$slug])) ? $comissoes[$slug] : null;
if ($com):
?>

<!-- Single commission view -->
<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-12 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 40% 50%,#BF8D1A 0%,transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <nav class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <a href="/crcap/pages/comissoes.php" class="hover:text-white transition">Comissões</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]"><?= h($com['title']) ?></span>
        </nav>
        <div class="flex items-start gap-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br <?= $com['color'] ?> flex items-center justify-center text-2xl flex-shrink-0 shadow-xl">
                <i class="fas <?= $com['icon'] ?>"></i>
            </div>
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <h1 class="font-serif text-2xl md:text-3xl font-bold"><?= h($com['title']) ?></h1>
                    <span class="px-3 py-1 text-[10px] font-bold rounded-full <?= $com['badge'][1] ?>"><?= $com['badge'][0] ?></span>
                </div>
                <p class="text-white/70 text-sm mb-3"><?= h($com['subtitle']) ?></p>
                <div class="flex flex-wrap gap-4 text-xs text-white/60">
                    <span class="flex items-center gap-1.5"><i class="fas fa-users text-[#BF8D1A]"></i><?= number_format($com['members']) ?> membros</span>
                    <span class="flex items-center gap-1.5"><i class="fas fa-calendar text-[#BF8D1A]"></i><?= h($com['freq']) ?></span>
                    <span class="flex items-center gap-1.5"><i class="fas fa-calendar-star text-[#BF8D1A]"></i>Próx: <?= h($com['next_event']) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-10">
    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <!-- Cover image -->
            <div class="rounded-2xl overflow-hidden h-64">
                <img src="<?= h($com['image']) ?>" alt="<?= h($com['title']) ?>" class="w-full h-full object-cover">
            </div>

            <!-- About -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-[#BF8D1A]"></i>Sobre a Comissão
                </h2>
                <div class="prose prose-sm max-w-none text-[#022E6B] leading-relaxed
                            [&>p]:mb-3 [&>h3]:font-bold [&>h3]:text-[#001644] [&>h3]:mt-4 [&>h3]:mb-2
                            [&>ul]:list-disc [&>ul]:pl-5 [&>ul>li]:mb-1">
                    <?= $com['about'] ?>
                </div>
            </div>

            <!-- Activities -->
            <div class="bg-white rounded-2xl p-8 border border-[#001644]/3 shadow-sm">
                <h2 class="font-bold text-[#001644] text-lg mb-5 flex items-center gap-2">
                    <i class="fas fa-star text-[#BF8D1A]"></i>Atividades
                </h2>
                <div class="space-y-3">
                    <?php foreach ($com['activities'] as $act): ?>
                    <div class="flex items-center gap-3 p-3 bg-[#F8FAFC] rounded-xl">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br <?= $com['color'] ?> flex items-center justify-center flex-shrink-0">
                            <i class="fas <?= $act[0] ?> text-white text-xs"></i>
                        </div>
                        <span class="text-sm text-[#022E6B]"><?= h($act[1]) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="space-y-5">
            <!-- Join card -->
            <div class="bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-6 text-white">
                <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center mb-4">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
                <h3 class="font-bold text-lg mb-2">Quero participar</h3>
                <p class="text-white/70 text-xs mb-4">Faça parte desta comissão e contribua para o desenvolvimento da profissão.</p>
                <a href="/crcap/pages/contato.php?assunto=comissao-<?= $slug ?>" class="block w-full py-3 text-center bg-[#BF8D1A] text-white font-bold rounded-xl hover:bg-white hover:text-[#001644] transition text-sm">
                    Inscrever-se
                </a>
            </div>

            <!-- Info card -->
            <div class="bg-white rounded-2xl p-6 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Informações</h3>
                <ul class="space-y-3 text-xs">
                    <li class="flex items-center gap-3 text-[#022E6B]">
                        <i class="fas fa-users text-[#BF8D1A] w-4 text-center"></i>
                        <span><?= number_format($com['members']) ?> membros ativos</span>
                    </li>
                    <li class="flex items-center gap-3 text-[#022E6B]">
                        <i class="fas fa-sync text-[#BF8D1A] w-4 text-center"></i>
                        <span>Reuniões: <?= h($com['freq']) ?></span>
                    </li>
                    <li class="flex items-center gap-3 text-[#022E6B]">
                        <i class="fas fa-calendar text-[#BF8D1A] w-4 text-center"></i>
                        <span><?= h($com['next_event']) ?></span>
                    </li>
                    <li class="flex items-center gap-3 text-[#022E6B]">
                        <i class="fas fa-envelope text-[#BF8D1A] w-4 text-center"></i>
                        <a href="mailto:<?= h($com['contact']) ?>" class="hover:text-[#BF8D1A] transition"><?= h($com['contact']) ?></a>
                    </li>
                </ul>
            </div>

            <!-- Other commissions -->
            <div class="bg-white rounded-2xl p-6 border border-[#001644]/3 shadow-sm">
                <h3 class="font-bold text-[#001644] text-sm mb-4">Outras Comissões</h3>
                <div class="space-y-2">
                    <?php foreach ($comissoes as $k=>$c): if($k===$slug) continue; ?>
                    <a href="/crcap/pages/comissoes.php?comissao=<?= $k ?>" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-[#F8FAFC] transition group">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br <?= $c['color'] ?> flex items-center justify-center flex-shrink-0">
                            <i class="fas <?= $c['icon'] ?> text-white text-xs"></i>
                        </div>
                        <span class="text-xs font-semibold text-[#001644] group-hover:text-[#BF8D1A] transition"><?= h($c['title']) ?></span>
                        <i class="fas fa-chevron-right text-[9px] text-[#001644]/20 ml-auto"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php else: ?>

<!-- Commissions listing -->
<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-14 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 50% 50%,#BF8D1A 0%,transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">
        <nav class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <a href="/crcap/index.php" class="hover:text-white transition">Início</a>
            <i class="fas fa-chevron-right text-[9px]"></i>
            <span class="text-[#BF8D1A]">Comissões</span>
        </nav>
        <div class="flex items-start gap-5">
            <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center text-3xl flex-shrink-0">
                <i class="fas fa-sitemap"></i>
            </div>
            <div>
                <h1 class="font-serif text-3xl md:text-4xl font-bold mb-2">Comissões CRCAP</h1>
                <p class="text-white/70 text-sm max-w-2xl">Grupos de trabalho, iniciativas e programas especiais que fortalecem a comunidade de administradores.</p>
            </div>
        </div>
    </div>
</section>

<main class="container mx-auto px-4 py-12">
    <div class="grid md:grid-cols-3 gap-8">
        <?php foreach ($comissoes as $slug_key => $com): ?>
        <div class="bg-white rounded-2xl overflow-hidden border border-[#001644]/3 shadow-sm hover:-translate-y-2 hover:shadow-xl hover:shadow-[#001644]/10 transition group">
            <!-- Image -->
            <div class="relative h-48 overflow-hidden">
                <img src="<?= h($com['image']) ?>" alt="<?= h($com['title']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-[#001644]/80 to-transparent"></div>
                <div class="absolute top-3 right-3">
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full <?= $com['badge'][1] ?>"><?= $com['badge'][0] ?></span>
                </div>
                <div class="absolute bottom-3 left-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?= $com['color'] ?> flex items-center justify-center text-white text-xl shadow-lg">
                        <i class="fas <?= $com['icon'] ?>"></i>
                    </div>
                </div>
            </div>
            <!-- Content -->
            <div class="p-6">
                <h3 class="font-bold text-[#001644] text-lg mb-1 group-hover:text-[#BF8D1A] transition"><?= h($com['title']) ?></h3>
                <p class="text-xs text-[#022E6B] mb-4 leading-relaxed"><?= h($com['subtitle']) ?></p>
                <div class="flex items-center gap-4 text-[10px] text-[#022E6B] mb-5">
                    <span class="flex items-center gap-1"><i class="fas fa-users text-[#BF8D1A]"></i><?= number_format($com['members']) ?> membros</span>
                    <span class="flex items-center gap-1"><i class="fas fa-sync text-[#BF8D1A]"></i><?= h($com['freq']) ?></span>
                </div>
                <a href="/crcap/pages/comissoes.php?comissao=<?= $slug_key ?>" class="flex items-center justify-center gap-2 w-full py-2.5 bg-[#001644] text-white rounded-xl text-xs font-bold hover:bg-[#BF8D1A] transition">
                    Saiba mais <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CTA -->
    <div class="mt-12 bg-gradient-to-br from-[#001644] to-[#022E6B] rounded-2xl p-10 text-white text-center relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background:radial-gradient(circle at 50% 0%, #BF8D1A 0%, transparent 70%)"></div>
        <div class="relative z-10">
            <i class="fas fa-plus-circle text-[#BF8D1A] text-4xl mb-4 block"></i>
            <h2 class="font-serif text-2xl font-bold mb-2">Quer criar uma nova comissão?</h2>
            <p class="text-white/70 text-sm mb-6 max-w-lg mx-auto">Tem uma ideia para um grupo de trabalho ou iniciativa? Entre em contato com o CRCAP e proponha sua comissão.</p>
            <a href="/crcap/pages/contato.php" class="inline-flex items-center gap-2 px-8 py-3 bg-[#BF8D1A] text-white font-bold rounded-xl hover:bg-white hover:text-[#001644] transition">
                <i class="fas fa-envelope"></i>Propor nova comissão
            </a>
        </div>
    </div>
</main>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>