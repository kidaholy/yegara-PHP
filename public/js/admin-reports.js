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
    timeRange: 'month',
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
    menuSalesTab: 'All',
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
            const startTime = Date.now();
            const [salesRes, ordersRes, receptionRes] = await Promise.all([
                this.api('GET', `api/reports/sales.php${query}`),
                this.api('GET', `api/reports/orders.php${query}&limit=1000&includeDeleted=true`),
                this.api('GET', `api/reports/bedroom-revenue.php${query}`, { optional: true })
            ]);

            this.periodData = salesRes?.data || null;
            this.orders = Array.isArray(ordersRes) ? ordersRes : (ordersRes?.data || []);
            this.receptionRevenue = receptionRes?.data?.totalRevenue || 0;

            const elapsed = Date.now() - startTime;
            if (elapsed < 1500) await new Promise(r => setTimeout(r, 1500 - elapsed));
            
            this.setLoading(false);
            this.renderActiveSlide();

            await this.fetchSecondaryData(query);

        } catch (e) {
            console.error('Core data fetch failed:', e);
            this.showError('Critical data load failed.');
            this.setLoading(false);
        }
    },

    async fetchSecondaryData(query) {
        try {
            const [stockRes, usageRes] = await Promise.all([
                this.api('GET', 'api/stock.php', { optional: true }),
                this.api('GET', `api/reports/stock-usage.php${query}`)
            ]);
            this.stockItems = stockRes || [];
            this.stockUsageData = usageRes?.data || null;
            
            this.renderActiveSlide();
        } catch (e) { console.warn('Secondary data load failed:', e); }
    },

    // ─── CALCULATIONS & AGGREGATIONS ─────────────────────────────────────────────
    getCalculatedStats() {
        const s = this.periodData?.summary || {};
        const rev = s.totalRevenue || 0;
        const totalRev = rev + this.receptionRevenue;
        let periodInvest = (s.periodStockInvestment || 0) + (s.totalOtherExpenses || 0);
        
        // If secondary data (stockUsageData) is loaded, override with accurate "Current Inventory Value"
        if (this.stockUsageData && this.stockUsageData.stockAnalysis) {
            const inventoryValue = this.stockUsageData.stockAnalysis.reduce((sum, item) => sum + ((item.closingStock || 0) * (item.weightedAvgCost || 0)), 0);
            periodInvest = inventoryValue + (s.totalOtherExpenses || 0);
        }
        
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
                if (item.mainCategory && item.mainCategory.toLowerCase() === 'food') foodRev += (item.price * item.quantity);
                if (item.mainCategory && item.mainCategory.toLowerCase() === 'drinks') drinksRev += (item.price * item.quantity);
            });
        });

        // Aggregated Menu Sales (aggregated by "name | cashier")
        const menuSales = {};
        this.orders.forEach(o => {
            if (o.status === 'cancelled' || o.isDeleted) return;
            (o.items || []).forEach(item => {
                const cashierName = o.createdBy?.name || 'Unknown';
                const key = `${item.name}|${cashierName}`;
                if (!menuSales[key]) {
                    menuSales[key] = { 
                        name: item.name, 
                        cashier: cashierName,
                        category: item.category,
                        mainCategory: item.mainCategory || 'Food',
                        quantity: 0,
                        revenue: 0 
                    };
                }
                menuSales[key].quantity += parseFloat(item.quantity);
                menuSales[key].revenue += (item.price * item.quantity);
            });
        });

        // Cashier Stats (Detailed)
        const cashierStats = {};
        let totalOrdersCount = 0;
        let foodOrdersCount = 0;
        let drinksOrdersCount = 0;

        this.orders.forEach(o => {
            if (o.status === 'cancelled' || o.isDeleted) return;
            totalOrdersCount++;
            
            let hasFood = false;
            let hasDrinks = false;
            (o.items||[]).forEach(i => {
                if ((i.mainCategory||'').toLowerCase() === 'food') hasFood = true;
                if ((i.mainCategory||'').toLowerCase() === 'drinks') hasDrinks = true;
            });
            if (hasFood) foodOrdersCount++;
            if (hasDrinks) drinksOrdersCount++;

            const name = o.createdBy?.name || 'Unknown Cashier';
            if (!cashierStats[name]) cashierStats[name] = { amount: 0, count: 0, food: 0, drinks: 0, foodCount: 0, drinksCount: 0 };
            cashierStats[name].amount += parseFloat(o.totalAmount || 0);
            cashierStats[name].count++;
            if (hasFood) cashierStats[name].foodCount++;
            if (hasDrinks) cashierStats[name].drinksCount++;

            (o.items || []).forEach(item => {
                const cat = (item.mainCategory || '').toLowerCase();
                const itemRev = (item.price * item.quantity);
                if (cat === 'food') cashierStats[name].food += itemRev;
                else if (cat === 'drinks') cashierStats[name].drinks += itemRev;
            });
        });

        return { 
            totalRevenue: totalRev, 
            orderRevenue: rev,
            foodRevenue: foodRev, 
            drinksRevenue: drinksRev,
            cashierStats: cashierStats,
            totalOrdersCount: totalOrdersCount,
            foodOrdersCount: foodOrdersCount,
            drinksOrdersCount: drinksOrdersCount,
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
            b.classList.toggle('bg-gray-800', act);
            b.classList.toggle('border-gray-700', act);
            b.classList.toggle('text-gray-200', act);
            b.classList.toggle('shadow-sm', act);
            
            b.classList.toggle('bg-transparent', !act);
            b.classList.toggle('border-transparent', !act);
            b.classList.toggle('text-gray-500', !act);
            b.classList.toggle('hover:text-gray-300', !act);
            b.classList.toggle('hover:bg-gray-800/30', !act);
            
            const iconBox = b.querySelector('.w-8');
            if (iconBox) {
                iconBox.classList.toggle('bg-[#c5a059]', act);
                iconBox.classList.toggle('text-gray-900', act);
                iconBox.classList.toggle('bg-gray-800', !act);
                iconBox.classList.toggle('text-gray-500', !act);
                iconBox.classList.toggle('group-hover:text-gray-400', !act);
            }
        });
        document.querySelectorAll('.report-nav-btn-mobile').forEach((b, i) => {
            const act = i === idx;
            b.classList.toggle('bg-[#c5a059]', act);
            b.classList.toggle('text-gray-900', act);
            b.classList.toggle('border-[#c5a059]', act);
            
            b.classList.toggle('bg-gray-800', !act);
            b.classList.toggle('border-gray-700', !act);
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
            case 'inventory': html = this.renderInventory(); break;
            case 'store': html = this.renderStore(); break;
            case 'menu-sales': html = this.renderMenuSales(); break;
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
        Object.entries(stats.cashierStats).forEach(([name, cStat]) => {
            const amt = cStat.amount;
            const pct = stats.orderRevenue > 0 ? Math.round((amt/stats.orderRevenue)*100) : 0;
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
                <div class="hidden md:block rounded-xl border border-gray-700/50 bg-gray-800/20 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-700/50 flex justify-between items-center bg-gray-800/50">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-200">Statement of Accounts</h3>
                        <button onclick="ReportHub.exportFinancial()" class="text-xs font-semibold uppercase text-gray-400 hover:text-white transition-colors flex items-center gap-2 outline-none">
                             <i data-lucide="file-text" class="w-4 h-4"></i> Export Word
                        </button>
                    </div>
                    <table class="w-full text-left">
                        <thead><tr class="text-xs font-semibold uppercase tracking-wider text-gray-500 bg-gray-800/20 border-b border-gray-700/50"><th class="px-6 py-4">Metric</th><th class="px-6 py-4">Type</th><th class="px-6 py-4 text-right">Amount</th></tr></thead>
                        <tbody class="divide-y divide-gray-700/30">
                            ${rows.map(r => {
                                const cls = { emerald:'text-emerald-400 bg-emerald-500/10 border-emerald-500/20', 
                                              red:'text-red-400 bg-red-500/10 border-red-500/20',
                                              blue:'text-blue-400 bg-blue-500/10 border-blue-500/20',
                                              gold:'text-[#c5a059] bg-[#c5a059]/10 border-[#c5a059]/20',
                                              gray:'text-gray-400 bg-gray-700 border-gray-600' }[r.c];
                                return `<tr class="hover:bg-gray-800/50 transition-colors group"><td class="px-6 py-4"><p class="text-sm font-bold text-gray-200">${r.m}</p><p class="text-xs text-gray-500 font-semibold mt-1">${r.d}</p></td><td class="px-6 py-4"><span class="px-2 py-1 rounded-md border text-xs font-bold ${cls}">${r.t}</span></td><td class="px-6 py-4 text-right font-bold text-base ${r.v >= 0 ? 'text-gray-200' : 'text-red-400'}">${r.v < 0 ? '-' : '+'}${this.fmt(Math.abs(r.v))}</td></tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    },


    renderInventory() {
        const u = this.stockUsageData?.stockAnalysis || [];
        const lowCount = u.filter(i => i.isLowStock).length;
        return `
            <div class="space-y-6">
                ${lowCount > 0 ? `<div class="flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400"><i data-lucide="alert-triangle" class="w-4 h-4"></i><p class="text-xs font-bold uppercase tracking-wider">${lowCount} Low Stock items alert.</p></div>` : ''}
                <div class="flex justify-between items-center px-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Inventory Turnover · ${u.length} Items</h3>
                    <div class="flex items-center gap-4">
                        <button onclick="ReportHub.confirmWipe('all_stock')" class="px-3 py-1.5 rounded-lg border border-red-500/20 bg-red-500/10 text-red-400 text-[10px] font-bold uppercase tracking-widest hover:bg-red-500/20 transition-all flex items-center gap-2">
                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                            Clear Stock
                        </button>
                        <button onclick="ReportHub.exportInventoryCSV()" class="text-xs font-semibold uppercase text-gray-400 hover:text-white transition-colors flex items-center gap-2 outline-none"><i data-lucide="download" class="w-4 h-4"></i> Export CSV</button>
                    </div>
                </div>
                <div class="hidden lg:block rounded-xl border border-gray-700/50 bg-gray-800/20 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="text-xs font-semibold uppercase tracking-wider text-gray-500 bg-gray-800/50 border-b border-gray-700/50"><th class="px-6 py-4">Item Name</th><th class="px-6 py-4 text-right">Sell Price</th><th class="px-6 py-4 text-right">Remains</th><th class="px-6 py-4 text-right">Investment (@avg)</th><th class="px-6 py-4 text-right">Usage</th><th class="px-6 py-4 text-right">Potential Value</th><th class="px-6 py-4 text-center">Status</th></tr></thead>
                        <tbody class="divide-y divide-gray-700/30">
                            ${u.filter(i => Math.round(i.closingStock||0) > 0).map(i => `
                                <tr class="${i.isLowStock ? 'bg-red-500/5' : ''} hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4"><p class="font-bold text-gray-200">${i.name}</p><p class="text-xs text-gray-500 font-semibold mt-1">${i.category}</p></td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-300">${this.fmt(i.currentUnitCost)}</td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-200">${this.fmtQty(i.closingStock)} ${i.unit}</td>
                                    <td class="px-6 py-4 text-right font-bold text-[#c5a059]">${this.fmt(i.weightedAvgCost * i.closingStock)}</td>
                                    <td class="px-6 py-4 text-right text-emerald-400 font-bold">-${this.fmtQty(i.consumed)}</td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-200">${this.fmt(i.currentUnitCost * i.closingStock)}</td>
                                    <td class="px-6 py-4 text-center"><span class="px-2 py-1 rounded-md border text-xs font-bold ${i.isLowStock ? 'text-red-400 border-red-500/20 bg-red-500/10' : 'text-emerald-400 border-emerald-500/20 bg-emerald-500/10'}">${i.isLowStock ? 'LOW' : 'OK'}</span></td>
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
                <div class="flex justify-between items-center px-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Store Investment Analytics</h3>
                    <div class="flex items-center gap-4">
                        <button onclick="ReportHub.confirmWipe('all_store')" class="px-3 py-1.5 rounded-lg border border-red-500/20 bg-red-500/10 text-red-400 text-[10px] font-bold uppercase tracking-widest hover:bg-red-500/20 transition-all flex items-center gap-2">
                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                            Clear Store
                        </button>
                        <button onclick="ReportHub.confirmWipe('all')" class="px-3 py-1.5 rounded-lg border border-gray-700/50 bg-gray-800/40 text-gray-400 text-[10px] font-bold uppercase tracking-widest hover:bg-gray-800 transition-all flex items-center gap-2">
                            Wipe Global
                        </button>
                    </div>
                </div>
                <div class="hidden lg:block rounded-xl border border-gray-700/50 bg-gray-800/20 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="text-xs font-semibold uppercase tracking-wider text-gray-500 bg-gray-800/50 border-b border-gray-700/50"><th class="px-6 py-4">Item Name</th><th class="px-6 py-4 text-right">Unit Cost</th><th class="px-6 py-4 text-right">In Store</th><th class="px-6 py-4 text-right">IN (Period)</th><th class="px-6 py-4 text-right">OUT (Period)</th><th class="px-6 py-4 text-right">Total Inv.</th><th class="px-6 py-4 text-center">Status</th></tr></thead>
                        <tbody class="divide-y divide-gray-700/30">
                            ${u.filter(i => (i.storeQuantity||0) > 0 || (i.storeIn||0) > 0 || (i.storeOut||0) > 0).map(i => `
                                <tr class="hover:bg-gray-800/50 transition-colors"><td class="px-6 py-4"><p class="font-bold text-gray-200">${i.name}</p><p class="text-xs text-gray-500 font-semibold mt-1">${i.category}</p></td><td class="px-6 py-4 text-right font-bold text-gray-300">${this.fmt(i.weightedAvgCost).replace(' Br','')}</td><td class="px-6 py-4 text-right font-bold text-gray-200">${this.fmtQty(i.storeQuantity)} ${i.unit}</td><td class="px-6 py-4 text-right text-emerald-400 font-bold">+${this.fmtQty(i.storeIn)}</td><td class="px-6 py-4 text-right text-red-400 font-bold">-${this.fmtQty(i.storeOut)}</td><td class="px-6 py-4 text-right font-bold text-[#c5a059]">${this.fmt(i.storeClosingValue)}</td><td class="px-6 py-4 text-center"><span class="px-2 py-1 rounded-md border border-gray-600 bg-gray-700/50 text-[10px] font-bold uppercase text-gray-400">STORE_OK</span></td></tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>`;
    },

    renderMenuSales() {
        const stats = this.getCalculatedStats();
        const filtered = stats.menuItemSales.filter(s => {
            const currentTab = this.menuSalesTab.toLowerCase();
            const itemMainCat = (s.mainCategory || '').toLowerCase();
            if (currentTab !== 'all' && itemMainCat !== currentTab) return false;
            if (this.menuCashierFilter !== 'All' && s.cashier !== this.menuCashierFilter) return false;
            if (this.menuSearchTerm && !s.name.toLowerCase().includes(this.menuSearchTerm.toLowerCase())) return false;
            return true;
        });
        const cashiers = ['All', ...new Set(stats.menuItemSales.map(m => m.cashier))];
        
        // Cashier Summary Cards
        let summaryHtml = '';
        const isAllCashier = this.menuCashierFilter === 'All';
        const currentTab = this.menuSalesTab; // 'All', 'Food', 'Drinks'
        
        const rawStat = isAllCashier 
            ? { 
                amount: stats.orderRevenue, 
                count: stats.totalOrdersCount, 
                food: stats.foodRevenue, 
                drinks: stats.drinksRevenue,
                foodCount: stats.foodOrdersCount,
                drinksCount: stats.drinksOrdersCount
              }
            : (stats.cashierStats[this.menuCashierFilter] || { amount: 0, count: 0, food: 0, drinks: 0, foodCount: 0, drinksCount: 0 });
        
        // Active display metrics based on ALL/FOOD/DRINKS tab
        let activeRevenue = rawStat.amount;
        let activeCount = rawStat.count;
        let revLabel = 'Total Revenue';
        let resColor = 'emerald-400';
        let subLabel = isAllCashier ? 'CONSOLIDATED EARNINGS' : 'Contribution to Revenue';

        if (currentTab === 'Food') {
            activeRevenue = rawStat.food;
            activeCount = rawStat.foodCount;
            revLabel = 'Food Revenue';
            resColor = '#c5a059'; // Gold
            subLabel = 'Culinary Performance';
        } else if (currentTab === 'Drinks') {
            activeRevenue = rawStat.drinks;
            activeCount = rawStat.drinksCount;
            revLabel = 'Drinks Revenue';
            resColor = 'blue-400';
            subLabel = 'Beverage Sales';
        }

        const displayName = isAllCashier ? 'ALL STAFF MEMBERS' : this.menuCashierFilter;
        const consistencyText = isAllCashier ? 'SYSTEM STABILITY OK' : 'CONSISTENCY OK';

        summaryHtml = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <!-- Staff Member Card -->
                <div class="p-8 rounded-2xl border border-gray-800 bg-[#111413] relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <i data-lucide="${isAllCashier ? 'users' : 'user'}" class="w-32 h-32"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-[#c5a059] uppercase tracking-[0.2em] mb-4">${isAllCashier ? 'Organization' : 'Staff Member'}</p>
                        <h4 class="text-2xl font-serif italic font-bold text-gray-100 leading-tight mb-6 uppercase">${displayName}</h4>
                        <div class="flex items-center gap-2 text-emerald-500">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                            <p class="text-[10px] font-bold uppercase tracking-widest">Active · ${currentTab}</p>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Revenue Card -->
                <div class="p-8 rounded-2xl border border-gray-800 bg-[#111413] flex flex-col justify-center">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-4">${revLabel}</p>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-4xl font-black text-[${resColor}]">${Number(activeRevenue).toLocaleString('en-US', {maximumFractionDigits:0})}</h4>
                        <span class="text-xl font-bold text-[${resColor}]/50">Br</span>
                    </div>
                    <p class="text-[10px] font-black text-gray-600 uppercase tracking-widest mt-4">${subLabel}</p>
                </div>

                <!-- Dynamic Orders Card -->
                <div class="p-8 rounded-2xl border border-gray-800 bg-[#111413] flex flex-col justify-center">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] mb-4">Orders Handled</p>
                    <h4 class="text-4xl font-black text-gray-100">${activeCount}</h4>
                    <div class="flex items-center gap-2 text-[#c5a059] mt-4">
                        <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                        <p class="text-[10px] font-black uppercase tracking-widest">${consistencyText}</p>
                    </div>
                </div>
            </div>
        `;

        return `
            <div class="space-y-8">
                <!-- Header with Icon & Title -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-gray-800 border border-gray-700 flex items-center justify-center text-[#c5a059] shadow-lg shadow-black/20">
                            <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                        </div>
                        <h2 class="text-2xl font-serif italic font-bold text-gray-100">Menu Item Sales</h2>
                    </div>
                    
                    <div class="relative group">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                        <input type="text" oninput="ReportHub.setMenuSearch(this.value)" 
                            placeholder="Search menu items..." 
                            class="bg-[#111413] border border-gray-800 rounded-xl pl-12 pr-6 py-3 text-sm text-gray-200 outline-none w-full lg:w-80 focus:border-[#c5a059]/50 transition-all placeholder:text-gray-600">
                    </div>
                </div>

                <!-- Filters & Tabs -->
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex bg-gray-900/50 p-1 rounded-xl border border-gray-800">
                        ${['All', 'Food', 'Drinks'].map(t => `
                            <button onclick="ReportHub.setMenuTab('${t}')" 
                                class="px-6 py-2 rounded-lg text-[11px] font-black uppercase tracking-widest transition-all ${this.menuSalesTab === t ? 'bg-[#c5a059]/10 text-[#c5a059] shadow-inner shadow-black/20' : 'text-gray-500 hover:text-gray-300'}">
                                ${t}
                            </button>`).join('')}
                    </div>

                    <select onchange="ReportHub.setMenuCashier(this.value)" 
                        class="bg-[#111413] border border-gray-800 rounded-xl px-5 py-2.5 text-xs font-black uppercase tracking-wider text-gray-400 outline-none focus:border-[#c5a059]/50 transition-all min-w-[240px]">
                        ${cashiers.map(c => `<option value="${c}" ${this.menuCashierFilter === c ? 'selected' : ''}>${c.toUpperCase()}</option>`).join('')}
                    </select>

                    <button onclick="ReportHub.exportMenuCSV()" 
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-emerald-500/20 bg-emerald-500/5 text-emerald-500 text-[11px] font-black uppercase tracking-widest hover:bg-emerald-500 hover:text-white transition-all shadow-lg shadow-emerald-950/20">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i> CSV
                    </button>
                </div>

                <!-- Summary Stats -->
                ${summaryHtml}

                <!-- Table Content -->
                <div class="rounded-2xl border border-gray-800/50 bg-[#111413]/30 overflow-hidden backdrop-blur-sm shadow-2xl">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black uppercase tracking-[0.15em] border-b border-gray-800/50 bg-gray-900/20">
                                <th class="px-8 py-5 text-gray-500">Menu Item</th>
                                <th class="px-8 py-5 text-gray-500">Cashier</th>
                                <th class="px-8 py-5 text-emerald-400 text-right">Quantity Sold</th>
                                <th class="px-8 py-5 text-[#c5a059] text-right">Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/30">
                            ${filtered.sort((a,b)=>b.quantity-a.quantity).map(s => `
                                <tr class="hover:bg-gray-800/20 transition-all group">
                                    <td class="px-8 py-6">
                                        <p class="font-bold text-gray-200 text-sm group-hover:text-white">${s.name}</p>
                                        <p class="text-[9px] font-black text-[#c5a059] uppercase tracking-widest mt-1.5 opacity-60 group-hover:opacity-100 transition-opacity">${s.category}</p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-tight group-hover:text-gray-400">${s.cashier}</p>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <p class="text-lg font-black text-emerald-400 flex items-center justify-end gap-1.5">
                                            ${Math.round(s.quantity)}
                                            <span class="text-[9px] text-emerald-400/50 tracking-widest">SOLD</span>
                                        </p>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <p class="text-base font-black text-gray-200 flex items-center justify-end gap-1.5">
                                            ${this.fmt(s.revenue).replace(' Br','')}
                                            <span class="text-[9px] text-[#c5a059] tracking-widest font-black">BR</span>
                                        </p>
                                    </td>
                                </tr>`).join('') || `<tr><td colspan="4" class="py-20 text-center text-[10px] font-black text-gray-600 uppercase tracking-[0.3em]">No matching sales found</td></tr>`}
                        </tbody>
                    </table>
                </div>
            </div>`;
    },

    // ─── HELPERS & SUB-LOGIC ───────────────────────────────────────────────────
    setLoading(s) { document.getElementById('loading-bar')?.classList.toggle('opacity-100', s); },
    showError(m) { console.error(m); },
    async api(method, url, opts = {}) {
        const res = await fetch(url, { method, credentials: 'same-origin' });
        const text = await res.text();
        let json;
        try { json = JSON.parse(text); } catch { json = null; }
        if (!res.ok || (json?.status === 'error')) {
            if (opts.optional) return null;
            throw new Error(json?.message || `Request failed: ${url}`);
        }
        return json;
    },
    fmt(n) { return Number(n||0).toLocaleString('en-US', {minimumFractionDigits:0, maximumFractionDigits:0}) + ' Br'; },
    fmtQty(n) { const v = n||0; return (v % 1 === 0 ? v : parseFloat(v.toFixed(2))).toLocaleString(); },
    
    getQueryString() {
        const params = new URLSearchParams();
        params.append('period', this.timeRange);
        if (this.timeRange === 'custom') {
            const d = this.selectedDate.toISOString().split('T')[0];
            params.append('startDate', d);
            params.append('endDate', d);
        }
        return `?${params.toString()}`;
    },

    setTimeRange(r) {
        this.timeRange = r;
        document.querySelectorAll('.range-btn').forEach(b => {
             const act = b.id === `range-btn-${r}`;
             b.classList.toggle('bg-[#c5a059]', act);
             b.classList.toggle('text-gray-900', act);
             
             b.classList.toggle('text-gray-500', !act);
             b.classList.toggle('hover:text-white', !act);
             b.classList.toggle('hover:bg-gray-800', !act);
        });
        this.fetchAllData();
    },

    setCustomDate(v) {
        if (!v) return;
        this.selectedDate = new Date(v);
        this.timeRange = 'custom';
        document.querySelectorAll('.range-btn').forEach(b => {
             b.classList.remove('bg-[#c5a059]', 'text-gray-900');
             b.classList.add('text-gray-500', 'hover:text-white', 'hover:bg-gray-800');
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

    async confirmWipe(type = 'all') {
        const labels = {
            'all_stock': 'ALL ACTIVE POS STOCK (Front House)',
            'all_store': 'ALL BULK STORE QUANTITIES (Warehouse)',
            'all': 'ABSOLUTELY EVERYTHING (Store + Stock)'
        };
        const label = labels[type] || 'everything';

        if (!confirm(`CRITICAL ACTION: This will set ${label} to ZERO. Are you absolutely sure?`)) return;
        if (!confirm('FINAL CONFIRMATION: This action is irreversible. Proceed?')) return;
        
        try {
            const res = await fetch(`api/stock.php?id=${type}`, { method: 'DELETE' });
            const json = await res.json();
            if (res.ok) {
                alert(`Success: ${label} has been wiped.`);
                this.fetchAllData();
            } else {
                throw new Error(json.message || 'Failed to wipe quantities');
            }
        } catch (err) {
            alert('Error: ' + err.message);
        }
    },

    mobileStatCard(l, v, c) {
        const cls = { emerald:'text-emerald-400', red:'text-red-400' }[c] || 'text-gray-200';
        return `<div class="p-5 rounded-xl border border-gray-700/50 bg-gray-800/40"><p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">${l}</p><h4 class="text-xl font-bold ${cls}">${this.fmt(v)}</h4></div>`;
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
                    Math.round(i.quantity||0),
                    Math.round(i.price||0),
                    Math.round((i.price||0) * (i.quantity||0)),
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
        const rows = u.map(i => [i.name, Math.round(i.currentUnitCost||0), Math.round(i.openingStock||0), Math.round((i.openingStock||0)*(i.weightedAvgCost||0)), Math.round(i.consumed||0), Math.round(i.closingStock||0), Math.round((i.closingStock||0)*(i.currentUnitCost||0)), i.isLowStock?'LOW':'OK']);
        ReportExporter.toCSV(`Inventory-Investment-${this.timeRange}.csv`, h, rows);
    },

    exportMenuCSV() {
        const stats = this.getCalculatedStats();

        // Use the exact same filter logic as the rendered report table
        const filtered = stats.menuItemSales.filter(s => {
            const currentTab = this.menuSalesTab.toLowerCase();
            const itemMainCat = (s.mainCategory || '').toLowerCase();
            if (currentTab !== 'all' && itemMainCat !== currentTab) return false;
            if (this.menuCashierFilter !== 'All' && s.cashier !== this.menuCashierFilter) return false;
            if (this.menuSearchTerm && !s.name.toLowerCase().includes(this.menuSearchTerm.toLowerCase())) return false;
            return true;
        }).sort((a, b) => b.quantity - a.quantity);

        const period = this.timeRange.toUpperCase();
        const cashierLabel = this.menuCashierFilter === 'All' ? 'All Staff' : this.menuCashierFilter;
        const tabLabel = this.menuSalesTab;

        const h = ['Menu Item', 'Category', 'Sub-Category', 'Cashier', 'Quantity Sold', 'Revenue (Br)'];

        const rows = filtered.map(i => [
            i.name,
            i.mainCategory || 'Food',
            i.category || 'General',
            i.cashier,
            Math.round(i.quantity || 0),
            Math.round(i.revenue || 0)
        ]);

        // Totals row
        const totalQty = filtered.reduce((s, i) => s + (i.quantity || 0), 0);
        const totalRev = filtered.reduce((s, i) => s + (i.revenue || 0), 0);
        rows.push(['', '', '', 'TOTAL', Math.round(totalQty), Math.round(totalRev)]);

        // Metadata banner rows at top
        const meta = [
            [`Menu Item Sales Report — Period: ${period} | Filter: ${tabLabel} | Cashier: ${cashierLabel}`, '', '', '', '', ''],
            [`Generated: ${new Date().toLocaleString()}`, '', '', '', '', ''],
            ['', '', '', '', '', ''],
        ];

        const filename = `Menu-Sales-${tabLabel}-${cashierLabel.replace(/\s+/g, '_')}-${period}.csv`;
        ReportExporter.toCSV(filename, h, [...meta, ...rows]);
    }
};

const ReportExporter = {
    toCSV(filename, headers, rows) {
        const csvString = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
        const blob = new Blob(['\uFEFF' + csvString], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url; a.download = filename; a.click();
        URL.revokeObjectURL(url);
    },

    toWord(filename, title, dataRows) {
        const tableHtml = `
            <table>
                <tr><th>Metric</th><th>Type</th><th>Amount</th><th>Description</th></tr>
                ${dataRows.map(r => `<tr><td>${r[0]}</td><td>${r[1]}</td><td>${Number(r[2]||0).toLocaleString('en-US', {maximumFractionDigits:0})} Br</td><td>${r[3]}</td></tr>`).join('')}
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
