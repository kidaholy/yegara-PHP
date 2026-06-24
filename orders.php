<?php
/**
 * Orders Management Page - Abe Hotel
 * High-Fidelity "Luxury-First" Edition (Spec-Corrected)
 */
require_once 'includes/layout.php';
require_once 'includes/order-utils.php';
require_once 'includes/report-dates.php'; // Add this for resolveReportDateRange
requireAuth(['admin', 'cashier', 'chef', 'reception', 'receptionist', 'store', 'store_keeper'], 'orders:view');

$user = getCurrentUser();
$userRole = $user['role'] ?? '';
$userName = $user['name'] ?? 'Cashier';
$isCashierView = ($userRole === 'cashier')
    || (isset($_GET['view']) && $_GET['view'] === 'recent' && in_array($userRole, ['cashier', 'admin'], true));
$isKiosk = isset($_GET['kiosk']) && $_GET['kiosk'] == 1;

if ($isCashierView) {
    $manager = new SettingsManager();
    $config = $manager->getSetting('configuration') ?? [];
    $showRevenue = !empty($config['enable_cashier_today_revenue']);
    $welcomeDate = date('D, M j');
    
    $range = resolveReportDateRange('today');
    $todayStart = $range['start']->format('Y-m-d H:i:s');
    $todayEnd = $range['end']->format('Y-m-d H:i:s');

    try {
        $todayOrders = db('orders')->findMany([
            'where' => [
                'isDeleted' => false,
                'createdAt' => ['gte' => $todayStart, 'lte' => $todayEnd],
            ],
        ]);
        $orderIds = array_map(fn($o) => $o['id'], $todayOrders);
        $itemsMap = [];
        if (!empty($orderIds)) {
            $orderItems = db('orderItems')->findMany([
                'where' => ['orderId' => ['in' => $orderIds], 'isDeleted' => false],
            ]);
            foreach ($orderItems as $item) {
                $itemsMap[$item['orderId']][] = $item;
            }
        }
        foreach ($todayOrders as &$o) {
            $o['items'] = $itemsMap[$o['id']] ?? [];
        }
        unset($o);

        $todayOrders = array_values(array_filter($todayOrders, fn($o) => !isRoomServiceOrder($o)));
        usort($todayOrders, fn($a, $b) => strtotime($b['createdAt'] ?? 'now') - strtotime($a['createdAt'] ?? 'now'));

        $todayRevenue = 0;
        $foodRevenue = 0;
        $drinkRevenue = 0;

        $myOrders = [];
        foreach ($todayOrders as $o) {
            $orderUserId = $o['createdBy']['id'] ?? '';
            // Only show orders by this specific cashier
            if ($userRole === 'cashier' && $orderUserId != $user['id']) {
                continue;
            }

            $myOrders[] = $o;

            if (strtolower($o['status'] ?? '') !== 'cancelled') {
                $todayRevenue += (float) ($o['totalAmount'] ?? 0);
                foreach ($o['items'] as $it) {
                    $mCat = strtolower($it['mainCategory'] ?? '');
                    $itAmt = (float) ($it['price'] ?? 0) * (int) ($it['quantity'] ?? 1);
                    if ($mCat === 'food')
                        $foodRevenue += $itAmt;
                    elseif ($mCat === 'drinks')
                        $drinkRevenue += $itAmt;
                }
            }
        }
        $todayOrders = $myOrders;
    } catch (Exception $e) {
        $todayOrders = [];
        $todayRevenue = 0;
        $foodRevenue = 0;
        $drinkRevenue = 0;
    }

    renderHeader($isKiosk ? 'Kiosk Mode' : 'Recent Orders', ['nav' => $isKiosk ? 'kiosk' : 'pos', 'posTab' => 'recent']);
    ?>
    <div class="min-h-screen w-full bg-[#0f1110] <?php echo $isKiosk ? 'p-0' : 'p-6 lg:p-12'; ?> flex justify-center">
        <div class="max-w-screen-2xl w-full <?php echo $isKiosk ? 'space-y-0' : 'space-y-8'; ?>">

            <?php if (!$isKiosk): ?>
                <div
                    class="glass p-8 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-8 bg-gray-900/40">
                    <div class="flex items-center gap-6">
                        <div
                            class="w-16 h-16 rounded-xl bg-gray-800 border border-gray-700 flex items-center justify-center text-blue-400">
                            <i data-lucide="clipboard-list" class="w-8 h-8"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl lg:text-4xl font-bold text-white leading-tight mt-1">Today's Orders</h1>
                            <p class="text-sm font-medium text-gray-400 mt-2">
                                Welcome, <?php echo htmlspecialchars(strtoupper($userName)); ?> &bull;
                                <?php echo $welcomeDate; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="orders.php?view=recent&kiosk=1"
                            class="text-xs font-bold uppercase tracking-widest text-[#c5a059] bg-[#c5a059]/10 px-4 py-2 rounded-lg hover:bg-[#c5a059]/20 transition-all flex items-center gap-2">
                            <i data-lucide="monitor" class="w-4 h-4"></i> Enter Kiosk
                        </a>
                        <a href="cashier.php"
                            class="text-xs font-bold uppercase tracking-widest text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
                            Back to POS <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm font-medium text-gray-400">Your Orders</p>
                            <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                                <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-white leading-none tracking-tight"><?php echo count($todayOrders); ?>
                        </p>
                    </div>
                    <?php if ($showRevenue): ?>
                        <div
                            class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                            <div class="flex items-center justify-between mb-4">
                                <p class="text-sm font-medium text-gray-400">Food Revenue</p>
                                <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-emerald-400">
                                    <i data-lucide="utensils" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-white leading-none tracking-tight">
                                <?php echo number_format($foodRevenue, 0); ?> <span class="text-xs text-gray-500">ETB</span></p>
                        </div>
                        <div
                            class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                            <div class="flex items-center justify-between mb-4">
                                <p class="text-sm font-medium text-gray-400">Drinks Revenue</p>
                                <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-purple-400">
                                    <i data-lucide="glass-water" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-white leading-none tracking-tight">
                                <?php echo number_format($drinkRevenue, 0); ?> <span class="text-xs text-gray-500">ETB</span></p>
                        </div>
                        <div
                            class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                            <div class="flex items-center justify-between mb-4">
                                <p class="text-sm font-medium text-gray-400">Total Revenue</p>
                                <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                                    <i data-lucide="dollar-sign" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <p class="text-2xl font-bold text-white leading-none tracking-tight">
                                <?php echo number_format($todayRevenue, 0); ?> <span class="text-xs text-gray-500">ETB</span></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($isKiosk): ?>
                <a href="orders.php?view=recent"
                    class="fixed top-4 right-4 z-[300] px-5 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-black uppercase tracking-widest border border-red-500/30 transition-all flex items-center gap-2 shadow-lg">
                    <i data-lucide="minimize" class="w-4 h-4"></i> Exit Kiosk
                </a>
            <?php endif; ?>

            <div
                class="glass <?php echo $isKiosk ? 'rounded-none border-none min-h-screen overflow-hidden flex flex-col bg-[#0f1110]' : 'p-8 rounded-2xl border border-blue-900/40 bg-blue-950/20'; ?>">
                <?php if (!$isKiosk): ?>
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-500">
                                <i data-lucide="receipt" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-blue-400 mt-1">Order History</h3>
                                <p class="text-sm font-medium text-blue-400/60 mt-1">All orders placed today</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="<?php echo $isKiosk ? 'flex-1 overflow-y-auto px-4 pt-16 pb-6 space-y-4' : ''; ?>">
                    <?php if (empty($todayOrders)): ?>
                        <div class="py-16 flex flex-col items-center justify-center text-center">
                            <i data-lucide="inbox" class="w-14 h-14 mb-4 text-gray-600"></i>
                            <p class="text-sm font-medium text-gray-500">No orders placed today yet</p>
                        </div>
                    <?php else: ?>
                        <div class="<?php echo $isKiosk ? 'space-y-4 max-w-5xl mx-auto' : 'space-y-4'; ?>">
                            <?php foreach ($todayOrders as $o):
                                $status = strtolower($o['status'] ?? 'pending');
                                $itemCount = count($o['items'] ?? []);
                                $statusColors = [
                                    'preparing' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                                    'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                    'ready' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                    'served' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                    'completed' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                    'cancelled' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                ];
                                $statusCls = $statusColors[$status] ?? 'bg-white/5 text-muted-foreground border-white/10';
                                ?>
                                <div
                                    class="glass p-5 rounded-2xl border border-blue-900/30 bg-gray-900/60 hover:bg-gray-900 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-6">
                                    <div class="flex items-center gap-6 min-w-0">
                                        <div class="shrink-0 w-24 text-center">
                                            <p
                                                class="text-[10px] font-black text-blue-500 uppercase tracking-widest leading-none mb-1">
                                                #<?php echo htmlspecialchars($o['orderNumber'] ?? '—'); ?></p>
                                            <p class="text-[10px] text-gray-500">
                                                <?php echo date('h:i A', strtotime($o['createdAt'])); ?></p>
                                        </div>
                                        <div class="h-10 w-[1px] bg-gray-800 hidden md:block"></div>
                                        <div class="min-w-0">
                                            <h4 class="text-base font-bold text-white mb-1">
                                                <?php echo htmlspecialchars($o['tableNumber'] ?? '—'); ?></h4>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <?php if (!empty($o['menuTierName']) && strcasecmp($o['menuTierName'], 'Standard') !== 0): ?>
                                                    <span
                                                        class="text-[10px] font-black uppercase tracking-tighter px-2 py-0.5 rounded border bg-purple-500/10 text-purple-300 border-purple-500/20">
                                                        <?php echo htmlspecialchars($o['menuTierName']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span
                                                    class="text-[10px] font-black uppercase tracking-tighter px-2 py-0.5 rounded border <?php echo $statusCls; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex-1 min-w-0 md:px-6">
                                        <?php
                                        $food = array_filter($o['items'] ?? [], fn($i) => strtolower($i['mainCategory'] ?? '') === 'food');
                                        $drinks = array_filter($o['items'] ?? [], fn($i) => strtolower($i['mainCategory'] ?? '') === 'drinks');
                                        $others = array_filter($o['items'] ?? [], fn($i) => !in_array(strtolower($i['mainCategory'] ?? ''), ['food', 'drinks']));
                                        ?>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <?php if ($food): ?>
                                                <div
                                                    class="flex items-center gap-2 pr-3 border-r border-gray-800/50 mr-1 last:border-0 last:mr-0">
                                                    <div class="p-1 rounded bg-emerald-500/10 text-emerald-500/70"><i
                                                            data-lucide="utensils" class="w-3 h-3"></i></div>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach ($food as $item): ?>
                                                            <span
                                                                class="text-xs text-gray-400 bg-gray-800/40 px-2 py-1 rounded-lg border border-gray-700/30">
                                                                <span
                                                                    class="font-bold text-emerald-400/80"><?php echo (int) ($item['quantity'] ?? 1); ?>×</span>
                                                                <?php echo htmlspecialchars($item['name'] ?? 'Item'); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($drinks): ?>
                                                <div
                                                    class="flex items-center gap-2 pr-3 border-r border-gray-800/50 mr-1 last:border-0 last:mr-0">
                                                    <div class="p-1 rounded bg-blue-500/10 text-blue-500/70"><i
                                                            data-lucide="glass-water" class="w-3 h-3"></i></div>
                                                    <div class="flex flex-wrap gap-2">
                                                        <?php foreach ($drinks as $item): ?>
                                                            <span
                                                                class="text-xs text-gray-400 bg-gray-800/40 px-2 py-1 rounded-lg border border-gray-700/30">
                                                                <span
                                                                    class="font-bold text-blue-400/80"><?php echo (int) ($item['quantity'] ?? 1); ?>×</span>
                                                                <?php echo htmlspecialchars($item['name'] ?? 'Item'); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php foreach ($others as $item): ?>
                                                <span
                                                    class="text-xs text-gray-400 bg-gray-800/40 px-2 py-1 rounded-lg border border-gray-700/30">
                                                    <span
                                                        class="font-bold text-gray-300"><?php echo (int) ($item['quantity'] ?? 1); ?>×</span>
                                                    <?php echo htmlspecialchars($item['name'] ?? 'Item'); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div
                                        class="shrink-0 flex items-center gap-8 md:text-right border-t md:border-t-0 border-gray-800 pt-4 md:pt-0">
                                        <div class="hidden lg:block">
                                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-0.5">Total
                                                Amount</p>
                                            <p class="text-lg font-bold text-white font-serif">
                                                <?php echo number_format((float) ($o['totalAmount'] ?? 0), 0); ?> <span
                                                    class="text-xs text-blue-400 ml-0.5">ETB</span></p>
                                        </div>
                                        <div class="lg:hidden">
                                            <p class="text-lg font-bold text-white">
                                                <?php echo number_format((float) ($o['totalAmount'] ?? 0), 0); ?> <span
                                                    class="text-xs text-blue-400">ETB</span></p>
                                        </div>
                                        <div
                                            class="h-10 w-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition-all cursor-pointer">
                                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        renderFooter();
        exit;
}

$title = __('admin_orders.title');
$filter_tab = $_GET['tab'] ?? 'all';

// --- Filters ---
$filter_time = $_GET['time'] ?? 'today'; // Switched default to today for better UX
$filter_date = $_GET['date'] ?? '';
$filter_category = $_GET['category'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

try {
    $allOrders = db('orders')->findMany(['where' => ['isDeleted' => false]]);
    $allOrderItems = db('orderItems')->findMany(['where' => ['isDeleted' => false]]);
    $deletedOrders = db('orders')->findMany(['where' => ['isDeleted' => true]]);

    // Map items to orders
    $itemsMap = [];
    foreach ($allOrderItems as $item) {
        $itemsMap[$item['orderId']][] = $item;
    }
    foreach ($allOrders as &$o) {
        $o['items'] = $itemsMap[$o['id']] ?? [];
    }
    unset($o);
    foreach ($deletedOrders as &$o) {
        $o['items'] = $itemsMap[$o['id']] ?? [];
    }
    unset($o);

    // --- Time Filtering (Global) ---
    $now = new DateTime();
    
    $applyTimeFilter = function ($orders) use ($filter_time, $filter_date) {
        if ($filter_time === 'all') return $orders;
        
        $range = resolveReportDateRange($filter_time === 'custom' ? 'today' : $filter_time, $filter_date, $filter_date);
        $start = $range['start'];
        $end = $range['end'];
        
        return array_filter($orders, function ($o) use ($start, $end) {
            return isWithinReportRange($o['createdAt'] ?? null, $start, $end);
        });
    };

    $allOrders = array_values($applyTimeFilter($allOrders));
    $deletedOrders = array_values($applyTimeFilter($deletedOrders));

    $delayedOrders = [];

    // Helper process for metric calculations
    $processMetrics = function (&$orders) use ($now, &$delayedOrders) {
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
            unset($o);
        }
    };

    $processMetrics($allOrders);
    $processMetrics($deletedOrders);

    usort($allOrders, fn($a, $b) => strtotime($b['createdAt'] ?? 'now') - strtotime($a['createdAt'] ?? 'now'));

    // --- Multi-bucket Sidebar Stats ---
    $preparingBucket = array_filter($allOrders, fn($o) => in_array(strtolower($o['status'] ?? ''), ['preparing', 'pending', 'unconfirmed']));
    $readyBucket = array_filter($allOrders, fn($o) => strtolower($o['status'] ?? '') === 'ready');
    $servedBucket = array_filter($allOrders, fn($o) => in_array(strtolower($o['status'] ?? ''), ['served', 'completed']));

    $calcStats = function ($ordersArray) {
        $foodRev = 0;
        $drinkRev = 0;
        $totalDelay = 0;
        $totalPrep = 0;
        foreach ($ordersArray as $o) {
            foreach ($o['items'] as $item) {
                $itemMainCat = strtolower($item['mainCategory'] ?? '');
                if ($itemMainCat === 'food')
                    $foodRev += (float) $item['price'] * (int) $item['quantity'];
                elseif ($itemMainCat === 'drinks')
                    $drinkRev += (float) $item['price'] * (int) $item['quantity'];
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
            'avgPrep' => $count > 0 ? round($totalPrep / $count) : 0
        ];
    };

    $stats = [
        'all' => $calcStats($allOrders),
        'preparing' => $calcStats($preparingBucket),
        'ready' => $calcStats($readyBucket),
        'served' => $calcStats($servedBucket),
        'room' => ['count' => count(array_filter($allOrders, fn($o) => in_array('room', array_map('strtolower', (array) ($o['distributions'] ?? [])))))],
        'deleted' => ['count' => count($deletedOrders)]
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
        $activeCashierName = 'None';
        $cashierOrders = [];
    }

    // --- Filtering ---
    if ($filter_tab === 'room') {
        $filteredOrders = array_filter($allOrders, function ($o) {
            $dist = $o['distributions'] ?? [];
            return in_array('room', array_map('strtolower', (array) $dist))
                || in_array('reception', array_map('strtolower', (array) $dist));
        });
        $filter_status = 'room'; // For UI highlighting
    } else {
        $filteredOrders = match ($filter_status) {
            'all' => $allOrders,
            'preparing' => $preparingBucket,
            'ready' => $readyBucket,
            'served' => $servedBucket,
            'deleted' => $deletedOrders,
            'cashier' => $cashierOrders,
            'room' => array_filter($allOrders, fn($o) => in_array('room', array_map('strtolower', (array) ($o['distributions'] ?? [])))),
            default => $allOrders
        };
    }

    $filteredOrders = array_values($filteredOrders);

    if ($filter_category !== 'all') {
        $filteredOrders = array_filter($filteredOrders, function ($o) use ($filter_category) {
            foreach ($o['items'] as $it)
                if (strtolower($it['mainCategory'] ?? '') === strtolower($filter_category))
                    return true;
            return false;
        });
    }

    if ($search) {
        $filteredOrders = array_filter($filteredOrders, function ($o) use ($search) {
            $match = stripos($o['orderNumber'] ?? '', $search) !== false
                || stripos($o['tableNumber'] ?? '', $search) !== false
                || stripos($o['customerName'] ?? '', $search) !== false;
            
            if (!$match && !empty($o['items'])) {
                foreach ($o['items'] as $it) {
                    if (stripos($it['name'] ?? '', $search) !== false) {
                        return true;
                    }
                }
            }
            return $match;
        });
    }
    $filteredOrders = array_values($filteredOrders);

} catch (Exception $e) {
    $filteredOrders = [];
}

renderHeader($title);
?>

    <div class="h-screen w-full bg-[#0f1110] flex flex-col overflow-hidden">

        <!-- Delay Alert Banner -->
        <?php if (!empty($delayedOrders)): ?>
            <div id="delay-alert-banner"
                class="bg-red-950/20 border-y border-red-900/50 px-8 py-3 flex items-center gap-4 flex-shrink-0 z-40">
                <div
                    class="flex items-center gap-2 text-red-100 bg-red-600 px-3 py-1.5 rounded-lg text-xs font-bold shrink-0">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i> PREPARATION DELAY!
                </div>
                <div class="flex-1 flex gap-3 overflow-x-auto whitespace-nowrap custom-scrollbar pb-1">
                    <?php foreach ($delayedOrders as $do): ?>
                        <div class="glass px-3 py-1.5 rounded-lg border border-red-900/30 flex items-center gap-3 shrink-0">
                            <span class="text-white font-semibold text-sm">#<?php echo substr($do['orderNumber'], -4); ?></span>
                            <span class="text-red-400 font-medium text-xs"><?php echo $do['computedDelay']; ?>m Delay</span>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400 text-xs font-semibold"><?php echo $do['tableNumber']; ?></span>
                                <?php if (!empty($do['distributions'])):
                                    foreach ($do['distributions'] as $d): ?>
                                        <span class="text-xs bg-red-500/10 text-red-400 px-1.5 rounded-md font-medium">🚚
                                            <?php echo $d; ?></span>
                                    <?php endforeach; endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="w-full max-w-[1600px] mx-auto flex flex-col lg:grid lg:grid-cols-12 gap-6 px-4 lg:px-6 py-4 lg:py-6 flex-1 overflow-hidden">

            <!-- SIDEBAR/CAROUSEL -->
            <aside class="lg:col-span-3 flex flex-col shrink-0">
                <div class="glass rounded-xl lg:rounded-2xl border border-gray-700/50 bg-gray-800/80 overflow-hidden transition-all">
                    <h2 class="px-6 py-4 text-xs font-semibold text-gray-400 border-b border-gray-700/50 uppercase tracking-wider hidden lg:block">
                        FILTER BUCKETS</h2>
                    <div class="flex lg:flex-col overflow-x-auto lg:overflow-y-auto lg:divide-y divide-gray-700/30 custom-scrollbar p-2 lg:p-0 gap-2 lg:gap-0 no-scrollbar">
                        <?php
                        $tabs = [
                            ['id' => 'all', 'label' => __('admin_orders.all_orders'), 'icon' => 'clipboard-list', 'data' => $stats['all'], 'color' => 'orange'],
                            ['id' => 'preparing', 'label' => __('admin_orders.preparing'), 'icon' => 'flame', 'data' => $stats['preparing'], 'color' => 'red'],
                            ['id' => 'served', 'label' => __('admin_orders.served'), 'icon' => 'package-check', 'data' => $stats['served'], 'color' => 'blue'],
                            ['id' => 'cashier', 'label' => 'CASHIER', 'icon' => 'users', 'data' => ['count' => count($cashierNames)], 'color' => 'purple'],
                            ['id' => 'deleted', 'label' => 'DELETED', 'icon' => 'trash-2', 'data' => $stats['deleted'], 'color' => 'white'],
                        ];
                        foreach ($tabs as $tab):
                            $isActive = $filter_status === $tab['id'];
                            $href = "?status={$tab['id']}&time=$filter_time&date=$filter_date&category=$filter_category";
                            $cls = $isActive ? "bg-[#c5a059]/10 lg:bg-white/[0.05] border-l-0 lg:border-l-4 border-[#c5a059] ring-1 ring-[#c5a059]/30 lg:ring-0" : "hover:bg-white/[0.02] border-l-0 lg:border-l-4 border-transparent";
                            $tColor = $tab['color'];
                            ?>
                            <a href="<?php echo $href; ?>"
                                class="flex items-center gap-2 lg:gap-4 px-4 lg:px-6 py-2.5 lg:py-4 transition-colors group rounded-lg lg:rounded-none shrink-0 <?php echo $cls; ?>">
                                <div
                                    class="w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-<?php echo $tColor; ?>-500/10 flex items-center justify-center flex-shrink-0 text-<?php echo $tColor; ?>-400">
                                    <i data-lucide="<?php echo $tab['icon']; ?>" class="w-4 h-4 lg:w-5 lg:h-5"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs lg:text-sm font-semibold text-gray-200 whitespace-nowrap"><?php echo $tab['label']; ?></p>
                                    <?php if (isset($tab['data']['avgPrep'])): ?>
                                        <div class="hidden lg:flex items-center gap-2 mt-1">
                                            <span class="text-xs text-gray-500 font-medium"><?php echo $tab['data']['avgPrep']; ?>m avg</span>
                                            <span class="w-1 h-1 rounded-full bg-gray-600"></span>
                                            <span class="text-xs font-semibold text-emerald-400"><?php echo number_format($tab['data']['foodRev'] + $tab['data']['drinkRev'], 0); ?> Br</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span
                                    class="text-[10px] lg:text-xs font-bold w-5 h-5 lg:w-7 lg:h-7 rounded-full <?php echo $isActive ? 'bg-[#c5a059] text-gray-900' : 'bg-gray-700 text-gray-400'; ?> flex items-center justify-center shrink-0">
                                    <?php echo $tab['data']['count'] ?? 0; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Insights Block - Desktop Only -->
                <div class="hidden lg:block mt-6 glass p-6 rounded-2xl border border-gray-700/50 bg-gray-800/80">
                    <h3 class="text-white font-semibold text-sm mb-2 flex items-center gap-2">
                        <i data-lucide="zap" class="w-4 h-4 text-emerald-400"></i>
                        <?php echo __('admin_orders.need_insights'); ?>
                    </h3>
                    <p class="text-xs text-gray-400 mb-4"><?php echo __('admin_orders.check_reports'); ?></p>
                    <div class="w-full h-1.5 bg-gray-700 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full w-[94%]"></div>
                    </div>
                </div>
            </aside>

            <!-- MAIN PANEL -->
            <main class="lg:col-span-9 flex flex-col h-full overflow-hidden">

                <div
                    class="glass rounded-2xl border border-gray-700/50 bg-gray-900/40 flex flex-col h-full overflow-hidden">
                    <!-- Header Controls -->
                    <div
                        class="px-4 lg:px-8 py-4 lg:py-6 border-b border-gray-700/50 flex flex-col md:flex-row md:items-center justify-between gap-4 lg:gap-6">
                        <div>
                            <h1 class="text-xl lg:text-3xl font-bold text-white tracking-tight leading-none text-center md:text-left">
                                <?php echo __('admin_orders.order_management'); ?></h1>
                            <div class="flex flex-col md:flex-row items-center gap-4 mt-4">
                                <span
                                    class="text-[10px] lg:text-xs font-semibold text-gray-400 lg:text-gray-500 uppercase tracking-widest"><?php echo count($filteredOrders); ?>
                                    <?php echo __('admin_orders.orders_count'); ?></span>
                                <div class="flex items-center gap-1 p-1 bg-gray-800/80 rounded-lg overflow-x-auto no-scrollbar w-full md:w-auto">
                                    <?php foreach (['today', 'week', 'month', 'all'] as $t): ?>
                                        <a href="?time=<?php echo $t; ?>&status=<?php echo $filter_status; ?>&category=<?php echo $filter_category; ?>&date=<?php echo $filter_date; ?>"
                                            class="px-2.5 lg:px-3 py-1.5 rounded-md text-[10px] lg:text-xs font-medium whitespace-nowrap <?php echo $filter_time === $t ? 'bg-[#c5a059] text-gray-900' : 'text-gray-400 hover:text-gray-200'; ?> transition-colors">
                                            <?php echo ucfirst($t); ?>
                                        </a>
                                    <?php endforeach; ?>

                                    <div class="flex items-center gap-2 ml-2 pl-2 border-l border-gray-700">
                                        <form method="GET" class="flex items-center gap-2 relative group">
                                            <input type="hidden" name="time" value="custom">
                                            <input type="hidden" name="status" value="<?php echo $filter_status; ?>">
                                            <input type="hidden" name="category"
                                                value="<?php echo $filter_category; ?>">

                                            <div class="relative">
                                                <input type="date" name="date" value="<?php echo $filter_date; ?>"
                                                    onchange="this.form.submit()"
                                                    class="bg-gray-900 border border-gray-700 rounded-lg pl-3 pr-2 py-1.5 text-[10px] font-bold text-gray-200 focus:border-[#c5a059] outline-none w-36 transition-all cursor-pointer">
                                                <i data-lucide="calendar"
                                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500 pointer-events-none"></i>
                                            </div>

                                            <?php if ($filter_time === 'custom'): ?>
                                                <span
                                                    class="text-[10px] font-bold text-[#c5a059] uppercase tracking-tighter">Custom
                                                    Date</span>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex p-1 bg-gray-800/80 rounded-lg border border-gray-700 self-center md:self-auto overflow-x-auto no-scrollbar">
                            <?php foreach (['all' => 'All', 'food' => 'Food', 'drinks' => 'Drinks'] as $k => $v): ?>
                                <a href="?category=<?php echo $k; ?>&time=<?php echo $filter_time; ?>&status=<?php echo $filter_status; ?>&date=<?php echo $filter_date; ?>"
                                    class="px-4 py-2 rounded-md text-xs lg:text-sm font-semibold whitespace-nowrap <?php echo $filter_category === $k ? 'bg-gray-600 text-white shadow-sm' : 'text-gray-400 hover:text-white'; ?> transition-colors">
                                    <?php echo $v; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Search + Action Bar -->
                    <div class="px-4 lg:px-8 py-3 lg:py-4 border-b border-gray-700/50 flex items-center gap-3 lg:gap-6 bg-gray-800/30">
                        <div class="relative flex-1 max-w-sm">
                            <i data-lucide="search"
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                            <input type="text" id="order-search" value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Search..."
                                class="w-full bg-gray-900/50 border border-gray-700/50 rounded-lg py-2 lg:py-2.5 pl-9 pr-4 text-xs lg:text-sm text-gray-200 placeholder:text-gray-500 focus:outline-none focus:ring-1 focus:ring-[#c5a059] transition-all">
                        </div>

                        <?php if ($filter_status !== 'cashier'): ?>
                            <div class="flex items-center gap-2 lg:gap-3 ml-auto">
                                <button onclick="handleBulkAction('bulk-serve')"
                                    class="flex items-center gap-2 px-3 lg:px-5 py-2 lg:py-2.5 bg-[#c5a059] text-gray-900 text-xs lg:text-sm font-bold rounded-lg hover:bg-[#b08d4a] active:scale-95 transition-all">
                                    <i data-lucide="check-check" class="w-4 h-4"></i> <span class="hidden sm:inline">Mark All as Served</span>
                                </button>
                                <button
                                    onclick="handleBulkAction('<?php echo $filter_status === 'deleted' ? 'empty-trash' : 'bulk-delete'; ?>')"
                                    class="px-3 py-2.5 bg-gray-800 border border-gray-700 text-gray-400 text-sm rounded-lg hover:bg-red-600 hover:text-white hover:border-red-500 transition-colors">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 lg:p-8 custom-scrollbar flex-1 overflow-y-auto bg-[#0f1110]">
                        <?php if ($filter_status === 'cashier'): ?>
                            <!-- Cashier Carousel Header -->
                            <div class="space-y-6 lg:space-y-8 animate-in fade-in">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 lg:w-14 lg:h-14 rounded-full bg-gray-800 border border-gray-700 flex items-center justify-center text-[#c5a059]">
                                            <i data-lucide="user-round" class="w-5 h-5 lg:w-6 lg:h-6"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl lg:text-2xl font-bold text-white"><?php echo $activeCashierName; ?></h3>
                                            <p class="text-[10px] lg:text-xs text-gray-500 font-semibold uppercase tracking-wider mt-1">
                                                PRIMARY FLOOR CASHIER</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 bg-gray-800 p-2 rounded-xl border border-gray-700">
                                        <a href="?status=cashier&cashierIdx=<?php echo $activeCashierIdx - 1; ?>&time=<?php echo $filter_time; ?>&date=<?php echo $filter_date; ?>&category=<?php echo $filter_category; ?>"
                                            class="w-10 h-10 rounded-lg hover:bg-gray-700 flex items-center justify-center text-gray-400 hover:text-white transition-colors"><i
                                                data-lucide="chevron-left"></i></a>
                                        <span
                                            class="text-sm font-bold text-[#c5a059] px-3"><?php echo $activeCashierIdx + 1; ?>
                                            &mdash; <?php echo count($cashierNames); ?></span>
                                        <a href="?status=cashier&cashierIdx=<?php echo $activeCashierIdx + 1; ?>&time=<?php echo $filter_time; ?>&date=<?php echo $filter_date; ?>&category=<?php echo $filter_category; ?>"
                                            class="w-10 h-10 rounded-lg hover:bg-gray-700 flex items-center justify-center text-gray-400 hover:text-white transition-colors"><i
                                                data-lucide="chevron-right"></i></a>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="glass p-6 rounded-xl border border-gray-700">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                            COLLECTED REVENUE</p>
                                        <div class="flex items-baseline gap-2">
                                            <h4 class="text-4xl font-bold text-emerald-400">
                                                <?php echo number_format($cashierGroups[$activeCashierName]['revenue'] ?? 0, 0); ?>
                                            </h4>
                                            <span class="text-emerald-400/60 font-medium text-sm">ETB</span>
                                        </div>
                                    </div>
                                    <div class="glass p-6 rounded-xl border border-gray-700">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">TICKET
                                            COUNT</p>
                                        <h4 class="text-4xl font-bold text-white"><?php echo count($cashierOrders); ?></h4>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <?php foreach ($cashierOrders as $co): ?>
                                        <div
                                            class="glass px-6 py-4 rounded-xl border border-gray-700 hover:border-[#c5a059]/30 transition-colors flex items-center justify-between group">
                                            <div class="flex items-center gap-5">
                                                <span
                                                    class="text-xs font-medium text-gray-500"><?php echo date('H:i', strtotime($co['createdAt'])); ?></span>
                                                <span
                                                    class="text-lg font-bold text-[#c5a059]">#<?php echo substr($co['orderNumber'], -4); ?></span>
                                                <span
                                                    class="text-xs font-semibold bg-gray-800 px-2.5 py-1 rounded-md text-gray-300 border border-gray-700"><?php echo $co['tableNumber']; ?></span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span
                                                    class="text-md font-bold text-white group-hover:text-emerald-400 transition-colors"><?php echo number_format($co['totalAmount'], 0); ?>
                                                    Br</span>
                                                <i data-lucide="chevron-right"
                                                    class="w-4 h-4 text-gray-500 group-hover:text-[#c5a059] transition-colors"></i>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        <?php elseif (empty($filteredOrders)): ?>
                            <div class="flex flex-col items-center justify-center h-full py-32 opacity-10 space-y-8">
                                <span class="text-[180px] leading-none">🍃</span>
                                <div class="text-center">
                                    <h3 class="text-3xl font-bold text-gray-400 mb-2">
                                        <?php echo __('admin_orders.quiet_for_now'); ?></h3>
                                    <p class="text-sm font-medium text-gray-600 uppercase tracking-widest">
                                        <?php echo __('admin_orders.no_orders_found'); ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php foreach ($filteredOrders as $o):
                                    $status = strtolower($o['status'] ?? 'pending');
                                    $tColor = $o['delayColor'];
                                    $metrics = ['totalTaken' => $o['computedTaken'], 'delay' => $o['computedDelay'], 'threshold' => $o['thresholdMinutes'] ?? 20];
                                    $searchString = strtolower(($o['orderNumber'] ?? '') . ' ' . ($o['tableNumber'] ?? '') . ' ' . ($o['customerName'] ?? ''));
                                    if (!empty($o['items'])) {
                                        foreach ($o['items'] as $si) {
                                            $searchString .= ' ' . strtolower($si['name'] ?? '');
                                        }
                                    }
                                    ?>
                                    <div data-search="<?php echo htmlspecialchars($searchString); ?>"
                                        class="order-card bg-gray-800/60 border border-gray-700/50 rounded-2xl px-4 lg:px-8 py-4 lg:py-5 hover:border-[#c5a059]/30 hover:bg-gray-800 transition-colors group relative flex flex-col lg:flex-row lg:items-center gap-4 lg:gap-8">

                                        <!-- LEFT SECTION -->
                                        <div class="lg:w-48 flex-shrink-0">
                                            <p class="text-[10px] lg:text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 lg:mb-2 text-center lg:text-left">
                                                <?php echo date('M d — h:i A', strtotime($o['createdAt'])); ?></p>
                                            <h4 class="text-xl lg:text-2xl font-bold text-[#c5a059] leading-none mb-2 lg:mb-3 text-center lg:text-left">
                                                #<?php echo substr($o['orderNumber'], -4); ?></h4>
                                            <div class="flex flex-wrap justify-center lg:justify-start gap-2 text-[10px] lg:text-xs">
                                                <span
                                                    class="font-semibold text-gray-400 bg-gray-900 border border-gray-700 px-2 py-0.5 rounded-md uppercase"><?php echo $o['floorNumber'] ?? 'GF'; ?></span>
                                                <span
                                                    class="font-semibold text-[#c5a059] bg-[#c5a059]/10 border border-[#c5a059]/20 px-2 py-0.5 rounded-md uppercase"><?php echo $o['tableNumber']; ?></span>
                                                <?php if (!empty($o['menuTierName']) && strcasecmp($o['menuTierName'], 'Standard') !== 0): ?>
                                                    <span
                                                        class="font-semibold text-purple-300 bg-purple-500/10 border border-purple-500/20 px-2 py-0.5 rounded-md uppercase"><?php echo htmlspecialchars($o['menuTierName']); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($o['distributions'])):
                                                    foreach ($o['distributions'] as $d): ?>
                                                        <span
                                                            class="font-semibold text-orange-400 bg-orange-500/10 border border-orange-500/20 px-2 py-0.5 rounded-md uppercase">🚚
                                                            <?php echo $d; ?></span>
                                                    <?php endforeach; endif; ?>
                                            </div>
                                        </div>

                                        <!-- MIDDLE SECTION -->
                                        <div class="flex-1 min-w-0 border-t border-b lg:border-0 border-gray-700/30 py-4 lg:py-0">
                                            <div class="flex items-center justify-center lg:justify-start gap-3 mb-3">
                                                <span
                                                    class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider bg-<?php echo $tColor; ?>-500/10 text-<?php echo $tColor; ?>-400 border border-<?php echo $tColor; ?>-500/20">
                                                    <i data-lucide="<?php echo match ($status) { 'ready' => 'check', 'preparing' => 'flame', 'served' => 'package-check', default => 'clock'}; ?>"
                                                        class="w-3.5 h-3.5 inline mr-1 mb-0.5"></i>
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-700"></span>
                                                <span
                                                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider"><?php echo count($o['items']); ?>
                                                    Items</span>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-x-5 gap-y-3">
                                                <?php
                                                $food = array_filter($o['items'] ?? [], fn($i) => strtolower($i['mainCategory'] ?? '') === 'food');
                                                $drinks = array_filter($o['items'] ?? [], fn($i) => strtolower($i['mainCategory'] ?? '') === 'drinks');
                                                $others = array_filter($o['items'] ?? [], fn($i) => !in_array(strtolower($i['mainCategory'] ?? ''), ['food', 'drinks']));
                                                ?>

                                                <?php if ($food): ?>
                                                    <div
                                                        class="flex items-center gap-3 pr-5 lg:border-r border-gray-700/50 mr-1 last:border-0 last:mr-0">
                                                        <div
                                                            class="p-1.2 lg:p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm">
                                                            <i data-lucide="utensils" class="w-3.5 lg:w-4 h-3.5 lg:h-4"></i></div>
                                                        <div class="flex flex-wrap gap-x-4 lg:gap-x-5 gap-y-2">
                                                            <?php foreach ($food as $item):
                                                                $isVIP = !empty($item['menuTierName']) && strcasecmp($item['menuTierName'], 'Standard') !== 0; ?>
                                                                <div class="flex items-center gap-2.5">
                                                                    <div
                                                                        class="w-6 h-6 rounded-md bg-gray-900 border border-gray-700 flex items-center justify-center text-xs font-bold text-emerald-400">
                                                                        <?php echo $item['quantity']; ?></div>
                                                                    <div class="relative flex items-center gap-2">
                                                                        <span
                                                                            class="text-sm font-semibold text-gray-200"><?php echo $item['name']; ?></span>
                                                                        <?php if ($isVIP): ?><span
                                                                                class="text-[9px] bg-purple-500/20 text-purple-300 px-1 py-0.5 rounded font-bold"><?php echo htmlspecialchars($item['menuTierName'] ?? 'VIP'); ?></span><?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($drinks): ?>
                                                    <div
                                                        class="flex items-center gap-3 pr-5 lg:border-r border-gray-700/50 mr-1 last:border-0 last:mr-0">
                                                        <div
                                                            class="p-1.2 lg:p-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 shadow-sm">
                                                            <i data-lucide="glass-water" class="w-3.5 lg:w-4 h-3.5 lg:h-4"></i></div>
                                                        <div class="flex flex-wrap gap-x-4 lg:gap-x-5 gap-y-2">
                                                            <?php foreach ($drinks as $item):
                                                                $isVIP = !empty($item['menuTierName']) && strcasecmp($item['menuTierName'], 'Standard') !== 0; ?>
                                                                <div class="flex items-center gap-2.5">
                                                                    <div
                                                                        class="w-6 h-6 rounded-md bg-gray-900 border border-gray-700 flex items-center justify-center text-xs font-bold text-blue-400">
                                                                        <?php echo $item['quantity']; ?></div>
                                                                    <div class="relative flex items-center gap-2">
                                                                        <span
                                                                            class="text-sm font-semibold text-gray-200"><?php echo $item['name']; ?></span>
                                                                        <?php if ($isVIP): ?><span
                                                                                class="text-[9px] bg-purple-500/20 text-purple-300 px-1 py-0.5 rounded font-bold"><?php echo htmlspecialchars($item['menuTierName'] ?? 'VIP'); ?></span><?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <?php foreach ($others as $item):
                                                    $isVIP = !empty($item['menuTierName']) && strcasecmp($item['menuTierName'], 'Standard') !== 0; ?>
                                                    <div class="flex items-center gap-2.5">
                                                        <div
                                                            class="w-6 h-6 rounded-md bg-gray-900 border border-gray-700 flex items-center justify-center text-xs font-bold text-gray-400">
                                                            <?php echo $item['quantity']; ?></div>
                                                        <div class="relative flex items-center gap-2">
                                                            <span
                                                                class="text-sm font-semibold text-gray-200"><?php echo $item['name']; ?></span>
                                                            <?php if ($isVIP): ?><span
                                                                    class="text-[9px] bg-purple-500/20 text-purple-300 px-1 py-0.5 rounded font-bold"><?php echo htmlspecialchars($item['menuTierName'] ?? 'VIP'); ?></span><?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- RIGHT SECTION -->
                                        <div
                                            class="lg:w-48 flex-shrink-0 flex justify-between lg:justify-end items-center gap-4 lg:gap-6">
                                            <div class="text-right flex flex-col items-end">
                                                <div
                                                    class="flex items-center justify-end gap-2 bg-<?php echo $tColor; ?>-500/10 px-3 py-1.5 rounded-lg border border-<?php echo $tColor; ?>-500/20 mb-2">
                                                    <div class="text-right">
                                                        <p
                                                            class="text-xs font-bold text-<?php echo $tColor; ?>-400 leading-none">
                                                            <?php echo $o['computedDelay'] > 0 ? "+{$o['computedDelay']}m" : (strtolower($status) === 'ready' ? 'READY' : 'ON TIME'); ?>
                                                        </p>
                                                    </div>
                                                    <i data-lucide="<?php echo $o['computedDelay'] > 0 ? 'alert-circle' : 'check-circle-2'; ?>"
                                                        class="w-4 h-4 text-<?php echo $tColor; ?>-400"></i>
                                                </div>
                                                <div class="flex items-baseline justify-end gap-1.5">
                                                    <h4 class="text-2xl font-bold text-white tracking-tight">
                                                        <?php echo number_format($o['totalAmount'], 0); ?></h4>
                                                    <span class="text-gray-500 font-bold text-xs uppercase">Br</span>
                                                </div>
                                            </div>

                                            <?php if (!$o['isDeleted']): ?>
                                                <button
                                                    onclick="handleDeleteOrder('<?php echo $o['id']; ?>', '<?php echo substr($o['orderNumber'], -4); ?>')"
                                                    class="w-10 h-10 rounded-lg border border-gray-700 bg-gray-800 flex items-center justify-center text-gray-500 hover:bg-red-600 hover:text-white hover:border-red-500 transition-colors lg:opacity-0 group-hover:opacity-100">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
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
        document.getElementById('order-search')?.addEventListener('input', function (e) {
            const term = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.order-card');
            
            cards.forEach(card => {
                const searchData = card.getAttribute('data-search') || '';
                if (searchData.includes(term)) {
                    card.classList.remove('hidden');
                    card.classList.add('flex');
                } else {
                    card.classList.remove('flex');
                    card.classList.add('hidden');
                }
            });
        });

        document.getElementById('order-search')?.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                const url = new URL(window.location);
                url.searchParams.set('search', this.value);
                window.location.href = url.href;
            }
        });

        // Force Lucide to render any new icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>

    <style>
        .glass {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        #efficiency-bar {
            transition: width 1s ease-out;
        }
    </style>

    <?php renderFooter(); ?>