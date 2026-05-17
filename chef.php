<?php
/**
 * Kitchen Display System (Chef View)
 */
require_once 'includes/layout.php';

requireAuth(['chef', 'admin']);

$title = "Kitchen Display";
renderHeader($title);
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold font-playfair tracking-tight">Kitchen Monitor</h1>
            <p class="text-muted-foreground">Active orders requiring preparation.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-xs text-muted-foreground flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-ping"></span>
                Auto-refreshing in <span id="timer">10</span>s
            </div>
            <button onclick="refreshOrders()" class="p-2 glass rounded-lg hover:bg-white/10 transition-colors">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Orders Grid -->
    <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Orders will be injected here via JS -->
        <div class="col-span-full py-20 text-center text-muted-foreground">
            <div class="animate-spin mb-4 inline-block"><i data-lucide="loader-2" class="w-8 h-8"></i></div>
            <p>Loading active orders...</p>
        </div>
    </div>
</div>

<script>
    let timeLeft = 10;
    const timerEl = document.getElementById('timer');

    async function refreshOrders() {
        try {
            const resp = await fetch('api/orders.php');
            const orders = await resp.json();
            renderOrders(orders);
            timeLeft = 10;
        } catch (err) {
            console.error('Failed to fetch orders:', err);
        }
    }

    function renderOrders(orders) {
        const grid = document.getElementById('orders-grid');
        if (orders.length === 0) {
            grid.innerHTML = `
                <div class="col-span-full py-20 text-center border-2 border-dashed border-border rounded-2xl">
                    <p class="text-muted-foreground">No active orders. Kitchen is clear!</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = orders.map(order => `
            <div class="glass rounded-2xl border border-border flex flex-col overflow-hidden animate-in fade-in zoom-in duration-300">
                <div class="p-5 border-b border-border bg-white/5 flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold bg-blue-500 text-white px-2 py-0.5 rounded">#${order.orderNumber}</span>
                            <span class="text-xs text-muted-foreground">${new Date(order.createdAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                        </div>
                        <h3 class="font-bold text-lg">${order.tableNumber === 'Buy&Go' ? 'Takeaway' : 'Table ' + order.tableNumber}</h3>
                    </div>
                    <div class="text-right">
                        <span class="block px-2 py-1 rounded text-[10px] font-bold uppercase ${getStatusColor(order.status)}">
                            ${order.status}
                        </span>
                    </div>
                </div>
                
                <div class="flex-1 p-5 space-y-3">
                    ${order.items.map(item => `
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex-1">
                                <p class="font-medium text-sm leading-tight">${item.quantity}x ${item.name}</p>
                                ${item.notes ? `<p class="text-[11px] text-orange-400 mt-1 italic">Note: ${item.notes}</p>` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div class="p-3 bg-white/5 border-t border-border grid grid-cols-2 gap-2">
                    ${order.status === 'preparing' ? `
                        <button onclick="updateStatus('${order.id}', 'ready')" class="col-span-2 bg-emerald-500 hover:bg-emerald-600 text-white py-2 rounded-lg text-sm font-bold transition-colors">
                            Mark Ready
                        </button>
                    ` : `
                        <button onclick="updateStatus('${order.id}', 'preparing')" class="col-span-2 bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg text-sm font-bold transition-colors">
                            Start Preparing
                        </button>
                    `}
                </div>
            </div>
        `).join('');
        lucide.createIcons();
    }

    function getStatusColor(status) {
        switch(status) {
            case 'pending': return 'bg-orange-500/10 text-orange-500 border border-orange-500/20';
            case 'preparing': return 'bg-blue-500/10 text-blue-500 border border-blue-500/20';
            case 'ready': return 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20';
            default: return 'bg-slate-500/10 text-slate-500 border border-slate-500/20';
        }
    }

    async function updateStatus(id, status) {
        try {
            await fetch('api/orders.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id, status})
            });
            refreshOrders();
        } catch (err) {
            alert('Failed to update status');
        }
    }

    // Polling logic
    setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            refreshOrders();
        }
        timerEl.innerText = timeLeft;
    }, 1000);

    // Initial load
    refreshOrders();
</script>

<?php renderFooter(); ?>
