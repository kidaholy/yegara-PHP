<?php
/**
 * Admin Dashboard - Abe Hotel
 * High-Fidelity "Luxury-First" Edition (Spec-Corrected)
 */
require_once 'includes/layout.php';
requireAuth(['admin']); // spec: ProtectedRoute requiredRoles=["admin"]
$title = "Admin Dashboard";

renderHeader($title);
?>

<div class="min-h-screen w-full bg-[#0f1110] p-6 lg:p-12 flex justify-center">
    <div class="max-w-screen-2xl w-full space-y-12">
        
        <!-- SECTION 1: HEADER -->
        <div class="glass p-10 rounded-[3rem] border border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-8 bg-[#151716]/40 shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
            <div class="flex items-center gap-8">
                <!-- Icon Box -->
                <div class="w-20 h-20 rounded-3xl bg-[#1a1c1b] border border-[#d4af37]/20 flex items-center justify-center text-[#d4af37] shadow-inner relative group">
                    <div class="absolute inset-0 bg-[#d4af37]/5 rounded-3xl blur-xl group-hover:bg-[#d4af37]/10 transition-all"></div>
                    <i data-lucide="bar-chart-3" class="w-10 h-10 relative z-10"></i>
                </div>
                <div>
                    <h1 class="text-4xl lg:text-5xl font-black font-playfair italic text-[#f3cf7a] leading-tight">Admin Dashboard</h1>
                    <p class="text-[11px] uppercase font-black tracking-[0.5em] text-gray-500 mt-2 opacity-60">Business Intelligence & Performance Hub</p>
                </div>
            </div>
            
            <div class="flex items-center gap-6">
                <div id="last-updated" class="text-[10px] font-black uppercase tracking-widest text-[#d4af37]/40 hidden lg:block">
                    Last update: synchronizing...
                </div>
                <button id="refresh-btn" class="w-14 h-14 rounded-2xl bg-white/5 border border-white/5 flex items-center justify-center text-white/50 hover:bg-white/10 hover:text-white transition-all group active:scale-90 shadow-2xl backdrop-blur-md">
                    <i data-lucide="refresh-cw" class="w-6 h-6 group-hover:rotate-180 transition-transform duration-700 ease-in-out"></i>
                </button>
            </div>
        </div>

        <!-- SECTION 2: KEY METRICS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- Metric Card: Today's Revenue -->
            <div id="today-revenue" class="metric-card glass p-10 rounded-[3rem] border border-white/5 bg-[#151716] shadow-2xl group hover:border-[#d4af37]/30 transition-all duration-500">
                <div class="metric-icon-box inline-flex p-4 rounded-2xl bg-[#1a1712] text-[#d4af37] border border-[#d4af37]/20 mb-10 transition-transform group-hover:scale-110">
                    <i data-lucide="dollar-sign" class="w-6 h-6"></i>
                </div>
                <p class="text-[11px] uppercase font-black tracking-[0.2em] text-gray-500 mb-3">Today's Revenue</p>
                <p class="metric-value text-5xl font-black font-playfair italic text-[#f3cf7a] leading-none">---</p>
                <div class="w-16 h-1 bg-[#d4af37]/20 rounded-full mt-6 group-hover:w-24 transition-all duration-500"></div>
            </div>

            <!-- Metric Card: Total Orders -->
            <div id="total-orders" class="metric-card glass p-10 rounded-[3rem] border border-white/5 bg-[#151716] shadow-2xl group hover:border-[#d4af37]/30 transition-all duration-500">
                <div class="metric-icon-box inline-flex p-4 rounded-2xl bg-[#1a1712] text-[#d4af37] border border-[#d4af37]/20 mb-10 transition-transform group-hover:scale-110">
                    <i data-lucide="shopping-cart" class="w-6 h-6"></i>
                </div>
                <p class="text-[11px] uppercase font-black tracking-[0.2em] text-gray-500 mb-3">Total Orders</p>
                <p class="metric-value text-5xl font-black font-playfair italic text-[#f3cf7a] leading-none">-</p>
                <p class="metric-subtext text-xs text-gray-600 font-light pt-4 italic">calculating metrics...</p>
            </div>

            <!-- Metric Card: Average Order -->
            <div id="avg-order" class="metric-card glass p-10 rounded-[3rem] border border-white/5 bg-[#151716] shadow-2xl group hover:border-[#d4af37]/30 transition-all duration-500">
                <div class="metric-icon-box inline-flex p-4 rounded-2xl bg-[#1a1712] text-[#d4af37] border border-[#d4af37]/20 mb-10 transition-transform group-hover:scale-110">
                    <i data-lucide="trending-up" class="w-6 h-6"></i>
                </div>
                <p class="text-[11px] uppercase font-black tracking-[0.2em] text-gray-500 mb-3">Average Order</p>
                <p class="metric-value text-5xl font-black font-playfair italic text-[#f3cf7a] leading-none">---</p>
                <div class="w-16 h-1 bg-[#d4af37]/20 rounded-full mt-6 group-hover:w-24 transition-all duration-500"></div>
            </div>

            <!-- Metric Card: Stock Alerts -->
            <div id="stock-alerts" class="metric-card glass p-10 rounded-[3rem] border border-white/5 bg-[#151716] shadow-2xl group hover:border-red-500/20 transition-all duration-500">
                <div class="metric-icon-box inline-flex p-4 rounded-2xl bg-[#1a1c1b] text-gray-500 border border-white/5 mb-10 transition-transform group-hover:scale-110">
                    <i data-lucide="package" class="w-6 h-6"></i>
                </div>
                <p class="text-[11px] uppercase font-black tracking-[0.2em] text-gray-500 mb-3">Stock Alerts</p>
                <p class="metric-value text-5xl font-black font-playfair italic text-[#f3cf7a] leading-none">-</p>
                <div class="w-16 h-1 bg-white/5 rounded-full mt-6 group-hover:w-24 transition-all duration-500"></div>
            </div>

        </div>

        <!-- SECTION 3: QUICK ACTIONS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
            
            <a href="reports.php" class="group h-full">
                <div class="glass p-10 rounded-[3rem] border border-white/10 bg-[#151716] shadow-2xl hover:shadow-[0_4px_40px_rgba(212,175,55,0.15)] hover:border-[#d4af37]/40 transition-all flex flex-col h-full active:scale-[0.98]">
                    <div class="flex items-start justify-between mb-10">
                        <i data-lucide="bar-chart-3" class="w-12 h-12 text-[#d4af37] transition-transform group-hover:scale-110 duration-700 group-hover:rotate-3"></i>
                        <div class="p-2 rounded-full bg-white/5 group-hover:bg-[#d4af37]/20 transition-colors">
                            <i data-lucide="arrow-up-right" class="w-5 h-5 text-white/10 group-hover:text-[#d4af37] transition-colors"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-3">View Reports</h3>
                    <p class="text-[11px] text-gray-500 uppercase font-black tracking-widest opacity-80">Full Sales & Strategic Analytics</p>
                </div>
            </a>

            <a href="reports.php#inventory" class="group h-full">
                <div class="glass p-10 rounded-[3rem] border border-white/10 bg-[#151716] shadow-2xl hover:shadow-[0_4px_40px_rgba(212,175,55,0.2)] hover:border-[#d4af37]/40 transition-all flex flex-col h-full active:scale-[0.98]">
                    <div class="flex items-start justify-between mb-10">
                        <i data-lucide="package" class="w-12 h-12 text-[#d4af37] transition-transform group-hover:scale-110 duration-700 group-hover:rotate-3"></i>
                        <div class="p-2 rounded-full bg-white/5 group-hover:bg-[#d4af37]/20 transition-colors">
                            <i data-lucide="arrow-up-right" class="w-5 h-5 text-white/10 group-hover:text-[#d4af37] transition-colors"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-3">Manage Stock</h3>
                    <p class="text-[11px] text-gray-500 uppercase font-black tracking-widest opacity-80">Live Inventory Audit & Controls</p>
                </div>
            </a>

            <a href="reception.php" class="group h-full">
                <div class="glass p-10 rounded-[3rem] border border-white/10 bg-[#151716] shadow-2xl hover:shadow-[0_4px_40px_rgba(212,175,55,0.15)] hover:border-[#d4af37]/40 transition-all flex flex-col h-full active:scale-[0.98]">
                    <div class="flex items-start justify-between mb-10">
                        <i data-lucide="key-round" class="w-12 h-12 text-[#d4af37] transition-transform group-hover:scale-110 duration-700 group-hover:rotate-3"></i>
                        <div class="p-2 rounded-full bg-white/5 group-hover:bg-[#d4af37]/20 transition-colors">
                            <i data-lucide="arrow-up-right" class="w-5 h-5 text-white/10 group-hover:text-[#d4af37] transition-colors"></i>
                        </div>
                    </div>
                    <h3 class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-3">Services</h3>
                    <p class="text-[11px] text-gray-500 uppercase font-black tracking-widest opacity-80">Room, Floor & Customer Workflow</p>
                </div>
            </a>

        </div>
>

        <!-- SECTION 4: STOCK ALERTS (Conditional via JS) -->
        <div id="stock-alerts-panel" class="hidden animate-in fade-in slide-in-from-bottom-5 duration-700">
            <div class="glass p-10 rounded-[3rem] border border-red-900/50 bg-[#1a0f0f] shadow-2xl shadow-red-900/20">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500">
                        <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-black font-playfair italic text-red-500 leading-none">Stock Alerts <span class="alerts-count"></span></h3>
                        <p class="text-[9px] uppercase font-black tracking-widest text-red-500/40 mt-2">Critical Inventory Shortages</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 alerts-list">
                    <!-- Alerts injected here -->
                </div>
            </div>
        </div>

    </div>
</div>

<script src="public/js/admin-dashboard.js"></script>

<style>
.glass { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); }
.metric-card { transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
</style>

<?php renderFooter(); ?>
