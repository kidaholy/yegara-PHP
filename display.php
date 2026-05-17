<?php
/**
 * Public Order Display Module (Kiosk/TV Mode)
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';

// No auth required for public display, but maybe a secure token in real use.
// For now, it's a public read-only view.

try {
    $orders = db('orders')->findMany([
        'where' => [
            'status' => ['in' => ['preparing', 'ready']],
            'isDeleted' => false,
            'createdAt' => ['gte' => date('Y-m-d 00:00:00')]
        ],
        'orderBy' => ['createdAt' => 'desc']
    ]);

    $preparing = array_filter($orders, fn($o) => $o['status'] === 'preparing');
    $ready = array_filter($orders, fn($o) => $o['status'] === 'ready');

} catch (Exception $e) {
    $preparing = []; $ready = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status - Prime Addis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/geist-mono@1.2.0/dist/index.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f1110;
            color: #fff;
            overflow: hidden;
        }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .font-mono { font-family: 'Geist Mono', monospace; }
        
        .grid-cols-display {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100vh;
        }

        .ready-panel {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%);
            border-left: 1px solid rgba(16, 185, 129, 0.1);
        }

        .preparing-panel {
            background: linear-gradient(135deg, rgba(197, 160, 89, 0.05) 0%, transparent 100%);
        }

        .order-card {
            background: rgba(21, 24, 23, 0.8);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.03);
            animation: bounceIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .ready-card {
            border-color: rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.1);
        }

        @keyframes bounceIn {
            from { opacity: 0; transform: scale(0.8) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .gold-mesh {
            background-image: radial-gradient(#c5a059 0.5px, transparent 0.5px);
            background-size: 32px 32px;
            opacity: 0.03;
        }
    </style>
    <!-- Auto Refresh -->
    <meta http-equiv="refresh" content="10">
</head>
<body>
    <div class="fixed inset-0 gold-mesh"></div>

    <div class="grid-cols-display">
        <!-- Preparing Section -->
        <div class="preparing-panel p-16 flex flex-col h-screen">
            <div class="flex items-center gap-6 mb-16">
                <div class="w-20 h-20 rounded-3xl bg-gold/10 flex items-center justify-center text-gold">
                    <i data-lucide="chef-hat" class="w-10 h-10"></i>
                </div>
                <div>
                    <h2 class="text-5xl font-black font-playfair text-gold/60 uppercase tracking-tighter">Preparing</h2>
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-gold/30">Culinary Team at Work</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 overflow-y-auto pr-4 custom-scrollbar">
                <?php foreach ($preparing as $order): ?>
                <div class="order-card p-10 rounded-[3rem] text-center">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gold/40 mb-3 block">Guest Order</span>
                    <h3 class="text-6xl font-black font-mono text-white tracking-tighter">#<?php echo substr($order['orderNumber'], -4); ?></h3>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Ready Section -->
        <div class="ready-panel p-16 flex flex-col h-screen overflow-hidden">
            <div class="flex items-center gap-6 mb-16">
                <div class="w-20 h-20 rounded-3xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                    <i data-lucide="bell" class="w-10 h-10 animate-bounce"></i>
                </div>
                <div>
                    <h2 class="text-5xl font-black font-playfair text-emerald-500 uppercase tracking-tighter">Please Collect</h2>
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-emerald-500/40">Ready for Service</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8">
                <?php foreach ($ready as $order): ?>
                <div class="order-card ready-card p-12 rounded-[4rem] flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-500/50 mb-3 block">Pick-up Station</span>
                        <h3 class="text-8xl font-black font-mono text-white tracking-tighter">#<?php echo substr($order['orderNumber'], -4); ?></h3>
                    </div>
                    <div class="flex flex-col items-center gap-4 text-emerald-500">
                        <i data-lucide="check-circle-2" class="w-20 h-20"></i>
                        <span class="text-xs font-bold uppercase tracking-widest">Confirmed</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Clock Overlay -->
    <div class="fixed bottom-10 right-10 glass px-8 py-4 rounded-3xl border border-white/5 flex items-center gap-4">
        <i data-lucide="clock" class="w-5 h-5 text-gold/50"></i>
        <div class="text-2xl font-black font-mono text-white">
            <span id="display-time">00:00</span>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function updateTime() {
            const now = new Date();
            document.getElementById('display-time').innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        updateTime();
        setInterval(updateTime, 60000);
    </script>
</body>
</html>
