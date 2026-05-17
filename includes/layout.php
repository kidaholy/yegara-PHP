<?php
/**
 * Shared layout components for the PHP Management System
 */

require_once 'auth.php';

function renderHeader($title = "Management System") {
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
        <!-- Google Fonts: Inter and Playfair Display -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
        <!-- Lucide Icons -->
        <script src="https://unpkg.com/lucide@latest"></script>
        <style>
            :root {
                --background: 222 47% 4%;
                --foreground: 213 31% 91%;
                --muted: 223 47% 11%;
                --muted-foreground: 215.4 16.3% 56.9%;
                --accent: 216 34% 17%;
                --accent-foreground: 210 40% 98%;
                --popover: 224 71% 4%;
                --popover-foreground: 215 20.2% 65.1%;
                --border: 216 34% 17%;
                --input: 216 34% 17%;
                --card: 222 47% 4%;
                --card-foreground: 213 31% 91%;
                --primary: 210 40% 98%;
                --primary-foreground: 222.2 47.4% 11.2%;
                --secondary: 222.2 47.4% 11.2%;
                --secondary-foreground: 210 40% 98%;
                --destructive: 0 63% 31%;
                --destructive-foreground: 210 40% 98%;
                --ring: 216 34% 17%;
                --radius: 0.75rem;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: hsl(var(--background));
                color: hsl(var(--foreground));
                -webkit-font-smoothing: antialiased;
            }

            .font-playfair { font-family: 'Playfair Display', serif; }

            /* Smooth Page Transition */
            .page-enter {
                animation: fadeIn 0.4s ease-out;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(5px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Radix-UI like Sidebar and Cards */
            .glass {
                background: rgba(3, 7, 18, 0.5);
                backdrop-filter: blur(12px);
                border: 1px solid hsl(var(--border));
            }

            .sidebar-link {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.625rem 0.875rem;
                border-radius: var(--radius);
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                color: hsl(var(--muted-foreground));
                font-size: 0.875rem;
                font-weight: 500;
            }

            .sidebar-link:hover {
                background-color: hsl(var(--accent));
                color: hsl(var(--foreground));
            }

            .sidebar-link.active {
                background-color: hsl(var(--accent));
                color: hsl(var(--foreground));
                box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.4);
            }

            /* Custom scrollbar matching Radix scroll-area */
            ::-webkit-scrollbar { width: 8px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { 
                background: hsl(var(--muted)); 
                border-radius: 9999px; 
                border: 2px solid hsl(var(--background));
            }
            ::-webkit-scrollbar-thumb:hover { background: hsl(var(--accent)); }
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

            <div class="p-4 border-t border-border space-y-2">
                <div class="flex items-center gap-3 px-3 py-3 rounded-lg bg-white/5 border border-white/5">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
                        <span class="text-xs font-bold"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate text-white"><?php echo $user['name']; ?></p>
                        <p class="text-[10px] text-muted-foreground truncate uppercase font-bold tracking-tight"><?php echo $user['role']; ?></p>
                    </div>
                </div>
                <a href="/logout.php" class="sidebar-link text-red-400/80 hover:bg-red-500/10 hover:text-red-400 group">
                    <i data-lucide="log-out" class="w-4 h-4 transition-transform group-hover:-translate-x-1"></i>
                    <span>Log Out</span>
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
        'admin' => [
            ['icon' => 'layout-dashboard', 'label' => 'Dashboard', 'url' => '/admin.php'],
            ['icon' => 'shopping-cart', 'label' => 'Orders', 'url' => '/admin-orders.php'],
            ['icon' => 'pie-chart', 'label' => 'Reports', 'url' => '/admin-reports.php'],
            ['icon' => 'users', 'label' => 'Staff', 'url' => '/admin-staff.php'],
            ['icon' => 'settings', 'label' => 'Settings', 'url' => '/admin-settings.php'],
        ],
        'cashier' => [
            ['icon' => 'plus-circle', 'label' => 'New Order', 'url' => '/cashier.php'],
            ['icon' => 'list', 'label' => 'My Orders', 'url' => '/cashier-orders.php'],
        ],
        'chef' => [
            ['icon' => 'utensils', 'label' => 'Kitchen', 'url' => '/chef.php'],
        ],
        'bar' => [
            ['icon' => 'beer', 'label' => 'Bar', 'url' => '/bar.php'],
        ]
    ];

    $roleLinks = $links[$role] ?? [];
    $currentUrl = $_SERVER['SCRIPT_NAME'];

    foreach ($roleLinks as $link) {
        $active = ($currentUrl === $link['url']) ? 'active' : '';
        echo "<a href='{$link['url']}' class='sidebar-link {$active}'>";
        echo "<i data-lucide='{$link['icon']}' class='w-5 h-5'></i>";
        echo "<span>{$link['label']}</span>";
        echo "</a>";
    }
}
?>
