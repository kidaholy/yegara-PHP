/**
 * MenuManager — Reusable Menu CRUD Engine
 * Used by: Services→Standard Menu, vip1-menu.php, vip2-menu.php
 * 
 * Usage:
 *   new MenuManager({ containerId, apiBaseUrl, collection, categoryType, publicMenuUrl }).init()
 */
class MenuManager {
    constructor(config = {}) {
        this.config = {
            containerId: config.containerId || 'menu-manager-root',
            apiBaseUrl: config.apiBaseUrl || 'api/admin/menu.php',
            collection: config.collection || 'menuItems',
            categoryType: config.categoryType || 'menu',
            publicMenuUrl: config.publicMenuUrl || '/public-menu'
        };

        this.state = {
            items: [],
            categories: [],
            distributions: [],
            activeTab: '',            // Auto-detected
            mainCategories: [],       // Auto-detected from items
            searchQuery: '',
            selectedCategory: '',
            selectedDistribution: '',
            page: 1,
            perPage: 20,
            loading: false,
            swapMode: false,
            swapSourceId: null,
            showCategoryManager: false,
            showDistributionManager: false
        };
    }

    // ─── INIT ──────────────────────────────────────────────────────────────────
    async init() {
        this.renderShell();
        await this.loadData();
        this.render();
    }

    renderShell() {
        const el = document.getElementById(this.config.containerId);
        if (!el) return;
        el.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Sidebar -->
            <aside class="md:col-span-4 lg:col-span-3 space-y-4">
                <!-- Actions -->
                <div class="bg-gray-800 p-5 rounded-xl border border-gray-700/50 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Actions</p>
                    <button onclick="AdminServices.openMenuModal()" class="w-full bg-[#c5a059] text-gray-900 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider shadow-sm hover:bg-[#b59048] transition-colors">
                        + Add New Item
                    </button>
                    <div class="flex gap-2">
                        <button onclick="menuMgr.normalize()" class="flex-1 bg-gray-700 border border-gray-600 text-gray-400 hover:text-white py-2 rounded-lg text-xs font-bold uppercase transition-all">Re-index</button>
                        <button onclick="menuMgr.toggleSwap()" id="mm-swap-btn" class="flex-1 bg-gray-700 border border-gray-600 text-gray-400 hover:text-white py-2 rounded-lg text-xs font-bold uppercase transition-all">Swap</button>
                    </div>
                    <div class="relative">
                        <button onclick="menuMgr.toggleExportMenu()" class="w-full bg-gray-700 border border-gray-600 py-2 rounded-lg text-xs font-bold uppercase text-gray-400 hover:text-white transition-all">Export CSV ▾</button>
                        <div id="mm-export-menu" class="hidden absolute top-full mt-1 w-full bg-gray-800 border border-gray-700 rounded-xl overflow-hidden z-20 shadow-xl">
                            <button onclick="menuMgr.exportCSV('Food')" class="block w-full text-left px-4 py-2.5 bg-transparent border-0 text-xs text-gray-400 hover:text-white hover:bg-gray-700">Food Items</button>
                            <button onclick="menuMgr.exportCSV('Drinks')" class="block w-full text-left px-4 py-2.5 bg-transparent border-0 text-xs text-gray-400 hover:text-white hover:bg-gray-700">Drink Items</button>
                            <button onclick="menuMgr.exportCSV()" class="block w-full text-left px-4 py-2.5 bg-transparent border-0 text-xs text-gray-400 hover:text-white hover:bg-gray-700">Complete Menu</button>
                        </div>
                    </div>
                    <button onclick="menuMgr.showMenuQR()" class="w-full bg-[#c5a059]/10 border border-[#c5a059]/30 text-[#c5a059] py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-[#c5a059]/20 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="qr-code" class="w-4 h-4"></i> Menu QR
                    </button>
                </div>
                <!-- Filters -->
                <div class="bg-gray-800 p-5 rounded-xl border border-gray-700/50 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-1">Filters</p>
                    <input type="text" placeholder="Search name, id, category…"
                        oninput="menuMgr.state.searchQuery=this.value;menuMgr.state.page=1;menuMgr.render()"
                        class="w-full bg-gray-900 border border-gray-700 text-sm text-gray-200 py-2.5 px-3.5 rounded-lg outline-none focus:border-[#c5a059] transition-colors">
                    <select onchange="menuMgr.state.selectedCategory=this.value;menuMgr.state.page=1;menuMgr.render()"
                        id="mm-cat-filter" class="w-full bg-gray-900 border border-gray-700 text-sm text-gray-300 py-2.5 px-3.5 rounded-lg outline-none appearance-none focus:border-[#c5a059] transition-colors">
                        <option value="">All Categories</option>
                    </select>
                    <select onchange="menuMgr.state.selectedDistribution=this.value;menuMgr.state.page=1;menuMgr.render()"
                        id="mm-dist-filter" class="w-full bg-gray-900 border border-gray-700 text-sm text-gray-300 py-2.5 px-3.5 rounded-lg outline-none appearance-none focus:border-[#c5a059] transition-colors">
                        <option value="">All Distributions</option>
                    </select>
                </div>
            </aside>
            <!-- Main panel -->
            <div class="md:col-span-8 lg:col-span-9 space-y-5">
                <!-- Tab header -->
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <h2 class="text-lg font-bold text-gray-200">Menu Items</h2>
                    <div class="flex gap-2" id="mm-tabs-container">
                        <!-- Dynamic tabs -->
                    </div>
                </div>
                <!-- Item grid -->
                <div id="mm-items-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
                <!-- Pagination -->
                <div id="mm-pagination" class="flex items-center justify-center gap-3 pt-4"></div>
            </div>
        </div>`;

        // Set alias for easy inline calls
        window.menuMgr = this;
    }

    // ─── DATA ──────────────────────────────────────────────────────────────────
    async loadData() {
        this.state.loading = true;
        try {
            const [menuRes, catRes, distRes, stockRes] = await Promise.all([
                this._api('GET', `${this.config.apiBaseUrl}?collection=${this.config.collection}&excludeImages=true&t=${Date.now()}`),
                this._api('GET', `api/categories.php?type=${this.config.categoryType}`),
                this._api('GET', `api/categories.php?type=distribution`),
                this._api('GET', `api/stock.php?availableOnly=false`)
            ]);
            this.state.items = menuRes.data || [];
            this.state.categories = Array.isArray(catRes) ? catRes : (catRes.data || []);
            this.state.distributions = Array.isArray(distRes) ? distRes : (distRes.data || []);
            this.state.stocks = Array.isArray(stockRes) ? stockRes : [];
        } catch(e) { console.error('MenuManager load error', e); }
        this.state.loading = false;
    }

    // ─── RENDER ────────────────────────────────────────────────────────────────
    render() {
        this._renderTabs();
        this._renderFilters();
        this._renderItems();
        this._renderPagination();
        lucide.createIcons();
    }

    _renderTabs() {
        const container = document.getElementById('mm-tabs-container');
        if (!container) return;

        // Detect all active main categories
        const cats = [...new Set(this.state.items.map(i => i.mainCategory || 'Food'))].sort();
        this.state.mainCategories = cats;

        // Set default tab if none active
        if (!this.state.activeTab || (this.state.activeTab !== 'all' && !cats.includes(this.state.activeTab))) {
            this.state.activeTab = cats.includes('Food') ? 'Food' : (cats[0] || 'all');
        }

        const allCats = ['all', ...cats];
        
        container.innerHTML = allCats.map(cat => {
            const count = cat === 'all' ? this.state.items.length : this.state.items.filter(i => i.mainCategory === cat).length;
            const on = this.state.activeTab === cat;
            const label = cat === 'all' ? 'All Items' : cat;
            return `
                <button onclick="menuMgr.setActiveTab('${cat}')" 
                    class="mm-tab px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider border transition-all ${on ? 'bg-[#c5a059]/15 text-[#c5a059] border-[#c5a059]/30' : 'border-gray-600 text-gray-500 hover:text-gray-200 hover:border-gray-500'}">
                    ${label} <span class="opacity-50">(${count})</span>
                </button>
            `;
        }).join('');
    }

    _renderFilters() {
        const catSel = document.getElementById('mm-cat-filter');
        const distSel = document.getElementById('mm-dist-filter');
        if (!catSel) return;
        catSel.innerHTML = '<option value="">All Categories</option>' + 
            this.state.categories.map(c => `<option value="${c.name}" ${this.state.selectedCategory === c.name ? 'selected' : ''}>${c.name}</option>`).join('');
        distSel.innerHTML = '<option value="">All Distributions</option>' +
            this.state.distributions.map(d => `<option value="${d.name}" ${this.state.selectedDistribution === d.name ? 'selected' : ''}>${d.name}</option>`).join('');
    }

    _getFiltered() {
        let list = this.state.items;
        if (this.state.activeTab !== 'all') {
            list = list.filter(i => i.mainCategory === this.state.activeTab);
        }
        
        if (this.state.searchQuery) {
            const q = this.state.searchQuery.toLowerCase();
            list = list.filter(i => (i.name||'').toLowerCase().includes(q) || (i.menuId||'').toString().includes(q) || (i.category||'').toLowerCase().includes(q));
        }
        if (this.state.selectedCategory) list = list.filter(i => i.category === this.state.selectedCategory);
        if (this.state.selectedDistribution) list = list.filter(i => (i.distributions||[]).includes(this.state.selectedDistribution));
        return list;
    }

    _renderItems() {
        const grid = document.getElementById('mm-items-grid');
        if (!grid) return;
        const filtered = this._getFiltered();
        const start = (this.state.page - 1) * this.state.perPage;
        const paged = filtered.slice(start, start + this.state.perPage);

        if (paged.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-24 text-center text-gray-700 text-[10px] uppercase tracking-[1em] font-bold">No items found</div>';
            return;
        }

        grid.innerHTML = paged.map(item => {
            const isSwapSource = this.state.swapMode && this.state.swapSourceId === item.id;
            return `
            <div class="bg-gray-800 rounded-xl border ${isSwapSource ? 'border-purple-500/50 ring-2 ring-purple-500/30' : 'border-gray-700/50'} overflow-hidden group hover:border-gray-600 transition-all">
                ${item.hasImage !== false ? `<div class="h-28 overflow-hidden bg-gray-900">
                    <img src="api/cashier/image.php?id=${encodeURIComponent(item.id)}&collection=${encodeURIComponent(this.config.collection)}" 
                         loading="lazy" decoding="async"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" 
                         alt="${item.name}"
                         onerror="this.parentElement.innerHTML='<div class=&quot;h-full bg-gradient-to-r from-[#c5a059]/10 to-transparent flex items-center justify-center text-gray-700&quot;><i data-lucide=&quot;image-off&quot; class=&quot;w-6 h-6&quot;></i></div>';lucide.createIcons();">
                </div>` : `<div class="h-10 bg-gradient-to-r from-[#c5a059]/10 to-transparent"></div>`}
                <div class="p-4 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-gray-200 truncate">${item.name}</p>
                            <p class="text-xs text-gray-500">${item.category || 'General'}</p>
                        </div>
                        <span class="text-xs font-bold text-[#c5a059] bg-[#c5a059]/10 px-2 py-0.5 rounded-lg shrink-0">#${item.menuId || '?'}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-[#c5a059] font-mono">${Number(item.price).toLocaleString()} Br</span>
                        <div class="flex items-center gap-2">
                            ${(() => {
                                if (!item.stockItemId) return '';
                                const st = this.state.stocks.find(s => s.id === item.stockItemId);
                                if (!st) return '';
                                const qty = parseFloat(st.quantity || 0);
                                const status = st.status || 'available';
                                const color = (status === 'finished' || status === 'out_of_stock' || qty <= 0) ? 'bg-red-500' : (qty < 10 ? 'bg-orange-500' : 'bg-emerald-500');
                                return `
                                    <div class="flex items-center gap-1 px-2 py-0.5 bg-gray-700 rounded-lg border border-gray-600">
                                        <span class="w-1.5 h-1.5 rounded-full ${color}"></span>
                                        <span class="text-xs font-bold text-gray-400">${qty} ${st.unit || ''}</span>
                                    </div>
                                `;
                            })()}
                            <span class="w-2 h-2 rounded-full ${item.available !== false ? 'bg-emerald-500' : 'bg-red-500'}"></span>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-2 border-t border-gray-700/50 opacity-0 group-hover:opacity-100 transition-all">
                        ${this.state.swapMode ? `
                        <button onclick="menuMgr.handleSwap('${item.id}')" class="flex-1 py-1.5 text-xs font-bold uppercase text-purple-400 bg-purple-500/10 rounded-lg border border-purple-500/20">
                            ${isSwapSource ? 'Selected' : 'Swap ID'}
                        </button>` : `
                        <button onclick='AdminServices.openMenuModal(${JSON.stringify(item).replace(/'/g,"&#39;")})' class="flex-1 py-1.5 text-xs font-bold uppercase text-gray-400 hover:text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors">Edit</button>
                        <button onclick="menuMgr.deleteItem('${item.id}')" class="py-1.5 px-3 text-xs text-red-400 hover:text-red-300 bg-red-500/10 rounded-lg hover:bg-red-500/20 transition-colors">
                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                        </button>`}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    _renderPagination() {
        const el = document.getElementById('mm-pagination');
        if (!el) return;
        const total = this._getFiltered().length;
        const pages = Math.ceil(total / this.state.perPage);
        if (pages <= 1) { el.innerHTML = ''; return; }

        let html = `<span class="text-xs text-gray-500 font-bold">${total} items</span>`;
        for (let i = 1; i <= pages; i++) {
            html += `<button onclick="menuMgr.setPage(${i})" class="w-8 h-8 rounded-lg text-xs font-bold ${i === this.state.page ? 'bg-[#c5a059] text-gray-900' : 'bg-gray-700 text-gray-400 hover:text-white hover:bg-gray-600'} transition-colors">${i}</button>`;
        }
        el.innerHTML = html;
    }

    // ─── ACTIONS ───────────────────────────────────────────────────────────────
    setActiveTab(tab) { this.state.activeTab = tab; this.state.page = 1; this.render(); }
    setPage(p) { this.state.page = p; this.render(); }

    toggleSwap() {
        this.state.swapMode = !this.state.swapMode;
        this.state.swapSourceId = null;
        const btn = document.getElementById('mm-swap-btn');
        if (btn) {
            btn.classList.toggle('bg-purple-500/20', this.state.swapMode);
            btn.classList.toggle('text-purple-400', this.state.swapMode);
            btn.textContent = this.state.swapMode ? 'Cancel Swap' : 'Swap';
        }
        this.render();
    }

    async handleSwap(itemId) {
        if (!this.state.swapSourceId) {
            this.state.swapSourceId = itemId;
            this.render();
            return;
        }
        // Execute swap
        const src = this.state.items.find(i => i.id === this.state.swapSourceId);
        const tgt = this.state.items.find(i => i.id === itemId);
        if (!src || !tgt) return;

        await this._api('POST', `${this.config.apiBaseUrl}?action=swap&collection=${this.config.collection}`, {
            menuId1: src.menuId,
            menuId2: tgt.menuId
        });

        this.state.swapMode = false;
        this.state.swapSourceId = null;
        await this.loadData();
        this.render();
    }

    async normalize() {
        await this._api('POST', `${this.config.apiBaseUrl}?action=normalize&collection=${this.config.collection}`);
        await this.loadData();
        this.render();
    }

    async deleteItem(id) {
        if (!confirm('Delete this menu item?')) return;
        await this._api('DELETE', `${this.config.apiBaseUrl}?id=${id}&collection=${this.config.collection}`);
        this.state.items = this.state.items.filter(i => i.id !== id);
        this.render();
    }

    toggleExportMenu() {
        document.getElementById('mm-export-menu')?.classList.toggle('hidden');
    }

    exportCSV(mainCat = null) {
        document.getElementById('mm-export-menu')?.classList.add('hidden');
        let list = [...this.state.items];
        if (mainCat) list = list.filter(i => i.mainCategory === mainCat);

        const header = 'Menu ID,Name,Main Category,Category,Price,Available,Description';
        const rows = list.map(i => [
            i.menuId, `"${(i.name||'').replace(/"/g,'""')}"`, i.mainCategory, `"${i.category||''}"`,
            i.price, i.available !== false ? 'Yes' : 'No', `"${(i.description||'').replace(/"/g,'""')}"`
        ].join(','));
        const csv = '\uFEFF' + header + '\n' + rows.join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        const label = mainCat ? mainCat.toLowerCase() : 'complete';
        const d = new Date();
        const Y = d.getFullYear(), M = String(d.getMonth()+1).padStart(2,'0'), D = String(d.getDate()).padStart(2,'0');
        a.download = `${label}_export_${Y}-${M}-${D}.csv`;
        a.click();
    }

    showMenuQR() {
        // Build URL for public menu
        let url = `${location.origin}/menu.php`;
        if (this.config.collection !== 'menuItems') {
            // It's a VIP tier
            const tierId = this.config.collection.replace('Menu', '').toLowerCase();
            url += `?tier=${tierId}`;
        }
        
        if (typeof AdminServices.openMenuQRModal === 'function') {
            AdminServices.openMenuQRModal(url);
        } else {
            console.warn('AdminServices.openMenuQRModal not found');
        }
    }

    // ─── API Helper ────────────────────────────────────────────────────────────
    _api(method, url, data) {
        const opt = { method, headers: { 'Content-Type': 'application/json' } };
        if (data) opt.body = JSON.stringify(data);
        return fetch(url, opt).then(r => r.json()).catch(() => ({}));
    }
}
