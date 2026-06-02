<?php
/**
 * Active POS Stock — Management View
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';
requireAuth(['admin']);

renderHeader("Active Stock");
?>

<div class="flex-1 w-full bg-[#0f1110] py-10 px-4 lg:px-10 xl:px-16">
  <div class="max-w-[1600px] mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

      <!-- ═══════ SIDEBAR ═══════ -->
      <aside class="lg:col-span-3 space-y-6 sticky top-24">

        <!-- Quick Stats -->
        <div class="glass p-8 rounded-[2.5rem] border border-white/5 bg-[#151716] shadow-2xl relative overflow-hidden group">
          <div class="absolute -right-4 -bottom-4 opacity-[0.04] group-hover:rotate-12 transition-transform duration-1000">
            <i data-lucide="shopping-cart" class="w-32 h-32 text-[#d4af37]"></i>
          </div>

          <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 rounded-2xl bg-[#1a1712] border border-[#d4af37]/20 flex items-center justify-center text-[#d4af37]">
              <i data-lucide="shopping-cart" class="w-6 h-6"></i>
            </div>
            <div>
              <h2 class="text-xl font-black font-playfair italic text-[#f3cf7a]">POS Stock</h2>
              <p class="text-[9px] uppercase font-black tracking-widest text-gray-600">Active Inventory</p>
            </div>
          </div>

          <div class="space-y-5">
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-gray-600 mb-1">POS Inventory Value</p>
              <h3 id="stat-pos-value" class="text-3xl font-black text-white">0.00 Br</h3>
            </div>
            <div class="p-4 rounded-2xl bg-amber-400/[0.04] border border-amber-400/10">
              <p id="stat-low-stock" class="text-xl font-black text-amber-400">0</p>
              <p class="text-[8px] uppercase font-black tracking-widest text-amber-600">Low Stock Items</p>
            </div>
            <div class="p-4 rounded-2xl bg-white/5 border border-white/5">
              <p id="stat-in-store" class="text-xl font-bold text-gray-400">0</p>
              <p class="text-[8px] uppercase font-black tracking-widest text-gray-600">Units in Bulk Store</p>
            </div>
          </div>
        </div>

        <!-- Restock CTA -->
        <a href="store.php" class="block glass p-7 rounded-[2.5rem] border border-[#d4af37]/15 bg-gradient-to-br from-[#1a1712] to-[#0f1110] shadow-xl group relative overflow-hidden no-underline">
          <div class="absolute -right-5 -top-5 opacity-[0.06] rotate-12 group-hover:rotate-45 transition-transform duration-700">
            <i data-lucide="package-plus" class="w-24 h-24 text-[#d4af37]"></i>
          </div>
          <h3 class="text-[9px] font-black uppercase tracking-widest text-[#d4af37] mb-3">Need to Restock?</h3>
          <p class="text-[9px] text-gray-600 leading-relaxed font-bold mb-4">
            Transfer items from bulk storage to make them available for POS sales.
          </p>
          <div class="flex items-center gap-2 text-[#f3cf7a] text-[10px] font-black uppercase tracking-widest group-hover:gap-3 transition-all">
            Go to Store Hub <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </div>
        </a>
      </aside>

      <!-- ═══════ MAIN ═══════ -->
      <main class="lg:col-span-9 space-y-8">

        <!-- Header -->
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <div>
            <div class="flex items-center gap-3">
              <i data-lucide="shopping-cart" class="w-6 h-6 text-[#d4af37]"></i>
              <h1 class="text-3xl font-black font-playfair italic text-[#f3cf7a]">Active Stock</h1>
            </div>
            <p class="text-[9px] uppercase font-black tracking-widest text-gray-600 mt-1">Inventory currently available for POS sales</p>
          </div>

          <!-- Search + Export -->
          <div class="flex items-center gap-3">
            <div class="relative">
              <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-600"></i>
              <input type="text" oninput="handleSearch(event)" placeholder="Search items..."
                class="bg-white/5 border border-white/5 rounded-full pl-10 pr-5 py-2.5 text-[11px] font-bold text-white focus:border-[#d4af37]/30 outline-none w-52 transition-all">
            </div>
            <!-- Export Dropdown -->
            <div id="export-btn-wrap" class="relative">
              <button id="export-btn" onclick="toggleExport()"
                class="flex items-center gap-2 px-4 py-2.5 rounded-full border border-white/10 bg-white/5 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-white hover:border-white/20 transition-all">
                <i data-lucide="download" class="w-3.5 h-3.5"></i> Export
                <i data-lucide="chevron-down" class="w-3 h-3"></i>
              </button>
              <!-- Dropdown -->
              <div id="export-dropdown" class="hidden absolute right-0 top-full mt-2 w-48 glass rounded-2xl border border-white/10 shadow-2xl z-50 overflow-hidden py-2">
                <button onclick="exportCSV('all')"   class="w-full text-left px-5 py-3 text-[10px] font-black uppercase hover:bg-white/5 text-gray-400 hover:text-white transition-colors">All Stock</button>
                <button onclick="exportCSV('ready')" class="w-full text-left px-5 py-3 text-[10px] font-black uppercase hover:bg-white/5 text-emerald-400 hover:text-emerald-300 transition-colors">Ready Stock</button>
                <button onclick="exportCSV('low')"   class="w-full text-left px-5 py-3 text-[10px] font-black uppercase hover:bg-white/5 text-amber-400 hover:text-amber-300 transition-colors">Low Stock</button>
                <button onclick="exportCSV('empty')" class="w-full text-left px-5 py-3 text-[10px] font-black uppercase hover:bg-white/5 text-red-400 hover:text-red-300 transition-colors">Empty Stock</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Loader -->
        <div id="stock-loader" class="flex flex-col items-center justify-center py-40 animate-pulse">
          <i data-lucide="loader-2" class="w-10 h-10 text-[#d4af37] animate-spin mb-5"></i>
          <p class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-600">Syncing POS Inventory...</p>
        </div>

        <!-- Table -->
        <div id="stock-table-wrap" class="hidden">
          <div class="glass rounded-[2rem] border border-white/5 overflow-hidden shadow-2xl">
            <table class="w-full text-left">
              <thead class="bg-white/[0.03] border-b border-white/5">
                <tr>
                  <?php foreach(['Item','Category','Active Stock','Status','Management'] as $h): ?>
                  <th class="p-5 text-[9px] uppercase font-black tracking-widest text-gray-600"><?= $h ?></th>
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
  <div class="absolute inset-0 bg-black/85 backdrop-blur-md" onclick="closeEditModal()"></div>
  <div class="glass w-full max-w-md rounded-[2.5rem] bg-[#151716] border border-white/10 shadow-2xl relative z-10">
    <div class="p-8">
      <h2 class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-1">Adjust Stock</h2>
      <p class="text-[9px] uppercase font-black tracking-widest text-gray-500 mb-8">Manual POS Inventory Update</p>

      <form onsubmit="submitEdit(event)" class="space-y-5">
        <div class="space-y-2">
          <label class="text-[9px] uppercase font-black tracking-widest text-gray-600 pl-1">Item Name (Locked)</label>
          <input id="edit-item-name" type="text" readonly
            class="w-full bg-white/[0.03] border border-white/5 rounded-2xl p-4 text-sm font-bold text-gray-500 outline-none cursor-not-allowed">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-[9px] uppercase font-black tracking-widest text-gray-600 pl-1">Active Qty</label>
            <input id="edit-qty" type="number" step="any" min="0" required
              class="w-full bg-[#0f1110] border border-[#d4af37]/20 rounded-2xl p-4 text-xl font-black text-[#f3cf7a] focus:border-[#d4af37]/50 outline-none transition-all">
          </div>
          <div class="space-y-2">
            <label class="text-[9px] uppercase font-black tracking-widest text-gray-600 pl-1">Alert Limit</label>
            <input id="edit-min-limit" type="number" step="any" min="0" required
              class="w-full bg-[#0f1110] border border-white/5 rounded-2xl p-4 text-sm font-bold text-white focus:border-[#d4af37]/30 outline-none transition-all">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-white/5">
          <button type="button" onclick="closeEditModal()"
            class="py-4 rounded-2xl bg-white/5 text-gray-400 font-black text-[11px] uppercase tracking-widest hover:bg-white/10 transition-all">Cancel</button>
          <button type="submit"
            class="py-4 rounded-2xl bg-gradient-to-r from-[#d4af37] to-[#f3cf7a] text-black font-black text-[11px] uppercase tracking-widest hover:scale-[1.02] transition-all">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="public/js/admin-stock.js"></script>
<?php renderFooter(); ?>
