<?php
/**
 * Public landing page - fully driven by Website CMS (data/cms.json)
 */
require_once 'includes/layout.php';
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/social-icons.php';
require_once __DIR__ . '/includes/SettingsManager.php';

$manager = new SettingsManager();
extract($manager->getBrandingVars());

$cms = getCmsData();
$hero = $cms['hero'] ?? [];
$aboutSec = $cms['sections']['about'] ?? [];
$servicesSec = $cms['sections']['services'] ?? [];
$gallerySec = $cms['sections']['gallery'] ?? [];
$contactSec = $cms['sections']['contact'] ?? [];
$socialSec = $cms['sections']['social'] ?? [];
$visionSec = $cms['sections']['vision'] ?? [];

$services = $cms['services'] ?? [];
$gallery = $cms['gallery'] ?? [];
$contact = $cms['contact'] ?? [];
$about = $cms['about'] ?? [];

$heroImage = ($hero['background_image'] ?? '') ?: 'assets/welcome_bg.png';
$heroHeadline = ($hero['headline'] ?? '') ?: $appName;
$heroSubtitle = ($hero['subtitle'] ?? '') ?: $appTagline;
$heroEyebrow = ($hero['eyebrow'] ?? '') ?: 'Welcome';
$ctaLink = ($hero['cta_text'] ?? '') ? ($hero['cta_link'] ?? '#services') : '';
$ctaText = $hero['cta_text'] ?? 'Explore';
$phoneHref = !empty($contact['phone']) ? preg_replace('/\s+/', '', $contact['phone']) : '';

$aboutImage = trim($about['image'] ?? '');
$showAboutImage = $aboutImage !== '' && $aboutImage !== 'assets/about_placeholder.png';
if ($showAboutImage) {
    $aboutImagePath = __DIR__ . '/' . ltrim($aboutImage, '/');
    $showAboutImage = file_exists($aboutImagePath);
}

$title = $appName . ' - ' . $heroHeadline;
renderHeader($title, ['nav' => 'kiosk']);
renderSocialIconsStylesheet();
?>

<div class="landing-page min-h-screen bg-[#f6f1e8] text-[#1e211d] font-sans selection:bg-[#b9653a]/20 overflow-x-hidden">
    <header class="fixed inset-x-0 top-0 z-50 border-b border-[#1e211d]/10 bg-[#eee8de]/95 shadow-sm shadow-black/5 backdrop-blur-xl">
        <div class="mx-auto flex h-[76px] max-w-[1500px] items-center justify-between px-5 sm:px-8 lg:px-12">
            <a href="#" class="flex min-w-0 items-center gap-3" aria-label="<?php echo htmlspecialchars($appName); ?> home">
                <?php if ($publicLogoUrl): ?>
                    <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-full border border-[#1e211d]/10 bg-white shadow-sm">
                        <img src="<?php echo htmlspecialchars($publicLogoUrl); ?>" alt="<?php echo htmlspecialchars($appName); ?> logo" class="h-full w-full object-cover">
                    </span>
                <?php else: ?>
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-[#1e211d] text-[10px] font-black uppercase tracking-[0.16em] text-[#f6f1e8] shadow-sm">
                        <?php echo htmlspecialchars(substr($appName, 0, 3)); ?>
                    </span>
                <?php endif; ?>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-black uppercase tracking-[0.18em] text-[#1e211d]"><?php echo htmlspecialchars($appName); ?></span>
                    <span class="hidden truncate text-xs text-[#6c7168] sm:block"><?php echo htmlspecialchars($appTagline); ?></span>
                </span>
            </a>

            <nav class="hidden items-center gap-9 text-xs font-black uppercase tracking-[0.2em] text-[#6c7168] md:flex">
                <a href="#about" class="transition hover:text-[#b9653a]">About</a>
                <a href="#services" class="transition hover:text-[#b9653a]">Services</a>
                <a href="#gallery" class="transition hover:text-[#b9653a]">Menu</a>
                <a href="#contact" class="transition hover:text-[#b9653a]">Contact</a>
            </nav>

            <div class="flex items-center gap-2">

                <a href="login.php" class="hidden h-12 items-center rounded-full border border-[#1e211d]/20 px-5 text-xs font-black uppercase tracking-[0.14em] text-[#1e211d] transition hover:border-[#b9653a] hover:text-[#b9653a] sm:inline-flex">
                    Login
                </a>
                <button type="button" id="mobileMenuBtn" class="grid h-12 w-12 place-items-center rounded-full border border-[#1e211d]/20 text-[#1e211d] md:hidden" aria-label="Open navigation">
                    <i data-lucide="menu" class="h-5 w-5"></i>
                </button>
            </div>
        </div>
    </header>

    <div id="mobileNav" class="fixed inset-0 z-[70] hidden bg-[#1e211d] px-6 text-[#f6f1e8]">
        <div class="flex h-20 items-center justify-between">
            <span class="text-sm font-black uppercase tracking-[0.18em]"><?php echo htmlspecialchars($appName); ?></span>
            <button type="button" id="mobileNavClose" class="grid h-11 w-11 place-items-center rounded-lg border border-white/20" aria-label="Close navigation">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <nav class="flex min-h-[70vh] flex-col justify-center gap-7 text-3xl font-semibold">
            <a href="#about" class="mobile-nav-link">About</a>
            <a href="#services" class="mobile-nav-link">Services</a>
            <a href="#gallery" class="mobile-nav-link">Menu</a>
            <a href="#contact" class="mobile-nav-link">Contact</a>
            <a href="login.php" class="mobile-nav-link text-[#d28f62]">Login</a>
        </nav>
    </div>

    <main>
        <section class="hero-section relative isolate min-h-screen overflow-hidden pt-[76px]">
            <div class="absolute inset-0">
                <?php if ($heroImage): ?>
                    <img src="<?php echo htmlspecialchars($heroImage); ?>" alt="" class="h-full w-full object-cover hero-image">
                <?php endif; ?>
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(11,14,11,0.74)_0%,rgba(11,14,11,0.42)_45%,rgba(11,14,11,0.16)_100%)]"></div>
                <div class="absolute inset-x-0 bottom-0 h-36 bg-[#f6f1e8]"></div>
            </div>

            <div class="relative mx-auto grid min-h-[calc(100svh-76px)] max-w-[1500px] grid-cols-1 items-center gap-8 px-5 pb-16 pt-8 sm:px-8 lg:grid-cols-[minmax(0,1fr)_minmax(340px,480px)] lg:px-12 lg:pb-20">
                <div class="max-w-4xl text-white">
                    <div class="reveal mb-5 inline-flex items-center gap-3 rounded-full border border-white/25 bg-white/10 px-5 py-2.5 text-xs font-black uppercase tracking-[0.22em] shadow-lg shadow-black/10 backdrop-blur-md">
                        <span class="h-2 w-2 rounded-full bg-[#d28f62]"></span>
                        <?php echo htmlspecialchars($heroEyebrow); ?>
                    </div>
                    <h1 class="hero-title reveal reveal-delay-1 text-balance font-serif-lux font-semibold leading-[0.9] tracking-normal drop-shadow-[0_10px_32px_rgba(0,0,0,0.4)]">
                        <?php echo htmlspecialchars($heroHeadline); ?>
                    </h1>
                    <p class="reveal reveal-delay-2 mt-6 max-w-2xl text-base font-medium leading-8 text-white/90 sm:text-lg">
                        <?php echo htmlspecialchars($heroSubtitle); ?>
                    </p>
                    <div class="reveal reveal-delay-3 mt-9 flex flex-col gap-3 sm:flex-row">
                        <?php if (!empty($ctaText)): ?>
                            <a href="<?php echo htmlspecialchars($ctaLink); ?>" class="inline-flex h-13 items-center justify-center gap-3 rounded-full bg-[#df925d] px-8 py-4 text-sm font-black uppercase tracking-[0.14em] text-[#1e211d] shadow-xl shadow-black/25 transition hover:-translate-y-0.5 hover:bg-[#f0b27b]">
                                <?php echo htmlspecialchars($ctaText); ?>
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </a>
                        <?php endif; ?>
                        <a href="#gallery" class="inline-flex h-13 items-center justify-center gap-3 rounded-full border border-white/30 bg-white/10 px-8 py-4 text-sm font-black uppercase tracking-[0.14em] text-white shadow-lg shadow-black/10 backdrop-blur-md transition hover:-translate-y-0.5 hover:bg-white hover:text-[#1e211d]">
                            View Menu
                            <i data-lucide="utensils" class="h-4 w-4"></i>
                        </a>
                    </div>
                </div>

                <aside class="hero-panel reveal reveal-delay-2 w-full max-w-[480px] justify-self-start rounded-[28px] border border-white/25 bg-[#f6f1e8]/90 p-5 text-[#1e211d] shadow-2xl shadow-black/30 backdrop-blur-xl lg:justify-self-end">
                    <div class="flex items-center justify-between border-b border-[#1e211d]/10 px-2 py-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#8b6f58]">Services</p>
                            <p class="mt-1 text-4xl font-black"><?php echo count($services); ?></p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-[#1e211d] text-white">
                            <i data-lucide="concierge-bell" class="h-5 w-5"></i>
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-b border-[#1e211d]/10 px-2 py-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#8b6f58]">Menu Looks</p>
                            <p class="mt-1 text-4xl font-black"><?php echo count($gallery); ?></p>
                        </div>
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-[#df925d] text-[#1e211d]">
                            <i data-lucide="image" class="h-5 w-5"></i>
                        </span>
                    </div>
                    <div class="mt-3 rounded-2xl bg-[#526454] p-5 text-white shadow-lg shadow-[#526454]/20">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-white/60">Next Step</p>
                        <a href="#contact" class="mt-3 inline-flex items-center gap-2 text-xl font-black">
                            Plan a visit
                            <i data-lucide="arrow-up-right" class="h-5 w-5"></i>
                        </a>
                    </div>
                </aside>
            </div>
        </section>

        <section id="about" class="px-5 py-20 sm:px-8 lg:px-10 lg:py-28">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                <?php if ($showAboutImage): ?>
                    <figure class="relative order-2 overflow-hidden rounded-lg bg-[#1e211d] lg:order-1">
                        <img src="<?php echo htmlspecialchars($aboutImage); ?>" alt="<?php echo htmlspecialchars($about['title'] ?? $appName); ?>" class="aspect-[4/5] h-full w-full object-cover">
                        <figcaption class="absolute bottom-4 left-4 right-4 rounded-lg bg-[#f6f1e8]/90 p-4 text-sm font-semibold text-[#1e211d] backdrop-blur-md">
                            <?php echo htmlspecialchars($appTagline); ?>
                        </figcaption>
                    </figure>
                <?php endif; ?>

                <div class="<?php echo $showAboutImage ? 'order-1 lg:order-2' : ''; ?>">
                    <?php if (!empty($aboutSec['badge'])): ?>
                        <p class="mb-5 text-xs font-black uppercase tracking-[0.24em] text-[#b9653a]"><?php echo htmlspecialchars($aboutSec['badge']); ?></p>
                    <?php endif; ?>
                    <h2 class="max-w-3xl font-serif-lux text-5xl font-semibold leading-tight tracking-normal text-[#1e211d] lg:text-7xl">
                        <?php echo htmlspecialchars($about['title'] ?? 'About Us'); ?>
                    </h2>
                    <div class="mt-8 max-w-2xl space-y-6 text-lg leading-9 text-[#5f645d]">
                        <p><?php echo nl2br(htmlspecialchars($about['content'] ?? '')); ?></p>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-3">
                        <a href="#services" class="inline-flex items-center gap-2 rounded-lg bg-[#1e211d] px-5 py-3 text-sm font-black uppercase tracking-[0.14em] text-white transition hover:bg-[#526454]">
                            Explore Services
                            <i data-lucide="sparkles" class="h-4 w-4"></i>
                        </a>
                        <a href="#contact" class="inline-flex items-center gap-2 rounded-lg border border-[#1e211d]/20 px-5 py-3 text-sm font-black uppercase tracking-[0.14em] text-[#1e211d] transition hover:border-[#b9653a] hover:text-[#b9653a]">
                            Contact
                            <i data-lucide="send" class="h-4 w-4"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($services)): ?>
            <section id="services" class="bg-[#1e211d] px-5 py-20 text-white sm:px-8 lg:px-10 lg:py-28">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-12 grid gap-6 lg:grid-cols-[0.8fr_1fr] lg:items-end">
                        <div>
                            <?php if (!empty($servicesSec['badge'])): ?>
                                <p class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-[#d28f62]"><?php echo htmlspecialchars($servicesSec['badge']); ?></p>
                            <?php endif; ?>
                            <h2 class="font-serif-lux text-5xl font-semibold leading-tight tracking-normal lg:text-7xl"><?php echo htmlspecialchars($servicesSec['title'] ?? 'Our Services'); ?></h2>
                        </div>
                        <?php if (!empty($servicesSec['subtitle'])): ?>
                            <p class="max-w-2xl text-base leading-8 text-white/60 lg:justify-self-end"><?php echo htmlspecialchars($servicesSec['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <?php foreach ($services as $idx => $s): ?>
                            <article class="group overflow-hidden rounded-lg border border-white/10 bg-white/[0.04]">
                                <div class="relative aspect-[16/11] overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($s['image']); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-[#1e211d]/70 to-transparent"></div>
                                    <div class="absolute bottom-4 left-4 grid h-12 w-12 place-items-center rounded-lg bg-[#d28f62] text-[#1e211d]">
                                        <i data-lucide="<?php echo htmlspecialchars($s['icon'] ?? 'star'); ?>" class="h-6 w-6"></i>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <p class="mb-3 text-xs font-black uppercase tracking-[0.2em] text-[#d28f62]">0<?php echo $idx + 1; ?></p>
                                    <h3 class="text-2xl font-black"><?php echo htmlspecialchars($s['title']); ?></h3>
                                    <p class="mt-4 text-sm leading-7 text-white/60"><?php echo htmlspecialchars($s['description']); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($gallery)): ?>
            <section id="gallery" class="px-5 py-20 sm:px-8 lg:px-10 lg:py-28">
                <div class="mx-auto max-w-7xl">
                    <div class="mb-12 flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                        <div>
                            <?php if (!empty($gallerySec['badge'])): ?>
                                <p class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-[#b9653a]"><?php echo htmlspecialchars($gallerySec['badge']); ?></p>
                            <?php endif; ?>
                            <h2 class="font-serif-lux text-5xl font-semibold leading-tight tracking-normal text-[#1e211d] lg:text-7xl"><?php echo htmlspecialchars($gallerySec['title'] ?? 'Menu Gallery'); ?></h2>
                        </div>
                        <?php if (!empty($gallerySec['subtitle'])): ?>
                            <p class="max-w-xl text-base leading-8 text-[#5f645d]"><?php echo htmlspecialchars($gallerySec['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="grid auto-rows-[180px] grid-cols-2 gap-3 md:grid-cols-4 lg:auto-rows-[230px]">
                        <?php foreach ($gallery as $idx => $g):
                            $span = ($idx % 6 === 0) ? 'md:col-span-2 md:row-span-2' : (($idx % 5 === 2) ? 'md:row-span-2' : '');
                        ?>
                            <figure class="group relative overflow-hidden rounded-lg bg-[#1e211d] <?php echo $span; ?>">
                                <img src="<?php echo htmlspecialchars($g['image']); ?>" alt="<?php echo htmlspecialchars($g['title']); ?>" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-transparent opacity-80"></div>
                                <figcaption class="absolute bottom-0 left-0 right-0 p-4 text-sm font-black uppercase tracking-[0.16em] text-white">
                                    <?php echo htmlspecialchars($g['title']); ?>
                                </figcaption>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer id="contact" class="bg-[#10130f] px-5 pb-10 pt-20 text-white sm:px-8 lg:px-10 lg:pt-28">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-12 lg:grid-cols-[1fr_1.2fr]">
                <div>
                    <p class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-[#d28f62]">Contact</p>
                    <h2 class="font-serif-lux text-5xl font-semibold leading-tight tracking-normal lg:text-7xl">
                        <?php echo htmlspecialchars($contactSec['title'] ?? 'Contact Us'); ?>
                    </h2>
                    <?php if (!empty($visionSec['content'])): ?>
                        <blockquote class="mt-8 max-w-xl border-l-2 border-[#d28f62] pl-5 text-base leading-8 text-white/62">
                            &ldquo;<?php echo htmlspecialchars($visionSec['content']); ?>&rdquo;
                        </blockquote>
                    <?php endif; ?>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <?php if (!empty($contact['address'])): ?>
                        <div class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                            <i data-lucide="map-pin" class="mb-5 h-6 w-6 text-[#d28f62]"></i>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-white/40">Address</p>
                            <p class="mt-3 text-sm leading-7 text-white/70"><?php echo nl2br(htmlspecialchars($contact['address'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($contact['phone'])): ?>
                        <div class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                            <i data-lucide="phone" class="mb-5 h-6 w-6 text-[#d28f62]"></i>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-white/40">Phone</p>
                            <a href="tel:<?php echo htmlspecialchars($phoneHref); ?>" class="mt-3 block text-sm leading-7 text-white/70 transition hover:text-[#d28f62]"><?php echo htmlspecialchars($contact['phone']); ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($contact['email'])): ?>
                        <div class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                            <i data-lucide="mail" class="mb-5 h-6 w-6 text-[#d28f62]"></i>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-white/40">Email</p>
                            <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="mt-3 block break-words text-sm leading-7 text-white/70 transition hover:text-[#d28f62]"><?php echo htmlspecialchars($contact['email']); ?></a>
                        </div>
                    <?php endif; ?>
                    <?php
                    $allSocial = isset($cms['social']) && is_array($cms['social']) ? $cms['social'] : [];
                    $socialLinks = [];
                    foreach ($allSocial as $s) {
                        if (!empty($s['platform'])) {
                           $socialLinks[] = $s;
                        }
                    }
                    $socialCount = count($socialLinks);
                    if ($socialCount > 4) $socialCount = 4;
                    
                    if ($socialCount > 0):
                    ?>
                        <div class="rounded-lg border border-white/10 bg-white/[0.04] p-6">
                            <i data-lucide="share-2" class="mb-5 h-6 w-6 text-[#d28f62]"></i>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-white/40"><?php echo htmlspecialchars($socialSec['title'] ?? 'Follow Us'); ?></p>
                            <div class="contact-social-icons mt-4" data-count="<?php echo $socialCount; ?>">
                                <?php foreach (array_slice($socialLinks, 0, 4) as $s): 
                                    $link = !empty(trim((string)($s['link'] ?? ''))) ? $s['link'] : '#';
                                ?>
                                    <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" rel="noopener noreferrer" class="social-link-item" title="<?php echo htmlspecialchars(getSocialPlatformLabel($s['platform'], $s)); ?>" aria-label="<?php echo htmlspecialchars(getSocialPlatformLabel($s['platform'], $s)); ?>">
                                        <?php echo renderSocialIconBadge($s['platform'], ['size' => 'contact', 'active' => true], $s); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-16 flex flex-col gap-4 border-t border-white/10 pt-8 text-xs font-bold uppercase tracking-[0.18em] text-white/40 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?>. All rights reserved.</p>
                <a href="login.php" class="transition hover:text-[#d28f62]">Login</a>
            </div>
        </div>
    </footer>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&display=swap');

html { scroll-behavior: smooth; }

/* Fix: renderHeader() wraps content in flex-row — force column so sections stack & center */
body, main, main > div {
    flex-direction: column !important;
    width: 100% !important;
    min-width: 0 !important;
}

/* Ensure the landing-page fills the full viewport width */
.landing-page {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
}

.landing-page .font-serif-lux {
    font-family: 'Cormorant Garamond', Georgia, serif;
}

.hero-section {
    min-height: 100svh;
}

.hero-image {
    filter: saturate(1.08) contrast(1.02);
}

.hero-title {
    font-size: clamp(4.35rem, 8.2vw, 8.25rem);
}

.hero-panel {
    transform-origin: center right;
}

.text-balance {
    text-wrap: balance;
}

.h-13 {
    height: 3.25rem;
}

.reveal {
    opacity: 0;
    transform: translateY(18px);
    animation: revealUp 0.8s ease forwards;
}

.reveal-delay-1 { animation-delay: 0.12s; }
.reveal-delay-2 { animation-delay: 0.24s; }
.reveal-delay-3 { animation-delay: 0.36s; }

@keyframes revealUp {
    to { opacity: 1; transform: translateY(0); }
}

@media (max-height: 780px) and (min-width: 1024px) {
    .hero-title {
        font-size: clamp(3.9rem, 6.5vw, 6.35rem);
    }

    .hero-panel {
        transform: scale(0.92);
    }
}

@media (max-width: 767px) {
    .hero-title {
        font-size: clamp(3.45rem, 17vw, 5.25rem);
    }
}

@media (prefers-reduced-motion: reduce) {
    .reveal {
        animation: none;
        opacity: 1;
        transform: none;
    }
}
.contact-social-icons {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.85rem;
}

.contact-social-icons[data-count="4"] {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
}

</style>

<script>
(function () {
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileNav = document.getElementById('mobileNav');
    const closeBtn = document.getElementById('mobileNavClose');

    const closeNav = () => mobileNav?.classList.add('hidden');

    menuBtn?.addEventListener('click', () => mobileNav?.classList.remove('hidden'));
    closeBtn?.addEventListener('click', closeNav);
    mobileNav?.querySelectorAll('.mobile-nav-link').forEach(link => {
        link.addEventListener('click', closeNav);
    });
})();
</script>

<?php renderFooter(); ?>
