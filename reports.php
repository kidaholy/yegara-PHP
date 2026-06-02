<?php
/**
 * Admin Reports Hub — Business Intelligence (6 slides)
 * Consistent with Next.js Dark Luxury UX
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';

// Auth & Permissions
requireAuth(['admin', 'reception', 'store', 'cashier']);
$user = getCurrentUser();
$isAdmin = ($user['role'] === 'admin');

// Next.js equivalent permissions (Simplified for PHP/JsonDB context)
// In a real Laravel/RBAC system, these would be in a DB.
// Here we simulate the specific permission check mentioned in the prompt.
$userPermissions = $user['permissions'] ?? [];

$allSlides = [
    ['id' => "financial", 'label' => "Financial Summary", 'permission' => "reports:financial_summary", 'icon' => 'trending-up'],
    ['id' => "orders", 'label' => "Order History", 'permission' => "reports:order_history", 'icon' => 'shopping-bag'],
    ['id' => "inventory", 'label' => "Inventory Investment", 'permission' => "reports:inventory_investment", 'icon' => 'package'],
    ['id' => "store", 'label' => "Store Investment", 'permission' => "reports:store_investment", 'icon' => 'warehouse'],
    ['id' => "menu-sales", 'label' => "Menu Item Sales", 'permission' => "reports:menu_item_sales", 'icon' => 'bar-chart-2'],
    ['id' => "cashier-insights", 'label' => "Cashier Insights", 'permission' => "reports:cashier_insights", 'icon' => 'users'],
];

$slides = [];
if ($isAdmin || in_array('reports:view', $userPermissions)) {
    $slides = $allSlides;
} else {
    foreach ($allSlides as $s) {
        if (in_array($s['permission'], $userPermissions)) {
            $slides[] = $s;
        }
    }
}

renderHeader("Business Intelligence");
?>

<script>
  window.reportSlides = <?= json_encode($slides) ?>;
  window.userPermissions = <?= json_encode($userPermissions) ?>;
  window.companyName = "Abe Hotel & POS";
</script>

<div class="min-h-screen bg-[#0f1110] text-gray-300 font-sans selection:bg-[#d4af37]/30">
    <div class="max-w-[1600px] mx-auto px-4 lg:px-10 py-10">
        
        <!-- HEADER CARD -->
        <div class="glass p-8 rounded-[2.5rem] border border-white/5 bg-[#151716] shadow-2xl mb-8 relative overflow-hidden group">
            <div class="absolute -right-10 -top-10 opacity-[0.03] group-hover:rotate-12 transition-transform duration-1000 pointer-events-none">
                <i data-lucide="bar-chart-3" class="w-64 h-64 text-[#d4af37]"></i>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 relative z-10">
                <div>
                    <h1 class="text-4xl font-black font-playfair italic text-[#f3cf7a] leading-tight">Business Intelligence</h1>
                    <p id="slide-subtitle" class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-500 mt-2">Consolidated reports · Financial Summary</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Range Pills -->
                    <div class="flex bg-black/40 p-1.5 rounded-2xl border border-white/5">
                        <?php foreach(['today', 'week', 'month', 'year'] as $r): ?>
                        <button onclick="ReportHub.setTimeRange('<?= $r ?>')" id="range-btn-<?= $r ?>"
                            class="range-btn px-5 py-2.5 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all <?= $r==='week' ? 'bg-[#d4af37] text-black shadow-lg shadow-[#d4af37]/20' : 'text-gray-500 hover:text-white' ?>">
                            <?= $r ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Custom Date -->
                    <div class="relative group">
                        <input type="date" id="custom-date-picker" onchange="ReportHub.setCustomDate(this.value)"
                            class="bg-[#0f1110] border border-white/10 rounded-2xl px-5 py-2.5 text-[10px] font-black text-white focus:border-[#d4af37]/40 outline-none w-44 transition-all uppercase tracking-widest cursor-pointer">
                        <i data-lucide="calendar" class="absolute right-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500 pointer-events-none"></i>
                    </div>

                    <!-- Print -->
                    <button onclick="window.print()" class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:bg-white/10 transition-all shadow-xl">
                        <i data-lucide="printer" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-10">
            <!-- SIDE TABS (Desktop) -->
            <aside class="hidden md:flex flex-col w-64 space-y-2 sticky top-24 h-fit">
                <?php foreach($slides as $idx => $s): ?>
                <button onclick="ReportHub.goToSlide(<?= $idx ?>)" data-idx="<?= $idx ?>"
                    class="report-nav-btn group flex items-center gap-4 px-6 py-5 rounded-[1.5rem] border transition-all text-left <?= $idx === 0 ? 'bg-gradient-to-r from-[#1a1712] to-[#151716] border-[#d4af37]/30 text-[#f3cf7a] shadow-xl' : 'bg-transparent border-transparent text-gray-500 hover:text-gray-300' ?>">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center border transition-all <?= $idx === 0 ? 'bg-[#d4af37] border-[#d4af37] text-black' : 'bg-white/5 border-white/5 group-hover:border-white/10' ?>">
                        <i data-lucide="<?= $s['icon'] ?>" class="w-4 h-4"></i>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest"><?= $s['label'] ?></span>
                </button>
                <?php endforeach; ?>

                <?php if (empty($slides)): ?>
                    <p class="text-[10px] uppercase font-black text-red-400 p-6 text-center border border-red-400/20 rounded-3xl">No report sections available. Please check permissions.</p>
                <?php endif; ?>
            </aside>

            <!-- MOBILE TABS -->
            <div class="md:hidden flex overflow-x-auto no-scrollbar gap-2 pb-4 mb-4">
                <?php foreach($slides as $idx => $s): ?>
                <button onclick="ReportHub.goToSlide(<?= $idx ?>)" data-idx-mobile="<?= $idx ?>"
                    class="report-nav-btn-mobile flex items-center gap-3 px-6 py-4 rounded-3xl border whitespace-nowrap transition-all <?= $idx === 0 ? 'bg-[#d4af37] text-black border-[#d4af37]' : 'bg-white/5 text-gray-500 border-white/5' ?>">
                    <i data-lucide="<?= $s['icon'] ?>" class="w-3.5 h-3.5"></i>
                    <span class="text-[9px] font-black uppercase tracking-widest font-sans"><?= $s['label'] ?></span>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- SLIDE PANEL -->
            <div class="flex-1 min-w-0 relative">
                <!-- Loading indicator (thin gold bar) -->
                <div id="loading-bar" class="absolute top-0 left-0 h-1 bg-gradient-to-r from-transparent via-[#d4af37] to-transparent w-full opacity-0 transition-opacity duration-300 z-50 overflow-hidden">
                    <div class="h-full bg-[#d4af37] animate-progress-indeterminate w-1/3"></div>
                </div>

                <div id="slide-panel" class="min-h-[700px]">
                    <!-- Injected by public/js/admin-reports.js -->
                     <div class="flex flex-col items-center justify-center py-40 animate-pulse text-gray-700">
                         <i data-lucide="loader-2" class="w-12 h-12 animate-spin mb-6 stroke-[1px]"></i>
                         <p class="text-[10px] font-black uppercase tracking-[0.5em]">Initializing BI Hub...</p>
                     </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes slideInRight {
    0% { opacity: 0; transform: translateX(30px) scale(0.98); }
    100% { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes slideInLeft {
    0% { opacity: 0; transform: translateX(-30px) scale(0.98); }
    100% { opacity: 1; transform: translateX(0) scale(1); }
}
.slide-enter-right { animation: slideInRight 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.slide-enter-left { animation: slideInLeft 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

@keyframes progress-indet {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(300%); }
}
.animate-progress-indeterminate { animation: progress-indet 1.5s infinite linear; }

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

@media print {
    aside, .no-print, header, footer, .BentoNavbar, .range-btn, #custom-date-picker { display: none !important; }
    .glass { border: none !important; box-shadow: none !important; background: white !important; color: black !important; }
    #slide-panel { min-height: auto !important; }
    body { background: white !important; color: black !important; }
}
</style>

<script src="public/js/admin-reports.js"></script>
<?php renderFooter(); ?>
