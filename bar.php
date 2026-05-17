<?php
/**
 * Refined Bar Display System (Drink Monitor)
 */
require_once 'includes/layout.php';

requireAuth(['bar', 'admin']);

$title = "Bar Monitor";
renderHeader($title);
?>

<div class="space-y-10 max-w-[1600px] mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h1 class="text-3xl font-bold font-playfair tracking-tight text-white flex items-center gap-3">
                <i data-lucide="beer" class="w-8 h-8 text-amber-500"></i>
                Active Bar Orders
            </h1>
            <div class="flex items-center gap-2 text-xs text-muted-foreground font-medium">
                <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5"></i> Refreshing in <span id="timer" class="text-white font-bold inline-block w-4">10</span>s</span>
                <span class="opacity-20">|</span>
                <span class="text-amber-500 flex items-center gap-1.5 font-bold"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Service Active</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-white/5 p-1 rounded-xl border border-white/5 items-center">
                <button class="px-5 py-2 rounded-lg text-xs font-bold bg-white text-slate-950 shadow-lg">Newest</button>
                <button class="px-5 py-2 rounded-lg text-xs font-bold text-muted-foreground hover:text-white transition-colors">By Priority</button>
            </div>
            <button onclick="refreshOrders()" class="bg-white/5 border border-white/10 text-white p-2.5 rounded-xl hover:bg-white/10 transition-all font-bold group">
                <i data-lucide="rotate-cw" class="w-4 h-4 group-active:rotate-180 transition-transform"></i>
            </button>
        </div>
    </div>

    <!-- Orders Grid -->
    <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <!-- Initial Loader -->
        <div class="col-span-full py-32 text-center text-muted-foreground">
            <div class="flex flex-col items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center animate-spin">
                    <i data-lucide="loader-2" class="w-6 h-6 text-amber-500"></i>
                </div>
                <p class="text-sm font-medium tracking-wide animate-pulse">Syncing with bar database...</p>
            </div>
        </div>
    </div>
</div>

<script>
    let timeLeft = 10;
    const timerEl = document.getElementById('timer');

    async function refreshOrders() {
        try {
            const resp = await fetch('api/orders.php?mainCategory=Drinks');
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
                <div class="col-span-full py-32 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-[2.5rem] bg-white/[0.01]">
                    <div class="w-16 h-16 rounded-3xl bg-emerald-500/10 flex items-center justify-center mb-6 border border-emerald-500/20">
                        <i data-lucide="glass-water" class="w-8 h-8 text-emerald-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Bar is Clear</h3>
                    <p class="text-muted-foreground text-sm max-w-xs text-center opacity-60">No pending drink orders. Everything is served!</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = orders.map(order => {
            const statusColor = getStatusColors(order.status);
            
            return `
                <div class="glass rounded-[2rem] border border-white/5 flex flex-col overflow-hidden animate-in fade-in zoom-in duration-500 shadow-2xl hover:border-white/10 transition-all group">
                    <div class="p-6 border-b border-white/5 bg-white/[0.02] flex justify-between items-start">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold bg-white text-slate-950 px-2 py-0.5 rounded-md shadow-lg">#${order.orderNumber}</span>
                            </div>
                            <h3 class="font-bold text-lg text-white tracking-tight">${order.tableNumber === 'Buy&Go' ? 'Takeaway' : 'Table ' + order.tableNumber}</h3>
                            <p class="text-[10px] text-muted-foreground font-medium opacity-50">${new Date(order.createdAt).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div class="px-2.5 py-1 rounded-lg border text-[9px] font-black uppercase tracking-widest ${statusColor.pill}">
                                ${order.status}
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex-1 p-6 space-y-4">
                        ${order.items.map(item => `
                            <div class="flex justify-between items-start gap-4 p-3 rounded-2xl bg-white/[0.03] border border-white/5 group-hover:border-white/10 transition-all">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-amber-500/10 flex items-center justify-center text-[10px] font-bold text-amber-400 border border-amber-500/10">${item.quantity}</span>
                                        <p class="font-bold text-slate-200 text-sm truncate">${item.name}</p>
                                    </div>
                                    ${item.notes ? `<p class="mt-2 ml-8 text-[10px] text-orange-400 font-medium italic opacity-80">${item.notes}</p>` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>

                    <div class="p-4 bg-white/[0.02] border-t border-white/5 grid grid-cols-1 gap-2">
                        ${order.status === 'preparing' ? `
                            <button onclick="updateStatus('${order.id}', 'ready')" class="w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all shadow-[0_4px_15px_rgba(16,185,129,0.2)]">
                                Complete Drinks
                            </button>
                        ` : `
                            <button onclick="updateStatus('${order.id}', 'preparing')" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 py-3 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all shadow-[0_4px_15px_rgba(245,158,11,0.2)]">
                                Start Pouring
                            </button>
                        `}
                    </div>
                </div>
            `;
        }).join('');
        lucide.createIcons();
    }

    function getStatusColors(status) {
        switch(status.toLowerCase()) {
            case 'pending': return { pill: 'bg-slate-500/10 text-slate-400 border-slate-500/20' };
            case 'preparing': return { pill: 'bg-amber-500/10 text-amber-500 border-amber-500/20' };
            case 'ready': return { pill: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' };
            default: return { pill: 'bg-slate-500/10 text-slate-400 border-slate-500/20' };
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
            alert('Update failed.');
        }
    }

    setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) refreshOrders();
        timerEl.innerText = timeLeft;
    }, 1000);

    refreshOrders();
</script>

<?php renderFooter(); ?>
