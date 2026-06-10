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
                <div class="w-10 h-10 rounded-lg bg-white/10 border border-white/20 flex flex-col items-center justify-center flex-shrink-0">
                    <span class="text-[10px] font-bold tracking-widest text-white leading-none">ABE</span>
                </div>
                <div>
                    <h1 class="text-white font-bold text-lg leading-none tracking-tight mt-0.5">ABE HOTEL</h1>
                    <p class="text-[9px] text-white/50 font-medium uppercase tracking-wider leading-none mt-1">Admin Dashboard</p>
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
                <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-blue-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">0</span>
                </div>

                <!-- User Avatar -->
                <div class="w-9 h-9 rounded-full bg-white/10 border border-white/20 flex items-center justify-center text-white font-bold text-sm">
                    <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                </div>

                <!-- User Greeting -->
                <span class="hidden lg:block text-sm font-medium text-white/80">
                    Hi, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>
                </span>

                <!-- Logout -->
                <a href="logout.php" class="px-4 py-2 bg-white/10 hover:bg-red-500/80 hover:text-white text-white text-sm font-medium rounded-lg transition-colors border border-white/10">
                    Logout
                </a>
            </div>
        </nav>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="flex-1 flex bg-[#0f1110] relative">
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
        ['name' => 'Store',     'url' => 'store.php',    'roles' => ['admin', 'store_keeper']],
        ['name' => 'Stock',     'url' => 'stock.php',    'roles' => ['admin']],
        ['name' => 'Reports',   'url' => 'reports.php',  'roles' => ['admin']],
        ['name' => 'Services',  'url' => 'services.php','roles' => ['admin', 'reception']],
        ['name' => 'Settings',  'url' => 'settings.php', 'roles' => ['admin']],
    ];

    $currentUrl = basename($_SERVER['SCRIPT_NAME']);

    foreach ($links as $link) {
        if (in_array($role, $link['roles'])) {
            $active = ($currentUrl === $link['url']);
            $cls = $active
                ? 'px-4 py-2 text-sm font-semibold text-[#c5a059] bg-[#c5a059]/10 rounded-md transition-colors'
                : 'px-4 py-2 text-sm font-medium text-white/60 hover:text-[#c5a059] hover:bg-[#c5a059]/5 rounded-md transition-colors';
            echo "<a href='{$link['url']}' class='{$cls}'>{$link['name']}</a>";
        }
    }
}

function renderSidebarLinks($role) {
    $links = [
        ['name' => __('dashboard'), 'icon' => 'layout-dashboard', 'url' => 'admin.php', 'roles' => ['admin']],
        ['name' => __('reception'), 'icon' => 'key-round', 'url' => 'services.php', 'roles' => ['receptionist', 'admin']],
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
