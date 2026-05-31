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
        iconBox?.classList.remove('bg-[#1a1c1b]', 'text-gray-400', 'border-white/10');
        iconBox?.classList.add('bg-[#1a0f0f]', 'text-red-400', 'border-red-900/50');
        card.classList.add('border-red-900/50');
    } else if (color === 'gray') {
        iconBox?.classList.add('bg-[#1a1c1b]', 'text-gray-400', 'border-white/10');
        iconBox?.classList.remove('bg-[#1a0f0f]', 'text-red-400', 'border-red-900/50');
        card.classList.remove('border-red-900/50');
    }
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
            <div class="flex justify-between p-4 bg-[#0f1110] rounded-lg border border-red-900/30">
                <div>
                    <p class="font-medium text-gray-200">${a.name}</p>
                    <p class="text-sm text-gray-500">${a.current} ${a.unit} remaining</p>
                </div>
                <span class="text-[10px] h-fit uppercase bg-red-950/80 text-red-400 px-3 py-1 rounded-full border border-red-900/50">
                    ${a.urgency}
                </span>
            </div>
        `).join('');

        if (alerts.length > 5) {
            const moreLink = document.createElement('a');
            moreLink.href = 'reports.php';
            moreLink.className = 'text-[10px] uppercase font-black text-red-400/60 hover:text-red-400 transition-colors pt-4 flex items-center gap-2';
            moreLink.innerHTML = `View all ${alerts.length} alerts <i data-lucide="arrow-right" class="w-3 h-3"></i>`;
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
