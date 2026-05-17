<?php
/**
 * High-Fidelity Admin Dashboard - Abe Hotel
 * Layout: Orders Management with Left Category Sidebar
 */
require_once 'includes/layout.php';

requireAuth(['admin']);
$title = "Dashboard";

try {
    $allOrders = db('orders')->findMany(['where' => ['isDeleted' => false]]);
    $deletedOrders = db('orders')->findMany(['where' => ['isDeleted' => true]]);
    
    $preparing = array_filter($allOrders, fn($o) => strtolower($o['status'] ?? '') === 'preparing');
    $ready     = array_filter($allOrders, fn($o) => strtolower($o['status'] ?? '') === 'ready');
    $served    = array_filter($allOrders, fn($o) => in_array(strtolower($o['status'] ?? ''), ['served', 'completed']));
    
    // Today's revenue
    $today = date('Y-m-d');
    $todayOrders = array_filter($allOrders, fn($o) => strpos($o['createdAt'] ?? '', $today) === 0);
    $todayRevenue = array_sum(array_column($todayOrders, 'totalAmount'));
    
} catch (Exception $e) {
    $allOrders = $preparing = $ready = $served = $deletedOrders = [];
    $todayRevenue = 0;
}

renderHeader($title);
?>

<!-- Two-Panel Layout: Left Category Sidebar + Right Content -->
<div class="flex h-full w-full overflow-hidden">

    <!-- ===== LEFT CATEGORY SIDEBAR ===== -->
    <aside class="w-[280px] flex-shrink-0 bg-[#111413] border-r border-white/5 flex flex-col overflow-y-auto p-4">
        <h2 class="text-[13px] font-black italic text-[#c5a059] px-3 py-4 mb-1" style="font-family: 'Cormorant Garamond', serif;">Orders</h2>

        <?php
        $categories = [
            [
                'label' => 'All Orders',
                'icon'  => 'clipboard-list',
                'count' => count($allOrders),
                'active'=> true,
                'icon_bg' => 'bg-orange-500/20',
                'icon_color' => 'text-orange-400',
                'badge_bg' => 'bg-orange-500',
                'avg' => number_format($todayRevenue, 0) . ' Br avg',
            ],
            [
                'label' => 'Preparing',
                'icon'  => 'flame',
                'count' => count($preparing),
                'active'=> false,
                'icon_bg' => 'bg-red-500/10',
                'icon_color' => 'text-red-400',
                'badge_bg' => 'bg-red-500/20',
                'avg' => '0 Br avg',
            ],
            [
                'label' => 'Ready',
                'icon'  => 'check-square-2',
                'count' => count($ready),
                'active'=> false,
                'icon_bg' => 'bg-green-500/10',
                'icon_color' => 'text-green-400',
                'badge_bg' => 'bg-green-500/20',
                'avg' => '0 Br avg',
            ],
            [
                'label' => 'Served',
                'icon'  => 'package-check',
                'count' => count($served),
                'active'=> false,
                'icon_bg' => 'bg-blue-500/10',
                'icon_color' => 'text-blue-400',
                'badge_bg' => 'bg-blue-500/20',
                'avg' => '0 Br avg',
            ],
            [
                'label' => 'By Cashier',
                'icon'  => 'user-round',
                'count' => 0,
                'active'=> false,
                'icon_bg' => 'bg-purple-500/10',
                'icon_color' => 'text-purple-400',
                'badge_bg' => 'bg-purple-500/20',
                'avg' => '0 Br avg',
            ],
            [
                'label' => 'Deleted History',
                'icon'  => 'trash-2',
                'count' => count($deletedOrders),
                'active'=> false,
                'icon_bg' => 'bg-white/5',
                'icon_color' => 'text-white/30',
                'badge_bg' => 'bg-white/10',
                'avg' => '0 Br avg',
            ],
        ];

        foreach ($categories as $cat): 
            $activeCls = $cat['active']
                ? 'bg-[#1e2120] border border-white/10 shadow-inner'
                : 'border border-transparent hover:bg-white/5';
        ?>
        <button class="w-full flex items-center gap-3 p-3 rounded-xl mb-1 transition-all group <?php echo $activeCls; ?>">
            <div class="w-9 h-9 rounded-xl <?php echo $cat['icon_bg']; ?> flex items-center justify-center flex-shrink-0">
                <i data-lucide="<?php echo $cat['icon']; ?>" class="w-4 h-4 <?php echo $cat['icon_color']; ?>"></i>
            </div>
            <div class="flex-1 text-left">
                <p class="text-[11px] font-black uppercase tracking-widest <?php echo $cat['active'] ? 'text-white' : 'text-white/40 group-hover:text-white/70'; ?> transition-colors">
                    <?php echo $cat['label']; ?>
                </p>
                <p class="text-[9px] text-white/20 font-medium mt-0.5"><?php echo $cat['avg']; ?></p>
            </div>
            <span class="text-[10px] font-black w-6 h-6 rounded-full <?php echo $cat['badge_bg']; ?> flex items-center justify-center <?php echo $cat['active'] ? 'text-orange-300' : 'text-white/30'; ?>">
                <?php echo $cat['count']; ?>
            </span>
        </button>
        <?php endforeach; ?>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Content Header -->
        <div class="px-8 py-5 border-b border-white/5 flex flex-col sm:flex-row sm:items-center gap-4 bg-[#0f1110] flex-shrink-0">
            <div class="flex-1">
                <h1 class="text-2xl font-semibold italic text-[#c5a059]" style="font-family: 'Cormorant Garamond', serif;">Order Management</h1>
                <p class="text-[10px] uppercase tracking-widest text-white/20 font-bold mt-0.5">
                    <?php echo count($allOrders); ?> Orders
                </p>
            </div>

            <!-- Time Filters -->
            <div class="flex items-center gap-1 flex-wrap">
                <?php
                $timeFilters = ['Today', 'Week', 'Month', 'Year'];
                foreach ($timeFilters as $i => $tf):
                    $active = $tf === 'Week';
                    $cls = $active
                        ? 'px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-full bg-[#c5a059] text-black shadow-md'
                        : 'px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-full text-white/40 hover:text-white hover:bg-white/5 transition-all';
                ?>
                <button class="<?php echo $cls; ?>"><?php echo strtoupper($tf); ?></button>
                <?php endforeach; ?>

                <span class="w-[1px] h-5 bg-white/10 mx-1"></span>

                <?php
                $catFilters = ['All', 'Food', 'Drinks'];
                foreach ($catFilters as $cf):
                    $isAll = $cf === 'All';
                    $cls = $isAll
                        ? 'px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-full bg-[#1a1d1c] border border-white/10 text-white shadow-md'
                        : 'px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-full text-white/40 hover:text-white hover:bg-white/5 transition-all';
                ?>
                <button class="<?php echo $cls; ?>"><?php echo strtoupper($cf); ?></button>
                <?php endforeach; ?>

                <button class="flex items-center gap-2 px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-full text-white/30 hover:text-white hover:bg-white/5 transition-all border border-white/5">
                    <i data-lucide="calendar" class="w-3 h-3"></i>
                    Specific Date
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="px-8 py-4 bg-[#0f1110] border-b border-white/5 flex-shrink-0">
            <div class="relative max-w-[340px]">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[#c5a059]/50"></i>
                <input type="text" placeholder="Search floor, table, order..."
                    class="w-full bg-[#1a1d1c] border border-white/5 rounded-xl py-3 pl-10 pr-4 text-sm text-white/70 placeholder:text-white/20 focus:outline-none focus:border-[#c5a059]/30 focus:bg-[#1e2120] transition-all">
            </div>
        </div>

        <!-- Orders Content Area -->
        <div class="flex-1 overflow-y-auto bg-[#0f1110] relative flex flex-col items-center justify-center">
            <?php if (empty($allOrders)): ?>
            <!-- Empty State: Quiet for now -->
            <div class="flex flex-col items-center justify-center text-center p-16 space-y-4">
                <div class="text-[120px] leading-none select-none" style="filter: drop-shadow(0 0 40px rgba(197,160,89,0.1));">
                    🌙
                </div>
                <h3 class="text-2xl font-semibold italic text-[#c5a059]/60" style="font-family: 'Cormorant Garamond', serif;">
                    Quiet for now...
                </h3>
                <p class="text-[10px] uppercase tracking-[0.4em] font-black text-white/20">No Orders Found</p>
            </div>
            <?php else: ?>
            <!-- Orders Table -->
            <div class="w-full p-6 space-y-2">
                <?php foreach ($allOrders as $order): 
                    $statusColors = [
                        'preparing' => 'text-red-400 bg-red-500/10',
                        'ready'     => 'text-green-400 bg-green-500/10',
                        'served'    => 'text-blue-400 bg-blue-500/10',
                        'completed' => 'text-green-400 bg-green-500/10',
                        'pending'   => 'text-yellow-400 bg-yellow-500/10',
                    ];
                    $sc = $statusColors[strtolower($order['status'] ?? 'pending')] ?? 'text-white/40 bg-white/5';
                ?>
                <div class="flex items-center justify-between bg-[#151817] border border-white/5 rounded-xl px-5 py-4 hover:border-[#c5a059]/20 hover:bg-[#191c1b] transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="text-[10px] font-black text-white/30 w-16 truncate">
                            #<?php echo substr($order['id'] ?? '', 0, 6); ?>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-white">
                                <?php echo $order['tableNumber'] ?? 'Table —'; ?>
                            </p>
                            <p class="text-[9px] text-white/30">
                                <?php echo date('H:i', strtotime($order['createdAt'] ?? 'now')); ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $sc; ?>">
                            <?php echo ucfirst($order['status'] ?? 'Pending'); ?>
                        </span>
                        <span class="text-sm font-bold text-white">
                            <?php echo number_format($order['totalAmount'] ?? 0, 2); ?> Br
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
