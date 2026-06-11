<?php
require_once 'includes/layout.php';
requireAuth(['admin', 'reception', 'receptionist']);
$title = "Services Hub";
renderHeader($title);

// Load dynamic tiers for initial server-side render
$tiersDataPath = 'data/menuTiers.json';
$menuTiers = file_exists($tiersDataPath) ? json_decode(file_get_contents($tiersDataPath), true) : [];
?>

<style>
    @keyframes slideInUp { from { transform: translateY(18px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .tab-content-anim { animation: slideInUp .45s cubic-bezier(.4,0,.2,1) both; }

    /* Tab active state */
    .services-tab-btn { transition: all .25s; border-bottom-width: 2px; }
    .services-tab-btn.active-tab { color: #c5a059; border-bottom-color: #c5a059; }
    .services-tab-btn:not(.active-tab) { color: #9ca3af; border-bottom-color: transparent; }
    .services-tab-btn:not(.active-tab):hover { color: #f3f4f6; }
</style>

<div class="max-w-screen-2xl w-full flex flex-col h-[calc(100vh-theme(space.4))] overflow-hidden bg-[#0f1110] rounded-2xl mt-2 mb-2 lg:ml-2">
    <!-- Header / Tab Bar -->
    <header class="px-8 pt-8 pb-0 border-b border-gray-700/50 bg-gray-800/80 backdrop-blur-xl shrink-0">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-0">
            <div>
                <h1 class="text-2xl font-bold text-gray-200 tracking-tight">Services Hub</h1>
                <p class="text-xs uppercase font-semibold tracking-wider text-gray-500 mt-1">Operations & Logistics</p>
            </div>
            <!-- Tabs -->
            <nav class="flex items-end gap-2 overflow-x-auto pb-0">
                <?php
                $userRole = $_SESSION['role'] ?? 'guest';
                $isAdmin = $userRole === 'admin';
                
                $tabs = [];
                if ($isAdmin) {
                    $tabs = [
                        ['key'=>'rooms',        'label'=>'Rooms',         'icon'=>'building'],
                        ['key'=>'menu-standard','label'=>'Standard Menu', 'icon'=>'utensils'],
                        ['key'=>'vip',          'label'=>'VIP Menus',     'icon'=>'crown'],
                        ['key'=>'reception',    'label'=>'Reception',     'icon'=>'bell'],
                        ['key'=>'room-orders',  'label'=>'Room Orders',   'icon'=>'shopping-basket'],
                    ];
                } else {
                    $tabs = [
                        ['key'=>'reception',    'label'=>'Reception',     'icon'=>'bell'],
                        ['key'=>'room-orders',  'label'=>'Room Orders',   'icon'=>'shopping-basket'],
                    ];
                }
                $defaultTab = $tabs[0]['key'];
                
                foreach ($tabs as $t): ?>
                <button onclick="AdminServices.setTab('<?php echo $t['key']; ?>')"
                    data-tab="<?php echo $t['key']; ?>"
                    class="services-tab-btn flex items-center gap-2 px-4 py-3 text-xs font-bold uppercase tracking-wider whitespace-nowrap relative">
                    <i data-lucide="<?php echo $t['icon']; ?>" class="w-4 h-4"></i>
                    <?php echo $t['label']; ?>
                    <?php if ($t['key'] === 'room-orders'): ?>
                    <span id="tab-badge-orders" class="hidden w-5 h-5 bg-red-500 text-[10px] font-bold text-white rounded-full flex items-center justify-center border border-gray-900 shadow-sm ml-1">0</span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <!-- Content -->
    <div id="services-content-panel" class="flex-1 overflow-y-auto p-8">
        <!-- Populated by admin-services.js -->
    </div>
</div>

<script>
    window.INITIAL_TAB = "<?php echo $defaultTab; ?>";
</script>

<!-- ═══════════════════════════════════════════════════════ MODALS ═══ -->

<!-- Room Modal -->
<div id="room-modal" class="hidden fixed inset-0 z-[100] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-gray-800 w-full max-w-2xl rounded-2xl p-8 border border-gray-700 shadow-2xl animate-in">
        <h2 id="room-modal-title" class="text-xl font-bold text-gray-200 mb-6 border-b border-gray-700/50 pb-4">Add New Room</h2>
        <form onsubmit="AdminServices._saveRoom(event)" class="space-y-5 text-gray-300">
            <input type="hidden" id="room-id">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="lbl">Room Number *</label>
                    <input type="text" id="room-number" required class="inp" placeholder="e.g. 101">
                </div>
                <div class="space-y-1.5">
                    <label class="lbl">Floor *</label>
                    <select id="room-floor" required class="inp appearance-none">
                        <option value="">Select floor…</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="lbl">Room Type</label>
                    <select id="room-type" class="inp appearance-none">
                        <option value="standard">Standard</option>
                        <option value="deluxe">Deluxe</option>
                        <option value="suite">Suite</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="lbl">Category</label>
                    <input type="text" id="room-category" class="inp" placeholder="e.g. Deluxe Suite">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="lbl">Price per Night (Br) *</label>
                    <input type="number" id="room-price" required min="0" class="inp" placeholder="0">
                </div>
                <div class="space-y-1.5">
                    <label class="lbl">Status</label>
                    <select id="room-status" class="inp appearance-none">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="dirty">Dirty</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-700/50 mt-4">
                <button type="button" onclick="document.getElementById('room-modal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 transition-colors border border-gray-700 hover:border-gray-600">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 border border-[#c5a059] hover:bg-[#b59048] transition-colors shadow-sm">Save Room</button>
            </div>
        </form>
    </div>
</div>

<!-- Tier Modal -->
<div id="tier-modal" class="hidden fixed inset-0 z-[100] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-gray-800 w-full max-w-lg rounded-2xl p-8 border border-gray-700 shadow-2xl animate-in">
        <h2 id="tier-modal-title" class="text-xl font-bold text-gray-200 mb-6 border-b border-gray-700/50 pb-4">Create New VIP Tier</h2>
        <form onsubmit="AdminServices._saveTier(event)" class="space-y-5 text-gray-300">
            <input type="hidden" id="tier-id">
            <div class="space-y-1.5">
                <label class="lbl">Tier Name *</label>
                <input type="text" id="tier-name" required class="inp" placeholder="e.g. VVIP">
            </div>
            <div class="space-y-1.5">
                <label class="lbl">Price Increase Percentage (%) *</label>
                <input type="number" id="tier-percentage" required min="1" step="0.1" class="inp" placeholder="e.g. 15">
                <p class="text-[10px] uppercase font-bold tracking-wider text-gray-500 mt-1">This will clone the Standard Menu and increase all prices.</p>
            </div>
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-700/50 mt-4">
                <button type="button" onclick="document.getElementById('tier-modal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 transition-colors border border-gray-700 hover:border-gray-600">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 border border-[#c5a059] hover:bg-[#b59048] transition-colors shadow-sm">Save Tier</button>
            </div>
        </form>
    </div>
</div>

<!-- Menu Item Modal -->
<div id="menu-modal" class="hidden fixed inset-0 z-[100] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6 overflow-y-auto">
    <div class="bg-gray-800 w-full max-w-4xl rounded-2xl p-8 border border-gray-700 shadow-2xl my-auto animate-in text-gray-300">
        <h2 id="menu-modal-title" class="text-xl font-bold text-gray-200 mb-6 border-b border-gray-700/50 pb-4">Add Menu Item</h2>
        <form onsubmit="AdminServices._saveMenuItem(event)" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <input type="hidden" id="menu-item-id">
            <div class="space-y-5">
                <div class="space-y-1.5"><label class="lbl">Name *</label><input type="text" id="menu-name" required class="inp"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5"><label class="lbl">Price (Br) *</label><input type="number" id="menu-price" required class="inp"></div>
                    <div class="space-y-1.5">
                        <label class="lbl">Main Category</label>
                        <select id="menu-main-cat" class="inp appearance-none">
                            <option value="Food">Food</option>
                            <option value="Drinks">Drinks</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="lbl">Category</label>
                    <select id="menu-category" class="inp appearance-none"></select>
                </div>
                <div class="space-y-1.5"><label class="lbl">Description</label><textarea id="menu-desc" rows="3" class="inp resize-none"></textarea></div>

                <!-- NEW: Stock Linkage -->
                <div class="p-5 rounded-xl border border-gray-700 bg-gray-900 space-y-4 items-start">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Stock Linkage (Optional)</p>
                    <div class="grid grid-cols-2 gap-4 w-full">
                        <div class="space-y-1.5">
                            <label class="lbl">Link to Stock</label>
                            <select id="menu-stock-id" class="inp appearance-none text-sm">
                                <option value="">No linkage</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="lbl">Deduct per Sale</label>
                            <input type="number" id="menu-stock-consumption" step="0.01" class="inp" placeholder="1.0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-5">
                <!-- NEW: Reporting Config -->
                <div class="p-5 rounded-xl border border-gray-700 bg-gray-900 space-y-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Reporting Configuration</p>
                    <div class="grid grid-cols-2 gap-4 w-full">
                        <div class="space-y-1.5">
                            <label class="lbl">Report Unit</label>
                            <select id="menu-report-unit" class="inp appearance-none">
                                <option value="piece">piece</option>
                                <option value="kg">kg</option>
                                <option value="liter">liter</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="lbl">Amount per Sale</label>
                            <input type="number" id="menu-report-qty" step="0.01" class="inp" placeholder="1.0">
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="lbl">Item Image</label>
                    <div id="image-preview-area" class="h-44 rounded-xl bg-gray-900 border border-dashed border-gray-600 flex flex-col items-center justify-center cursor-pointer overflow-hidden group hover:border-[#c5a059] transition-colors">
                        <i data-lucide="camera" class="w-8 h-8 text-gray-500 group-hover:text-[#c5a059] transition-colors mb-2"></i>
                        <span class="text-xs font-bold uppercase text-gray-500 group-hover:text-[#c5a059]">Click to upload</span>
                        <img id="menu-img-preview" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl">
                    </div>
                    <input type="file" id="menu-img-upload" hidden accept="image/*">
                    <input type="hidden" id="menu-img-base64">
                </div>
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-700/50 mt-4">
                    <button type="button" onclick="document.getElementById('menu-modal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 transition-colors border border-gray-700 hover:border-gray-600">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 border border-[#c5a059] hover:bg-[#b59048] transition-colors shadow-sm">Publish</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reception Detail Modal -->
<div id="rec-detail-modal" class="hidden fixed inset-0 z-[100] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-gray-800 w-full max-w-2xl rounded-2xl p-8 border border-gray-700 shadow-2xl overflow-y-auto max-h-[90vh]">
        <input type="hidden" id="rec-detail-id-hidden">
        <div class="flex justify-between items-start mb-6 border-b border-gray-700/50 pb-4">
            <div>
                <h3 id="rec-detail-name" class="text-xl font-bold text-gray-200">Guest Name</h3>
                <span id="rec-detail-status" class="text-xs font-bold uppercase tracking-wider text-gray-500"></span>
            </div>
            <button onclick="document.getElementById('rec-detail-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-200">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="rec-detail-body" class="text-gray-300 space-y-4"></div>
    </div>
</div>

<!-- QR Modal -->
<div id="qr-modal" class="hidden fixed inset-0 z-[110] bg-gray-900/90 backdrop-blur-sm flex items-center justify-center p-6 text-gray-300 text-center">
    <div class="bg-gray-800 p-8 rounded-2xl border border-gray-700 shadow-2xl space-y-6 max-w-sm mx-auto">
        <h3 id="qr-room-title" class="text-xl font-bold uppercase text-gray-200">Room QR Code</h3>
        <div id="qr-content" class="bg-white p-4 rounded-xl mx-auto shadow-sm w-[232px] h-[232px] flex items-center justify-center"></div>
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Scan for Digital Room Service</p>
        <div class="flex gap-3 pt-4 border-t border-gray-700/50">
            <button onclick="document.getElementById('qr-modal').classList.add('hidden')" class="flex-1 py-3 text-sm font-bold bg-transparent border border-gray-600 rounded-lg text-gray-400 hover:text-white transition-colors">Close</button>
            <button onclick="window.print()" class="flex-1 py-3 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 transition-colors shadow-sm">Print</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ SHARED CSS ═══ -->
<style>
<style>
    .lbl { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; margin-bottom:0.375rem; }
    .inp { width:100%; background:rgb(31 41 55 / 0.5); border:1px solid rgb(55 65 81); border-radius:0.5rem; padding:0.625rem 0.875rem; font-size:0.875rem; color:#f3f4f6; outline:none; transition: all 0.2s; }
    .inp:focus { border-color:#c5a059; background:rgb(31 41 55); }
    #image-preview-area { position: relative; }
    #image-preview-area { position: relative; }
</style>

<!-- ═══════════════════════════════════════════════════════ SCRIPTS ═══ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="public/js/menu-manager.js"></script>
<script src="public/js/admin-services.js"></script>
<script>
// ── Room Modal Integration ───────────────────────────────────────────────────
AdminServices.openRoomModal = (room = null) => {
    const floorSel = document.getElementById('room-floor');
    floorSel.innerHTML = '<option value="">Select floor…</option>' +
        AdminServices.floors.map(f => `<option value="${f.id}"${room && room.floorId === f.id ? ' selected' : ''}>Floor ${f.floorNumber}</option>`).join('');

    if (room && room.id) {
        document.getElementById('room-modal-title').textContent = 'Edit Room ' + room.roomNumber;
        document.getElementById('room-id').value = room.id;
        document.getElementById('room-number').value = room.roomNumber || '';
        document.getElementById('room-type').value = room.type || 'standard';
        document.getElementById('room-category').value = room.category || '';
        document.getElementById('room-price').value = room.price || '';
        document.getElementById('room-status').value = room.status || 'available';
        document.querySelectorAll('[name="room-tier"]').forEach(el => el.checked = (el.value === (room.roomServiceMenuTier || 'standard')));
    } else {
        document.getElementById('room-modal-title').textContent = 'Add New Room';
        document.getElementById('room-id').value = '';
        ['room-number','room-category','room-price'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('room-type').value = 'standard';
        document.getElementById('room-status').value = 'available';
        document.querySelector('[name="room-tier"][value="standard"]').checked = true;
    }
    document.getElementById('room-modal').classList.remove('hidden');
};

AdminServices._saveRoom = async (e) => {
    e.preventDefault();
    const id = document.getElementById('room-id').value;
    const tier = document.querySelector('[name="room-tier"]:checked')?.value || 'standard';
    const payload = {
        roomNumber: document.getElementById('room-number').value,
        floorId: document.getElementById('room-floor').value,
        type: document.getElementById('room-type').value,
        category: document.getElementById('room-category').value,
        price: parseFloat(document.getElementById('room-price').value),
        status: document.getElementById('room-status').value,
        roomServiceMenuTier: tier
    };
    const method = id ? 'PUT' : 'POST';
    const url = id ? `api/admin/rooms.php?id=${id}` : 'api/admin/rooms.php';
    await AdminServices.api(method, url, payload);
    document.getElementById('room-modal').classList.add('hidden');
    AdminServices.fetchRoomsData();
};

// ── QR Modal ─────────────────────────────────────────────────────────────────
AdminServices.openQRModal = (roomNumber) => {
    document.getElementById('qr-content').innerHTML = '';
    new QRCode(document.getElementById('qr-content'), {
        text: `${location.origin}/room-service/${roomNumber}`,
        width: 200, height: 200, correctLevel: QRCode.CorrectLevel.H
    });
    document.getElementById('qr-room-title').textContent = `Room ${roomNumber} — QR Link`;
    document.getElementById('qr-modal').classList.remove('hidden');
};

// ── Menu Modal ───────────────────────────────────────────────────────────────
AdminServices.openMenuModal = (item = {}) => {
    const mm = AdminServices.menuManager;
    const catSel = document.getElementById('menu-category');
    catSel.innerHTML = (mm ? mm.state.categories : []).map(c => `<option value="${c.name}">${c.name}</option>`).join('');

    document.getElementById('menu-item-id').value = item.id || '';
    document.getElementById('menu-name').value = item.name || '';
    document.getElementById('menu-price').value = item.price || '';
    document.getElementById('menu-main-cat').value = item.mainCategory || 'Food';
    document.getElementById('menu-category').value = item.category || '';
    document.getElementById('menu-desc').value = item.description || '';
    document.getElementById('menu-img-base64').value = item.image || '';
    document.getElementById('menu-stock-id').value = item.stockItemId || '';
    document.getElementById('menu-stock-consumption').value = item.stockConsumption || '';
    document.getElementById('menu-report-unit').value = item.reportUnit || 'piece';
    document.getElementById('menu-report-qty').value = item.reportQuantity || '';

    // Load available stocks for the dropdown
    AdminServices.fetchActiveStocks();

    const prev = document.getElementById('menu-img-preview');
    if (item.image) { prev.src = item.image; prev.classList.remove('hidden'); }
    else prev.classList.add('hidden');

    document.getElementById('menu-modal-title').textContent = item.id ? 'Edit Item' : 'Add Menu Item';
    document.getElementById('menu-modal').classList.remove('hidden');
};

AdminServices._saveMenuItem = async (e) => {
    e.preventDefault();
    const id = document.getElementById('menu-item-id').value;
    const method = id ? 'PUT' : 'POST';
    const mm = AdminServices.menuManager;
    const url = id
        ? `api/admin/menu.php?id=${id}&collection=${mm?.config.collection || 'menuItems'}`
        : `api/admin/menu.php?collection=${mm?.config.collection || 'menuItems'}`;
    const payload = {
        name: document.getElementById('menu-name').value,
        price: parseFloat(document.getElementById('menu-price').value),
        mainCategory: document.getElementById('menu-main-cat').value,
        category: document.getElementById('menu-category').value,
        description: document.getElementById('menu-desc').value,
        image: document.getElementById('menu-img-base64').value,
        stockItemId: document.getElementById('menu-stock-id').value,
        stockConsumption: parseFloat(document.getElementById('menu-stock-consumption').value || 0),
        reportUnit: document.getElementById('menu-report-unit').value,
        reportQuantity: parseFloat(document.getElementById('menu-report-qty').value || 1)
    };
    await AdminServices.api(method, url, payload);
    document.getElementById('menu-modal').classList.add('hidden');
    if (mm) { await mm.loadData(); mm.render(); }
};

AdminServices.fetchActiveStocks = async () => {
    try {
        const stocks = await AdminServices.api('GET', 'api/stock.php?availableOnly=true');
        const sel = document.getElementById('menu-stock-id');
        const currentVal = sel.value;
        sel.innerHTML = '<option value="">No linkage</option>' + 
            stocks.map(s => `<option value="${s.id}">${s.name} (${s.quantity} ${s.unit})</option>`).join('');
        if (currentVal) sel.value = currentVal;
    } catch(e) {}
};

// ── Image upload (canvas compress) ───────────────────────────────────────────
document.getElementById('image-preview-area').addEventListener('click', () =>
    document.getElementById('menu-img-upload').click());
document.getElementById('menu-img-upload').addEventListener('change', (e) => {
    const file = e.target.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        const img = new Image();
        img.onload = () => {
            const MAX = 800;
            let w = img.width, h = img.height;
            if (w > h && w > MAX) { h = h * MAX/w; w = MAX; }
            else if (h > MAX) { w = w * MAX/h; h = MAX; }
            const c = document.createElement('canvas');
            c.width = w; c.height = h;
            c.getContext('2d').drawImage(img, 0, 0, w, h);
            const b64 = c.toDataURL('image/jpeg', 0.8);
            document.getElementById('menu-img-base64').value = b64;
            const prev = document.getElementById('menu-img-preview');
            prev.src = b64; prev.classList.remove('hidden');
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});
</script>

<?php renderFooter(); ?>
