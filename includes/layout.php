<?php
/**
 * Shared layout components for the PHP Management System
 */

require_once 'lang.php';
require_once 'auth.php';
require_once __DIR__ . '/SettingsManager.php';
require_once __DIR__ . '/menu-tiers.php';

function renderHeader($title = "Management System", $options = []) {
    global $currentLang;
    $user = getCurrentUser();
    $navMode = $options['nav'] ?? 'default';
    $posTab = $options['posTab'] ?? 'standard';
    
    // Load branding & SEO
    $manager = new SettingsManager();
    extract($manager->getBrandingVars());
    
    // Fetch CMS data for SEO
    require_once __DIR__ . '/cms.php';
    $cms = getCmsData();
    $seo = $cms['seo'] ?? [];
    
    $fullTitle = htmlspecialchars($title . " | " . $appName);
    $metaDesc = htmlspecialchars($seo['description'] ?? "Welcome to " . $appName);
    $metaKeywords = htmlspecialchars($seo['keywords'] ?? "");
    $ogImage = htmlspecialchars($seo['og_image'] ?? ($logoUrl ?: ""));
    
    $baseUrl = ""; // No longer used for canonicals
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $currentLang; ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="<?php echo $metaDesc; ?>">
        <meta name="keywords" content="<?php echo $metaKeywords; ?>">
        <meta name="robots" content="index, follow">
        
        <?php if (!empty($seo['google_verification'])): ?>
        <meta name="google-site-verification" content="<?php echo htmlspecialchars($seo['google_verification']); ?>" />
        <?php endif; ?>

        <title><?php echo $fullTitle; ?></title>
        <?php if ($faviconUrl): ?>
        <link rel="icon" href="<?php echo htmlspecialchars($faviconUrl); ?>" />
        <?php endif; ?>

        <?php if ($publicLogoUrl): ?>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "Hotel",
          "name": "<?php echo addslashes($appName); ?>",
          "logo": "<?php echo addslashes((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . $publicLogoUrl); ?>",
          "url": "<?php echo addslashes((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/"); ?>",
          "description": "<?php echo addslashes($metaDesc); ?>",
          "address": {
            "@type": "PostalAddress",
            "addressLocality": "Dilla",
            "addressRegion": "Gedeo Zone, SNNPR",
            "addressCountry": "ET"
          },
          "priceRange": "$$",
          "telephone": "<?php echo addslashes($contact['phone'] ?? ''); ?>"
        }
        </script>
        <?php endif; ?>

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Google Fonts: Inter -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <!-- Lucide Icons -->
        <script src="https://unpkg.com/lucide@latest"></script>
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            :root {
                --background: 144 8% 6%; /* #0f1110 */
                --foreground: 40 10% 90%;
                --muted: 150 5% 11%;
                --muted-foreground: 40 5% 60%;
                --accent: 40 45% 56%; /* #c5a059 - Elegance Gold */
                --accent-foreground: 40 10% 10%;
                --popover: 144 8% 4%;
                --popover-foreground: 40 10% 90%;
                --border: 150 5% 15%;
                --input: 150 5% 15%;
                --card: 150 6% 9%; /* #151817 - Obsidian Glass */
                --card-foreground: 40 10% 90%;
                --primary: 40 45% 56%;
                --primary-foreground: 40 10% 10%;
                --secondary: 150 5% 11%; /* #1a1d1c - Matte Graphite */
                --secondary-foreground: 40 10% 90%;
                --destructive: 0 63% 31%;
                --destructive-foreground: 210 40% 98%;
                --ring: 40 45% 56%;
                --radius: 1.25rem;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: #0f1110;
                color: hsl(var(--foreground));
                -webkit-font-smoothing: antialiased;
            }

            /* Smooth Page Transition */
            .page-enter {
                animation: fadeIn 0.4s ease-out;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Standard Glass Components */
            .glass {
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .sidebar-link {
                display: flex;
                align-items: center;
                gap: 0.875rem;
                padding: 0.875rem 1.25rem;
                border-radius: var(--radius);
                transition: all 0.2s ease;
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.875rem;
                font-weight: 500;
            }

            .sidebar-link:hover {
                background-color: rgba(197, 160, 89, 0.05);
                color: #c5a059;
            }

            .sidebar-link.active {
                background-color: rgba(197, 160, 89, 0.1);
                color: #c5a059;
                font-weight: 600;
            }

            /* Custom Standard Scrollbar */
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { 
                background: rgba(255, 255, 255, 0.2); 
                border-radius: 9999px; 
            }
            ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.4); }

            /* Mobile Menu State */
            #mobile-menu.active {
                transform: translateX(0);
            }
        </style>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        borderRadius: {
                            lg: "var(--radius)",
                            md: "calc(var(--radius) - 2px)",
                            sm: "calc(var(--radius) - 4px)",
                        },
                        colors: {
                            background: "hsl(var(--background))",
                            foreground: "hsl(var(--foreground))",
                            primary: {
                                DEFAULT: "hsl(var(--primary))",
                                foreground: "hsl(var(--primary-foreground))",
                            },
                            secondary: {
                                DEFAULT: "hsl(var(--secondary))",
                                foreground: "hsl(var(--secondary-foreground))",
                            },
                            muted: {
                                DEFAULT: "hsl(var(--muted))",
                                foreground: "hsl(var(--muted-foreground))",
                            },
                            accent: {
                                DEFAULT: "hsl(var(--accent))",
                                foreground: "hsl(var(--accent-foreground))",
                            },
                            border: "hsl(var(--border))",
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="min-h-screen flex flex-col selection:bg-primary/10 selection:text-primary">
        
        <?php if ($user && $navMode !== 'kiosk'): ?>
        <!-- Top Navigation Bar -->
        <nav class="h-[60px] bg-[#111413] border-b border-white/5 flex items-center justify-between px-6 shrink-0 z-50 sticky top-0">
            <!-- Logo & Brand -->
            <a href="admin.php" class="flex items-center gap-3 hover:opacity-90 transition-opacity">
                <?php if ($logoUrl): ?>
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-white/10 border border-white/20 flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-white/10 border border-white/20 flex flex-col items-center justify-center flex-shrink-0">
                        <span class="text-[10px] font-bold tracking-widest text-white leading-none"><?php echo htmlspecialchars(strtoupper(substr($appName, 0, 3))); ?></span>
                    </div>
                <?php endif; ?>
                <div>
                    <h1 class="text-white font-bold text-lg leading-none tracking-tight mt-0.5"><?php echo htmlspecialchars($appName); ?></h1>
                    <p class="text-[9px] text-white/50 font-medium uppercase tracking-wider leading-none mt-1"><?php echo htmlspecialchars($appTagline); ?></p>
                </div>
            </a>

            <!-- Nav Links -->
            <div class="hidden md:flex items-center gap-1">
                <?php
                if ($navMode === 'pos') {
                    renderPosNavLinks($posTab);
                } elseif ($navMode === 'kitchen') {
                    echo '<span class="text-sm font-bold uppercase tracking-[0.25em] text-white">Kitchen</span>';
                } else {
                    renderTopNavLinks($user['role']);
                }
                ?>
            </div>

            <!-- Right Side -->
            <div class="flex items-center gap-2 md:gap-4">
                <span class="hidden lg:block text-sm font-medium text-white/80">
                    Hi, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?>!
                </span>
                <a href="logout.php" class="px-3 py-1.5 md:px-4 md:py-2 bg-white/10 hover:bg-red-500/80 hover:text-white text-white text-sm font-medium rounded-lg transition-colors border border-white/10">
                    Logout
                </a>
                
                <!-- Mobile Menu Toggle -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 text-white/70 hover:text-white transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </nav>

        <!-- Mobile Navigation Drawer -->
        <div id="mobile-menu" class="fixed inset-0 z-[100] bg-black/95 backdrop-blur-xl translate-x-full transition-transform duration-300 md:hidden">
            <div class="flex flex-col h-full">
                <!-- Mobile Header -->
                <div class="h-[60px] px-6 flex items-center justify-between border-b border-white/10 bg-[#111413]">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-white/10 border border-white/20 flex items-center justify-center">
                            <span class="text-[8px] font-bold text-white"><?php echo htmlspecialchars(strtoupper(substr($appName, 0, 3))); ?></span>
                        </div>
                        <span class="text-white font-bold">Menu</span>
                    </div>
                    <button id="mobile-menu-close" class="p-2 text-white/70 hover:text-white transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Mobile Body -->
                <div class="flex-1 overflow-y-auto p-6 space-y-8">
                    <div class="space-y-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#c5a059]/40 border-b border-[#c5a059]/10 pb-2">Main Navigation</p>
                        <div class="flex flex-col gap-1">
                            <?php 
                            if ($navMode === 'pos') {
                                renderPosNavLinks($posTab);
                            } elseif ($navMode === 'kitchen') {
                                echo '<span class="text-sm font-bold uppercase tracking-[0.25em] text-white">Kitchen</span>';
                            } else {
                                // For standard admin, we use the sidebar links as they are more complete and include icons
                                renderSidebarLinks($user['role']);
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Mobile Footer -->
                <div class="p-6 border-t border-white/10 bg-[#111413]/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#c5a059]/20 flex items-center justify-center text-[#c5a059]">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></p>
                            <p class="text-[10px] text-white/40 uppercase tracking-wider"><?php echo htmlspecialchars($user['role'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="flex-1 flex bg-[#0f1110] relative min-h-0">
            <div class="flex-1 flex relative page-enter min-h-0 w-full">
    <?php
}

function renderFooter() {
    ?>
            </div>
        </main>

        <script>
            lucide.createIcons();

            // Mobile Menu Toggle Logic
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileToggle = document.getElementById('mobile-menu-toggle');
            const mobileClose = document.getElementById('mobile-menu-close');

            if (mobileToggle && mobileMenu) {
                mobileToggle.addEventListener('click', () => {
                    mobileMenu.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }

            if (mobileClose && mobileMenu) {
                mobileClose.addEventListener('click', () => {
                    mobileMenu.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            // Close menu on link click
            const mobileLinks = mobileMenu?.querySelectorAll('a');
            mobileLinks?.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });
        </script>
    </body>
    </html>
    <?php
}

function getAdminNavLinks($role) {
    if (empty($role)) {
        $user = getCurrentUser();
        $role = $user['role'] ?? 'guest';
    }

    $links = [
        ['name' => 'Overview',     'icon' => 'layout-dashboard', 'url' => 'admin.php',        'roles' => ['admin'], 'perm' => 'overview:view'],
        ['name' => 'Orders',       'icon' => 'shopping-cart',    'url' => 'orders.php',       'roles' => ['admin', 'cashier'], 'perm' => 'orders:view'],
        ['name' => 'Users',        'icon' => 'users',            'url' => 'staff.php',        'roles' => ['admin'], 'perm' => 'users:view'],
        ['name' => 'Store',        'icon' => 'door-closed',      'url' => 'store.php',        'roles' => ['admin', 'store_keeper'], 'perm' => 'store:view'],
        ['name' => 'Stock',        'icon' => 'package-2',        'url' => 'stock.php',        'roles' => ['admin'], 'perm' => 'stock:view'],
        ['name' => 'Reports',      'icon' => 'bar-chart-3',      'url' => 'reports.php',      'roles' => ['admin'], 'perm' => 'reports:view'],
        ['name' => 'Services',     'icon' => 'key-round',       'url' => 'services.php',     'roles' => ['admin', 'reception', 'receptionist'], 'perm' => 'services:view'],
        ['name' => 'Website CMS',  'icon' => 'globe-2',          'url' => 'website_cms.php',  'roles' => ['admin'], 'perm' => 'settings:update'],
        ['name' => 'Settings',     'icon' => 'settings-2',       'url' => 'settings.php',     'roles' => ['admin'], 'perm' => 'settings:view'],
    ];

    $filtered = [];
    foreach ($links as $link) {
        $hasRole = in_array($role, $link['roles']);
        $hasPerm = isset($link['perm']) && hasPermission($link['perm']);
        
        // Special case for reports
        if ($link['url'] === 'reports.php' && hasPermissionPattern('/^reports:/')) {
            $hasPerm = true;
        }

        // Special case for services / reception hub
        if ($link['url'] === 'services.php' && hasPermission('reception:access')) {
            $hasPerm = true;
        }

        // Special case for overview dashboard
        if ($link['url'] === 'admin.php' && (
            hasPermission('orders:view') ||
            hasPermission('stock:view') ||
            hasPermissionPattern('/^reports:/')
        )) {
            $hasPerm = true;
        }

        if ($hasRole || $hasPerm) {
            $filtered[] = $link;
        }
    }
    return $filtered;
}

function renderTopNavLinks($role) {
    $links = getAdminNavLinks($role);
    $currentUrl = basename($_SERVER['SCRIPT_NAME']);

    foreach ($links as $link) {
        $active = ($currentUrl === $link['url']);
        $cls = $active
            ? 'px-4 py-2 text-sm font-semibold text-[#c5a059] bg-[#c5a059]/10 rounded-md transition-colors'
            : 'px-4 py-2 text-sm font-medium text-white/60 hover:text-[#c5a059] hover:bg-[#c5a059]/5 rounded-md transition-colors';
        echo "<a href='{$link['url']}' class='{$cls}'>{$link['name']}</a>";
    }
}

function renderPosNavLinks($activeTab = 'standard') {
    $tabs = [
        ['key' => 'standard', 'label' => 'Standard POS', 'url' => 'cashier.php'],
    ];

    foreach (getMenuTiers() as $tier) {
        $tabs[] = [
            'key' => $tier['id'],
            'label' => ($tier['name'] ?? 'VIP') . ' POS',
            'url' => 'cashier.php?tier=' . urlencode($tier['id']),
        ];
    }

    $tabs[] = ['key' => 'recent', 'label' => 'Recent Orders', 'url' => 'orders.php?view=recent'];

    foreach ($tabs as $tab) {
        $on = ($activeTab === $tab['key']);
        $cls = $on
            ? 'px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-[#c5a059] bg-[#c5a059]/10 rounded-md'
            : 'px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-white/50 hover:text-[#c5a059] hover:bg-[#c5a059]/5 rounded-md transition-colors';
        echo "<a href='{$tab['url']}' class='{$cls}'>{$tab['label']}</a>";
    }
}

function renderSidebarLinks($role) {
    if (empty($role)) {
        $user = getCurrentUser();
        $role = $user['role'] ?? 'guest';
    }
    
    $links = getAdminNavLinks($role);
    $currentUrl = basename($_SERVER['SCRIPT_NAME']);

    foreach ($links as $link) {
        $active = ($currentUrl === $link['url']) ? 'active' : '';
        echo "<a href='{$link['url']}' class='sidebar-link {$active}'>";
        echo "<i data-lucide='{$link['icon']}' class='w-4 h-4'></i>";
        echo "<span>{$link['name']}</span>";
        echo "</a>";
    }
}
?>
