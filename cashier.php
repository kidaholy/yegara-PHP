<?php
/**
 * Cashier POS — Standard / VIP layout (menu grid + order cart sidebar)
 */
require_once 'includes/layout.php';

requireAuth(['cashier', 'admin']);

$posMode = $_GET['mode'] ?? 'standard';
if (!in_array($posMode, ['standard', 'vip1', 'vip2'], true)) $posMode = 'standard';

$collections = ['standard' => 'menuItems', 'vip1' => 'vip1Menu', 'vip2' => 'vip2Menu'];
$titles = ['standard' => 'Standard POS', 'vip1' => 'VIP 1 POS', 'vip2' => 'VIP 2 POS'];
$collection = $collections[$posMode];
$posTitle = $titles[$posMode];
$user = getCurrentUser();
$userName = $user['name'] ?? 'Cashier';
$welcomeDate = date('D, M j');

renderHeader($posTitle, ['nav' => 'pos', 'posTab' => $posMode]);
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
            <div class="lg:col-span-4 flex flex-col self-start w-full glass p-6 rounded-2xl border border-gray-700/50 bg-gray-800/60">
                <div class="flex items-center justify-between mb-4 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                            <i data-lucide="receipt" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Order Cart</h2>
                            <p class="text-xs text-gray-500 font-medium">Table &amp; distribution</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 shrink-0 mb-4">
                    <button type="button" id="cart-tab-food" data-tab="Food" class="cart-cat-tab py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider border transition-all">Food</button>
                    <button type="button" id="cart-tab-drinks" data-tab="Drinks" class="cart-cat-tab py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider border transition-all">Drinks</button>
                </div>

                <div class="space-y-3 shrink-0 mb-4 pb-4 border-b border-gray-700/50">
                    <div>
                        <label class="pos-label">Select Table</label>
                        <button type="button" id="table-picker-btn" class="pos-inp w-full flex items-center justify-between text-left">
                            <span id="table-picker-label">Buy & Go</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-500 shrink-0"></i>
                        </button>
                        <input type="hidden" id="table-number" value="Buy&Go">
                        <input type="hidden" id="floor-id" value="">
                        <input type="hidden" id="floor-number" value="">
                    </div>
                    <div>
                        <label class="pos-label">Distribution</label>
                        <select id="distribution-select" class="pos-inp w-full"><option value="">All Distributions</option></select>
                    </div>
                    <div>
                        <label class="pos-label">Batch Number</label>
                        <input type="text" id="batch-number" placeholder="Optional..." class="pos-inp w-full">
                    </div>
                </div>

                <div class="max-h-72 overflow-y-auto custom-scrollbar" id="cart-scroll">
                    <div id="cart-container" class="space-y-2 hidden"></div>
                    <div id="cart-empty" class="flex flex-col items-center justify-center text-center opacity-40 py-6">
                        <i data-lucide="shopping-cart" class="w-12 h-12 mb-3 text-gray-600"></i>
                        <p class="text-sm font-medium text-gray-500">Your cart is empty</p>
                    </div>
                </div>

                <button id="place-order-btn" type="button" disabled
                        class="w-full mt-3 bg-blue-500 hover:bg-blue-600 text-white font-bold py-3.5 rounded-xl text-sm uppercase tracking-widest disabled:opacity-30 disabled:cursor-not-allowed transition-all shrink-0 flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> Send to Kitchen
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table picker modal -->
<div id="table-modal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
    <div class="w-full max-w-lg glass border border-gray-700/50 bg-gray-900/95 rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 pt-6 pb-4 shrink-0">
            <h3 class="text-xl font-bold text-white tracking-wide">Select Table</h3>
            <button type="button" id="table-modal-close" class="w-8 h-8 rounded-lg flex items-center justify-center text-muted-foreground hover:text-white hover:bg-white/5">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="px-6 pb-3 shrink-0">
            <button type="button" id="pick-buy-go"
                    class="w-full py-2.5 mb-3 rounded-xl text-xs font-bold uppercase tracking-wider border border-gray-700 text-gray-400 hover:border-blue-500/40 hover:text-blue-400 transition-all">
                Buy & Go (no table)
            </button>
            <div id="floor-tabs" class="grid grid-cols-3 gap-2"></div>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto custom-scrollbar px-6 pb-6">
            <div id="table-grid" class="grid grid-cols-4 gap-2"></div>
        </div>
    </div>
</div>

<script>
    const POS_COLLECTION = <?php echo json_encode($collection); ?>;
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
            const resp = await fetch('api/cashier/bootstrap.php?collection=' + encodeURIComponent(POS_COLLECTION));
            const data = JSON.parse(await resp.text());
            if (!resp.ok) throw new Error(data.message || 'Failed to load');

            allItems = data.items || [];
            categories = data.categories || [];
            distributions = data.distributions || [];
            floorPlan = data.floorPlan || [];
            appName = data.branding?.app_name || appName;

            if (floorPlan.length) {
                activeFloorId = floorPlan.find(f => f.id === USER_FLOOR_ID)?.id
                    || floorPlan.find(f => /ground/i.test(f.floorNumber))?.id
                    || floorPlan[0].id;
            }

            document.getElementById('distribution-select').innerHTML = '<option value="">All Distributions</option>' +
                distributions.map(d => `<option value="${esc(d.name)}">${esc(d.name)}</option>`).join('');

            initTablePicker();
            renderAll();
        } catch (err) {
            document.getElementById('items-grid').innerHTML =
                `<div class="col-span-full py-12 text-center text-red-400 text-sm">${esc(err.message)}</div>`;
        }
    }

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
        if (!floorPlan.length) {
            el.innerHTML = '<p class="col-span-3 text-xs text-muted-foreground text-center py-2">No floors configured</p>';
            return;
        }
        el.innerHTML = floorPlan.map(f => {
            const on = f.id === activeFloorId;
            return `<button type="button" data-floor="${esc(f.id)}"
                class="floor-tab px-2 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wide border transition-all text-center leading-tight ${on ? 'bg-blue-500/15 text-blue-400 border-blue-500/40' : 'border-gray-700 text-gray-400 hover:text-white hover:border-gray-600'}">${esc(f.label)}</button>`;
        }).join('');
    }

    function renderTableGrid() {
        const grid = document.getElementById('table-grid');
        const floor = floorPlan.find(f => f.id === activeFloorId);
        const selectedNum = document.getElementById('table-number').value;

        if (!floor || !floor.tables.length) {
            grid.innerHTML = '<p class="col-span-4 text-center text-xs text-muted-foreground py-8">No tables on this floor</p>';
            return;
        }

        grid.innerHTML = floor.tables.map(t => {
            const on = selectedNum === t.tableNumber;
            return `<button type="button" data-table="${esc(t.tableNumber)}" data-floor-id="${esc(floor.id)}" data-floor-num="${esc(floor.floorNumber)}"
                class="table-pick py-3 rounded-lg text-xs font-bold border transition-all ${on ? 'bg-blue-500/20 text-blue-400 border-blue-500/40' : 'border-gray-700 text-gray-300 hover:border-blue-500/40 hover:text-blue-400'}">${esc(t.tableNumber)}</button>`;
        }).join('');
    }

    function setTableSelection(tableNumber, floorId, floorNumber, label) {
        document.getElementById('table-number').value = tableNumber;
        document.getElementById('floor-id').value = floorId || '';
        document.getElementById('floor-number').value = floorNumber || '';
        document.getElementById('table-picker-label').textContent = label;
        closeTableModal();
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
        bar.innerHTML = `<button type="button" data-cat="" class="cat-chip shrink-0 px-4 py-2 rounded-full text-[11px] font-bold uppercase tracking-wide border transition-all ${!selectedCategory ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-700 text-gray-400 hover:text-white hover:border-gray-600'}">All Items</button>` +
            cats.map(c => `<button type="button" data-cat="${esc(c)}" class="cat-chip shrink-0 px-4 py-2 rounded-full text-[11px] font-bold uppercase tracking-wide border transition-all ${selectedCategory === c ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-700 text-gray-400 hover:text-white hover:border-gray-600'}">${esc(c)}</button>`).join('');
    }

    function renderMainTabs() {
        const foodN = allItems.filter(i => i.mainCategory === 'Food').length;
        const drinkN = allItems.filter(i => i.mainCategory === 'Drinks').length;
        document.getElementById('food-count').textContent = `(${foodN})`;
        document.getElementById('drinks-count').textContent = `(${drinkN})`;

        ['Food', 'Drinks'].forEach(tab => {
            const on = activeTab === tab;
            const cls = on ? 'bg-blue-500/15 text-blue-400 border-blue-500/40' : 'border-gray-700 text-gray-400 hover:text-white hover:border-gray-600';
            document.getElementById(tab === 'Food' ? 'main-tab-food' : 'main-tab-drinks').className = `main-cat-tab flex-1 py-3 rounded-xl text-sm font-bold border transition-all ${cls}`;
            document.getElementById(tab === 'Food' ? 'cart-tab-food' : 'cart-tab-drinks').className = `cart-cat-tab py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider border transition-all ${cls}`;
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

        badge.textContent = totalItems + (totalItems === 1 ? ' Item' : ' Items');
        btn.disabled = !cart.length;

        if (!cart.length) {
            container.classList.add('hidden');
            empty.classList.remove('hidden');
            document.getElementById('cart-total').textContent = '0 ETB';
            return;
        }

        empty.classList.add('hidden');
        container.classList.remove('hidden');
        container.innerHTML = cart.map((item, i) => `
            <div class="flex items-center gap-2 p-3 rounded-xl bg-gray-900/60 border border-gray-700/50 hover:border-blue-500/30 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-white truncate">${esc(item.name)}</p>
                    <p class="text-[10px] text-muted-foreground">${fmt(item.price)} &times; ${item.quantity}</p>
                </div>
                <div class="flex items-center bg-gray-800 rounded-lg border border-gray-700 shrink-0">
                    <button type="button" data-qty="${i}" data-delta="-1" class="qty-btn w-7 h-7 text-sm font-bold text-muted-foreground hover:text-white">−</button>
                    <span class="w-7 text-center text-xs font-bold">${item.quantity}</span>
                    <button type="button" data-qty="${i}" data-delta="1" class="qty-btn w-7 h-7 text-sm font-bold text-muted-foreground hover:text-white">+</button>
                </div>
                <button type="button" data-remove="${i}" class="text-red-400/50 hover:text-red-400 text-xs px-1">✕</button>
            </div>
        `).join('');

        const total = cart.reduce((a, i) => a + i.price * i.quantity, 0);
        document.getElementById('cart-total').textContent = Number(total).toLocaleString() + ' ETB';
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
                alert('Order #' + result.orderNumber + ' sent to kitchen!');
                cart = [];
                document.getElementById('batch-number').value = '';
                renderCart();
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
</style>

<?php renderFooter(); ?>
