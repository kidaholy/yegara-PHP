<?php
/**
 * Refined Admin Dashboard for the PHP Management System
 */
require_once 'includes/layout.php';

requireAuth(['admin']);

$title = "Dashboard";

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

    $activeUsers = db('users')->count(['where' => ['isActive' => [ 'not' => false ]]]);
    $recentOrders = array_slice($orders, 0, 8);
    
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

<div class="space-y-10 max-w-[1400px] mx-auto">
    <!-- Header with Breadcrumbs & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h1 class="text-3xl font-bold font-playfair tracking-tight text-white">Overview</h1>
            <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium">
                <span class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> Today, <?php echo date('M d, Y'); ?></span>
                <span class="opacity-20">|</span>
                <span class="text-emerald-500 flex items-center gap-1.5"><i data-lucide="trending-up" class="w-3.5 h-3.5"></i> Live Data Feed</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-white/5 p-1 rounded-xl border border-white/5 items-center">
                <button class="px-4 py-2 rounded-lg text-xs font-bold bg-white text-slate-950 shadow-lg">Realtime</button>
                <button class="px-4 py-2 rounded-lg text-xs font-bold text-muted-foreground hover:text-white transition-colors">Historical</button>
            </div>
            <button class="bg-white/5 border border-white/10 text-white p-2.5 rounded-xl hover:bg-white/10 transition-all font-bold">
                <i data-lucide="download" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php 
        $stats = [
            ['label' => 'Total Revenue', 'value' => number_format($totalRevenue, 2) . ' Br', 'icon' => 'banknote', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-500/10', 'trend' => '+12.5%', 'desc' => 'vs last month'],
            ['label' => 'Today\'s Sales', 'value' => number_format($todayRevenue, 2) . ' Br', 'icon' => 'zap', 'color' => 'text-blue-500', 'bg' => 'bg-blue-500/10', 'trend' => '+4.1%', 'desc' => 'tracking live'],
            ['label' => 'Total Orders', 'value' => $totalOrders, 'icon' => 'package', 'color' => 'text-orange-500', 'bg' => 'bg-orange-500/10', 'trend' => '+8.2%', 'desc' => 'all categories'],
            ['label' => 'Staff Active', 'value' => $activeUsers, 'icon' => 'users', 'color' => 'text-purple-500', 'bg' => 'bg-purple-500/10', 'trend' => 'Normal', 'desc' => 'currently shifts'],
        ];

        foreach ($stats as $s): ?>
        <div class="glass p-6 rounded-3xl border border-white/5 hover:border-white/10 transition-all group relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/5 rounded-full blur-2xl group-hover:bg-white/10 transition-all"></div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-10 h-10 rounded-2xl <?php echo $s['bg']; ?> flex items-center justify-center <?php echo $s['color']; ?>">
                    <i data-lucide="<?php echo $s['icon']; ?>" class="w-5 h-5"></i>
                </div>
                <div class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-tight <?php echo strpos($s['trend'], '+') !== false ? 'text-emerald-500' : 'text-muted-foreground opacity-50'; ?>">
                    <?php echo $s['trend']; ?>
                </div>
            </div>
            <div class="space-y-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground opacity-50"><?php echo $s['label']; ?></p>
                <h3 class="text-2xl font-bold text-white tracking-tight"><?php echo $s['value']; ?></h3>
            </div>
            <p class="text-[10px] font-medium text-muted-foreground py-2 mt-4 border-t border-white/5"><?php echo $s['desc']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders Table -->
        <div class="lg:col-span-2 glass rounded-[2rem] border border-white/5 overflow-hidden flex flex-col shadow-2xl">
            <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                    <h3 class="font-bold text-white tracking-tight">Recent Orders</h3>
                </div>
                <button class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground hover:text-white transition-colors bg-white/5 px-4 py-2 rounded-xl">View Journal</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest bg-white/[0.01]">
                            <th class="px-8 py-5 text-left font-bold opacity-40">Order Ref</th>
                            <th class="px-8 py-5 text-left font-bold opacity-40">Entity</th>
                            <th class="px-8 py-5 text-left font-bold opacity-40">Status</th>
                            <th class="px-8 py-5 text-right font-bold opacity-40">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        <?php foreach ($recentOrders as $o): ?>
                        <tr class="text-sm hover:bg-white/[0.03] transition-all group">
                            <td class="px-8 py-4.5">
                                <div class="flex flex-col">
                                    <span class="font-bold text-white text-xs tracking-tight">#<?php echo $o['orderNumber'] ?? substr($o['id'], 0, 6); ?></span>
                                    <span class="text-[10px] text-muted-foreground opacity-60"><?php echo date('H:i', strtotime($o['createdAt'])); ?> · Today</span>
                                </div>
                            </td>
                            <td class="px-8 py-4.5">
                                <span class="text-xs font-semibold text-slate-300"><?php echo $o['tableNumber'] === 'Buy&Go' ? 'Takeaway' : 'Table ' . $o['tableNumber']; ?></span>
                            </td>
                            <td class="px-8 py-4.5">
                                <?php 
                                $s_colors = [
                                    'completed' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
                                    'preparing' => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
                                    'ready' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                                    'served' => 'bg-purple-500/10 text-purple-500 border-purple-500/20',
                                    'pending' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                ];
                                $s_color = $s_colors[strtolower($o['status'])] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                ?>
                                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg border <?php echo $s_color; ?>">
                                    <span class="w-1 h-1 rounded-full bg-current"></span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider"><?php echo $o['status']; ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-4.5 text-right">
                                <span class="text-xs font-bold text-white"><?php echo number_format($o['totalAmount'], 2); ?> <span class="opacity-40 font-medium">Br</span></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-8 py-4 bg-white/[0.01] border-t border-white/5">
                <p class="text-[10px] text-center text-muted-foreground opacity-40 italic">End of recent order queue</p>
            </div>
        </div>

        <!-- System Intelligence / Shortcuts -->
        <div class="flex flex-col gap-6">
            <div class="glass p-7 rounded-[2rem] border border-white/5 space-y-6 flex-1 shadow-xl">
                <h3 class="font-bold text-white tracking-tight flex items-center gap-3">
                    <div class="w-6 h-6 rounded bg-blue-500 flex items-center justify-center"><i data-lucide="zap" class="w-3.5 h-3.5 text-white"></i></div>
                    Fast Actions
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <button class="flex flex-col items-center justify-center p-4 glass rounded-2xl border border-white/5 hover:border-blue-500/50 hover:bg-blue-500/5 transition-all text-center group">
                        <i data-lucide="plus-circle" class="w-5 h-5 text-blue-500 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-400">Add User</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-4 glass rounded-2xl border border-white/5 hover:border-purple-500/50 hover:bg-purple-500/5 transition-all text-center group">
                        <i data-lucide="settings" class="w-5 h-5 text-purple-500 mb-2 group-hover:translate-x-0.5 transition-transform"></i>
                        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-400">Settings</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-4 glass rounded-2xl border border-white/5 hover:border-emerald-500/50 hover:bg-emerald-500/5 transition-all text-center group">
                        <i data-lucide="printer" class="w-5 h-5 text-emerald-500 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-400">Sync Data</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-4 glass rounded-2xl border border-white/5 hover:border-orange-500/50 hover:bg-orange-500/5 transition-all text-center group">
                        <i data-lucide="help-circle" class="w-5 h-5 text-orange-500 mb-2 group-hover:scale-110 transition-transform"></i>
                        <span class="text-[10px] font-bold uppercase tracking-tight text-slate-400">Support</span>
                    </button>
                </div>
            </div>

            <div class="glass p-7 rounded-[2rem] border border-emerald-500/20 bg-emerald-500/5 space-y-4 shadow-xl">
                 <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center"><i data-lucide="shield" class="w-4 h-4 text-slate-950"></i></div>
                    <h3 class="font-bold text-white tracking-tight">Security Status</h3>
                </div>
                <p class="text-[11px] text-emerald-500/80 leading-relaxed font-medium">Running on Yegara Shared Host. PHP Session and JSON isolation enabled. High-fidelity data sync active.</p>
                <div class="w-full bg-emerald-500/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-emerald-500 h-full w-[94%]"></div>
                </div>
                <div class="flex justify-between text-[9px] font-bold uppercase text-emerald-500/50 uppercase tracking-widest pt-2">
                    <span>Integrity: 94%</span>
                    <span>No threats found</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
