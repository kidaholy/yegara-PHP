<?php
/**
 * Refined Strategic Reporting Module
 */
require_once 'includes/layout.php';

requireAuth(['admin']);

$title = "Financial Reports";
$period = $_GET['period'] ?? 'today';

// Date range logic
$startDate = date('Y-m-d 00:00:00');
switch ($period) {
    case 'week': $startDate = date('Y-m-d 00:00:00', strtotime('-7 days')); break;
    case 'month': $startDate = date('Y-m-d 00:00:00', strtotime('-30 days')); break;
    case 'year': $startDate = date('Y-m-d 00:00:00', strtotime('-365 days')); break;
}

try {
    $orders = db('orders')->findMany([
        'where' => [
            'isDeleted' => false,
            'createdAt' => ['gte' => $startDate],
            'status' => 'completed'
        ]
    ]);

    // Revenue Segregation
    $revenueByCat = [];
    $revenueByStaff = [];
    $totalRev = 0;
    
    // Fetch all order items for these orders
    $orderIds = array_map(fn($o) => $o['id'], $orders);
    if (!empty($orderIds)) {
        $items = db('order_items')->findMany([
            'where' => ['orderId' => ['in' => $orderIds], 'isDeleted' => false]
        ]);
    } else {
        $items = [];
    }

    foreach ($items as $item) {
        $cat = $item['mainCategory'] ?? 'Uncategorized';
        $revenueByCat[$cat] = ($revenueByCat[$cat] ?? 0) + ($item['price'] * $item['quantity']);
    }

    foreach ($orders as $order) {
        $totalRev += $order['totalAmount'];
        $staffId = $order['cashierId'] ?? 'unknown';
        $revenueByStaff[$staffId] = ($revenueByStaff[$staffId] ?? 0) + $order['totalAmount'];
    }

    // Sort staff by performance
    arsort($revenueByStaff);
    
    // Fetch user names for staff reporting
    $users = db('users')->findMany(['where' => ['id' => ['in' => array_keys($revenueByStaff)]]]);
    $userMap = [];
    foreach ($users as $u) $userMap[$u['id']] = $u['name'];

} catch (Exception $e) {
    $orders = []; $revenueByCat = []; $revenueByStaff = []; $totalRev = 0;
}

renderHeader($title);
?>

<div class="space-y-10 max-w-[1400px] mx-auto animate-in">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h1 class="text-3xl font-bold font-playfair tracking-tight text-white">Strategic Reports</h1>
            <p class="text-xs text-muted-foreground font-medium opacity-50">Decision support and performance benchmarking</p>
        </div>
        <div class="flex bg-white/5 p-1 rounded-xl border border-white/5 items-center">
            <?php foreach (['today', 'week', 'month', 'year'] as $p): ?>
                <a href="?period=<?php echo $p; ?>" 
                   class="px-5 py-2 rounded-lg text-xs font-bold transition-all <?php echo $period === $p ? 'bg-white text-slate-950 shadow-lg' : 'text-muted-foreground hover:text-white'; ?>">
                    <?php echo ucfirst($p); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass p-8 rounded-[2rem] border border-white/5 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-20 h-20 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground opacity-50 mb-2">Total Period Revenue</p>
            <h2 class="text-4xl font-black text-white"><?php echo number_format($totalRev, 2); ?> <span class="text-lg opacity-40 font-bold">Br</span></h2>
            <div class="mt-6 flex items-center gap-2 text-xs font-bold text-emerald-500">
                <i data-lucide="trending-up" class="w-4 h-4"></i>
                Verified Transactions
            </div>
        </div>

        <div class="glass p-8 rounded-[2rem] border border-white/5 md:col-span-2">
            <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground opacity-50 mb-6">Revenue Segregation (By Category)</p>
            <div class="space-y-6">
                <?php foreach ($revenueByCat as $cat => $val): 
                    $perc = ($totalRev > 0) ? ($val / $totalRev) * 100 : 0;
                ?>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-white"><?php echo $cat; ?></span>
                        <span class="text-muted-foreground"><?php echo number_format($perc, 1); ?>% (<?php echo number_format($val, 2); ?> Br)</span>
                    </div>
                    <div class="w-full bg-white/5 h-1.5 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-1000" style="width: <?php echo $perc; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($revenueByCat)): ?>
                    <p class="text-sm text-muted-foreground italic opacity-40">No categorical data available for this period.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Performance Benchmarking Table -->
    <div class="glass rounded-[2.5rem] border border-white/5 overflow-hidden shadow-2xl">
        <div class="px-8 py-6 border-b border-white/5 bg-white/[0.01] flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-500">
                    <i data-lucide="award" class="w-5 h-5"></i>
                </div>
                <h3 class="font-bold text-white tracking-tight text-lg">Cashier Contributions</h3>
            </div>
            <button class="text-[10px] font-bold uppercase tracking-widest text-blue-400 hover:text-blue-300">Download CSV</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest border-b border-white/5">
                        <th class="px-8 py-5 text-left opacity-40">Rank</th>
                        <th class="px-8 py-5 text-left opacity-40">Team Member</th>
                        <th class="px-8 py-5 text-center opacity-40">Orders</th>
                        <th class="px-8 py-5 text-right opacity-40">Contribution Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.03]">
                    <?php 
                    $rank = 1;
                    foreach ($revenueByStaff as $id => $val): 
                        $orderCount = count(array_filter($orders, fn($o) => ($o['cashierId'] ?? 'unknown') === $id));
                    ?>
                    <tr class="text-sm hover:bg-white/[0.02] transition-colors group">
                        <td class="px-8 py-5">
                            <div class="w-6 h-6 rounded-lg bg-white/5 flex items-center justify-center text-[10px] font-bold text-muted-foreground group-hover:bg-blue-500 group-hover:text-white transition-all">
                                <?php echo $rank++; ?>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500/20 to-purple-600/20 border border-white/10 flex items-center justify-center text-[10px] font-bold text-white">
                                    <?php echo strtoupper(substr($userMap[$id] ?? 'U', 0, 1)); ?>
                                </div>
                                <span class="font-bold text-slate-200"><?php echo $userMap[$id] ?? 'Deleted User'; ?></span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center font-bold text-muted-foreground">
                            <?php echo $orderCount; ?>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <span class="text-sm font-bold text-white"><?php echo number_format($val, 2); ?> <span class="opacity-40 italic">Br</span></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($revenueByStaff)): ?>
                    <tr>
                        <td colspan="4" class="px-8 py-12 text-center text-muted-foreground italic opacity-40 text-sm">No performance data found for this period.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderHeader(); ?>
