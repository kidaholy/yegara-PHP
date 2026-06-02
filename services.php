<?php
require_once 'includes/layout.php';
requireAuth(['admin']);
$title = "Services Hub";
renderHeader($title);
?>

<style>
    @keyframes slideInUp { from { transform: translateY(18px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .tab-content-anim { animation: slideInUp .45s cubic-bezier(.4,0,.2,1) both; }
    .gold-pill { background: linear-gradient(135deg, #d4af37 0%, #f3cf7a 100%); }

    /* Tab active state */
    .services-tab-btn { transition: all .25s; }
    .services-tab-btn.active-tab { color: #fff; background: rgba(212,175,55,.12); border-color: rgba(212,175,55,.35); }
    .services-tab-btn:not(.active-tab) { color: #6b7280; border-color: transparent; }
    .services-tab-btn:not(.active-tab):hover { color: #fff; background: rgba(255,255,255,.04); }
</style>

<div class="flex-1 flex flex-col h-screen overflow-hidden">
    <!-- Header / Tab Bar -->
    <header class="px-8 pt-8 pb-0 border-b border-white/5 bg-[#111413]/60 backdrop-blur-xl shrink-0">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-0">
            <div>
                <h1 class="text-3xl font-black font-playfair italic text-white tracking-tight gold-glow">Services Hub</h1>
                <p class="text-[9px] uppercase font-black tracking-[0.4em] text-[#d4af37]/40 mt-0.5">Operations &amp; Logistics</p>
            </div>
            <!-- Tabs -->
            <nav class="flex items-end gap-1 overflow-x-auto pb-0">
                <?php
                $tabs = [
                    ['key'=>'rooms',        'label'=>'Rooms',         'icon'=>'building'],
                    ['key'=>'menu-standard','label'=>'Standard Menu', 'icon'=>'utensils'],
                    ['key'=>'vip',          'label'=>'VIP Menus',     'icon'=>'crown'],
                    ['key'=>'reception',    'label'=>'Reception',     'icon'=>'bell'],
                    ['key'=>'room-orders',  'label'=>'Room Orders',   'icon'=>'shopping-basket'],
                ];
                foreach ($tabs as $t): ?>
                <button onclick="AdminServices.setTab('<?php echo $t['key']; ?>')"
                    data-tab="<?php echo $t['key']; ?>"
                    class="services-tab-btn flex items-center gap-2 px-5 py-3 rounded-t-xl border border-b-0 text-[10px] font-black uppercase tracking-widest whitespace-nowrap relative">
                    <i data-lucide="<?php echo $t['icon']; ?>" class="w-3.5 h-3.5"></i>
                    <?php echo $t['label']; ?>
                    <?php if ($t['key'] === 'room-orders'): ?>
                    <span id="tab-badge-orders" class="hidden w-4 h-4 bg-red-500 text-[8px] font-black text-white rounded-full flex items-center justify-center border-2 border-[#111413]">0</span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <!-- Content -->
    <div id="services-content-panel" class="flex-1 overflow-y-auto p-8 bg-[#0f1110]">
        <!-- Populated by admin-services.js -->
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ MODALS ═══ -->

<!-- Room Modal -->
<div id="room-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-6">
    <div class="glass w-full max-w-2xl rounded-[3rem] p-12 border border-white/10 animate-in">
        <h2 id="room-modal-title" class="text-3xl font-black text-white italic font-playfair mb-10 gold-glow">Add New Room</h2>
        <form onsubmit="AdminServices._saveRoom(event)" class="space-y-6 text-white">
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
            <div class="space-y-1.5">
                <label class="lbl">Room Service Menu Tier</label>
                <div class="flex gap-4">
                    <?php foreach (['standard','vip1','vip2'] as $tier): ?>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="room-tier" value="<?php echo $tier; ?>" <?php echo $tier==='standard'?'checked':''; ?> class="accent-[#d4af37]">
                        <span class="text-xs font-bold uppercase"><?php echo strtoupper($tier); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="document.getElementById('room-modal').classList.add('hidden')" class="flex-1 py-4 text-[10px] font-black uppercase text-white/30 hover:text-white">Cancel</button>
                <button type="submit" class="flex-1 gold-pill py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-black shadow-xl">Save Room</button>
            </div>
        </form>
    </div>
</div>

<!-- Menu Item Modal -->
<div id="menu-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-6 overflow-y-auto">
    <div class="glass w-full max-w-4xl rounded-[3rem] p-12 border border-white/10 my-auto animate-in text-white">
        <h2 id="menu-modal-title" class="text-3xl font-black italic font-playfair mb-10 gold-glow">Add Menu Item</h2>
        <form onsubmit="AdminServices._saveMenuItem(event)" class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <input type="hidden" id="menu-item-id">
            <div class="space-y-6">
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
            </div>
            <div class="space-y-6">
                <div class="space-y-1.5">
                    <label class="lbl">Item Image</label>
                    <div id="image-preview-area" class="h-44 rounded-2xl bg-black/40 border-2 border-dashed border-white/8 flex flex-col items-center justify-center cursor-pointer overflow-hidden group hover:border-[#d4af37]/30 transition-colors">
                        <i data-lucide="camera" class="w-8 h-8 text-white/20 group-hover:text-[#d4af37]/40 transition-colors mb-2"></i>
                        <span class="text-[9px] font-black uppercase text-white/20 group-hover:text-[#d4af37]/40">Click to upload</span>
                        <img id="menu-img-preview" class="hidden absolute inset-0 w-full h-full object-cover rounded-2xl">
                    </div>
                    <input type="file" id="menu-img-upload" hidden accept="image/*">
                    <input type="hidden" id="menu-img-base64">
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="document.getElementById('menu-modal').classList.add('hidden')" class="flex-1 py-4 text-[10px] font-black uppercase text-white/30 hover:text-white">Cancel</button>
                    <button type="submit" class="flex-1 gold-pill py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-black shadow-xl">Publish</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reception Detail Modal -->
<div id="rec-detail-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-6">
    <div class="glass w-full max-w-2xl rounded-[3rem] p-12 border border-white/10 overflow-y-auto max-h-[90vh]">
        <input type="hidden" id="rec-detail-id-hidden">
        <div class="flex justify-between items-start mb-8">
            <div>
                <h3 id="rec-detail-name" class="text-2xl font-black text-white italic font-playfair gold-glow">Guest Name</h3>
                <span id="rec-detail-status" class="text-[9px] font-black uppercase tracking-widest text-gray-500"></span>
            </div>
            <button onclick="document.getElementById('rec-detail-modal').classList.add('hidden')" class="text-gray-500 hover:text-white">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div id="rec-detail-body" class="text-white space-y-4"></div>
    </div>
</div>

<!-- QR Modal -->
<div id="qr-modal" class="hidden fixed inset-0 z-[110] bg-black/90 backdrop-blur-xl flex items-center justify-center p-6 text-white text-center">
    <div class="glass p-12 rounded-[3.5rem] border border-white/10 space-y-8 max-w-sm mx-auto">
        <h3 id="qr-room-title" class="text-2xl font-black italic font-playfair uppercase text-white">Room QR Code</h3>
        <div id="qr-content" class="bg-white p-4 rounded-3xl mx-auto shadow-2xl w-[232px] h-[232px] flex items-center justify-center"></div>
        <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#d4af37]/50">Scan for Digital Room Service</p>
        <div class="flex gap-4">
            <button onclick="document.getElementById('qr-modal').classList.add('hidden')" class="flex-1 py-4 text-[10px] font-black uppercase text-white/30">Close</button>
            <button onclick="window.print()" class="flex-1 gold-pill py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-black">Print</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ SHARED CSS ═══ -->
<style>
    .lbl { display:block; font-size:9px; font-weight:900; text-transform:uppercase; letter-spacing:.15em; color:rgba(212,175,55,.5); margin-left:.375rem; margin-bottom:.25rem; }
    .inp { width:100%; background:rgba(0,0,0,.4); border:1px solid rgba(255,255,255,.05); border-radius:1rem; padding:.875rem 1.25rem; font-size:.875rem; color:#fff; outline:none; }
    .inp:focus { border-color:rgba(212,175,55,.3); }
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
        image: document.getElementById('menu-img-base64').value
    };
    await AdminServices.api(method, url, payload);
    document.getElementById('menu-modal').classList.add('hidden');
    if (mm) { await mm.loadData(); mm.render(); }
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
