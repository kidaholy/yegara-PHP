<?php
/**
 * Shared layout components for the PHP Management System
 */

require_once 'lang.php';
require_once 'auth.php';

function renderHeader($title = "Management System") {
    global $currentLang;
    $user = getCurrentUser();
    $appName = "Prime Addis"; // Default from layout.tsx
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title . " - " . $appName; ?></title>
        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Google Fonts: Inter, Playfair Display, and JetBrains Mono -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
        <!-- Geist Mono via CDN fallback -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist-mono@1.2.0/dist/index.css">
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
                background-image: 
                    radial-gradient(circle at 0% 0%, rgba(197, 160, 89, 0.03) 0%, transparent 40%),
                    radial-gradient(circle at 100% 100%, rgba(197, 160, 89, 0.03) 0%, transparent 40%);
            }

            .font-playfair { font-family: 'Playfair Display', serif; }
            .font-mono { font-family: 'JetBrains Mono', monospace; }

            /* Premium Animations */
            @keyframes pulse-glow {
                0% { box-shadow: 0 0 0 0 rgba(197, 160, 89, 0.4); }
                70% { box-shadow: 0 0 0 10px rgba(197, 160, 89, 0); }
                100% { box-shadow: 0 0 0 0 rgba(197, 160, 89, 0); }
            }
            .pulse-glow { animation: pulse-glow 2s infinite; }

            /* Smooth Page Transition */
            .page-enter {
                animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Obsidian Glass Components */
            .glass {
                background: rgba(21, 24, 23, 0.7);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(197, 160, 89, 0.1);
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4);
            }

            .sidebar-link {
                display: flex;
                align-items: center;
                gap: 0.875rem;
                padding: 0.875rem 1.25rem;
                border-radius: var(--radius);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                color: rgba(255, 255, 255, 0.4);
                font-size: 0.875rem;
                font-weight: 500;
            }

            .sidebar-link:hover {
                background-color: rgba(197, 160, 89, 0.08);
                color: #c5a059;
                transform: translateX(6px);
            }

            .sidebar-link.active {
                background-color: #c5a059;
                color: #0f1110;
                box-shadow: 0 12px 24px -6px rgba(197, 160, 89, 0.4);
                font-weight: 700;
            }

            /* Custom Gold Scrollbar */
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { 
                background: rgba(197, 160, 89, 0.3); 
                border-radius: 9999px; 
            }
            ::-webkit-scrollbar-thumb:hover { background: rgba(197, 160, 89, 0.6); }

            /* Atmospheric Mesh */
            .gold-mesh {
                background-image: radial-gradient(#c5a059 0.5px, transparent 0.5px);
                background-size: 32px 32px;
                opacity: 0.03;
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
    <body class="min-h-screen flex overflow-hidden selection:bg-primary/10 selection:text-primary">
        
        <?php if ($user): ?>
        <!-- Sidebar -->
        <aside class="w-[260px] glass h-screen flex flex-col border-r border-border sticky top-0 z-50">
            <div class="px-6 py-8">
                <h1 class="text-xl font-bold font-playfair tracking-tight text-white flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center">
                        <i data-lucide="hotel" class="w-5 h-5 text-slate-950"></i>
                    </div>
                    <?php echo $appName; ?>
                </h1>
            </div>

            <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
                <p class="px-4 py-2 text-[10px] font-bold uppercase tracking-wider text-muted-foreground opacity-50">Navigation</p>
                <?php renderSidebarLinks($user['role']); ?>
            </nav>

            <div class="p-6 border-t border-white/5 space-y-4">
                <div class="flex items-center gap-3 p-4 bg-white/5 rounded-2xl border border-white/5 mx-2">
                    <div class="w-10 h-10 rounded-xl bg-gold flex items-center justify-center text-charcoal font-black shadow-[0_0_15px_rgba(197,160,89,0.3)]">
                        <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate"><?php echo $user['name']; ?></p>
                        <p class="text-[10px] text-gold/50 truncate uppercase font-bold tracking-tight"><?php echo $user['role']; ?></p>
                    </div>
                </div>

                <!-- Language Switcher -->
                <div class="flex gap-2 px-2">
                    <?php 
                    $currentUrl = $_SERVER['REQUEST_URI'];
                    $cleanUrl = strtok($currentUrl, '?');
                    ?>
                    <a href="<?php echo $cleanUrl; ?>?lang=en" class="flex-1 py-1.5 text-[9px] font-black border border-white/5 rounded-lg text-center <?php echo $currentLang == 'en' ? 'bg-gold/10 text-gold border-gold/20' : 'text-white/40 hover:bg-white/5'; ?>">EN</a>
                    <a href="<?php echo $cleanUrl; ?>?lang=am" class="flex-1 py-1.5 text-[9px] font-black border border-white/5 rounded-lg text-center <?php echo $currentLang == 'am' ? 'bg-gold/10 text-gold border-gold/20' : 'text-white/40 hover:bg-white/5'; ?>">አማ</a>
                </div>

                <a href="logout.php" class="sidebar-link text-red-500/60 hover:text-red-400 font-bold text-xs px-4">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span><?php echo __('sign_out'); ?></span>
                </a>
            </div>
        </aside>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#030712]">
            <!-- Topbar -->
            <?php if ($user): ?>
            <header class="h-14 border-b border-white/5 bg-[#030712]/80 backdrop-blur-md flex items-center justify-between px-6 shrink-0 z-40">
                <div class="flex items-center gap-4">
                    <button class="lg:hidden p-2 text-muted-foreground hover:bg-white/5 rounded-md transition-colors"><i data-lucide="menu" class="w-4 h-4"></i></button>
                    <div class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                        <span class="opacity-50">Pages</span>
                        <i data-lucide="chevron-right" class="w-3 h-3 opacity-30"></i>
                        <span class="text-foreground"><?php echo $title; ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div id="connection-status" class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-emerald-500 bg-emerald-500/5 px-2.5 py-1 rounded-full border border-emerald-500/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                        Live
                    </div>
                    <button class="w-8 h-8 rounded-full border border-border flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-white/5 transition-all">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                    </button>
                </div>
            </header>
            <?php endif; ?>

            <div class="flex-1 overflow-y-auto p-6 md:p-8 relative page-enter">
    <?php
}

function renderFooter() {
    ?>
            </div>
        </main>

        <script>
            // Initialize Lucide Icons
            lucide.createIcons();
            
            // Basic connection status mock (replaces Socket.io indicator)
            window.addEventListener('online', () => {
                const status = document.getElementById('connection-status');
                if (status) status.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Connected';
            });
            window.addEventListener('offline', () => {
                const status = document.getElementById('connection-status');
                if (status) status.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500"></span> Offline';
            });
        </script>
    </body>
    </html>
    <?php
}

function renderSidebarLinks($role) {
    $links = [
        ['name' => __('dashboard'), 'icon' => 'layout-dashboard', 'url' => 'admin.php', 'roles' => ['admin']],
        ['name' => __('reception'), 'icon' => 'key-round', 'url' => 'reception.php', 'roles' => ['receptionist', 'admin']],
        ['name' => __('cashier_pos'), 'icon' => 'shopping-cart', 'url' => 'cashier.php', 'roles' => ['cashier', 'admin']],
        ['name' => __('kitchen'), 'icon' => 'utensils', 'url' => 'chef.php', 'roles' => ['chef', 'admin']],
        ['name' => __('bar_monitor'), 'icon' => 'beer', 'url' => 'bar.php', 'roles' => ['bar', 'admin']],
        ['name' => __('strategic_reports'), 'icon' => 'bar-chart-3', 'url' => 'reports.php', 'roles' => ['admin']],
        ['name' => __('staff_directory'), 'icon' => 'users', 'url' => 'staff.php', 'roles' => ['admin']],
        ['name' => __('menu_settings'), 'icon' => 'settings', 'url' => 'settings.php', 'roles' => ['admin']],
    ];

    $currentUrl = basename($_SERVER['SCRIPT_NAME']);

    foreach ($links as $link) {
        if (in_array($role, $link['roles'])) {
            $active = ($currentUrl === $link['url']) ? 'active' : '';
            echo "<a href='{$link['url']}' class='sidebar-link {$active}'>";
            echo "<i data-lucide='{$link['icon']}' class='w-4 h-4'></i>";
            echo "<span>{$link['name']}</span>";
            echo "</a>";
        }
    }
}
?>
