/**
 * Admin Reports Hub — Core Logic Engine
 * Replicating Next.js BI Hub logic with state-driven rendering
 * 100% Spec Adherence Fix
 */

// Global Helpers
const setText = (id, text) => {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
};

const ReportHub = {
    // ─── STATE ───────────────────────────────────────────────────────────────────
    timeRange: 'week',
    activeSlide: 0,
    animating: false,
    direction: 'right',
    initialized: false,
    loadingSlide: false,
    selectedDate: new Date(),
    
    // Data (Shared)
    orders: [],
    stockItems: [],
    menuItems: [],
    periodData: null,      // from /api/reports/sales
    stockUsageData: null,  // from /api/reports/stock-usage
    receptionRevenue: 0,   // from /api/reports/bedroom-revenue
    
    // UI Local State
    orderHistoryTab: 'All',
    menuSalesTab: 'Food',
    menuSearchTerm: '',
    menuCashierFilter: 'All',
    activeCashierIdx: 0,

    // ─── INIT ─────────────────────────────────────────────────────────────────────
    async init() {
        try {
            this.setupEventListeners();
            await this.fetchAllData();
            this.initialized = true;
            this.renderActiveSlide();
        } catch (err) {
            console.error("Hub Initialization Failed:", err);
            this.showError("Failed to initialize Report Hub. Check network.");
            const panel = document.getElementById('slide-panel');
            if (panel) panel.innerHTML = `<div class="p-20 text-center text-red-500 font-black uppercase tracking-widest text-[10px]">Critical Hub Failure</div>`;
        }
    },

    setupEventListeners() {
        window.ReportHub = this;
    },

    // ─── DATA FETCHING (TWO-STAGE) ────────────────────────────────────────────────
    async fetchAllData() {
        this.setLoading(true);
        const query = this.getQueryString();

        try {
            // Stage 1: Critical (blocking ~1.5s visual floor per spec)
            const startTime = Date.now();
            const [salesRes, ordersRes, receptionRes] = await Promise.all([
                this.api('GET', `api/reports/sales.php${query}`),
                this.api('GET', `api/reports/orders.php${this.getOrdersQuery()}`),
                this.api('GET', `api/reports/bedroom-revenue.php${query}`)
            ]);

            this.periodData = salesRes?.data || null;
            this.orders = Array.isArray(ordersRes) ? ordersRes : (ordersRes?.data || []);
            this.receptionRevenue = receptionRes?.data?.totalRevenue || 0;

            // Ensure 1.5s visual floor as per spec
            const elapsed = Date.now() - startTime;
            if (elapsed < 1500) await new Promise(r => setTimeout(r, 1500 - elapsed));
            
            this.setLoading(false);
            this.renderActiveSlide();

            // Stage 2: Secondary (Background, non-blocking)
            this.fetchSecondaryData(query);

        } catch (e) {
            console.error('Core data fetch failed:', e);
            this.showError('Critical data load failed.');
            this.setLoading(false);
        }
    },

    async fetchSecondaryData(query) {
        try {
            const [stockRes, usageRes, menuRes] = await Promise.all([
                this.api('GET', 'api/stock.php'),
                this.api('GET', `api/reports/stock-usage.php${query}`),
                this.api('GET', 'api/categories.php?all=true')
            ]);
            this.stockItems = stockRes || [];
            this.stockUsageData = usageRes.data;
            this.menuItems = menuRes || [];
            
            if ([2, 3, 4].includes(this.activeSlide)) this.renderActiveSlide();
        } catch (e) { console.warn('Secondary data load failed:', e); }
    },

    // ─── CALCULATIONS & AGGREGATIONS ─────────────────────────────────────────────
    getCalculatedStats() {
        const s = this.periodData?.summary || {};
        const rev = s.totalRevenue || 0;
        const totalRev = rev + this.receptionRevenue;
        const periodInvest = (s.periodStockInvestment || 0) + (s.totalOtherExpenses || 0);
        
        // Group Orders by Cashier
        const cashierMap = {};
        this.orders.forEach(o => {
            if (o.status === 'cancelled' || o.isDeleted) return;
            const name = o.createdBy?.name || 'Unknown Cashier';
            if (!cashierMap[name]) cashierMap[name] = 0;
            cashierMap[name] += parseFloat(o.totalAmount || 0);
        });

        // Split Food/Drinks
        let foodRev = 0, drinksRev = 0;
        this.orders.forEach(o => {
            if (o.status === 'cancelled' || o.isDeleted) return;
            (o.items || []).forEach(item => {
                if (item.mainCategory === 'Food') foodRev += (item.price * item.quantity);
                if (item.mainCategory === 'Drinks') drinksRev += (item.price * item.quantity);
            });
        });

        // Aggregated Menu Sales (aggregated by "name | cashier")
        const menuSales = {};
        this.orders.forEach(o => {
            if (o.status === 'cancelled' || o.isDeleted) return;
            (o.items || []).forEach(item => {
                const key = `${item.name}|${o.createdBy?.name || 'Unknown'}`;
                if (!menuSales[key]) {
                    menuSales[key] = { 
                        name: item.name, 
                        cashier: o.createdBy?.name || 'Unknown',
                        category: item.category,
                        mainCategory: item.mainCategory,
                        quantity: 0,
                        revenue: 0 
                    };
                }
                menuSales[key].quantity += parseFloat(item.quantity);
                menuSales[key].revenue += (item.price * item.quantity);
            });
        });

        return { 
            totalRevenue: totalRev, 
            orderRevenue: rev,
            foodRevenue: foodRev, 
            drinksRevenue: drinksRev,
            cashierRevenue: cashierMap,
            menuItemSales: Object.values(menuSales),
            periodInvestment: periodInvest,
            periodProfit: totalRev - (s.totalOperationalExpenses || 0) - periodInvest,
            totalOperationalExpenses: s.totalOperationalExpenses || 0
        };
    },

    // ─── SLIDE NAVIGATION ────────────────────────────────────────────────────────
    goToSlide(idx) {
        if (this.animating || this.activeSlide === idx) return;
        this.direction = idx > this.activeSlide ? 'right' : 'left';
        this.animating = true;
        this.updateNavUI(idx);
        const panel = document.getElementById('slide-panel');
        panel.classList.remove('slide-enter-right', 'slide-enter-left');
        setTimeout(() => {
            this.activeSlide = idx;
            this.renderActiveSlide();
            this.animating = false;
        }, 260);
    },

    updateNavUI(idx) {
        document.querySelectorAll('.report-nav-btn').forEach((b, i) => {
            const act = i === idx;
            b.classList.toggle('bg-gradient-to-r', act);
            b.classList.toggle('from-[#1a1712]', act);
            b.classList.toggle('to-[#151716]', act);
            b.classList.toggle('border-[#d4af37]/30', act);
            b.classList.toggle('text-[#f3cf7a]', act);
            b.classList.toggle('shadow-xl', act);
            b.classList.toggle('text-gray-500', !act);
            b.querySelector('.w-10')?.classList.toggle('bg-[#d4af37]', act);
            b.querySelector('.w-10')?.classList.toggle('text-black', act);
            b.querySelector('.w-10')?.classList.toggle('bg-white/5', !act);
        });
        document.querySelectorAll('.report-nav-btn-mobile').forEach((b, i) => {
            const act = i === idx;
            b.classList.toggle('bg-[#d4af37]', act);
            b.classList.toggle('text-black', act);
            b.classList.toggle('border-[#d4af37]', act);
            b.classList.toggle('bg-white/5', !act);
            b.classList.toggle('text-gray-500', !act);
        });
        const labels = window.reportSlides.map(s => s.label);
        setText('slide-subtitle', `Consolidated reports · ${labels[idx] || 'Analytics'}`);
    },

    // ─── RENDERING ───────────────────────────────────────────────────────────────
    renderActiveSlide() {
        const slide = window.reportSlides[this.activeSlide];
        if (!slide) return;
        const panel = document.getElementById('slide-panel');
        const animClass = this.direction === 'right' ? 'slide-enter-right' : 'slide-enter-left';
        let html = '';
        switch(slide.id) {
            case 'financial': html = this.renderFinancial(); break;
            case 'orders': html = this.renderOrders(); break;
            case 'inventory': html = this.renderInventory(); break;
            case 'store': html = this.renderStore(); break;
            case 'menu-sales': html = this.renderMenuSales(); break;
            case 'cashier-insights': html = this.renderCashierInsights(); break;
            default: html = `<div class="p-20 text-center">Section ${slide.label} Content</div>`;
        }
        panel.innerHTML = `<div class="${animClass}">${html}</div>`;
        lucide.createIcons();
    },

    renderFinancial() {
        const stats = this.getCalculatedStats();
        const rows = [
            { m: 'Total Revenue',  t: 'INCOME',   v: stats.totalRevenue, c: 'emerald', d: 'Combined Order + Bedroom Revenue' },
            { m: 'Reception Rev',  t: 'INCOME',   v: this.receptionRevenue, c: 'blue',  d: 'Direct Room Booking Income' },
            { m: 'Food Revenue',   t: 'BREAKDOWN', v: stats.foodRevenue, c: 'gold',  d: 'Total Food Sales from Orders' },
            { m: 'Drinks Revenue', t: 'BREAKDOWN', v: stats.drinksRevenue, c: 'gold',  d: 'Total Drinks Sales from Orders' },
        ];
        Object.entries(stats.cashierRevenue).forEach(([name, amt]) => {
            const pct = stats.orderRevenue > 0 ? ((amt/stats.orderRevenue)*100).toFixed(1) : 0;
            rows.push({ m: name, t: 'CASHIER SALES', v: amt, c: 'gray', d: `${pct}% of order contributions` });
        });
        rows.push(
            { m: 'Operational Exp', t: 'EXPENSE', v: -stats.totalOperationalExpenses, c: 'red', d: 'Monthly overhead & fixed costs' },
            { m: 'Stock Invest',    t: 'EXPENSE', v: -stats.periodInvestment, c: 'red', d: 'Cost of raw materials & restocking' },
            { m: 'Net Profit',      t: 'PROFIT',  v: stats.periodProfit, c: stats.periodProfit >= 0 ? 'emerald' : 'red', d: 'Final takeaway for the period' }
        );

        return `
            <div class="space-y-6">
                <div class="grid md:hidden grid-cols-1 gap-4">
                    ${this.mobileStatCard('Income', stats.totalRevenue, 'emerald')}
                    ${this.mobileStatCard('Expenses', stats.totalOperationalExpenses + stats.periodInvestment, 'red')}
                    ${this.mobileStatCard('Profit', stats.periodProfit, stats.periodProfit >= 0 ? 'emerald' : 'red')}
                </div>
                <div class="hidden md:block glass rounded-[2.5rem] border border-white/5 overflow-hidden">
                    <div class="px-8 py-5 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                        <h3 class="text-[10px] uppercase font-black tracking-widest text-[#f3cf7a]">Statement of Accounts</h3>
                        <button onclick="ReportHub.exportFinancial()" class="text-[9px] font-black uppercase text-gray-500 hover:text-white transition-all flex items-center gap-2 outline-none">
                             <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Export Word
                        </button>
                    </div>
                    <table class="w-full text-left">
                        <thead><tr class="text-[9px] uppercase font-black tracking-widest text-gray-600 border-b border-white/5"><th class="px-8 py-5">Metric</th><th class="px-8 py-5">Type</th><th class="px-8 py-5 text-right">Amount</th></tr></thead>
                        <tbody class="divide-y divide-white/5">
                            ${rows.map(r => {
                                const cls = { emerald:'text-emerald-400 bg-emerald-500/10 border-emerald-500/20', 
                                              red:'text-red-400 bg-red-500/10 border-red-500/20',
                                              blue:'text-blue-400 bg-blue-500/10 border-blue-500/20',
                                              gold:'text-[#d4af37] bg-[#d4af37]/10 border-[#d4af37]/20',
                                              gray:'text-gray-400 bg-white/5 border-white/5' }[r.c];
                                return `<tr class="hover:bg-white/[0.01] transition-all group"><td class="px-8 py-6"><p class="text-sm font-black text-white">${r.m}</p><p class="text-[9px] text-gray-600 mt-1">${r.d}</p></td><td class="px-8 py-6"><span class="px-3 py-1 rounded-full border text-[8px] font-black tracking-tighter ${cls}">${r.t}</span></td><td class="px-8 py-6 text-right font-mono font-bold text-base ${r.v >= 0 ? 'text-white' : 'text-red-400'}">${r.v < 0 ? '-' : '+'}${this.fmt(Math.abs(r.v))}</td></tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    },

    renderOrders() {
        const filtered = this.orders.filter(o => {
            if (this.orderHistoryTab === 'Food') return (o.items||[]).some(i => i.mainCategory === 'Food');
            if (this.orderHistoryTab === 'Drinks') return (o.items||[]).some(i => i.mainCategory === 'Drinks');
            return true;
        });

        return `
            <div class="space-y-6">
                <div class="flex items-center justify-between pb-2 border-b border-white/5">
                    <div class="flex gap-4">
                        ${['All','Food','Drinks'].map(t => `<button onclick="ReportHub.setOrderTab('${t}')" class="text-[10px] font-black uppercase tracking-widest pb-3 border-b-2 transition-all ${this.orderHistoryTab === t ? 'text-[#d4af37] border-[#d4af37]' : 'text-gray-500 border-transparent hover:text-white'}">${t} Orders</button>`).join('')}
                    </div>
                    <div class="flex gap-3">
                        <button onclick="ReportHub.exportOrdersCSV('${this.orderHistoryTab}')" class="text-[9px] font-black uppercase text-gray-500 hover:text-[#d4af37] transition-all flex items-center gap-2 outline-none">
                             <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
                        </button>
                    </div>
                </div>
                <div class="glass rounded-[2rem] border border-white/5 overflow-hidden max-h-[600px] overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left">
                        <thead class="sticky top-0 bg-[#151716] z-20"><tr class="text-[9px] uppercase font-black tracking-widest text-gray-600 border-b border-white/5"><th class="px-6 py-4">Order# | Table</th><th class="px-6 py-4">Items Breakdown</th><th class="px-6 py-4 text-right">Payment</th><th class="px-6 py-4 text-center">Status</th></tr></thead>
                        <tbody class="divide-y divide-white/5 text-[11px]">
                            ${filtered.map(o => {
                                // Spec: row total = sum of matching items only
                                let tabTotal = 0;
                                (o.items||[]).forEach(i => {
                                    if (this.orderHistoryTab === 'All' || i.mainCategory === this.orderHistoryTab) {
                                        tabTotal += (i.price * i.quantity);
                                    }
                                });
                                return `<tr class="hover:bg-white/[0.02]"><td class="px-6 py-5"><p class="font-black text-white text-sm">#${o.orderNumber}</p><p class="uppercase text-gray-500 font-bold mt-1 tracking-tighter">${o.tableNumber || 'Walking'} · ${new Date(o.createdAt).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</p></td><td class="px-6 py-5"><div class="max-w-[300px] text-gray-400 space-y-0.5">${(o.items||[]).map(i => `<p class="truncate"><span class="text-white font-bold">${i.quantity}</span> x ${i.name}</p>`).join('')}</div></td><td class="px-6 py-5 text-right"><p class="font-black text-[#f3cf7a] text-sm">${this.fmt(tabTotal)}</p><p class="uppercase text-[9px] text-gray-600 font-bold mt-0.5">${o.paymentMethod || 'cash'}</p></td><td class="px-6 py-5 text-center"><span class="px-2.5 py-1 rounded-full border text-[8px] font-black uppercase ${o.status==='cancelled'?'bg-red-500/10 text-red-400 border-red-500/20':'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'}">${o.status}</span></td></tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
    },

    renderInventory() {
        const u = this.stockUsageData?.stockAnalysis || [];
        const lowCount = u.filter(i => i.isLowStock).length;
        return `
            <div class="space-y-6">
                ${lowCount > 0 ? `<div class="flex items-center gap-4 p-5 rounded-3xl bg-red-500/[0.03] border border-red-500/10 text-red-400/80"><i data-lucide="alert-triangle" class="w-4 h-4"></i><p class="text-[9px] uppercase font-black tracking-widest">${lowCount} Low Stock items alert.</p></div>` : ''}
                <div class="flex justify-between items-center px-2">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-600">Inventory Turnover · ${u.length} Items</h3>
                    <button onclick="ReportHub.exportInventoryCSV()" class="text-[9px] font-black uppercase text-gray-500 hover:text-white transition-all flex items-center gap-2 outline-none"><i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV</button>
                </div>
                <div class="hidden lg:block glass rounded-[2rem] border border-white/5 overflow-hidden">
                    <table class="w-full text-left text-[11px]">
                        <thead><tr class="text-[9px] uppercase font-black tracking-widest text-gray-600 bg-white/[0.03] border-b border-white/5"><th class="px-6 py-5">Item Name</th><th class="px-6 py-5 text-right">Sell Price</th><th class="px-6 py-5 text-right">Remains</th><th class="px-6 py-5 text-right">Investment (@avg)</th><th class="px-6 py-5 text-right">Usage</th><th class="px-6 py-5 text-right">Potential Value</th><th class="px-6 py-5 text-center">Status</th></tr></thead>
                        <tbody class="divide-y divide-white/5">
                            ${u.filter(i => (i.openingStock||0) > 0 || (i.closingStock||0) > 0 || (i.consumed||0) > 0).map(i => `
                                <tr class="${i.isLowStock ? 'bg-red-500/[0.01]' : ''} hover:bg-white/[0.02]">
                                    <td class="px-6 py-5"><p class="font-black text-white">${i.name}</p><p class="text-[9px] text-gray-600 uppercase mt-0.5">${i.category}</p></td>
                                    <td class="px-6 py-5 text-right font-mono">${this.fmt(i.currentUnitCost)}</td>
                                    <td class="px-6 py-5 text-right font-bold text-white">${i.closingStock} ${i.unit}</td>
                                    <td class="px-6 py-5 text-right font-mono text-[#f3cf7a]">${this.fmt(i.weightedAvgCost * i.closingStock)}</td>
                                    <td class="px-6 py-5 text-right text-emerald-400 font-bold">-${i.consumed}</td>
                                    <td class="px-6 py-5 text-right font-mono text-white">${this.fmt(i.currentUnitCost * i.closingStock)}</td>
                                    <td class="px-6 py-5 text-center"><span class="px-2 py-0.5 rounded-full border text-[8px] font-black ${i.isLowStock ? 'text-red-400 border-red-400/20 bg-red-400/10' : 'text-emerald-400 border-emerald-400/20 bg-emerald-400/10'}">${i.isLowStock ? 'LOW' : 'OK'}</span></td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
    },

    renderStore() {
        const u = this.stockUsageData?.stockAnalysis || [];
        return `
            <div class="space-y-6">
                <div class="flex justify-between items-center px-2"><h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-600">Store Investment Analytics</h3></div>
                <div class="hidden lg:block glass rounded-[2rem] border border-white/5 overflow-hidden">
                    <table class="w-full text-left text-[11px]">
                        <thead><tr class="text-[9px] uppercase font-black tracking-widest text-gray-600 bg-white/[0.03] border-b border-white/5"><th class="px-6 py-5">Item Name</th><th class="px-6 py-5 text-right">Unit Cost</th><th class="px-6 py-5 text-right">In Store</th><th class="px-6 py-5 text-right">Total Inv.</th><th class="px-6 py-5 text-right">Transferred</th><th class="px-6 py-5 text-center">Status</th></tr></thead>
                        <tbody class="divide-y divide-white/5">
                            ${u.filter(i => (i.storeQuantity||0) > 0 || (i.transferred||0) > 0).map(i => `
                                <tr class="hover:bg-white/[0.02] transition-colors"><td class="px-6 py-5"><p class="font-black text-white">${i.name}</p><p class="text-[9px] text-gray-600 uppercase mt-0.5">${i.category}</p></td><td class="px-6 py-5 text-right font-mono">${this.fmt(i.weightedAvgCost).replace(' Br','')}</td><td class="px-6 py-5 text-right font-black text-white text-base">${i.storeQuantity} ${i.unit}</td><td class="px-6 py-5 text-right font-mono font-bold text-[#f3cf7a]">${this.fmt(i.storeClosingValue)}</td><td class="px-6 py-5 text-right text-gray-500 font-bold">${i.transferred} ${i.unit}</td><td class="px-6 py-5 text-center"><span class="px-2 py-0.5 rounded-full border border-white/5 text-[8px] font-black uppercase text-gray-600">STORE_OK</span></td></tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
    },

    renderMenuSales() {
        const stats = this.getCalculatedStats();
        const filtered = stats.menuItemSales.filter(s => {
            if (s.mainCategory !== this.menuSalesTab) return false;
            if (this.menuCashierFilter !== 'All' && s.cashier !== this.menuCashierFilter) return false;
            if (this.menuSearchTerm && !s.name.toLowerCase().includes(this.menuSearchTerm.toLowerCase())) return false;
            return true;
        });
        const cashiers = ['All', ...new Set(stats.menuItemSales.map(m => m.cashier))];

        return `
            <div class="space-y-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-4 border-b border-white/5">
                    <div class="flex gap-4">
                        ${['Food','Drinks'].map(t => `<button onclick="ReportHub.setMenuTab('${t}')" class="text-[10px] font-black uppercase tracking-widest pb-3 border-b-2 transition-all ${this.menuSalesTab === t ? 'text-[#d4af37] border-[#d4af37]' : 'text-gray-500 border-transparent hover:text-white'}">${t} Sales</button>`).join('')}
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                         <div class="relative group"><i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-600"></i><input type="text" oninput="ReportHub.setMenuSearch(this.value)" placeholder="Search item..." class="bg-white/5 border border-white/10 rounded-2xl pl-10 pr-5 py-2.5 text-[10px] font-black text-white outline-none w-52 focus:border-[#d4af37]/30"></div>
                         <select onchange="ReportHub.setMenuCashier(this.value)" class="bg-[#0f1110] border border-white/10 rounded-2xl px-5 py-2.5 text-[10px] font-black text-gray-400 outline-none w-48">${cashiers.map(c => `<option value="${c}" ${this.menuCashierFilter === c ? 'selected' : ''}>${c}</option>`).join('')}</select>
                         <button onclick="ReportHub.exportMenuCSV()" class="w-11 h-11 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-500 hover:text-white outline-none"><i data-lucide="download" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <div class="glass rounded-[2rem] border border-white/5 overflow-hidden">
                    <table class="w-full text-left text-[11px]">
                        <thead><tr class="text-[9px] uppercase font-black tracking-widest text-gray-600 bg-white/[0.03] border-b border-white/5"><th class="px-8 py-5">Menu Item</th><th class="px-8 py-5">Aggregated Cashier</th><th class="px-8 py-5 text-right">Quantity Sold</th><th class="px-8 py-5 text-right">Revenue Generated</th></tr></thead>
                        <tbody class="divide-y divide-white/5">
                            ${filtered.sort((a,b)=>b.revenue-a.revenue).map(s => `<tr class="hover:bg-white/[0.02] transition-colors"><td class="px-8 py-5"><p class="font-black text-white">${s.name}</p><p class="text-[9px] text-gray-600 font-bold uppercase mt-0.5">${s.category}</p></td><td class="px-8 py-5 text-gray-400 font-bold">${s.cashier}</td><td class="px-8 py-5 text-right font-black text-white text-base">${s.quantity}</td><td class="px-8 py-5 text-right font-mono font-bold text-[#f3cf7a]">${this.fmt(s.revenue)}</td></tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
    },

    renderCashierInsights() {
        const stats = this.getCalculatedStats();
        const cashiers = Object.entries(stats.cashierRevenue).map(([name, amt]) => {
            const count = this.orders.filter(o => (o.createdBy?.name || 'Unknown Cashier') === name && o.status !=='cancelled').length;
             const items = stats.menuItemSales.filter(m => m.cashier === name);
             return { name, amount: amt, count, items };
        });
        if (!cashiers.length) return `<div class="py-40 text-center text-[10px] uppercase font-black text-gray-600 tracking-[0.4em]">No cashier data available for this period</div>`;
        const active = cashiers[this.activeCashierIdx % cashiers.length];
        const pct = stats.orderRevenue > 0 ? ((active.amount/stats.orderRevenue)*100).toFixed(1) : 0;

        return `
            <div class="space-y-8">
                <div class="flex items-center justify-between px-2">
                    <button onclick="ReportHub.navCashier(-1)" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-gray-500 hover:text-[#d4af37] transition-all outline-none"><i data-lucide="chevron-left"></i></button>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">${this.activeCashierIdx + 1} / ${cashiers.length} Staff Members</p>
                    <button onclick="ReportHub.navCashier(1)" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-gray-500 hover:text-[#d4af37] transition-all outline-none"><i data-lucide="chevron-right"></i></button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass p-8 rounded-[3rem] border border-white/5 text-center"><div class="w-20 h-20 rounded-full bg-[#d4af37]/10 border border-[#d4af37]/20 flex items-center justify-center mx-auto mb-6 text-[#d4af37]"><i data-lucide="user" class="w-10 h-10"></i></div><h4 class="text-2xl font-black font-playfair italic text-[#f3cf7a] mb-1 line-clamp-1">${active.name}</h4><p class="text-[9px] uppercase font-black tracking-widest text-[#d4af37]/60">Active this period</p></div>
                    <div class="glass p-8 rounded-[3rem] bg-[#d4af37]/5 border border-[#d4af37]/10 text-center"><p class="text-[9px] uppercase font-black tracking-widest text-[#d4af37] mb-6">Total Contribution</p><h4 class="text-3xl font-black text-white mb-2 underline decoration-[#d4af37] underline-offset-8">${this.fmt(active.amount)}</h4><p class="text-[10px] font-bold text-[#d4af37]/60">${pct}% of order revenue</p></div>
                    <div class="glass p-8 rounded-[3rem] border border-white/5 text-center"><p class="text-[9px] uppercase font-black tracking-widest text-gray-600 mb-6">Orders Handled</p><h4 class="text-4xl font-black text-white">${active.count}</h4><p class="text-[10px] font-bold text-gray-600 mt-2">Closed Transactions</p></div>
                </div>
                <div class="glass p-8 rounded-[3rem] border border-white/5">
                    <h5 class="text-[10px] uppercase font-black tracking-widest text-gray-500 mb-6">Items Sold by Staff</h5>
                    <div class="space-y-4">
                        ${(active.items || []).sort((a,b)=>b.revenue-a.revenue).slice(0,10).map(i => `
                            <div class="flex items-center justify-between pb-4 border-b border-white/5"><div><p class="font-black text-white text-sm">${i.name}</p><p class="text-[8px] uppercase font-black text-gray-600 tracking-tighter">${i.category}</p></div><div class="text-right"><p class="text-[#f3cf7a] font-mono font-bold">${this.fmt(i.revenue)}</p><p class="text-[10px] text-gray-600 font-bold">${i.quantity} units</p></div></div>`).join('')}
                    </div>
                </div>
            </div>`;
    },

    // ─── HELPERS & SUB-LOGIC ───────────────────────────────────────────────────
    setLoading(s) { document.getElementById('loading-bar')?.classList.toggle('opacity-100', s); },
    showError(m) { /* Silenced JS Alert as requested */ console.error(m); },
    api(method, url) { return fetch(url, { method }).then(r => r.json()); },
    fmt(n) { return Number(n||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' Br'; },
    
    getQueryString() {
        const url = new URLSearchParams();
        url.append('period', this.timeRange);
        if (this.timeRange === 'custom') {
            url.append('startDate', this.selectedDate.toISOString().split('T')[0]);
            url.append('endDate', this.selectedDate.toISOString().split('T')[0]);
        }
        return `?${url.toString()}`;
    },

    getOrdersQuery() {
        const url = new URLSearchParams();
        url.append('limit', 1000);
        url.append('includeDeleted', 'true');
        const now = new Date();
        const start = new Date();
        if (this.timeRange === 'week') start.setDate(now.getDate() - 7);
        else if (this.timeRange === 'month') start.setDate(now.getDate() - 30);
        else if (this.timeRange === 'year') start.setDate(now.getDate() - 365);
        else if (this.timeRange === 'custom') {
            start.setTime(this.selectedDate.getTime());
            now.setTime(this.selectedDate.getTime());
            now.setHours(23,59,59);
        }
        url.append('startDate', start.toISOString());
        return `?${url.toString()}`;
    },

    setTimeRange(r) {
        this.timeRange = r;
        document.querySelectorAll('.range-btn').forEach(b => {
             const act = b.id === `range-btn-${r}`;
             b.classList.toggle('bg-[#d4af37]', act);
             b.classList.toggle('text-black', act);
             b.classList.toggle('shadow-[#d4af37]/20', act);
             b.classList.toggle('text-gray-500', !act);
             b.classList.toggle('cursor-pointer', true);
        });
        this.fetchAllData();
    },

    setCustomDate(v) {
        if (!v) return;
        this.selectedDate = new Date(v);
        this.timeRange = 'custom';
        document.querySelectorAll('.range-btn').forEach(b => {
             b.classList.remove('bg-[#d4af37]', 'text-black', 'shadow-[#d4af37]/20');
             b.classList.add('text-gray-500');
        });
        this.fetchAllData();
    },

    setOrderTab(t) { this.orderHistoryTab = t; this.renderActiveSlide(); },
    setMenuTab(t) { this.menuSalesTab = t; this.renderActiveSlide(); },
    setMenuSearch(v) { this.menuSearchTerm = v; this.renderActiveSlide(); },
    setMenuCashier(v) { this.menuCashierFilter = v; this.renderActiveSlide(); },
    navCashier(d) {
        let next = this.activeCashierIdx + d;
        if (next < 0) next = 0;
        this.activeCashierIdx = next;
        this.renderActiveSlide();
    },

    mobileStatCard(l, v, c) {
        const cls = { emerald:'text-emerald-400 decoration-emerald-500/30', red:'text-red-400 decoration-red-500/30' }[c] || 'text-white';
        return `<div class="glass p-6 rounded-[2rem] border border-white/5"><p class="text-[9px] uppercase font-black tracking-widest text-gray-600 mb-2">${l}</p><h4 class="text-2xl font-black font-mono underline decoration-4 underline-offset-8 ${cls}">${this.fmt(v)}</h4></div>`;
    },

    // ─── EXPORT LOGIC ────────────────────────────────────────────────────────────
    exportFinancial() {
        const stats = this.getCalculatedStats();
        const rows = [
            ['Total Revenue', 'INCOME', stats.totalRevenue, 'Order + Bedroom Revenue'],
            ['Reception Revenue', 'INCOME', this.receptionRevenue, 'Booking Income'],
            ['Food Revenue', 'BREAKDOWN', stats.foodRevenue, 'Food Sales'],
            ['Drinks Revenue', 'BREAKDOWN', stats.drinksRevenue, 'Drinks Sales'],
            ['Operational Expenses', 'EXPENSE', stats.totalOperationalExpenses, 'Overhead'],
            ['Stock Investment', 'EXPENSE', stats.periodInvestment, 'Cost of Goods'],
            ['Net Profit', 'PROFIT', stats.periodProfit, 'Final Outcome'],
        ];
        ReportExporter.toWord(`Financial-Summary-${this.timeRange}.doc`, 'Financial Summary Statement', rows);
    },

    exportOrdersCSV(tab) {
        // Spec Headers: Date, Time, Order#, Table, Item, Category, Qty, Unit Price, Total, Cashier, Floor
        const headers = ['Date', 'Time', 'Order#', 'Table', 'Item', 'Category', 'Qty', 'Unit Price', 'Total', 'Cashier', 'Floor'];
        const csvRows = [];
        this.orders.forEach(o => {
            if (o.status === 'cancelled') return;
            (o.items||[]).forEach(i => {
                if (tab !== 'All' && i.mainCategory !== tab) return;
                csvRows.push([
                    new Date(o.createdAt).toLocaleDateString(),
                    new Date(o.createdAt).toLocaleTimeString(),
                    o.orderNumber,
                    o.tableNumber || 'Walking',
                    i.name,
                    i.category || 'General',
                    i.quantity,
                    i.price,
                    (i.price * i.quantity),
                    o.createdBy?.name || '—',
                    o.floor || '—'
                ]);
            });
        });
        ReportExporter.toCSV(`Orders-${tab}-${this.timeRange}.csv`, headers, csvRows);
    },

    exportInventoryCSV() {
        // Spec Headers: Item Name, Unit Cost, Quantity, Total Purchase, Consumed, Remains, Potential Rev, Status
        const u = this.stockUsageData?.stockAnalysis || [];
        const h = ['Item Name', 'Unit Cost', 'Quantity', 'Total Purchase', 'Consumed', 'Remains', 'Potential Rev', 'Status'];
        const rows = u.map(i => [i.name, i.currentUnitCost, i.openingStock, (i.openingStock*i.weightedAvgCost), i.consumed, i.closingStock, (i.closingStock*i.currentUnitCost), i.isLowStock?'LOW':'OK']);
        ReportExporter.toCSV(`Inventory-Investment-${this.timeRange}.csv`, h, rows);
    },

    exportMenuCSV() {
        // Spec Headers: Menu Item, Cashier, Sub Category, Quantity Sold, Total Revenue
        const stats = this.getCalculatedStats();
        const f = stats.menuItemSales.filter(i => i.mainCategory === this.menuSalesTab);
        const h = ['Menu Item', 'Cashier', 'Sub Category', 'Quantity Sold', 'Total Revenue'];
        const rows = f.map(i => [i.name, i.cashier, i.category, i.quantity, i.revenue]);
        ReportExporter.toCSV(`Menu-Sales-${this.menuSalesTab}.csv`, h, rows);
    }
};

const ReportExporter = {
    toCSV(filename, headers, rows) {
        const csv = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url; a.download = filename; a.click();
        URL.revokeObjectURL(url);
    },

    toWord(filename, title, dataRows) {
        const tableHtml = `
            <table>
                <tr><th>Metric</th><th>Type</th><th>Amount</th><th>Description</th></tr>
                ${dataRows.map(r => `<tr><td>${r[0]}</td><td>${r[1]}</td><td>${Number(r[2]).toLocaleString()} Br</td><td>${r[3]}</td></tr>`).join('')}
            </table>
        `;
        const header = `
            <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
            <head><meta charset='utf-8'><title>${title}</title>
            <style>
                body { font-family: 'Segoe UI', Arial; }
                table { border-collapse: collapse; width: 100%; border: 1px solid #ddd; }
                th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                h1 { color: #d4af37; border-bottom: 2px solid #d4af37; padding-bottom: 10px; }
            </style></head><body>
            <p style="text-align:right">${window.companyName}</p>
            <h1>${title}</h1>
            <p>Period: ${ReportHub.timeRange} (${new Date().toLocaleDateString()})</p><br>
        `;
        const source = header + tableHtml + "</body></html>";
        const blob = new Blob(['\ufeff', source], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url; a.download = filename; a.click();
        URL.revokeObjectURL(url);
    }
};

document.addEventListener('DOMContentLoaded', () => ReportHub.init());
