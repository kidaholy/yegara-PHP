/**
 * Admin Active Stock — POS Inventory Controller
 */

// ─── STATE ─────────────────────────────────────────────────────────────────────
const S = {
    items: [],
    loading: false,
    searchTerm: '',
    categoryFilter: 'All',
    editingId: null,
    showExportDropdown: false,
};

// ─── INIT ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    fetchStock();
    // Close export dropdown on outside click
    document.addEventListener('click', e => {
        if (!e.target.closest('#export-btn-wrap')) {
            S.showExportDropdown = false;
            document.getElementById('export-dropdown')?.classList.add('hidden');
        }
    });
});

async function fetchStock() {
    showLoader(true);
    try {
        const data = await api('GET', 'api/stock.php?availableOnly=true');
        // Also accept items with 0 qty that are still 'active'
        const all  = await api('GET', 'api/stock.php');
        S.items = all || [];
    } catch(e) {
        console.error(e);
    } finally { showLoader(false); }
    renderTable();
    renderStats();
}

// ─── STATS ────────────────────────────────────────────────────────────────────
function renderStats() {
    const visible     = S.items.filter(i => matchesCatFilter(i, S.categoryFilter));
    const totalValue  = visible.reduce((a, i) => a + (i.quantity||0) * (i.unitCost||0), 0);
    const lowStock    = visible.filter(i => i.trackQuantity && (i.quantity||0) <= (i.minLimit||0) && Math.round(i.quantity||0) > 0).length;

    setText('stat-pos-value',   fmt(totalValue));
    setText('stat-low-stock',   lowStock);
}

// ─── TABLE ────────────────────────────────────────────────────────────────────
// Category tab helper (shared with store)
function matchesCatFilter(item, filter) {
    if (filter === 'All') return true;
    const cat = (item.category || '').toLowerCase();
    const isDrink = cat === 'drinks' || cat === 'wiski' || cat === 'drink' || cat === 'beverage';
    const isFood  = cat === 'food'   || cat === 'meat'  || cat === 'foods';
    if (filter === 'Food')   return isFood;
    if (filter === 'Drinks') return isDrink;
    return true;
}

window.setCategoryFilter = function(f) {
    S.categoryFilter = f;
    // Update tab pill styles
    document.querySelectorAll('.cat-tab-btn').forEach(b => {
        const act = b.dataset.cat === f;
        b.classList.toggle('bg-[#c5a059]/10', act);
        b.classList.toggle('text-[#c5a059]',   act);
        b.classList.toggle('text-gray-500',    !act);
    });
    renderTable();
    renderStats();
};

function renderTable() {
    const tbody = document.getElementById('stock-tbody');
    if (!tbody) return;

    // Inject category tabs above the table if not yet present
    const tabsId = 'stock-cat-tabs';
    const wrap   = document.getElementById('stock-table-wrap');
    if (wrap && !document.getElementById(tabsId)) {
        const tabsDiv = document.createElement('div');
        tabsDiv.id = tabsId;
        tabsDiv.className = 'flex items-center gap-1 p-1 bg-gray-900 rounded-xl border border-gray-700 mb-4 w-fit';
        tabsDiv.innerHTML = ['All','Food','Drinks'].map(t => `
            <button data-cat="${t}" onclick="setCategoryFilter('${t}')"
                class="cat-tab-btn px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all
                ${S.categoryFilter === t ? 'bg-[#c5a059]/10 text-[#c5a059]' : 'text-gray-500 hover:text-gray-300'}"
                >${t}</button>`).join('');
        wrap.insertBefore(tabsDiv, wrap.firstChild);
    } else if (document.getElementById(tabsId)) {
        // Re-render tab active state
        document.querySelectorAll('.cat-tab-btn').forEach(b => {
            const act = b.dataset.cat === S.categoryFilter;
            b.classList.toggle('bg-[#c5a059]/10', act);
            b.classList.toggle('text-[#c5a059]',   act);
            b.classList.toggle('text-gray-500',    !act);
        });
    }

    const filtered = S.items.filter(i =>
        matchesCatFilter(i, S.categoryFilter) &&
        ((i.name||'').toLowerCase().includes(S.searchTerm.toLowerCase()) ||
         (i.category||'').toLowerCase().includes(S.searchTerm.toLowerCase()))
    ).filter(i => Math.round(i.quantity||0) > 0);

    if (!filtered.length) {
        tbody.innerHTML = `
        <tr><td colspan="5" class="py-20 text-center">
          <p class="text-4xl mb-4">🛒</p>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-5">Your active stock is empty.<br>Transfer items from the Store to start selling.</p>
          <a href="store.php" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gray-800 border border-gray-700 text-xs font-bold uppercase tracking-wider text-[#c5a059] hover:bg-gray-700 transition-colors">
            <i data-lucide="warehouse" class="w-4 h-4"></i> Go to Store
          </a>
        </td></tr>`;
        lucide.createIcons();
        return;
    }

    tbody.innerHTML = filtered.map(i => {
        const qty  = i.quantity || 0;
        const min  = i.minLimit || 0;
        let qtyColor = 'text-emerald-400';
        let badge    = `<span class="px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold uppercase">Ready</span>`;

        if (qty <= 0) {
            qtyColor = 'text-red-400';
            badge    = `<span class="px-2.5 py-1 rounded-md bg-red-500/10 text-red-400 border border-red-500/20 text-xs font-bold uppercase">Empty</span>`;
        } else if (i.trackQuantity && qty <= min) {
            qtyColor = 'text-amber-400';
            badge    = `<span class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-500 border border-amber-500/20 text-xs font-bold uppercase">Low Stock</span>`;
        }

        return `
        <tr class="hover:bg-gray-800/50 border-b border-gray-700/30 group transition-colors">
          <td class="p-3 lg:p-4">
            <p class="text-xs lg:text-sm font-bold text-gray-200">${esc(i.name)}</p>
            <p class="text-[10px] lg:text-xs text-gray-500 font-semibold mt-0.5">${esc(i.category||'')} · ${i.totalPurchased||0} total</p>
          </td>
          <td class="p-3 lg:p-4 hidden sm:table-cell">
            <span class="text-xs px-2 py-1 rounded-md bg-gray-800 border border-gray-700 font-semibold text-gray-400">${esc(i.category||'')}</span>
          </td>
          <td class="p-3 lg:p-4">
            <span class="text-base lg:text-lg font-bold ${qtyColor}">${qty % 1 === 0 ? qty : parseFloat(qty.toFixed(2))}</span>
            <span class="text-[10px] lg:text-xs text-gray-500 ml-0.5 lg:ml-1">${i.unit||''}</span>
          </td>
          <td class="p-3 lg:p-4 text-center lg:text-left">${badge}</td>
          <td class="p-3 lg:p-4 text-right">
            <div class="flex items-center justify-end gap-1 lg:gap-2">
              <button onclick="openEdit('${i.id}')"
                class="w-7 h-7 lg:w-8 lg:h-8 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 flex items-center justify-center">
                <i data-lucide="pencil" class="w-3 lg:w-3.5 h-3 lg:h-3.5"></i>
              </button>
              <button onclick="deleteStock('${i.id}')"
                class="w-7 h-7 lg:w-8 lg:h-8 rounded-lg bg-gray-800 border border-gray-700 text-red-500 flex items-center justify-center">
                <i data-lucide="trash-2" class="w-3 lg:w-3.5 h-3 lg:h-3.5"></i>
              </button>
            </div>
          </td>
        </tr>`;
    }).join('');
    lucide.createIcons();
}

// ─── EDIT MODAL ───────────────────────────────────────────────────────────────
window.openEdit = function(id) {
    S.editingId = id;
    const i = S.items.find(x => x.id === id);
    if (!i) return;
    document.getElementById('edit-item-name').value  = i.name;
    document.getElementById('edit-qty').value        = i.quantity || 0;
    document.getElementById('edit-min-limit').value  = i.minLimit || 0;
    document.getElementById('edit-modal').classList.remove('hidden');
    lucide.createIcons();
};

window.closeEditModal = function() {
    document.getElementById('edit-modal').classList.add('hidden');
};

window.submitEdit = async function(e) {
    e.preventDefault();
    try {
        await api('PUT', `api/stock.php?id=${S.editingId}`, {
            quantity: parseFloat(document.getElementById('edit-qty').value),
            minLimit: parseFloat(document.getElementById('edit-min-limit').value),
        });
        closeEditModal();
        await fetchStock();
    } catch(err) { alert(err.message); }
};

window.deleteStock = async function(id) {
    if (!confirm('Remove from POS list? If item still has quantity in Store, the master record will be kept.')) return;
    try {
        await api('DELETE', `api/stock.php?id=${id}&source=stock`);
        await fetchStock();
    } catch(e) { alert(e.message); }
};

// ─── CSV EXPORT ───────────────────────────────────────────────────────────────
window.toggleExport = function() {
    S.showExportDropdown = !S.showExportDropdown;
    document.getElementById('export-dropdown')?.classList.toggle('hidden', !S.showExportDropdown);
};

window.exportCSV = function(filter) {
    S.showExportDropdown = false;
    document.getElementById('export-dropdown')?.classList.add('hidden');

    const base = S.items.filter(i =>
        (i.name||'').toLowerCase().includes(S.searchTerm.toLowerCase())
    );

    let rows;
    switch(filter) {
        case 'ready': rows = base.filter(i => !i.trackQuantity || (i.quantity||0) > (i.minLimit||0)); break;
        case 'low':   rows = base.filter(i => i.trackQuantity && (i.quantity||0) <= (i.minLimit||0) && (i.quantity||0) > 0); break;
        case 'empty': rows = base.filter(i => i.trackQuantity && (i.quantity||0) <= 0); break;
        default:      rows = base;
    }

    const headers = ['Item Name','Category','Quantity','Unit','Status','Min Limit','Unit Cost','Total Value'];
    const data = rows.map(i => {
        let status = 'Ready';
        if (Math.round(i.quantity||0) <= 0) status = 'Empty';
        else if (i.trackQuantity && (i.quantity||0) <= (i.minLimit||0)) status = 'Low Stock';
        return [i.name, i.category||'', Math.round(i.quantity||0), i.unit||'pcs', status, Math.round(i.minLimit||0), Math.round(i.unitCost||0), Math.round((i.quantity||0)*(i.unitCost||0))];
    });

    const csvString = [headers, ...data].map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob(['\uFEFF' + csvString], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a'); a.href = url; a.download = `stock-${filter}.csv`; a.click();
    URL.revokeObjectURL(url);
};

// ─── UTIL ─────────────────────────────────────────────────────────────────────
async function api(method, url, body) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const r = await fetch(url, opts);
    const json = await r.json();
    if (!r.ok) throw new Error(json.message || 'Request failed');
    return json;
}

function showLoader(show) {
    document.getElementById('stock-loader')?.classList.toggle('hidden', !show);
    document.getElementById('stock-table-wrap')?.classList.toggle('hidden', show);
}

function fmt(n) { return Number(n||0).toLocaleString('en-US', {minimumFractionDigits:0, maximumFractionDigits:0}) + ' Br'; }
function setText(id, t) { const el = document.getElementById(id); if(el) el.textContent = t; }
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
window.handleSearch = function(e) { S.searchTerm = e.target.value; renderTable(); };
