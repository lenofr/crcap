<?php
/**
 * includes/page_banner.php
 * Renders a consistent hero banner for interior pages.
 *
 * Expected variables (set before including):
 *   $bannerTitle       string  – Main heading
 *   $bannerSubtitle    string  – Optional subheading
 *   $bannerIcon        string  – Font Awesome class e.g. 'fa-shield-alt'
 *   $bannerBreadcrumb  array   – [['Label','url'], ...] (url=null for current)
 */
$bannerTitle      = $bannerTitle      ?? 'Página';
$bannerSubtitle   = $bannerSubtitle   ?? '';
$bannerIcon       = $bannerIcon       ?? 'fa-file-alt';
$bannerBreadcrumb = $bannerBreadcrumb ?? [['Home', '/index.php']];
?>
<section class="bg-gradient-to-br from-[#001644] via-[#022E6B] to-[#001644] text-white py-12 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10"
         style="background:radial-gradient(circle at 30% 50%, #BF8D1A 0%, transparent 60%)"></div>
    <div class="container mx-auto px-4 relative z-10">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-white/50 text-xs mb-6">
            <?php foreach ($bannerBreadcrumb as $i => $crumb): ?>
                <?php if ($i > 0): ?>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                <?php endif; ?>
                <?php if ($crumb[1]): ?>
                    <a href="<?= h($crumb[1]) ?>" class="hover:text-white transition"><?= h($crumb[0]) ?></a>
                <?php else: ?>
                    <span class="text-[#BF8D1A] font-medium"><?= h($crumb[0]) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- Title block -->
        <div class="flex items-start gap-5">
            <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-2xl flex-shrink-0">
                <i class="fas <?= h($bannerIcon) ?>"></i>
            </div>
            <div>
                <h1 class="font-serif text-2xl md:text-3xl font-bold leading-tight mb-1">
                    <?= h($bannerTitle) ?>
                </h1>
                <?php if ($bannerSubtitle): ?>
                <p class="text-white/70 text-sm max-w-2xl"><?= h($bannerSubtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>
