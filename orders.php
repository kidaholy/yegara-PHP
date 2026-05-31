<?php
/**
 * Orders Management Page - Abe Hotel
 * High-Fidelity "Luxury-First" Edition (Spec-Corrected)
 */
require_once 'includes/layout.php';
requireAuth(['admin']);
$title = __('admin_orders.title');

// --- Filters ---
$filter_time     = $_GET['time']     ?? 'week';
$filter_category = $_GET['category'] ?? 'all';
$filter_status   = $_GET['status']   ?? 'all';
$search          = trim($_GET['search'] ?? '');

try {
    $allOrders = db('orders')->findMany(['where' => ['isDeleted' => false]]);
    $allOrderItems = db('orderItems')->findMany(['where' => ['isDeleted' => false]]);
    $deletedOrders = db('orders')->findMany(['where' => ['isDeleted' => true]]);

    // Map items to orders
    $itemsMap = [];
    foreach ($allOrderItems as $item) { $itemsMap[$item['orderId']][] = $item; }
    foreach ($allOrders as &$o) { $o['items'] = $itemsMap[$o['id']] ?? []; }
    foreach ($deletedOrders as &$o) { $o['items'] = $itemsMap[$o['id']] ?? []; }

    // --- Performance Metrics & Performance Monitoring ---
    $now = new DateTime();
    $delayedOrders = [];

    // Helper process for metric calculations
    $processMetrics = function(&$orders) use ($now, &$delayedOrders) {
        foreach ($orders as &$o) {
            $threshold = intval($o['thresholdMinutes'] ?? 20);
            $created = new DateTime($o['createdAt'] ?? 'now');
            
            if (in_array(strtolower($o['status'] ?? ''), ['served', 'completed', 'cancelled'])) {
                $totalTaken = intval($o['totalPreparationTime'] ?? 0);
            } else {
                $diff = $now->diff($created);
                $totalTaken = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
            }

            $o['computedTaken'] = $totalTaken;
            $o['computedDelay'] = max(0, $totalTaken - $threshold);
            $o['delayColor'] = $o['computedDelay'] === 0 ? 'emerald' : ($o['computedDelay'] <= 10 ? 'amber' : 'rose');

            if ($o['computedDelay'] > 0 && !in_array(strtolower($o['status'] ?? ''), ['served', 'completed', 'cancelled'])) {
                $delayedOrders[] = $o;
            }
        }
    };

    $processMetrics($allOrders);
    $processMetrics($deletedOrders);

    usort($allOrders, fn($a, $b) => intval($b['orderNumber'] ?? 0) - intval($a['orderNumber'] ?? 0));

    // --- Multi-bucket Sidebar Stats ---
    $preparingBucket = array_filter($allOrders, fn($o) => in_array(strtolower($o['status'] ?? ''), ['preparing', 'pending', 'unconfirmed']));
    $readyBucket     = array_filter($allOrders, fn($o) => strtolower($o['status'] ?? '') === 'ready');
    $servedBucket    = array_filter($allOrders, fn($o) => in_array(strtolower($o['status'] ?? ''), ['served', 'completed']));

    $calcStats = function($ordersArray) {
        $foodRev = 0; $drinkRev = 0; $totalDelay = 0; $totalPrep = 0;
        foreach ($ordersArray as $o) {
            foreach ($o['items'] as $item) {
                $itemMainCat = strtolower($item['mainCategory'] ?? '');
                if ($itemMainCat === 'food') $foodRev += (float)$item['price'] * (int)$item['quantity'];
                elseif ($itemMainCat === 'drinks') $drinkRev += (float)$item['price'] * (int)$item['quantity'];
            }
            $totalDelay += $o['computedDelay'];
            $totalPrep += $o['computedTaken'];
        }
        $count = count($ordersArray);
        return [
            'count' => $count,
            'foodRev' => $foodRev,
            'drinkRev' => $drinkRev,
            'avgDelay' => $count > 0 ? round($totalDelay / $count) : 0,
            'avgPrep'  => $count > 0 ? round($totalPrep / $count) : 0
        ];
    };

    $stats = [
        'all'       => $calcStats($allOrders),
        'preparing' => $calcStats($preparingBucket),
        'ready'     => $calcStats($readyBucket),
        'served'    => $calcStats($servedBucket),
        'deleted'   => ['count' => count($deletedOrders)]
    ];

    // --- Cashier Carousel ---
    $cashierGroups = [];
    foreach ($allOrders as $o) {
        $cName = $o['createdBy']['name'] ?? 'System';
        $cashierGroups[$cName]['orders'][] = $o;
        $cashierGroups[$cName]['revenue'] = ($cashierGroups[$cName]['revenue'] ?? 0) + $o['totalAmount'];
    }
    $cashierNames = array_keys($cashierGroups);
    $activeCashierIdx = intval($_GET['cashierIdx'] ?? 0);
    if (!empty($cashierNames)) {
        $activeCashierName = $cashierNames[$activeCashierIdx % count($cashierNames)];
        $cashierOrders = $cashierGroups[$activeCashierName]['orders'];
    } else {
        $activeCashierName = 'None'; $cashierOrders = [];
    }

    // --- Filtering ---
    $filteredOrders = match($filter_status) {
        'all'       => $allOrders,
        'preparing' => $preparingBucket,
        'ready'     => $readyBucket,
        'served'    => $servedBucket,
        'deleted'   => $deletedOrders,
        'cashier'   => $cashierOrders,
        default     => $allOrders
    };

    if ($filter_time !== 'all') {
        $filteredOrders = array_filter($filteredOrders, function($o) use ($filter_time, $now) {
            $created = new DateTime($o['createdAt'] ?? 'now');
            $diff = $now->diff($created)->days;
            return match($filter_time) { 'today'=>$diff === 0, 'week'=>$diff<=7, 'month'=>$diff<=30, 'year'=>$diff<=365, default=>true };
        });
    }

    if ($filter_category !== 'all') {
        $filteredOrders = array_filter($filteredOrders, function($o) use ($filter_category) {
            foreach ($o['items'] as $it) if (strtolower($it['mainCategory'] ?? '') === strtolower($filter_category)) return true;
            return false;
        });
    }

    if ($search) {
        $filteredOrders = array_filter($filteredOrders, function($o) use ($search) {
            return stripos($o['orderNumber'] ?? '', $search) !== false 
                || stripos($o['tableNumber'] ?? '', $search) !== false 
                || stripos($o['customerName'] ?? '', $search) !== false;
        });
    }
    $filteredOrders = array_values($filteredOrders);

} catch (Exception $e) { $filteredOrders = []; }

renderHeader($title);
?>

<div class="min-h-screen bg-[#0f1110] text-[#c5a059] flex flex-col pt-20">
    
    <!-- Delay Alert Banner -->
    <?php if (!empty($delayedOrders)): ?>
    <div id="delay-alert-banner" class="bg-red-500/10 border-y border-red-500/20 px-8 py-3 flex items-center gap-4 sticky top-20 z-40 backdrop-blur-md">
        <div class="flex items-center gap-2 text-white bg-red-600 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-600/20">
            <i data-lucide="alert-triangle" class="w-3 h-3"></i> PREPARATION DELAY!
        </div>
        <div class="flex-1 flex gap-3 overflow-x-auto whitespace-nowrap custom-scrollbar pb-1">
            <?php foreach ($delayedOrders as $do): ?>
            <div class="glass px-3 py-1.5 rounded-xl border border-red-500/20 flex items-center gap-2.5">
                <span class="text-white font-bold text-[10px] italic font-playfair">#<?php echo substr($do['orderNumber'], -4); ?></span>
                <span class="text-red-400 font-bold text-[10px]"><?php echo $do['computedDelay']; ?>m Delay</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-white/30 text-[9px] uppercase tracking-widest font-black"><?php echo $do['tableNumber']; ?></span>
                    <?php if(!empty($do['distributions'])): foreach($do['distributions'] as $d): ?>
                    <span class="text-[8px] bg-red-500/10 text-red-400 px-1.5 rounded-md font-black">🚚 <?php echo $d; ?></span>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-12 gap-8 px-8 py-8 flex-1">
        
        <!-- SIDEBAR -->
        <aside class="lg:col-span-3 space-y-6 sticky top-28 h-fit">
            <div class="glass rounded-[30px] border border-white/5 overflow-hidden shadow-2xl">
                <h2 class="px-7 py-6 text-[10px] font-black uppercase tracking-[0.4em] text-white/20 border-b border-white/5">FILTER BUCKETS</h2>
                <div class="divide-y divide-white/[0.04]">
                    <?php
                    $tabs = [
                        ['id'=>'all',       'label'=>__('admin_orders.all_orders'), 'icon'=>'clipboard-list', 'data'=>$stats['all'],       'color'=>'orange'],
                        ['id'=>'preparing', 'label'=>__('admin_orders.preparing'),  'icon'=>'flame',          'data'=>$stats['preparing'], 'color'=>'red'],
                        ['id'=>'ready',     'label'=>__('admin_orders.ready'),      'icon'=>'check-circle-2', 'data'=>$stats['ready'],     'color'=>'green'],
                        ['id'=>'served',    'label'=>__('admin_orders.served'),     'icon'=>'package-check',  'data'=>$stats['served'],    'color'=>'blue'],
                        ['id'=>'cashier',   'label'=>'BY CASHIER',                  'icon'=>'users',          'data'=>['count'=>count($cashierNames)], 'color'=>'purple'],
                        ['id'=>'deleted',   'label'=>'DELETED HISTORY',             'icon'=>'trash-2',        'data'=>$stats['deleted'],   'color'=>'white'],
                    ];
                    foreach ($tabs as $tab):
                        $isActive = $filter_status === $tab['id'];
                        $href = "?status={$tab['id']}&time=$filter_time&category=$filter_category";
                        $cls = $isActive ? "bg-white/[0.05] border-l-[6px] border-[#c5a059]" : "hover:bg-white/[0.02] border-l-[6px] border-transparent";
                        $tColor = $tab['color'];
                    ?>
                    <a href="<?php echo $href; ?>" class="flex items-center gap-5 px-7 py-5 transition-all group <?php echo $cls; ?>">
                        <div class="w-11 h-11 rounded-2xl bg-<?php echo $tColor; ?>-500/10 flex items-center justify-center flex-shrink-0 text-<?php echo $tColor; ?>-400 group-hover:scale-110 transition-transform">
                            <i data-lucide="<?php echo $tab['icon']; ?>" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-black tracking-[0.1em] text-white/90 uppercase"><?php echo $tab['label']; ?></p>
                            <?php if (isset($tab['data']['avgPrep'])): ?>
                            <div class="flex items-center gap-2 mt-1.5 opacity-40">
                                <span class="text-[9px] font-bold"><?php echo $tab['data']['avgPrep']; ?>M AVG</span>
                                <span class="w-1 h-1 rounded-full bg-white/20"></span>
                                <span class="text-[9px] font-bold text-emerald-400"><?php echo number_format($tab['data']['foodRev'] + $tab['data']['drinkRev'], 0); ?> Br</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] font-black w-8 h-8 rounded-full <?php echo $isActive ? 'bg-[#c5a059] text-black shadow-lg shadow-gold/20' : 'bg-white/5 text-white/20'; ?> flex items-center justify-center">
                            <?php echo $tab['data']['count'] ?? 0; ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="glass p-8 rounded-[35px] border border-[#c5a059]/10 bg-gradient-to-br from-[#c5a059]/5 to-transparent relative overflow-hidden group">
                <i data-lucide="zap" class="absolute -right-4 -top-4 w-24 h-24 text-[#c5a059]/5 rotate-12 transition-transform group-hover:rotate-45 duration-700"></i>
                <h3 class="text-white font-black text-[12px] uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <?php echo __('admin_orders.need_insights'); ?>
                </h3>
                <p class="text-[11px] text-[#c5a059]/60 leading-relaxed italic mb-4"><?php echo __('admin_orders.check_reports'); ?></p>
                <div class="w-full h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-500 to-emerald-300 h-full w-[94%]" id="efficiency-bar"></div>
                </div>
            </div>
        </aside>

        <!-- MAIN PANEL -->
        <main class="lg:col-span-9 flex flex-col space-y-6">
            
            <div class="glass rounded-[40px] border border-white/5 bg-[#0f1110]/50 shadow-2xl flex flex-col">
                <!-- Header Controls -->
                <div class="px-10 py-7 border-b border-white/5 bg-white/[0.02] flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-4xl font-black font-playfair italic text-[#c5a059] tracking-tight leading-none"><?php echo __('admin_orders.order_management'); ?></h1>
                        <div class="flex items-center gap-4 mt-4">
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-white/20"><?php echo count($filteredOrders); ?> <?php echo __('admin_orders.orders_count'); ?></span>
                            <div class="flex items-center gap-1.5 p-1 bg-white/5 rounded-full">
                                <?php foreach(['today','week','month','year'] as $t): ?>
                                <a href="?time=<?php echo $t; ?>&status=<?php echo $filter_status; ?>&category=<?php echo $filter_category; ?>" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $filter_time === $t ? 'bg-[#c5a059] text-black' : 'text-white/20 hover:text-white/40'; ?> transition-all">
                                    <?php echo $t; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex p-1 bg-white/5 rounded-2xl border border-white/5 self-end md:self-auto">
                        <?php foreach(['all'=>'ALL','food'=>'FOOD','drinks'=>'DRINKS'] as $k=>$v): ?>
                        <a href="?category=<?php echo $k; ?>&time=<?php echo $filter_time; ?>&status=<?php echo $filter_status; ?>" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest <?php echo $filter_category===$k ? 'bg-[#151817] text-white shadow-xl ring-1 ring-white/5' : 'text-white/30 hover:text-white/50'; ?> transition-all">
                            <?php echo $v; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Search + Action Bar -->
                <div class="px-10 py-6 border-b border-white/5 flex items-center gap-6 bg-white/[0.01]">
                    <div class="relative flex-1 max-w-sm">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[#c5a059]/30"></i>
                        <input type="text" id="order-search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search floor, table, order..." class="w-full bg-[#1a1d1c]/50 border border-white/5 rounded-2xl py-3.5 pl-12 pr-5 text-sm text-white/70 placeholder:text-white/10 focus:outline-none focus:ring-2 focus:ring-[#c5a059]/20 transition-all">
                    </div>

                    <?php if ($filter_status !== 'cashier'): ?>
                    <div class="flex items-center gap-4 ml-auto">
                        <button onclick="handleBulkAction('bulk-serve')" class="flex items-center gap-2.5 px-7 py-3.5 bg-gradient-to-br from-[#c5a059] to-[#d4af37] text-black text-[10px] font-black uppercase tracking-widest rounded-2xl hover:scale-[1.03] active:scale-95 transition-all shadow-xl shadow-gold/10">
                            <i data-lucide="check-check" class="w-4 h-4"></i> Mark All as Served
                        </button>
                        <button onclick="handleBulkAction('<?php echo $filter_status==='deleted'?'empty-trash':'bulk-delete'; ?>')" class="px-4 py-3.5 bg-white/5 border border-white/10 text-white/30 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-red-600 hover:text-white hover:border-red-500 transition-all group">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="p-10 custom-scrollbar min-h-[600px] bg-[#0f1110]">
                    <?php if ($filter_status === 'cashier'): ?>
                        <!-- Cashier Carousel Header -->
                        <div class="space-y-10 animate-in fade-in duration-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-5">
                                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#c5a059] to-transparent p-[1px]">
                                        <div class="w-full h-full rounded-full bg-[#0f1110] flex items-center justify-center text-gold">
                                            <i data-lucide="user-round" class="w-7 h-7"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="text-3xl font-black text-white italic font-playfair"><?php echo $activeCashierName; ?></h3>
                                        <p class="text-[10px] text-white/20 font-black uppercase tracking-[0.3em] mt-1">PRIMARY FLOOR CASHIER</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 bg-white/5 p-3 rounded-[2rem] border border-white/10 shadow-2xl">
                                    <a href="?status=cashier&cashierIdx=<?php echo $activeCashierIdx - 1; ?>&time=<?php echo $filter_time; ?>" class="w-12 h-12 rounded-full hover:bg-white/10 flex items-center justify-center text-white/30 hover:text-white transition-all"><i data-lucide="chevron-left"></i></a>
                                    <span class="text-xs font-black text-gold px-4 tracking-widest"><?php echo $activeCashierIdx + 1; ?> &mdash; <?php echo count($cashierNames); ?></span>
                                    <a href="?status=cashier&cashierIdx=<?php echo $activeCashierIdx + 1; ?>&time=<?php echo $filter_time; ?>" class="w-12 h-12 rounded-full hover:bg-white/10 flex items-center justify-center text-white/30 hover:text-white transition-all"><i data-lucide="chevron-right"></i></a>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="glass p-8 rounded-[2.5rem] border border-white/5">
                                    <p class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em] mb-2">COLLECTED REVENUE</p>
                                    <div class="flex items-baseline gap-2">
                                        <h4 class="text-5xl font-black text-emerald-400"><?php echo number_format($cashierGroups[$activeCashierName]['revenue'] ?? 0, 0); ?></h4>
                                        <span class="text-emerald-400/40 font-bold text-sm">ETB</span>
                                    </div>
                                </div>
                                <div class="glass p-8 rounded-[2.5rem] border border-white/5">
                                    <p class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em] mb-2">TICKET COUNT</p>
                                    <h4 class="text-5xl font-black text-white"><?php echo count($cashierOrders); ?></h4>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <?php foreach ($cashierOrders as $co): ?>
                                <div class="glass px-8 py-5 rounded-[1.5rem] border border-white/5 hover:border-gold/10 transition-all flex items-center justify-between group">
                                    <div class="flex items-center gap-6">
                                        <span class="text-[10px] font-black text-white/20 uppercase tracking-widest"><?php echo date('H:i', strtotime($co['createdAt'])); ?></span>
                                        <span class="text-xl font-bold font-playfair italic text-[#c5a059]">#<?php echo substr($co['orderNumber'], -4); ?></span>
                                        <span class="text-[10px] font-black bg-white/5 px-3 py-1 rounded-full text-white/60"><?php echo $co['tableNumber']; ?></span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-lg font-black text-white group-hover:text-emerald-400 transition-colors"><?php echo number_format($co['totalAmount'], 0); ?> Br</span>
                                        <i data-lucide="chevron-right" class="w-4 h-4 text-white/10 group-hover:text-gold transition-colors"></i>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php elseif (empty($filteredOrders)): ?>
                        <div class="flex flex-col items-center justify-center h-full py-32 opacity-10 space-y-8">
                            <span class="text-[180px] leading-none">🍃</span>
                            <div class="text-center">
                                <h3 class="text-4xl italic font-playfair text-[#c5a059] mb-3"><?php echo __('admin_orders.quiet_for_now'); ?></h3>
                                <p class="text-[12px] font-black uppercase tracking-[0.8em]"><?php echo __('admin_orders.no_orders_found'); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($filteredOrders as $o):
                                $status = strtolower($o['status'] ?? 'pending');
                                $tColor = $o['delayColor'];
                                $metrics = ['totalTaken'=>$o['computedTaken'], 'delay'=>$o['computedDelay'], 'threshold'=>$o['thresholdMinutes'] ?? 20];
                            ?>
                            <div class="lg:h-36 bg-[#151817] border border-white/[0.04] rounded-[3rem] px-10 py-6 hover:border-[#c5a059]/30 hover:bg-[#181c1a] transition-all group relative overflow-hidden flex flex-col lg:flex-row lg:items-center gap-10">
                                
                                <!-- LEFT SECTION -->
                                <div class="lg:w-60 flex-shrink-0">
                                    <p class="text-[10px] font-black text-white/20 uppercase tracking-[0.4em] mb-2"><?php echo date('M d — h:i A', strtotime($o['createdAt'])); ?></p>
                                    <h4 class="text-4xl font-black font-playfair italic text-[#c5a059] leading-none">#<?php echo substr($o['orderNumber'], -4); ?></h4>
                                    <div class="flex flex-wrap gap-2 mt-4">
                                        <span class="text-[9px] font-black text-white/50 bg-white/5 px-2.5 py-1 rounded-lg uppercase tracking-widest border border-white/5"><?php echo $o['floorNumber'] ?? 'GF'; ?></span>
                                        <span class="text-[9px] font-black text-[#c5a059] bg-[#c5a059]/10 px-2.5 py-1 rounded-lg uppercase tracking-widest border border-[#c5a059]/10"><?php echo $o['tableNumber']; ?></span>
                                        <?php if(!empty($o['distributions'])): foreach($o['distributions'] as $d): ?>
                                        <span class="text-[9px] font-black text-orange-400 bg-orange-500/10 px-2.5 py-1 rounded-lg uppercase tracking-widest border border-orange-500/10">🚚 <?php echo $d; ?></span>
                                        <?php endforeach; endif; ?>
                                    </div>
                                </div>

                                <!-- MIDDLE SECTION -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 mb-4">
                                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-<?php echo $tColor; ?>-500/10 text-<?php echo $tColor; ?>-400 border border-<?php echo $tColor; ?>-500/20">
                                            <i data-lucide="<?php echo match($status){'ready'=>'check','preparing'=>'flame','served'=>'package-check',default=>'clock'}; ?>" class="w-3 h-3 inline mr-1 mb-0.5"></i>
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                        <span class="w-1.5 h-1.5 rounded-full bg-white/5"></span>
                                        <span class="text-[10px] font-black text-white/20 uppercase tracking-[0.2em]"><?php echo count($o['items']); ?> Items Total</span>
                                    </div>
                                    <div class="flex flex-wrap gap-x-6 gap-y-3">
                                        <?php foreach ($o['items'] as $item): $isVIP = strpos(strtolower($item['menuTier'] ?? ''), 'vip') !== false; ?>
                                        <div class="flex items-center gap-3">
                                            <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center text-[11px] font-black text-white/40"><?php echo $item['quantity']; ?></div>
                                            <div class="relative">
                                                <span class="text-[14px] font-bold text-white/80"><?php echo $item['name']; ?></span>
                                                <?php if($isVIP): ?><span class="absolute -right-6 -top-1 text-[7px] bg-gold text-black px-1 rounded font-black">VIP</span><?php endif; ?>
                                            </div>
                                            <span class="text-[10px] text-white/10 font-medium italic"><?php echo $item['preparationTime'] ?? 0; ?>m</span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- RIGHT SECTION -->
                                <div class="lg:w-72 flex-shrink-0 flex items-center justify-between lg:justify-end gap-8">
                                    <div class="text-right flex flex-col items-end">
                                        <div class="flex items-center gap-3 bg-<?php echo $tColor; ?>-500/5 px-4 py-2 rounded-2xl border border-<?php echo $tColor; ?>-500/10 mb-2">
                                            <div class="text-right">
                                                <p class="text-[13px] font-black text-<?php echo $tColor; ?>-400 leading-none">
                                                    <?php echo $o['computedDelay'] > 0 ? "+{$o['computedDelay']}m Delay" : (strtolower($status)==='ready' ? 'READY' : 'ON TIME'); ?>
                                                </p>
                                                <p class="text-[8px] font-black text-white/20 uppercase tracking-[0.2em] mt-1.5">vs <?php echo $metrics['threshold']; ?>m limit</p>
                                            </div>
                                            <i data-lucide="<?php echo $o['computedDelay']>0 ? 'alert-circle' : 'check-circle-2'; ?>" class="w-6 h-6 text-<?php echo $tColor; ?>-400"></i>
                                        </div>
                                        <div class="flex items-baseline gap-2">
                                            <h4 class="text-4xl font-black font-playfair italic text-white tracking-tighter"><?php echo number_format($o['totalAmount'], 0); ?></h4>
                                            <span class="text-[#c5a059]/40 font-black text-[10px] uppercase">Br</span>
                                        </div>
                                    </div>

                                    <?php if(!$o['isDeleted']): ?>
                                    <button onclick="handleDeleteOrder('<?php echo $o['id']; ?>', '<?php echo substr($o['orderNumber'], -4); ?>')" class="w-12 h-12 rounded-2xl bg-white/5 border border-white/5 flex items-center justify-center text-white/10 hover:bg-red-600 hover:text-white hover:border-red-500 transition-all opacity-0 group-hover:opacity-100 shadow-2xl scale-90 group-hover:scale-100">
                                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Logic functions matching the spec's JS structure
async function handleBulkAction(action) {
    let title = action === 'bulk-serve' ? 'Mark All as Served' : 
                action === 'empty-trash' ? 'Empty Trash' : 'Delete All Orders';
    
    let message = action === 'bulk-serve' ? 'Mark all active orders as served? Stock will be updated.' : 
                  action === 'empty-trash' ? 'This permenently deletes all records in the trash. This cannot be undone.' :
                  'Move all active orders to the deleted history?';

    if (!confirm(`${title}\n\n${message}`)) return;

    try {
        const res = await fetch('api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Action failed');
        }
    } catch (e) { alert('Network Error'); }
}

async function handleDeleteOrder(id, num) {
    if (!confirm(`Move Order #${num} to deleted history? Stock items will be restored to inventory.`)) return;
    try {
        const res = await fetch('api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    } catch (e) { alert('Error deleting order'); }
}

// Manual Refresh: Automatic refresh has been disabled per user request to prevent disruption.
// The page will now only refresh on user-initiated actions (Serve/Delete).

// Search functionality
document.getElementById('order-search')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const url = new URL(window.location);
        url.searchParams.set('search', this.value);
        window.location.href = url.href;
    }
});
</script>

<style>
.glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); }
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(197, 160, 89, 0.1); border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(197, 160, 89, 0.3); }
#efficiency-bar { transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1); }
</style>

<?php renderFooter(); ?>
