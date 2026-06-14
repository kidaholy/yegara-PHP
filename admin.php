<?php
/**
 * Admin Dashboard - Abe Hotel
 * High-Fidelity "Luxury-First" Edition (Spec-Corrected)
 */
require_once 'includes/layout.php';
    requireAuth(['admin'], ['overview:view', 'reports:financial_summary', 'stock:view', 'orders:view']);
    
    $title = "Admin Dashboard";
    renderHeader($title);
    ?>
    
    <div class="min-h-screen w-full bg-[#0f1110] p-6 lg:p-12 flex justify-center">
        <div class="max-w-screen-2xl w-full space-y-12">
        
        <!-- SECTION 1: HEADER -->
        <?php if (hasPermission('overview:view')): ?>
        <div class="glass p-8 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-8 bg-gray-900/40">
            <div class="flex items-center gap-6">
                <!-- Icon Box -->
                <div class="w-16 h-16 rounded-xl bg-gray-800 border border-gray-700 flex items-center justify-center text-blue-400">
                    <i data-lucide="bar-chart-3" class="w-8 h-8"></i>
                </div>
                <div>
                    <h1 class="text-3xl lg:text-4xl font-bold text-white leading-tight mt-1">Admin Dashboard</h1>
                    <p class="text-sm font-medium text-gray-400 mt-2">Business Intelligence & Performance Hub</p>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <div id="last-updated" class="text-xs font-semibold text-gray-500 hidden lg:block">
                    Last update: synchronizing...
                </div>
                <button id="refresh-btn" class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:bg-white/10 hover:text-white transition-colors active:scale-95">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="flex items-center justify-between mb-8">
            <div class="space-y-1">
                <h1 class="text-3xl font-black text-white italic font-playfair tracking-tight gold-glow">Financial Overview</h1>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/40">Strategic Real-time Metrics</p>
            </div>
            <button id="refresh-btn" class="p-4 bg-white/5 border border-white/10 rounded-2xl text-[#d4af37]">
                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- SECTION 2: KEY METRICS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <?php if (hasPermission('overview:view') || hasPermission('reports:financial_summary')): ?>
            <!-- Metric Card: Today's Revenue -->
            <div id="today-revenue" class="metric-card glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Today's Revenue</p>
                    <div class="metric-icon-box inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="dollar-sign" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="metric-value text-3xl font-bold text-white leading-none tracking-tight">---</p>
            </div>

            <!-- Metric Card: Average Order -->
            <div id="avg-order" class="metric-card glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Average Order</p>
                    <div class="metric-icon-box inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="trending-up" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="metric-value text-3xl font-bold text-white leading-none tracking-tight">---</p>
            </div>
            <?php endif; ?>

            <?php if (hasPermission('overview:view') || hasPermission('orders:view')): ?>
            <!-- Metric Card: Active Orders -->
            <div id="active-orders" class="metric-card glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Active Orders</p>
                    <div class="metric-icon-box inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="bell-ring" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="metric-value text-3xl font-bold text-white leading-none tracking-tight">-</p>
            </div>
            <?php endif; ?>

            <?php if (hasPermission('overview:view') || hasPermission('stock:view')): ?>
            <!-- Metric Card: Stock Alerts -->
            <div id="stock-alerts" class="metric-card glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Stock Alerts</p>
                    <div class="metric-icon-box inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-gray-400">
                        <i data-lucide="package" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="metric-value text-3xl font-bold text-white leading-none tracking-tight">-</p>
            </div>
            <?php endif; ?>

        </div>

        <!-- SECTION 3: QUICK ACTIONS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
            
            <?php if (hasPermission('reports:view') || hasPermissionPattern('/^reports:/')): ?>
            <a href="reports.php" class="group h-full">
                <div class="glass p-8 rounded-2xl border border-gray-700/50 bg-gray-800/60 hover:bg-gray-800 transition-colors flex flex-col h-full">
                    <div class="flex items-start justify-between mb-8">
                        <i data-lucide="bar-chart-3" class="w-8 h-8 text-blue-400"></i>
                        <i data-lucide="arrow-right" class="w-5 h-5 text-gray-500 group-hover:text-blue-400 transition-colors group-hover:translate-x-1"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">View Reports</h3>
                    <p class="text-sm text-gray-400">Full Sales & Strategic Analytics</p>
                </div>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('stock:view')): ?>
            <a href="reports.php#inventory" class="group h-full">
                <div class="glass p-8 rounded-2xl border border-gray-700/50 bg-gray-800/60 hover:bg-gray-800 transition-colors flex flex-col h-full">
                    <div class="flex items-start justify-between mb-8">
                        <i data-lucide="package" class="w-8 h-8 text-blue-400"></i>
                        <i data-lucide="arrow-right" class="w-5 h-5 text-gray-500 group-hover:text-blue-400 transition-colors group-hover:translate-x-1"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Manage Stock</h3>
                    <p class="text-sm text-gray-400">Live Inventory Audit & Controls</p>
                </div>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('services:view')): ?>
            <a href="services.php" class="group h-full">
                <div class="glass p-8 rounded-2xl border border-gray-700/50 bg-gray-800/60 hover:bg-gray-800 transition-colors flex flex-col h-full">
                    <div class="flex items-start justify-between mb-8">
                        <i data-lucide="key-round" class="w-8 h-8 text-blue-400"></i>
                        <i data-lucide="arrow-right" class="w-5 h-5 text-gray-500 group-hover:text-blue-400 transition-colors group-hover:translate-x-1"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Services</h3>
                    <p class="text-sm text-gray-400">Room, Floor & Customer Workflow</p>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="https://s16387.fra1.stableserver.net:2096/cpsess6687300317/3rdparty/roundcube/?_task=mail&_mbox=INBOX" target="_blank" class="group h-full">
                <div class="glass p-8 rounded-2xl border border-[#c5a059]/20 bg-[#c5a059]/5 hover:bg-[#c5a059]/10 transition-colors flex flex-col h-full">
                    <div class="flex items-start justify-between mb-8">
                        <i data-lucide="mail" class="w-8 h-8 text-[#c5a059]"></i>
                        <i data-lucide="external-link" class="w-5 h-5 text-gray-500 group-hover:text-[#c5a059] transition-colors group-hover:translate-x-1"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Check Email</h3>
                    <p class="text-sm text-gray-400">Official Hotel Webmail Access</p>
                </div>
            </a>
            <?php endif; ?>

        </div>
        </div>
    </div>
</div>

<script src="public/js/admin-dashboard.js"></script>

<style>
/* Extracted layout styles are now in layout.php directly */
</style>

<?php renderFooter(); ?>
