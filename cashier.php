<?php
/**
 * Cashier POS — Standard / VIP layout (menu grid + order cart sidebar)
 */
require_once 'includes/layout.php';

requireAuth(['cashier', 'admin'], 'cashier:access');

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

                <div class="flex gap-2 shrink-0 mb-3" id="main-tabs-container">
                    <!-- Dynamic tabs populated by JS -->
                </div>

                <div class="relative group/cat-slider mb-4">
                    <button type="button" onclick="scrollCats(-200)" class="absolute -left-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-gray-900/90 border border-gray-700/50 text-gray-400 opacity-0 group-hover/cat-slider:opacity-100 hover:text-white hover:border-amber-500/50 transition-all flex items-center justify-center shadow-xl backdrop-blur-sm">
                        <i data-lucide="chevron-left" class="w-5 h-5"></i>
                    </button>
                    
                    <div class="flex gap-2 overflow-x-auto shrink-0 pb-2 scroll-smooth custom-h-scrollbar" id="category-chips">
                        <button type="button" data-cat="" class="cat-chip shrink-0 px-4 py-2 rounded-full text-[11px] font-bold uppercase tracking-wide border transition-all">All Items</button>
                    </div>

                    <button type="button" onclick="scrollCats(200)" class="absolute -right-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-gray-900/90 border border-gray-700/50 text-gray-400 opacity-0 group-hover/cat-slider:opacity-100 hover:text-white hover:border-amber-500/50 transition-all flex items-center justify-center shadow-xl backdrop-blur-sm">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>
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
                <div class="flex gap-2 shrink-0 mb-6" id="cart-tabs-container">
                    <!-- Dynamic cart tabs populated by JS -->
                </div>

                <!-- Input Boxes -->
                <div class="space-y-3 mb-6">
                    <!-- Hidden floor select (used by JS, not displayed) -->
                    <select id="floor-select" class="hidden"></select>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button type="button" id="table-picker-btn" 
                                class="w-full h-16 px-4 rounded-2xl bg-gray-900 border border-gray-800 flex items-center justify-between text-left group hover:border-gray-600 transition-all">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-gray-800/80 border border-gray-700/60 flex items-center justify-center text-amber-500/90 shrink-0">
                                    <i data-lucide="armchair" class="w-4 h-4"></i>
                                </span>
                                <span id="table-picker-label" class="text-[10px] font-black text-gray-400 uppercase tracking-widest truncate">Select Table</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600 shrink-0"></i>
                        </button>
                        <button type="button" id="room-picker-btn"
                                class="w-full h-16 px-4 rounded-2xl bg-gray-900 border border-gray-800 flex items-center justify-between text-left group hover:border-gray-600 transition-all">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-gray-800/80 border border-gray-700/60 flex items-center justify-center text-amber-500/90 shrink-0">
                                    <i data-lucide="bed-double" class="w-4 h-4"></i>
                                </span>
                                <span id="room-picker-label" class="text-[10px] font-black text-gray-400 uppercase tracking-widest truncate">Checked-in Room</span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600 shrink-0"></i>
                        </button>
                    </div>

                    <!-- Custom Distribution Dropdown -->
                    <div class="relative" id="dist-dropdown-wrap">
                        <button type="button" id="dist-trigger"
                                onclick="toggleDistDropdown()"
                                class="w-full h-16 px-4 rounded-2xl bg-gray-900 border border-gray-800 flex items-center justify-between text-left hover:border-gray-600 transition-all">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-9 h-9 rounded-xl bg-gray-800/80 border border-gray-700/60 flex items-center justify-center text-amber-500/90 shrink-0">
                                    <i data-lucide="truck" class="w-4 h-4"></i>
                                </span>
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

                    <div class="relative h-16 rounded-2xl bg-gray-900 border border-gray-800 flex items-center px-4 gap-3">
                        <span class="w-9 h-9 rounded-xl bg-gray-800/80 border border-gray-700/60 flex items-center justify-center text-amber-500/90 shrink-0">
                            <i data-lucide="hash" class="w-4 h-4"></i>
                        </span>
                        <input type="text" id="batch-number" placeholder="BATCH NUMBER" 
                               class="bg-transparent text-[10px] font-black text-gray-400 uppercase tracking-widest w-full outline-none placeholder:text-gray-700">
                    </div>

                    <input type="hidden" id="table-number" value="Buy&Go">
                    <input type="hidden" id="room-number" value="">
                    <input type="hidden" id="room-id" value="">
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
                        <i data-lucide="chef-hat" class="w-4 h-4"></i> SEND TO KITCHEN
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Room picker modal -->
<div id="room-modal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
    <div class="w-full max-w-3xl bg-[#0a0a0a] border border-gray-800/80 rounded-3xl shadow-[0_0_50px_rgba(0,0,0,0.8)] overflow-hidden max-h-[90vh] flex flex-col relative">
        <button type="button" id="room-modal-close"
                class="absolute top-6 right-6 w-10 h-10 rounded-xl bg-gray-900 border border-gray-800 flex items-center justify-center text-gray-500 hover:text-white hover:border-amber-500/50 transition-all z-10">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
        <div class="px-8 pt-8 pb-4 shrink-0">
            <h3 class="text-3xl font-bold text-amber-500 tracking-tight italic" style="font-family: 'Playfair Display', serif;">Checked-in Rooms</h3>
            <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.25em] mt-2">Select a guest room</p>
        </div>
        <div class="px-8 pb-4 shrink-0">
            <button type="button" id="clear-room-pick"
                    class="w-full py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] bg-gray-900/50 border border-gray-800 text-gray-500 hover:border-amber-500/30 hover:text-amber-500 transition-all">
                Clear Room Selection
            </button>
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto custom-gold-scrollbar px-8 pb-8 mt-2">
            <div id="room-grid"></div>
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
    let allRooms = [];
    let cart = [];
    let activeTab = '';
    let selectedCategory = '';
    let mainCategories = [];
    let activeFloorId = '';
    let appName = 'ABE HOTEL';
    let enablePrinting = true;
    let searchTimer = null;

    function esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
    function fmt(n) { return Number(n).toLocaleString() + ' ETB'; }

    function scrollCats(amt) {
        document.getElementById('category-chips').scrollBy({ left: amt, behavior: 'smooth' });
    }

    function initDragScroll() {
        const slider = document.getElementById('category-chips');
        if (!slider) return;
        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.classList.add('active');
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
            slider.style.cursor = 'grabbing';
            slider.style.userSelect = 'none';
        });
        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });
        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.style.cursor = 'grab';
        });
        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2; // scroll-fast
            slider.scrollLeft = scrollLeft - walk;
        });

        // Initialize cursor
        slider.style.cursor = 'grab';
    }

    function getSavedFloorId() {
        return document.getElementById('floor-id')?.value || activeFloorId || '';
    }

    function resolveFloorId() {
        const saved = getSavedFloorId();
        if (saved && floorPlan.find(f => f.id === saved)) return saved;
        if (USER_FLOOR_ID && floorPlan.find(f => f.id === USER_FLOOR_ID)) return USER_FLOOR_ID;
        return floorPlan.find(f => /ground/i.test(f.floorNumber))?.id || floorPlan[0]?.id || '';
    }

    function syncFloorSelection(floorId) {
        if (!floorId) return;
        const floor = floorPlan.find(f => f.id === floorId);
        if (!floor) return;
        activeFloorId = floor.id;
        document.getElementById('floor-select').value = floor.id;
        document.getElementById('floor-id').value = floor.id;
        document.getElementById('floor-number').value = floor.floorNumber;
    }

    async function loadData() {
        try {
            let bootUrl = 'api/cashier/bootstrap.php?collection=' + encodeURIComponent(POS_COLLECTION);
            if (MENU_TIER_ID) bootUrl += '&tier=' + encodeURIComponent(MENU_TIER_ID);
            const resp = await fetch(bootUrl);
            const text = await resp.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (jsonErr) {
                console.error('Invalid JSON:', text);
                if (!resp.ok) throw new Error(`Server Error (${resp.status})`);
                throw new Error('Invalid response from server');
            }
            if (!resp.ok) throw new Error(data.message || `Failed to load (${resp.status})`);

            allItems = data.items || [];
            categories = data.categories || [];
            distributions = data.distributions || [];
            floorPlan = data.floorPlan || [];
            allRooms = data.rooms || [];
            appName = data.branding?.app_name || appName;
            enablePrinting = data.configuration?.enable_cashier_printing !== false;

            const floorSelect = document.getElementById('floor-select');
            floorSelect.innerHTML = floorPlan.map(f => `<option value="${esc(f.id)}">${esc(f.label)}</option>`).join('');

            if (floorPlan.length) {
                syncFloorSelection(resolveFloorId());
            }

            populateDistList(distributions);
            initDragScroll();

            initTablePicker();
            initRoomPicker();
            renderAll();
        } catch (err) {
            document.getElementById('items-grid').innerHTML =
                `<div class="col-span-full py-12 text-center text-red-400 text-sm">${esc(err.message)}</div>`;
        }
    }

    function getFloorDisplayLabel(floorId, floorNumber) {
        const floor = floorPlan.find(f => f.id === floorId);
        if (floor) return floor.label.replace('FLOOR #', 'Floor ');
        if (floorNumber) return `Floor ${floorNumber}`;
        return '';
    }

    function buildOrderReceiptData(orderNumber, cartItems, totalAmount) {
        const roomNum = document.getElementById('room-number').value;
        const tableNum = document.getElementById('table-number').value;
        const floorId = document.getElementById('floor-id').value;
        const floorNumber = document.getElementById('floor-number').value;
        const room = roomNum ? allRooms.find(r => r.roomNumber === roomNum) : null;
        const distribution = document.getElementById('distribution-select').value.trim() || null;
        const batchNumber = document.getElementById('batch-number').value.trim() || null;
        const isRoomOrder = !!roomNum;
        const isBuyAndGo = !isRoomOrder && tableNum === 'Buy&Go';

        return {
            orderNumber,
            roomNumber: roomNum || null,
            guestName: room?.guestName || null,
            tableNumber: isRoomOrder ? null : tableNum,
            isBuyAndGo,
            floorLabel: isRoomOrder
                ? null
                : (isBuyAndGo ? null : getFloorDisplayLabel(floorId, floorNumber) || null),
            distribution,
            batchNumber,
            items: cartItems,
            totalAmount
        };
    }

    function receiptRow(label, value) {
        if (!value) return '';
        return `<div class="receipt-row"><span>${esc(label)}</span><span>${esc(value)}</span></div>`;
    }

    function renderSingleReceipt(order, copyTitle) {
        const dateStr = new Date().toLocaleString();
        let itemsHtml = order.items.map(i => `
            <tr>
                <td style="width: 50%">${esc(i.name)}</td>
                <td style="width: 20%; text-align: center">${i.quantity}</td>
                <td style="width: 30%; text-align: right">${Number(i.price * i.quantity).toLocaleString()}</td>
            </tr>
        `).join('');

        const metaRows = [
            receiptRow('Order #:', String(order.orderNumber)),
            receiptRow('Date:', dateStr),
            order.roomNumber ? receiptRow('Room:', `Room ${order.roomNumber}`) : '',
            order.guestName ? receiptRow('Guest:', order.guestName) : '',
            !order.roomNumber && order.isBuyAndGo ? receiptRow('Service:', 'Buy & Go') : '',
            !order.roomNumber && !order.isBuyAndGo && order.tableNumber
                ? receiptRow('Table:', order.tableNumber) : '',
            order.floorLabel ? receiptRow('Floor:', order.floorLabel) : '',
            order.distribution ? receiptRow('Distribution:', order.distribution) : '',
            order.batchNumber ? receiptRow('Batch #:', order.batchNumber) : '',
        ].join('');

        return `
            <div class="receipt-instance">
                <div class="receipt-header">
                    <div class="receipt-copy-label">${esc(copyTitle)}</div>
                    <div class="receipt-title uppercase">${esc(appName)}</div>
                    <p class="receipt-tagline">Hotel Management System</p>
                </div>
                <div class="receipt-divider"></div>
                ${metaRows}
                <div class="receipt-divider"></div>
                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th style="width: 50%">Item</th>
                            <th style="width: 20%; text-align: center">Qty</th>
                            <th style="width: 30%; text-align: right">Total</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="receipt-row receipt-total">
                    <span>TOTAL:</span>
                    <span>${Number(order.totalAmount).toLocaleString()} ETB</span>
                </div>
                <div class="receipt-divider"></div>
                <div class="receipt-footer">
                    <p>THANK YOU!</p>
                    <p>Please visit us again</p>
                </div>
            </div>
        `;
    }

    function printReceipt(order) {
        const receipt = document.getElementById('receipt-print');
        receipt.innerHTML = renderSingleReceipt(order, 'RECEIPT');

        window.print();
        setTimeout(() => {
            window.print();
        }, 500);
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
        let list = allItems;
        if (activeTab !== 'all') {
            list = list.filter(i => i.mainCategory === activeTab);
        }
        if (selectedCategory) list = list.filter(i => i.category === selectedCategory);
        if (nameQ) list = list.filter(i => (i.name || '').toLowerCase().includes(nameQ) || (i.category || '').toLowerCase().includes(nameQ));
        if (idQ) list = list.filter(i => (i.menuId || '').toString().includes(idQ));
        return list;
    }

    function getOrderDestination() {
        const room = document.getElementById('room-number').value;
        if (room) return `Room ${room}`;
        return document.getElementById('table-number').value;
    }

    function clearRoomSelection() {
        document.getElementById('room-number').value = '';
        document.getElementById('room-id').value = '';
        document.getElementById('room-picker-label').textContent = 'Checked-in Room';
    }

    function initTablePicker() {
        renderFloorTabs();
        renderTableGrid();
    }

    function initRoomPicker() {
        renderRoomGrid();
    }

    function getActiveFloor() {
        return floorPlan.find(f => f.id === activeFloorId) || floorPlan[0] || null;
    }

    function getUniversalTables() {
        const seen = new Map();
        floorPlan.forEach(floor => {
            (floor.tables || []).forEach(t => {
                if (!seen.has(String(t.tableNumber))) {
                    seen.set(String(t.tableNumber), t);
                }
            });
        });
        return [...seen.values()].sort((a, b) =>
            String(a.tableNumber).localeCompare(String(b.tableNumber), undefined, { numeric: true, sensitivity: 'base' })
        );
    }

    function renderPickerFloorTabs(containerId) {
        const el = document.getElementById(containerId);
        if (!el || !floorPlan.length) return;
        el.innerHTML = floorPlan.map(f => {
            const on = f.id === activeFloorId;
            return `<button type="button" data-floor="${esc(f.id)}"
                class="picker-floor-tab px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all ${on ? 'bg-[#151515] text-white border-gray-600 shadow-lg' : 'bg-gray-900/40 border-gray-800/50 text-gray-500 hover:text-gray-300'}">${esc(f.label)}</button>`;
        }).join('');
    }

    function renderFloorTabs() {
        renderPickerFloorTabs('floor-tabs');
    }

    function getUniversalRooms() {
        return [...allRooms].sort((a, b) =>
            String(a.roomNumber).localeCompare(String(b.roomNumber), undefined, { numeric: true, sensitivity: 'base' })
        );
    }

    function renderRoomGrid() {
        const grid = document.getElementById('room-grid');
        if (!grid) return;
        const selectedRoom = document.getElementById('room-number').value;
        const rooms = getUniversalRooms();

        if (!rooms.length) {
            grid.innerHTML = '<p class="text-center text-xs text-gray-600 py-12 uppercase tracking-widest font-black">No checked-in guests</p>';
            return;
        }

        grid.innerHTML = `<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                ${rooms.map(r => {
                    const on = selectedRoom === r.roomNumber;
                    const stay = r.checkIn && r.checkOut
                        ? `${r.checkIn.slice(0, 10)} → ${r.checkOut.slice(0, 10)}`
                        : '';
                    return `<button type="button" data-room-id="${esc(r.id)}" data-room-num="${esc(r.roomNumber)}"
                        class="room-pick min-h-[5.5rem] w-full flex flex-col items-center justify-center rounded-2xl text-sm font-black border transition-all px-2 py-3 ${on ? 'bg-amber-500/10 text-amber-400 border-amber-500/40 shadow-[0_0_20px_rgba(245,158,11,0.15)]' : 'bg-[#111] border-gray-800 text-gray-300 hover:border-gray-500 hover:text-white hover:bg-gray-900/50'}">
                        <span class="text-base">${esc('Room ' + r.roomNumber)}</span>
                        <span class="text-[9px] text-emerald-400 mt-1 uppercase tracking-wider truncate max-w-full">${esc(r.guestName || 'Guest')}</span>
                        ${stay ? `<span class="text-[8px] text-gray-500 mt-1">${esc(stay)}</span>` : ''}
                    </button>`;
                }).join('')}
            </div>`;
    }

    function setRoomSelection(room) {
        if (!room) return;
        document.getElementById('room-number').value = room.roomNumber;
        document.getElementById('room-id').value = room.id;
        document.getElementById('table-number').value = 'Buy&Go';
        document.getElementById('table-picker-label').textContent = 'Buy & Go';
        if (room.floorId) syncFloorSelection(room.floorId);
        const guestLabel = room.guestName ? ` · ${room.guestName}` : '';
        document.getElementById('room-picker-label').textContent = `Room ${room.roomNumber}${guestLabel}`;
        closeRoomModal();
    }

    function openRoomModal() {
        const savedFloorId = getSavedFloorId();
        if (savedFloorId && floorPlan.find(f => f.id === savedFloorId)) {
            activeFloorId = savedFloorId;
        } else if (!activeFloorId) {
            activeFloorId = resolveFloorId();
        }
        document.getElementById('room-modal').classList.remove('hidden');
        renderRoomGrid();
        lucide.createIcons();
    }

    function closeRoomModal() {
        document.getElementById('room-modal').classList.add('hidden');
    }

    function renderTableGrid() {
        const grid = document.getElementById('table-grid');
        const selectedNum = document.getElementById('table-number').value;
        const tables = getUniversalTables();
        const activeFloor = getActiveFloor();

        if (!tables.length) {
            grid.innerHTML = '<p class="text-center text-xs text-gray-600 py-12 uppercase tracking-widest font-black">No tables found</p>';
            return;
        }

        const floorHint = activeFloor
            ? `<p class="text-[10px] font-black text-amber-500/80 uppercase tracking-[0.25em] mb-4 px-1">Serving ${esc(activeFloor.label)} · all tables available</p>`
            : '';

        grid.innerHTML = `${floorHint}
            <div class="grid grid-cols-4 gap-3">
                ${tables.map(t => {
                    const on = selectedNum === t.tableNumber;
                    const label = String(t.tableNumber).startsWith('T#') ? t.tableNumber : 'T#' + t.tableNumber;
                    return `<button type="button" data-table="${esc(t.tableNumber)}"
                        class="table-pick h-14 w-full flex items-center justify-center rounded-2xl text-sm font-black border transition-all ${on ? 'bg-amber-500/10 text-amber-400 border-amber-500/40 shadow-[0_0_20px_rgba(245,158,11,0.15)]' : 'bg-[#111] border-gray-800 text-gray-300 hover:border-gray-500 hover:text-white hover:bg-gray-900/50'}">${esc(label)}</button>`;
                }).join('')}
            </div>`;
    }

    function setTableSelection(tableNumber, floorId, floorNumber, label) {
        clearRoomSelection();
        document.getElementById('table-number').value = tableNumber;
        if (floorId) {
            syncFloorSelection(floorId);
        } else {
            document.getElementById('floor-id').value = '';
            document.getElementById('floor-number').value = '';
        }
        document.getElementById('table-picker-label').textContent = label;
        closeTableModal();
    }

    function updateFloorInputs() {
        syncFloorSelection(document.getElementById('floor-select').value);
    }

    function openTableModal() {
        const savedFloorId = getSavedFloorId();
        if (savedFloorId && floorPlan.find(f => f.id === savedFloorId)) {
            activeFloorId = savedFloorId;
        } else if (!activeFloorId) {
            activeFloorId = resolveFloorId();
        }
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
        const itemsToUse = activeTab === 'all' ? allItems : allItems.filter(i => i.mainCategory === activeTab);
        const cats = [...new Set(itemsToUse.map(i => i.category).filter(Boolean))].sort();
        const genBtn = (label, cat, active) => `
            <button type="button" data-cat="${esc(cat)}" 
                    class="cat-chip shrink-0 px-5 py-2.5 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all ${active ? 'bg-amber-500 text-black border-amber-500' : 'bg-gray-900/40 border-gray-800 text-gray-500 hover:text-gray-300'}">
                ${esc(label)}
            </button>`;
            
        bar.innerHTML = genBtn('All Items', '', !selectedCategory) +
            cats.map(c => genBtn(c, c, selectedCategory === c)).join('');
    }

    function renderMainTabs() {
        const mainContainer = document.getElementById('main-tabs-container');
        const cartContainer = document.getElementById('cart-tabs-container');
        
        // Detect all active main categories from allItems
        const cats = [...new Set(allItems.map(i => i.mainCategory || 'Food'))].sort();
        mainCategories = cats;

        // Set default active tab
        if (!activeTab || (activeTab !== 'all' && !cats.includes(activeTab))) {
            activeTab = cats.includes('Food') ? 'Food' : (cats[0] || 'all');
        }
        
        const allCats = ['all', ...cats];

        if (mainContainer) {
            mainContainer.innerHTML = allCats.map(tab => {
                const on = activeTab === tab;
                const count = tab === 'all' ? allItems.length : allItems.filter(i => i.mainCategory === tab).length;
                const cls = on ? 'bg-white/10 text-white border-gray-600' : 'bg-gray-900/20 border-gray-800/50 text-gray-600 hover:text-gray-400';
                
                let icon = 'layers';
                if (tab === 'Food') icon = 'utensils-crossed';
                else if (tab === 'Drinks') icon = 'wine';
                else if (tab === 'all') icon = 'layout-grid';

                const label = tab === 'all' ? 'All' : tab;
                
                return `
                    <button type="button" onclick="activeTab='${tab}';selectedCategory='';renderAll()" 
                        class="main-cat-tab flex-1 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border transition-all ${cls}">
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="${icon}" class="w-3.5 h-3.5"></i>${esc(label)}
                        </span> 
                        <span class="opacity-30 ml-1">(${count})</span>
                    </button>
                `;
            }).join('');
        }

        if (cartContainer) {
            cartContainer.innerHTML = allCats.map(tab => {
                const on = activeTab === tab;
                const cls = on ? 'bg-gray-900 text-white border-gray-700 shadow-lg' : 'bg-[#1a1c1b]/30 border-gray-800/40 text-gray-600 hover:text-gray-400';
                let icon = 'layers';
                if (tab === 'Food') icon = 'utensils-crossed';
                else if (tab === 'Drinks') icon = 'wine';
                else if (tab === 'all') icon = 'layout-grid';

                let label = tab.toUpperCase();
                if (tab === 'Food') label = 'BUTCHER';
                else if (tab === 'Drinks') label = 'DRINKS';
                else if (tab === 'all') label = 'ALL ITEMS';
                
                return `
                    <button type="button" onclick="activeTab='${tab}';selectedCategory='';renderAll()" 
                        class="cart-cat-tab flex-1 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all flex items-center justify-center gap-2 ${cls}">
                        <i data-lucide="${icon}" class="w-3.5 h-3.5"></i> ${label}
                    </button>
                `;
            }).join('');
        }
        
        lucide.createIcons();
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
        lucide.createIcons();
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
                    tableNumber: getOrderDestination(),
                    roomNumber: document.getElementById('room-number').value || null,
                    guestName: (() => {
                        const rn = document.getElementById('room-number').value;
                        if (!rn) return null;
                        const room = allRooms.find(r => r.roomNumber === rn);
                        return room?.guestName || null;
                    })(),
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
                const totalAmount = cart.reduce((a, i) => a + i.price * i.quantity, 0);
                const orderData = buildOrderReceiptData(result.orderNumber, [...cart], totalAmount);

                cart = [];
                document.getElementById('batch-number').value = '';
                clearRoomSelection();
                selectDist('', 'All Distributions');
                setTableSelection('Buy&Go', '', '', 'Select Table');
                renderCart();

                setTimeout(() => {
                    if (!enablePrinting) return;
                    const receipt = document.getElementById('receipt-print');
                    if (receipt.parentElement !== document.body) {
                        document.body.appendChild(receipt);
                    }
                    printReceipt(orderData);
                }, 150);
            } else alert('Error: ' + (result.message || 'Failed'));
        } catch { alert('Server error.'); }
        finally { btn.disabled = !cart.length; btn.innerHTML = old; }
    }


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
        renderFloorTabs();
        renderTableGrid();
    };

    document.getElementById('table-picker-btn').onclick = openTableModal;
    document.getElementById('room-picker-btn').onclick = openRoomModal;
    document.getElementById('room-modal-close').onclick = closeRoomModal;
    document.getElementById('room-modal').addEventListener('click', e => {
        if (e.target.id === 'room-modal') closeRoomModal();
    });
    document.getElementById('clear-room-pick').onclick = () => {
        clearRoomSelection();
        closeRoomModal();
    };
    document.getElementById('table-modal-close').onclick = closeTableModal;
    document.getElementById('table-modal').addEventListener('click', e => {
        if (e.target.id === 'table-modal') closeTableModal();
    });
    document.getElementById('pick-buy-go').onclick = () => setTableSelection('Buy&Go', '', '', 'Buy & Go');

    document.addEventListener('click', e => {
        const tab = e.target.closest('.picker-floor-tab');
        if (!tab) return;
        syncFloorSelection(tab.dataset.floor);
        renderFloorTabs();
        renderTableGrid();
    });

    document.getElementById('table-grid').addEventListener('click', e => {
        const btn = e.target.closest('.table-pick');
        if (!btn) return;
        const floor = getActiveFloor();
        const tableNum = btn.dataset.table;
        const floorLabel = floor ? floor.label.replace('FLOOR #', 'Floor ') : '';
        const label = tableNum + (floorLabel ? ' · ' + floorLabel : '');
        setTableSelection(tableNum, floor?.id || '', floor?.floorNumber || '', label);
    });

    document.getElementById('room-grid').addEventListener('click', e => {
        const btn = e.target.closest('.room-pick');
        if (!btn) return;
        const room = allRooms.find(r => r.id === btn.dataset.roomId);
        if (room) setRoomSelection(room);
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

    .custom-h-scrollbar::-webkit-scrollbar { height: 8px; }
    .custom-h-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.3); border-radius: 10px; }
    .custom-h-scrollbar::-webkit-scrollbar-thumb { background: #c5a059; border-radius: 10px; border: 2px solid #0a0a0a; }
    .custom-h-scrollbar::-webkit-scrollbar-thumb:hover { background: #d4af37; }

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
        #receipt-print, #receipt-print * { visibility: visible !important; color: black !important; font-weight: bold !important; }
        .receipt-header { text-align: center; margin-bottom: 10px; }
        .receipt-copy-label { font-size: 10px; font-weight: bold; border: 1px solid black; display: inline-block; padding: 2px 6px; margin-bottom: 5px; }
        .receipt-title { font-size: 20px; text-transform: uppercase; margin: 5px 0; }
        .receipt-tagline { font-size: 11px; margin-bottom: 10px; }
        .receipt-divider { border-bottom: 2px dashed black; margin: 10px 0; }
        .receipt-row { display: flex !important; justify-content: space-between; margin-bottom: 4px; }
        .receipt-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .receipt-table th { text-align: left; border-bottom: 2px solid black; padding: 5px 0; font-size: 12px; }
        .receipt-table td { padding: 5px 0; vertical-align: top; border-bottom: 1px dashed #ccc; }
        .receipt-total { font-size: 16px; margin-top: 10px; border-top: 2px dashed black; padding-top: 10px; }
        .receipt-footer { text-align: center; margin-top: 20px; font-size: 11px; }
        .receipt-spacer { height: 40mm; border-top: 1px dashed #000; margin: 20mm 0; position: relative; }
        .receipt-spacer::after { content: "CUT HERE"; position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: white; padding: 0 10px; font-size: 10px; }
    }
</style>

<!-- Receipt Template -->
<div id="receipt-print"></div>

<?php renderFooter(); ?>
