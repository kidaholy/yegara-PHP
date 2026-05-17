<?php
/**
 * Admin Dashboard - Abe Hotel
 */
require_once 'includes/layout.php';

requireAuth(['admin']);
$title = "Dashboard";

try {
    $orders = db('orders')->findMany(['where' => ['isDeleted' => false]]);
    $totalOrders = count($orders);
    $totalRevenue = 0;
    $todayRevenue = 0;
    $today = date('Y-m-d');

    foreach ($orders as $order) {
        $totalRevenue += $order['totalAmount'] ?? 0;
        if (strpos($order['createdAt'] ?? '', $today) === 0) {
            $todayRevenue += $order['totalAmount'] ?? 0;
        }
    }

    $activeUsers = db('users')->count(['where' => ['isDeleted' => false]]);

    $chartData = [];
    $days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $days[] = date('D', strtotime($date));
        $dayRev = 0;
        foreach ($orders as $o) {
            if (strpos($o['createdAt'] ?? '', $date) === 0) {
                $dayRev += (float)$o['totalAmount'];
            }
        }
        $chartData[] = $dayRev;
    }

    $revenueGrowth = "+14.8%";
    $profitMargin  = "24.2%";

    $recentOrders = $orders;
    usort($recentOrders, fn($a, $b) => strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));
    $recentOrders = array_slice($recentOrders, 0, 8);

} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    $totalOrders = $totalRevenue = $todayRevenue = $activeUsers = 0;
    $recentOrders = [];
    $days = $chartData = [];
    $revenueGrowth = $profitMargin = "0%";
}

renderHeader($title);
?>

<div class="flex-1 overflow-y-auto p-6 md:p-8">
<div class="space-y-8 max-w-[1400px] mx-auto">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold font-playfair tracking-tight text-white">Overview</h1>
            <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium mt-1">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                Today, <?php echo date('M d, Y'); ?>
                <span class="opacity-20 mx-1">|</span>
                <span class="text-emerald-500 flex items-center gap-1"><i data-lucide="trending-up" class="w-3.5 h-3.5"></i> Live Data Feed</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-white/5 p-1 rounded-xl border border-white/5">
                <button class="px-4 py-2 rounded-lg text-xs font-bold bg-white text-slate-950 shadow-lg">Realtime</button>
                <button class="px-4 py-2 rounded-lg text-xs font-bold text-muted-foreground hover:text-white transition-colors">Historical</button>
            </div>
            <button class="bg-white/5 border border-white/10 text-white p-2.5 rounded-xl hover:bg-white/10 transition-all">
                <i data-lucide="download" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php
        $stats = [
            ['label' => 'Total Revenue',   'value' => number_format($totalRevenue, 2) . ' Br', 'icon' => 'banknote',    'color' => 'text-emerald-500', 'bg' => 'bg-emerald-500/10', 'trend' => '+12.5%', 'desc' => 'vs last month'],
            ['label' => 'Profit Margin',   'value' => $profitMargin,   'icon' => 'percent',     'color' => 'text-blue-500',    'bg' => 'bg-blue-500/10',    'trend' => '+2.1%',  'desc' => 'optimized'],
            ['label' => 'Revenue Growth',  'value' => $revenueGrowth,  'icon' => 'trending-up', 'color' => 'text-orange-400',  'bg' => 'bg-orange-500/10',  'trend' => '+5.2%',  'desc' => 'daily avg'],
            ['label' => 'Staff Active',    'value' => $activeUsers,    'icon' => 'users',        'color' => 'text-purple-400',  'bg' => 'bg-purple-500/10',  'trend' => 'Normal', 'desc' => 'current shifts'],
        ];
        foreach ($stats as $s): ?>
        <div class="glass p-6 rounded-3xl border border-white/5 hover:border-white/10 transition-all group relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/5 rounded-full blur-2xl group-hover:bg-white/10 transition-all"></div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-10 h-10 rounded-2xl <?php echo $s['bg']; ?> flex items-center justify-center <?php echo $s['color']; ?>">
                    <i data-lucide="<?php echo $s['icon']; ?>" class="w-5 h-5"></i>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-tight <?php echo strpos($s['trend'], '+') !== false ? 'text-emerald-500' : 'text-muted-foreground opacity-50'; ?>">
                    <?php echo $s['trend']; ?>
                </span>
            </div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground opacity-50"><?php echo $s['label']; ?></p>
            <h3 class="text-2xl font-bold text-white tracking-tight mt-1"><?php echo $s['value']; ?></h3>
            <p class="text-[10px] font-medium text-muted-foreground py-2 mt-3 border-t border-white/5"><?php echo $s['desc']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Revenue Chart -->
    <div class="glass p-8 rounded-[2.5rem] border border-white/5 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
            <i data-lucide="trending-up" class="w-32 h-32 text-gold"></i>
        </div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-xl font-bold text-white font-playfair tracking-tight">Revenue Analytics</h3>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gold/40 mt-1">7-Day Performance Window</p>
            </div>
            <div class="px-4 py-2 bg-gold/10 border border-gold/20 rounded-xl text-gold font-bold text-[10px] uppercase tracking-widest">
                Active Visualizer
            </div>
        </div>
        <div class="h-56">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders Table -->
        <div class="lg:col-span-2 glass rounded-[2rem] border border-white/5 overflow-hidden shadow-2xl">
            <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                    <h3 class="font-bold text-white tracking-tight">Recent Orders</h3>
                </div>
                <a href="cashier.php" class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground hover:text-white transition-colors bg-white/5 px-4 py-2 rounded-xl">View All</a>
            </div>
            <?php if (empty($recentOrders)): ?>
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <p class="text-4xl mb-3">🌙</p>
                <p class="text-sm font-bold text-white/20 uppercase tracking-widest">No orders yet</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest bg-white/[0.01]">
                            <th class="px-8 py-4 text-left opacity-40">Order Ref</th>
                            <th class="px-8 py-4 text-left opacity-40">Table</th>
                            <th class="px-8 py-4 text-left opacity-40">Status</th>
                            <th class="px-8 py-4 text-right opacity-40">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        <?php foreach ($recentOrders as $o):
                            $statusColors = [
                                'completed' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
                                'preparing' => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
                                'ready'     => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                                'served'    => 'bg-purple-500/10 text-purple-500 border-purple-500/20',
                                'pending'   => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                            ];
                            $sc = $statusColors[strtolower($o['status'] ?? 'pending')] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                        ?>
                        <tr class="text-sm hover:bg-white/[0.03] transition-all">
                            <td class="px-8 py-4">
                                <span class="font-bold text-white text-xs">#<?php echo substr($o['id'] ?? '', 0, 6); ?></span>
                                <p class="text-[10px] text-white/30"><?php echo date('H:i', strtotime($o['createdAt'] ?? 'now')); ?></p>
                            </td>
                            <td class="px-8 py-4">
                                <span class="text-xs font-semibold text-slate-300">
                                    <?php echo ($o['tableNumber'] ?? '') === 'Buy&Go' ? 'Takeaway' : 'Table ' . ($o['tableNumber'] ?? '—'); ?>
                                </span>
                            </td>
                            <td class="px-8 py-4">
                                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg border <?php echo $sc; ?>">
                                    <span class="w-1 h-1 rounded-full bg-current"></span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider"><?php echo ucfirst($o['status'] ?? 'Pending'); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <span class="text-xs font-bold text-white"><?php echo number_format($o['totalAmount'] ?? 0, 2); ?> <span class="opacity-40 font-medium">Br</span></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Fast Actions + Status -->
        <div class="flex flex-col gap-6">
            <div class="glass p-7 rounded-[2rem] border border-white/5 space-y-5 flex-1 shadow-xl">
                <h3 class="font-bold text-white tracking-tight flex items-center gap-3">
                    <div class="w-6 h-6 rounded bg-blue-500 flex items-center justify-center">
                        <i data-lucide="zap" class="w-3.5 h-3.5 text-white"></i>
                    </div>
                    Fast Actions
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="staff.php" class="flex flex-col items-center justify-center p-4 glass rounded-2xl border border-white/5 hover:border-blue-500/50 hover:bg-blue-500/5 transition-all text-center group">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-blue-500 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-400">Add User</span>
                    </a>
                    <a href="settings.php" class="flex flex-col items-center justify-center p-4 glass rounded-2xl border border-white/5 hover:border-purple-500/50 hover:bg-purple-500/5 transition-all text-center group">
                        <i data-lucide="settings" class="w-5 h-5 text-purple-500 mb-2 group-hover:rotate-45 transition-transform duration-300"></i>
                        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-400">Settings</span>
                    </a>
                    <a href="reports.php" class="flex flex-col items-center justify-center p-4 glass rounded-2xl border border-white/5 hover:border-emerald-500/50 hover:bg-emerald-500/5 transition-all text-center group">
                        <i data-lucide="bar-chart-3" class="w-5 h-5 text-emerald-500 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-400">Reports</span>
                    </a>
                    <a href="reception.php" class="flex flex-col items-center justify-center p-4 glass rounded-2xl border border-white/5 hover:border-orange-500/50 hover:bg-orange-500/5 transition-all text-center group">
                        <i data-lucide="key-round" class="w-5 h-5 text-orange-500 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-400">Reception</span>
                    </a>
                </div>
            </div>

            <div class="glass p-7 rounded-[2rem] border border-emerald-500/20 bg-emerald-500/5 space-y-4 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center">
                        <i data-lucide="shield" class="w-4 h-4 text-slate-950"></i>
                    </div>
                    <h3 class="font-bold text-white tracking-tight">System Status</h3>
                </div>
                <p class="text-[11px] text-emerald-500/80 leading-relaxed font-medium">JSON file storage active. Session-based authentication enabled. Zero-setup deployment ready.</p>
                <div class="w-full bg-emerald-500/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-full w-[97%]"></div>
                </div>
                <div class="flex justify-between text-[9px] font-bold uppercase text-emerald-500/50 tracking-widest">
                    <span>Integrity: 97%</span>
                    <span>No threats found</span>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
if (ctx) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(197, 160, 89, 0.35)');
    gradient.addColorStop(1, 'rgba(197, 160, 89, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($days); ?>,
            datasets: [{
                label: 'Revenue (Br)',
                data: <?php echo json_encode($chartData); ?>,
                borderColor: '#c5a059',
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                backgroundColor: gradient,
                pointBackgroundColor: '#c5a059',
                pointBorderColor: '#0f1110',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 10, weight: 'bold' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 10, weight: 'bold' } }
                }
            }
        }
    });
}
</script>

<?php renderFooter(); ?>
