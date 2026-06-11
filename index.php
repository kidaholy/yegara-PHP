<?php
/**
 * Public landing page — fully driven by Website CMS (data/cms.json)
 */
require_once 'includes/layout.php';
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/social-icons.php';
require_once __DIR__ . '/includes/SettingsManager.php';

$manager = new SettingsManager();
extract($manager->getBrandingVars());

$cms = getCmsData();
$hero = $cms['hero'];
$aboutSec = $cms['sections']['about'] ?? [];
$servicesSec = $cms['sections']['services'] ?? [];
$gallerySec = $cms['sections']['gallery'] ?? [];
$contactSec = $cms['sections']['contact'] ?? [];
$socialSec = $cms['sections']['social'] ?? [];
$visionSec = $cms['sections']['vision'] ?? [];

$title = $appName . ' — ' . ($hero['headline'] ?? 'Luxury Experience');
renderHeader($title, ['nav' => 'kiosk']);
renderSocialIconsStylesheet();
?>

<div class="landing-page w-full bg-[#080808] text-gray-300 font-sans selection:bg-[#c5a059]/30 overflow-x-hidden">

    <!-- HERO -->
    <section class="relative min-h-screen flex flex-col overflow-hidden">
        <div class="absolute inset-0 z-0 overflow-hidden">
            <img src="<?php echo htmlspecialchars($hero['background_image']); ?>" alt="" class="w-full h-full object-cover scale-105 animate-[slowZoom_20s_ease-in-out_infinite_alternate]">
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-[#080808]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_0%,#080808_85%)]"></div>
        </div>

        <header class="relative z-50 px-6 lg:px-16 py-8 flex justify-between items-center">
            <a href="#" class="flex items-center gap-4 group">
                <?php if ($logoUrl): ?>
                    <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full overflow-hidden border-2 border-[#c5a059]/50 shadow-xl">
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-full border-2 border-[#c5a059]/60 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                        <span class="text-[10px] font-black tracking-widest text-[#c5a059]"><?php echo htmlspecialchars(strtoupper(substr($appName, 0, 3))); ?></span>
                    </div>
                <?php endif; ?>
                <div class="hidden sm:block">
                    <p class="text-[#c5a059] font-bold text-lg leading-none"><?php echo htmlspecialchars($appName); ?></p>
                    <p class="text-[9px] text-white/40 uppercase tracking-[0.25em] mt-1"><?php echo htmlspecialchars($appTagline); ?></p>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-[10px] font-bold uppercase tracking-[0.25em] text-white/45">
                <a href="#about" class="hover:text-[#c5a059] transition-colors">About</a>
                <a href="#services" class="hover:text-[#c5a059] transition-colors">Services</a>
                <a href="#gallery" class="hover:text-[#c5a059] transition-colors">Menu</a>
                <a href="#contact" class="hover:text-[#c5a059] transition-colors">Contact</a>
            </nav>

            <div class="flex items-center gap-3">
                <button type="button" id="mobileMenuBtn" class="md:hidden p-2 text-white/60 hover:text-[#c5a059]">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <a href="login.php" class="hidden sm:inline-flex px-6 py-2.5 border border-[#c5a059]/60 text-[#c5a059] text-[10px] font-black uppercase tracking-[0.2em] rounded-sm hover:bg-[#c5a059] hover:text-black transition-all">
                    Login
                </a>
            </div>
        </header>

        <!-- Mobile nav drawer -->
        <div id="mobileNav" class="fixed inset-0 z-[60] bg-black/95 backdrop-blur-xl flex flex-col items-center justify-center gap-8 text-lg font-bold uppercase tracking-[0.3em] text-white/70 hidden">
            <button type="button" id="mobileNavClose" class="absolute top-8 right-8 p-2 text-white/50 hover:text-white">
                <i data-lucide="x" class="w-8 h-8"></i>
            </button>
            <a href="#about" class="mobile-nav-link hover:text-[#c5a059]">About</a>
            <a href="#services" class="mobile-nav-link hover:text-[#c5a059]">Services</a>
            <a href="#gallery" class="mobile-nav-link hover:text-[#c5a059]">Menu</a>
            <a href="#contact" class="mobile-nav-link hover:text-[#c5a059]">Contact</a>
        </div>

        <div class="relative z-10 flex-1 flex flex-col items-center justify-center text-center px-6 pb-24 pt-8 max-w-5xl mx-auto">
            <div class="reveal flex items-center gap-6 mb-6">
                <span class="w-12 lg:w-20 h-px bg-gradient-to-r from-transparent to-[#c5a059]/50"></span>
                <span class="font-serif-lux italic text-[#c5a059] text-xl lg:text-2xl tracking-wide"><?php echo htmlspecialchars($hero['eyebrow']); ?></span>
                <span class="w-12 lg:w-20 h-px bg-gradient-to-l from-transparent to-[#c5a059]/50"></span>
            </div>

            <h1 class="reveal reveal-delay-1 font-serif-lux text-6xl sm:text-7xl lg:text-[9rem] leading-[0.9] text-[#c5a059] italic font-light mb-6 drop-shadow-2xl">
                <?php echo htmlspecialchars($hero['headline']); ?>
            </h1>

            <p class="reveal reveal-delay-2 text-sm lg:text-base text-white/70 max-w-2xl leading-relaxed font-light mb-12">
                <?php echo htmlspecialchars($hero['subtitle']); ?>
            </p>

            <?php if (!empty($hero['cta_text'])): ?>
            <a href="<?php echo htmlspecialchars($hero['cta_link'] ?: '#services'); ?>" class="reveal reveal-delay-3 group inline-flex items-center gap-3 px-10 py-4 bg-[#c5a059] text-black text-xs font-black uppercase tracking-[0.25em] rounded-sm hover:bg-[#d4b06a] transition-all shadow-xl shadow-[#c5a059]/20">
                <?php echo htmlspecialchars($hero['cta_text']); ?>
                <i data-lucide="arrow-down" class="w-4 h-4 group-hover:translate-y-0.5 transition-transform"></i>
            </a>
            <?php endif; ?>
        </div>

        <div class="relative z-10 pb-10 flex justify-center">
            <a href="#about" class="animate-bounce text-white/30 hover:text-[#c5a059] transition-colors">
                <i data-lucide="chevron-down" class="w-8 h-8"></i>
            </a>
        </div>
    </section>

    <!-- ABOUT -->
    <?php
    $aboutImage = trim($cms['about']['image'] ?? '');
    $showAboutImage = $aboutImage !== '' && $aboutImage !== 'assets/about_placeholder.png';
    if ($showAboutImage) {
        $aboutImagePath = __DIR__ . '/' . ltrim($aboutImage, '/');
        $showAboutImage = file_exists($aboutImagePath);
    }
    ?>
    <section id="about" class="py-24 lg:py-32 px-6 lg:px-16">
        <div class="max-w-7xl mx-auto grid grid-cols-1 <?php echo $showAboutImage ? 'lg:grid-cols-2' : ''; ?> gap-16 lg:gap-24 items-center">
            <div class="space-y-8">
                <?php if (!empty($aboutSec['badge'])): ?>
                <span class="inline-block px-4 py-1.5 border border-[#c5a059]/25 rounded-full text-[10px] font-black uppercase tracking-[0.35em] text-[#c5a059]">
                    <?php echo htmlspecialchars($aboutSec['badge']); ?>
                </span>
                <?php endif; ?>
                <h2 class="font-serif-lux text-5xl lg:text-6xl italic text-white leading-tight">
                    <?php echo htmlspecialchars($cms['about']['title']); ?>
                </h2>
                <div class="w-16 h-0.5 bg-gradient-to-r from-[#c5a059] to-transparent"></div>
                <p class="text-lg text-gray-400 leading-relaxed font-light">
                    <?php echo nl2br(htmlspecialchars($cms['about']['content'])); ?>
                </p>
            </div>
            <?php if ($showAboutImage): ?>
            <div class="relative group">
                <div class="absolute -inset-3 border border-[#c5a059]/15 rounded-2xl translate-x-3 translate-y-3 transition-transform group-hover:translate-x-4 group-hover:translate-y-4"></div>
                <div class="relative aspect-[4/5] max-h-[520px] rounded-2xl overflow-hidden shadow-2xl">
                    <img src="<?php echo htmlspecialchars($aboutImage); ?>" alt="<?php echo htmlspecialchars($cms['about']['title']); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- SERVICES -->
    <?php if (!empty($cms['services'])): ?>
    <section id="services" class="py-24 lg:py-32 bg-[#0c0e0d] relative">
        <div class="absolute inset-0 opacity-30 bg-[radial-gradient(ellipse_at_top,#c5a05908,transparent_60%)]"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-16">
            <div class="text-center mb-16 lg:mb-20 space-y-4">
                <?php if (!empty($servicesSec['badge'])): ?>
                <p class="text-[10px] font-black uppercase tracking-[0.45em] text-[#c5a059]"><?php echo htmlspecialchars($servicesSec['badge']); ?></p>
                <?php endif; ?>
                <h2 class="font-serif-lux text-5xl lg:text-6xl italic text-white"><?php echo htmlspecialchars($servicesSec['title'] ?? 'Our Services'); ?></h2>
                <?php if (!empty($servicesSec['subtitle'])): ?>
                <p class="text-sm text-gray-500 max-w-xl mx-auto"><?php echo htmlspecialchars($servicesSec['subtitle']); ?></p>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 lg:gap-8">
                <?php foreach ($cms['services'] as $s): ?>
                <article class="service-card group relative h-[420px] lg:h-[480px] rounded-2xl overflow-hidden cursor-default">
                    <img src="<?php echo htmlspecialchars($s['image']); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent opacity-90 group-hover:opacity-95 transition-opacity"></div>
                    <div class="absolute inset-0 p-8 lg:p-10 flex flex-col justify-end">
                        <div class="w-12 h-12 rounded-xl bg-[#c5a059] flex items-center justify-center text-black mb-5 transform translate-y-2 opacity-80 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500">
                            <i data-lucide="<?php echo htmlspecialchars($s['icon']); ?>" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($s['title']); ?></h3>
                        <p class="text-sm text-gray-400 leading-relaxed max-h-0 overflow-hidden opacity-0 group-hover:max-h-24 group-hover:opacity-100 transition-all duration-500"><?php echo htmlspecialchars($s['description']); ?></p>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- MENU GALLERY -->
    <?php if (!empty($cms['gallery'])): ?>
    <section id="gallery" class="py-24 lg:py-32 px-6 lg:px-16">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 mb-14 lg:mb-20">
                <div class="space-y-4">
                    <?php if (!empty($gallerySec['badge'])): ?>
                    <p class="text-[10px] font-black uppercase tracking-[0.45em] text-[#c5a059]"><?php echo htmlspecialchars($gallerySec['badge']); ?></p>
                    <?php endif; ?>
                    <h2 class="font-serif-lux text-5xl lg:text-6xl italic text-white"><?php echo htmlspecialchars($gallerySec['title'] ?? 'Menu Gallery'); ?></h2>
                </div>
                <?php if (!empty($gallerySec['subtitle'])): ?>
                <p class="text-sm text-gray-500 max-w-md leading-relaxed"><?php echo htmlspecialchars($gallerySec['subtitle']); ?></p>
                <?php endif; ?>
            </div>

            <div class="gallery-masonry grid grid-cols-2 md:grid-cols-4 gap-3 lg:gap-4 auto-rows-[180px] lg:auto-rows-[220px]">
                <?php foreach ($cms['gallery'] as $idx => $g):
                    $span = ($idx % 7 === 0) ? 'md:col-span-2 md:row-span-2' : (($idx % 5 === 2) ? 'md:row-span-2' : '');
                ?>
                <figure class="gallery-item relative rounded-xl overflow-hidden group <?php echo $span; ?>">
                    <img src="<?php echo htmlspecialchars($g['image']); ?>" alt="<?php echo htmlspecialchars($g['title']); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-[#c5a059]/0 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500 flex items-end p-5 lg:p-6">
                        <figcaption class="text-xs font-black uppercase tracking-[0.25em] text-white translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                            <?php echo htmlspecialchars($g['title']); ?>
                        </figcaption>
                    </div>
                    <div class="absolute top-3 right-3 w-8 h-8 rounded-full bg-[#c5a059]/90 flex items-center justify-center text-black opacity-0 group-hover:opacity-100 scale-75 group-hover:scale-100 transition-all duration-300">
                        <i data-lucide="zoom-in" class="w-4 h-4"></i>
                    </div>
                </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CONTACT & FOOTER -->
    <footer id="contact" class="pt-24 lg:pt-32 pb-12 bg-black border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6 lg:px-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-16 lg:gap-20 mb-20">
                <!-- Contact -->
                <div class="space-y-8">
                    <h3 class="font-serif-lux text-3xl lg:text-4xl italic text-[#c5a059]">
                        <?php echo htmlspecialchars($contactSec['title'] ?? 'Contact Us'); ?>
                    </h3>
                    <ul class="space-y-5 text-sm">
                        <?php if (!empty($cms['contact']['address'])): ?>
                        <li class="flex gap-4 items-start">
                            <span class="w-10 h-10 rounded-full bg-[#c5a059]/10 flex items-center justify-center shrink-0">
                                <i data-lucide="map-pin" class="w-4 h-4 text-[#c5a059]"></i>
                            </span>
                            <span class="text-white/55 leading-relaxed pt-2"><?php echo nl2br(htmlspecialchars($cms['contact']['address'])); ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($cms['contact']['phone'])): ?>
                        <li class="flex gap-4 items-center">
                            <span class="w-10 h-10 rounded-full bg-[#c5a059]/10 flex items-center justify-center shrink-0">
                                <i data-lucide="phone" class="w-4 h-4 text-[#c5a059]"></i>
                            </span>
                            <a href="tel:<?php echo preg_replace('/\s+/', '', $cms['contact']['phone']); ?>" class="text-white/55 hover:text-[#c5a059] transition-colors"><?php echo htmlspecialchars($cms['contact']['phone']); ?></a>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($cms['contact']['email'])): ?>
                        <li class="flex gap-4 items-center">
                            <span class="w-10 h-10 rounded-full bg-[#c5a059]/10 flex items-center justify-center shrink-0">
                                <i data-lucide="mail" class="w-4 h-4 text-[#c5a059]"></i>
                            </span>
                            <a href="mailto:<?php echo htmlspecialchars($cms['contact']['email']); ?>" class="text-white/55 hover:text-[#c5a059] transition-colors"><?php echo htmlspecialchars($cms['contact']['email']); ?></a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Social -->
                <?php if (!empty($cms['social'])): ?>
                <div class="space-y-8">
                    <h3 class="font-serif-lux text-3xl lg:text-4xl italic text-[#c5a059]">
                        <?php echo htmlspecialchars($socialSec['title'] ?? 'Follow Us'); ?>
                    </h3>
                    <div class="flex flex-wrap gap-4">
                        <?php foreach ($cms['social'] as $s):
                            if (empty($s['link'])) continue;
                        ?>
                        <a href="<?php echo htmlspecialchars($s['link']); ?>" target="_blank" rel="noopener noreferrer"
                           class="social-link-item group"
                           title="<?php echo htmlspecialchars(getSocialPlatformLabel($s['platform'])); ?>">
                            <?php echo renderSocialIconBadge($s['platform'], ['size' => 'lg', 'active' => true]); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Vision -->
                <?php if (!empty($visionSec['content'])): ?>
                <div class="space-y-6 md:col-span-2 lg:col-span-1">
                    <h3 class="font-serif-lux text-3xl lg:text-4xl italic text-[#c5a059]">
                        <?php echo htmlspecialchars($visionSec['title'] ?? 'Our Vision'); ?>
                    </h3>
                    <blockquote class="text-sm text-white/40 leading-loose italic border-l-2 border-[#c5a059]/30 pl-6">
                        "<?php echo htmlspecialchars($visionSec['content']); ?>"
                    </blockquote>
                </div>
                <?php endif; ?>
            </div>

            <div class="pt-10 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-[10px] font-bold text-white/25 uppercase tracking-[0.35em]">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?>. All rights reserved.
                </p>
                <a href="login.php" class="text-[10px] font-bold text-white/20 uppercase tracking-widest hover:text-[#c5a059] transition-colors">
                    Login
                </a>
            </div>
        </div>
    </footer>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600&display=swap');

html { scroll-behavior: smooth; }

.landing-page .font-serif-lux {
    font-family: 'Cormorant Garamond', Georgia, serif;
}

@keyframes slowZoom {
    from { transform: scale(1); }
    to { transform: scale(1.08); }
}

.reveal {
    opacity: 0;
    transform: translateY(24px);
    animation: revealUp 1s ease forwards;
}
.reveal-delay-1 { animation-delay: 0.15s; }
.reveal-delay-2 { animation-delay: 0.3s; }
.reveal-delay-3 { animation-delay: 0.45s; }

@keyframes revealUp {
    to { opacity: 1; transform: translateY(0); }
}

.gallery-item {
    min-height: 100%;
}

.service-card {
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
}
</style>

<script>
(function () {
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileNav = document.getElementById('mobileNav');
    const closeBtn = document.getElementById('mobileNavClose');

    menuBtn?.addEventListener('click', () => mobileNav?.classList.remove('hidden'));
    closeBtn?.addEventListener('click', () => mobileNav?.classList.add('hidden'));
    mobileNav?.querySelectorAll('.mobile-nav-link').forEach(link => {
        link.addEventListener('click', () => mobileNav.classList.add('hidden'));
    });
})();
</script>

<?php renderFooter(); ?>
