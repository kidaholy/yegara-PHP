<?php
/**
 * Cashier POS — Standard / VIP layout (menu grid + order cart sidebar)
 */
require_once 'includes/layout.php';

requireAuth(['cashier', 'admin']);

$tierId = trim($_GET['tier'] ?? '');
$activeTier = $tierId !== '' ? getMenuTierById($tierId) : null;
if ($tierId !== '' && !$activeTier) {
    header('Location: cashier.php');
    exit;
}

$collection = $activeTier ? getMenuTierCollection($activeTier) : 'menuItems';
$posTitle = $activeTier ? (($activeTier['name'] ?? 'VIP') . ' POS') : 'Standard POS';
$posTab = $activeTier ? $activeTier['id'] : 'standard';
$menuTierName = $activeTier ? ($activeTier['name'] ?? 'VIP') : 'Standard';

$user = getCurrentUser();
$userName = $user['name'] ?? 'Cashier';
$welcomeDate = date('D, M j');

renderHeader($posTitle, ['nav' => 'pos', 'posTab' => $posTab]);
?>

<div class="min-h-screen w-full bg-[#0f1110] p-6 lg:p-8 flex justify-center">
    <div class="max-w-screen-2xl w-full space-y-8 flex flex-col min-h-[calc(100dvh-60px)]">

        <!-- Header -->
        <div class="glass p-8 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-8 bg-gray-900/40 shrink-0">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 rounded-xl bg-gray-800 border border-gray-700 flex items-center justify-center text-blue-400">
                    <i data-lucide="shopping-cart" class="w-8 h-8"></i>
                </div>
                <div>
                    <h1 class="text-3xl lg:text-4xl font-bold text-white leading-tight mt-1"><?php echo htmlspecialchars($posTitle); ?></h1>
                    <p class="text-sm font-medium text-gray-400 mt-2">
                        Welcome, <?php echo htmlspecialchars(strtoupper($userName)); ?> &bull; <?php echo $welcomeDate; ?>
                        <?php if ($activeTier): ?>
                        &bull; <span class="text-purple-300"><?php echo htmlspecialchars($activeTier['name']); ?> (+<?php echo (float)$activeTier['percentage']; ?>%)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="orders.php?view=recent" class="text-xs font-bold uppercase tracking-widest text-blue-400 hover:text-blue-300 transition-colors flex items-center gap-2">
                    Recent Orders <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
                <button type="button" onclick="loadData()" class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 hover:bg-white/10 hover:text-white transition-colors active:scale-95">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 shrink-0">
            <div class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Available Items</p>
                    <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="layout-grid" class="w-5 h-5"></i>
                    </div>
                </div>
                <p id="available-count" class="text-3xl font-bold text-white leading-none tracking-tight">—</p>
            </div>
            <div class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Cart Items</p>
                    <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                    </div>
                </div>
                <p id="cart-badge" class="text-3xl font-bold text-white leading-none tracking-tight">0 Items</p>
            </div>
            <div class="glass p-6 rounded-2xl bg-gray-800/80 hover:bg-gray-800 transition-colors border border-gray-700/50">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-400">Cart Total</p>
                    <div class="inline-flex p-3 rounded-lg bg-gray-900 border border-gray-700 text-blue-400">
                        <i data-lucide="dollar-sign" class="w-5 h-5"></i>
                    </div>
                </div>
                <p id="cart-total" class="text-3xl font-bold text-white leading-none tracking-tight">0 ETB</p>
            </div>
        </div>

        <!-- POS workspace -->
        <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-12 gap-8 pb-4">
            <!-- Menu panel -->
            <div class="lg:col-span-8 flex flex-col min-h-0 glass p-6 rounded-2xl border border-gray-700/50 bg-gray-800/60">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 shrink-0 mb-4">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-3 w-4 h-4 text-gray-500"></i>
                        <input type="text" id="search-name" placeholder="Search by item name..." class="pos-inp w-full pl-10">
                    </div>
                    <div class="relative">
                        <span class="absolute left-3 top-3 text-gray-500 text-sm font-bold">#</span>
                        <input type="text" id="search-id" placeholder="Item ID" class="pos-inp w-full pl-8">
                    </div>
                </div>

                <div class="flex gap-2 shrink-0 mb-3">
                    <button type="button" id="main-tab-food" data-tab="Food" class="main-cat-tab flex-1 py-3 rounded-xl text-sm font-bold border transition-all">
                        Food <span id="food-count" class="opacity-60"></span>
                    </button>
                    <button type="button" id="main-tab-drinks" data-tab="Drinks" class="main-cat-tab flex-1 py-3 rounded-xl text-sm font-bold border transition-all">
                        Drinks <span id="drinks-count" class="opacity-60"></span>
                    </button>
                </div>

                <div class="flex gap-2 overflow-x-auto shrink-0 mb-4 pb-1 no-scrollbar" id="category-chips">
                    <button type="button" data-cat="" class="cat-chip shrink-0 px-4 py-2 rounded-full text-[11px] font-bold uppercase tracking-wide border transition-all">All Items</button>
                </div>

                <div class="flex-1 min-h-[24rem] lg:min-h-0 overflow-y-auto custom-scrollbar pr-1" id="menu-scroll">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 pb-4" id="items-grid">
                        <div class="col-span-full py-20 text-center text-gray-500 animate-pulse text-sm">Loading menu...</div>
                    </div>
                </div>
            </div>

            <!-- Cart panel -->
            <div class="lg:col-span-4 flex flex-col self-start w-full bg-[#0a0a0a] rounded-[2.5rem] border border-gray-800/80 shadow-2xl p-6 overflow-hidden">
                <!-- Cart Header -->
                <div class="flex items-center gap-4 mb-8 shrink-0">
                    <div class="w-14 h-14 rounded-2xl bg-gray-900 border border-gray-800 flex items-center justify-center text-gray-500">
                        <i data-lucide="shopping-cart" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-amber-500 italic leading-none" style="font-family: 'Playfair Display', serif;">Order Cart</h2>
                        <p id="items-count-badge" class="text-[10px] font-black text-gray-600 uppercase tracking-widest mt-1.5">0 ITEMS</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-2 shrink-0 mb-6">
                    <button type="button" id="cart-tab-food" data-tab="Food" class="cart-cat-tab flex-1 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all flex items-center justify-center gap-2">
                        🥩 BUTCHER
                    </button>
                    <button type="button" id="cart-tab-drinks" data-tab="Drinks" class="cart-cat-tab flex-1 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all flex items-center justify-center gap-2">
                        🥤 DRINKS
                    </button>
                </div>

                <!-- Input Boxes -->
                <div class="space-y-3 mb-6">
                    <!-- Hidden floor select (used by JS, not displayed) -->
                    <select id="floor-select" class="hidden"></select>

                    <button type="button" id="table-picker-btn" 
                            class="w-full h-16 px-4 rounded-2xl bg-gray-900 border border-gray-800 flex items-center justify-between text-left group hover:border-gray-600 transition-all">
                        <div class="flex items-center gap-3">
                            <span class="text-lg">🪑</span>
                            <span id="table-picker-label" class="text-[10px] font-black text-gray-400 uppercase tracking-widest truncate">Select Table</span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600"></i>
                    </button>

                    <!-- Custom Distribution Dropdown -->
                    <div class="relative" id="dist-dropdown-wrap">
                        <button type="button" id="dist-trigger"
                                onclick="toggleDistDropdown()"
                                class="w-full h-16 px-4 rounded-2xl bg-gray-900 border border-gray-800 flex items-center justify-between text-left hover:border-gray-600 transition-all">
                            <div class="flex items-center gap-3">
                                <span class="text-lg">🚚</span>
                                <span id="dist-label" class="text-[10px] font-black text-gray-400 uppercase tracking-widest truncate">All Distributions</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600 transition-transform" id="dist-chevron"></i>
                        </button>
                        <!-- Hidden native select for form submission -->
                        <select id="distribution-select" class="hidden"></select>
                        <!-- Custom dropdown panel -->
                        <div id="dist-panel" class="hidden absolute left-0 right-0 top-[calc(100%+8px)] z-50 bg-[#0d0d0d] border border-gray-800 rounded-2xl overflow-hidden shadow-2xl max-h-64 overflow-y-auto custom-gold-scrollbar">
                            <div id="dist-list"></div>
                        </div>
                    </div>

                    <div class="relative h-16 rounded-2xl bg-gray-900 border border-gray-800 flex items-center px-4">
                        <span class="text-lg mr-3">🏷️</span>
                        <input type="text" id="batch-number" placeholder="BATCH NUMBER" 
                               class="bg-transparent text-[10px] font-black text-gray-400 uppercase tracking-widest w-full outline-none placeholder:text-gray-700">
                    </div>

                    <input type="hidden" id="table-number" value="Buy&Go">
                    <input type="hidden" id="floor-id" value="">
                    <input type="hidden" id="floor-number" value="">
                </div>

                <div class="flex-1 min-h-[300px] overflow-y-auto custom-gold-scrollbar mb-6" id="cart-scroll">
                    <div id="cart-container" class="space-y-3 hidden"></div>
                    <div id="cart-empty" class="flex flex-col items-center justify-center text-center py-12">
                        <div class="w-24 h-24 mb-6 opacity-20 relative">
                            <i data-lucide="shopping-cart" class="w-full h-full text-gray-400"></i>
                            <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0a] to-transparent"></div>
                        </div>
                        <p class="text-xs font-black text-gray-600 uppercase tracking-widest" style="font-family: 'Playfair Display', serif;">Your cart is empty</p>
                    </div>
                </div>

                <!-- Cart Footer -->
                <div class="pt-6 border-t border-gray-800/80">
                    <div class="bg-gray-900/30 border border-gray-800/80 rounded-3xl p-6 mb-4">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">TOTAL</p>
                            <p class="text-3xl font-bold text-amber-500 italic" style="font-family: 'Playfair Display', serif;">
                                <span id="cart-total-val">0</span> <span class="text-sm">ETB</span>
                            </p>
                        </div>
                    </div>
                    <button id="place-order-btn" type="button" disabled
                            class="w-full bg-[#c5a059] hover:bg-[#d4af37] text-black font-black py-5 rounded-[2rem] text-[11px] uppercase tracking-[0.3em] shadow-xl transition-all disabled:opacity-20 disabled:grayscale flex items-center justify-center gap-3">
                        🚀 SEND TO KITCHEN
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Table picker modal -->
<div id="table-modal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
    <div class="w-full max-w-3xl bg-[#0a0a0a] border border-gray-800/80 rounded-3xl shadow-[0_0_50px_rgba(0,0,0,0.8)] overflow-hidden max-h-[90vh] flex flex-col relative">
        <!-- Close Button (Gold Square) -->
        <button type="button" id="table-modal-close" 
                class="absolute top-6 right-6 w-10 h-10 rounded-xl bg-gray-900 border border-gray-800 flex items-center justify-center text-gray-500 hover:text-white hover:border-amber-500/50 transition-all z-10">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="px-8 pt-8 pb-4 shrink-0">
            <h3 class="text-3xl font-bold text-amber-500 tracking-tight italic" style="font-family: 'Playfair Display', serif;">Select Table</h3>
        </div>

        <div class="px-8 pb-4 shrink-0">
            <div id="floor-tabs" class="flex flex-wrap items-center justify-center gap-2"></div>
        </div>

        <div class="px-8 pb-4 shrink-0">
            <button type="button" id="pick-buy-go"
                    class="w-full py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] bg-gray-900/50 border border-gray-800 text-gray-500 hover:border-amber-500/30 hover:text-amber-500 transition-all">
                Buy & Go (Out Service)
            </button>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto custom-gold-scrollbar px-8 pb-8 mt-2">
            <div id="table-grid"></div>
        </div>
    </div>
</div>

<script>
    const POS_COLLECTION = <?php echo json_encode($collection); ?>;
    const MENU_TIER_ID = <?php echo json_encode($activeTier['id'] ?? null); ?>;
    const MENU_TIER_NAME = <?php echo json_encode($menuTierName); ?>;
    const USER_FLOOR_ID = <?php echo json_encode($user['floorId'] ?? ''); ?>;

    let allItems = [];
    let categories = [];
    let distributions = [];
    let floorPlan = [];
    let cart = [];
    let activeTab = 'Food';
    let selectedCategory = '';
    let activeFloorId = '';
    let appName = 'ABE HOTEL';
    let searchTimer = null;

    function esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
    function fmt(n) { return Number(n).toLocaleString() + ' ETB'; }

    async function loadData() {
        try {
            let bootUrl = 'api/cashier/bootstrap.php?collection=' + encodeURIComponent(POS_COLLECTION);
            if (MENU_TIER_ID) bootUrl += '&tier=' + encodeURIComponent(MENU_TIER_ID);
            const resp = await fetch(bootUrl);
            const data = JSON.parse(await resp.text());
            if (!resp.ok) throw new Error(data.message || 'Failed to load');

            allItems = data.items || [];
            categories = data.categories || [];
            distributions = data.distributions || [];
            floorPlan = data.floorPlan || [];
            appName = data.branding?.app_name || appName;

            const floorSelect = document.getElementById('floor-select');
            floorSelect.innerHTML = floorPlan.map(f => `<option value="${esc(f.id)}">${esc(f.label)}</option>`).join('');

            if (floorPlan.length) {
                activeFloorId = floorPlan.find(f => f.id === USER_FLOOR_ID)?.id
                    || floorPlan.find(f => /ground/i.test(f.floorNumber))?.id
                    || floorPlan[0].id;
                
                floorSelect.value = activeFloorId;
                updateFloorInputs();
            }

            populateDistList(distributions);

            initTablePicker();
            renderAll();
        } catch (err) {
            document.getElementById('items-grid').innerHTML =
                `<div class="col-span-full py-12 text-center text-red-400 text-sm">${esc(err.message)}</div>`;
        }
    }

    function printReceipt(order, typeLabel = 'Kitchen Copy') {
        const dateStr = new Date().toLocaleString();
        const receipt = document.getElementById('receipt-print');
        
        let itemsHtml = order.items.map(i => `
            <tr>
                <td style="width: 50%">${esc(i.name)}</td>
                <td style="width: 20%; text-align: center">${i.quantity}</td>
                <td style="width: 30%; text-align: right">${Number(i.price * i.quantity).toLocaleString()}</td>
            </tr>
        `).join('');

        receipt.innerHTML = `
            <div class="receipt-header">
                <div class="receipt-box uppercase">${esc(typeLabel)}</div>
                <div class="receipt-title uppercase">${esc(appName)}</div>
                <p class="receipt-tagline">Hotel Management System</p>
            </div>
            <div class="receipt-divider"></div>
            <div class="receipt-row">
                <span>Order #:</span>
                <span style="font-weight: bold">${esc(order.orderNumber)}</span>
            </div>
            <div class="receipt-row">
                <span>Date:</span>
                <span>${dateStr}</span>
            </div>
            <div class="receipt-row">
                <span>Table:</span>
                <span style="font-weight: bold">${esc(order.tableNumber)}</span>
            </div>
            <div class="receipt-row">
                <span>Floor:</span>
                <span style="font-weight: bold">${esc(order.floorLabel || order.floorNumber || 'Ground')}</span>
            </div>
            <div class="receipt-divider"></div>
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width: 50%">Item</th>
                        <th style="width: 20%; text-align: center">Qty</th>
                        <th style="width: 30%; text-align: right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>
            <div class="receipt-row receipt-total">
                <span>TOTAL:</span>
                <span>${Number(order.totalAmount).toLocaleString()} ETB</span>
            </div>
            <div class="receipt-divider"></div>
            <div class="receipt-footer">
                <p style="font-weight: bold; font-size: 11px; margin-bottom: 4px;">THANK YOU!</p>
                <p>Please visit us again</p>
                <p style="margin-top: 10px; font-size: 9px; opacity: 0.8;">Powered by Prime Addis POS</p>
            </div>
        `;

        window.print();
    }

    /* ── Custom Distribution Dropdown ── */
    function populateDistList(dists) {
        const list = document.getElementById('dist-list');
        const sel  = document.getElementById('distribution-select');
        sel.innerHTML = '<option value="">All Distributions</option>' +
            dists.map(d => `<option value="${esc(d.name)}">${esc(d.name)}</option>`).join('');

        const items = [{ name: '', label: 'All Distributions' }, ...dists.map(d => ({ name: d.name, label: d.name }))];
        list.innerHTML = items.map(item => `
            <button type="button" onclick="selectDist('${item.name.replace(/'/g,"\\'")}', '${item.label.replace(/'/g,"\\'")}')"\
                    class="w-full text-left px-5 py-4 text-[10px] font-black uppercase tracking-widest transition-all hover:bg-amber-500/10 hover:text-amber-400 text-gray-400 border-b border-gray-800/40 last:border-0">
                ${esc(item.label)}
            </button>`).join('');
    }

    function toggleDistDropdown() {
        const panel   = document.getElementById('dist-panel');
        const chevron = document.getElementById('dist-chevron');
        const isOpen  = !panel.classList.contains('hidden');
        panel.classList.toggle('hidden', isOpen);
        chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
    }

    function selectDist(value, label) {
        document.getElementById('distribution-select').value = value;
        document.getElementById('dist-label').textContent = label || 'All Distributions';
        document.getElementById('dist-panel').classList.add('hidden');
        document.getElementById('dist-chevron').style.transform = '';
    }

    document.addEventListener('click', e => {
        const wrap = document.getElementById('dist-dropdown-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dist-panel')?.classList.add('hidden');
            const ch = document.getElementById('dist-chevron');
            if (ch) ch.style.transform = '';
        }
    });

    function getFilteredItems() {
        const nameQ = document.getElementById('search-name').value.toLowerCase().trim();
        const idQ = document.getElementById('search-id').value.trim();
        let list = allItems.filter(i => i.mainCategory === activeTab);
        if (selectedCategory) list = list.filter(i => i.category === selectedCategory);
        if (nameQ) list = list.filter(i => (i.name || '').toLowerCase().includes(nameQ) || (i.category || '').toLowerCase().includes(nameQ));
        if (idQ) list = list.filter(i => (i.menuId || '').toString().includes(idQ));
        return list;
    }

    function initTablePicker() {
        renderFloorTabs();
        renderTableGrid();
    }

    function renderFloorTabs() {
        const el = document.getElementById('floor-tabs');
        if (!floorPlan.length) return;
        el.innerHTML = floorPlan.map(f => {
            const on = f.id === activeFloorId;
            return `<button type="button" data-floor="${esc(f.id)}"
                class="floor-tab px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all ${on ? 'bg-[#151515] text-white border-gray-600 shadow-lg' : 'bg-gray-900/40 border-gray-800/50 text-gray-500 hover:text-gray-300'}">${esc(f.label)}</button>`;
        }).join('');
    }

    function renderTableGrid() {
        const grid = document.getElementById('table-grid');
        const selectedNum = document.getElementById('table-number').value;

        if (!floorPlan.length) {
            grid.innerHTML = '<p class="col-span-4 text-center text-xs text-gray-600 py-12 uppercase tracking-widest font-black">No tables found</p>';
            return;
        }

        grid.innerHTML = floorPlan.map(floor => {
            if (!floor.tables || !floor.tables.length) return '';
            return `
                <div class="mb-8">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.3em] mb-4 px-1 pb-2 border-b border-gray-800/60">${esc(floor.label)}</p>
                    <div class="grid grid-cols-4 gap-3">
                        ${floor.tables.map(t => {
                            const on = selectedNum === t.tableNumber;
                            const label = String(t.tableNumber).startsWith('T#') ? t.tableNumber : 'T#' + t.tableNumber;
                            return `<button type="button" data-table="${esc(t.tableNumber)}" data-floor-id="${esc(floor.id)}" data-floor-num="${esc(floor.floorNumber)}"
                                class="table-pick h-14 w-full flex items-center justify-center rounded-2xl text-sm font-black border transition-all ${on ? 'bg-amber-500/10 text-amber-400 border-amber-500/40 shadow-[0_0_20px_rgba(245,158,11,0.15)]' : 'bg-[#111] border-gray-800 text-gray-300 hover:border-gray-500 hover:text-white hover:bg-gray-900/50'}">${esc(label)}</button>`;
                        }).join('')}
                    </div>
                </div>`;
        }).join('');
    }

    function setTableSelection(tableNumber, floorId, floorNumber, label) {
        document.getElementById('table-number').value = tableNumber;
        document.getElementById('floor-id').value = floorId || '';
        document.getElementById('floor-number').value = floorNumber || '';
        if (floorId) {
            document.getElementById('floor-select').value = floorId;
            activeFloorId = floorId;
        }
        document.getElementById('table-picker-label').textContent = label;
        closeTableModal();
    }

    function updateFloorInputs() {
        const floorId = document.getElementById('floor-select').value;
        const floor = floorPlan.find(f => f.id === floorId);
        if (floor) {
            document.getElementById('floor-id').value = floor.id;
            document.getElementById('floor-number').value = floor.floorNumber;
            activeFloorId = floor.id;
        }
    }

    function openTableModal() {
        document.getElementById('table-modal').classList.remove('hidden');
        renderFloorTabs();
        renderTableGrid();
        lucide.createIcons();
    }

    function closeTableModal() {
        document.getElementById('table-modal').classList.add('hidden');
    }

    function renderCategoryChips() {
        const bar = document.getElementById('category-chips');
        const cats = [...new Set(allItems.filter(i => i.mainCategory === activeTab).map(i => i.category).filter(Boolean))].sort();
        const genBtn = (label, cat, active) => `
            <button type="button" data-cat="${esc(cat)}" 
                    class="cat-chip shrink-0 px-5 py-2.5 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all ${active ? 'bg-amber-500 text-black border-amber-500' : 'bg-gray-900/40 border-gray-800 text-gray-500 hover:text-gray-300'}">
                ${esc(label)}
            </button>`;
            
        bar.innerHTML = genBtn('All Items', '', !selectedCategory) +
            cats.map(c => genBtn(c, c, selectedCategory === c)).join('');
    }

    function renderMainTabs() {
        const foodN = allItems.filter(i => i.mainCategory === 'Food').length;
        const drinkN = allItems.filter(i => i.mainCategory === 'Drinks').length;
        
        ['Food', 'Drinks'].forEach(tab => {
            const on = activeTab === tab;
            const mainCls = on ? 'bg-white/10 text-white border-gray-600' : 'bg-gray-900/20 border-gray-800/50 text-gray-600 hover:text-gray-400';
            const cartCls = on ? 'bg-gray-900 text-white border-gray-700 shadow-lg' : 'bg-[#1a1c1b]/30 border-gray-800/40 text-gray-600 hover:text-gray-400';
            
            const mainBtn = document.getElementById(tab === 'Food' ? 'main-tab-food' : 'main-tab-drinks');
            if (mainBtn) {
                mainBtn.className = `main-cat-tab flex-1 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border transition-all ${mainCls}`;
                mainBtn.innerHTML = `${tab === 'Food' ? '🍔' : '🍹'} ${esc(tab)} <span class="opacity-30 ml-1">(${tab === 'Food' ? foodN : drinkN})</span>`;
            }
            
            const cartBtn = document.getElementById(tab === 'Food' ? 'cart-tab-food' : 'cart-tab-drinks');
            if (cartBtn) {
                cartBtn.className = `cart-cat-tab flex-1 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all flex items-center justify-center gap-2 ${cartCls}`;
                cartBtn.innerHTML = `${tab === 'Food' ? '🥩 BUTCHER' : '🥤 DRINKS'}`;
            }
        });
    }

    function renderGrid() {
        const grid = document.getElementById('items-grid');
        const filtered = getFilteredItems();
        document.getElementById('available-count').textContent = filtered.length;

        if (!filtered.length) {
            grid.innerHTML = '<div class="col-span-full py-16 text-center text-muted-foreground text-sm uppercase tracking-widest font-bold">No items found</div>';
            return;
        }

        grid.innerHTML = filtered.map(item => `
            <button type="button" data-add="${esc(item.id)}"
                    class="group flex flex-col rounded-xl overflow-hidden border border-gray-700/50 bg-gray-900/60 hover:border-blue-500/40 hover:bg-gray-900 transition-all active:scale-[0.97] text-left">
                <div class="aspect-square w-full overflow-hidden bg-gray-950 relative">
                    ${item.hasImage ? `
                    <img src="api/cashier/image.php?id=${encodeURIComponent(item.id)}" loading="lazy" decoding="async"
                         alt="${esc(item.name)}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                         onerror="this.style.display='none'">` : ''}
                    <span class="absolute top-1.5 right-1.5 text-[9px] font-bold text-blue-400 bg-gray-900/80 px-1.5 py-0.5 rounded border border-gray-700">#${esc(item.menuId || '?')}</span>
                </div>
                <div class="p-3 min-h-[3.5rem]">
                    <p class="text-[11px] font-bold text-white leading-tight line-clamp-2 group-hover:text-blue-400 transition-colors">${esc(item.name)}</p>
                    <p class="text-[10px] font-bold text-blue-400 mt-1 font-mono">${Number(item.price).toLocaleString()} ETB</p>
                </div>
            </button>
        `).join('');
    }

    function renderAll() {
        renderMainTabs();
        renderCategoryChips();
        renderGrid();
    }

    function setActiveTab(tab) {
        activeTab = tab;
        selectedCategory = '';
        renderAll();
    }

    function addToCart(itemId) {
        const item = allItems.find(i => i.id === itemId);
        if (!item) return;
        const ex = cart.find(c => c.id === itemId);
        if (ex) ex.quantity++;
        else cart.push({ ...item, quantity: 1, notes: '' });
        renderCart();
    }

    function renderCart() {
        const container = document.getElementById('cart-container');
        const empty = document.getElementById('cart-empty');
        const badge = document.getElementById('cart-badge');
        const btn = document.getElementById('place-order-btn');
        const totalItems = cart.reduce((a, i) => a + i.quantity, 0);

        badge.textContent = totalItems + (totalItems === 1 ? ' ITEM' : ' ITEMS');
        document.getElementById('items-count-badge').textContent = badge.textContent;
        btn.disabled = !cart.length;

        if (!cart.length) {
            container.classList.add('hidden');
            empty.classList.remove('hidden');
            document.getElementById('cart-total-val').textContent = '0';
            return;
        }

        empty.classList.add('hidden');
        container.classList.remove('hidden');
        container.innerHTML = cart.map((item, i) => `
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-gray-900/50 border border-gray-800/50 hover:border-amber-500/30 transition-all group">
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-black text-white uppercase tracking-wider truncate group-hover:text-amber-500 transition-colors">${esc(item.name)}</p>
                    <p class="text-[9px] font-bold text-gray-500 mt-1 uppercase tracking-widest">${fmt(item.price)} &times; ${item.quantity}</p>
                </div>
                <div class="flex items-center bg-black border border-gray-800 rounded-xl overflow-hidden shrink-0">
                    <button type="button" data-qty="${i}" data-delta="-1" class="qty-btn w-8 h-8 text-xs font-black text-gray-400 hover:text-amber-500 transition-colors">－</button>
                    <span class="w-8 text-center text-[10px] font-black text-white">${item.quantity}</span>
                    <button type="button" data-qty="${i}" data-delta="1" class="qty-btn w-8 h-8 text-xs font-black text-gray-400 hover:text-amber-500 transition-colors">＋</button>
                </div>
                <button type="button" data-remove="${i}" class="text-red-500/30 hover:text-red-500 transition-colors px-1">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        `).join('');

        const total = cart.reduce((a, i) => a + i.price * i.quantity, 0);
        document.getElementById('cart-total-val').textContent = Number(total).toLocaleString();
        const totalTop = document.getElementById('cart-total');
        if (totalTop) totalTop.textContent = fmt(total);
        lucide.createIcons();
    }

    async function placeOrder() {
        const btn = document.getElementById('place-order-btn');
        const old = btn.innerHTML;
        try {
            btn.disabled = true;
            btn.textContent = 'Sending...';
            const dist = document.getElementById('distribution-select').value;
            const resp = await fetch('api/orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tableNumber: document.getElementById('table-number').value,
                    floorId: document.getElementById('floor-id').value || null,
                    floorNumber: document.getElementById('floor-number').value || null,
                    paymentMethod: 'cash',
                    batchNumber: document.getElementById('batch-number').value.trim() || null,
                    distributions: dist ? [dist] : [],
                    menuTierId: MENU_TIER_ID,
                    menuTierName: MENU_TIER_NAME,
                    menuCollection: POS_COLLECTION,
                    totalAmount: cart.reduce((a, i) => a + i.price * i.quantity, 0),
                    items: cart.map(i => ({
                        menuItemId: i.id,
                        menuId: i.menuId,
                        name: i.name,
                        quantity: i.quantity,
                        price: i.price,
                        category: i.category,
                        mainCategory: i.mainCategory,
                        notes: ''
                    }))
                })
            });
            const result = await resp.json();
            if (resp.ok) {
                const orderData = {
                    orderNumber: result.orderNumber,
                    tableNumber: document.getElementById('table-number').value,
                    floorLabel: document.getElementById('table-picker-label').textContent.split(' · ')[1] || null,
                    floorNumber: document.getElementById('floor-number').value,
                    items: cart,
                    totalAmount: cart.reduce((a, i) => a + i.price * i.quantity, 0)
                };
                
                cart = [];
                document.getElementById('batch-number').value = '';
                renderCart();
                
                // Trigger sequential printing for Kitchen and Table copies
                setTimeout(() => {
                    const receipt = document.getElementById('receipt-print');
                    if (receipt.parentElement !== document.body) {
                        document.body.appendChild(receipt);
                    }
                    // Sequential calls: standard browsers will wait for first dialog to close
                    printReceipt(orderData, 'Kitchen Copy');
                    printReceipt(orderData, 'Table Copy');
                }, 150);
            } else alert('Error: ' + (result.message || 'Failed'));
        } catch { alert('Server error.'); }
        finally { btn.disabled = !cart.length; btn.innerHTML = old; }
    }

    document.getElementById('main-tab-food').onclick = () => setActiveTab('Food');
    document.getElementById('main-tab-drinks').onclick = () => setActiveTab('Drinks');
    document.getElementById('cart-tab-food').onclick = () => setActiveTab('Food');
    document.getElementById('cart-tab-drinks').onclick = () => setActiveTab('Drinks');

    document.getElementById('category-chips').addEventListener('click', e => {
        const chip = e.target.closest('.cat-chip');
        if (chip) { selectedCategory = chip.dataset.cat || ''; renderAll(); }
    });

    document.getElementById('items-grid').addEventListener('click', e => {
        const btn = e.target.closest('[data-add]');
        if (btn) addToCart(btn.dataset.add);
    });

    document.getElementById('cart-container').addEventListener('click', e => {
        const q = e.target.closest('.qty-btn');
        if (q) {
            const i = +q.dataset.qty;
            cart[i].quantity += +q.dataset.delta;
            if (cart[i].quantity < 1) cart.splice(i, 1);
            renderCart();
            return;
        }
        const rm = e.target.closest('[data-remove]');
        if (rm) { cart.splice(+rm.dataset.remove, 1); renderCart(); }
    });

    document.getElementById('place-order-btn').onclick = placeOrder;

    document.getElementById('floor-select').onchange = () => {
        updateFloorInputs();
        // Clear table if it doesn't belong to the new floor
        const currentTable = document.getElementById('table-number').value;
        if (currentTable !== 'Buy&Go') {
            setTableSelection('Buy&Go', '', '', 'Buy & Go');
        }
    };

    document.getElementById('table-picker-btn').onclick = openTableModal;
    document.getElementById('table-modal-close').onclick = closeTableModal;
    document.getElementById('table-modal').addEventListener('click', e => {
        if (e.target.id === 'table-modal') closeTableModal();
    });
    document.getElementById('pick-buy-go').onclick = () => setTableSelection('Buy&Go', '', '', 'Buy & Go');

    document.getElementById('floor-tabs').addEventListener('click', e => {
        const tab = e.target.closest('[data-floor]');
        if (tab) { activeFloorId = tab.dataset.floor; renderFloorTabs(); renderTableGrid(); }
    });

    document.getElementById('table-grid').addEventListener('click', e => {
        const btn = e.target.closest('.table-pick');
        if (!btn) return;
        const floor = floorPlan.find(f => f.id === btn.dataset.floorId);
        const label = btn.dataset.table + (floor ? ' · ' + floor.label.replace('FLOOR #', 'Floor ') : '');
        setTableSelection(btn.dataset.table, btn.dataset.floorId, btn.dataset.floorNum, label);
    });

    ['search-name', 'search-id'].forEach(id => {
        document.getElementById(id).addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(renderGrid, 120);
        });
    });

    loadData();
</script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,700&display=swap');

    .pos-inp {
        background: #111413;
        border: 1px solid #374151;
        border-radius: 0.75rem;
        padding: 0.65rem 0.875rem;
        font-size: 0.8125rem;
        color: #fff;
        outline: none;
        transition: border-color 0.15s ease;
    }
    .pos-inp:focus { border-color: rgba(59, 130, 246, 0.55); }
    .pos-inp option { background: #1a1d1c; }
    .pos-label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280; margin-bottom: 4px; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 999px; }
    
    .custom-gold-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-gold-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-gold-scrollbar::-webkit-scrollbar-thumb { background: #c5a059; border-radius: 10px; border: 2px solid #0a0a0a; }
    .custom-gold-scrollbar::-webkit-scrollbar-thumb:hover { background: #d4af37; }

    /* ── Receipt Printing Styles ── */
    #receipt-print { display: none; }
    @media print {
        @page { margin: 0; size: auto; }
        body { visibility: hidden; background: white !important; }
        #receipt-print { 
            visibility: visible !important;
            display: block !important; 
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm; 
            padding: 4mm; 
            color: black !important;
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 13px;
            line-height: 1.3;
        }
        #receipt-print * { visibility: visible !important; color: black !important; }
        .receipt-header { text-align: center; margin-bottom: 10px; }
        .receipt-title { font-size: 20px; font-weight: 900; text-transform: uppercase; margin: 5px 0; }
        .receipt-tagline { font-size: 11px; margin-bottom: 10px; }
        .receipt-box { border: 2px solid black; padding: 3px 10px; display: inline-block !important; font-weight: 900; margin-bottom: 10px; }
        .receipt-divider { border-bottom: 2px dashed black; margin: 10px 0; }
        .receipt-row { display: flex !important; justify-content: space-between; margin-bottom: 4px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .receipt-table th { text-align: left; border-bottom: 2px solid black; padding: 5px 0; font-size: 12px; }
        .receipt-table td { padding: 5px 0; vertical-align: top; border-bottom: 1px dashed #eee; }
        .receipt-total { font-size: 16px; font-weight: 900; margin-top: 10px; border-top: 2px dashed black; padding-top: 10px; }
        .receipt-footer { text-align: center; margin-top: 20px; font-size: 11px; }
    }
</style>

<!-- Receipt Template -->
<div id="receipt-print"></div>

<?php renderFooter(); ?>
