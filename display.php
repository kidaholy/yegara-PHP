<?php
/**
 * Public Order Display Module (Kiosk/TV Mode) — Updated for Full Display
 */
require_once 'includes/layout.php';

// Auth required: display or admin
requireAuth(['display', 'admin'], 'display:access');

$user = getCurrentUser();
$userName = $user['name'] ?? 'Display';

renderHeader('Kitchen Display');
?>

<div id="kitchen-page" class="min-h-screen w-full bg-[#0f1110] p-4 flex justify-center">
    <div class="w-full">

        <!-- Kitchen queue - Full Screen Mode -->
        <div id="kitchen-queue-panel" class="glass p-6 rounded-2xl border border-[#c5a059]/20 bg-[#c5a059]/5 h-full">
            <div id="queue-scroll" class="h-full">
                <div id="queue-row" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                    <div id="queue-loading" class="col-span-full flex items-center justify-center text-gray-500 text-sm animate-pulse py-12">
                        Loading kitchen queue...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Subtle Live Indicator Overlay -->
<div class="fixed bottom-6 right-6 flex items-center gap-2 bg-black/40 backdrop-blur-md px-4 py-2 rounded-full border border-white/5 z-50">
    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Live Monitor</span>
    <span id="last-updated" class="text-[10px] font-bold text-gray-600 ml-2">Syncing...</span>
</div>

<script>
    let activeCategory = '';
    let refreshTimer = null;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function getTimeAgo(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 60000);
        if (diff < 1) return 'Just now';
        if (diff < 60) return diff + 'm ago';
        const hrs = Math.floor(diff / 60);
        if (hrs < 24) return hrs + 'h ago';
        return Math.floor(hrs / 24) + 'd ago';
    }

    function renderQueue(queue) {
        const row = document.getElementById('queue-row');
        
        if (!queue.length) {
            row.innerHTML = `
                <div class="col-span-full flex flex-col items-center justify-center w-full py-32 text-center">
                    <div class="w-20 h-20 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mb-6">
                        <i data-lucide="check-circle-2" class="w-10 h-10 text-emerald-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-300">Queue is clear</p>
                    <p class="text-sm text-gray-500 mt-2 uppercase tracking-widest">No active food orders</p>
                </div>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }

        row.innerHTML = queue.map(order => `
            <article class="glass flex flex-col rounded-2xl border border-[#c5a059]/10 bg-gray-900/60 transition-colors overflow-hidden h-full shadow-2xl">
                <div class="px-5 pt-5 pb-4 border-b border-gray-700/50">
                    <div class="flex justify-between items-start mb-2">
                        <p class="text-[10px] font-black text-[#c5a059] uppercase tracking-widest leading-none">Order</p>
                        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md bg-gray-800 text-gray-500 border border-gray-700 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i>${getTimeAgo(order.createdAt)}
                        </span>
                    </div>
                    <p class="text-4xl font-black text-white leading-none mb-4 tracking-tighter">#${esc(order.orderNumber)}</p>
                    <div class="flex flex-wrap gap-2">
                        ${order.menuTierName && order.menuTierName !== 'Standard' ? `<span class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md bg-purple-500/10 text-purple-300 border border-purple-500/20">${esc(order.menuTierName)}</span>` : ''}
                        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md bg-[#c5a059]/10 text-[#c5a059] border border-[#c5a059]/20 font-mono">${esc(order.floorLabel)}</span>
                        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md bg-gray-800 text-gray-300 border border-gray-700 font-mono">${esc(order.tableLabel)}</span>
                    </div>
                </div>

                <div class="flex-1 px-5 py-4 space-y-3">
                    ${order.items.map(item => `
                        <div class="rounded-xl border border-gray-700/50 bg-gray-800/40 p-4">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="min-w-0">
                                    <p class="text-base font-bold text-white leading-snug">${esc(item.name)}</p>
                                    ${item.category ? `<p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 mt-1">${esc(item.category)}</p>` : ''}
                                </div>
                                <span class="shrink-0 w-10 h-10 rounded-xl bg-gray-950 border border-gray-700 flex items-center justify-center text-lg font-black text-[#c5a059] shadow-inner">${item.quantity}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                    <i data-lucide="hourglass" class="w-3 h-3"></i>${esc(item.status || 'pending')}
                                </span>
                            </div>
                            ${item.notes ? `<p class="text-[11px] text-amber-400/80 mt-3 italic bg-amber-500/5 p-2 rounded-lg border border-amber-500/10">${esc(item.notes)}</p>` : ''}
                        </div>
                    `).join('')}
                </div>
            </article>
        `).join('');

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async function refreshQueue() {
        try {
            const url = 'api/kitchen/queue.php?mainCategory=food';
            const resp = await fetch(url);
            const json = await resp.json();
            if (!resp.ok) throw new Error(json.message || 'Failed to load queue');

            const data = json.data || {};
            renderQueue(data.queue || []);
            document.getElementById('last-updated').textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (err) {
            document.getElementById('queue-row').innerHTML =
                `<div class="text-red-400 text-sm py-8 font-bold uppercase tracking-widest bg-red-500/5 p-6 rounded-2xl border border-red-500/10">${esc(err.message)}</div>`;
        }
    }

    refreshQueue();
    refreshTimer = setInterval(refreshQueue, 10000);
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(197, 160, 89, 0.2); border-radius: 999px; }
    
    .glass {
        background: rgba(255, 255, 255, 0.02);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    body {
        background-color: #0c0d0c;
        cursor: none; /* Hide cursor for TV/Display use cases */
    }

    #kitchen-page {
        padding-top: 1rem;
    }
</style>

<?php renderFooter(); ?>
