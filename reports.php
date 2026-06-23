<?php
/**
 * Admin Reports Hub — Business Intelligence (6 slides)
 * Consistent with Next.js Dark Luxury UX
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';

// Auth & Permissions
requireAuth(['admin', 'reception', 'store', 'cashier'], [
    'reports:view', 
    'reports:financial_summary', 
    'reports:order_history', 
    'reports:inventory_investment', 
    'reports:store_investment', 
    'reports:menu_item_sales', 
    'reports:cashier_insights'
]);
$user = getCurrentUser();
$isAdmin = ($user['role'] === 'admin');

// Next.js equivalent permissions (Simplified for PHP/JsonDB context)
// In a real Laravel/RBAC system, these would be in a DB.
// Here we simulate the specific permission check mentioned in the prompt.
$userPermissions = $user['permissions'] ?? [];

$allSlides = [
    ['id' => "financial", 'label' => "Financial Summary", 'permission' => "reports:financial_summary", 'icon' => 'trending-up'],
    ['id' => "inventory", 'label' => "Inventory Investment", 'permission' => "reports:inventory_investment", 'icon' => 'package'],
    ['id' => "store", 'label' => "Store Investment", 'permission' => "reports:store_investment", 'icon' => 'warehouse'],
    ['id' => "menu-sales", 'label' => "Menu Item Sales", 'permission' => "reports:menu_item_sales", 'icon' => 'bar-chart-2'],
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

<div class="min-h-screen w-full bg-[#0f1110] text-gray-300 font-sans selection:bg-[#c5a059]/30 p-6 lg:p-12 flex justify-center">
    <div class="max-w-screen-2xl w-full space-y-8">
        
        <!-- HEADER CARD -->
        <div class="p-8 rounded-2xl border border-gray-700/50 bg-gray-800/80 mb-8 relative overflow-hidden">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 relative z-10">
                <div>
                    <h1 class="text-3xl font-bold text-gray-200">Business Intelligence</h1>
                    <p id="slide-subtitle" class="text-xs font-semibold uppercase tracking-wider text-gray-500 mt-2">Consolidated reports · Financial Summary</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Range Pills -->
                    <div class="flex bg-gray-900 rounded-xl border border-gray-700 p-1">
                        <?php foreach(['today', 'week', 'month', 'year'] as $r): ?>
                        <button onclick="ReportHub.setTimeRange('<?= $r ?>')" id="range-btn-<?= $r ?>"
                            class="range-btn px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors <?= $r==='month' ? 'bg-[#c5a059] text-gray-900' : 'text-gray-500 hover:text-white hover:bg-gray-800' ?>">
                            <?= $r ?>
                        </button>
                        <?php endforeach; ?>
                        <!-- Duration pill -->
                        <button onclick="ReportHub.toggleDurationPicker()" id="range-btn-duration"
                            class="range-btn px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors text-gray-500 hover:text-white hover:bg-gray-800 flex items-center gap-1.5">
                            <i data-lucide="calendar-range" class="w-3.5 h-3.5"></i>
                            Duration
                        </button>
                    </div>

                    <!-- Duration From/To Pickers (hidden by default) -->
                    <div id="duration-picker" class="hidden items-center gap-2 bg-gray-900 border border-gray-700 rounded-xl p-2 flex-wrap">
                        <div class="relative">
                            <span class="absolute -top-2 left-2 text-[9px] font-black uppercase tracking-widest text-[#c5a059] bg-gray-900 px-1">From</span>
                            <input type="date" id="duration-start" 
                                class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-200 focus:border-[#c5a059] outline-none transition-colors cursor-pointer">
                        </div>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gray-600"></i>
                        <div class="relative">
                            <span class="absolute -top-2 left-2 text-[9px] font-black uppercase tracking-widest text-[#c5a059] bg-gray-900 px-1">To</span>
                            <input type="date" id="duration-end" 
                                class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-xs font-semibold text-gray-200 focus:border-[#c5a059] outline-none transition-colors cursor-pointer">
                        </div>
                        <button onclick="ReportHub.applyDuration()" 
                            class="px-3 py-1.5 bg-[#c5a059] text-gray-900 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-[#d4b06a] transition-colors flex items-center gap-1">
                            <i data-lucide="check" class="w-3 h-3"></i> Apply
                        </button>
                        <button onclick="ReportHub.clearDuration()" 
                            class="px-2 py-1.5 bg-gray-800 border border-gray-700 text-gray-500 rounded-lg text-[10px] font-black uppercase tracking-widest hover:text-white transition-colors">
                            Clear
                        </button>
                    </div>

                    <!-- Custom Single Date -->
                    <div class="relative group">
                        <input type="date" id="custom-date-picker" onchange="ReportHub.setCustomDate(this.value)"
                            class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm font-semibold text-gray-200 focus:border-[#c5a059] outline-none w-44 transition-colors cursor-pointer">
                        <i data-lucide="calendar" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none"></i>
                    </div>

                    <!-- Print -->
                    <button onclick="window.print()" class="w-10 h-10 rounded-lg bg-gray-800 border border-gray-700 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-10">
            <!-- SIDE TABS (Desktop) -->
            <aside class="hidden md:flex flex-col w-64 space-y-2 sticky top-24 h-fit">
                <?php foreach($slides as $idx => $s): ?>
                <button onclick="ReportHub.goToSlide(<?= $idx ?>)" data-idx="<?= $idx ?>"
                    class="report-nav-btn group flex items-center gap-3 px-5 py-4 rounded-xl border transition-colors text-left <?= $idx === 0 ? 'bg-gray-800 border-gray-700 text-gray-200 shadow-sm' : 'bg-transparent border-transparent text-gray-500 hover:text-gray-300 hover:bg-gray-800/30' ?>">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $idx === 0 ? 'bg-[#c5a059] text-gray-900' : 'bg-gray-800 text-gray-500 group-hover:text-gray-400' ?>">
                        <i data-lucide="<?= $s['icon'] ?>" class="w-4 h-4"></i>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-wider"><?= $s['label'] ?></span>
                </button>
                <?php endforeach; ?>

                <?php if (empty($slides)): ?>
                    <p class="text-xs uppercase font-bold text-red-400 p-4 text-center border border-red-500/20 rounded-xl bg-red-500/5">No report sections available.</p>
                <?php endif; ?>
            </aside>

            <!-- MOBILE TABS -->
            <div class="md:hidden flex overflow-x-auto no-scrollbar gap-2 pb-4 mb-4">
                <?php foreach($slides as $idx => $s): ?>
                <button onclick="ReportHub.goToSlide(<?= $idx ?>)" data-idx-mobile="<?= $idx ?>"
                    class="report-nav-btn-mobile flex items-center gap-2 px-5 py-3 rounded-lg border whitespace-nowrap transition-colors <?= $idx === 0 ? 'bg-[#c5a059] text-gray-900 border-[#c5a059]' : 'bg-gray-800 border-gray-700 text-gray-500' ?>">
                    <i data-lucide="<?= $s['icon'] ?>" class="w-3.5 h-3.5"></i>
                    <span class="text-[10px] font-bold uppercase tracking-wider font-sans"><?= $s['label'] ?></span>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- SLIDE PANEL -->
            <div class="flex-1 min-w-0 relative">
                <!-- Loading indicator (thin gold bar) -->
                <div id="loading-bar" class="absolute top-0 left-0 h-1 bg-gradient-to-r from-transparent via-[#c5a059] to-transparent w-full opacity-0 transition-opacity duration-300 z-50 overflow-hidden">
                    <div class="h-full bg-[#c5a059] animate-progress-indeterminate w-1/3"></div>
                </div>

                <div id="slide-panel" class="min-h-[700px]">
                    <!-- Injected by public/js/admin-reports.js -->
                     <div class="flex flex-col items-center justify-center py-40 animate-pulse text-gray-500">
                         <i data-lucide="loader-2" class="w-10 h-10 animate-spin mb-4"></i>
                         <p class="text-xs font-semibold uppercase tracking-wider">Initializing Reports...</p>
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
    aside, .no-print, header, footer, .BentoNavbar, .range-btn, #custom-date-picker, #duration-picker, #range-btn-duration { display: none !important; }
    .glass { border: none !important; box-shadow: none !important; background: white !important; color: black !important; }
    #slide-panel { min-height: auto !important; }
    body { background: white !important; color: black !important; }
}
</style>

<script src="public/js/admin-reports.js?v=<?= time() ?>"></script>
<?php renderFooter(); ?>
