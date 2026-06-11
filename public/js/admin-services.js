/**
 * Admin Services Hub Orchestrator v2
 * Full spec: Rooms + QR, Menu, VIP Landing, Reception (filters+audio), Room Orders
 */

const AdminServices = {
    // ─── STATE ─────────────────────────────────────────────────────────────────
    activeTab: 'menu-standard',

    // Rooms
    rooms: [], floors: [], cashiers: [],

    // Reception
    receptionSubView: 'status',   // status | check-in
    receptionRequests: [],
    receptionFilter: 'all',       // all | pending | checked-in | denied | checked-out
    receptionSearch: '',
    receptionDateFilter: 'all',   // all | today | week | year | custom
    receptionCustomDate: '',
    prevReceptionCount: 0,
    
    // VIP Tiers
    menuTiers: [],

    menuManager: null,
    pollingTimer: null,
    audio: new Audio('/notification.mp3'),

    // ─── INIT ──────────────────────────────────────────────────────────────────
    async init() {
        this.setTab(window.INITIAL_TAB || 'menu-standard');
        this.fetchQueueData();
        this.pollingTimer = setInterval(() => this.fetchQueueData(), 15000);
        lucide.createIcons();
    },

    // ─── POLLING ───────────────────────────────────────────────────────────────
    async fetchQueueData() {
        try {
            const recRes = await this.api('GET', 'api/reception-requests.php?limit=500');
            const recs = Array.isArray(recRes) ? recRes : (recRes.data || []);

            const pendingCount = recs.filter(r => this._isPending(r.status)).length;

            if (pendingCount > this.prevReceptionCount) {
                this._playAlert();
            }

            this.prevReceptionCount = pendingCount;
            this.receptionRequests = recs;

            if (this.activeTab === 'reception') this._renderReceptionContent();
        } catch (e) { console.warn('Poll error', e); }
    },

    _playAlert() {
        let n = 0;
        const t = () => { if (n++ < 5) { this.audio.play().catch(()=>{}); setTimeout(t, 1500); } };
        t();
    },



    // ─── TAB SWITCHING ─────────────────────────────────────────────────────────
    async setTab(tab) {
        this.activeTab = tab;
        document.querySelectorAll('.services-tab-btn').forEach(btn => {
            const on = btn.dataset.tab === tab;
            btn.classList.toggle('active-tab', on);
        });
        this._renderPanel();
        if (tab === 'menu-standard') this._initMenuManager();
        if (tab === 'rooms') this.fetchRoomsData();
        if (tab === 'vip') this.fetchTiersData();
        if (tab === 'reception') { this.fetchQueueData(); if (!this.floors.length) this.fetchRoomsData(); }
    },

    _renderPanel() {
        const panel = document.getElementById('services-content-panel');
        const map = {
            'rooms': () => this._buildRoomsHTML(),
            'menu-standard': () => `<div id="menu-manager-root"></div>`,
            'vip': () => this._buildVipHTML(),
            'reception': () => this._buildReceptionShellHTML()
        };
        panel.innerHTML = `<div class="tab-content-anim">${(map[this.activeTab] || (() => ''))()}</div>`;
        lucide.createIcons();
        if (this.activeTab === 'reception') this._renderReceptionContent();
    },

    // ─── TAB 1: ROOMS ──────────────────────────────────────────────────────────
    _buildRoomsHTML() {
        const floors = (this.floors || []).filter(f => !f.isDeleted);
        const floorsWithRooms = floors.filter(f => this.rooms.some(r => r.floorId === f.id));
        return `
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Sidebar -->
            <aside class="lg:col-span-3 space-y-4 lg:sticky lg:top-6 h-fit">
                <div class="bg-gray-800 p-5 rounded-xl border border-gray-700/50 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 flex items-center gap-2">
                        <i data-lucide="bed-double" class="w-4 h-4 text-[#c5a059]"></i>
                        Rooms · <span class="text-[#c5a059]">${this.rooms.length} Units</span>
                    </p>
                    <button onclick="AdminServices.openRoomModal()" class="w-full bg-[#c5a059] text-gray-900 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm hover:bg-[#b59048] transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add Room
                    </button>
                    <button onclick="AdminServices.fetchRoomsData()" class="w-full bg-gray-700 border border-gray-600 text-gray-400 hover:text-white py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition flex items-center justify-center gap-2">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh
                    </button>
                </div>
                <!-- Legend -->
                <div class="bg-gray-800 p-4 rounded-xl border border-gray-700/50 space-y-2.5">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Status</p>
                    <div class="space-y-2 text-xs">
                        <div class="flex gap-2 items-center"><div class="w-2 h-2 rounded-full bg-emerald-500"></div><span class="text-gray-400">Available</span></div>
                        <div class="flex gap-2 items-center"><div class="w-2 h-2 rounded-full bg-red-500"></div><span class="text-gray-400">Occupied</span></div>
                        <div class="flex gap-2 items-center"><div class="w-2 h-2 rounded-full bg-amber-500"></div><span class="text-gray-400">Maintenance / Dirty</span></div>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <main class="lg:col-span-9 space-y-8">
                ${floorsWithRooms.length === 0
                    ? '<div class="py-32 text-center text-gray-600 text-sm uppercase tracking-widest font-bold">No rooms added yet</div>'
                    : floorsWithRooms.map(f => {
                        const fr = this.rooms.filter(r => r.floorId === f.id);
                        const isVip = !!f.isVIP;
                        return `
                        <div>
                            <!-- Floor Header -->
                            <div class="flex items-center gap-4 mb-5 bg-gray-900/60 border border-gray-700/50 rounded-xl px-5 py-3">
                                <i data-lucide="monitor" class="w-5 h-5 text-[#c5a059] shrink-0"></i>
                                <h3 class="text-sm font-black uppercase tracking-widest text-gray-200">Floor ${f.floorNumber} ${f.name ? f.name : 'Rooms'}</h3>
                                ${isVip ? '<span class="text-[10px] font-bold uppercase tracking-wider bg-[#c5a059]/10 text-[#c5a059] border border-[#c5a059]/30 px-2.5 py-0.5 rounded-full">VIP</span>' : ''}
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 ml-1">${fr.length} Rooms</span>
                            </div>
                            <!-- Room Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                ${fr.map(r => this._roomCard(r)).join('')}
                            </div>
                        </div>`;
                    }).join('')}
            </main>
        </div>`;
    },

    _roomCard(r) {
        const statusLabel = r.status === 'available' ? 'Available' : r.status === 'occupied' ? 'Occupied' : r.status === 'maintenance' ? 'Maintenance' : (r.status || 'Unknown');
        const statusBadge = r.status === 'available'
            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
            : r.status === 'occupied'
            ? 'bg-red-500/10 text-red-400 border-red-500/20'
            : 'bg-amber-500/10 text-amber-400 border-amber-500/20';
        return `
        <div class="bg-[#0d0f0e] border border-gray-800 rounded-2xl p-5 flex items-center gap-4 group hover:border-[#c5a059]/30 transition-all relative overflow-hidden cursor-pointer">
            <!-- Bed icon box -->
            <div class="w-14 h-14 rounded-xl bg-gray-900 border border-gray-700/60 flex items-center justify-center text-gray-500 shrink-0 group-hover:border-[#c5a059]/20 group-hover:text-[#c5a059] transition-colors">
                <i data-lucide="bed-double" class="w-6 h-6"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <h4 class="text-sm font-black text-gray-200 tracking-wide">Room ${r.roomNumber}</h4>
                    <span class="text-[10px] font-bold uppercase tracking-wider border px-2 py-0.5 rounded-full shrink-0 ${statusBadge}">${statusLabel}</span>
                </div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-500 truncate">${r.category || r.type || 'Standard'}</p>
                <p class="text-sm font-bold text-[#c5a059] mt-2">${Number(r.price || 0).toLocaleString()} Br</p>
            </div>
            <!-- Hover actions -->
            <div class="absolute bottom-3 right-3 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all">
                <button onclick='AdminServices.openRoomModal(${JSON.stringify(r).replace(/'/g, "&#39;")})' class="w-7 h-7 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 hover:text-white flex items-center justify-center" title="Edit">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                </button>
                <button onclick="AdminServices.deleteRoom('${r.id}')" class="w-7 h-7 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 hover:text-red-400 flex items-center justify-center" title="Delete">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>`;
    },

    async fetchRoomsData() {
        try {
            const [rr, fr, ur, tr] = await Promise.all([
                this.api('GET', 'api/admin/rooms.php'),
                this.api('GET', 'api/admin/floors.php'),
                this.api('GET', 'api/users.php'),
                this.api('GET', 'api/admin/menu-tiers.php')
            ]);
            this.rooms = rr.data || [];
            this.floors = fr.data || [];
            this.cashiers = (ur.data || []).filter(u => ['admin','cashier'].includes(u.role));
            this.menuTiers = tr.data || [];
        } catch(e) { console.warn(e); }
        this._renderPanel();
    },

    async assignFloorCashier(floorId, cashierId) {
        await this.api('PUT', `api/admin/floors.php?id=${floorId}`, { roomServiceCashierId: cashierId || null });
    },

    async deleteRoom(id) {
        if (!confirm('Delete this room permanently?')) return;
        await this.api('DELETE', `api/admin/rooms.php?id=${id}`);
        this.rooms = this.rooms.filter(r => r.id !== id);
        this._renderPanel();
    },

    // ─── TAB 2: VIP LANDING ────────────────────────────────────────────────────
    
    async fetchTiersData() {
        try {
            const tr = await this.api('GET', 'api/admin/menu-tiers.php');
            this.menuTiers = tr.data || [];
        } catch(e) { console.warn(e); }
        this._renderPanel();
    },

    _buildVipHTML() {
        const tiers = this.menuTiers || [];
        return `
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <aside class="lg:col-span-3 space-y-4 lg:sticky lg:top-6 h-fit">
                <div class="bg-gray-800 p-5 rounded-xl border border-gray-700/50 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">VIP Tiers · ${tiers.length}</p>
                    <button onclick="AdminServices.openTierModal()" class="w-full bg-[#c5a059] text-gray-900 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm hover:bg-[#b59048] transition-colors">
                        + Create Tier
                    </button>
                    <button onclick="AdminServices.fetchTiersData()" class="w-full bg-gray-700 border border-gray-600 text-gray-400 hover:text-white py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition flex items-center justify-center gap-2">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh
                    </button>
                </div>
                <div class="bg-gray-800 p-4 rounded-xl border border-gray-700/50 space-y-2">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">How it works</p>
                    <p class="text-xs text-gray-400 leading-relaxed">Each tier clones the Standard Menu and applies your custom price increase. Cashier POS tabs are created automatically.</p>
                </div>
            </aside>
            <main class="lg:col-span-9 space-y-4">
                ${tiers.length === 0 ? '<div class="py-32 text-center text-gray-600 text-sm uppercase tracking-widest font-bold">No VIP tiers yet</div>' :
                tiers.map(t => `
                <div class="bg-gray-800 p-5 rounded-xl border border-gray-700/50 group hover:border-gray-600 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-12 h-12 rounded-xl bg-[#c5a059]/10 border border-[#c5a059]/20 flex items-center justify-center text-[#c5a059] shrink-0">
                                <i data-lucide="crown" class="w-6 h-6"></i>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-lg font-bold text-gray-200">${t.name}</h4>
                                <p class="text-xs text-gray-500 mt-1">+${t.percentage}% above Standard Menu</p>
                                <p class="text-[10px] text-gray-600 mt-1 font-mono truncate">${t.filePrefix}Menu.json</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="cashier.php?tier=${t.id}" class="px-4 py-2 rounded-lg border border-blue-500/30 text-blue-400 text-xs font-bold uppercase hover:bg-blue-500/10 transition-colors">Open POS</a>
                            <a href="vip-menu.php?tier=${t.id}" class="px-4 py-2 rounded-lg border border-[#c5a059]/40 text-[#c5a059] text-xs font-bold uppercase hover:bg-[#c5a059]/10 transition-colors">Manage Menu</a>
                            <button onclick="AdminServices.openTierModalById('${t.id}')" class="w-9 h-9 rounded-lg border border-gray-700 text-gray-400 hover:text-white hover:border-gray-600 transition-colors" title="Edit">
                                <i data-lucide="edit-3" class="w-4 h-4 mx-auto"></i>
                            </button>
                            <button onclick="AdminServices.deleteTier('${t.id}')" class="w-9 h-9 rounded-lg border border-gray-700 text-gray-400 hover:text-red-400 hover:border-red-500/30 transition-colors" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4 mx-auto"></i>
                            </button>
                        </div>
                    </div>
                </div>`).join('')}
            </main>
        </div>`;
    },

    openTierModalById(id) {
        const tier = (this.menuTiers || []).find(t => t.id === id) || null;
        this.openTierModal(tier);
    },

    openTierModal(tier = null) {
        document.getElementById('tier-id').value = tier?.id || '';
        document.getElementById('tier-name').value = tier?.name || '';
        document.getElementById('tier-percentage').value = tier?.percentage ?? '';
        document.getElementById('tier-modal-title').textContent = tier ? 'Edit VIP Tier' : 'Create New VIP Tier';
        document.getElementById('tier-modal').classList.remove('hidden');
    },

    async _saveTier(e) {
        e.preventDefault();
        const id = document.getElementById('tier-id').value;
        const payload = {
            name: document.getElementById('tier-name').value,
            percentage: parseFloat(document.getElementById('tier-percentage').value)
        };
        const res = id
            ? await this.api('PUT', `api/admin/menu-tiers.php?id=${encodeURIComponent(id)}`, payload)
            : await this.api('POST', 'api/admin/menu-tiers.php', payload);
        if (res.status === 'success') {
            document.getElementById('tier-modal').classList.add('hidden');
            this.fetchTiersData();
        } else {
            alert(res.message || 'Error saving tier');
        }
    },

    async deleteTier(id) {
        if (!confirm('Permanently delete this VIP tier and its associated menu? This cannot be undone.')) return;
        await this.api('DELETE', `api/admin/menu-tiers.php?id=${id}`);
        this.fetchTiersData();
    },

    // ─── TAB 3: RECEPTION ──────────────────────────────────────────────────────
    setReceptionSubView(v) {
        const isAdmin = window.USER_ROLE === 'admin';
        // Enforce role-based view restrictions
        if (isAdmin && v !== 'status') return;
        if (!isAdmin && v !== 'check-in') return;
        
        this.receptionSubView = v;
        this._renderPanel();
    },

    _buildReceptionShellHTML() {
        const isAdmin = window.USER_ROLE === 'admin';
        // Enforce role-based view default
        if (isAdmin && this.receptionSubView !== 'status') {
            this.receptionSubView = 'status';
        } else if (!isAdmin && this.receptionSubView !== 'check-in') {
            this.receptionSubView = 'check-in';
        }

        const isStatus = this.receptionSubView === 'status';
        
        return `
        <div class="space-y-6">
            <!-- Dynamic View Area -->
            <div id="reception-view-area">
                ${isStatus ? this._buildReceptionStatusHTML() : this._buildReceptionCheckInHTML()}
            </div>
        </div>`;
    },

    _buildReceptionStatusHTML() {
        return `
        <div class="space-y-6">
            <!-- Search & Filters Row -->
            <div class="flex flex-wrap gap-4 items-center bg-gray-800/40 p-2 rounded-xl border border-gray-700/30">
                <div class="relative flex-1 min-w-[280px]">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-gray-500"></i>
                    </div>
                    <input type="text" id="rec-search" placeholder="Search guests by name, phone, room or ID..."
                        oninput="AdminServices.receptionSearch = this.value; AdminServices._renderReceptionContent()"
                        class="w-full bg-gray-900/50 border border-transparent rounded-lg py-3 pl-11 pr-4 text-xs font-bold uppercase tracking-widest text-gray-200 outline-none focus:border-[#c5a059]/50 focus:bg-gray-800 transition-colors placeholder:text-gray-600">
                </div>
                
                <div class="flex items-center gap-2 pr-2" id="rec-date-pills">
                    ${['all','today','week','year'].map(d => `
                    <button onclick="AdminServices._setDateFilter('${d}')"
                        class="rec-date-pill text-xs font-bold uppercase tracking-wider px-4 py-2.5 rounded-lg transition-all ${d === 'all' ? 'bg-[#1a1c1a] text-[#c5a059] border border-[#c5a059]/30' : 'text-gray-500 hover:text-gray-300'}"
                        data-date="${d}">${d === 'all' ? 'All Time' : d.charAt(0).toUpperCase()+d.slice(1)}</button>
                    `).join('')}
                    <button class="text-xs font-bold uppercase tracking-wider px-4 py-2.5 rounded-lg text-gray-500 hover:text-gray-300 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4"></i> Pick Date
                    </button>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-[#121413] border border-[#1a1c1a] rounded-2xl p-6 text-center hover:border-[#c5a059]/20 transition-colors">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#c5a059]/70 mb-2">Total Revenue</p>
                    <h3 id="stat-revenue" class="text-3xl font-black font-playfair italic text-[#c5a059]">0</h3>
                    <p class="text-[10px] text-gray-500 mt-2 uppercase tracking-wider">ETB · All Time</p>
                </div>
                <div class="bg-[#121413] border border-[#1a1c1a] rounded-2xl p-6 text-center hover:border-gray-700 transition-colors">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#c5a059]/70 mb-2">Approved Guests</p>
                    <h3 id="stat-guests" class="text-3xl font-black font-playfair italic text-white">0</h3>
                    <p class="text-[10px] text-gray-500 mt-2 uppercase tracking-wider">Checked In / Active</p>
                </div>
                <div class="bg-[#121413] border border-[#1a1c1a] rounded-2xl p-6 text-center hover:border-gray-700 transition-colors">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#c5a059]/70 mb-2">Avg. Stay</p>
                    <h3 id="stat-stay" class="text-3xl font-black font-playfair italic text-white">—</h3>
                    <p class="text-[10px] text-gray-500 mt-2 uppercase tracking-wider">Nights per Guest</p>
                </div>
            </div>

            <!-- Status Pills -->
            <div class="flex items-center justify-between border-b border-gray-800 pb-4">
                <div class="flex gap-3 flex-wrap" id="rec-status-pills">
                    ${[
                        {key:'pending', label:'Checkin Waiting', icon:'clock'},
                        {key:'checked-in', label:'Checked In', icon:'check-square'},
                        {key:'denied', label:'Denied', icon:'x-circle'},
                        {key:'checked-out', label:'Checkout', icon:'log-out'},
                        {key:'all', label:'All', icon:'users'} // Changed label all to just All
                    ].map(s => `
                    <button onclick="AdminServices._setStatusFilter('${s.key}')"
                        class="rec-status-pill flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider px-4 py-2 rounded-lg border transition-all ${s.key === 'pending' ? 'bg-[#c5a059]/10 text-[#c5a059] border-[#c5a059]/30' : 'border-transparent text-gray-500 hover:text-gray-300'}"
                        data-status="${s.key}">
                        <i data-lucide="${s.icon}" class="w-3.5 h-3.5"></i>
                        ${s.label} (<span class="rec-count-${s.key}">0</span>)
                    </button>
                    `).join('')}
                </div>
                <button onclick="AdminServices.fetchQueueData()" class="text-gray-500 hover:text-[#c5a059] transition-colors p-2 shrink-0">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Cards Container -->
            <div id="rec-cards-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"></div>
        </div>`;
    },

    _buildReceptionCheckInHTML() {
        const activeFloors = (this.floors || []).filter(f => !f.isDeleted);
        return `
        <div class="bg-gray-800/40 border border-gray-700/50 rounded-2xl p-8 max-w-5xl mx-auto">
            <h3 class="text-sm font-bold uppercase tracking-widest text-[#c5a059] flex items-center gap-2 mb-8"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> New Check-In</h3>
            
            <!-- Hidden file inputs -->
            <input type="file" id="ci-photo-file" accept="image/*" class="sr-only" onchange="AdminServices._ciProfileFileChange(this)">
            <input type="file" id="ci-id-front-file" accept="image/*,application/pdf" class="sr-only" onchange="AdminServices._ciIdUpload(this,'front')">
            <input type="file" id="ci-id-back-file"  accept="image/*,application/pdf" class="sr-only" onchange="AdminServices._ciIdUpload(this,'back')">

            <form onsubmit="AdminServices.submitNewCheckIn(event)" class="space-y-8">
                <!-- Row 1: Core details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <div class="space-y-1.5 border-b border-gray-700/50 pb-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Guest Name *</label>
                            <input type="text" id="ci-name" required class="w-full bg-transparent border-none text-sm text-white focus:outline-none placeholder:text-gray-700" placeholder="Full name">
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-1.5 border-b border-gray-700/50 pb-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 flex items-center gap-1.5"><i data-lucide="credit-card" class="w-3 h-3"></i> Fayda ID (FAN)</label>
                                <input type="text" id="ci-fayda" class="w-full bg-transparent border-none text-sm text-white focus:outline-none placeholder:text-gray-700" placeholder="16-digit FAN number">
                            </div>
                            <div class="space-y-1.5 border-b border-gray-700/50 pb-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 flex items-center gap-1.5"><i data-lucide="phone" class="w-3 h-3"></i> Phone</label>
                                <input type="text" id="ci-phone" class="w-full bg-transparent border-none text-sm text-white focus:outline-none placeholder:text-gray-700" placeholder="+251 9XX XXX XXX">
                            </div>
                        </div>
                        
                        <div class="bg-gray-900/40 rounded-xl p-5 border border-gray-800 space-y-5 mt-6">
                            <h4 class="text-[10px] font-black uppercase tracking-widest text-[#c5a059] flex items-center gap-2"><i data-lucide="camera" class="w-3 h-3"></i> Guest Photos &amp; ID</h4>
                            
                            <!-- 1. Profile Photo -->
                            <div class="space-y-2">
                                <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">1. Guest Profile Photo (URL or File Upload)</label>
                                <div class="flex gap-2 items-center">
                                    <input type="text" id="ci-photo-url"
                                        oninput="AdminServices._ciProfileUrlChange(this.value)"
                                        class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-xs text-white focus:border-[#c5a059]/50 outline-none transition-colors"
                                        placeholder="https://example.com/photo.jpg or paste URL">
                                    <button type="button" onclick="document.getElementById('ci-photo-file').click()"
                                        class="shrink-0 px-4 py-2 border border-gray-600 rounded-lg text-xs font-bold uppercase tracking-wider text-gray-400 hover:text-white hover:border-gray-500 flex items-center gap-2 transition-colors">
                                        <i data-lucide="upload" class="w-3 h-3"></i> Upload File
                                    </button>
                                </div>
                                <!-- Profile Preview -->
                                <div id="ci-photo-preview" class="hidden mt-2">
                                    <div class="flex items-center gap-4 bg-gray-800 rounded-xl p-3 border border-gray-700">
                                        <img id="ci-photo-img" src="" alt="Profile" class="w-16 h-16 rounded-full object-cover border-2 border-[#c5a059]/40">
                                        <div>
                                            <p class="text-xs font-bold text-white">Profile Preview</p>
                                            <p class="text-[10px] text-gray-500 mt-0.5">This image will be saved as the guest profile.</p>
                                            <button type="button" onclick="AdminServices._ciClearProfile()" class="text-[10px] text-red-400 hover:text-red-300 mt-1">✕ Remove</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. ID Card Upload -->
                            <div class="space-y-2">
                                <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">2. ID Card Upload *</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Front -->
                                    <div>
                                        <div id="ci-id-front-btn" onclick="document.getElementById('ci-id-front-file').click()"
                                            class="h-24 border-2 border-dashed border-gray-700 rounded-xl flex flex-col items-center justify-center text-gray-500 hover:border-[#c5a059]/40 hover:text-[#c5a059] transition-colors cursor-pointer">
                                            <i data-lucide="upload" class="w-5 h-5 mb-1"></i>
                                            <span class="text-[9px] font-bold uppercase tracking-wider">Upload Front</span>
                                        </div>
                                        <div id="ci-id-front-preview" class="hidden mt-2 relative group rounded-xl overflow-hidden border border-gray-700">
                                            <img id="ci-id-front-img" src="" class="w-full h-28 object-cover">
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                                <button type="button" onclick="AdminServices._ciClearId('front')" class="text-white text-xs font-bold bg-red-600 px-3 py-1 rounded-lg">✕ Remove</button>
                                            </div>
                                            <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-[9px] text-[#c5a059] font-bold text-center py-1 uppercase tracking-wider">Front</div>
                                        </div>
                                    </div>
                                    <!-- Back -->
                                    <div>
                                        <div id="ci-id-back-btn" onclick="document.getElementById('ci-id-back-file').click()"
                                            class="h-24 border-2 border-dashed border-gray-700 rounded-xl flex flex-col items-center justify-center text-gray-500 hover:border-[#c5a059]/40 hover:text-[#c5a059] transition-colors cursor-pointer">
                                            <i data-lucide="upload" class="w-5 h-5 mb-1"></i>
                                            <span class="text-[9px] font-bold uppercase tracking-wider">Upload Back</span>
                                        </div>
                                        <div id="ci-id-back-preview" class="hidden mt-2 relative group rounded-xl overflow-hidden border border-gray-700">
                                            <img id="ci-id-back-img" src="" class="w-full h-28 object-cover">
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                                <button type="button" onclick="AdminServices._ciClearId('back')" class="text-white text-xs font-bold bg-red-600 px-3 py-1 rounded-lg">✕ Remove</button>
                                            </div>
                                            <div class="absolute bottom-0 left-0 right-0 bg-black/70 text-[9px] text-[#c5a059] font-bold text-center py-1 uppercase tracking-wider">Back</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-6 border-b border-gray-700/50 pb-2">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Floor</label>
                                <select id="ci-floor" onchange="AdminServices._ciFloorChange(this.value)" class="w-full bg-transparent border-none text-sm text-white focus:outline-none appearance-none">
                                    <option value="" class="bg-gray-800">Select Floor...</option>
                                    ${activeFloors.map(f => `<option value="${f.id}" class="bg-gray-800">Floor ${f.floorNumber}</option>`).join('')}
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Room *</label>
                                <select id="ci-room" class="w-full bg-transparent border-none text-sm text-white focus:outline-none appearance-none">
                                    <option value="" class="bg-gray-800">Select a floor first</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-1.5 border-b border-gray-700/50 pb-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Number of Guests</label>
                            <input type="number" id="ci-guests" min="1" value="1" class="w-full bg-transparent border-none text-sm text-white focus:outline-none">
                        </div>

                        <div class="space-y-1.5 border-b border-gray-700/50 pb-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Stay Duration (Days) *</label>
                            <input type="number" id="ci-duration" required min="1" class="w-full bg-transparent border-none text-sm text-white focus:outline-none placeholder:text-gray-700" placeholder="How many days?">
                        </div>

                        <div class="space-y-3 pt-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Payment Method</label>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                                ${['CASH', 'MOBILE BANKING', 'TELEBIRR', 'CHEQUE'].map((method, idx) => `
                                <label class="cursor-pointer">
                                    <input type="radio" name="ci-payment" value="${method}" ${idx===0?'checked':''} class="peer sr-only">
                                    <div class="text-[9px] font-bold tracking-widest uppercase text-center border border-gray-700 text-gray-400 rounded-lg py-3 hover:bg-gray-800 peer-checked:bg-[#c5a059]/10 peer-checked:border-[#c5a059]/50 peer-checked:text-[#c5a059] transition-all flex items-center justify-center gap-1.5">
                                        <i data-lucide="${method==='CASH'?'banknote':method==='MOBILE BANKING'?'smartphone':method==='TELEBIRR'?'phone-call':'credit-card'}" class="w-3 h-3"></i> ${method}
                                    </div>
                                </label>
                                `).join('')}
                            </div>
                        </div>

                        <!-- Receipt Number / PDF URL -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Receipt Number or URL</label>
                            <div class="border-b border-gray-700/50 pb-2">
                                <input type="text" id="ci-receipt"
                                    oninput="AdminServices._ciReceiptChange(this.value)"
                                    class="w-full bg-transparent border-none text-sm text-white focus:outline-none placeholder:text-gray-700"
                                    placeholder="Enter receipt number or paste PDF link">
                            </div>
                            <!-- PDF Embed Preview -->
                            <div id="ci-receipt-pdf-preview" class="hidden rounded-xl overflow-hidden border border-gray-700 mt-2">
                                <div class="bg-gray-900 px-4 py-2 flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#c5a059]">📄 Receipt PDF Preview</span>
                                    <button type="button" onclick="AdminServices._ciClearReceipt()" class="text-[10px] text-gray-400 hover:text-red-400">✕ Clear</button>
                                </div>
                                <iframe id="ci-receipt-iframe" src="" class="w-full h-64 bg-white" frameborder="0"></iframe>
                            </div>
                        </div>

                        <div class="space-y-1.5 border-b border-gray-700/50 pb-2 h-[88px]">
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400">Notes</label>
                            <textarea id="ci-notes" class="w-full bg-transparent border-none text-sm text-white focus:outline-none placeholder:text-gray-700 h-full resize-none" placeholder="Additional details or remarks..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#c5a059]/20">
                    <button type="submit" class="w-full bg-[#c5a059] text-gray-900 py-5 rounded-xl text-sm font-black uppercase tracking-widest hover:bg-[#b59048] transition-colors shadow-[0_4px_20px_rgba(197,160,89,0.3)] flex justify-center items-center gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>`;
    },

    // ── Profile Photo helpers ─────────────────────────────────────────────────
    _ciProfileUrlChange(url) {
        if (!url) { this._ciClearProfile(); return; }
        const img = document.getElementById('ci-photo-img');
        const preview = document.getElementById('ci-photo-preview');
        if (img && preview) { img.src = url; preview.classList.remove('hidden'); }
    },

    _ciProfileFileChange(input) {
        const file = input.files[0]; if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const urlBox = document.getElementById('ci-photo-url');
            if (urlBox) urlBox.value = e.target.result;
            this._ciProfileUrlChange(e.target.result);
        };
        reader.readAsDataURL(file);
    },

    _ciClearProfile() {
        const urlBox = document.getElementById('ci-photo-url');
        const preview = document.getElementById('ci-photo-preview');
        if (urlBox) urlBox.value = '';
        if (preview) preview.classList.add('hidden');
    },

    // ── ID Front/Back helpers ─────────────────────────────────────────────────
    _ciIdUpload(input, side) {
        const file = input.files[0]; if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById(`ci-id-${side}-img`);
            const btn = document.getElementById(`ci-id-${side}-btn`);
            const preview = document.getElementById(`ci-id-${side}-preview`);
            if (img) img.src = e.target.result;
            if (btn) btn.classList.add('hidden');
            if (preview) preview.classList.remove('hidden');
            // Store as base64 in hidden var
            AdminServices[`_ciId${side.charAt(0).toUpperCase()+side.slice(1)}B64`] = e.target.result;
        };
        reader.readAsDataURL(file);
    },

    _ciClearId(side) {
        const btn = document.getElementById(`ci-id-${side}-btn`);
        const preview = document.getElementById(`ci-id-${side}-preview`);
        const fileInput = document.getElementById(`ci-id-${side}-file`);
        if (btn) btn.classList.remove('hidden');
        if (preview) preview.classList.add('hidden');
        if (fileInput) fileInput.value = '';
        AdminServices[`_ciId${side.charAt(0).toUpperCase()+side.slice(1)}B64`] = null;
    },

    // ── Receipt PDF preview helper ────────────────────────────────────────────
    _ciReceiptChange(val) {
        const pdfPreview = document.getElementById('ci-receipt-pdf-preview');
        const iframe = document.getElementById('ci-receipt-iframe');
        const isPdf = val && (val.startsWith('http') || val.startsWith('data:application/pdf')) &&
                      (val.toLowerCase().includes('.pdf') || val.startsWith('data:application/pdf'));
        if (pdfPreview && iframe) {
            if (isPdf) {
                iframe.src = val;
                pdfPreview.classList.remove('hidden');
            } else {
                pdfPreview.classList.add('hidden');
                iframe.src = '';
            }
        }
    },

    _ciClearReceipt() {
        const receipt = document.getElementById('ci-receipt');
        if (receipt) receipt.value = '';
        this._ciReceiptChange('');
    },

    _ciFloorChange(floorId) {
        const select = document.getElementById('ci-room');
        if (!select) return;
        if (!floorId) {
            select.innerHTML = '<option value="" class="bg-gray-800">Select a floor first</option>';
            return;
        }
        const rmFilters = this.rooms.filter(r => r.floorId === floorId && r.status === 'available');
        if (rmFilters.length === 0) {
            select.innerHTML = '<option value="" class="bg-gray-800">No rooms available</option>';
        } else {
            select.innerHTML = rmFilters.map(r => `<option value="${r.roomNumber}" class="bg-gray-800">Room ${r.roomNumber} - ${r.category}</option>`).join('');
        }
    },

    async submitNewCheckIn(e) {
        e.preventDefault();
        const paymentMethod = document.querySelector('input[name="ci-payment"]:checked')?.value || 'CASH';
        const body = {
            guestName: document.getElementById('ci-name').value,
            phone: document.getElementById('ci-phone').value,
            faydaId: document.getElementById('ci-fayda').value,
            roomNumber: document.getElementById('ci-room')?.value || '',
            guests: parseInt(document.getElementById('ci-guests')?.value || 1),
            stayDuration: parseInt(document.getElementById('ci-duration')?.value || 1),
            paymentMethod,
            receiptNumber: document.getElementById('ci-receipt')?.value || '',
            notes: document.getElementById('ci-notes')?.value || '',
            profilePhoto: document.getElementById('ci-photo-url')?.value || '',
            idPhotoFront: AdminServices._ciIdFrontB64 || '',
            idPhotoBack: AdminServices._ciIdBackB64 || '',
            inquiryType: 'WALK_IN',
        };
        const res = await this.api('POST', 'api/reception-requests.php', body);
        if (res?.status === 'success') {
            this.fetchQueueData();
            this.setReceptionSubView('status');
        } else {
            alert(res?.message || 'Error submitting check-in');
        }
    },


    _setDateFilter(d) {
        this.receptionDateFilter = d;
        document.querySelectorAll('.rec-date-pill').forEach(b => {
            const on = b.dataset.date === d;
            b.classList.toggle('bg-[#c5a059]', on);
            b.classList.toggle('text-gray-900', on);
            b.classList.toggle('border-[#c5a059]', on);
            b.classList.toggle('border-gray-600', !on);
            b.classList.toggle('text-gray-500', !on);
            b.classList.toggle('hover:text-gray-200', !on);
        });
        this._renderReceptionContent();
    },

    _setStatusFilter(s) {
        this.receptionFilter = s;
        document.querySelectorAll('.rec-status-pill').forEach(b => {
            const on = b.dataset.status === s;
            if (on) {
                b.classList.add('bg-[#c5a059]/10', 'text-[#c5a059]', 'border-[#c5a059]/30');
                b.classList.remove('border-transparent', 'text-gray-500');
            } else {
                b.classList.remove('bg-[#c5a059]/10', 'text-[#c5a059]', 'border-[#c5a059]/30');
                b.classList.add('border-transparent', 'text-gray-500');
            }
        });
        this._renderReceptionContent();
    },

    async approveReceptionItem(id) {
        if (!confirm('Approve this guest check-in?')) return;
        const res = await this.api('PUT', `api/reception-requests.php?id=${id}`, { status: 'CHECKIN_APPROVED' });
        if (res.status === 'success') this.fetchQueueData();
    },

    async denyReceptionItem(id) {
        if (!confirm('Reject this guest check-in?')) return;
        const note = prompt('Enter rejection reason (optional):');
        const res = await this.api('PUT', `api/reception-requests.php?id=${id}`, { status: 'REJECTED', reviewNote: note });
        if (res.status === 'success') this.fetchQueueData();
    },

    _filterReception() {
        let list = [...this.receptionRequests];
        // Date filter
        const now = new Date();
        const toDay = d => { const x = new Date(d); x.setHours(0,0,0,0); return x; };
        if (this.receptionDateFilter === 'today') list = list.filter(r => toDay(r.createdAt).toDateString() === now.toDateString());
        if (this.receptionDateFilter === 'week') { const w = new Date(now - 7*864e5); list = list.filter(r => new Date(r.createdAt) >= w); }
        if (this.receptionDateFilter === 'year') list = list.filter(r => new Date(r.createdAt).getFullYear() === now.getFullYear());
        // Search
        if (this.receptionSearch) {
            const q = this.receptionSearch.toLowerCase();
            list = list.filter(r => (r.guestName||'').toLowerCase().includes(q) || (r.phone||'').includes(q) || (r.faydaId||'').includes(q) || (r.roomNumber||'').includes(q));
        }
        // Status bucket
        const buckets = {
            'pending': ['CHECKIN_PENDING','CHECKOUT_PENDING','EXTEND_PENDING','pending'],
            'checked-in': ['CHECKIN_APPROVED','check_in','ACTIVE','guests','staying'],
            'denied': ['REJECTED','denied'],
            'checked-out': ['CHECKED_OUT','CHECKOUT_APPROVED','check_out','checked-out']
        };
        if (this.receptionFilter !== 'all') list = list.filter(r => (buckets[this.receptionFilter]||[]).includes(r.status));
        return list;
    },

    _renderReceptionContent() {
        const container = document.getElementById('rec-cards-container');
        if (!container) return;
        const list = this._filterReception();

        // Update count badges on pills
        const allBuckets = {
            'all': this.receptionRequests,
            'pending': this.receptionRequests.filter(r => ['CHECKIN_PENDING','CHECKOUT_PENDING','EXTEND_PENDING','pending'].includes(r.status)),
            'checked-in': this.receptionRequests.filter(r => ['CHECKIN_APPROVED','check_in','ACTIVE','guests','staying'].includes(r.status)),
            'denied': this.receptionRequests.filter(r => ['REJECTED','denied'].includes(r.status)),
            'checked-out': this.receptionRequests.filter(r => ['CHECKED_OUT','CHECKOUT_APPROVED','check_out','checked-out'].includes(r.status))
        };
        Object.keys(allBuckets).forEach(k => {
            const el = document.querySelectorAll(`.rec-count-${k}`);
            el.forEach(e => e.textContent = allBuckets[k].length);
        });

        // Update top-level Stats
        const revEl = document.getElementById('stat-revenue');
        const approvedEl = document.getElementById('stat-guests');
        const stayEl = document.getElementById('stat-stay');
        
        let totalRevenue = 0;
        let guestsActive = 0;
        let totalStayDuration = 0;
        let stayCount = 0;

        allBuckets['all'].forEach(r => {
            if (r.roomPrice) totalRevenue += Number(r.roomPrice);
            if (['CHECKIN_APPROVED','ACTIVE'].includes(r.status)) {
                guestsActive++;
                if (r.checkIn && r.checkOut) {
                    const days = Math.round((new Date(r.checkOut) - new Date(r.checkIn)) / 864e5);
                    if (days > 0) { totalStayDuration += days; stayCount++; }
                }
            }
        });

        if (revEl) revEl.textContent = totalRevenue.toLocaleString();
        if (approvedEl) approvedEl.textContent = guestsActive;
        if (stayEl) stayEl.textContent = stayCount > 0 ? (totalStayDuration / stayCount).toFixed(1) : '—';

        if (list.length === 0) {
            container.innerHTML = '<div class="col-span-full py-32 text-center text-gray-700 uppercase tracking-[1em] text-[10px] font-bold">No requests match your filters</div>';
            return;
        }

        container.innerHTML = list.map(r => {
            const badgeClass = this._getStatusBadgeClass(r.status);
            const isPending = ['CHECKIN_PENDING', 'pending'].includes(r.status);
            const isAdmin = window.USER_ROLE === 'admin';

            return `
            <div class="bg-[#121413] rounded-2xl border border-[#1a1c1a] p-6 hover:border-[#c5a059]/20 transition-all flex flex-col relative overflow-hidden group">
                <div class="flex justify-between items-start gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gray-900 border border-[#2a2c2a] flex items-center justify-center shrink-0">
                            ${r.photoUrl ? `<img src="${r.photoUrl}" class="w-full h-full object-cover rounded-xl">` : `<i data-lucide="user" class="w-5 h-5 text-gray-500"></i>`}
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-sm font-black text-gray-200 uppercase tracking-widest truncate">${r.guestName || 'Guest'}</h4>
                            <p class="text-[10px] uppercase font-bold text-gray-500 tracking-[0.2em] mt-1">${r.inquiryType?.replace(/_/g,' ') || 'CHECK-IN'}</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg border shrink-0 ${badgeClass}">${r.status.replace(/_/g,' ')}</span>
                </div>

                <div class="space-y-4 text-xs font-medium text-gray-300">
                    <div class="flex items-center gap-3 bg-gray-900/50 rounded-lg p-3 border border-[#2a2c2a]">
                        <i data-lucide="door-open" class="w-4 h-4 text-gray-500"></i>
                        <span class="text-gray-400">Room</span>
                        <span class="ml-auto font-bold text-white">${r.roomNumber ? `Room ${r.roomNumber}` : '—'}</span>
                    </div>
                    
                    <div class="flex justify-between items-center border-b border-gray-800 pb-2">
                        <div class="flex items-center gap-2">
                            <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-600"></i>
                            <span class="text-gray-500">Phone</span>
                        </div>
                        <span class="text-gray-300">${r.phone || '—'}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-gray-800 pb-2">
                         <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-gray-600"></i>
                            <span class="text-gray-500">Stay</span>
                        </div>
                        <span class="text-gray-300">${r.checkIn ? `${r.checkIn?.slice(0,10)} → ${r.checkOut?.slice(0,10)||'?'}` : '—'}</span>
                    </div>
                </div>

                ${isAdmin && isPending ? `
                <div class="grid grid-cols-2 gap-3 mt-6">
                    <button onclick="AdminServices.approveReceptionItem('${r.id}')" class="bg-[#c5a059] text-gray-900 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-[#b59048] transition-colors shadow-lg shadow-[#c5a059]/10">APPROVE</button>
                    <button onclick="AdminServices.denyReceptionItem('${r.id}')" class="bg-gray-800 border border-gray-700 text-red-400 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-red-500/10 hover:border-red-500/30 transition-all">REJECT</button>
                </div>
                ` : `
                <div class="mt-auto pt-6 flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-[#c5a059]">
                    <span>Payment Ref</span>
                    <span>#${r.faydaId?.substring(0,8) || (Math.random()*10000|0).toString().padStart(4,'0')}${r.id.substring(0,4).toUpperCase()}</span>
                </div>
                `}

                <button onclick="AdminServices.viewReceptionDetail('${r.id}')" class="absolute top-2 right-2 w-7 h-7 flex items-center justify-center text-gray-700 hover:text-gray-400 transition-colors opacity-0 group-hover:opacity-100">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                </button>
            </div>`;
        }).join('');
    },

    _getStatusDotClass(s) {
        if (['pending','CHECKIN_PENDING'].includes(s)) return 'bg-amber-500';
        if (['CHECKOUT_PENDING'].includes(s)) return 'bg-orange-500';
        if (['CHECKIN_APPROVED','check_in','ACTIVE','staying','guests'].includes(s)) return 'bg-emerald-500';
        if (['CHECKED_OUT','CHECKOUT_APPROVED','check_out','checked-out'].includes(s)) return 'bg-purple-500';
        return 'bg-red-500';
    },

    _getStatusBadgeClass(s) {
        if (['pending','CHECKIN_PENDING'].includes(s)) return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
        if (['CHECKOUT_PENDING','EXTEND_PENDING'].includes(s)) return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
        if (['CHECKIN_APPROVED','check_in','ACTIVE','staying','guests'].includes(s)) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        if (['CHECKED_OUT','CHECKOUT_APPROVED','check_out','checked-out'].includes(s)) return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
        return 'bg-red-500/10 text-red-400 border-red-500/20';
    },

    _isPending(s) {
        return ['pending','CHECKIN_PENDING','CHECKOUT_PENDING','EXTEND_PENDING'].includes(s);
    },

    async actionReception(id, action) {
        const req = this.receptionRequests.find(r => r.id === id);
        if (!req) return;
        let status = 'REJECTED';
        if (action === 'approve') {
            if (req.status === 'CHECKOUT_PENDING') status = 'CHECKED_OUT';
            else if (req.status === 'EXTEND_PENDING') status = 'CHECKIN_APPROVED';
            else status = 'CHECKIN_APPROVED';
        } else {
            // deny check_out = keep as CHECKIN_APPROVED (guest stays checked in)
            if (req.status === 'CHECKOUT_PENDING') status = 'CHECKIN_APPROVED';
        }
        await this.api('PUT', `api/reception-requests.php?id=${id}`, { status });
        await this.fetchQueueData();
    },

    async wipeReception() {
        if (!confirm('⚠️ This will permanently delete ALL reception records. Continue?')) return;
        await this.api('DELETE', 'api/reception-requests.php?action=wipe');
        await this.fetchQueueData();
    },

    async viewReceptionDetail(id) {
        const res = await this.api('GET', `api/reception-requests.php?id=${id}`);
        const r = res.data;
        if (!r) return;
        
        // Open detail modal
        document.getElementById('rec-detail-name').textContent = r.guestName || '—';
        document.getElementById('rec-detail-status').textContent = r.status.replace(/_/g, ' ');
        document.getElementById('rec-detail-body').innerHTML = `
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><p class="text-gray-500 text-[9px] uppercase mb-1">Fayda ID</p><p class="font-mono text-white">${r.faydaId || '—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase mb-1">Phone</p><p class="font-mono text-white">${r.phone || '—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase mb-1">Room</p><p class="font-bold text-white">${r.roomNumber || '—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase mb-1">Price/Night</p><p class="font-mono text-[#d4af37]">${Number(r.roomPrice||0).toLocaleString()} ETB</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase mb-1">Check In</p><p class="text-white">${r.checkIn?.slice(0,10)||'—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase mb-1">Check Out</p><p class="text-white">${r.checkOut?.slice(0,10)||'—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase mb-1">Guests</p><p class="text-white">${r.guests||1}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase mb-1">Payment</p><p class="text-white">${r.paymentMethod||'—'} ${r.paymentReference ? '· ' + r.paymentReference : ''}</p></div>
                ${r.notes ? `<div class="col-span-2"><p class="text-gray-500 text-[9px] uppercase mb-1">Notes</p><p class="text-gray-300">${r.notes}</p></div>` : ''}
                ${r.reviewNote ? `<div class="col-span-2"><p class="text-gray-500 text-[9px] uppercase mb-1">Review Note</p><p class="text-gray-300">${r.reviewNote}</p></div>` : ''}
                ${r.transactionUrl ? `<div class="col-span-2"><p class="text-gray-500 text-[9px] uppercase mb-1">Receipt</p><a href="${r.transactionUrl}" target="_blank" class="text-[#d4af37] underline text-xs">View Transaction</a></div>` : ''}
            </div>
            ${r.idPhotoFront || r.idPhotoBack ? `
            <div class="grid grid-cols-2 gap-4 mt-4">
                ${r.idPhotoFront ? `<div><p class="text-gray-500 text-[9px] uppercase mb-2">ID Front</p><img src="${r.idPhotoFront}" class="w-full rounded-xl object-cover h-32"></div>` : ''}
                ${r.idPhotoBack ? `<div><p class="text-gray-500 text-[9px] uppercase mb-2">ID Back</p><img src="${r.idPhotoBack}" class="w-full rounded-xl object-cover h-32"></div>` : ''}
            </div>` : ''}`;
        document.getElementById('rec-detail-modal').classList.remove('hidden');
        document.getElementById('rec-detail-id-hidden').value = id;
    },



    // ─── MENU MANAGER ──────────────────────────────────────────────────────────
    _initMenuManager() {
        if (!this.menuManager) {
            this.menuManager = new MenuManager({
                containerId: 'menu-manager-root',
                apiBaseUrl: 'api/admin/menu.php',
                collection: 'menuItems'
            });
            this.menuManager.init();
        } else {
            this.menuManager.renderShell();
            this.menuManager.render();
        }
    },

    // ─── HELPERS ───────────────────────────────────────────────────────────────
    api(method, url, data) {
        const opt = { method, headers: { 'Content-Type': 'application/json' } };
        if (data) opt.body = JSON.stringify(data);
        return fetch(url, opt).then(r => r.json()).catch(() => ({}));
    },

    // Stubs overridden by services.php inline script
    openRoomModal(r) {},
    openQRModal(n) {},
    openMenuModal(i) {}
};

document.addEventListener('DOMContentLoaded', () => AdminServices.init());
