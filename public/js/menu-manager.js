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
            activeTab: 'Food',        // Food | Drinks
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
        <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Sidebar -->
            <aside class="md:col-span-4 lg:col-span-3 space-y-5">
                <!-- Actions -->
                <div class="glass p-5 rounded-[2rem] border border-white/5 space-y-3">
                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#d4af37]/50 mb-2">Actions</p>
                    <button onclick="AdminServices.openMenuModal()" class="w-full bg-gradient-to-r from-[#d4af37] to-[#f3cf7a] text-black py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl hover:scale-[1.02] transition-transform">
                        + Add New Item
                    </button>
                    <div class="flex gap-2">
                        <button onclick="menuMgr.normalize()" class="flex-1 bg-white/5 border border-white/5 text-white/40 hover:text-white py-3 rounded-xl text-[9px] font-black uppercase transition-all">Re-index</button>
                        <button onclick="menuMgr.toggleSwap()" id="mm-swap-btn" class="flex-1 bg-white/5 border border-white/5 text-white/40 hover:text-white py-3 rounded-xl text-[9px] font-black uppercase transition-all">Swap</button>
                    </div>
                    <div class="relative">
                        <button onclick="menuMgr.toggleExportMenu()" class="w-full bg-white/5 border border-white/5 py-3 rounded-xl text-[9px] font-black uppercase text-white/40 hover:text-white transition-all">Export CSV ▾</button>
                        <div id="mm-export-menu" class="hidden absolute top-full mt-1 w-full bg-[#1a1c1b] border border-white/10 rounded-xl overflow-hidden z-20 shadow-2xl">
                            <button onclick="menuMgr.exportCSV('Food')" class="block w-full text-left px-4 py-3 bg-transparent border-0 text-[10px] text-white/60 hover:text-white hover:bg-white/5">Food Items</button>
                            <button onclick="menuMgr.exportCSV('Drinks')" class="block w-full text-left px-4 py-3 bg-transparent border-0 text-[10px] text-white/60 hover:text-white hover:bg-white/5">Drink Items</button>
                            <button onclick="menuMgr.exportCSV()" class="block w-full text-left px-4 py-3 bg-transparent border-0 text-[10px] text-white/60 hover:text-white hover:bg-white/5">Complete Menu</button>
                        </div>
                    </div>
                </div>
                <!-- Filters -->
                <div class="glass p-5 rounded-[2rem] border border-white/5 space-y-4">
                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#d4af37]/50 mb-1">Filters</p>
                    <input type="text" placeholder="Search name, id, category…"
                        oninput="menuMgr.state.searchQuery=this.value;menuMgr.state.page=1;menuMgr.render()"
                        class="w-full bg-black/40 border border-white/5 text-sm text-white py-3 px-4 rounded-xl outline-none focus:border-[#d4af37]/30">
                    <select onchange="menuMgr.state.selectedCategory=this.value;menuMgr.state.page=1;menuMgr.render()"
                        id="mm-cat-filter" class="w-full bg-black/40 border border-white/5 text-sm text-white py-3 px-4 rounded-xl outline-none appearance-none">
                        <option value="">All Categories</option>
                    </select>
                    <select onchange="menuMgr.state.selectedDistribution=this.value;menuMgr.state.page=1;menuMgr.render()"
                        id="mm-dist-filter" class="w-full bg-black/40 border border-white/5 text-sm text-white py-3 px-4 rounded-xl outline-none appearance-none">
                        <option value="">All Distributions</option>
                    </select>
                </div>
            </aside>
            <!-- Main panel -->
            <div class="md:col-span-8 lg:col-span-9 space-y-6">
                <!-- Tab header -->
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <h2 class="text-2xl font-black font-playfair italic text-white gold-glow">Menu Items</h2>
                    <div class="flex gap-2">
                        <button onclick="menuMgr.setActiveTab('Food')" id="mm-tab-food" class="mm-tab px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all">Food <span id="mm-food-count" class="opacity-50"></span></button>
                        <button onclick="menuMgr.setActiveTab('Drinks')" id="mm-tab-drinks" class="mm-tab px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all">Drinks <span id="mm-drinks-count" class="opacity-50"></span></button>
                    </div>
                </div>
                <!-- Item grid -->
                <div id="mm-items-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5"></div>
                <!-- Pagination -->
                <div id="mm-pagination" class="flex items-center justify-center gap-4 pt-6"></div>
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
                this._api('GET', `${this.config.apiBaseUrl}?collection=${this.config.collection}&t=${Date.now()}`),
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
        const foodBtn = document.getElementById('mm-tab-food');
        const drinkBtn = document.getElementById('mm-tab-drinks');
        if (!foodBtn) return;
        const foodCount = this.state.items.filter(i => i.mainCategory === 'Food').length;
        const drinkCount = this.state.items.filter(i => i.mainCategory === 'Drinks').length;
        document.getElementById('mm-food-count').textContent = `(${foodCount})`;
        document.getElementById('mm-drinks-count').textContent = `(${drinkCount})`;
        
        [foodBtn, drinkBtn].forEach(b => {
            const on = (b === foodBtn && this.state.activeTab === 'Food') || (b === drinkBtn && this.state.activeTab === 'Drinks');
            b.className = `mm-tab px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all ${on ? 'bg-[#d4af37]/15 text-[#d4af37] border-[#d4af37]/30' : 'border-white/10 text-gray-500 hover:text-white'}`;
        });
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
        let list = this.state.items.filter(i => i.mainCategory === this.state.activeTab);
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
            <div class="glass rounded-[1.5rem] border ${isSwapSource ? 'border-purple-500/50 ring-2 ring-purple-500/30' : 'border-white/5'} overflow-hidden group hover:bg-white/[0.02] transition-all">
                ${item.image ? `<div class="h-32 overflow-hidden bg-black/40">
                    <img src="${item.image}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="${item.name}">
                </div>` : `<div class="h-12 bg-gradient-to-r from-[#d4af37]/5 to-transparent"></div>`}
                <div class="p-4 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-white truncate">${item.name}</p>
                            <p class="text-[9px] text-gray-600 font-mono">${item.category || 'General'}</p>
                        </div>
                        <span class="text-[9px] font-black text-[#d4af37] bg-[#d4af37]/5 px-2 py-0.5 rounded-full shrink-0">#${item.menuId || '?'}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-black text-[#f3cf7a] font-mono">${Number(item.price).toLocaleString()} Br</span>
                        <div class="flex items-center gap-2">
                            ${(() => {
                                if (!item.stockItemId) return '';
                                const st = this.state.stocks.find(s => s.id === item.stockItemId);
                                if (!st) return '';
                                const qty = parseFloat(st.quantity || 0);
                                const status = st.status || 'available';
                                const color = (status === 'finished' || status === 'out_of_stock' || qty <= 0) ? 'bg-red-500' : (qty < 10 ? 'bg-orange-500' : 'bg-emerald-500');
                                return `
                                    <div class="flex items-center gap-1.5 px-2 py-0.5 bg-white/5 rounded-full border border-white/5">
                                        <span class="w-1.5 h-1.5 rounded-full ${color}"></span>
                                        <span class="text-[8px] font-black text-white/40 uppercase">${qty} ${st.unit || ''}</span>
                                    </div>
                                `;
                            })()}
                            <span class="w-2 h-2 rounded-full ${item.available !== false ? 'bg-emerald-500' : 'bg-red-500'}"></span>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-2 border-t border-white/5 opacity-0 group-hover:opacity-100 transition-all">
                        ${this.state.swapMode ? `
                        <button onclick="menuMgr.handleSwap('${item.id}')" class="flex-1 py-2 text-[9px] font-black uppercase text-purple-400 bg-purple-500/10 rounded-lg border border-purple-500/10">
                            ${isSwapSource ? 'Selected' : 'Swap ID'}
                        </button>` : `
                        <button onclick='AdminServices.openMenuModal(${JSON.stringify(item).replace(/'/g,"&#39;")})' class="flex-1 py-2 text-[9px] font-black uppercase text-gray-400 hover:text-white bg-white/5 rounded-lg">Edit</button>
                        <button onclick="menuMgr.deleteItem('${item.id}')" class="py-2 px-3 text-[9px] text-red-400 hover:text-red-300 bg-red-500/5 rounded-lg">
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

        let html = `<span class="text-[9px] text-gray-600 font-bold">${total} items</span>`;
        for (let i = 1; i <= pages; i++) {
            html += `<button onclick="menuMgr.setPage(${i})" class="w-8 h-8 rounded-lg text-[10px] font-black ${i === this.state.page ? 'bg-[#d4af37] text-black' : 'bg-white/5 text-gray-500 hover:text-white'}">${i}</button>`;
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
        a.download = `${label}_export_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
    }

    // ─── API Helper ────────────────────────────────────────────────────────────
    _api(method, url, data) {
        const opt = { method, headers: { 'Content-Type': 'application/json' } };
        if (data) opt.body = JSON.stringify(data);
        return fetch(url, opt).then(r => r.json()).catch(() => ({}));
    }
}
