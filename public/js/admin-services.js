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
    receptionRequests: [],
    receptionFilter: 'all',       // all | pending | checked-in | denied | checked-out
    receptionSearch: '',
    receptionDateFilter: 'all',   // all | today | week | year | custom
    receptionCustomDate: '',
    prevReceptionCount: 0,

    // Room Orders
    roomOrders: [],
    prevOrdersCount: 0,

    menuManager: null,
    pollingTimer: null,
    audio: new Audio('/notification.mp3'),

    // ─── INIT ──────────────────────────────────────────────────────────────────
    async init() {
        this.setTab('menu-standard');
        this.fetchQueueData();
        this.pollingTimer = setInterval(() => this.fetchQueueData(), 15000);
        lucide.createIcons();
    },

    // ─── POLLING ───────────────────────────────────────────────────────────────
    async fetchQueueData() {
        try {
            const [recRes, ordRes] = await Promise.all([
                this.api('GET', 'api/reception-requests.php?limit=500'),
                this.api('GET', 'api/room-orders.php')
            ]);

            const recs = Array.isArray(recRes) ? recRes : (recRes.data || []);
            const ords = ordRes.data || [];

            const pendingCount = recs.filter(r => this._isPending(r.status)).length;
            const ordCount = ords.length;

            if (pendingCount > this.prevReceptionCount || ordCount > this.prevOrdersCount) {
                this._playAlert();
            }

            this.prevReceptionCount = pendingCount;
            this.prevOrdersCount = ordCount;
            this.receptionRequests = recs;
            this.roomOrders = ords;

            this._updateBadges(ordCount);
            if (this.activeTab === 'reception') this._renderReceptionContent();
            if (this.activeTab === 'room-orders') this._renderOrdersContent();
        } catch (e) { console.warn('Poll error', e); }
    },

    _playAlert() {
        let n = 0;
        const t = () => { if (n++ < 5) { this.audio.play().catch(()=>{}); setTimeout(t, 1500); } };
        t();
    },

    _updateBadges(count) {
        const b = document.getElementById('tab-badge-orders');
        if (!b) return;
        b.textContent = count;
        b.classList.toggle('hidden', count === 0);
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
        if (tab === 'reception') this.fetchQueueData();
        if (tab === 'room-orders') this.fetchQueueData();
    },

    _renderPanel() {
        const panel = document.getElementById('services-content-panel');
        const map = {
            'rooms': () => this._buildRoomsHTML(),
            'menu-standard': () => `<div id="menu-manager-root"></div>`,
            'vip': () => this._buildVipHTML(),
            'reception': () => this._buildReceptionShellHTML(),
            'room-orders': () => this._buildOrdersHTML()
        };
        panel.innerHTML = `<div class="tab-content-anim">${(map[this.activeTab] || (() => ''))()}</div>`;
        lucide.createIcons();
        if (this.activeTab === 'reception') this._renderReceptionContent();
        if (this.activeTab === 'room-orders') this._renderOrdersContent();
    },

    // ─── TAB 1: ROOMS ──────────────────────────────────────────────────────────
    _buildRoomsHTML() {
        const floorsWithRooms = this.floors.filter(f => this.rooms.some(r => r.floorId === f.id));
        return `
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Sidebar -->
            <aside class="lg:col-span-3 space-y-4 lg:sticky lg:top-6 h-fit">
                <div class="bg-gray-800 p-5 rounded-xl border border-gray-700/50 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Rooms · ${this.rooms.length} Units</p>
                    <button onclick="AdminServices.openRoomModal()" class="w-full bg-[#c5a059] text-gray-900 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm hover:bg-[#b59048] transition-colors">
                        + Add Room
                    </button>
                    <button onclick="AdminServices.fetchRoomsData()" class="w-full bg-gray-700 border border-gray-600 text-gray-400 hover:text-white py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition flex items-center justify-center gap-2">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Refresh
                    </button>
                </div>
                <!-- Legend -->
                <div class="bg-gray-800 p-4 rounded-xl border border-gray-700/50 space-y-2.5">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Status Legend</p>
                    <div class="space-y-2 text-xs">
                        <div class="flex gap-2 items-center"><div class="w-2 h-2 rounded-full bg-emerald-500"></div><span class="text-gray-400">Available</span></div>
                        <div class="flex gap-2 items-center"><div class="w-2 h-2 rounded-full bg-red-500"></div><span class="text-gray-400">Occupied</span></div>
                        <div class="flex gap-2 items-center"><div class="w-2 h-2 rounded-full bg-amber-500"></div><span class="text-gray-400">Maintenance / Dirty</span></div>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <main class="lg:col-span-9 space-y-10">
                ${floorsWithRooms.length === 0 ? '<div class="py-32 text-center text-gray-600 text-sm uppercase tracking-widest font-bold">No rooms added yet</div>' :
                floorsWithRooms.map(f => {
                    const fr = this.rooms.filter(r => r.floorId === f.id);
                    return `
                    <div>
                        <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-700/50">
                            <div class="flex items-center gap-3">
                                <h3 class="text-base font-bold text-gray-200">Floor ${f.floorNumber}</h3>
                                ${f.isVIP ? '<span class="text-xs font-bold uppercase bg-[#c5a059]/10 text-[#c5a059] border border-[#c5a059]/30 px-2.5 py-0.5 rounded-full">VIP FLOOR</span>' : ''}
                                <span class="text-xs text-gray-500">${fr.length} rooms</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-500">Handler:</span>
                                <select onchange="AdminServices.assignFloorCashier('${f.id}', this.value)"
                                    class="bg-gray-700 border border-gray-600 rounded-lg py-1.5 px-3 text-xs text-gray-300 outline-none focus:border-[#c5a059]">
                                    <option value="">Unassigned</option>
                                    ${this.cashiers.map(c => `<option value="${c.id}" ${f.roomServiceCashierId === c.id ? 'selected' : ''}>${c.name}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            ${fr.map(r => this._roomCard(r)).join('')}
                        </div>
                    </div>`;
                }).join('')}
            </main>
        </div>`;
    },

    _roomCard(r) {
        const statusDot = r.status === 'available' ? 'bg-emerald-500' : r.status === 'occupied' ? 'bg-red-500' : 'bg-amber-500';
        const tierBadge = r.roomServiceMenuTier && r.roomServiceMenuTier !== 'standard'
            ? `<span class="text-xs font-bold uppercase bg-purple-500/10 text-purple-400 border border-purple-500/20 px-2 py-0.5 rounded-full">${r.roomServiceMenuTier.toUpperCase()}</span>` : '';
        return `
        <div class="bg-gray-800 p-5 rounded-xl border border-gray-700/50 group hover:border-gray-600 transition-all relative overflow-hidden">
            <div class="flex justify-between items-start mb-3">
                <div class="w-10 h-10 rounded-lg bg-gray-700 flex items-center justify-center text-gray-200 font-bold text-base">${r.roomNumber}</div>
                <div class="flex flex-col items-end gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-full ${statusDot}"></div>
                    ${tierBadge}
                </div>
            </div>
            <p class="text-sm font-bold text-gray-200 mb-0.5 truncate">${r.category || 'Room'}</p>
            <p class="text-xs text-gray-500 font-mono">${Number(r.price).toLocaleString()} Br</p>
            <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-700/50 opacity-0 group-hover:opacity-100 transition-all">
                <button onclick='AdminServices.openRoomModal(${JSON.stringify(r).replace(/'/g, "&#39;")})' class="text-gray-500 hover:text-gray-200 transition-colors" title="Edit">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </button>
                <button onclick="AdminServices.openQRModal('${r.roomNumber}')" class="text-gray-500 hover:text-[#c5a059] transition-colors" title="QR Code">
                    <i data-lucide="qr-code" class="w-4 h-4"></i>
                </button>
                <button onclick="AdminServices.deleteRoom('${r.id}')" class="text-gray-500 hover:text-red-400 transition-colors" title="Delete">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        </div>`;
    },

    async fetchRoomsData() {
        try {
            const [rr, fr, ur] = await Promise.all([
                this.api('GET', 'api/admin/rooms.php'),
                this.api('GET', 'api/admin/floors.php'),
                this.api('GET', 'api/users.php')
            ]);
            this.rooms = rr.data || [];
            this.floors = fr.data || [];
            this.cashiers = (ur.data || []).filter(u => ['admin','cashier'].includes(u.role));
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
    _buildVipHTML() {
        return `
        <div class="flex flex-col items-center justify-center py-20 gap-12">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-200 tracking-tight">VIP Menus</h2>
                <p class="text-xs uppercase tracking-wider text-gray-500 mt-2 font-bold">Curated for Premium Guests</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-2xl">
                <a href="vip1-menu.php" class="bg-gray-800 border border-gray-700/50 rounded-xl p-8 text-center group hover:border-[#c5a059]/40 hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto bg-gray-700 rounded-xl flex items-center justify-center text-[#c5a059] mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="wine" class="w-8 h-8"></i>
                    </div>
                    <h4 class="text-lg font-bold text-gray-200 mb-1">VIP 1</h4>
                    <p class="text-xs uppercase font-bold tracking-wider text-gray-500">Open Manager →</p>
                </a>
                <a href="vip2-menu.php" class="bg-gray-800 border border-gray-700/50 rounded-xl p-8 text-center group hover:border-[#c5a059]/40 hover:-translate-y-1 transition-all duration-300">
                    <div class="w-16 h-16 mx-auto bg-gray-700 rounded-xl flex items-center justify-center text-[#c5a059] mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="chef-hat" class="w-8 h-8"></i>
                    </div>
                    <h4 class="text-lg font-bold text-gray-200 mb-1">VIP 2</h4>
                    <p class="text-xs uppercase font-bold tracking-wider text-gray-500">Open Manager →</p>
                </a>
            </div>
        </div>`;
    },

    // ─── TAB 3: RECEPTION ──────────────────────────────────────────────────────
    _buildReceptionShellHTML() {
        return `
        <div class="space-y-6">
            <!-- Search & Filters Row -->
            <div class="flex flex-wrap gap-3 items-center">
                <input type="text" id="rec-search" placeholder="Search name, phone, Fayda ID, room..."
                    oninput="AdminServices.receptionSearch = this.value; AdminServices._renderReceptionContent()"
                    class="flex-1 min-w-[200px] bg-gray-800 border border-gray-700 rounded-lg py-2.5 px-4 text-sm text-gray-200 outline-none focus:border-[#c5a059] transition-colors">
                <!-- Date Pills -->
                <div class="flex gap-2 flex-wrap" id="rec-date-pills">
                    ${['all','today','week','year'].map(d => `
                    <button onclick="AdminServices._setDateFilter('${d}')"
                        class="rec-date-pill text-xs font-bold uppercase tracking-wider px-4 py-2 rounded-lg border transition-all ${d === 'all' ? 'bg-[#c5a059] text-gray-900 border-[#c5a059]' : 'border-gray-600 text-gray-500 hover:text-gray-200 hover:border-gray-500'}"
                        data-date="${d}">${d === 'all' ? 'All Time' : d.charAt(0).toUpperCase()+d.slice(1)}</button>
                    `).join('')}
                </div>
                <div class="ml-auto flex gap-3">
                    <button onclick="AdminServices.wipeReception()" class="text-xs font-bold uppercase tracking-wider text-red-500/60 hover:text-red-400 transition-colors">Wipe All</button>
                    <button onclick="AdminServices.fetchQueueData()" class="text-gray-500 hover:text-gray-200 transition-colors">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <!-- Status Filter Pills -->
            <div class="flex gap-2 flex-wrap" id="rec-status-pills">
                ${[
                    {key:'all', label:'Guests'},
                    {key:'pending', label:'Pending'},
                    {key:'checked-in', label:'Checked In'},
                    {key:'denied', label:'Denied'},
                    {key:'checked-out', label:'Checked Out'}
                ].map(s => `
                <button onclick="AdminServices._setStatusFilter('${s.key}')"
                    class="rec-status-pill text-xs font-bold uppercase tracking-wider px-4 py-2 rounded-lg border transition-all ${s.key === 'all' ? 'bg-[#c5a059]/15 text-[#c5a059] border-[#c5a059]/30' : 'border-gray-600 text-gray-500 hover:text-gray-200 hover:border-gray-500'}"
                    data-status="${s.key}">${s.label} <span class="rec-count-${s.key} opacity-60"></span></button>
                `).join('')}
            </div>
            <!-- Cards container -->
            <div id="rec-cards-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"></div>
        </div>`;
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
            b.classList.toggle('bg-[#c5a059]/15', on);
            b.classList.toggle('text-[#c5a059]', on);
            b.classList.toggle('border-[#c5a059]/30', on);
            b.classList.toggle('text-gray-500', !on);
            b.classList.toggle('border-gray-600', !on);
        });
        this._renderReceptionContent();
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
            const el = document.querySelector(`.rec-count-${k}`);
            if (el) el.textContent = allBuckets[k].length ? `(${allBuckets[k].length})` : '';
        });

        if (list.length === 0) {
            container.innerHTML = '<div class="col-span-full py-32 text-center text-gray-700 uppercase tracking-[1em] text-[10px] font-bold">No requests match your filters</div>';
            return;
        }

        container.innerHTML = list.map(r => {
            const dotClass = this._getStatusDotClass(r.status);
            const badgeClass = this._getStatusBadgeClass(r.status);
            const pending = this._isPending(r.status);
            return `
            <div class="bg-gray-800 rounded-xl border border-gray-700/50 relative overflow-hidden hover:border-gray-600 transition-all">
                <div class="h-1 w-full ${dotClass}"></div>
                <div class="p-5 space-y-4">
                    <div class="flex justify-between items-start gap-4">
                        <div class="min-w-0">
                            <h4 class="text-sm font-bold text-gray-200 truncate">${r.guestName || 'Guest'}</h4>
                            <p class="text-xs text-gray-500 mt-0.5">${r.inquiryType || 'Check-in'}</p>
                        </div>
                        <span class="text-xs font-bold uppercase px-2.5 py-1 rounded-lg border whitespace-nowrap shrink-0 ${badgeClass}">${r.status.replace(/_/g,' ')}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                        <div><span class="text-gray-500 mr-1">Room</span><span class="text-gray-200 font-bold">${r.roomNumber || '—'}</span></div>
                        <div><span class="text-gray-500 mr-1">Guests</span><span class="text-gray-200 font-bold">${r.guests || 1}</span></div>
                        <div class="col-span-2"><span class="text-gray-500 mr-1">Phone</span><span class="text-gray-200 font-mono">${r.phone || '—'}</span></div>
                        ${r.checkIn ? `<div class="col-span-2"><span class="text-gray-500 mr-1">Stay</span><span class="text-gray-200 font-mono">${r.checkIn?.slice(0,10)} → ${r.checkOut?.slice(0,10)||'?'}</span></div>` : ''}
                        ${r.roomPrice ? `<div class="col-span-2"><span class="text-gray-500 mr-1">Price</span><span class="text-[#c5a059] font-bold font-mono">${Number(r.roomPrice).toLocaleString()} ETB</span></div>` : ''}
                    </div>
                    <div class="flex gap-2 pt-1">
                        ${pending ? `
                        <button onclick="AdminServices.actionReception('${r.id}','deny')"
                            class="flex-1 py-2 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 text-xs font-bold uppercase hover:bg-red-500/20 transition-all">Deny</button>
                        <button onclick="AdminServices.actionReception('${r.id}','approve')"
                            class="flex-1 py-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold uppercase hover:bg-emerald-500/20 transition-all">Approve</button>
                        ` : `
                        <button onclick="AdminServices.viewReceptionDetail('${r.id}')"
                            class="w-full py-2 rounded-lg bg-gray-700 border border-gray-600 text-gray-300 text-xs font-bold uppercase hover:bg-gray-600 transition-all">View Details</button>
                        `}
                    </div>
                </div>
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

    // ─── TAB 4: ROOM ORDERS ────────────────────────────────────────────────────
    _buildOrdersHTML() {
        return `<div id="orders-container" class="space-y-10"></div>`;
    },

    _renderOrdersContent() {
        const c = document.getElementById('orders-container');
        if (!c) return;
        if (this.roomOrders.length === 0) {
            c.innerHTML = `
            <div class="flex flex-col items-center justify-center py-40 gap-4 text-center">
                <div class="w-16 h-16 rounded-3xl bg-white/3 flex items-center justify-center text-gray-700">
                    <i data-lucide="inbox" class="w-8 h-8"></i>
                </div>
                <p class="text-[10px] uppercase tracking-[1em] text-gray-700 font-bold">Queue Empty</p>
            </div>`;
            lucide.createIcons();
            return;
        }
        c.innerHTML = `
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Room Service Queue</h3>
            <span class="bg-red-500/15 text-red-400 border border-red-500/20 text-xs font-bold px-3 py-1 rounded-lg">${this.roomOrders.length} Awaiting</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            ${this.roomOrders.map(o => `
            <div class="bg-gray-800 rounded-xl border border-gray-700/50 relative overflow-hidden">
                <div class="h-1 w-full bg-[#c5a059]"></div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-5">
                        <div class="w-12 h-12 rounded-lg bg-gray-700 flex items-center justify-center text-gray-200 font-bold text-lg">${o.tableNumber}</div>
                        <div>
                            <p class="text-base font-bold text-gray-200">Room ${o.tableNumber}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Floor ${o.floorNumber || '—'} · ${new Date(o.createdAt).toLocaleTimeString()}</p>
                        </div>
                    </div>
                    <div class="space-y-2.5 mb-5">
                        ${(o.items||[]).map(i => `
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-mono text-xs text-[#c5a059] bg-[#c5a059]/10 px-2 py-0.5 rounded mr-2">${i.quantity}×</span>
                            <span class="flex-1 text-gray-300 font-medium">${i.name}</span>
                            <span class="text-gray-500 font-mono text-xs">${((i.price||0)*(i.quantity||1)).toLocaleString()}</span>
                        </div>`).join('')}
                        <div class="pt-3 border-t border-gray-700/50 flex justify-between items-center">
                            <span class="text-xs font-bold text-gray-500 uppercase">Total</span>
                            <span class="text-lg font-bold text-[#c5a059] font-mono">${Number(o.totalAmount||0).toLocaleString()} Br</span>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="AdminServices.actionOrder('${o.id}','cancelled')"
                            class="flex-1 py-2.5 bg-red-500/10 text-red-400 border border-red-500/20 rounded-lg text-xs font-bold uppercase hover:bg-red-500/20 transition-all">Deny</button>
                        <button onclick="AdminServices.actionOrder('${o.id}','pending')"
                            class="flex-1 py-2.5 bg-[#c5a059] text-gray-900 rounded-lg text-xs font-bold uppercase hover:bg-[#b59048] transition-colors shadow-sm">Send to Kitchen</button>
                    </div>
                </div>
            </div>`).join('')}
        </div>`;
        lucide.createIcons();
    },

    async actionOrder(id, status) {
        if (status === 'cancelled' && !confirm('Cancel this room-service order?')) return;
        await this.api('PUT', `api/orders.php?id=${id}`, { status });
        await this.fetchQueueData();
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
