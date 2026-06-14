<?php
/**
 * Food Kitchen Display — Chef / Admin view
 */
require_once 'includes/layout.php';

requireAuth(['bar', 'admin'], 'bar:access');

$user = getCurrentUser();
$userName = $user['name'] ?? 'Bartender';

renderHeader('Bar Display');
?>

<button type="button" id="kiosk-exit-btn" onclick="toggleKiosk(false)"
        class="hidden fixed top-4 right-4 z-[300] items-center gap-2 px-5 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs font-black uppercase tracking-widest border border-red-500/30 transition-all shadow-lg">
    <i data-lucide="minimize" class="w-4 h-4"></i> Exit Kiosk
</button>

<div id="kitchen-page" class="min-h-screen w-full bg-[#0f1110] p-6 lg:p-12 flex justify-center">
    <div class="max-w-screen-2xl w-full space-y-8">

        <!-- Header -->
        <div class="kitchen-ui glass p-8 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-8 bg-gray-900/40">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 rounded-xl bg-gray-800 border border-gray-700 flex items-center justify-center text-blue-400">
                    <i data-lucide="glass-water" class="w-8 h-8"></i>
                </div>
                <div>
                    <h1 class="text-3xl lg:text-4xl font-bold text-white leading-tight mt-1">Bar Display</h1>
                    <p class="text-sm font-medium text-gray-400 mt-2">Drink Queue & Fulfillment</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div id="last-updated" class="text-xs font-semibold text-gray-500 hidden lg:block">Last update: synchronizing...</div>
                <button type="button" id="kiosk-btn" onclick="toggleKiosk()"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-gray-300 text-xs font-bold uppercase tracking-wider hover:bg-white/10 hover:text-white transition-all">
                    <i data-lucide="maximize" class="w-4 h-4"></i> Kiosk
                </button>
                <button type="button" id="refresh-btn" onclick="refreshQueue()"
                        class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:bg-white/10 hover:text-white transition-colors active:scale-95">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <!-- Metrics -->
        <div class="kitchen-ui grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Orders In Queue</p>
                    <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="list-ordered" class="w-5 h-5"></i>
                    </div>
                </div>
                <p id="queue-count" class="text-3xl font-bold text-white leading-none tracking-tight">0</p>
            </div>
            <div class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Bartender On Duty</p>
                    <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="glass-water" class="w-5 h-5"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-white leading-none tracking-tight"><?php echo htmlspecialchars(strtoupper($userName)); ?></p>
            </div>
            <div class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50 sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Active Filter</p>
                    <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="filter" class="w-5 h-5"></i>
                    </div>
                </div>
                <p id="active-filter-label" class="text-lg font-bold text-white leading-tight truncate">All Items</p>
            </div>
        </div>

        <!-- Category filters -->
        <div class="kitchen-ui glass p-6 rounded-2xl border border-gray-700/50 bg-gray-800/60">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-4">Bar Categories</p>
            <div id="category-filters" class="flex flex-wrap gap-2 max-h-36 overflow-y-auto custom-scrollbar pr-1">
                <button type="button" data-cat="" class="cat-pill active">All Items</button>
            </div>
        </div>

        <!-- Kitchen queue -->
        <div id="kitchen-queue-panel" class="glass p-8 rounded-2xl border border-blue-900/40 bg-blue-950/20">
            <div class="kitchen-ui flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500">
                        <i data-lucide="beer" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-blue-400 mt-1">Bar Queue <span id="queue-count-label" class="text-blue-500"></span></h3>
                        <p class="text-sm font-medium text-blue-400/60 mt-1">Today's pending drink orders</p>
                    </div>
                </div>
            </div>

            <div id="queue-scroll" class="pb-2">
                <div id="queue-row" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div id="queue-loading" class="col-span-full flex items-center justify-center text-gray-500 text-sm animate-pulse py-12">
                        Loading bar queue...
                    </div>
                </div>
            </div>
        </div>
    </div>
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

    function renderCategories(categories) {
        const bar = document.getElementById('category-filters');
        const pills = ['<button type="button" data-cat="" class="cat-pill' + (!activeCategory ? ' active' : '') + '">All Items</button>'];
        categories.forEach(cat => {
            const on = activeCategory === cat;
            pills.push(`<button type="button" data-cat="${esc(cat)}" class="cat-pill${on ? ' active' : ''}">${esc(cat)}</button>`);
        });
        bar.innerHTML = pills.join('');
        document.getElementById('active-filter-label').textContent = activeCategory || 'All Items';
    }

    function renderQueue(queue) {
        const row = document.getElementById('queue-row');
        document.getElementById('queue-count').textContent = queue.length;
        document.getElementById('queue-count-label').textContent = queue.length ? `(${queue.length})` : '';

        if (!queue.length) {
            row.innerHTML = `
                <div class="col-span-full flex flex-col items-center justify-center w-full py-16 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mb-4">
                        <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-500"></i>
                    </div>
                    <p class="text-lg font-bold text-gray-300">Bar is clear</p>
                    <p class="text-sm text-gray-500 mt-1">No drink orders waiting right now</p>
                </div>`;
            lucide.createIcons();
            return;
        }

        row.innerHTML = queue.map(order => `
            <article class="glass flex flex-col rounded-xl border border-blue-900/30 bg-gray-900/60 hover:bg-gray-900 transition-colors overflow-hidden h-full">
                <div class="px-5 pt-5 pb-4 border-b border-gray-700/50">
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest leading-none mb-2">Order</p>
                    <p class="text-3xl font-bold text-white leading-none mb-3">#${esc(order.orderNumber)}</p>
                    <div class="flex flex-wrap gap-2">
                        ${order.menuTierName && order.menuTierName !== 'Standard' ? `<span class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md bg-purple-500/10 text-purple-300 border border-purple-500/20">${esc(order.menuTierName)}</span>` : ''}
                        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md bg-blue-500/10 text-blue-400 border border-blue-500/20">${esc(order.floorLabel)}</span>
                        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md bg-gray-800 text-gray-300 border border-gray-700">${esc(order.tableLabel)}</span>
                        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded-md bg-gray-800 text-gray-500 border border-gray-700 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i>${getTimeAgo(order.createdAt)}
                        </span>
                    </div>
                </div>

                <div class="flex-1 px-5 py-4 space-y-3 max-h-64 overflow-y-auto custom-scrollbar">
                    ${order.items.map(item => `
                        <div class="rounded-lg border border-gray-700/50 bg-gray-800/50 p-3">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-1">#${esc(item.menuId || '—')}</p>
                                    <p class="text-sm font-bold text-white leading-snug">${esc(item.name)}</p>
                                    ${item.category ? `<p class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 mt-1">${esc(item.category)}</p>` : ''}
                                </div>
                                <span class="shrink-0 w-8 h-8 rounded-lg bg-gray-900 border border-gray-700 flex items-center justify-center text-sm font-bold text-gray-200">${item.quantity}</span>
                            </div>
                            <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                <i data-lucide="hourglass" class="w-3 h-3"></i>${esc(item.status || 'pending')}
                            </span>
                            ${item.notes ? `<p class="text-[10px] text-amber-400/80 mt-2 italic">${esc(item.notes)}</p>` : ''}
                        </div>
                    `).join('')}
                </div>

                <div class="p-4 border-t border-gray-700/50 shrink-0">
                    <button type="button" data-fulfill-id="${esc(order.id)}" data-fulfill-num="${esc(order.orderNumber)}"
                            class="remove-btn w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Remove
                    </button>
                </div>
            </article>
        `).join('');

        lucide.createIcons();
    }

    async function refreshQueue() {
        const refreshBtn = document.getElementById('refresh-btn');
        const refreshIcon = refreshBtn?.querySelector('i');
        refreshIcon?.classList.add('animate-spin');

        try {
            const url = 'api/kitchen/queue.php?mainCategory=drinks' + (activeCategory ? '&category=' + encodeURIComponent(activeCategory) : '');
            const resp = await fetch(url);
            const json = await resp.json();
            if (!resp.ok) throw new Error(json.message || 'Failed to load queue');

            const data = json.data || {};
            renderCategories(data.categories || []);
            renderQueue(data.queue || []);
            document.getElementById('last-updated').textContent = 'Last update: ' + new Date().toLocaleTimeString();
        } catch (err) {
            document.getElementById('queue-row').innerHTML =
                `<div class="text-red-400 text-sm py-8">${esc(err.message)}</div>`;
        } finally {
            refreshIcon?.classList.remove('animate-spin');
        }
    }

    async function removeOrder(id, num) {
        try {
            const resp = await fetch('api/orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'fulfill-category',
                    id: id,
                    mainCategory: 'drinks'
                }),
            });
            const json = await resp.json();
            if (!resp.ok) throw new Error(json.message || 'Failed to remove items');
            refreshQueue();
        } catch (err) {
            alert(err.message || 'Could not remove items');
        }
    }

    function toggleKiosk(force) {
        const on = typeof force === 'boolean' ? force : !document.body.classList.contains('kitchen-kiosk');
        document.body.classList.toggle('kitchen-kiosk', on);
        const exitBtn = document.getElementById('kiosk-exit-btn');
        if (exitBtn) exitBtn.classList.toggle('hidden', !on);
        if (on) exitBtn?.classList.add('flex');
        else exitBtn?.classList.remove('flex');
        lucide.createIcons();
    }

    document.getElementById('category-filters').addEventListener('click', e => {
        const pill = e.target.closest('.cat-pill');
        if (!pill) return;
        activeCategory = pill.dataset.cat || '';
        refreshQueue();
    });

    document.getElementById('queue-row').addEventListener('click', e => {
        const btn = e.target.closest('.remove-btn');
        if (!btn) return;
        removeOrder(btn.dataset.fulfillId, btn.dataset.fulfillNum);
    });

    refreshQueue();
    refreshTimer = setInterval(refreshQueue, 10000);
</script>

<style>
    .cat-pill {
        padding: 0.5rem 0.9rem;
        border-radius: 999px;
        border: 1px solid #374151;
        background: #1f2937;
        color: #9ca3af;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .cat-pill:hover,
    .cat-pill.active {
        background: #3b82f6;
        color: #fff;
        border-color: #3b82f6;
    }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 999px; }

    body.kitchen-kiosk { overflow: hidden; }
    body.kitchen-kiosk nav { display: none !important; }
    body.kitchen-kiosk #kitchen-page {
        min-height: 100dvh;
        padding: 1rem;
        padding-top: 4rem;
    }
    body.kitchen-kiosk #kitchen-page > div {
        max-width: none;
    }
    body.kitchen-kiosk .kitchen-ui { display: none !important; }
    body.kitchen-kiosk #kitchen-queue-panel {
        padding: 0;
        border: none;
        background: transparent;
        border-radius: 0;
        box-shadow: none;
    }
    body.kitchen-kiosk #queue-scroll {
        height: calc(100dvh - 5rem);
        padding-bottom: 0.5rem;
        overflow-y: auto;
    }
    body.kitchen-kiosk #queue-row {
        align-items: stretch;
    }
    body.kitchen-kiosk .kitchen-card {
        width: 100%;
    }
</style>

<?php renderFooter(); ?>
