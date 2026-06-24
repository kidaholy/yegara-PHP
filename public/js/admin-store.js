/**
 * Admin Store — Full "Dark Luxury" Controller
 * 5 tabs: Inventory | Fixed Assets | Categories | Expenses | Transfers
 */

// ─── STATE ───────────────────────────────────────────────────────────────────
const S = {
    activeTab: 'inventory',
    role: window.storeRole || 'admin',
    isAdmin: (window.storeRole || 'admin') === 'admin',
    items: [], expenses: [], assets: [], transfers: [],
    stockCats: [], assetCats: [], expenseCats: [],
    searchTerm: '',
    expenseDateFilter: 'all',
    transferFilter: 'all',
    transferSearch: '',
    expandedAsset: null,
    // current modal context ids
    editItemId: null,
    restockItemId: null,
    transferItemId: null,
    editAssetId: null,
    dismissAssetId: null,
    editExpenseId: null,
    denialRequestId: null,
    catActiveSubtab: 'stock',
    inventoryCategoryFilter: 'All',
};

// ─── INIT ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    if (S.role === 'store_keeper') switchTab('transfers');
    else switchTab('inventory');
    await fetchAll();
    lucide.createIcons();
});

async function fetchAll() {
    showLoader(true);
    try {
        const [items, expenses, assets, transfers, stockCats, assetCats, expenseCats] = await Promise.all([
            api('GET', 'api/stock.php'),
            api('GET', 'api/operational-expenses.php'),
            api('GET', 'api/fixed-assets.php'),
            api('GET', 'api/inventory-transfers.php'),
            api('GET', 'api/categories.php?type=stock'),
            api('GET', 'api/categories.php?type=fixed-asset'),
            api('GET', 'api/categories.php?type=expense'),
        ]);
        S.items      = items      || [];
        S.expenses   = expenses   || [];
        S.assets     = assets     || [];
        S.transfers  = transfers  || [];
        S.stockCats  = stockCats  || [];
        S.assetCats  = assetCats  || [];
        S.expenseCats= expenseCats|| [];
    } catch(e) { console.error(e); }
    finally { showLoader(false); }
    renderSidebar();
    renderTab();
    lucide.createIcons();
}

// ─── API HELPER ───────────────────────────────────────────────────────────────
async function api(method, url, body) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const r = await fetch(url, opts);
    const json = await r.json();
    if (!r.ok) throw new Error(json.message || 'Request failed');
    return json;
}

// ─── SIDEBAR STATS ────────────────────────────────────────────────────────────
function renderSidebar() {
    const f = S.inventoryCategoryFilter;
    const invItems = (S.activeTab === 'inventory') ? S.items.filter(i => matchesInvCatFilter(i, f)) : S.items;
    
    const storeVal     = invItems.reduce((a, i) => a + (i.totalInvestment || (i.storeQuantity||0) * (i.averagePurchasePrice||0)), 0);
    const assetVal     = S.assets.reduce((a, x) => a + (x.total_value||x.totalValue||0), 0);
    const monthExpense = S.expenses.reduce((a, e) => a + (e.amount||0), 0);

    setText('si-store-value',  fmt(storeVal));
    setText('si-sku-count',    invItems.length);
    setText('si-asset-value',  fmt(assetVal));
    setText('si-asset-count',  S.assets.length);
    setText('si-expense-total',fmt(monthExpense));

    // Update label to reflect filter
    const label = document.querySelector('#si-store-value + p') || document.querySelector('#si-sku-count + p');
    const title = document.querySelector('h2.text-lg.lg\\:text-xl.font-bold.text-white');
    if (title) title.textContent = f === 'All' ? 'Store' : `${f} Store`;
}

// ─── TAB ──────────────────────────────────────────────────────────────────────
window.switchTab = function(tab) {
    S.activeTab = tab;
    document.querySelectorAll('.store-tab-btn').forEach(b => {
        const active = b.dataset.tab === tab;
        b.classList.toggle('active', active);
        b.classList.toggle('text-[#c5a059]', active);
        b.classList.toggle('text-gray-400', !active);
    });
    renderTab();
};

function renderTab() {
    const el = document.getElementById('tab-content');
    if (!el) return;
    const map = {
        inventory:     renderInventory,
        'fixed-assets':renderAssets,
        categories:    renderCategories,
        expenses:      renderExpenses,
        transfers:     renderTransfers,
    };
    el.innerHTML = (map[S.activeTab] || renderInventory)();
    lucide.createIcons();
}

// Category tab helper (shared logic)
function matchesInvCatFilter(item, filter) {
    if (filter === 'All') return true;
    const cat = (item.category || '').toLowerCase();
    const isDrink = cat === 'drinks' || cat === 'wiski' || cat === 'drink' || cat === 'beverage';
    const isFood  = cat === 'food'   || cat === 'meat'  || cat === 'foods';
    if (filter === 'Food')   return isFood;
    if (filter === 'Drinks') return isDrink;
    return true;
}

window.setInventoryCategoryFilter = function(f) {
    S.inventoryCategoryFilter = f;
    renderSidebar(); // Update stats to reflect the category distribution
    renderTab(); // Will trigger renderInventory
};

// ─── TAB 1: BULK INVENTORY ────────────────────────────────────────────────────
function renderInventory() {
    const term = S.searchTerm.toLowerCase();
    const items = S.items.filter(i => {
        const matchesSearch = (i.name||'').toLowerCase().includes(term) || (i.category||'').toLowerCase().includes(term);
        const matchesCat    = matchesInvCatFilter(i, S.inventoryCategoryFilter);
        return matchesSearch && matchesCat;
    });

    const rows = items.map(i => {
        const storeQty = i.storeQuantity || 0;
        const posQty   = i.quantity || 0;
        const lowStore = i.storeMinLimit && storeQty <= i.storeMinLimit;
        return `
        <tr class="hover:bg-gray-800/50 transition-colors group border-b border-gray-700/30">
          <td class="p-3 lg:p-4">
            <p class="text-xs lg:text-sm font-bold text-gray-200 leading-tight">${esc(i.name)}</p>
            <p class="text-[10px] lg:text-xs font-semibold text-gray-500 mt-0.5">${esc(i.category)} · ${i.unit}</p>
          </td>
          <td class="p-3 lg:p-4 text-center lg:text-left">
            <span class="text-lg lg:text-xl font-bold ${lowStore ? 'text-amber-400' : 'text-white'}">${storeQty}</span>
            <span class="text-[10px] text-gray-500 ml-0.5 lg:ml-1">${i.unit}</span>
            ${lowStore ? '<span class="block lg:inline-block mt-1 lg:mt-0 lg:ml-2 px-1.5 py-0.5 rounded-md bg-amber-500/10 text-amber-400 text-[9px] lg:text-xs font-bold border border-amber-500/20">LOW</span>' : ''}
          </td>
          <td class="p-3 lg:p-4 hidden sm:table-cell">
            ${posQty > 0
              ? `<span class="px-2 lg:px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] lg:text-xs font-semibold">${posQty} ${i.unit}</span>`
              : `<span class="px-2 lg:px-2.5 py-1 rounded-lg bg-gray-800 text-gray-500 border border-gray-700 text-[10px] lg:text-xs font-semibold">0</span>`
            }
          </td>
          <td class="p-3 lg:p-4 text-right sm:text-left">
            <div class="flex items-center justify-end sm:justify-start gap-1 lg:gap-2">
              <button onclick="openTransfer('${i.id}')" ${storeQty <= 0 ? 'disabled' : ''} title="Transfer to POS"
                class="w-7 h-7 lg:w-8 lg:h-8 rounded-lg flex items-center justify-center border transition-colors
                ${storeQty > 0 ? 'bg-[#c5a059]/10 border-[#c5a059]/30 text-[#c5a059]' : 'bg-gray-800 border-gray-700 text-gray-600 cursor-not-allowed'}">
                <i data-lucide="arrow-right-left" class="w-3 lg:w-3.5 h-3 lg:h-3.5"></i>
              </button>
              <button onclick="openRestock('${i.id}')"
                class="w-7 h-7 lg:w-8 lg:h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center hover:bg-blue-500 hover:text-white">
                <i data-lucide="plus-circle" class="w-3 lg:w-3.5 h-3 lg:h-3.5"></i>
              </button>
              <button onclick="openEditItem('${i.id}')"
                class="w-7 h-7 lg:w-8 lg:h-8 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 flex items-center justify-center">
                <i data-lucide="pencil" class="w-3 lg:w-3.5 h-3 lg:h-3.5"></i>
              </button>
            </div>
          </td>
        </tr>`;
    });

    if (!rows.length) return empty('No inventory items found. Add your first bulk item above.');

    return `
    <div class="rounded-xl border border-gray-700/50 overflow-hidden">
      <!-- Header with Tabs + Export -->
      <div class="flex flex-col md:flex-row md:items-center justify-between px-5 py-4 border-b border-gray-700/50 bg-gray-800/50 gap-4">
        <div class="flex items-center gap-4">
          <div class="flex bg-gray-900/50 p-1 rounded-lg border border-gray-700/50">
            ${['All', 'Food', 'Drinks'].map(t => `
              <button onclick="setInventoryCategoryFilter('${t}')"
                class="px-4 py-1 rounded-md text-[10px] font-black uppercase tracking-widest transition-all
                ${S.inventoryCategoryFilter === t ? 'bg-[#c5a059]/10 text-[#c5a059]' : 'text-gray-500 hover:text-gray-300'}">
                ${t}
              </button>`).join('')}
          </div>
          <h3 class="hidden lg:block text-[10px] font-black uppercase tracking-widest text-gray-500">
            ${items.length} Items &nbsp;·&nbsp; ${items.filter(i=>(i.storeQuantity||0)>0).length} in Bulk
          </h3>
        </div>
        
        <button onclick="exportCSV('inventory')" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-800 border border-gray-700 text-xs font-semibold text-gray-400 hover:text-white transition-colors w-fit">
          <i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-800/50 border-b border-gray-700/50">
              <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500">Item</th>
              <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500 text-center lg:text-left">Store</th>
              <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500 hidden sm:table-cell">POS</th>
              <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500 text-right sm:text-left">Actions</th>
            </tr>
          </thead>
          <tbody>${rows.join('')}</tbody>
        </table>
      </div>
    </div>`;
}

// ─── TAB 2: FIXED ASSETS ─────────────────────────────────────────────────────
function renderAssets() {
    if (!S.assets.length) return empty('No fixed assets. Add your first asset using the sidebar.');
    const cards = filtered(S.assets, 'name').map(a => {
        const status = a.status || 'active';
        const statusColors = { active: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
                               partial: 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                               dismissed: 'text-red-400 bg-red-500/10 border-red-500/20' };
        const col = statusColors[status] || statusColors.active;
        const history = a.dismissals || [];
        const totalVal = a.totalValue || (a.quantity * a.unitPrice) || 0;
        const valueLost = history.reduce((sum, d) => sum + (d.valueLost||0), 0);
        return `
        <div class="bg-gray-800/60 p-5 rounded-2xl border border-gray-700/50 hover:bg-gray-800 transition-colors">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h4 class="text-base font-bold text-gray-200">${esc(a.name)}</h4>
              <p class="text-xs font-semibold text-gray-500">${esc(a.category||'')}</p>
            </div>
            <span class="px-2.5 py-1 rounded-md border text-xs font-bold uppercase ${col}">${status}</span>
          </div>
          <div class="grid grid-cols-3 gap-2 mb-4">
            <div class="p-2.5 rounded-lg bg-gray-900 border border-gray-700 text-center">
              <p class="text-base font-bold text-white">${a.quantity||0}</p>
              <p class="text-xs font-semibold text-gray-500">Units</p>
            </div>
            <div class="p-2.5 rounded-lg bg-gray-900 border border-gray-700 text-center">
              <p class="text-sm font-bold text-white">${fmt(totalVal)}</p>
              <p class="text-xs font-semibold text-gray-500">Total Value</p>
            </div>
            <div class="p-2.5 rounded-lg bg-red-500/5 border border-red-500/10 text-center">
              <p class="text-sm font-bold text-red-400">${fmt(valueLost)}</p>
              <p class="text-xs font-semibold text-gray-500">Value Lost</p>
            </div>
          </div>
          ${S.isAdmin ? `
          <div class="flex items-center gap-2 pt-3 border-t border-gray-700/50">
            <button onclick="openDismiss('${a.id}')" class="flex-1 py-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs font-semibold hover:bg-amber-500 hover:text-gray-900 transition-colors">Dismiss</button>
            <button onclick="openEditAsset('${a.id}')" class="w-8 h-8 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 flex items-center justify-center hover:text-white transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
            <button onclick="deleteAsset('${a.id}')" class="w-8 h-8 rounded-lg bg-gray-800 border border-gray-700 text-red-500 flex items-center justify-center hover:bg-red-600 hover:text-white transition-colors"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
            ${history.length ? `<button onclick="toggleAssetHistory('${a.id}')" class="w-8 h-8 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 flex items-center justify-center hover:text-white transition-colors"><i data-lucide="history" class="w-3.5 h-3.5"></i></button>` : ''}
          </div>` : ''}
          ${S.expandedAsset === a.id && history.length ? `
          <div class="mt-3 space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Dismissal History</p>
            ${history.map(h => `
            <div class="p-3 rounded-lg bg-gray-900 border border-gray-700 text-xs">
              <div class="flex justify-between"><span class="text-gray-300 font-semibold">${h.reason||''}</span><span class="text-red-400 font-bold">${fmt(h.valueLost||0)} lost</span></div>
              <p class="text-gray-500 mt-1">${h.date ? new Date(h.date).toLocaleDateString() : ''} · ${h.quantity||0} units</p>
            </div>`).join('')}
          </div>` : ''}
        </div>`;
    });
    return `
    <div class="flex items-center justify-between mb-5">
      <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400">${S.assets.length} Fixed Assets · Total: ${fmt(S.assets.reduce((a,x)=>a+(x.totalValue||0),0))}</h3>
      <button onclick="exportCSV('assets')" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-800 border border-gray-700 text-xs font-semibold text-gray-400 hover:text-white transition-colors"><i data-lucide="download" class="w-3.5 h-3.5"></i> Export CSV</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">${cards.join('')}</div>`;
}

window.toggleAssetHistory = function(id) {
    S.expandedAsset = S.expandedAsset === id ? null : id;
    renderTab();
};

// ─── TAB 3: CATEGORIES ────────────────────────────────────────────────────────
function renderCategories() {
    const subtabs = [['stock','Stock'],['fixed-asset','Fixed Asset'],['expense','Expense']];
    const catMap  = { stock: S.stockCats, 'fixed-asset': S.assetCats, expense: S.expenseCats };
    const cats    = catMap[S.catActiveSubtab] || [];

    return `
    <div class="space-y-6">
      <div class="flex gap-3">
        ${subtabs.map(([k,l]) => `
        <button onclick="setCatSubtab('${k}')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all
          ${S.catActiveSubtab===k ? 'bg-[#1a1712] text-[#d4af37] border-[#d4af37]/30' : 'bg-white/5 text-gray-500 border-white/5 hover:text-white'}">
          ${l}
        </button>`).join('')}
      </div>
      ${S.isAdmin ? `
      <form onsubmit="addCategory(event)" class="flex gap-3">
        <input type="text" name="catName" placeholder="New ${S.catActiveSubtab} category..." required
          class="flex-1 bg-white/5 border border-white/5 rounded-2xl px-5 py-3 text-sm font-bold text-white focus:border-[#d4af37]/30 outline-none">
        <button type="submit" class="px-6 py-3 rounded-2xl bg-[#d4af37] text-black font-black text-[10px] uppercase tracking-widest hover:scale-[1.02] transition-all">Add</button>
      </form>` : ''}
      <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        ${cats.map(c => `
        <div class="glass p-5 rounded-[1.5rem] border border-white/5 group">
          <p class="text-sm font-black text-white mb-1">${esc(c.name)}</p>
          <p class="text-[9px] uppercase font-black tracking-widest text-gray-600">${S.catActiveSubtab}</p>
          ${S.isAdmin ? `
          <div class="flex gap-2 mt-4 opacity-0 group-hover:opacity-100 transition-opacity">
            <button onclick="deleteCategory('${c.id}')" class="flex-1 py-1.5 rounded-xl bg-red-500/10 text-red-400 text-[9px] font-black uppercase hover:bg-red-500 hover:text-white transition-all">Delete</button>
          </div>` : ''}
        </div>`).join('')}
        ${!cats.length ? `<p class="col-span-3 text-[10px] uppercase font-black tracking-widest text-gray-600 pt-8 text-center">No ${S.catActiveSubtab} categories yet.</p>` : ''}
      </div>
    </div>`;
}

window.setCatSubtab = function(k) { S.catActiveSubtab = k; renderTab(); };

window.addCategory = async function(e) {
    e.preventDefault();
    const name = e.target.catName.value.trim();
    if (!name) return;
    try {
        await api('POST', 'api/categories.php', { name, type: S.catActiveSubtab });
        e.target.reset();
        await fetchAll();
    } catch(err) { alert(err.message); }
};

window.deleteCategory = async function(id) {
    if (!confirm('Delete category? Existing items will not be removed.')) return;
    try { await api('DELETE', `api/categories.php?id=${id}`); await fetchAll(); }
    catch(err) { alert(err.message); }
};

// ─── TAB 4: OPERATIONAL EXPENSES ─────────────────────────────────────────────
function renderExpenses() {
    const now   = new Date();
    const filt  = S.expenseDateFilter;
    let exps = S.expenses;
    if (filt === 'today') exps = exps.filter(e => sameDay(e.date || e.recorded_at, now));
    else if (filt === 'week') exps = exps.filter(e => withinDays(e.date || e.recorded_at, 7));
    else if (filt === 'month') exps = exps.filter(e => sameMonth(e.date || e.recorded_at, now));

    const rows = exps.filter(e => esc(e.name||'').toLowerCase().includes(S.searchTerm.toLowerCase())).map(e => `
    <tr class="hover:bg-white/[0.02] border-b border-white/5 group transition-colors">
      <td class="p-3 lg:p-5 hidden md:table-cell"><p class="text-[10px] font-bold text-gray-400">${e.date ? new Date(e.date).toLocaleDateString() : '—'}</p></td>
      <td class="p-3 lg:p-5">
        <p class="text-xs lg:text-sm font-black text-white">${esc(e.name)}</p>
        <p class="text-[9px] text-gray-600 uppercase font-black tracking-widest mt-0.5 md:hidden">${e.date ? new Date(e.date).toLocaleDateString() : ''} · ${esc(e.category||'')}</p>
      </td>
      <td class="p-3 lg:p-5 hidden sm:table-cell"><span class="px-2 py-1 rounded-full bg-white/5 text-[9px] font-black text-gray-500 border border-white/5">${esc(e.category||'')}</span></td>
      <td class="p-3 lg:p-5 font-mono text-xs lg:text-sm text-white hidden lg:table-cell">${fmt(e.unit_cost||e.unitCost||0)}</td>
      <td class="p-3 lg:p-5 font-mono text-xs lg:text-sm text-white">${(e.quantity||0)} ${e.unit||''}</td>
      <td class="p-3 lg:p-5 font-mono text-sm font-black text-[#f3cf7a]">${fmt(e.amount||0)}</td>
      <td class="p-3 lg:p-5">
        ${S.isAdmin ? `
        <div class="flex gap-1.5 lg:gap-2">
          <button onclick="openRestockExpense('${e.id}')"
            class="w-7 h-7 lg:w-8 lg:h-8 rounded-xl bg-white/5 text-blue-400 flex items-center justify-center"><i data-lucide="plus-circle" class="w-3 lg:w-3.5 h-3 lg:h-3.5"></i></button>
          <button onclick="deleteExpense('${e.id}')"
            class="w-7 h-7 lg:w-8 lg:h-8 rounded-xl bg-white/5 text-red-400 flex items-center justify-center"><i data-lucide="trash-2" class="w-3 lg:w-3.5 h-3 lg:h-3.5"></i></button>
        </div>` : ''}
      </td>
    </tr>`);

    return `
    <div class="space-y-6">
      <div class="flex items-center gap-3 flex-wrap">
        ${['today','week','month','all'].map(f => `
        <button onclick="setExpFilter('${f}')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all
          ${S.expenseDateFilter===f ? 'bg-[#1a1712] text-[#d4af37] border-[#d4af37]/30' : 'bg-white/5 text-gray-500 border-white/5 hover:text-white'}">
          ${f}
        </button>`).join('')}
        <div class="ml-auto flex gap-3">
          ${S.isAdmin ? `<button onclick="openExpenseForm()" class="px-5 py-2 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-black uppercase hover:bg-emerald-500 hover:text-white transition-all flex items-center gap-2"><i data-lucide="plus" class="w-3.5 h-3.5"></i> New Expense</button>` : ''}
          <button onclick="exportCSV('expenses')" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/5 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-white transition-all"><i data-lucide="download" class="w-3.5 h-3.5"></i> CSV</button>
        </div>
      </div>
      <div class="glass rounded-[2rem] border border-white/5 overflow-hidden shadow-xl">
        <table class="w-full text-left">
          <thead class="bg-white/[0.03] border-b border-white/5">
            <tr>
              <th class="p-3 lg:p-5 text-[9px] uppercase font-black tracking-widest text-gray-600 hidden md:table-cell">Date</th>
              <th class="p-3 lg:p-5 text-[9px] uppercase font-black tracking-widest text-gray-600">Item</th>
              <th class="p-3 lg:p-5 text-[9px] uppercase font-black tracking-widest text-gray-600 hidden sm:table-cell">Category</th>
              <th class="p-3 lg:p-5 text-[9px] uppercase font-black tracking-widest text-gray-600 hidden lg:table-cell">Unit Cost</th>
              <th class="p-3 lg:p-5 text-[9px] uppercase font-black tracking-widest text-gray-600">Qty</th>
              <th class="p-3 lg:p-5 text-[9px] uppercase font-black tracking-widest text-gray-600">Amount</th>
              <th class="p-3 lg:p-5 text-[9px] uppercase font-black tracking-widest text-gray-600 text-right sm:text-left"></th>
            </tr>
          </thead>
          <tbody>${rows.length ? rows.join('') : `<tr><td colspan="7" class="py-16 text-center text-[10px] uppercase font-black text-gray-600">No expenses for this period.</td></tr>`}</tbody>
        </table>
      </div>
    </div>`;
}

window.setExpFilter = function(f) { S.expenseDateFilter = f; renderTab(); };

window.deleteExpense = async function(id) {
    if (!confirm('Delete this expense?')) return;
    try { await api('DELETE', `api/operational-expenses.php?id=${id}`); await fetchAll(); }
    catch(e) { alert(e.message); }
};

// ─── TAB 5: TRANSFERS ────────────────────────────────────────────────────────
function renderTransfers() {
    let list = S.transfers;
    if (S.transferFilter !== 'all') list = list.filter(t => t.status === S.transferFilter);
    if (S.transferSearch) list = list.filter(t => {
        const item = S.items.find(i => i.id == t.stockId);
        return (item?.name||'').toLowerCase().includes(S.transferSearch.toLowerCase());
    });

    const statusColors = {
        pending:  'text-amber-400 bg-amber-400/10 border-amber-400/20',
        approved: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
        denied:   'text-red-400 bg-red-500/10 border-red-500/20',
    };

    const cards = list.map(t => {
        const item = S.items.find(i => i.id == t.stockId);
        const col  = statusColors[t.status] || statusColors.pending;
        return `
        <div class="glass p-6 rounded-[2rem] border border-white/5 shadow-xl">
          <div class="flex items-start justify-between mb-4">
            <div>
              <h4 class="text-base font-black font-playfair italic text-[#f3cf7a]">${item ? esc(item.name) : 'Unknown Item'}</h4>
              <p class="text-[9px] uppercase font-black tracking-widest text-gray-600">${item ? esc(item.category||'') : ''}</p>
            </div>
            <span class="px-2.5 py-1 rounded-full border text-[8px] font-black uppercase ${col}">${t.status||'pending'}</span>
          </div>
          <div class="flex items-center justify-between mb-4 p-4 rounded-2xl bg-white/5 border border-white/5">
            <div>
              <p class="text-2xl font-bold text-white">${t.quantity||0} <span class="text-sm text-gray-500">${item?.unit||''}</span></p>
              <p class="text-[9px] uppercase font-black tracking-widest text-gray-600">Requested</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] font-bold text-gray-400">${t.requestedBy||'—'}</p>
              <p class="text-[9px] text-gray-600">${t.createdAt ? new Date(t.createdAt).toLocaleDateString() : '—'}</p>
            </div>
          </div>
          ${t.notes ? `<p class="text-[10px] text-gray-500 mb-4 italic">"${esc(t.notes)}"</p>` : ''}
          ${t.denialReason ? `<p class="text-[10px] text-red-400 mb-4">Denial reason: ${esc(t.denialReason)}</p>` : ''}
          ${S.isAdmin && t.status === 'pending' ? `
          <div class="flex gap-3 pt-4 border-t border-white/5">
            <button onclick="approveTransfer('${t.id}')" class="flex-1 py-2.5 rounded-xl bg-[#d4af37] text-black font-black text-[9px] uppercase tracking-widest hover:scale-[1.02] transition-all">Approve</button>
            <button onclick="openDenial('${t.id}')"    class="flex-1 py-2.5 rounded-xl bg-red-600/80 text-white font-black text-[9px] uppercase tracking-widest hover:bg-red-600 transition-all">Deny</button>
          </div>` : ''}
        </div>`;
    });

    return `
    <div class="space-y-6">
      <div class="flex items-center gap-3 flex-wrap">
        ${['all','pending','approved','denied'].map(f => `
        <button onclick="setTransferFilter('${f}')" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border transition-all
          ${S.transferFilter===f ? 'bg-[#1a1712] text-[#d4af37] border-[#d4af37]/30' : 'bg-white/5 text-gray-500 border-white/5 hover:text-white'}">
          ${f}${f==='pending' && S.transfers.filter(t=>t.status==='pending').length ? ` (${S.transfers.filter(t=>t.status==='pending').length})` : ''}
        </button>`).join('')}
      </div>
      ${!cards.length ? `<div class="py-20 text-center"><p class="text-4xl mb-4">📦</p><p class="text-[10px] uppercase font-black tracking-widest text-gray-600">No ${S.transferFilter} transfer requests.</p></div>` : ''}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">${cards.join('')}</div>
    </div>`;
}

window.setTransferFilter = function(f) { S.transferFilter = f; renderTab(); };

window.approveTransfer = async function(id) {
    try { await api('PATCH', `api/inventory-transfers.php?id=${id}`, { action:'approved' }); await fetchAll(); }
    catch(e) { alert(e.message); }
};

// ─── MODAL HELPERS ───────────────────────────────────────────────────────────
function showModal(id) { document.getElementById(id)?.classList.remove('hidden'); lucide.createIcons(); }
function hideModal(id) { document.getElementById(id)?.classList.add('hidden'); }
window.closeModal = function(id) { hideModal(id); };

// ── Restock ──
window.openRestock = function(itemId) {
    S.restockItemId = itemId;
    const i = S.items.find(x => x.id === itemId);
    if (!i) return;
    document.getElementById('restock-item-name').textContent = i.name;
    document.getElementById('restock-current').textContent   = `${i.storeQuantity||0} ${i.unit}`;
    document.getElementById('restock-qty').value   = '';
    document.getElementById('restock-unit-price').value = i.averagePurchasePrice || 0;
    document.getElementById('restock-upc').value   = i.unitCost || 0;
    document.getElementById('restock-notes').value = '';
    showModal('modal-restock');
};

window.submitRestock = async function(e) {
    e.preventDefault();
    const id  = S.restockItemId;
    const qty  = parseFloat(document.getElementById('restock-qty').value);
    const unitPrice = parseFloat(document.getElementById('restock-unit-price').value);
    const upc  = document.getElementById('restock-upc').value;
    const notes= document.getElementById('restock-notes').value;
    try {
        await api('PUT', `api/stock.php?id=${id}`, { action:'restock', quantityAdded: qty, unitPrice, newUnitCost: upc, notes });
        hideModal('modal-restock');
        await fetchAll();
    } catch(err) { alert(err.message); }
};

// ── Transfer ──
window.openTransfer = function(itemId) {
    S.transferItemId = itemId;
    const i = S.items.find(x => x.id === itemId);
    if (!i) return;
    document.getElementById('transfer-item-name').textContent  = i.name;
    document.getElementById('transfer-store-qty').textContent  = `${i.storeQuantity||0} ${i.unit} available`;
    document.getElementById('transfer-qty').value  = '';
    document.getElementById('transfer-notes').value = '';
    showModal('modal-transfer');
};

window.submitTransfer = async function(e) {
    e.preventDefault();
    const id    = S.transferItemId;
    const qty   = parseFloat(document.getElementById('transfer-qty').value);
    const notes = document.getElementById('transfer-notes').value;
    try {
        if (S.isAdmin) {
            await api('POST', 'api/store/transfer.php', { stockId: id, quantity: qty, notes });
        } else {
            await api('POST', 'api/inventory-transfers.php', { stockId: id, quantity: qty, notes });
        }
        hideModal('modal-transfer');
        await fetchAll();
    } catch(err) { alert(err.message); }
};

// ── Add/Edit Item ──
function populateItemCategories(selected = '') {
    const sel = document.getElementById('item-category');
    if (!sel) return;
    sel.innerHTML = '<option value="">-- Select Category --</option>' + S.stockCats.map(c => `<option value="${esc(c.name)}"${c.name===selected ? ' selected' : ''}>${esc(c.name)}</option>`).join('');
}

window.openAddItem = function() {
    S.editItemId = null;
    resetItemForm();
    document.getElementById('item-form-title').textContent = 'New Store Item';
    populateItemCategories();
    showModal('modal-item');
};

window.openEditItem = function(id) {
    S.editItemId = id;
    const i = S.items.find(x => x.id === id);
    if (!i) return;
    document.getElementById('item-form-title').textContent = 'Edit Item';
    populateItemCategories(i.category);
    setFormVals({
        'item-name': i.name, 'item-unit': i.unit||'pcs',
        'item-store-qty': i.storeQuantity||0, 'item-min-limit': i.minLimit||5,
        'item-store-min': i.storeMinLimit||20, 'item-buy-price': i.averagePurchasePrice||0,
        'item-sell-price': i.unitCost||0,
    });
    showModal('modal-item');
};

function resetItemForm() {
    setFormVals({ 'item-name':'','item-category':'','item-unit':'pcs',
                  'item-store-qty':0,'item-min-limit':5,'item-store-min':20,
                  'item-buy-price':0,'item-sell-price':0 });
}

window.submitItemForm = async function(e) {
    e.preventDefault();
    const d = {
        name: val('item-name'), category: val('item-category'), unit: val('item-unit'),
        storeQuantity: parseFloat(val('item-store-qty')||0),
        minLimit: parseFloat(val('item-min-limit')||5),
        storeMinLimit: parseFloat(val('item-store-min')||20),
        averagePurchasePrice: parseFloat(val('item-buy-price')||0),
        unitCost: parseFloat(val('item-sell-price')||0),
    };
    try {
        if (S.editItemId) await api('PUT', `api/stock.php?id=${S.editItemId}`, d);
        else await api('POST', 'api/stock.php', d);
        hideModal('modal-item');
        await fetchAll();
    } catch(err) { alert(err.message); }
};

window.deleteItem = async function(id) {
    if (!confirm('Remove from bulk store? POS stock will be preserved.')) return;
    try { await api('DELETE', `api/stock.php?id=${id}&source=store`); await fetchAll(); }
    catch(e) { alert(e.message); }
};

// ── Fixed Asset ──
window.openAddAsset = function() { 
    S.editAssetId = null; 
    resetAssetForm(); 
    document.getElementById('asset-form-title').textContent = 'New Fixed Asset'; 
    populateAssetCategories();
    showModal('modal-asset'); 
};
window.openEditAsset = function(id) { 
    S.editAssetId = id; 
    const a = S.assets.find(x => x.id === id); 
    if(!a) return; 
    document.getElementById('asset-form-title').textContent = 'Edit Asset'; 
    populateAssetCategories(a.category);
    setFormVals({'asset-name':a.name,'asset-qty':a.quantity||1,'asset-price':a.unit_price||a.unitPrice||0,'asset-date':a.purchase_date||a.purchaseDate||''}); 
    showModal('modal-asset'); 
};
function resetAssetForm() { setFormVals({'asset-name':'','asset-qty':1,'asset-price':0,'asset-date':''}); }
function populateAssetCategories(selected = '') {
    const sel = document.getElementById('asset-category');
    if (!sel) return;
    sel.innerHTML = '<option value="">-- Select Category --</option>' + S.assetCats.map(c => `<option value="${esc(c.name)}"${c.name===selected ? ' selected' : ''}>${esc(c.name)}</option>`).join('');
}

window.submitAssetForm = async function(e) {
    e.preventDefault();
    const d = { name: val('asset-name'), category: val('asset-category'), quantity: parseFloat(val('asset-qty')||1), unitPrice: parseFloat(val('asset-price')||0), purchaseDate: val('asset-date') };
    try {
        if (S.editAssetId) await api('PUT', `api/fixed-assets.php?id=${S.editAssetId}`, d);
        else await api('POST', 'api/fixed-assets.php', d);
        hideModal('modal-asset');
        await fetchAll();
    } catch(err) { alert(err.message); }
};

window.deleteAsset = async function(id) {
    if (!confirm('Delete this fixed asset?')) return;
    try { await api('DELETE', `api/fixed-assets.php?id=${id}`); await fetchAll(); }
    catch(e) { alert(e.message); }
};

// ── Dismiss Asset ──
window.openDismiss = function(id) { S.dismissAssetId=id; const a=S.assets.find(x=>x.id===id); if(!a) return; document.getElementById('dismiss-asset-name').textContent=a.name; document.getElementById('dismiss-qty').value=''; document.getElementById('dismiss-reason').value=''; document.getElementById('dismiss-value-lost').value=''; showModal('modal-dismiss'); };
window.submitDismiss = async function(e) {
    e.preventDefault();
    const id = S.dismissAssetId;
    try {
        await api('PUT', `api/fixed-assets.php?id=${id}`, { action:'dismiss', quantity: parseFloat(val('dismiss-qty')), reason: val('dismiss-reason'), valueLost: parseFloat(val('dismiss-value-lost')||0) });
        hideModal('modal-dismiss');
        await fetchAll();
    } catch(err) { alert(err.message); }
};

// ── Expense Form ──
function populateExpenseCategories() {
    const sel = document.getElementById('exp-category');
    if (!sel) return;
    sel.innerHTML = '<option value="">-- Select Category --</option>' + S.expenseCats.map(c => `<option value="${esc(c.name)}">${esc(c.name)}</option>`).join('');
}
window.openExpenseForm = function() { 
    document.getElementById('exp-date').value = today(); 
    document.getElementById('exp-name').value = ''; 
    populateExpenseCategories(); 
    document.getElementById('exp-unit-cost').value = ''; 
    document.getElementById('exp-qty').value = ''; 
    document.getElementById('exp-unit').value = 'pcs'; 
    document.getElementById('exp-desc').value = ''; 
    showModal('modal-expense'); 
};
window.submitExpenseForm = async function(e) {
    e.preventDefault();
    const d = { name: val('exp-name'), category: val('exp-category'), date: val('exp-date'), unitCost: parseFloat(val('exp-unit-cost')||0), quantity: parseFloat(val('exp-qty')||0), unit: val('exp-unit'), description: val('exp-desc') };
    try { await api('POST', 'api/operational-expenses.php', d); hideModal('modal-expense'); await fetchAll(); }
    catch(err) { alert(err.message); }
};

// ── Restock Expense Record ──
window.openRestockExpense = function(id) {
    S.restockExpId = id;
    const e = S.expenses.find(x => x.id === id);
    if (!e) return;
    document.getElementById('res-exp-name').textContent = e.name;
    document.getElementById('res-exp-current').textContent = `${e.quantity||0} ${e.unit||''}`;
    document.getElementById('res-exp-qty').value = '';
    document.getElementById('res-exp-unit-price').value = e.unit_cost || 0;
    document.getElementById('res-exp-notes').value = '';
    showModal('modal-restock-expense');
};

window.submitRestockExpense = async function(e) {
    e.preventDefault();
    const id = S.restockExpId;
    const qty = parseFloat(document.getElementById('res-exp-qty').value);
    const unitPrice = parseFloat(document.getElementById('res-exp-unit-price').value);
    const notes = document.getElementById('res-exp-notes').value;
    try {
        await api('PUT', `api/operational-expenses.php?id=${id}`, { action: 'restock', quantityAdded: qty, unitPrice, notes });
        hideModal('modal-restock-expense');
        await fetchAll();
    } catch(err) { alert(err.message); }
};

// ── Decrease Expense Record ──
window.openDecreaseExpense = function(id) {
    S.decreaseExpId = id;
    const e = S.expenses.find(x => x.id === id);
    if (!e) return;
    document.getElementById('dec-exp-name').textContent = e.name;
    document.getElementById('dec-exp-current').textContent = `${e.quantity||0} ${e.unit||''}`;
    document.getElementById('dec-exp-qty').value = '';
    document.getElementById('dec-exp-notes').value = '';
    showModal('modal-decrease-expense');
};

window.submitDecreaseExpense = async function(e) {
    e.preventDefault();
    const id = S.decreaseExpId;
    const qty = parseFloat(document.getElementById('dec-exp-qty').value);
    const notes = document.getElementById('dec-exp-notes').value;
    try {
        await api('PUT', `api/operational-expenses.php?id=${id}`, { action: 'decrease', quantity: qty, notes });
        hideModal('modal-decrease-expense');
        await fetchAll();
    } catch(err) { alert(err.message); }
};

// ── Denial Modal ──
window.openDenial = function(id) { S.denialRequestId=id; document.getElementById('denial-reason').value=''; showModal('modal-denial'); };
window.submitDenial = async function(e) {
    e.preventDefault();
    const reason = document.getElementById('denial-reason').value;
    try { await api('PATCH', `api/inventory-transfers.php?id=${S.denialRequestId}`, { action:'denied', reason }); hideModal('modal-denial'); await fetchAll(); }
    catch(err) { alert(err.message); }
};

// ─── CSV EXPORT ───────────────────────────────────────────────────────────────
window.exportCSV = function(type) {
    let rows = [], headers = [], filename = '';
    if (type === 'inventory') {
        headers = ['Name','Category','Unit','Unit Cost','In Store','Active','Value','Store Min','POS Min','Status'];
        rows = S.items.map(i => [i.name, i.category, i.unit, i.unitCost||0, i.storeQuantity||0, i.quantity||0, ((i.storeQuantity||0)*(i.averagePurchasePrice||0)).toFixed(2), i.storeMinLimit||0, i.minLimit||0, i.status||'']);
        filename = 'bulk-inventory.csv';
    } else if (type === 'expenses') {
        headers = ['Date','Name','Category','Unit Cost','Quantity','Amount','Description'];
        rows = S.expenses.map(e => [e.date||'', e.name, e.category||'', e.unit_cost||0, e.quantity||0, e.amount||0, e.description||'']);
        filename = 'operational-expenses.csv';
    } else if (type === 'assets') {
        headers = ['Name','Category','Qty','Unit Price','Total Value','Value Lost','Status','Purchase Date'];
        rows = S.assets.map(a => [a.name, a.category||'', a.quantity||0, a.unit_price||a.unitPrice||0, a.total_value||a.totalValue||0, a.value_lost||a.valueLost||0, a.status||'', a.purchase_date||a.purchaseDate||'']);
        filename = 'fixed-assets.csv';
    }
    const csvString = [headers, ...rows].map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob(['\uFEFF' + csvString], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
};

// ─── UTIL ─────────────────────────────────────────────────────────────────────
function filtered(arr, key='name') {
    if (!S.searchTerm) return arr;
    const q = S.searchTerm.toLowerCase();
    return arr.filter(i => (i[key]||i.name||'').toLowerCase().includes(q) || (i.category||'').toLowerCase().includes(q));
}
function showLoader(show) { document.getElementById('tab-loader')?.classList.toggle('hidden', !show); document.getElementById('tab-content')?.classList.toggle('hidden', show); }
function fmt(n) { return Number(n||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' Br'; }
function setText(id, t) { const el = document.getElementById(id); if (el) el.textContent = t; }
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function val(id) { return document.getElementById(id)?.value || ''; }
function setFormVals(obj) { for (const [id, v] of Object.entries(obj)) { const el = document.getElementById(id); if (el) el.value = v; } }
function empty(msg) { return `<div class="py-24 text-center"><p class="text-5xl mb-5">🏪</p><p class="text-[10px] uppercase font-black tracking-widest text-gray-600">${msg}</p></div>`; }
function today() { 
    const d = new Date();
    const Y = d.getFullYear(), M = String(d.getMonth()+1).padStart(2,'0'), D = String(d.getDate()).padStart(2,'0');
    return `${Y}-${M}-${D}`;
}
function sameDay(d, ref) { if (!d) return false; const x = new Date(d); return x.getFullYear()===ref.getFullYear() && x.getMonth()===ref.getMonth() && x.getDate()===ref.getDate(); }
function sameMonth(d, ref) { if (!d) return false; const x = new Date(d); return x.getFullYear()===ref.getFullYear() && x.getMonth()===ref.getMonth(); }
function withinDays(d, n) { if (!d) return false; return (Date.now() - new Date(d).getTime()) < n * 86400000; }

window.handleSearch = function(e) { S.searchTerm = e.target.value; renderTab(); };
