/**
 * Admin Dashboard AJAX Controller
 */
const state = {
    metrics: null,
    loading: true,
    error: null,
    lastUpdate: null
};

async function fetchMetrics() {
    state.loading = true;
    updateUI();
    
    try {
        const response = await fetch('api/business-metrics.php');
        if (!response.ok) throw new Error('Failed to fetch metrics');
        
        state.metrics = await response.json();
        state.error = null;
        state.lastUpdate = new Date();
    } catch (err) {
        state.error = err.message;
    } finally {
        state.loading = false;
        updateUI();
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount) + ' Br';
}

function updateUI() {
    const refreshBtn = document.getElementById('refresh-btn');
    const refreshIcon = refreshBtn?.querySelector('i');
    
    if (state.loading) {
        refreshIcon?.classList.add('animate-spin');
    } else {
        refreshIcon?.classList.remove('animate-spin');
    }

    if (state.error) {
        showError(state.error);
        return;
    }

    if (!state.metrics) return;

    const m = state.metrics;
    
    // Update Metric Cards
    updateMetricCard('today-revenue', formatCurrency(m.realTimeMetrics.todayRevenue));
    updateMetricCard('total-orders', m.realTimeMetrics.todayOrders, `${m.operationalMetrics.customerSatisfaction.completedOrders} completed`);
    updateMetricCard('avg-order', formatCurrency(m.realTimeMetrics.averageOrderValue));
    
    const stockAlertCount = m.inventoryInsights.lowStockAlerts.length;
    updateMetricCard('stock-alerts', stockAlertCount, '', stockAlertCount > 0 ? 'red' : 'gray');

    // Update Stock Alerts Panel
    updateStockAlertsPanel(m.inventoryInsights.lowStockAlerts);

    // Update Active Orders Panel
    updateActiveOrdersPanel(m.realTimeMetrics.recentActive || []);
}

function updateMetricCard(id, value, subtext = '', color = null) {
    const card = document.getElementById(id);
    if (!card) return;

    const valEl = card.querySelector('.metric-value');
    const subEl = card.querySelector('.metric-subtext');
    const iconBox = card.querySelector('.metric-icon-box');

    if (valEl) valEl.textContent = state.loading && !state.metrics ? '---' : value;
    if (subEl) subEl.textContent = state.loading && !state.metrics ? 'loading...' : subtext;

    if (color === 'red') {
        iconBox?.classList.remove('bg-gray-900', 'text-gray-400');
        iconBox?.classList.add('bg-red-500/10', 'text-red-500');
        card.classList.add('border-red-900/50');
    } else if (color === 'gray') {
        iconBox?.classList.add('bg-gray-900', 'text-gray-400');
        iconBox?.classList.remove('bg-red-500/10', 'text-red-500');
        card.classList.remove('border-red-900/50');
    }
}

function updateActiveOrdersPanel(orders) {
    const panel = document.getElementById('active-orders-panel');
    if (!panel) return;

    if (orders.length === 0) {
        panel.classList.add('hidden');
        return;
    }

    panel.classList.remove('hidden');
    const list = panel.querySelector('.orders-list');
    const countEl = panel.querySelector('.active-count');
    
    if (countEl) countEl.textContent = `(${orders.length})`;
    
    if (list) {
        list.innerHTML = orders.map(o => `
            <div class="glass p-5 rounded-xl border border-blue-900/30 bg-gray-900/60 hover:bg-gray-900 transition-colors flex flex-col group">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest leading-none mb-1">#${o.orderNumber.substr(-4)}</p>
                        <h4 class="text-sm font-bold text-white">${o.tableNumber === 'Buy&Go' ? 'Takeaway' : 'Table ' + o.tableNumber}</h4>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-tighter px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        ${o.status}
                    </span>
                </div>
                
                <div class="flex-1 space-y-2 mb-4">
                    ${(o.items || []).slice(0, 2).map(it => `
                        <div class="flex justify-between items-center text-[10px] text-gray-400">
                            <span class="truncate pr-2">${it.name}</span>
                            <span class="font-bold text-gray-200">x${it.quantity}</span>
                        </div>
                    `).join('')}
                    ${(o.items || []).length > 2 ? `<p class="text-[9px] text-gray-500 font-medium italic">+ ${(o.items.length - 2)} more items</p>` : ''}
                </div>

                <div class="pt-4 border-t border-gray-800 flex items-center justify-between">
                    <span class="text-[10px] font-bold text-gray-500">${getTimeAgo(o.createdAt)}</span>
                    <p class="text-xs font-bold text-blue-400">${formatCurrency(o.totalAmount)}</p>
                </div>
            </div>
        `).join('');
        lucide.createIcons();
    }
}

function getTimeAgo(dateStr) {
    const diff = Math.floor((new Date() - new Date(dateStr)) / 60000);
    if (diff < 1) return 'Just now';
    if (diff < 60) return diff + 'm ago';
    return Math.floor(diff/60) + 'h ago';
}

function updateStockAlertsPanel(alerts) {
    const panel = document.getElementById('stock-alerts-panel');
    if (!panel) return;

    if (alerts.length === 0) {
        panel.classList.add('hidden');
        return;
    }

    panel.classList.remove('hidden');
    const list = panel.querySelector('.alerts-list');
    const countEl = panel.querySelector('.alerts-count');
    
    if (countEl) countEl.textContent = `(${alerts.length})`;
    
    if (list) {
        list.innerHTML = alerts.slice(0, 5).map(a => `
            <div class="flex justify-between p-4 bg-gray-900 rounded-lg border border-red-900/30">
                <div>
                    <p class="font-medium text-gray-200">${a.name}</p>
                    <p class="text-sm text-gray-400">${a.current} ${a.unit} remaining</p>
                </div>
                <span class="text-xs font-semibold bg-red-950/50 text-red-400 px-2.5 py-1 rounded-md border border-red-900/50">
                    ${a.urgency}
                </span>
            </div>
        `).join('');

        if (alerts.length > 5) {
            const moreLink = document.createElement('a');
            moreLink.href = 'reports.php';
            moreLink.className = 'text-xs font-semibold text-red-400/60 hover:text-red-400 transition-colors pt-2 flex items-center gap-2';
            moreLink.innerHTML = `View all ${alerts.length} alerts <i data-lucide="arrow-right" class="w-4 h-4"></i>`;
            list.appendChild(moreLink);
            lucide.createIcons();
        }
    }
}

function showError(msg) {
    // Basic error handling - could be a full page overlay as per spec
    console.error(msg);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    fetchMetrics();
    setInterval(fetchMetrics, 60000); // 60s polling
    
    document.getElementById('refresh-btn')?.addEventListener('click', fetchMetrics);
});
