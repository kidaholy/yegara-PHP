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
    receptionFilter: 'all',       // all | pending | checked-in | rejected | checked-out
    receptionPeriod: 'all',       // all | today | week | month | year | specific
    receptionStartDate: '',
    receptionEndDate: '',
    checkoutAlertShown: false,
    receptionSearch: '',
    prevReceptionCount: 0,
    
    // VIP Tiers
    menuTiers: [],

    menuManager: null,
    pollingTimer: null,
    audio: new Audio('public/notification.mp3'),

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
            const params = new URLSearchParams({
                limit: 500,
                period: this.receptionPeriod,
                startDate: this.receptionStartDate,
                endDate: this.receptionEndDate
            });
            const recRes = await this.api('GET', `api/reception-requests.php?${params.toString()}`);
            const recs = Array.isArray(recRes) ? recRes : (recRes.data || []);

            const pendingCount = recs.filter(r => this._isPending(r.status)).length;

            if (pendingCount > this.prevReceptionCount) {
                this._playAlert();
            }

            this.prevReceptionCount = pendingCount;
            this.receptionRequests = recs;

            if (this.activeTab === 'reception') {
                this._renderReceptionContent();
                this._checkCheckoutDueAlert();
            }
        } catch (e) { console.warn('Poll error', e); }
    },

    _checkCheckoutDueAlert() {
        const isReception = ['reception', 'receptionist'].includes(window.USER_ROLE);
        if (!isReception) return;
        const today = new Date().toISOString().slice(0, 10);
        const dueGuests = this.receptionRequests.filter(r =>
            r.status === 'CHECKIN_APPROVED' && r.checkOut && r.checkOut.slice(0, 10) <= today
        );
        if (dueGuests.length > 0 && !this.checkoutAlertShown) {
            this.checkoutAlertShown = true;
            const names = dueGuests.map(g => g.guestName).join(', ');
            alert(`Checkout due today!\n\nPlease check out the following guests: ${names}\n\nGo to Guest Status → Checked In to perform direct checkout.`);
        }
        if (dueGuests.length === 0) this.checkoutAlertShown = false;
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
        panel.innerHTML = `${(map[this.activeTab] || (() => ''))()}`;
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

    async fetchRoomsData(skipRender = false) {
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
        if (!skipRender) this._renderPanel();
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
        if (isAdmin && v !== 'status') return;
        if (!isAdmin && !['check-in', 'status'].includes(v)) return;

        this.receptionSubView = v;
        this._renderPanel();
    },

    _isReceptionStaff() {
        return ['reception', 'receptionist'].includes(window.USER_ROLE);
    },

    _buildReceptionShellHTML() {
        const isAdmin = window.USER_ROLE === 'admin';
        // Default views based on role if not set
        if (isAdmin && !this.receptionSubView) this.receptionSubView = 'status';
        if (!isAdmin && !this.receptionSubView) this.receptionSubView = 'check-in';

        const isCheckIn = this.receptionSubView === 'check-in';
        const isStatus = this.receptionSubView === 'status';

        const receptionTabs = isAdmin ? `
                    <button onclick="AdminServices.setReceptionSubView('status')" 
                        class="px-5 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2
                        ${isStatus ? 'bg-[#c5a059] text-[#0f1110] shadow-lg shadow-[#c5a059]/20' : 'bg-transparent text-gray-500 border border-gray-700 hover:text-gray-300'}">
                        <i data-lucide="users" class="w-4 h-4"></i> Guest Status
                    </button>` : `
                    <button onclick="AdminServices.setReceptionSubView('check-in')" 
                        class="px-5 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2
                        ${isCheckIn ? 'bg-[#c5a059] text-[#0f1110] shadow-lg shadow-[#c5a059]/20' : 'bg-gray-800/50 text-gray-500 border border-gray-700 hover:text-gray-300'}">
                        <i data-lucide="file-plus" class="w-4 h-4"></i> New Check-in
                    </button>
                    <button onclick="AdminServices.setReceptionSubView('status')" 
                        class="px-5 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest transition-all flex items-center gap-2
                        ${isStatus ? 'bg-[#c5a059] text-[#0f1110] shadow-lg shadow-[#c5a059]/20' : 'bg-transparent text-gray-500 border border-gray-700 hover:text-gray-300'}">
                        <i data-lucide="users" class="w-4 h-4"></i> Guest Status
                    </button>`;

        const viewContent = isStatus ? this._buildReceptionStatusHTML() : this._buildReceptionCheckInHTML();

        return `
        <div class="space-y-4 lg:space-y-6 w-full">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-[#121413] border border-[#1a2c1a] rounded-2xl p-4 lg:p-6 shadow-xl">
                <div class="flex items-center gap-6">
                    <div class="w-16 h-16 rounded-2xl bg-[#c5a059]/10 border border-[#c5a059]/30 flex items-center justify-center text-[#c5a059] shadow-inner">
                        <i data-lucide="bell" class="w-8 h-8"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-serif-premium italic text-[#c5a059] tracking-tight">Reception Desk</h2>
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-gray-500 mt-1">${isAdmin ? 'Admin — Guest Approvals' : 'Reception — Guest Management'}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">${receptionTabs}</div>
            </div>

            <div id="reception-view-area" class="animate-in fade-in duration-500">${viewContent}</div>
        </div>`;
    },

    _statusFilterPillsHTML(activeKey = 'all') {
        return [
            { key: 'pending', label: 'Pending', icon: 'clock' },
            { key: 'checked-in', label: 'Checked In', icon: 'check-square' },
            { key: 'checked-out', label: 'Checked Out', icon: 'log-out' },
            { key: 'all', label: 'All', icon: 'users' }
        ].map(s => {
            if (s.key === 'pending') return ''; // Skip pending
            return `
            <button onclick="AdminServices._setStatusFilter('${s.key}')"
                class="rec-status-pill flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider px-4 py-2 rounded-lg border transition-all ${s.key === activeKey ? 'bg-[#c5a059]/10 text-[#c5a059] border-[#c5a059]/30' : 'border-transparent text-gray-500 hover:text-gray-300'}"
                data-status="${s.key}">
                <i data-lucide="${s.icon}" class="w-3.5 h-3.5"></i>
                ${s.label} (<span class="rec-count-${s.key}">0</span>)
            </button>`;}).join('');
    },

    _buildReceptionStatusHTML() {
        const activeFilter = this.receptionFilter || 'all';
        const activePeriod = this.receptionPeriod || 'all';
        return `
        <div class="space-y-6">
            <!-- Date Filter Row -->
            <div class="flex flex-wrap items-center justify-between gap-4 bg-gray-800/40 p-4 rounded-xl border border-gray-700/30">
                <div class="flex flex-wrap items-center gap-2">
                    ${['all', 'today', 'week', 'month', 'specific'].map(p => `
                        <button onclick="AdminServices._setReceptionPeriod('${p}')"
                            class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all
                            ${activePeriod === p ? 'bg-[#c5a059] text-[#0f1110] shadow-lg shadow-[#c5a059]/20' : 'bg-gray-800 text-gray-500 border border-gray-700 hover:text-gray-300'}">
                            ${p === 'specific' ? 'Specific Date' : p}
                        </button>
                    `).join('')}
                </div>
                <div id="rec-custom-dates" class="${activePeriod === 'specific' ? 'flex' : 'hidden'} items-center gap-2 animate-in fade-in slide-in-from-right-2 duration-300">
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Select Date:</span>
                    <input type="date" id="rec-specific-date" value="${this.receptionStartDate}" 
                        onchange="AdminServices._applySpecificDate(this.value)"
                        class="bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-[10px] font-bold text-gray-300 outline-none focus:border-[#c5a059]/50">
                </div>
            </div>

            <!-- Search Row -->
            <div class="bg-gray-800/40 p-2 rounded-xl border border-gray-700/30">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i data-lucide="search" class="w-4 h-4 text-gray-500"></i>
                    </div>
                    <input type="text" id="rec-search" placeholder="Search guests by name, phone, room or ID..."
                        oninput="AdminServices.receptionSearch = this.value; AdminServices._renderReceptionContent()"
                        class="w-full bg-gray-900/50 border border-transparent rounded-lg py-3 pl-11 pr-4 text-xs font-bold uppercase tracking-widest text-gray-200 outline-none focus:border-[#c5a059]/50 focus:bg-gray-800 transition-colors placeholder:text-gray-600">
                </div>
            </div>

            <div id="rec-checkout-banner" class="hidden bg-orange-500/10 border border-orange-500/30 rounded-2xl p-5 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center text-orange-400 shrink-0">
                        <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="text-sm font-black text-orange-300 uppercase tracking-widest">Checkout Due Today</p>
                        <p id="rec-checkout-banner-text" class="text-xs text-orange-400/80 mt-1">Guests whose stay ends today need to be checked out.</p>
                    </div>
                </div>
                <button onclick="AdminServices.checkoutAllGuests()" class="shrink-0 px-5 py-2.5 bg-purple-500/20 border border-purple-500/30 text-purple-300 hover:bg-purple-500 hover:text-white rounded-lg text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Checkout All
                </button>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-5">
                <div class="bg-[#121413] border border-[#1a1c1a] rounded-2xl p-6 text-center hover:border-[#c5a059]/20 transition-colors">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#c5a059]/70 mb-2">Total Revenue</p>
                    <h3 id="stat-revenue" class="text-3xl font-black font-serif-premium italic text-[#c5a059]">0</h3>
                    <p class="text-[10px] text-gray-500 mt-2 uppercase tracking-wider">ETB · All Time</p>
                </div>
                <div class="bg-[#121413] border border-[#1a1c1a] rounded-2xl p-6 text-center hover:border-gray-700 transition-colors">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#c5a059]/70 mb-2">Approved Guests</p>
                    <h3 id="stat-guests" class="text-3xl font-black font-serif-premium italic text-white">0</h3>
                    <p class="text-[10px] text-gray-500 mt-2 uppercase tracking-wider">Checked In / Active</p>
                </div>
                <div class="bg-[#121413] border border-[#1a1c1a] rounded-2xl p-6 text-center hover:border-gray-700 transition-colors">
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#c5a059]/70 mb-2">Avg. Stay</p>
                    <h3 id="stat-stay" class="text-3xl font-black font-serif-premium italic text-white">—</h3>
                    <p class="text-[10px] text-gray-500 mt-2 uppercase tracking-wider">Nights per Guest</p>
                </div>
            </div>

            <!-- Status Pills -->
            <div class="flex items-center justify-between border-b border-gray-800 pb-4 gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    ${this._statusFilterPillsHTML(activeFilter === 'pending' ? 'all' : activeFilter)}
                </div>
                <div class="flex items-center gap-3">
                    <button id="rec-checkout-all-btn" onclick="AdminServices.checkoutAllGuests()" class="hidden px-4 py-2 bg-purple-500/10 border border-purple-500/20 text-purple-400 hover:bg-purple-500 hover:text-white rounded-lg text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Checkout All
                    </button>
                    ${window.USER_ROLE === 'admin' ? `
                    <button onclick="AdminServices.wipeReception()" class="px-4 py-2 bg-red-500/10 border border-red-500/20 text-red-500 hover:bg-red-500 hover:text-white rounded-lg text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Clear All
                    </button>` : ''}
                    <button onclick="AdminServices.fetchQueueData()" class="text-gray-500 hover:text-[#c5a059] transition-colors p-2 shrink-0">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Cards Container -->
            <div id="rec-cards-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 lg:gap-5"></div>
        </div>`;
    },

    _buildReceptionCheckInHTML() {
        const activeFloors = (this.floors || []).filter(f => !f.isDeleted);
        return `
        <div class="w-full">
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#c5a059] mb-8 flex items-center gap-2">
                <i data-lucide="edit-3" class="w-3 h-3"></i> New Check-in
            </p>

            <!-- Hidden file inputs -->
            <input type="file" id="ci-photo-file" accept="image/*" class="sr-only" onchange="AdminServices._ciProfileFileChange(this)">
            <input type="file" id="ci-id-front-file" accept="image/*" class="sr-only" onchange="AdminServices._ciIdUpload(this,'front')">
            <input type="file" id="ci-id-back-file"  accept="image/*" class="sr-only" onchange="AdminServices._ciIdUpload(this,'back')">
            <input type="file" id="ci-receipt-file" accept="application/pdf,.pdf" class="sr-only" onchange="AdminServices._ciReceiptFileChange(this)">

            <form onsubmit="AdminServices.submitNewCheckIn(event)" class="grid grid-cols-1 md:grid-cols-2 gap-x-16 gap-y-10">
                
                <!-- LEFT COLUMN: GUEST DATA -->
                <div class="space-y-8">
                    <div class="ci-input-group">
                        <label class="ci-label">Guest Name *</label>
                        <input type="text" id="ci-name" required class="ci-field" placeholder="Kidus Yosef">
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div class="ci-input-group">
                            <label class="ci-label">Fayda ID (FAN)</label>
                            <input type="text" id="ci-fayda" class="ci-field" placeholder="1283478638648345">
                        </div>
                        <div class="ci-input-group">
                            <label class="ci-label">Phone</label>
                            <input type="text" id="ci-phone" class="ci-field" placeholder="+251978574875">
                        </div>
                    </div>

                    <!-- 1. Guest Profile Photo (URL + File) -->
                    <div class="space-y-3">
                        <p class="ci-label text-[#c5a059] flex items-center gap-2">
                            <i data-lucide="user-circle" class="w-3.5 h-3.5"></i> 1. Guest Profile Photo
                        </p>
                        <div class="flex gap-2">
                            <input type="text" id="ci-photo-url" oninput="AdminServices._ciProfileUrlChange(this.value)" 
                                class="flex-1 bg-gray-800/30 border border-gray-700 rounded-lg px-4 py-2.5 text-xs text-white focus:border-[#c5a059]/50 outline-none transition-colors" 
                                placeholder="Paste image URL or data-uri...">
                            <button type="button" onclick="document.getElementById('ci-photo-file').click()" 
                                class="shrink-0 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-white flex items-center gap-2 transition-colors">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                        <div id="ci-photo-preview" class="hidden relative w-32 h-32 mt-2">
                             <img id="ci-photo-img" src="" class="w-full h-full object-cover rounded-2xl border border-gray-700 shadow-2xl">
                             <div onclick="AdminServices._ciClearProfile()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs cursor-pointer shadow-lg hover:bg-red-600 transition-colors">✕</div>
                        </div>
                    </div>

                    <!-- 2. ID Card Upload (4 Grids) -->
                    <div class="space-y-4 pt-6">
                        <p class="ci-label text-[#c5a059] flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i> 2. ID Card Upload *
                        </p>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <!-- ID Front -->
                            <div class="relative group">
                                <div id="ci-id-front-placeholder" onclick="document.getElementById('ci-id-front-file').click()" 
                                    class="h-28 bg-gray-800/30 border border-dashed border-gray-700 rounded-2xl flex flex-col items-center justify-center text-gray-600 hover:text-gray-400 hover:border-[#c5a059]/30 transition-all cursor-pointer">
                                    <i data-lucide="image" class="w-5 h-5 mb-1"></i>
                                    <span class="text-[8px] font-bold uppercase tracking-widest">ID Front Side</span>
                                </div>
                                <div id="ci-id-front-preview" class="hidden h-28 bg-[#0f1110] border border-gray-700 rounded-2xl overflow-hidden relative shadow-2xl">
                                    <img id="ci-id-front-img" src="" class="w-full h-full object-cover">
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 backdrop-blur-md py-1.5 text-center">
                                        <span class="text-[9px] font-black uppercase tracking-widest text-[#c5a059]">ID Front Saved</span>
                                    </div>
                                    <div onclick="AdminServices._ciClearId('front')" class="absolute top-2 right-2 w-5 h-5 bg-red-500/80 hover:bg-red-500 text-white rounded-full flex items-center justify-center text-[10px] cursor-pointer transition-colors">✕</div>
                                </div>
                            </div>

                            <!-- ID Back -->
                            <div class="relative group">
                                <div id="ci-id-back-placeholder" onclick="document.getElementById('ci-id-back-file').click()" 
                                    class="h-28 bg-gray-800/30 border border-dashed border-gray-700 rounded-2xl flex flex-col items-center justify-center text-gray-600 hover:text-gray-400 hover:border-[#c5a059]/30 transition-all cursor-pointer">
                                    <i data-lucide="image" class="w-5 h-5 mb-1"></i>
                                    <span class="text-[8px] font-bold uppercase tracking-widest">ID Back Side</span>
                                </div>
                                <div id="ci-id-back-preview" class="hidden h-28 bg-[#0f1110] border border-gray-700 rounded-2xl overflow-hidden relative shadow-2xl">
                                    <img id="ci-id-back-img" src="" class="w-full h-full object-cover">
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 backdrop-blur-md py-1.5 text-center">
                                        <span class="text-[9px] font-black uppercase tracking-widest text-[#c5a059]">ID Back Saved</span>
                                    </div>
                                    <div onclick="AdminServices._ciClearId('back')" class="absolute top-2 right-2 w-5 h-5 bg-red-500/80 hover:bg-red-500 text-white rounded-full flex items-center justify-center text-[10px] cursor-pointer transition-colors">✕</div>
                                </div>
                            </div>

                            <!-- Extra 1 -->
                            <div class="h-28 bg-gray-800/10 border border-gray-700/30 rounded-2xl flex flex-col items-center justify-center text-gray-800 cursor-default opacity-40">
                                <i data-lucide="plus" class="w-5 h-5 mb-1"></i>
                                <span class="text-[8px] font-black uppercase tracking-widest">Extra Doc</span>
                            </div>
                            <!-- Extra 2 -->
                            <div class="h-28 bg-gray-800/10 border border-gray-700/30 rounded-2xl flex flex-col items-center justify-center text-gray-800 cursor-default opacity-40">
                                <i data-lucide="plus" class="w-5 h-5 mb-1"></i>
                                <span class="text-[8px] font-black uppercase tracking-widest">Extra Doc</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: STAY & PAYMENT -->
                <div class="space-y-8">
                    <div class="grid grid-cols-2 gap-8">
                        <div class="ci-input-group">
                            <label class="ci-label">Floor</label>
                            <select id="ci-floor" onchange="AdminServices._ciFloorChange(this.value)" class="ci-field appearance-none bg-[#121413]">
                                <option value="">Select Floor...</option>
                                ${activeFloors.map(f => `<option value="${f.id}">Floor ${f.floorNumber} ${f.name || ''}</option>`).join('')}
                            </select>
                        </div>
                        <div class="ci-input-group">
                            <label class="ci-label">Room *</label>
                            <select id="ci-room" onchange="AdminServices._updateStaySummary()" class="ci-field appearance-none bg-[#121413]">
                                <option value="">Select Room...</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div class="ci-input-group">
                            <label class="ci-label">Room Price (ETB)</label>
                            <input type="text" id="ci-room-price-display" readonly class="ci-field text-[#c5a059] font-bold" placeholder="0">
                        </div>
                        <div class="ci-input-group">
                            <label class="ci-label">Number of Guests</label>
                            <select id="ci-guests" class="ci-field bg-[#121413]">
                                <option value="1">1 Guest</option>
                                <option value="2">2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                            </select>
                        </div>
                    </div>

                    <div class="ci-input-group">
                        <label class="ci-label">Stay Duration (Days) *</label>
                        <input type="number" id="ci-duration" required min="1" value="1" oninput="AdminServices._updateStaySummary()" class="ci-field" placeholder="1">
                    </div>

                    <!-- LIVE SUMMARY BOX -->
                    <div class="summary-box">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#c5a059] mb-6 flex items-center gap-2">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Stay Summary
                        </p>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="summary-metric">
                                <p id="summary-nights" class="summary-val">1</p>
                                <p class="summary-unit">Nights</p>
                            </div>
                            <div class="summary-metric border-x border-gray-800">
                                <p id="summary-hours" class="summary-val">24h</p>
                                <p class="summary-unit">Total Hours</p>
                                <p id="summary-calc-info" class="text-[8px] text-gray-700 mt-1 uppercase">0 ETB/night × 1</p>
                            </div>
                            <div class="summary-metric">
                                <p id="summary-total" class="summary-val text-[#c5a059]">0</p>
                                <p class="summary-unit">ETB Total</p>
                            </div>
                        </div>
                    </div>

                    <!-- PAYMENT SECTION -->
                    <div class="space-y-4">
                        <label class="ci-label">Payment Method</label>
                        <div class="grid grid-cols-4 gap-2">
                            ${[
                                {key:'CASH', icon:'banknote'},
                                {key:'MOBILE BANKING', icon:'smartphone'},
                                {key:'TELEBIRR', icon:'phone-call'},
                                {key:'CHEQUE', icon:'credit-card'}
                            ].map(p => `
                            <label class="flex-1">
                                <input type="radio" name="ci-payment" value="${p.key}" class="pay-radio sr-only" ${p.key==='CASH'?'checked':''} onchange="AdminServices._togglePaymentFields()">
                                <div class="pay-btn">
                                    <i data-lucide="${p.icon}" class="pay-btn-icon"></i>
                                    <span class="pay-btn-label">${p.key}</span>
                                </div>
                            </label>`).join('')}
                        </div>
                    </div>

                    <div id="payment-extra-fields" class="hidden animate-in slide-in-from-top-2 duration-300 space-y-6">
                        <div class="ci-input-group">
                            <label class="ci-label">Transaction Number</label>
                            <input type="text" id="ci-transaction" class="ci-field" placeholder="Enter transaction number">
                        </div>
                        <div class="ci-input-group">
                            <label class="ci-label">Mobile Banking Receipt URL *</label>
                            <div class="flex gap-2">
                                <input type="text" id="ci-receipt-url" oninput="AdminServices._ciReceiptChange(this.value)" class="ci-field flex-1" placeholder="https://receipt.dashensuperapp.com/...">
                                <button type="button" onclick="document.getElementById('ci-receipt-file').click()"
                                    class="shrink-0 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-white flex items-center gap-2 transition-colors">
                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i> PDF
                                </button>
                            </div>
                        </div>
                        <div id="ci-receipt-pdf-preview" class="hidden bg-gray-800 rounded-xl p-4 border border-gray-700 animate-in fade-in space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-[#c5a059]/10 flex items-center justify-center text-[#c5a059] shrink-0">
                                    <i data-lucide="file-text" class="w-5 h-5"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-white truncate" id="receipt-filename">Receipt Preview</p>
                                    <p class="text-[9px] text-gray-500 uppercase mt-0.5">Receipt document preview</p>
                                </div>
                                <button type="button" onclick="AdminServices.openReceiptFull()"
                                    class="px-3 py-1.5 bg-[#c5a059]/10 border border-[#c5a059]/30 rounded-lg text-[9px] font-black uppercase tracking-widest text-[#c5a059] hover:bg-[#c5a059] hover:text-gray-900 transition-colors shrink-0">Open Full</button>
                                <button type="button" onclick="AdminServices._ciClearReceipt()"
                                    class="w-7 h-7 rounded-lg bg-red-500/10 border border-red-500/20 text-red-500 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center shrink-0">✕</button>
                            </div>
                            <div id="ci-receipt-embed-wrap" class="hidden rounded-xl overflow-hidden border border-gray-700 bg-gray-900 h-80 relative group cursor-pointer"
                                onclick="AdminServices.openReceiptFull()" title="Click to open full preview">
                                <iframe id="ci-receipt-iframe" class="w-full h-full bg-white pointer-events-none" title="Receipt preview" src="about:blank"></iframe>
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <span class="px-4 py-2 bg-[#c5a059] text-gray-900 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-lg">Open Full</span>
                                </div>
                            </div>
                            <div id="ci-receipt-link-wrap" class="hidden rounded-xl border border-gray-700 bg-gray-900 h-48 flex flex-col items-center justify-center text-center p-6 cursor-pointer hover:border-[#c5a059]/30 transition-colors"
                                onclick="AdminServices.openReceiptFull()" title="Click to open receipt page">
                                <i data-lucide="external-link" class="w-8 h-8 text-[#c5a059] mb-3"></i>
                                <p class="text-xs font-bold text-gray-300 mb-1">Banking receipt page linked</p>
                                <p class="text-[10px] text-gray-500">Click <span class="text-[#c5a059] font-bold">Open Full</span> to view in a new tab</p>
                            </div>
                        </div>
                    </div>

                    <div class="ci-input-group">
                        <label class="ci-label">Notes</label>
                        <textarea id="ci-notes" rows="2" class="ci-field resize-none" placeholder="Additional details or remarks..."></textarea>
                    </div>
                </div>

                <div class="col-span-full pt-8 border-t border-gray-800">
                    <button type="submit" class="w-full btn-gold py-5 rounded-xl text-xs font-black uppercase tracking-[0.4em] flex items-center justify-center gap-2 shadow-2xl">
                        <i data-lucide="check-circle" class="w-5 h-5"></i> Submit Request
                    </button>
                    <p class="text-center text-[9px] font-bold text-gray-700 mt-4 uppercase tracking-widest">By submitting you confirm all guest details are audited and verified</p>
                </div>
            </form>
        </div>`;
    },

    // ── Profile Photo helpers ─────────────────────────────────────────────────
    _ciProfileUrlChange(url) {
        if (!url) { this._ciClearProfile(); return; }
        const img = document.getElementById('ci-photo-img');
        const preview = document.getElementById('ci-photo-preview');
        if (img && preview) { 
            img.src = url; 
            preview.classList.remove('hidden'); 
        }
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
            const placeholder = document.getElementById(`ci-id-${side}-placeholder`);
            const preview = document.getElementById(`ci-id-${side}-preview`);
            if (img) img.src = e.target.result;
            if (placeholder) placeholder.classList.add('hidden');
            if (preview) preview.classList.remove('hidden');
            // Store as base64 in hidden var
            AdminServices[`_ciId${side.charAt(0).toUpperCase()+side.slice(1)}B64`] = e.target.result;
        };
        reader.readAsDataURL(file);
    },

    _ciClearId(side) {
        const placeholder = document.getElementById(`ci-id-${side}-placeholder`);
        const preview = document.getElementById(`ci-id-${side}-preview`);
        const fileInput = document.getElementById(`ci-id-${side}-file`);
        if (placeholder) placeholder.classList.remove('hidden');
        if (preview) preview.classList.add('hidden');
        if (fileInput) fileInput.value = '';
        AdminServices[`_ciId${side.charAt(0).toUpperCase()+side.slice(1)}B64`] = null;
    },

    // ── Receipt PDF preview helper ────────────────────────────────────────────
    _isReceiptPreviewable(val) {
        if (!val || typeof val !== 'string') return false;
        const v = val.trim();
        return v.startsWith('http://') || v.startsWith('https://') || v.startsWith('data:application/pdf') || v.startsWith('blob:');
    },

    _isReceiptEmbeddable(val) {
        const v = (val || '').trim();
        if (!v) return false;
        if (v.startsWith('data:application/pdf') || v.startsWith('blob:')) return true;
        return /^https?:\/\//i.test(v) && /\.pdf(\?|#|$)/i.test(v);
    },

    _receiptPreviewSrc(val) {
        const v = (val || '').trim();
        if (!v || !this._isReceiptEmbeddable(v)) return '';
        return v;
    },

    _receiptLabel(val) {
        const v = (val || '').trim();
        if (!v) return 'Receipt';
        if (v.startsWith('data:')) return 'Uploaded Receipt.pdf';
        const part = v.split('/').pop().split('?')[0];
        return part || 'Receipt Document';
    },

    _ciReceiptChange(val) {
        const pdfPreview = document.getElementById('ci-receipt-pdf-preview');
        const filename = document.getElementById('receipt-filename');
        const iframe = document.getElementById('ci-receipt-iframe');
        const embedWrap = document.getElementById('ci-receipt-embed-wrap');
        const linkWrap = document.getElementById('ci-receipt-link-wrap');
        const canPreview = this._isReceiptPreviewable(val);
        const embeddable = this._isReceiptEmbeddable(val);

        if (!pdfPreview) return;

        if (canPreview) {
            const label = this._receiptLabel(val);
            if (filename) filename.textContent = label;
            if (embedWrap) embedWrap.classList.toggle('hidden', !embeddable);
            if (linkWrap) linkWrap.classList.toggle('hidden', embeddable);
            if (iframe) iframe.src = embeddable ? val.trim() : 'about:blank';
            this._receiptFullUrl = val.trim();
            this._receiptFullTitle = label;
            pdfPreview.classList.remove('hidden');
            lucide.createIcons();
        } else {
            if (iframe) iframe.src = 'about:blank';
            if (embedWrap) embedWrap.classList.add('hidden');
            if (linkWrap) linkWrap.classList.add('hidden');
            this._receiptFullUrl = '';
            pdfPreview.classList.add('hidden');
        }
    },

    openReceiptFull(url, title) {
        const receiptUrl = (url || this._receiptFullUrl || document.getElementById('ci-receipt-url')?.value || document.getElementById('recheckin-receipt-url')?.value || '').trim();
        if (!receiptUrl || !this._isReceiptPreviewable(receiptUrl)) return;

        const embeddable = this._isReceiptEmbeddable(receiptUrl);
        const label = title || this._receiptFullTitle || this._receiptLabel(receiptUrl);

        if (!embeddable) {
            window.open(receiptUrl, '_blank', 'noopener,noreferrer');
            return;
        }

        const modal = document.getElementById('receipt-full-modal');
        const iframe = document.getElementById('receipt-full-iframe');
        const embedWrap = document.getElementById('receipt-full-embed-wrap');
        const fallback = document.getElementById('receipt-full-fallback');
        const fallbackLink = document.getElementById('receipt-full-fallback-link');
        const titleEl = document.getElementById('receipt-full-title');
        const external = document.getElementById('receipt-full-external');

        if (titleEl) titleEl.textContent = label;
        if (external) external.href = receiptUrl;
        if (fallbackLink) fallbackLink.href = receiptUrl;
        if (embedWrap) embedWrap.classList.remove('hidden');
        if (fallback) fallback.classList.add('hidden');
        if (iframe) iframe.src = receiptUrl;
        if (modal) modal.classList.remove('hidden');
        lucide.createIcons();
    },

    closeReceiptFull() {
        const modal = document.getElementById('receipt-full-modal');
        const iframe = document.getElementById('receipt-full-iframe');
        const embedWrap = document.getElementById('receipt-full-embed-wrap');
        const fallback = document.getElementById('receipt-full-fallback');
        if (iframe) iframe.src = 'about:blank';
        if (embedWrap) embedWrap.classList.remove('hidden');
        if (fallback) fallback.classList.add('hidden');
        if (modal) modal.classList.add('hidden');
    },

    _ciReceiptFileChange(input) {
        const file = input.files[0];
        if (!file) return;
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('Please upload a PDF receipt.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            const urlBox = document.getElementById('ci-receipt-url');
            if (urlBox) urlBox.value = e.target.result;
            this._ciReceiptChange(e.target.result);
        };
        reader.readAsDataURL(file);
    },

    _ciClearReceipt() {
        const urlBox = document.getElementById('ci-receipt-url');
        const fileInput = document.getElementById('ci-receipt-file');
        const iframe = document.getElementById('ci-receipt-iframe');
        if (urlBox) urlBox.value = '';
        if (fileInput) fileInput.value = '';
        if (iframe) iframe.src = 'about:blank';
        this._receiptFullUrl = '';
        this._receiptFullTitle = '';
        this._ciReceiptChange('');
    },

    // ── Re-check In form helpers ──────────────────────────────────────────────
    _rciProfileUrlChange(url) {
        if (!url) { this._rciClearProfile(); return; }
        const img = document.getElementById('recheckin-photo-img');
        const preview = document.getElementById('recheckin-photo-preview');
        if (img && preview) {
            img.src = url;
            preview.classList.remove('hidden');
        }
    },

    _rciProfileFileChange(input) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const urlBox = document.getElementById('recheckin-photo-url');
            if (urlBox) urlBox.value = e.target.result;
            this._rciProfileUrlChange(e.target.result);
        };
        reader.readAsDataURL(file);
    },

    _rciClearProfile() {
        const urlBox = document.getElementById('recheckin-photo-url');
        const preview = document.getElementById('recheckin-photo-preview');
        if (urlBox) urlBox.value = '';
        if (preview) preview.classList.add('hidden');
    },

    _rciIdUpload(input, side) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById(`recheckin-id-${side}-img`);
            const placeholder = document.getElementById(`recheckin-id-${side}-placeholder`);
            const preview = document.getElementById(`recheckin-id-${side}-preview`);
            if (img) img.src = e.target.result;
            if (placeholder) placeholder.classList.add('hidden');
            if (preview) preview.classList.remove('hidden');
            AdminServices[`_rciId${side.charAt(0).toUpperCase() + side.slice(1)}B64`] = e.target.result;
        };
        reader.readAsDataURL(file);
    },

    _rciClearId(side) {
        const placeholder = document.getElementById(`recheckin-id-${side}-placeholder`);
        const preview = document.getElementById(`recheckin-id-${side}-preview`);
        const fileInput = document.getElementById(`recheckin-id-${side}-file`);
        if (placeholder) placeholder.classList.remove('hidden');
        if (preview) preview.classList.add('hidden');
        if (fileInput) fileInput.value = '';
        AdminServices[`_rciId${side.charAt(0).toUpperCase() + side.slice(1)}B64`] = null;
    },

    _rciReceiptChange(val) {
        const pdfPreview = document.getElementById('recheckin-receipt-preview');
        const filename = document.getElementById('recheckin-receipt-filename');
        const iframe = document.getElementById('recheckin-receipt-iframe');
        const embedWrap = document.getElementById('recheckin-receipt-embed-wrap');
        const canPreview = this._isReceiptPreviewable(val);
        const embeddable = this._isReceiptEmbeddable(val);

        if (!pdfPreview) return;

        if (canPreview) {
            const label = this._receiptLabel(val);
            if (filename) filename.textContent = label;
            if (embedWrap) embedWrap.classList.toggle('hidden', !embeddable);
            if (iframe) iframe.src = embeddable ? val.trim() : 'about:blank';
            this._receiptFullUrl = val.trim();
            this._receiptFullTitle = label;
            pdfPreview.classList.remove('hidden');
        } else {
            if (iframe) iframe.src = 'about:blank';
            if (embedWrap) embedWrap.classList.add('hidden');
            this._receiptFullUrl = '';
            pdfPreview.classList.add('hidden');
        }
    },

    _rciReceiptFileChange(input) {
        const file = input.files[0];
        if (!file) return;
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            alert('Please upload a PDF receipt.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            const urlBox = document.getElementById('recheckin-receipt-url');
            if (urlBox) urlBox.value = e.target.result;
            this._rciReceiptChange(e.target.result);
        };
        reader.readAsDataURL(file);
    },

    _rciClearReceipt() {
        const urlBox = document.getElementById('recheckin-receipt-url');
        const fileInput = document.getElementById('recheckin-receipt-file');
        const iframe = document.getElementById('recheckin-receipt-iframe');
        if (urlBox) urlBox.value = '';
        if (fileInput) fileInput.value = '';
        if (iframe) iframe.src = 'about:blank';
        this._receiptFullUrl = '';
        this._receiptFullTitle = '';
        this._rciReceiptChange('');
    },

    _toggleRecheckinPaymentFields() {
        const method = document.querySelector('input[name="recheckin-payment"]:checked')?.value;
        const extra = document.getElementById('recheckin-payment-extra');
        if (extra) {
            extra.classList.toggle('hidden', !['MOBILE BANKING', 'TELEBIRR'].includes(method));
        }
    },

    _populateRecheckinIdPreview(side, dataUrl) {
        if (!dataUrl) return;
        const img = document.getElementById(`recheckin-id-${side}-img`);
        const placeholder = document.getElementById(`recheckin-id-${side}-placeholder`);
        const preview = document.getElementById(`recheckin-id-${side}-preview`);
        if (img) img.src = dataUrl;
        if (placeholder) placeholder.classList.add('hidden');
        if (preview) preview.classList.remove('hidden');
        AdminServices[`_rciId${side.charAt(0).toUpperCase() + side.slice(1)}B64`] = dataUrl;
    },

    _renderReceiptPreviewHtml(url, label = 'Digital Receipt') {
        if (!url || !this._isReceiptPreviewable(url)) {
            return `<a href="${url}" target="_blank" rel="noopener" class="text-[#c5a059] underline text-xs font-bold hover:text-white transition-colors">View Transaction</a>`;
        }
        const embeddable = this._isReceiptEmbeddable(url);
        const name = this._receiptLabel(url);
        this._receiptFullUrl = url;
        this._receiptFullTitle = name;
        const previewBody = embeddable ? `
                <div class="rounded-xl overflow-hidden border border-gray-700 bg-gray-900 h-72 relative group cursor-pointer"
                    onclick="AdminServices.openReceiptFull()" title="Click to open full preview">
                    <iframe src="${url}" class="w-full h-full bg-white pointer-events-none" title="${name}"></iframe>
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <span class="px-4 py-2 bg-[#c5a059] text-gray-900 rounded-lg text-[10px] font-black uppercase tracking-widest shadow-lg">Open Full</span>
                    </div>
                </div>` : `
                <div class="rounded-xl border border-gray-700 bg-gray-900 h-40 flex flex-col items-center justify-center text-center p-6 cursor-pointer hover:border-[#c5a059]/30 transition-colors"
                    onclick="AdminServices.openReceiptFull()" title="Click to open receipt page">
                    <p class="text-xs font-bold text-gray-300 mb-1">Banking receipt page</p>
                    <p class="text-[10px] text-gray-500">Opens in a new browser tab</p>
                </div>`;
        return `
            <div class="col-span-2 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-gray-500 text-[9px] uppercase font-black tracking-widest">${label}</p>
                    <button type="button" onclick="AdminServices.openReceiptFull()"
                        class="px-3 py-1.5 bg-[#c5a059]/10 border border-[#c5a059]/30 rounded-lg text-[9px] font-black uppercase tracking-widest text-[#c5a059] hover:bg-[#c5a059] hover:text-gray-900 transition-colors">Open Full</button>
                </div>
                ${previewBody}
            </div>`;
    },

    _ciFloorChange(floorId) {
        const select = document.getElementById('ci-room');
        if (!select) return;
        if (!floorId) {
            select.innerHTML = '<option value="">Select a floor first</option>';
            return;
        }

        // Identify rooms currently "held" by active reception requests
        // This prevents double-booking if a request is pending but room status isn't yet 'occupied'
        const activeStatuses = ['PENDING_APPROVAL', 'CHECKIN_PENDING', 'CHECKIN_APPROVED', 'CHECKOUT_PENDING', 'EXTEND_PENDING', 'pending', 'check_in', 'ACTIVE', 'guests', 'staying'];
        const heldRoomNumbers = this.receptionRequests
            .filter(r => !r.isDeleted && activeStatuses.includes(r.status))
            .map(r => String(r.roomNumber).trim());

        const rmFilters = this.rooms.filter(r => 
            r.floorId === floorId && 
            r.status === 'available' &&
            !heldRoomNumbers.includes(String(r.roomNumber).trim())
        );

        if (rmFilters.length === 0) {
            select.innerHTML = '<option value="">No rooms available</option>';
        } else {
            select.innerHTML = '<option value="">Select Room...</option>' + 
                rmFilters.map(r => `<option value="${r.roomNumber}" data-price="${r.price}">Room ${r.roomNumber} - ${r.category} (${Number(r.price).toLocaleString()} Br)</option>`).join('');
        }
        this._updateStaySummary();
    },

    _updateStaySummary() {
        const roomSel = document.getElementById('ci-room');
        const durationInp = document.getElementById('ci-duration');
        if (!roomSel || !durationInp) return;

        const opt = roomSel.options[roomSel.selectedIndex];
        const price = parseFloat(opt?.dataset.price || 0);
        const days = parseInt(durationInp.value || 1);
        
        const total = price * days;
        const hours = days * 24;

        // UI Updates
        const priceDisp = document.getElementById('ci-room-price-display');
        const nightsDisp = document.getElementById('summary-nights');
        const hoursDisp = document.getElementById('summary-hours');
        const totalDisp = document.getElementById('summary-total');
        const infoDisp = document.getElementById('summary-calc-info');

        if (priceDisp) priceDisp.value = price > 0 ? price.toLocaleString() : '0';
        if (nightsDisp) nightsDisp.textContent = days;
        if (hoursDisp) hoursDisp.textContent = hours + 'h';
        if (totalDisp) totalDisp.textContent = total.toLocaleString();
        if (infoDisp) infoDisp.textContent = `${price.toLocaleString()} ETB/night × ${days}`;
        
        lucide.createIcons();
    },

    _togglePaymentFields() {
        const method = document.querySelector('input[name="ci-payment"]:checked')?.value;
        const extra = document.getElementById('payment-extra-fields');
        if (extra) {
            if (['MOBILE BANKING', 'TELEBIRR'].includes(method)) {
                extra.classList.remove('hidden');
            } else {
                extra.classList.add('hidden');
            }
        }
    },

    async submitNewCheckIn(e) {
        try {
            if (e) e.preventDefault();
            const paymentMethod = document.querySelector('input[name="ci-payment"]:checked')?.value || 'CASH';
            const transNum = document.getElementById('ci-transaction')?.value || '';
            const receiptUrl = document.getElementById('ci-receipt-url')?.value || '';
            
            const nameEl = document.getElementById('ci-name');
            const roomEl = document.getElementById('ci-room');
            if (!nameEl?.value) { alert('Please enter guest name'); return; }
            if (!roomEl?.value) { alert('Please select a room'); return; }

            const body = {
                guestName: nameEl.value,
                phone: document.getElementById('ci-phone')?.value || '',
                faydaId: document.getElementById('ci-fayda')?.value || '',
                roomNumber: roomEl.value,
                guests: parseInt(document.getElementById('ci-guests')?.value || 1),
                stayDuration: parseInt(document.getElementById('ci-duration')?.value || 1),
                paymentMethod,
                receiptNumber: transNum,
                transactionUrl: receiptUrl,
                notes: document.getElementById('ci-notes')?.value || '',
                profilePhoto: document.getElementById('ci-photo-url')?.value || '',
                idPhotoFront: AdminServices._ciIdFrontB64 || '',
                idPhotoBack: AdminServices._ciIdBackB64 || '',
                inquiryType: 'WALK_IN',
            };
            
            const res = await this.api('POST', 'api/reception-requests.php', body);
            if (res?.status === 'success') {
                alert('Guest Checked In Successfully!');
                window.location.reload();
            } else {
                alert(res?.message || 'Error submitting check-in');
            }
        } catch (err) {
            console.error(err);
            alert('Submission Error: ' + err.message);
        }
    },


    _setStatusFilter(s) {
        this.receptionFilter = s;
        document.querySelectorAll('.rec-status-pill').forEach(b => {
            const on = b.dataset.status === s;
            b.classList.toggle('bg-[#c5a059]/10', on);
            b.classList.toggle('text-[#c5a059]', on);
            b.classList.toggle('border-[#c5a059]/30', on);
            b.classList.toggle('border-transparent', !on);
            b.classList.toggle('text-gray-500', !on);
        });
        this._renderReceptionContent();
    },

    async approveReceptionItem(id) {
        const req = this.receptionRequests.find(r => r.id === id);
        if (!req) return;
        let msg = 'Approve this guest check-in?';
        if (req.status === 'CHECKOUT_PENDING') msg = 'Approve checkout for this guest?';
        if (req.status === 'EXTEND_PENDING') msg = `Approve ${req.pendingExtraDays || 1} extra day(s) for this guest?`;
        if (!confirm(msg)) return;
        await this.actionReception(id, 'approve');
    },



    async requestCheckout(id) {
        if (!confirm('Check out this guest?')) return;
        const res = await this.api('PUT', `api/reception-requests.php?id=${id}`, { status: 'CHECKED_OUT' });
        if (res.status === 'success') {
            alert('Guest checked out successfully.');
            this.fetchQueueData();
        } else {
            alert(res?.message || 'Failed to check out guest');
        }
    },

    _getCheckedInGuests() {
        const checkedInStatuses = ['CHECKIN_APPROVED', 'check_in', 'ACTIVE', 'guests', 'staying'];
        return this.receptionRequests.filter(r => checkedInStatuses.includes(r.status));
    },

    async checkoutAllGuests() {
        const checkedIn = this._getCheckedInGuests();
        if (checkedIn.length === 0) {
            alert('No checked-in guests to check out.');
            return;
        }
        const names = checkedIn.map(g => g.guestName || 'Guest').join(', ');
        if (!confirm(`Check out all ${checkedIn.length} guest(s)?\n\n${names}`)) return;

        const res = await this.api('PUT', 'api/reception-requests.php?action=checkout-all', { status: 'CHECKED_OUT' });
        if (res.status === 'success') {
            alert(res.message || `Checked out ${res.count || checkedIn.length} guest(s) successfully.`);
            this.fetchQueueData();
        } else {
            alert(res?.message || 'Failed to check out guests');
        }
    },

    _isCheckedOutStatus(status) {
        return ['CHECKED_OUT', 'CHECKOUT_APPROVED', 'check_out', 'checked-out'].includes(status);
    },

    async requestRecheckIn(id) {
        if (!this.floors.length || !this.rooms.length) {
            await this.fetchRoomsData(true);
        }

        const res = await this.api('GET', `api/reception-requests.php?id=${id}`);
        const req = res.data || this.receptionRequests.find(r => r.id === id);
        if (!req) return;

        this._rciIdFrontB64 = req.idPhotoFront || null;
        this._rciIdBackB64 = req.idPhotoBack || null;

        document.getElementById('recheckin-request-id').value = id;
        document.getElementById('recheckin-name').value = req.guestName || '';
        document.getElementById('recheckin-phone').value = req.phone || '';
        document.getElementById('recheckin-fayda').value = req.faydaId || '';
        document.getElementById('recheckin-notes').value = req.notes || '';
        document.getElementById('recheckin-transaction').value = req.receiptNumber || '';

        const profileUrl = req.profilePhoto || `api/cashier/image.php?id=${encodeURIComponent(id)}&collection=receptionRequests&t=${Date.now()}`;
        const photoUrlEl = document.getElementById('recheckin-photo-url');
        if (photoUrlEl) photoUrlEl.value = req.profilePhoto || '';
        if (req.profilePhoto) {
            this._rciProfileUrlChange(req.profilePhoto);
        } else {
            this._rciClearProfile();
            const img = document.getElementById('recheckin-photo-img');
            const preview = document.getElementById('recheckin-photo-preview');
            if (img && preview) {
                img.src = profileUrl;
                img.onerror = () => preview.classList.add('hidden');
                preview.classList.remove('hidden');
            }
        }

        this._populateRecheckinIdPreview('front', req.idPhotoFront || null);
        if (!req.idPhotoFront) this._rciClearId('front');
        this._populateRecheckinIdPreview('back', req.idPhotoBack || null);
        if (!req.idPhotoBack) this._rciClearId('back');

        const paymentMethod = req.paymentMethod || 'CASH';
        document.querySelectorAll('input[name="recheckin-payment"]').forEach(radio => {
            radio.checked = radio.value === paymentMethod;
        });
        this._toggleRecheckinPaymentFields();

        const receiptUrlEl = document.getElementById('recheckin-receipt-url');
        if (receiptUrlEl) receiptUrlEl.value = req.transactionUrl || '';
        if (req.transactionUrl) {
            this._rciReceiptChange(req.transactionUrl);
        } else {
            this._rciClearReceipt();
        }

        const durationEl = document.getElementById('recheckin-duration');
        const guestsEl = document.getElementById('recheckin-guests');
        if (durationEl) durationEl.value = req.stayDuration || 1;
        if (guestsEl) guestsEl.value = String(req.guests || 1);

        const floorEl = document.getElementById('recheckin-floor');
        const activeFloors = (this.floors || []).filter(f => !f.isDeleted);
        if (floorEl) {
            floorEl.innerHTML = '<option value="">Select Floor...</option>' +
                activeFloors.map(f => `<option value="${f.id}">Floor ${f.floorNumber} ${f.name || ''}</option>`).join('');
        }

        const prevRoom = this.rooms.find(r => String(r.roomNumber) === String(req.roomNumber));
        const floorId = prevRoom?.floorId || activeFloors[0]?.id || '';
        if (floorEl && floorId) {
            floorEl.value = floorId;
            this._recheckinFloorChange(floorId, req.roomNumber);
        } else {
            this._recheckinFloorChange('', req.roomNumber);
        }

        this._updateRecheckInSummary();
        document.getElementById('rec-recheckin-modal').classList.remove('hidden');
        lucide.createIcons();
    },

    _recheckinFloorChange(floorId, preselectRoom = null) {
        const select = document.getElementById('recheckin-room');
        const requestId = document.getElementById('recheckin-request-id')?.value;
        if (!select) return;

        if (!floorId) {
            select.innerHTML = '<option value="">Select a floor first</option>';
            return;
        }

        const activeStatuses = ['PENDING_APPROVAL', 'CHECKIN_PENDING', 'CHECKIN_APPROVED', 'CHECKOUT_PENDING', 'EXTEND_PENDING', 'pending', 'check_in', 'ACTIVE', 'guests', 'staying'];
        const heldRoomNumbers = this.receptionRequests
            .filter(r => !r.isDeleted && r.id !== requestId && activeStatuses.includes(r.status))
            .map(r => String(r.roomNumber).trim());

        const req = this.receptionRequests.find(r => r.id === requestId);
        const previousRoom = preselectRoom || req?.roomNumber;

        const availableRooms = this.rooms.filter(r =>
            r.floorId === floorId &&
            r.status === 'available' &&
            !heldRoomNumbers.includes(String(r.roomNumber).trim())
        );

        const prevRoomObj = previousRoom
            ? this.rooms.find(r => String(r.roomNumber) === String(previousRoom) && r.floorId === floorId)
            : null;
        const roomOptions = [...availableRooms];
        if (prevRoomObj && !roomOptions.some(r => r.id === prevRoomObj.id)) {
            roomOptions.unshift(prevRoomObj);
        }

        if (roomOptions.length === 0) {
            select.innerHTML = '<option value="">No rooms available</option>';
        } else {
            select.innerHTML = '<option value="">Select Room...</option>' +
                roomOptions.map(r => `<option value="${r.roomNumber}" data-price="${r.price}">Room ${r.roomNumber} - ${r.category} (${Number(r.price).toLocaleString()} Br)</option>`).join('');
            if (previousRoom) {
                select.value = String(previousRoom);
            }
        }
        this._updateRecheckInSummary();
    },

    _updateRecheckInSummary() {
        const roomSel = document.getElementById('recheckin-room');
        const durationInp = document.getElementById('recheckin-duration');
        if (!roomSel || !durationInp) return;

        const opt = roomSel.options[roomSel.selectedIndex];
        const price = parseFloat(opt?.dataset.price || 0);
        const days = parseInt(durationInp.value || 1, 10);

        const today = new Date();
        const checkout = new Date(today);
        checkout.setDate(checkout.getDate() + days);

        const checkinEl = document.getElementById('recheckin-checkin-date');
        const checkoutEl = document.getElementById('recheckin-checkout-date');
        const totalEl = document.getElementById('recheckin-total');

        if (checkinEl) checkinEl.textContent = today.toISOString().slice(0, 10);
        if (checkoutEl) checkoutEl.textContent = checkout.toISOString().slice(0, 10);
        if (totalEl) totalEl.textContent = `${(price * days).toLocaleString()} ETB`;
    },

    async _submitRecheckIn(e) {
        e.preventDefault();
        const id = document.getElementById('recheckin-request-id').value;
        const guestName = document.getElementById('recheckin-name')?.value?.trim();
        const roomNumber = document.getElementById('recheckin-room')?.value;
        const stayDuration = parseInt(document.getElementById('recheckin-duration')?.value || 1, 10);
        const guests = parseInt(document.getElementById('recheckin-guests')?.value || 1, 10);

        if (!guestName) {
            alert('Please enter guest name');
            return;
        }
        if (!roomNumber) {
            alert('Please select a room');
            return;
        }
        if (!stayDuration || stayDuration < 1) {
            alert('Please enter a valid stay duration');
            return;
        }

        const body = {
            status: 'CHECKIN_APPROVED',
            guestName,
            phone: document.getElementById('recheckin-phone')?.value || '',
            faydaId: document.getElementById('recheckin-fayda')?.value || '',
            roomNumber,
            stayDuration,
            guests,
            paymentMethod: document.querySelector('input[name="recheckin-payment"]:checked')?.value || 'CASH',
            receiptNumber: document.getElementById('recheckin-transaction')?.value || '',
            transactionUrl: document.getElementById('recheckin-receipt-url')?.value || '',
            notes: document.getElementById('recheckin-notes')?.value || ''
        };

        const profilePhoto = document.getElementById('recheckin-photo-url')?.value?.trim();
        if (profilePhoto) body.profilePhoto = profilePhoto;
        if (this._rciIdFrontB64) body.idPhotoFront = this._rciIdFrontB64;
        if (this._rciIdBackB64) body.idPhotoBack = this._rciIdBackB64;

        const res = await this.api('PUT', `api/reception-requests.php?id=${id}`, body);

        if (res.status === 'success') {
            document.getElementById('rec-recheckin-modal').classList.add('hidden');
            alert('Guest re-checked in successfully.');
            this.receptionFilter = 'checked-in';
            await this.fetchQueueData();
            this._setStatusFilter('checked-in');
        } else {
            alert(res?.message || 'Failed to re-check in guest');
        }
    },

    async requestExtend(id) {
        const req = this.receptionRequests.find(r => r.id === id);
        if (!req) return;
        
        document.getElementById('extend-request-id').value = id;
        document.getElementById('extend-current-checkout').value = req.checkOut || '';
        document.getElementById('extend-extra-days').value = 1;
        document.getElementById('extend-prev-date').textContent = req.checkOut || 'N/A';
        
        this._updateExtendPreview();
        document.getElementById('rec-extend-modal').classList.remove('hidden');
    },

    _updateExtendPreview() {
        const currentStr = document.getElementById('extend-current-checkout').value;
        const extra = parseInt(document.getElementById('extend-extra-days').value, 10) || 0;
        if (!currentStr) return;
        
        const date = new Date(currentStr);
        date.setDate(date.getDate() + extra);
        document.getElementById('extend-new-date').textContent = date.toISOString().split('T')[0];
    },

    async _submitExtension(e) {
        e.preventDefault();
        const id = document.getElementById('extend-request-id').value;
        const days = parseInt(document.getElementById('extend-extra-days').value, 10);
        
        if (!days || days < 1) return;
        
        const res = await this.api('PUT', `api/reception-requests.php?id=${id}`, { 
            status: 'CHECKIN_APPROVED', 
            extraDays: days 
        });
        
        if (res.status === 'success') {
            alert(`Stay extended by ${days} day(s).`);
            document.getElementById('rec-extend-modal').classList.add('hidden');
            this.fetchQueueData();
        } else {
            alert(res?.message || 'Failed to extend stay');
        }
    },

    _getReceptionBuckets() {
        return {
            'all': this.receptionRequests,
            'checked-in': this.receptionRequests.filter(r => ['CHECKIN_APPROVED','check_in','ACTIVE','guests','staying'].includes(r.status)),
            'checked-out': this.receptionRequests.filter(r => ['CHECKED_OUT','CHECKOUT_APPROVED','check_out','checked-out'].includes(r.status))
        };
    },

    _updateStatusCounts() {
        const allBuckets = this._getReceptionBuckets();
        Object.keys(allBuckets).forEach(k => {
            document.querySelectorAll(`.rec-count-${k}`).forEach(e => {
                e.textContent = allBuckets[k].length;
            });
        });
        return allBuckets;
    },

    _filterReception() {
        let list = [...this.receptionRequests];
        if (this.receptionSearch) {
            const q = this.receptionSearch.toLowerCase();
            list = list.filter(r => 
                (r.guestName||'').toLowerCase().includes(q) || 
                (r.phone||'').includes(q) || 
                (r.faydaId||'').includes(q) || 
                (r.roomNumber||'').includes(q)
            );
        }
        const buckets = {
            'checked-in': ['CHECKIN_APPROVED','check_in','ACTIVE','guests','staying'],
            'checked-out': ['CHECKED_OUT','CHECKOUT_APPROVED','check_out','checked-out']
        };
        if (this.receptionFilter !== 'all') {
            list = list.filter(r => (buckets[this.receptionFilter]||[]).includes(r.status));
        }
        return list;
    },

    _updateReceptionStats() {
        const revEl = document.getElementById('stat-revenue');
        const approvedEl = document.getElementById('stat-guests');
        const stayEl = document.getElementById('stat-stay');
        
        let totalRevenue = 0;
        let guestsActive = 0;
        let totalStayDuration = 0;
        let stayCount = 0;

        this.receptionRequests.forEach(r => {
            if (r.roomPrice && ['CHECKIN_APPROVED','CHECKED_OUT','ACTIVE'].includes(r.status)) {
                totalRevenue += Number(r.roomPrice);
            }
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
    },

    _updateCheckoutBanner() {
        const banner = document.getElementById('rec-checkout-banner');
        const checkoutAllBtn = document.getElementById('rec-checkout-all-btn');
        const checkedIn = this._getCheckedInGuests();
        const today = new Date().toISOString().slice(0, 10);
        const dueGuests = checkedIn.filter(r => r.checkOut && r.checkOut.slice(0, 10) <= today);

        if (checkoutAllBtn) {
            checkoutAllBtn.classList.toggle('hidden', checkedIn.length === 0);
        }
        if (!banner) return;

        if (dueGuests.length > 0) {
            banner.classList.remove('hidden');
            const textEl = document.getElementById('rec-checkout-banner-text');
            if (textEl) {
                textEl.textContent = `${dueGuests.length} guest(s) due for checkout today: ${dueGuests.map(g => g.guestName || 'Guest').join(', ')}`;
            }
        } else {
            banner.classList.add('hidden');
        }
    },

    _renderReceptionContent() {
        this._updateReceptionStats();
        this._updateCheckoutBanner();
        const container = document.getElementById('rec-cards-container');
        if (!container) return;

        const list = this._filterReception();
        const allBuckets = this._updateStatusCounts();

        if (list.length === 0) {
            container.innerHTML = `<div class="col-span-full py-32 text-center text-gray-700 text-[10px] uppercase tracking-[1em] font-bold">No guests found</div>`;
            return;
        }

        container.innerHTML = list.map(r => this._renderGuestCard(r, {
            showReceptionActions: r.status === 'CHECKIN_APPROVED'
        })).join('');
        lucide.createIcons();
    },

    _setReceptionPeriod(p) {
        this.receptionPeriod = p;
        if (p !== 'specific') {
            this.receptionStartDate = '';
            this.receptionEndDate = '';
        }
        this._renderPanel(); 
        this.fetchQueueData();
    },

    _applySpecificDate(val) {
        this.receptionStartDate = val;
        this.receptionEndDate = val; // Sync for single day business day range
        this.fetchQueueData();
    },

    _renderGuestCard(r, options = {}) {
        const badgeClass = this._getStatusBadgeClass(r.status);
        const needsApproval = this._needsAdminAction(r.status);
        const isAdmin = window.USER_ROLE === 'admin';
        const showActions = options.showReceptionActions || false;
        const isCheckedIn = r.status === 'CHECKIN_APPROVED';
        const isCheckedOut = this._isCheckedOutStatus(r.status);
        const today = new Date().toISOString().slice(0, 10);
        const checkoutDue = isCheckedIn && r.checkOut && r.checkOut.slice(0, 10) <= today;
        const statusLabel = this._getStatusLabel(r);

        return `
        <div class="bg-[#121413] rounded-2xl border ${checkoutDue ? 'border-orange-500/40' : 'border-[#1a1c1a]'} p-6 hover:border-[#c5a059]/20 transition-all flex flex-col relative overflow-hidden group">
            ${checkoutDue ? '<div class="absolute top-0 left-0 right-0 h-1 bg-orange-500"></div>' : ''}
            <div class="flex justify-between items-start gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gray-900 border border-[#2a2c2a] flex items-center justify-center shrink-0">
                        <img src="api/cashier/image.php?id=${encodeURIComponent(r.id)}&collection=receptionRequests&t=${Date.now()}" 
                             loading="lazy" decoding="async"
                             class="w-full h-full object-cover rounded-xl"
                             onerror="this.parentElement.innerHTML='<i data-lucide=&quot;user&quot; class=&quot;w-5 h-5 text-gray-500&quot;></i>';lucide.createIcons();">
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-sm font-black text-gray-200 uppercase tracking-widest truncate">${r.guestName || 'Guest'}</h4>
                        <p class="text-[10px] uppercase font-bold text-gray-500 tracking-[0.2em] mt-1">${r.inquiryType?.replace(/_/g,' ') || 'CHECK-IN'}</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg border shrink-0 ${badgeClass}">${statusLabel}</span>
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
                    <span class="text-gray-300">${r.checkIn ? `${r.checkIn.slice(0,10)} → ${r.checkOut?.slice(0,10)||'?'}` : `${r.stayDuration || 1} night(s)`}</span>
                </div>
                ${r.roomPrice ? `<div class="flex justify-between items-center border-b border-gray-800 pb-2">
                    <span class="text-gray-500">Revenue</span>
                    <span class="text-[#c5a059] font-bold">${Number(r.roomPrice).toLocaleString()} ETB</span>
                </div>` : ''}
                ${r.status === 'EXTEND_PENDING' && r.pendingExtraDays ? `<div class="text-orange-400 text-[10px] font-bold uppercase tracking-wider">+${r.pendingExtraDays} day(s) requested</div>` : ''}
                ${checkoutDue ? `
                <div class="flex items-center gap-2 bg-orange-500/10 border border-orange-500/30 rounded-lg px-3 py-2 mt-1">
                    <i data-lucide="alarm-clock" class="w-3.5 h-3.5 text-orange-400 shrink-0"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest text-orange-400">Checkout Due Today</span>
                </div>` : ''}
            </div>

            <div class="mt-auto pt-6 flex flex-wrap items-center gap-2">
                <button onclick="AdminServices.viewReceptionDetail('${r.id}')" class="flex-1 min-w-[80px] bg-gray-800 border border-gray-700 text-gray-400 py-2.5 rounded-lg text-[9px] font-black uppercase tracking-widest hover:text-white transition-all">Details</button>
                ${showActions && isCheckedIn ? `
                    <button onclick="AdminServices.requestExtend('${r.id}')" class="flex-1 min-w-[80px] bg-blue-500/10 border border-blue-500/20 text-blue-400 py-2.5 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-blue-500 hover:text-white transition-colors">Extend</button>
                    <button onclick="AdminServices.requestCheckout('${r.id}')" class="flex-1 min-w-[80px] bg-purple-500/10 border border-purple-500/20 text-purple-400 py-2.5 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-purple-500 hover:text-white transition-colors">Checkout</button>
                ` : ''}
                ${isCheckedOut ? `
                    <button onclick="AdminServices.requestRecheckIn('${r.id}')" class="flex-1 min-w-[80px] bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 py-2.5 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-emerald-500 hover:text-white transition-colors">Re-check In</button>
                ` : ''}
                ${isAdmin ? `<button onclick="AdminServices.deleteReceptionItem('${r.id}')" class="px-3 py-2.5 bg-red-500/10 border border-red-500/20 text-red-500 hover:bg-red-500 hover:text-white rounded-lg transition-all"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>` : ''}
            </div>
        </div>`;
    },

    _getStatusLabel(r) {
        const map = {
            'PENDING_APPROVAL': 'Pending',
            'CHECKIN_PENDING': 'Pending',
            'CHECKIN_APPROVED': 'Checked In',
            'CHECKOUT_PENDING': 'Checkout Request',
            'EXTEND_PENDING': 'Extend Request',
            'CHECKED_OUT': 'Checked Out'
        };
        return map[r.status] || r.status.replace(/_/g, ' ');
    },

    _needsAdminAction(status) {
        return ['PENDING_APPROVAL','CHECKIN_PENDING','CHECKOUT_PENDING','EXTEND_PENDING','pending'].includes(status);
    },

    _getStatusDotClass(s) {
        if (['pending','CHECKIN_PENDING','PENDING_APPROVAL'].includes(s)) return 'bg-amber-500';
        if (['CHECKOUT_PENDING','EXTEND_PENDING'].includes(s)) return 'bg-orange-500';
        if (['CHECKIN_APPROVED','check_in','ACTIVE','staying','guests'].includes(s)) return 'bg-emerald-500';
        if (['CHECKED_OUT','CHECKOUT_APPROVED','check_out','checked-out'].includes(s)) return 'bg-purple-500';
        return 'bg-red-500';
    },

    _getStatusBadgeClass(s) {
        if (['pending','CHECKIN_PENDING','PENDING_APPROVAL'].includes(s)) return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
        if (['CHECKOUT_PENDING','EXTEND_PENDING'].includes(s)) return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
        if (['CHECKIN_APPROVED','check_in','ACTIVE','staying','guests'].includes(s)) return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        if (['CHECKED_OUT','CHECKOUT_APPROVED','check_out','checked-out'].includes(s)) return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
        return 'bg-red-500/10 text-red-400 border-red-500/20';
    },

    _isPending(s) {
        return ['pending','PENDING_APPROVAL','CHECKIN_PENDING','CHECKOUT_PENDING','EXTEND_PENDING'].includes(s);
    },

    async actionReception(id, action, reviewNote = '') {
        const req = this.receptionRequests.find(r => r.id === id);
        if (!req) return;
        const status = 'CHECKIN_APPROVED';
        const body = { status };
        if (reviewNote) body.reviewNote = reviewNote;
        const res = await this.api('PUT', `api/reception-requests.php?id=${id}`, body);
        if (res.status === 'success') await this.fetchQueueData();
        else alert(res?.message || 'Action failed');
    },

    async deleteReceptionItem(id) {
        if (!confirm('⚠️ Permanently delete this guest record?')) return;
        const res = await this.api('DELETE', `api/reception-requests.php?id=${id}`);
        if (res.status === 'success') this.fetchQueueData();
    },

    async wipeReception() {
        if (!confirm('⚠️ This will permanently delete ALL reception records. Continue?')) return;
        const res = await this.api('DELETE', 'api/reception-requests.php?action=wipe');
        if (res.status === 'success') this.fetchQueueData();
    },

    async viewReceptionDetail(id) {
        const res = await this.api('GET', `api/reception-requests.php?id=${id}`);
        const r = res.data;
        if (!r) return;
        
        const needsApproval = this._needsAdminAction(r.status);
        const isAdmin = window.USER_ROLE === 'admin';

        document.getElementById('rec-detail-name').textContent = r.guestName || '—';
        document.getElementById('rec-detail-status').textContent = this._getStatusLabel(r);
        document.getElementById('rec-detail-body').innerHTML = `
            <div class="flex items-start gap-6 pb-6 border-b border-gray-800 mb-6">
                <div class="w-24 h-24 rounded-2xl bg-gray-900 border border-gray-700 overflow-hidden shrink-0 shadow-2xl">
                    ${r.profilePhoto ? `<img src="${r.profilePhoto}" class="w-full h-full object-cover">` : `<div class="w-full h-full flex items-center justify-center text-gray-700"><i data-lucide="user" class="w-10 h-10"></i></div>`}
                </div>
                <div class="flex-1 space-y-1">
                    <p class="text-[10px] font-black text-[#c5a059] uppercase tracking-[0.2em]">Guest Profile Verified</p>
                    <h3 class="text-xl font-black text-white uppercase tracking-tight">${r.guestName || 'Unknown Guest'}</h3>
                    <p class="text-xs text-gray-500">${r.inquiryType?.replace(/_/g,' ') || 'WALK-IN CHECK-IN'}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-y-6 gap-x-8 text-sm">
                <div><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Fayda ID (FAN)</p><p class="font-mono text-white text-base">${r.faydaId || '—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Phone Number</p><p class="font-mono text-white text-base">${r.phone || '—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Room Assignment</p><p class="font-black text-white text-lg">${r.roomNumber ? 'Room ' + r.roomNumber : '—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Price Per Night</p><p class="font-mono text-[#c5a059] text-lg">${Number(r.roomPrice||0).toLocaleString()} ETB</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Check In Date</p><p class="text-white font-bold">${r.checkIn ? r.checkIn.slice(0,10) : '—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Check Out Planned</p><p class="text-white font-bold">${r.checkOut ? r.checkOut.slice(0,10) : '—'}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Total Guests</p><p class="text-white font-black text-lg">${r.guests||1}</p></div>
                <div><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Payment Method</p><p class="text-white font-black text-base">${r.paymentMethod||'—'}</p></div>
                
                ${r.receiptNumber ? `<div class="col-span-1"><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-1.5">Transaction ID</p><p class="font-mono text-gray-300">${r.receiptNumber}</p></div>` : ''}
                ${r.transactionUrl ? this._renderReceiptPreviewHtml(r.transactionUrl) : ''}
                
                ${r.notes ? `<div class="col-span-2 bg-gray-900/50 p-4 rounded-xl border border-gray-800"><p class="text-gray-500 text-[9px] uppercase font-black tracking-widest mb-2">Guest Notes</p><p class="text-gray-300 text-xs italic">"${r.notes}"</p></div>` : ''}
            </div>

            <div class="grid grid-cols-2 gap-4 mt-8 pt-8 border-t border-gray-800">
                ${r.idPhotoFront ? `
                <div class="space-y-2">
                    <p class="text-gray-500 text-[9px] uppercase font-black tracking-widest">ID Front Side</p>
                    <div class="h-40 rounded-2xl overflow-hidden border border-gray-700 bg-gray-900">
                        <img src="${r.idPhotoFront}" class="w-full h-full object-cover cursor-zoom-in hover:scale-105 transition-transform" onclick="window.open(this.src)">
                    </div>
                </div>` : ''}
                ${r.idPhotoBack ? `
                <div class="space-y-2">
                    <p class="text-gray-500 text-[9px] uppercase font-black tracking-widest">ID Back Side</p>
                    <div class="h-40 rounded-2xl overflow-hidden border border-gray-700 bg-gray-900">
                        <img src="${r.idPhotoBack}" class="w-full h-full object-cover cursor-zoom-in hover:scale-105 transition-transform" onclick="window.open(this.src)">
                    </div>
                </div>` : ''}
            </div>

            ${isAdmin && needsApproval ? `
            <div class="grid grid-cols-1 mt-8">
                <button onclick="AdminServices.approveReceptionItem('${r.id}'); document.getElementById('rec-detail-modal').classList.add('hidden');" 
                    class="bg-[#c5a059] text-gray-900 py-4 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-[#b59048] transition-all shadow-xl shadow-[#c5a059]/10">Approve</button>
            </div>` : ''}
            ${r.status === 'CHECKIN_APPROVED' ? `
            <div class="grid grid-cols-2 gap-4 mt-8">
                <button onclick="AdminServices.requestExtend('${r.id}'); document.getElementById('rec-detail-modal').classList.add('hidden');" 
                    class="bg-blue-500/10 border border-blue-500/20 text-blue-400 py-4 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-blue-500 hover:text-white transition-all">Extend Stay</button>
                <button onclick="AdminServices.requestCheckout('${r.id}'); document.getElementById('rec-detail-modal').classList.add('hidden');" 
                    class="bg-purple-500/10 border border-purple-500/20 text-purple-400 py-4 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-purple-500 hover:text-white transition-all">Check Out</button>
            </div>` : ''}
            ${this._isCheckedOutStatus(r.status) ? `
            <div class="grid grid-cols-1 mt-8">
                <button onclick="AdminServices.requestRecheckIn('${r.id}'); document.getElementById('rec-detail-modal').classList.add('hidden');" 
                    class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 py-4 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-emerald-500 hover:text-white transition-all">Re-check In</button>
            </div>` : ''}
        `;
        document.getElementById('rec-detail-modal').classList.remove('hidden');
        document.getElementById('rec-detail-id-hidden').value = id;
        lucide.createIcons();
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
