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
    updateMetricCard('avg-order', formatCurrency(m.realTimeMetrics.averageOrderValue));
    
    const activeOrdersCount = (m.realTimeMetrics.recentActive || []).length;
    updateMetricCard('active-orders', activeOrdersCount, '', activeOrdersCount > 0 ? 'blue' : 'gray');

    const stockAlertCount = m.inventoryInsights.lowStockAlerts.length;
    updateMetricCard('stock-alerts', stockAlertCount, '', stockAlertCount > 0 ? 'red' : 'gray');

    // Metrics are updated via updateMetricCard above
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
        iconBox?.classList.remove('bg-gray-900', 'text-gray-400', 'text-blue-400');
        iconBox?.classList.add('bg-red-500/10', 'text-red-500');
        card.classList.add('border-red-900/50');
        card.classList.remove('border-blue-900/50');
    } else if (color === 'blue') {
        iconBox?.classList.remove('bg-gray-900', 'text-gray-400', 'text-red-500');
        iconBox?.classList.add('bg-blue-500/10', 'text-blue-400');
        card.classList.add('border-blue-900/50');
        card.classList.remove('border-red-900/50');
    } else if (color === 'gray') {
        iconBox?.classList.add('bg-gray-900', 'text-gray-400');
        iconBox?.classList.remove('bg-red-500/10', 'text-red-500', 'bg-blue-500/10', 'text-blue-400');
        card.classList.remove('border-red-900/50', 'border-blue-900/50');
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
