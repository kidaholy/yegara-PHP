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
                --background: 222.2 84% 4.9%;
                --foreground: 210 40% 98%;
                --card: 222.2 84% 4.9%;
                --card-foreground: 210 40% 98%;
                --popover: 222.2 84% 4.9%;
                --popover-foreground: 210 40% 98%;
                --primary: 210 40% 98%;
                --primary-foreground: 222.2 47.4% 11.2%;
                --secondary: 217.2 32.6% 17.5%;
                --secondary-foreground: 210 40% 98%;
                --muted: 217.2 32.6% 17.5%;
                --muted-foreground: 215 20.2% 65.1%;
                --accent: 217.2 32.6% 17.5%;
                --accent-foreground: 210 40% 98%;
                --destructive: 0 62.8% 30.6%;
                --destructive-foreground: 210 40% 98%;
                --border: 217.2 32.6% 17.5%;
                --input: 217.2 32.6% 17.5%;
                --ring: 212.7 26.8% 83.9%;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: hsl(var(--background));
                color: hsl(var(--foreground));
            }

            .font-playfair { font-family: 'Playfair Display', serif; }

            /* Modern Glassmorphism & Premium UI */
            .glass {
                background: rgba(255, 255, 255, 0.03);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.05);
            }

            .sidebar-link {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
                border-radius: 0.5rem;
                transition: all 0.2s;
                color: hsl(var(--muted-foreground));
            }

            .sidebar-link:hover, .sidebar-link.active {
                background-color: hsl(var(--accent));
                color: hsl(var(--accent-foreground));
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar { width: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: hsl(var(--muted)); border-radius: 10px; }
        </style>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            background: "hsl(var(--background))",
                            foreground: "hsl(var(--foreground))",
                            primary: "hsl(var(--primary))",
                            secondary: "hsl(var(--secondary))",
                            muted: "hsl(var(--muted))",
                            accent: "hsl(var(--accent))",
                            border: "hsl(var(--border))",
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="min-h-screen flex overflow-hidden">
        
        <?php if ($user): ?>
        <!-- Sidebar -->
        <aside class="w-64 glass h-screen flex flex-col border-r border-border sticky top-0">
            <div class="p-6">
                <h1 class="text-xl font-bold font-playfair tracking-tight text-white flex items-center gap-2">
                    <i data-lucide="hotel" class="w-6 h-6"></i>
                    <?php echo $appName; ?>
                </h1>
            </div>

            <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
                <?php renderSidebarLinks($user['role']); ?>
            </nav>

            <div class="p-4 border-t border-border space-y-2">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                        <i data-lucide="user" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?php echo $user['name']; ?></p>
                        <p class="text-xs text-muted-foreground truncate uppercase"><?php echo $user['role']; ?></p>
                    </div>
                </div>
                <a href="/logout.php" class="sidebar-link text-red-400 hover:bg-red-500/10 hover:text-red-400">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- Topbar (Optional) -->
            <?php if ($user): ?>
            <header class="h-16 border-b border-border glass flex items-center justify-between px-8 shrink-0">
                <div class="flex items-center gap-4">
                    <button class="lg:hidden p-2 text-muted-foreground"><i data-lucide="menu"></i></button>
                    <h2 class="text-sm font-medium text-muted-foreground"><?php echo $title; ?></h2>
                </div>
                <div class="flex items-center gap-4">
                    <div id="connection-status" class="flex items-center gap-2 text-xs text-green-500">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        Connected
                    </div>
                </div>
            </header>
            <?php endif; ?>

            <div class="flex-1 overflow-y-auto p-8 relative">
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
