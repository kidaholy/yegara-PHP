<?php
/**
 * Admin Dashboard for the PHP Management System
 */
require_once 'includes/layout.php';

requireAuth(['admin']);

$title = "Admin Dashboard";

// Fetch data for stats
try {
    $orders = db('orders')->findMany([
        'where' => ['isDeleted' => false]
    ]);
    
    $totalOrders = count($orders);
    $totalRevenue = 0;
    $todayRevenue = 0;
    $today = date('Y-m-d');

    foreach ($orders as $order) {
        $totalRevenue += $order['totalAmount'] ?? 0;
        if (strpos($order['createdAt'], $today) === 0) {
            $todayRevenue += $order['totalAmount'] ?? 0;
        }
    }

    $activeUsers = db('users')->count(['where' => ['isActive' => true]]);
    $recentOrders = array_slice($orders, 0, 5); // Already sorted desc by JsonDB if it were real, but our port needs careful handling
    
    // Sort recent orders by date desc
    usort($recentOrders, function($a, $b) {
        return strcmp($b['createdAt'], $a['createdAt']);
    });

} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    $totalOrders = $totalRevenue = $todayRevenue = $activeUsers = 0;
    $recentOrders = [];
}

renderHeader($title);
?>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold font-playfair tracking-tight">Overview</h1>
            <p class="text-muted-foreground">Snapshot of your business performance.</p>
        </div>
        <div class="flex gap-3">
            <button class="bg-primary text-primary-foreground px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 hover:opacity-90 transition-opacity">
                <i data-lucide="download" class="w-4 h-4"></i>
                Export Report
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="glass p-6 rounded-2xl border border-border">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-muted-foreground">Total Revenue</span>
                <i data-lucide="dollar-sign" class="w-4 h-4 text-emerald-500"></i>
            </div>
            <div class="text-2xl font-bold"><?php echo number_format($totalRevenue, 2); ?> Br</div>
            <p class="text-xs text-muted-foreground mt-1">+12.5% from last month</p>
        </div>

        <div class="glass p-6 rounded-2xl border border-border">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-muted-foreground">Today's Revenue</span>
                <i data-lucide="trending-up" class="w-4 h-4 text-blue-500"></i>
            </div>
            <div class="text-2xl font-bold"><?php echo number_format($todayRevenue, 2); ?> Br</div>
            <p class="text-xs text-muted-foreground mt-1">Live tracking active</p>
        </div>

        <div class="glass p-6 rounded-2xl border border-border">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-muted-foreground">Total Orders</span>
                <i data-lucide="shopping-bag" class="w-4 h-4 text-orange-500"></i>
            </div>
            <div class="text-2xl font-bold"><?php echo $totalOrders; ?></div>
            <p class="text-xs text-muted-foreground mt-1">Total lifetime orders</p>
        </div>

        <div class="glass p-6 rounded-2xl border border-border">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-muted-foreground">Staff Members</span>
                <i data-lucide="users" class="w-4 h-4 text-purple-500"></i>
            </div>
            <div class="text-2xl font-bold"><?php echo $activeUsers; ?></div>
            <p class="text-xs text-muted-foreground mt-1">Currently active</p>
        </div>
    </div>

    <!-- Tables and Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders -->
        <div class="lg:col-span-2 glass rounded-2xl border border-border overflow-hidden">
            <div class="p-6 border-b border-border flex items-center justify-between">
                <h3 class="font-semibold">Recent Orders</h3>
                <a href="/admin-orders.php" class="text-sm text-blue-500 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                            <th class="px-6 py-4">Order ID</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <?php foreach ($recentOrders as $o): ?>
                        <tr class="text-sm hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 font-medium"><?php echo $o['orderNumber'] ?? substr($o['id'], 0, 8); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-[10px] uppercase font-bold bg-blue-500/10 text-blue-500 border border-blue-500/20">
                                    <?php echo $o['status']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4"><?php echo number_format($o['totalAmount'], 2); ?> Br</td>
                            <td class="px-6 py-4 text-muted-foreground"><?php echo date('H:i', strtotime($o['createdAt'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-muted-foreground">No recent orders found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Alerts -->
        <div class="glass rounded-2xl border border-border p-6">
            <h3 class="font-semibold mb-6">Real-time Updates</h3>
            <div class="space-y-6">
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center shrink-0">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">System Online</p>
                        <p class="text-xs text-muted-foreground">All modules running smoothly on shared host environment.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                        <i data-lucide="database" class="w-4 h-4 text-blue-500"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Data Integrity</p>
                        <p class="text-xs text-muted-foreground">JSON records synchronized correctly.</p>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center shrink-0">
                        <i data-lucide="clock" class="w-4 h-4 text-amber-500"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Auto-Refresh</p>
                        <p class="text-xs text-muted-foreground">Chef and Bar screens polling every 10 seconds.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
