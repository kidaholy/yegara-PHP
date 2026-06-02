<?php
/**
 * Admin Store — Warehouse Hub (5 tabs)
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';
requireAuth(['admin', 'store_keeper']);

$user   = getCurrentUser();
$isAdmin = ($user['role'] === 'admin');

renderHeader("Warehouse Store");
?>

<script>
  window.storeRole = <?= json_encode($user['role']) ?>;
  window.storeIsAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
</script>

<div class="flex-1 w-full bg-[#0f1110] py-10 px-4 lg:px-10 xl:px-16">
  <div class="max-w-[1600px] mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

      <!-- ═══════════ SIDEBAR ═══════════ -->
      <aside class="lg:col-span-4 xl:col-span-3 space-y-6 sticky top-24">

        <!-- Valuation Card -->
        <div class="glass p-8 rounded-[2.5rem] border border-white/5 bg-[#151716] shadow-2xl relative overflow-hidden group">
          <div class="absolute -right-4 -bottom-4 opacity-[0.04] group-hover:rotate-12 transition-transform duration-1000 pointer-events-none">
            <i data-lucide="warehouse" class="w-32 h-32 text-[#d4af37]"></i>
          </div>
          <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 rounded-2xl bg-[#1a1712] border border-[#d4af37]/20 flex items-center justify-center text-[#d4af37]">
              <i data-lucide="warehouse" class="w-6 h-6"></i>
            </div>
            <div>
              <h2 class="text-2xl font-black font-playfair italic text-[#f3cf7a]">Store</h2>
              <p class="text-[9px] uppercase font-black tracking-widest text-gray-500">Warehouse Valuation</p>
            </div>
          </div>

          <div class="space-y-5 mb-8">
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-gray-600 mb-1">Total Bulk Value</p>
              <h3 id="si-store-value" class="text-4xl font-black text-white">0.00 Br</h3>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="p-4 rounded-2xl bg-white/5 border border-white/5">
                <p id="si-sku-count" class="text-xl font-bold text-white">0</p>
                <p class="text-[8px] uppercase font-black tracking-widest text-gray-600">SKU Count</p>
              </div>
              <div class="p-4 rounded-2xl bg-white/5 border border-white/5">
                <p id="si-asset-value" class="text-xl font-bold text-white">0.00 Br</p>
                <p class="text-[8px] uppercase font-black tracking-widest text-gray-600">Fixed Assets</p>
              </div>
            </div>
            <div class="p-4 rounded-2xl bg-emerald-500/[0.04] border border-emerald-500/10 flex items-center gap-4">
              <i data-lucide="receipt" class="w-5 h-5 text-emerald-500/60 flex-shrink-0"></i>
              <div>
                <p id="si-expense-total" class="text-lg font-black text-emerald-400">0.00 Br</p>
                <p class="text-[8px] uppercase font-black tracking-widest text-emerald-600">Total Expenses</p>
              </div>
            </div>
          </div>

          <?php if ($isAdmin): ?>
          <div class="space-y-3">
            <button onclick="openAddItem()" class="w-full py-4 rounded-2xl bg-gradient-to-r from-[#d4af37] to-[#f3cf7a] text-black font-black text-[11px] uppercase tracking-[0.15em] shadow-[0_10px_30px_rgba(212,175,55,0.25)] hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
              <i data-lucide="plus" class="w-4 h-4"></i> Add New Item
            </button>
            <div class="grid grid-cols-3 gap-2">
              <button onclick="switchTab('categories')" class="py-3 rounded-xl bg-white/5 text-[9px] font-black uppercase text-gray-500 hover:text-white hover:bg-white/10 transition-all">Categories</button>
              <button onclick="openExpenseForm()" class="py-3 rounded-xl bg-white/5 text-[9px] font-black uppercase text-gray-500 hover:text-white hover:bg-white/10 transition-all">+ Expense</button>
              <button onclick="openAddAsset()" class="py-3 rounded-xl bg-white/5 text-[9px] font-black uppercase text-gray-500 hover:text-white hover:bg-white/10 transition-all">+ Asset</button>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Flow Info -->
        <div class="glass p-6 rounded-3xl border border-white/5 hidden lg:block">
          <h3 class="text-[9px] font-black uppercase tracking-widest text-[#d4af37] mb-3">Inventory Flow</h3>
          <p class="text-[9px] text-gray-600 leading-loose">
            Create items here → bulk enters <span class="text-[#d4af37]">Store</span>.<br>
            Transfer to <a href="stock.php" class="text-[#d4af37] underline decoration-dotted">Active Stock</a> to make available for POS sales.
          </p>
        </div>
      </aside>

      <!-- ═══════════ MAIN PANEL ═══════════ -->
      <main class="lg:col-span-8 xl:col-span-9 space-y-8">

        <!-- Tabs Bar -->
        <div class="flex items-center justify-between flex-wrap gap-4">
          <nav class="flex items-center gap-1 border-b border-white/5 pb-0">
            <?php
            $tabs = [
              ['inventory',    'Bulk Inventory', 'box'],
              ['fixed-assets', 'Fixed Assets',   'building-2'],
              ['categories',   'Categories',     'tag'],
              ['expenses',     'Expenses',       'receipt'],
              ['transfers',    'Transfers',      'arrow-right-left'],
            ];
            foreach ($tabs as [$key, $label, $icon]):
            ?>
            <button onclick="switchTab('<?= $key ?>')" data-tab="<?= $key ?>"
              class="store-tab-btn flex items-center gap-2 px-4 py-3 border-b-2 border-transparent text-gray-500 font-black text-[10px] uppercase tracking-widest transition-all hover:text-white whitespace-nowrap">
              <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i><?= $label ?>
            </button>
            <?php endforeach; ?>
          </nav>
          <!-- Search -->
          <div class="relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-600"></i>
            <input type="text" oninput="handleSearch(event)" placeholder="Search..."
              class="bg-white/5 border border-white/5 rounded-full pl-10 pr-5 py-2.5 text-[11px] font-bold text-white focus:border-[#d4af37]/30 outline-none w-56 transition-all">
          </div>
        </div>

        <!-- Loader -->
        <div id="tab-loader" class="flex flex-col items-center justify-center py-40 animate-pulse">
          <i data-lucide="loader-2" class="w-10 h-10 text-[#d4af37] animate-spin mb-5"></i>
          <p class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-600">Loading Warehouse...</p>
        </div>

        <!-- Tab Content -->
        <div id="tab-content" class="hidden min-h-[500px]"></div>

      </main>
    </div>
  </div>
</div>

<!-- ╔══════════════════════════════════════╗ -->
<!-- ║           M O D A L S               ║ -->
<!-- ╚══════════════════════════════════════╝ -->

<?php
$modalWrap = 'fixed inset-0 z-[999] flex items-end sm:items-center justify-center px-4 hidden';
$overlay   = 'absolute inset-0 bg-black/85 backdrop-blur-md';
$panel     = 'glass w-full max-w-lg rounded-[2.5rem] bg-[#151716] border border-white/10 shadow-[0_0_80px_rgba(0,0,0,0.8)] relative z-10 overflow-hidden';
$input     = 'w-full bg-[#0f1110] border border-white/5 rounded-2xl p-4 text-sm font-bold text-white focus:border-[#d4af37]/30 outline-none transition-all';
$label     = 'text-[9px] uppercase font-black tracking-widest text-gray-600 pl-1';
$btn       = 'w-full py-4 rounded-2xl bg-gradient-to-r from-[#d4af37] to-[#f3cf7a] text-black font-black text-[11px] uppercase tracking-widest shadow-xl hover:scale-[1.02] active:scale-95 transition-all';
$cancel    = 'w-full py-4 rounded-2xl bg-white/5 text-gray-400 font-black text-[11px] uppercase tracking-widest hover:bg-white/10 transition-all';
?>

<!-- RESTOCK MODAL -->
<div id="modal-restock" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-restock')"></div>
  <div class="<?= $panel ?>">
    <div class="p-8">
      <h2 class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-1">Restock</h2>
      <p id="restock-item-name" class="text-[9px] uppercase font-black tracking-widest text-gray-500 mb-6"></p>
      <div class="mb-6 p-4 rounded-2xl bg-white/5 border border-white/5 flex justify-between items-center">
        <span class="text-[10px] uppercase font-black text-gray-600">Current in Store</span>
        <span id="restock-current" class="font-bold text-white text-lg"></span>
      </div>
      <form onsubmit="submitRestock(event)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="<?= $label ?>">Qty to Add</label>
            <input id="restock-qty" type="number" step="any" min="0.001" required class="<?= $input ?> text-[#f3cf7a]">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Total Purchase Cost</label>
            <input id="restock-cost" type="number" step="any" min="0" required class="<?= $input ?>">
          </div>
        </div>
        <div class="space-y-2">
          <label class="<?= $label ?>">New Unit Cost (optional)</label>
          <input id="restock-upc" type="number" step="any" min="0" class="<?= $input ?>">
        </div>
        <div class="space-y-2">
          <label class="<?= $label ?>">Notes</label>
          <input id="restock-notes" type="text" class="<?= $input ?>" placeholder="e.g. Weekly supplies restock">
        </div>
        <div class="grid grid-cols-2 gap-3 pt-4">
          <button type="button" onclick="closeModal('modal-restock')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="<?= $btn ?>">Confirm Restock</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- TRANSFER MODAL -->
<div id="modal-transfer" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-transfer')"></div>
  <div class="<?= $panel ?>">
    <div class="p-8">
      <h2 class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-1">Transfer to POS</h2>
      <p id="transfer-item-name" class="text-[9px] uppercase font-black tracking-widest text-gray-500 mb-6"></p>
      <div class="mb-6 p-4 rounded-2xl bg-[#1a1712] border border-[#d4af37]/10 flex justify-between items-center">
        <span class="text-[10px] uppercase font-black text-gray-600">Bulk Available</span>
        <span id="transfer-store-qty" class="font-bold text-[#d4af37]"></span>
      </div>
      <?php if (!$isAdmin): ?>
      <div class="mb-4 p-4 rounded-2xl bg-amber-400/5 border border-amber-400/10 text-[10px] text-amber-400 font-bold">
        Your request will be sent for Admin approval.
      </div>
      <?php endif; ?>
      <form onsubmit="submitTransfer(event)" class="space-y-4">
        <div class="space-y-2">
          <label class="<?= $label ?>">Quantity to Transfer</label>
          <input id="transfer-qty" type="number" step="any" min="0.001" required class="<?= $input ?> text-[#f3cf7a] text-xl">
        </div>
        <div class="space-y-2">
          <label class="<?= $label ?>">Notes (optional)</label>
          <input id="transfer-notes" type="text" class="<?= $input ?>" placeholder="e.g. Morning shift restock">
        </div>
        <div class="grid grid-cols-2 gap-3 pt-4">
          <button type="button" onclick="closeModal('modal-transfer')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="<?= $btn ?>"><?= $isAdmin ? 'Transfer Now' : 'Request Transfer' ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ADD/EDIT ITEM MODAL -->
<div id="modal-item" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-item')"></div>
  <div class="<?= $panel ?> max-w-xl">
    <div class="p-8 overflow-y-auto" style="max-height:90vh">
      <h2 id="item-form-title" class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-6">New Store Item</h2>
      <form onsubmit="submitItemForm(event)" class="space-y-5">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 space-y-2">
            <label class="<?= $label ?>">Item Name</label>
            <input id="item-name" type="text" required class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Category</label>
            <input id="item-category" type="text" required class="<?= $input ?>" list="item-cat-list">
            <datalist id="item-cat-list"></datalist>
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Unit (kg/L/pcs)</label>
            <select id="item-unit" class="<?= $input ?> appearance-none">
              <option>pcs</option><option>kg</option><option>L</option><option>g</option><option>ml</option>
            </select>
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Initial Bulk Qty</label>
            <input id="item-store-qty" type="number" step="any" min="0" value="0" class="<?= $input ?> text-[#f3cf7a]">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Unit Buy Price (Br)</label>
            <input id="item-buy-price" type="number" step="any" min="0" value="0" class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">POS Alert Limit</label>
            <input id="item-min-limit" type="number" step="any" min="0" value="5" class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Store Alert Limit</label>
            <input id="item-store-min" type="number" step="any" min="0" value="20" class="<?= $input ?>">
          </div>
          <div class="col-span-2 space-y-2">
            <label class="<?= $label ?>">POS Unit Sell Price (Ref)</label>
            <input id="item-sell-price" type="number" step="any" min="0" value="0" class="<?= $input ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-white/5">
          <button type="button" onclick="closeModal('modal-item')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="<?= $btn ?>">Save Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- FIXED ASSET MODAL -->
<div id="modal-asset" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-asset')"></div>
  <div class="<?= $panel ?>">
    <div class="p-8">
      <h2 id="asset-form-title" class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-6">New Fixed Asset</h2>
      <form onsubmit="submitAssetForm(event)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 space-y-2">
            <label class="<?= $label ?>">Asset Name</label>
            <input id="asset-name" type="text" required class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Category</label>
            <input id="asset-category" type="text" class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Quantity</label>
            <input id="asset-qty" type="number" min="1" value="1" required class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Unit Price (Br)</label>
            <input id="asset-price" type="number" step="any" min="0" value="0" required class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Purchase Date</label>
            <input id="asset-date" type="date" class="<?= $input ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-white/5">
          <button type="button" onclick="closeModal('modal-asset')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="<?= $btn ?>">Save Asset</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DISMISS ASSET MODAL -->
<div id="modal-dismiss" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-dismiss')"></div>
  <div class="<?= $panel ?>">
    <div class="p-8">
      <h2 class="text-3xl font-black font-playfair italic text-amber-400 mb-1">Dismiss Asset</h2>
      <p id="dismiss-asset-name" class="text-[9px] uppercase font-black tracking-widest text-gray-500 mb-6"></p>
      <form onsubmit="submitDismiss(event)" class="space-y-4">
        <div class="space-y-2">
          <label class="<?= $label ?>">Reason</label>
          <input id="dismiss-reason" type="text" required placeholder="e.g. Damaged, Worn out..." class="<?= $input ?>">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="<?= $label ?>">Quantity Dismissed</label>
            <input id="dismiss-qty" type="number" step="any" min="0.001" required class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Value Lost (Br)</label>
            <input id="dismiss-value-lost" type="number" step="any" min="0" value="0" class="<?= $input ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-white/5">
          <button type="button" onclick="closeModal('modal-dismiss')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-4 rounded-2xl bg-amber-500 text-black font-black text-[11px] uppercase tracking-widest transition-all hover:scale-[1.02]">Confirm Dismiss</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EXPENSE MODAL -->
<div id="modal-expense" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-expense')"></div>
  <div class="<?= $panel ?>">
    <div class="p-8">
      <h2 class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-6">New Expense</h2>
      <form onsubmit="submitExpenseForm(event)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 space-y-2">
            <label class="<?= $label ?>">Expense Name</label>
            <input id="exp-name" type="text" required class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Category</label>
            <input id="exp-category" type="text" class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Date</label>
            <input id="exp-date" type="date" required class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Unit Cost (Br)</label>
            <input id="exp-unit-cost" type="number" step="any" min="0" required class="<?= $input ?>">
          </div>
          <div class="space-y-2">
            <label class="<?= $label ?>">Quantity</label>
            <input id="exp-qty" type="number" step="any" min="0" required class="<?= $input ?>">
          </div>
          <div class="col-span-2 space-y-2">
            <label class="<?= $label ?>">Unit</label>
            <select id="exp-unit" class="<?= $input ?> appearance-none">
              <option>pcs</option><option>kg</option><option>L</option>
            </select>
          </div>
          <div class="col-span-2 space-y-2">
            <label class="<?= $label ?>">Description</label>
            <input id="exp-desc" type="text" class="<?= $input ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-white/5">
          <button type="button" onclick="closeModal('modal-expense')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-4 rounded-2xl bg-emerald-500 text-black font-black text-[11px] uppercase tracking-widest hover:scale-[1.02] transition-all">Add Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DENIAL MODAL -->
<div id="modal-denial" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-denial')"></div>
  <div class="<?= $panel ?>">
    <div class="p-8">
      <h2 class="text-3xl font-black font-playfair italic text-red-400 mb-6">Deny Transfer</h2>
      <form onsubmit="submitDenial(event)" class="space-y-4">
        <div class="space-y-2">
          <label class="<?= $label ?>">Denial Reason</label>
          <textarea id="denial-reason" required rows="3" placeholder="Explain why this request is denied..."
            class="<?= $input ?> resize-none"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-white/5">
          <button type="button" onclick="closeModal('modal-denial')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-4 rounded-2xl bg-red-600 text-white font-black text-[11px] uppercase tracking-widest hover:bg-red-500 transition-all">Confirm Deny</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="public/js/admin-store.js"></script>
<?php renderFooter(); ?>
