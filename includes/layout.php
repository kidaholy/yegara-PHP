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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&family=Great+Vibes&display=swap" rel="stylesheet">
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
    <body class="min-h-screen flex flex-col selection:bg-primary/10 selection:text-primary">
        
        <?php if ($user): ?>
        <!-- Top Navigation Bar -->
        <nav class="h-[60px] bg-[#111413] border-b border-white/5 flex items-center justify-between px-6 shrink-0 z-50 sticky top-0">
            <!-- Logo -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#1a1209] border-2 border-[#c5a059] flex flex-col items-center justify-center shadow-lg flex-shrink-0">
                    <span class="text-[7px] font-black tracking-widest text-[#c5a059] leading-none">ABE</span>
                    <span class="text-[5px] font-bold tracking-[0.2em] text-[#c5a059] leading-none">HOTEL</span>
                </div>
                <div>
                    <h1 class="text-[#c5a059] font-black text-lg italic leading-none tracking-tight">ABE HOTEL</h1>
                    <p class="text-[7px] text-[#c5a059]/40 font-bold uppercase tracking-widest leading-none mt-0.5">ምርጥ አገልግሎት ለመስጠት</p>
                </div>
            </div>

            <!-- Nav Links -->
            <div class="hidden md:flex items-center gap-1">
                <?php renderTopNavLinks($user['role']); ?>
            </div>

            <!-- Right Side -->
            <div class="flex items-center gap-4">
                <!-- Notification Bell -->
                <div class="relative">
                    <button class="w-9 h-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 hover:text-white hover:bg-white/10 transition-all">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                    </button>
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-[#c5a059] text-black text-[8px] font-black rounded-full flex items-center justify-center">0</span>
                </div>

                <!-- User Avatar -->
                <div class="w-9 h-9 rounded-full bg-[#2a2a2a] border border-white/10 flex items-center justify-center text-white font-black text-sm">
                    <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                </div>

                <!-- User Greeting -->
                <span class="hidden lg:block text-[11px] font-black uppercase tracking-widest text-white/60">
                    Hi, <?php echo strtoupper(explode(' ', $user['name'])[0]); ?>! 
                    <span class="text-[#c5a059]">→</span>
                </span>

                <!-- Logout -->
                <a href="logout.php" class="px-5 py-2 bg-red-600/90 hover:bg-red-600 text-white text-[10px] font-black uppercase tracking-widest rounded-full transition-all shadow-lg">
                    Logout
                </a>
            </div>
        </nav>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="flex-1 flex bg-[#0f1110] relative">
            <div class="absolute inset-0 gold-mesh pointer-events-none"></div>
            
            <div class="flex-1 flex relative page-enter">
    <?php
}

function renderFooter() {
    ?>
            </div>
        </main>

        <script>
            lucide.createIcons();
        </script>
    </body>
    </html>
    <?php
}

function renderTopNavLinks($role) {
    $links = [
        ['name' => 'Overview',  'url' => 'admin.php',    'roles' => ['admin']],
        ['name' => 'Orders',    'url' => 'orders.php',   'roles' => ['admin', 'cashier']],
        ['name' => 'Users',     'url' => 'staff.php',    'roles' => ['admin']],
        ['name' => 'Store',     'url' => 'settings.php', 'roles' => ['admin']],
        ['name' => 'Stock',     'url' => 'reports.php',  'roles' => ['admin']],
        ['name' => 'Reports',   'url' => 'reports.php',  'roles' => ['admin']],
        ['name' => 'Services',  'url' => 'reception.php','roles' => ['admin', 'reception']],
        ['name' => 'Settings',  'url' => 'settings.php', 'roles' => ['admin']],
    ];

    $currentUrl = basename($_SERVER['SCRIPT_NAME']);

    foreach ($links as $link) {
        if (in_array($role, $link['roles'])) {
            $active = ($currentUrl === $link['url']);
            $cls = $active
                ? 'px-4 py-2 text-[11px] font-black uppercase tracking-widest text-[#c5a059] border-b-2 border-[#c5a059] bg-[#c5a059]/5 rounded-t-md'
                : 'px-4 py-2 text-[11px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors rounded-md hover:bg-white/5';
            echo "<a href='{$link['url']}' class='{$cls}'>{$link['name']}</a>";
        }
    }
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
