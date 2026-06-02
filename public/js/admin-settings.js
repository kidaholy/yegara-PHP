/**
 * Admin Settings Hub — State Driven Logic
 * Branding, Categories, Floors & Tables
 */

const AdminSettings = {
    // ─── STATE ───────────────────────────────────────────────────────────────────
    activeTab: 'branding',
    formData: {
        logo_url: '',
        favicon_url: '',
        app_name: 'Prime Addis',
        app_tagline: 'Coffee Management',
        vat_rate: '0.15',
        enable_cashier_printing: 'true',
        enable_cashier_today_revenue: 'false'
    },
    
    uploadMethod: 'url', // 'url' | 'file'
    saving: false,
    
    // Categories
    categories: [],
    categoryType: 'menu', // 'menu' | 'stock' | 'distribution'
    editingCategory: null,

    // Layout
    floors: [],
    tables: [],
    editingFloor: null,
    editingTable: null,

    // ─── INIT ─────────────────────────────────────────────────────────────────────
    async init() {
        this.loadInitialData();
        this.renderActiveTab();
        lucide.createIcons();
    },

    loadInitialData() {
        if (window.currentSettings) {
            Object.keys(this.formData).forEach(key => {
                if (window.currentSettings[key] !== undefined) {
                    this.formData[key] = window.currentSettings[key];
                }
            });
        }
        this.updatePreview();
    },

    // ─── TAB NAVIGATION ──────────────────────────────────────────────────────────
    setTab(tab) {
        this.activeTab = tab;
        
        // Update Buttons UI
        document.querySelectorAll('.settings-tab-btn').forEach(btn => {
            const isAct = btn.id === `tab-btn-${tab}`;
            btn.classList.toggle('border-[#d4af37]', isAct);
            btn.classList.toggle('text-white', isAct);
            btn.classList.toggle('border-transparent', !isAct);
            btn.classList.toggle('text-gray-500', !isAct);
        });

        this.renderActiveTab();
        
        // Lazy load tab data
        if (tab === 'categories') this.fetchCategories();
        if (tab === 'tables') this.fetchFloorsAndTables();
    },

    // ─── RENDERING ───────────────────────────────────────────────────────────────
    renderActiveTab() {
        const panel = document.getElementById('settings-content-panel');
        let html = '';

        switch(this.activeTab) {
            case 'branding': html = this.renderBranding(); break;
            case 'categories': html = this.renderCategories(); break;
            case 'tables': html = this.renderTables(); break;
        }

        panel.innerHTML = `<div class="tab-content-anim">${html}</div>`;
        lucide.createIcons();
    },

    renderBranding() {
        return `
            <div class="space-y-12">
                <!-- Header -->
                <div class="flex justify-between items-center px-2">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]">Branding & Logic</h3>
                    <div id="save-status" class="text-[9px] font-bold text-emerald-400 opacity-0 transition-opacity">Settings Saved</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <!-- App Configuration -->
                    <div class="space-y-8">
                        <div class="space-y-2">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-600 block ml-2">App Name</label>
                            <input type="text" value="${this.formData.app_name}" oninput="AdminSettings.updateField('app_name', this.value)"
                                class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-4 px-6 text-sm text-white focus:outline-none focus:border-[#d4af37]/30 transition-all font-bold">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-600 block ml-2">App Tagline</label>
                            <input type="text" value="${this.formData.app_tagline}" oninput="AdminSettings.updateField('app_tagline', this.value)"
                                class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-4 px-6 text-sm text-white focus:outline-none focus:border-[#d4af37]/30 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-600 block ml-2">VAT Rate (Decimal)</label>
                            <div class="relative">
                                <input type="number" step="0.01" value="${this.formData.vat_rate}" oninput="AdminSettings.updateField('vat_rate', this.value)"
                                    class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-4 px-6 text-sm text-white focus:outline-none focus:border-[#d4af37]/30 transition-all font-mono">
                                <span class="absolute right-6 top-1/2 -translate-y-1/2 bg-emerald-500/10 text-emerald-400 text-[10px] font-black px-2 py-1 rounded-md border border-emerald-500/20">
                                    ${(parseFloat(this.formData.vat_rate)*100).toFixed(0)}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Logo Settings -->
                    <div class="space-y-8">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-600 block ml-2">Logo Upload</label>
                            <div class="flex bg-black/40 p-1 rounded-full border border-white/5">
                                <button onclick="AdminSettings.setUploadMethod('url')" class="px-3 py-1 text-[8px] font-black uppercase rounded-full ${this.uploadMethod==='url'?'bg-white/10 text-white':'text-gray-600'}">URL</button>
                                <button onclick="AdminSettings.setUploadMethod('file')" class="px-3 py-1 text-[8px] font-black uppercase rounded-full ${this.uploadMethod==='file'?'bg-white/10 text-white':'text-gray-600'}">File</button>
                            </div>
                        </div>

                        ${this.uploadMethod === 'url' ? `
                            <input type="text" placeholder="https://example.com/logo.png" 
                                value="${this.formData.logo_url.startsWith('data:') ? '' : this.formData.logo_url}"
                                oninput="AdminSettings.updateField('logo_url', this.value)"
                                class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-4 px-6 text-sm text-white focus:outline-none focus:border-[#d4af37]/30 transition-all font-mono text-[11px]">
                        ` : `
                            <div class="relative group h-16">
                                <label class="w-full h-full flex items-center justify-center bg-white/5 border border-dashed border-white/10 rounded-2xl cursor-pointer hover:bg-white/10 transition-all">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">
                                        <i data-lucide="upload" class="w-4 h-4 inline-block mr-2 -mt-1 text-[#d4af37]"></i> Select Logo File
                                    </span>
                                    <input type="file" accept="image/*" class="hidden" onchange="AdminSettings.handleFile(this, 'logo_url')">
                                </label>
                            </div>
                        `}

                        <!-- Business Logic Toggles -->
                        <div class="space-y-6 pt-6">
                            ${this.renderToggle('enable_cashier_printing', 'Allow Cashier Printing', 'Automatically opens print dialog after checkout')}
                            ${this.renderToggle('enable_cashier_today_revenue', 'Show Daily Revenue', 'Allows cashiers to see their total sales for today')}
                        </div>
                    </div>
                </div>

                <!-- Footer Action -->
                <div class="pt-10 flex justify-end">
                    <button onclick="AdminSettings.saveBranding()" id="save-branding-btn"
                        class="bg-gradient-to-r from-[#d4af37] to-[#b38822] text-black px-12 py-4 rounded-2xl text-[11px] font-black uppercase tracking-widest shadow-2xl hover:scale-105 transition-all flex items-center gap-3">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Configuration
                    </button>
                </div>
            </div>
        `;
    },

    renderToggle(key, label, sub) {
        const active = this.formData[key] === 'true';
        return `
            <div class="flex items-center justify-between group">
                <div class="flex-1">
                    <p class="text-[11px] font-black text-white uppercase tracking-widest mb-1">${label}</p>
                    <p class="text-[10px] text-gray-600 font-bold">${sub}</p>
                </div>
                <div onclick="AdminSettings.toggleField('${key}')" class="toggle-pill ${active ? 'active' : ''}">
                    <div class="dot"></div>
                </div>
            </div>
        `;
    },

    renderCategories() {
        return `
            <div class="space-y-10">
                <div class="flex items-center justify-between pb-4 border-b border-white/5">
                    <div class="flex gap-4">
                        ${['menu','stock','distribution'].map(t => `
                            <button onclick="AdminSettings.setCatType('${t}')" 
                                class="text-[10px] font-black uppercase tracking-widest pb-3 border-b-2 transition-all
                                ${this.categoryType === t ? 'text-[#d4af37] border-[#d4af37]' : 'text-gray-500 border-transparent hover:text-white'}">
                                ${t}
                            </button>
                        `).join('')}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <!-- Form -->
                    <div class="space-y-6">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-6">${this.editingCategory ? 'Edit' : 'Add New'} ${this.categoryType} Category</h4>
                        <div class="space-y-1">
                            <input type="text" id="cat-name-input" placeholder="Category Name..." 
                                value="${this.editingCategory ? this.editingCategory.name : ''}"
                                class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-4 px-6 text-sm text-white focus:outline-none focus:border-[#d4af37]/30 font-bold transition-all">
                        </div>
                        <div class="flex gap-3">
                            ${this.editingCategory ? `
                                <button onclick="AdminSettings.setEditingCat(null)" class="flex-1 py-4 text-[10px] font-black uppercase text-gray-600">Cancel</button>
                            ` : ''}
                            <button onclick="AdminSettings.saveCategory()" class="flex-1 bg-white text-black rounded-2xl py-4 text-[10px] font-black uppercase tracking-widest shadow-xl">
                                ${this.editingCategory ? 'Update' : 'Create Category'}
                            </button>
                        </div>
                    </div>

                    <!-- List -->
                    <div class="space-y-4 max-h-[500px] overflow-y-auto no-scrollbar">
                        ${this.categories.map(c => `
                            <div class="glass p-5 rounded-2xl border border-white/5 flex items-center justify-between group hover:bg-white/[0.02] transition-all">
                                <div>
                                    <p class="text-sm font-bold text-white">${c.name}</p>
                                    <p class="text-[9px] uppercase font-black text-gray-600 tracking-tighter mt-1">${c.type || this.categoryType}</p>
                                </div>
                                <div class="flex gap-4 opacity-0 group-hover:opacity-100 transition-all">
                                    <button onclick="AdminSettings.setEditingCat(${JSON.stringify(c).replace(/"/g, '&quot;')})" class="text-gray-500 hover:text-[#d4af37]"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                    <button onclick="AdminSettings.deleteCategory('${c.id}')" class="text-gray-500 hover:text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    },

    renderTables() {
        return `
            <div class="space-y-12">
                <!-- Floors Section -->
                <div class="space-y-6">
                    <div class="flex justify-between items-center px-1">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-600">Floor Management</h4>
                        <span class="bg-white/5 px-2 py-0.5 rounded text-[9px] font-bold text-gray-500">${this.floors.length} TOTAL</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-white/[0.01] p-2 rounded-[2rem] border border-white/5">
                        <div class="md:col-span-4"><input id="floor-num" type="text" placeholder="Floor Name/No" class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-3 px-5 text-sm text-white focus:outline-none"></div>
                        <div class="md:col-span-3"><input id="floor-order" type="number" placeholder="Sort Order" class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-3 px-5 text-sm text-white focus:outline-none"></div>
                        <button onclick="AdminSettings.saveFloor()" class="md:col-span-5 bg-[#d4af37]/10 hover:bg-[#d4af37]/20 text-[#d4af37] border border-[#d4af37]/20 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all">Add/Update Floor</button>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        ${this.floors.map(f => `
                            <div class="glass py-3 px-6 rounded-full border border-white/5 flex items-center gap-4 group">
                                <span class="text-[10px] font-black uppercase tracking-widest text-[#f3cf7a]">Floor ${f.floorNumber}</span>
                                <div class="flex gap-2">
                                    <button onclick="AdminSettings.deleteFloor('${f.id}')" class="text-gray-700 hover:text-red-500 transition-colors"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- Tables Section -->
                <div class="space-y-6 pt-6 border-t border-white/5">
                    <div class="flex justify-between items-center px-1">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-600">Active Tables</h4>
                        <span class="bg-white/5 px-2 py-0.5 rounded text-[9px] font-bold text-gray-500">${this.tables.length} REGISTERED</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 bg-white/[0.01] p-2 rounded-[2rem] border border-white/5">
                        <div class="md:col-span-4"><input id="tab-num" type="text" placeholder="T-01" class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-3 px-5 text-sm font-bold text-white outline-none"></div>
                        <div class="md:col-span-3"><input id="tab-cap" type="number" placeholder="Seats" class="w-full bg-[#0f1110] border border-white/5 rounded-2xl py-3 px-5 text-sm text-white outline-none"></div>
                        <button onclick="AdminSettings.saveTable()" class="md:col-span-5 bg-white text-black rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all shadow-xl">Confirm Table</button>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                        ${this.tables.map(t => `
                            <div class="glass p-6 rounded-[2rem] border border-white/5 text-center relative group">
                                <p class="text-2xl font-black font-playfair italic text-[#f3cf7a] mb-1 font-bold">${t.tableNumber}</p>
                                <p class="text-[9px] uppercase font-black tracking-widest text-gray-600">${t.capacity} Seatings</p>
                                <button onclick="AdminSettings.deleteTable('${t.id}')" class="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all shadow-xl"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    },

    // ─── LOGIC HANDLERS ──────────────────────────────────────────────────────────
    updateField(key, val) {
        this.formData[key] = val;
        this.updatePreview();
    },

    toggleField(key) {
        this.formData[key] = this.formData[key] === 'true' ? 'false' : 'true';
        this.renderActiveTab();
    },

    updatePreview() {
        const logo = this.formData.logo_url || 'https://images.unsplash.com/photo-1541167760496-162955ed8a9f?w=200&h=200&fit=crop&q=80';
        document.getElementById('preview-logo').src = logo;
        document.getElementById('preview-nav-logo').src = logo;
        document.getElementById('preview-favicon').src = this.formData.favicon_url || logo;
        
        setText('preview-app-name', this.formData.app_name);
        setText('preview-app-tagline', this.formData.app_tagline);
        setText('preview-nav-name', this.formData.app_name);
        setText('preview-tab-name', `${this.formData.app_name} | Dashboard`);
    },

    setUploadMethod(m) { this.uploadMethod = m; this.renderActiveTab(); },

    async handleFile(input, key) {
        const file = input.files[0];
        if (!file) return;
        
        if (file.size > 5 * 1024 * 1024) return alert("File too large (>5MB)");
        
        const reader = new FileReader();
        reader.onload = async (e) => {
            const data = e.target.result;
            // Compress
            const compressed = await this.compressImage(data, key === 'logo_url' ? 200 : 64);
            this.formData[key] = compressed;
            if (key === 'logo_url' && !this.formData.favicon_url) this.formData.favicon_url = compressed;
            this.updatePreview();
            this.renderActiveTab();
        };
        reader.readAsDataURL(file);
    },

    compressImage(base64, size) {
        return new Promise((resolve) => {
            const img = new Image();
            img.src = base64;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = size; canvas.height = size;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, size, size);
                resolve(canvas.toDataURL('image/jpeg', 0.9));
            };
        });
    },

    async saveBranding() {
        const btn = document.getElementById('save-branding-btn');
        btn.disabled = true; btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Saving...';
        lucide.createIcons();

        try {
            const keys = Object.keys(this.formData);
            for (const key of keys) {
                await this.api('PUT', 'api/admin/settings.php', { key, value: this.formData[key] });
            }
            
            const status = document.getElementById('save-status');
            status.classList.remove('opacity-0');
            setTimeout(() => status.classList.add('opacity-0'), 3000);
            
        } catch (e) {
            console.error(e); alert("Failed to save settings.");
        } finally {
            btn.disabled = false; btn.innerHTML = '<i data-lucide="save" class="w-4 h-4"></i> Save Configuration';
            lucide.createIcons();
        }
    },

    // CRUD Handlers
    async fetchCategories() {
        const res = await this.api('GET', `api/categories.php?type=${this.categoryType}`);
        this.categories = res;
        this.renderActiveTab();
    },

    setCatType(t) { this.categoryType = t; this.fetchCategories(); },
    setEditingCat(c) { this.editingCategory = c; this.renderActiveTab(); },
    async saveCategory() {
        const name = document.getElementById('cat-name-input').value;
        if (!name) return;
        const method = this.editingCategory ? 'PUT' : 'POST';
        const url = this.editingCategory ? `api/categories.php?id=${this.editingCategory.id}` : 'api/categories.php';
        await this.api(method, url, { name, type: this.categoryType });
        this.editingCategory = null;
        this.fetchCategories();
    },
    async deleteCategory(id) {
        if (!confirm("Delete category?")) return;
        await this.api('DELETE', `api/categories.php?id=${id}`);
        this.fetchCategories();
    },

    async fetchFloorsAndTables() {
        const f = await this.api('GET', 'api/admin/floors.php');
        const t = await this.api('GET', 'api/admin/tables.php');
        this.floors = f.data || [];
        this.tables = t.data || [];
        this.renderActiveTab();
    },
    async saveFloor() {
        const floorNumber = document.getElementById('floor-num').value;
        const order = document.getElementById('floor-order').value;
        await this.api('POST', 'api/admin/floors.php', { floorNumber, order });
        this.fetchFloorsAndTables();
    },
    async deleteFloor(id) {
        if (!confirm("Delete floor? Associated tables will become unassigned.")) return;
        await this.api('DELETE', `api/admin/floors.php?id=${id}`);
        this.fetchFloorsAndTables();
    },
    async saveTable() {
        const tableNumber = document.getElementById('tab-num').value;
        const capacity = document.getElementById('tab-cap').value;
        await this.api('POST', 'api/admin/tables.php', { tableNumber, capacity });
        this.fetchFloorsAndTables();
    },
    async deleteTable(id) {
        if (!confirm("Delete table?")) return;
        await this.api('DELETE', `api/admin/tables.php?id=${id}`);
        this.fetchFloorsAndTables();
    },

    // Helpers
    api(method, url, data) {
        const opt = { method, headers: { 'Content-Type': 'application/json' } };
        if (data) opt.body = JSON.stringify(data);
        return fetch(url, opt).then(r => r.json());
    }
};

document.addEventListener('DOMContentLoaded', () => AdminSettings.init());
