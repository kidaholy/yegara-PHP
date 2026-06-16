<?php
/**
 * Active POS Stock — Management View
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';
requireAuth(['admin'], 'stock:view');

renderHeader("Active Stock");
?>

<div class="flex-1 w-full bg-[#0f1110] py-4 lg:py-10 px-4 lg:px-10 xl:px-16 transition-all">
  <div class="max-w-[1600px] mx-auto">
    <div class="flex flex-col lg:grid lg:grid-cols-12 gap-6 lg:gap-10 items-start">

      <!-- ═══════ SIDEBAR ═══════ -->
      <aside class="lg:col-span-3 space-y-4 lg:space-y-6 lg:sticky lg:top-24 w-full">

        <!-- Quick Stats -->
        <div class="p-4 lg:p-6 rounded-xl lg:rounded-2xl border border-gray-700/50 bg-gray-800/80 transition-all">
          <div class="flex items-center gap-3 lg:gap-4 mb-4 lg:mb-6">
            <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl bg-[#c5a059]/10 border border-[#c5a059]/20 flex items-center justify-center text-[#c5a059]">
              <i data-lucide="shopping-cart" class="w-5 lg:w-6 h-5 lg:h-6"></i>
            </div>
            <div>
              <h2 class="text-lg lg:text-xl font-bold text-gray-200 leading-none mb-1">POS Stock</h2>
              <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Active Inventory</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 lg:flex lg:flex-col gap-3 lg:space-y-4">
            <div class="bg-gray-900/40 p-4 rounded-xl border border-gray-700/30">
              <p class="text-[10px] uppercase tracking-wider text-gray-500 mb-1">POS Value</p>
              <h3 id="stat-pos-value" class="text-xl lg:text-2xl font-bold text-white">0 Br</h3>
            </div>
            <div class="p-4 rounded-xl bg-amber-500/5 border border-amber-500/10">
              <p class="text-[10px] uppercase tracking-wider text-amber-600 mb-1">Low Stock</p>
              <h3 id="stat-low-stock" class="text-xl lg:text-2xl font-bold text-amber-400">0</h3>
            </div>

          </div>
        </div>

        <!-- Restock CTA (Hidden on small mobile to save space) -->
        <a href="store.php" class="hidden sm:block p-4 lg:p-5 rounded-xl lg:rounded-2xl border border-gray-700/50 bg-gray-800/80 hover:bg-gray-800 transition-all no-underline group relative overflow-hidden">
          <div class="absolute -right-4 -top-4 opacity-[0.05] group-hover:rotate-12 transition-transform duration-500">
            <i data-lucide="package-plus" class="w-16 lg:w-20 h-16 lg:h-20 text-[#c5a059]"></i>
          </div>
          <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[#c5a059] mb-1">Restock</h3>
          <p class="text-xs text-gray-400 font-semibold mb-3 leading-relaxed hidden lg:block">
            Transfer items from bulk storage for POS sales.
          </p>
          <div class="flex items-center gap-2 text-[#c5a059] text-[10px] font-bold uppercase tracking-wider">
            Go to Store <i data-lucide="arrow-right" class="w-3 h-3 group-hover:translate-x-1 transition-transform"></i>
          </div>
        </a>
      </aside>

      <!-- ═══════ MAIN ═══════ -->
      <main class="lg:col-span-9 space-y-6 lg:space-y-8 w-full">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="sm:hidden w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center text-[#c5a059]">
              <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            </div>
            <div>
              <h1 class="text-xl lg:text-2xl font-bold text-gray-200 leading-tight">Active Stock</h1>
              <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mt-0.5">Available for POS sales</p>
            </div>
          </div>

          <!-- Search + Export -->
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative flex-1 sm:flex-initial">
              <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
              <input type="text" oninput="handleSearch(event)" placeholder="Search..."
                class="bg-gray-800 border border-gray-700/50 rounded-lg pl-9 pr-4 py-2 text-xs lg:text-sm text-white focus:border-[#c5a059] outline-none w-full sm:w-48 transition-all">
            </div>
            <!-- Export Dropdown -->
            <div id="export-btn-wrap" class="relative flex items-center gap-2">
              <button onclick="clearAllStockQuantities()"
                class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-red-500/20 bg-red-500/10 text-[10px] font-bold uppercase text-red-500 hover:bg-red-500 hover:text-white transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Clear</span>
              </button>
              <button id="export-btn" onclick="toggleExport()"
                class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-700 bg-gray-800 text-[10px] font-bold uppercase text-gray-400 hover:text-white">
                <i data-lucide="download" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">Export</span>
                <i data-lucide="chevron-down" class="w-3 h-3"></i>
              </button>
              <!-- Dropdown -->
              <div id="export-dropdown" class="hidden absolute right-0 top-full mt-2 w-48 bg-gray-900 border border-gray-700 rounded-lg shadow-xl z-50 overflow-hidden py-1">
                <button onclick="exportCSV('all')"   class="w-full text-left px-4 py-2.5 text-xs font-bold uppercase hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">All Stock</button>
                <button onclick="exportCSV('ready')" class="w-full text-left px-4 py-2.5 text-xs font-bold uppercase hover:bg-gray-800 text-emerald-400 hover:text-emerald-300 transition-colors">Ready Stock</button>
                <button onclick="exportCSV('low')"   class="w-full text-left px-4 py-2.5 text-xs font-bold uppercase hover:bg-gray-800 text-amber-400 hover:text-amber-300 transition-colors">Low Stock</button>
                <button onclick="exportCSV('empty')" class="w-full text-left px-4 py-2.5 text-xs font-bold uppercase hover:bg-gray-800 text-red-400 hover:text-red-300 transition-colors">Empty Stock</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Loader -->
        <div id="stock-loader" class="flex flex-col items-center justify-center py-40 animate-pulse">
          <i data-lucide="loader-2" class="w-8 h-8 text-[#c5a059] animate-spin mb-4"></i>
          <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Syncing POS Inventory...</p>
        </div>

        <!-- Table -->
        <div id="stock-table-wrap" class="hidden">
          <div class="rounded-xl border border-gray-700/50 bg-gray-800/20 overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-gray-800/50 border-b border-gray-700/50">
                <tr>
                  <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500">Item</th>
                  <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500 hidden sm:table-cell">Category</th>
                  <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500 text-center lg:text-left">Stock</th>
                  <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500 text-center lg:text-left">Status</th>
                  <th class="p-3 lg:p-4 text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="stock-tbody"></tbody>
            </table>
          </div>
        </div>

      </main>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
<div id="edit-modal" class="fixed inset-0 z-[999] flex items-end sm:items-center justify-center px-4 hidden">
  <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeEditModal()"></div>
  <div class="w-full max-w-md rounded-2xl bg-gray-900 border border-gray-700 shadow-2xl relative z-10">
    <div class="p-6">
      <h2 class="text-xl font-bold text-gray-200 mb-1">Adjust Stock</h2>
      <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-6">Manual POS Inventory Update</p>

      <form onsubmit="submitEdit(event)" class="space-y-4">
        <div class="space-y-1.5">
          <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 pl-1">Item Name (Locked)</label>
          <input id="edit-item-name" type="text" readonly
            class="w-full bg-gray-800/80 border border-gray-700 rounded-lg px-4 py-2.5 text-sm font-semibold text-gray-500 outline-none cursor-not-allowed">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 pl-1">Active Qty</label>
            <input id="edit-qty" type="number" step="any" min="0" required
              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-lg font-bold text-[#c5a059] focus:border-[#c5a059] outline-none transition-colors">
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wider text-gray-400 pl-1">Alert Limit</label>
            <input id="edit-min-limit" type="number" step="any" min="0" required
              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-lg font-bold text-gray-200 focus:border-[#c5a059] outline-none transition-colors">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-2 pt-4 border-t border-gray-700/50 mt-2">
          <button type="button" onclick="closeEditModal()"
            class="py-2.5 rounded-lg bg-gray-800 text-gray-400 font-bold text-xs uppercase tracking-wider hover:bg-gray-700 hover:text-white transition-colors">Cancel</button>
          <button type="submit"
            class="py-2.5 rounded-lg bg-[#c5a059] text-gray-900 font-bold text-xs uppercase tracking-wider hover:bg-[#b08d4a] transition-colors">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="public/js/admin-stock.js"></script>
<?php renderFooter(); ?>
