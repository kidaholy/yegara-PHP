<?php
/**
 * Admin Store — Warehouse Hub (5 tabs)
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';
requireAuth(['admin', 'store', 'store_keeper'], 'store:view');

$user   = getCurrentUser();
$isAdmin = ($user['role'] === 'admin');

renderHeader("Warehouse Store");
?>

<script>
  window.storeRole = <?= json_encode($user['role']) ?>;
  window.storeIsAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
</script>

<div class="flex-1 w-full bg-[#0f1110] py-4 lg:py-8 px-4 lg:px-8 xl:px-12 transition-all">
  <div class="max-w-[1600px] mx-auto">
    <div class="flex flex-col lg:grid lg:grid-cols-12 gap-6 lg:gap-8 items-start">

      <!-- ═══════════ SIDEBAR ═══════════ -->
      <aside class="lg:col-span-4 xl:col-span-3 space-y-4 lg:space-y-5 lg:sticky lg:top-24 w-full">

        <!-- Valuation Card -->
        <div class="glass p-5 lg:p-7 rounded-xl lg:rounded-2xl border border-gray-700/50 bg-gray-800/80 relative overflow-hidden transition-all">
          <div class="absolute -right-4 -bottom-4 opacity-[0.04] pointer-events-none">
            <i data-lucide="warehouse" class="w-24 lg:w-32 h-24 lg:h-32 text-[#c5a059]"></i>
          </div>
          <div class="flex items-center gap-3 lg:gap-4 mb-4 lg:mb-7">
            <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-xl bg-gray-900 border border-gray-700 flex items-center justify-center text-[#c5a059]">
              <i data-lucide="warehouse" class="w-4 lg:w-5 h-4 lg:h-5"></i>
            </div>
            <div>
              <h2 class="text-lg lg:text-xl font-bold text-white leading-none mb-1">Store</h2>
              <p class="text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500">Warehouse Valuation</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-col gap-4 mb-4 lg:mb-7">
            <div class="bg-gray-900/40 p-4 rounded-xl border border-gray-700/30">
              <p class="text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Total Bulk Value</p>
              <h3 id="si-store-value" class="text-xl lg:text-3xl font-bold text-white">0.00 Br</h3>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div class="p-3 rounded-xl bg-gray-900/40 border border-gray-700/30">
                <p id="si-sku-count" class="text-base lg:text-lg font-bold text-white leading-none">0</p>
                <p class="text-[10px] uppercase tracking-wider text-gray-500 mt-1">SKU Count</p>
              </div>
              <div class="p-3 rounded-xl bg-gray-900/40 border border-gray-700/30">
                <p id="si-asset-value" class="text-base lg:text-lg font-bold text-white leading-none">0.00 Br</p>
                <p class="text-[10px] uppercase tracking-wider text-gray-500 mt-1">Assets</p>
              </div>
            </div>
            <div class="sm:col-span-2 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-3">
              <i data-lucide="receipt" class="w-4 h-4 lg:w-5 lg:h-5 text-emerald-400 flex-shrink-0"></i>
              <div class="flex-1 flex justify-between sm:block">
                <p class="text-[10px] uppercase tracking-wider text-emerald-600 sm:mb-0.5">Expenses</p>
                <p id="si-expense-total" class="text-sm lg:text-base font-bold text-emerald-400">0.00 Br</p>
              </div>
            </div>
          </div>

          <?php if ($isAdmin): ?>
          <div class="space-y-2 lg:space-y-2.5">
            <button onclick="openAddItem()" class="w-full py-2.5 lg:py-3 rounded-xl bg-[#c5a059] text-gray-900 font-bold text-xs lg:text-sm tracking-wide hover:bg-[#b08d4a] active:scale-95 transition-colors flex items-center justify-center gap-2">
              <i data-lucide="plus" class="w-4 h-4"></i> Add New Item
            </button>
            <div class="grid grid-cols-3 gap-2">
              <button onclick="switchTab('categories')" class="py-2 rounded-lg bg-gray-900 border border-gray-700 text-[10px] font-semibold text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">Categories</button>
              <button onclick="openExpenseForm()" class="py-2 rounded-lg bg-gray-900 border border-gray-700 text-[10px] font-semibold text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">+ Expense</button>
              <button onclick="openAddAsset()" class="py-2 rounded-lg bg-gray-900 border border-gray-700 text-[10px] font-semibold text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">+ Asset</button>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </aside>

      <!-- ═══════════ MAIN PANEL ═══════════ -->
      <main class="lg:col-span-8 xl:col-span-9 w-full">

        <!-- Tabs Bar -->
        <div class="glass rounded-xl lg:rounded-2xl border border-gray-700/50 bg-gray-900/40 overflow-hidden transition-all">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-700/50">
            <nav class="flex items-center gap-1 p-1 bg-gray-800 rounded-lg overflow-x-auto no-scrollbar max-w-full">
              <?php
              $tabs = [
                ['inventory',    'Bulk Inventory', 'box'],
                ['fixed-assets', 'Assets',         'building-2'],
                ['categories',   'Categories',     'tag'],
                ['expenses',     'Expenses',       'receipt'],
                ['transfers',    'Transfers',      'arrow-right-left'],
              ];
              foreach ($tabs as [$key, $label, $icon]):
              ?>
              <button onclick="switchTab('<?= $key ?>')" data-tab="<?= $key ?>"
                class="store-tab-btn flex items-center gap-2 px-2.5 lg:px-3 py-1.5 lg:py-2 rounded-md text-[10px] lg:text-xs font-semibold text-gray-400 transition-colors hover:text-gray-200 whitespace-nowrap">
                <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i><?= $label ?>
              </button>
              <?php endforeach; ?>
            </nav>
            <!-- Search -->
            <div class="relative w-full sm:w-auto">
              <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
              <input type="text" oninput="handleSearch(event)" placeholder="Search..."
                class="bg-gray-800 border border-gray-700/50 rounded-lg pl-9 pr-4 py-2 text-xs lg:text-sm text-white focus:border-[#c5a059] outline-none w-full sm:w-48 transition-all placeholder:text-gray-500">
            </div>
          </div>

          <!-- Loader -->
          <div id="tab-loader" class="flex flex-col items-center justify-center py-32">
            <i data-lucide="loader-2" class="w-8 h-8 text-[#c5a059] animate-spin mb-4"></i>
            <p class="text-xs uppercase font-semibold tracking-widest text-gray-500">Loading Warehouse...</p>
          </div>

          <!-- Tab Content -->
          <div id="tab-content" class="hidden p-6 min-h-[400px]"></div>
        </div>

      </main>
    </div>
  </div>
</div>

<!-- ╔══════════════════════════════════════╗ -->
<!-- ║           M O D A L S               ║ -->
<!-- ╚══════════════════════════════════════╝ -->

<?php
$modalWrap = 'fixed inset-0 z-[999] flex items-end sm:items-center justify-center px-4 hidden';
$overlay   = 'absolute inset-0 bg-black/80 backdrop-blur-md';
$panel     = 'w-full max-w-lg rounded-2xl bg-gray-900 border border-gray-700 shadow-2xl relative z-10 overflow-hidden';
$input     = 'w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-sm text-white focus:border-[#c5a059] outline-none transition-colors placeholder:text-gray-500';
$label     = 'text-xs font-semibold text-gray-400';
$btn       = 'w-full py-3 rounded-lg bg-[#c5a059] text-gray-900 font-bold text-sm uppercase tracking-wider hover:bg-[#b08d4a] active:scale-95 transition-all';
$cancel    = 'w-full py-3 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 font-bold text-sm uppercase tracking-wider hover:bg-gray-700 hover:text-white transition-colors';
?>

<!-- RESTOCK MODAL -->
<div id="modal-restock" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-restock')"></div>
  <div class="<?= $panel ?>">
    <div class="p-7">
      <h2 class="text-xl font-bold text-white mb-1">Restock</h2>
      <p id="restock-item-name" class="text-xs font-semibold text-gray-500 mb-5"></p>
      <div class="mb-5 p-3 rounded-xl bg-gray-800 border border-gray-700 flex justify-between items-center">
        <span class="text-xs font-semibold text-gray-400">Current in Store</span>
        <span id="restock-current" class="font-bold text-white text-base"></span>
      </div>
      <form onsubmit="submitRestock(event)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Qty to Add</label>
            <input id="restock-qty" type="number" step="any" min="0.001" required class="<?= $input ?> text-[#c5a059]">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Unit Purchase Price (Br)</label>
            <input id="restock-unit-price" type="number" step="any" min="0" required class="<?= $input ?>">
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="<?= $label ?>">New Selling Price (Ref - optional)</label>
          <input id="restock-upc" type="number" step="any" min="0" class="<?= $input ?>">
        </div>
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Notes</label>
          <input id="restock-notes" type="text" class="<?= $input ?>" placeholder="e.g. Weekly supplies restock">
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3">
          <button type="button" onclick="closeModal('modal-restock')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="<?= $btn ?>">Confirm Restock</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DECREASE STOCK MODAL -->
<div id="modal-decrease" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-decrease')"></div>
  <div class="<?= $panel ?>">
    <div class="p-7">
      <h2 class="text-xl font-bold text-red-400 mb-1">Decrease Bulk Stock</h2>
      <p id="decrease-item-name" class="text-xs font-semibold text-gray-500 mb-5"></p>
      <div class="mb-5 p-3 rounded-xl bg-gray-800 border border-gray-700 flex justify-between items-center">
        <span class="text-xs font-semibold text-gray-400">Store Quantity</span>
        <span id="decrease-current" class="font-bold text-white text-base"></span>
      </div>
      <form onsubmit="submitDecrease(event)" class="space-y-4">
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Qty to Remove (Wastage/Usage)</label>
          <input id="decrease-qty" type="number" step="any" min="0.001" required class="<?= $input ?> text-red-400">
        </div>
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Notes / Reason</label>
          <input id="decrease-notes" type="text" class="<?= $input ?>" placeholder="e.g. Expired, Spilled...">
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3">
          <button type="button" onclick="closeModal('modal-decrease')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-3 rounded-lg bg-red-600 text-white font-bold text-sm uppercase tracking-wider hover:bg-red-500 active:scale-95 transition-all">Confirm Decrease</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- RESTOCK EXPENSE MODAL -->
<div id="modal-restock-expense" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-restock-expense')"></div>
  <div class="<?= $panel ?>">
    <div class="p-7">
      <h2 class="text-xl font-bold text-emerald-500 mb-1">Restock Expense</h2>
      <p id="res-exp-name" class="text-xs font-semibold text-gray-500 mb-5"></p>
      <div class="mb-5 p-3 rounded-xl bg-gray-800 border border-gray-700 flex justify-between items-center">
        <span class="text-xs font-semibold text-gray-400">Recorded Quantity</span>
        <span id="res-exp-current" class="font-bold text-white text-base"></span>
      </div>
      <form onsubmit="submitRestockExpense(event)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Qty to Add</label>
            <input id="res-exp-qty" type="number" step="any" min="0.001" required class="<?= $input ?> text-emerald-400">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">New Unit Price (Br)</label>
            <input id="res-exp-unit-price" type="number" step="any" min="0" required class="<?= $input ?>">
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Notes</label>
          <input id="res-exp-notes" type="text" class="<?= $input ?>" placeholder="e.g. Additional supplies...">
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3">
          <button type="button" onclick="closeModal('modal-restock-expense')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-3 rounded-lg bg-emerald-600 text-white font-bold text-sm uppercase tracking-wider hover:bg-emerald-500 active:scale-95 transition-all">Add & Update Price</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DECREASE EXPENSE MODAL -->
<div id="modal-decrease-expense" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-decrease-expense')"></div>
  <div class="<?= $panel ?>">
    <div class="p-7">
      <h2 class="text-xl font-bold text-red-500 mb-1">Decrease Expense Record</h2>
      <p id="dec-exp-name" class="text-xs font-semibold text-gray-500 mb-5"></p>
      <div class="mb-5 p-3 rounded-xl bg-gray-800 border border-gray-700 flex justify-between items-center">
        <span class="text-xs font-semibold text-gray-400">Recorded Quantity</span>
        <span id="dec-exp-current" class="font-bold text-white text-base"></span>
      </div>
      <form onsubmit="submitDecreaseExpense(event)" class="space-y-4">
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Qty to Remove</label>
          <input id="dec-exp-qty" type="number" step="any" min="0.001" required class="<?= $input ?> text-red-400">
        </div>
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Reason / Adjustment Notes</label>
          <input id="dec-exp-notes" type="text" class="<?= $input ?>" placeholder="e.g. Refunded, Data correction...">
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3">
          <button type="button" onclick="closeModal('modal-decrease-expense')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-3 rounded-lg bg-red-600 text-white font-bold text-sm uppercase tracking-wider hover:bg-red-500 active:scale-95 transition-all">Apply Adjustment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- TRANSFER MODAL -->
<div id="modal-transfer" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-transfer')"></div>
  <div class="<?= $panel ?>">
    <div class="p-7">
      <h2 class="text-xl font-bold text-white mb-1">Transfer to POS</h2>
      <p id="transfer-item-name" class="text-xs font-semibold text-gray-500 mb-5"></p>
      <div class="mb-5 p-3 rounded-xl bg-gray-900 border border-gray-700 flex justify-between items-center">
        <span class="text-xs font-semibold text-gray-400">Bulk Available</span>
        <span id="transfer-store-qty" class="font-bold text-[#c5a059] text-base"></span>
      </div>
      <?php if (!$isAdmin): ?>
      <div class="mb-4 p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-400 font-semibold">
        Your request will be sent for Admin approval.
      </div>
      <?php endif; ?>
      <form onsubmit="submitTransfer(event)" class="space-y-4">
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Quantity to Transfer</label>
          <input id="transfer-qty" type="number" step="any" min="0.001" required class="<?= $input ?> text-[#c5a059] text-lg">
        </div>
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Notes (optional)</label>
          <input id="transfer-notes" type="text" class="<?= $input ?>" placeholder="e.g. Morning shift restock">
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3">
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
    <div class="p-7 overflow-y-auto" style="max-height:90vh">
      <h2 id="item-form-title" class="text-xl font-bold text-white mb-5">New Store Item</h2>
      <form onsubmit="submitItemForm(event)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 space-y-1.5">
            <label class="<?= $label ?>">Item Name</label>
            <input id="item-name" type="text" required class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Category</label>
            <select id="item-category" required class="<?= $input ?> appearance-none"></select>
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Unit (kg/L/pcs)</label>
            <select id="item-unit" class="<?= $input ?> appearance-none">
              <option>pcs</option><option>kg</option><option>L</option><option>g</option><option>ml</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Initial Bulk Qty</label>
            <input id="item-store-qty" type="number" step="any" min="0" value="0" class="<?= $input ?> text-[#c5a059]">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Unit Buy Price (Br)</label>
            <input id="item-buy-price" type="number" step="any" min="0" value="0" class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">POS Alert Limit</label>
            <input id="item-min-limit" type="number" step="any" min="0" value="5" class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Store Alert Limit</label>
            <input id="item-store-min" type="number" step="any" min="0" value="20" class="<?= $input ?>">
          </div>
          <div class="col-span-2 space-y-1.5">
            <label class="<?= $label ?>">POS Unit Sell Price (Ref)</label>
            <input id="item-sell-price" type="number" step="any" min="0" value="0" class="<?= $input ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-700">
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
    <div class="p-7">
      <h2 id="asset-form-title" class="text-xl font-bold text-white mb-5">New Fixed Asset</h2>
      <form onsubmit="submitAssetForm(event)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 space-y-1.5">
            <label class="<?= $label ?>">Asset Name</label>
            <input id="asset-name" type="text" required class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Category</label>
            <select id="asset-category" class="<?= $input ?> appearance-none"></select>
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Quantity</label>
            <input id="asset-qty" type="number" min="1" value="1" required class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Unit Price (Br)</label>
            <input id="asset-price" type="number" step="any" min="0" value="0" required class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Purchase Date</label>
            <input id="asset-date" type="date" class="<?= $input ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-700">
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
    <div class="p-7">
      <h2 class="text-xl font-bold text-amber-400 mb-1">Dismiss Asset</h2>
      <p id="dismiss-asset-name" class="text-xs font-semibold text-gray-500 mb-5"></p>
      <form onsubmit="submitDismiss(event)" class="space-y-4">
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Reason</label>
          <input id="dismiss-reason" type="text" required placeholder="e.g. Damaged, Worn out..." class="<?= $input ?>">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Quantity Dismissed</label>
            <input id="dismiss-qty" type="number" step="any" min="0.001" required class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Value Lost (Br)</label>
            <input id="dismiss-value-lost" type="number" step="any" min="0" value="0" class="<?= $input ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-700">
          <button type="button" onclick="closeModal('modal-dismiss')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-3 rounded-lg bg-amber-500 text-gray-900 font-bold text-sm uppercase tracking-wider hover:bg-amber-400 active:scale-95 transition-all">Confirm Dismiss</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EXPENSE MODAL -->
<div id="modal-expense" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-expense')"></div>
  <div class="<?= $panel ?>">
    <div class="p-7">
      <h2 class="text-xl font-bold text-white mb-5">New Expense</h2>
      <form onsubmit="submitExpenseForm(event)" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 space-y-1.5">
            <label class="<?= $label ?>">Expense Name</label>
            <input id="exp-name" type="text" required class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Category</label>
            <select id="exp-category" class="<?= $input ?> appearance-none"></select>
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Date</label>
            <input id="exp-date" type="date" required class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Unit Cost (Br)</label>
            <input id="exp-unit-cost" type="number" step="any" min="0" required class="<?= $input ?>">
          </div>
          <div class="space-y-1.5">
            <label class="<?= $label ?>">Quantity</label>
            <input id="exp-qty" type="number" step="any" min="0" required class="<?= $input ?>">
          </div>
          <div class="col-span-2 space-y-1.5">
            <label class="<?= $label ?>">Unit</label>
            <select id="exp-unit" class="<?= $input ?> appearance-none">
              <option>pcs</option><option>kg</option><option>L</option>
            </select>
          </div>
          <div class="col-span-2 space-y-1.5">
            <label class="<?= $label ?>">Description</label>
            <input id="exp-desc" type="text" class="<?= $input ?>">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-700">
          <button type="button" onclick="closeModal('modal-expense')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-3 rounded-lg bg-emerald-500 text-gray-900 font-bold text-sm uppercase tracking-wider hover:bg-emerald-400 active:scale-95 transition-all">Add Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DENIAL MODAL -->
<div id="modal-denial" class="<?= $modalWrap ?>">
  <div class="<?= $overlay ?>" onclick="closeModal('modal-denial')"></div>
  <div class="<?= $panel ?>">
    <div class="p-7">
      <h2 class="text-xl font-bold text-red-400 mb-5">Deny Transfer</h2>
      <form onsubmit="submitDenial(event)" class="space-y-4">
        <div class="space-y-1.5">
          <label class="<?= $label ?>">Denial Reason</label>
          <textarea id="denial-reason" required rows="3" placeholder="Explain why this request is denied..."
            class="<?= $input ?> resize-none"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-700">
          <button type="button" onclick="closeModal('modal-denial')" class="<?= $cancel ?>">Cancel</button>
          <button type="submit" class="w-full py-3 rounded-lg bg-red-600 text-white font-bold text-sm uppercase tracking-wider hover:bg-red-500 active:scale-95 transition-all">Confirm Deny</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  .store-tab-btn.active {
    background-color: rgb(31 41 55); /* gray-800 equivalent */
    color: #c5a059;
  }
</style>

<script src="public/js/admin-store.js?v=<?= time() ?>"></script>
<?php renderFooter(); ?>
