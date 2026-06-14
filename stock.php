<?php
/**
 * Active POS Stock — Management View
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';
requireAuth(['admin'], 'stock:view');

renderHeader("Active Stock");
?>

<div class="flex-1 w-full bg-[#0f1110] py-10 px-4 lg:px-10 xl:px-16">
  <div class="max-w-[1600px] mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

      <!-- ═══════ SIDEBAR ═══════ -->
      <aside class="lg:col-span-3 space-y-6 sticky top-24">

        <!-- Quick Stats -->
        <div class="p-6 rounded-2xl border border-gray-700/50 bg-gray-800/80">
          <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-xl bg-[#c5a059]/10 border border-[#c5a059]/20 flex items-center justify-center text-[#c5a059]">
              <i data-lucide="shopping-cart" class="w-6 h-6"></i>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-200">POS Stock</h2>
              <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Active Inventory</p>
            </div>
          </div>

          <div class="space-y-4">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">POS Inventory Value</p>
              <h3 id="stat-pos-value" class="text-2xl font-bold text-white">0.00 Br</h3>
            </div>
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
              <p id="stat-low-stock" class="text-xl font-bold text-amber-400">0</p>
              <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-500">Low Stock Items</p>
            </div>
            <div class="p-4 rounded-xl bg-gray-900 border border-gray-700">
              <p id="stat-in-store" class="text-xl font-bold text-gray-300">0</p>
              <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Units in Bulk Store</p>
            </div>
          </div>
        </div>

        <!-- Restock CTA -->
        <a href="store.php" class="block p-5 rounded-2xl border border-gray-700/50 bg-gray-800/80 hover:bg-gray-800 transition-colors no-underline group relative overflow-hidden">
          <div class="absolute -right-4 -top-4 opacity-[0.05] group-hover:rotate-12 transition-transform duration-500">
            <i data-lucide="package-plus" class="w-20 h-20 text-[#c5a059]"></i>
          </div>
          <h3 class="text-xs font-semibold uppercase tracking-wider text-[#c5a059] mb-2">Need to Restock?</h3>
          <p class="text-sm text-gray-400 font-semibold mb-4 leading-relaxed">
            Transfer items from bulk storage to make them available for POS sales.
          </p>
          <div class="flex items-center gap-2 text-[#c5a059] text-xs font-bold uppercase tracking-wider">
            Go to Store Hub <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform"></i>
          </div>
        </a>
      </aside>

      <!-- ═══════ MAIN ═══════ -->
      <main class="lg:col-span-9 space-y-8">

        <!-- Header -->
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <div>
            <div class="flex items-center gap-3">
              <i data-lucide="shopping-cart" class="w-6 h-6 text-[#c5a059]"></i>
              <h1 class="text-2xl font-bold text-gray-200">Active Stock</h1>
            </div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mt-1">Inventory currently available for POS sales</p>
          </div>

          <!-- Search + Export -->
          <div class="flex items-center gap-2">
            <div class="relative">
              <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
              <input type="text" oninput="handleSearch(event)" placeholder="Search items..."
                class="bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2 text-sm text-white focus:border-[#c5a059] outline-none w-52 transition-colors placeholder:text-gray-500">
            </div>
            <!-- Export Dropdown -->
            <div id="export-btn-wrap" class="relative flex items-center gap-2">
              <button onclick="clearAllStockQuantities()"
                class="flex items-center gap-2 px-3 py-2 rounded-lg border border-red-500/20 bg-red-500/10 text-xs font-semibold text-red-500 hover:bg-red-500 hover:text-white transition-colors">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Clear All
              </button>
              <button id="export-btn" onclick="toggleExport()"
                class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-700 bg-gray-800 text-xs font-semibold text-gray-400 hover:text-white transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i> Export
                <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
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
                  <?php foreach(['Item','Category','Active Stock','Status','Management'] as $h): ?>
                  <th class="p-4 text-xs font-semibold uppercase tracking-wider text-gray-500"><?= $h ?></th>
                  <?php endforeach; ?>
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
