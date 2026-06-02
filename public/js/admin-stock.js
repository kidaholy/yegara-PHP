/**
 * Admin Active Stock — POS Inventory Controller
 */

// ─── STATE ─────────────────────────────────────────────────────────────────────
const S = {
    items: [],
    loading: false,
    searchTerm: '',
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
    const totalValue  = S.items.reduce((a, i) => a + (i.quantity||0) * (i.unitCost||0), 0);
    const lowStock    = S.items.filter(i => i.trackQuantity && (i.quantity||0) <= (i.minLimit||0)).length;
    const totalInStore= S.items.reduce((a, i) => a + (i.storeQuantity||0), 0);

    setText('stat-pos-value',   fmt(totalValue));
    setText('stat-low-stock',   lowStock);
    setText('stat-in-store',    totalInStore.toLocaleString());
}

// ─── TABLE ────────────────────────────────────────────────────────────────────
function renderTable() {
    const tbody = document.getElementById('stock-tbody');
    if (!tbody) return;

    const filtered = S.items.filter(i =>
        (i.name||'').toLowerCase().includes(S.searchTerm.toLowerCase()) ||
        (i.category||'').toLowerCase().includes(S.searchTerm.toLowerCase())
    );

    if (!filtered.length) {
        tbody.innerHTML = `
        <tr><td colspan="5" class="py-24 text-center">
          <p class="text-5xl mb-4">🛒</p>
          <p class="text-[10px] uppercase font-black tracking-widest text-gray-600 mb-6">Your active stock is empty.<br>Transfer items from the Store to start selling.</p>
          <a href="store.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-[#1a1712] text-[#d4af37] border border-[#d4af37]/20 text-[10px] font-black uppercase tracking-widest hover:bg-[#d4af37] hover:text-black transition-all">
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
        let badge    = `<span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[8px] font-black uppercase">Ready</span>`;

        if (qty <= 0) {
            qtyColor = 'text-red-400';
            badge    = `<span class="px-2.5 py-1 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 text-[8px] font-black uppercase">Empty</span>`;
        } else if (i.trackQuantity && qty <= min) {
            qtyColor = 'text-[#d4af37]';
            badge    = `<span class="px-2.5 py-1 rounded-full bg-[#d4af37]/10 text-[#d4af37] border border-[#d4af37]/20 text-[8px] font-black uppercase">Low Stock</span>`;
        }

        return `
        <tr class="hover:bg-[#1a1c1b] border-b border-white/5 group transition-colors">
          <td class="p-5">
            <p class="text-base font-black font-playfair italic text-[#f3cf7a]">${esc(i.name)}</p>
            <p class="text-[9px] text-gray-600 uppercase font-bold mt-0.5">${i.totalPurchased||0} total purchased</p>
          </td>
          <td class="p-5">
            <span class="text-[9px] px-2 py-1 rounded-full bg-white/5 border border-white/5 font-black uppercase tracking-widest text-gray-500">${esc(i.category||'')}</span>
          </td>
          <td class="p-5">
            <span class="text-xl font-bold ${qtyColor}">${qty}</span>
            <span class="text-[10px] text-gray-600 ml-1">${i.unit||''}</span>
          </td>
          <td class="p-5">${badge}</td>
          <td class="p-5">
            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button onclick="openEdit('${i.id}')" title="Edit"
                class="w-9 h-9 rounded-xl bg-white/5 border border-white/5 text-gray-500 flex items-center justify-center hover:text-white transition-all">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
              </button>
              <button onclick="deleteStock('${i.id}')" title="Remove from POS"
                class="w-9 h-9 rounded-xl bg-white/5 border border-white/5 text-red-500 flex items-center justify-center hover:bg-red-500/10 transition-all">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
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
        if ((i.quantity||0) <= 0) status = 'Empty';
        else if (i.trackQuantity && (i.quantity||0) <= (i.minLimit||0)) status = 'Low Stock';
        return [i.name, i.category||'', i.quantity||0, i.unit||'pcs', status, i.minLimit||0, i.unitCost||0, ((i.quantity||0)*(i.unitCost||0)).toFixed(2)];
    });

    const csv = [headers, ...data].map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
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

function fmt(n) { return Number(n||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' Br'; }
function setText(id, t) { const el = document.getElementById(id); if(el) el.textContent = t; }
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
window.handleSearch = function(e) { S.searchTerm = e.target.value; renderTable(); };
