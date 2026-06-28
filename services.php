<?php
require_once 'includes/layout.php';
requireAuth(['admin', 'reception', 'receptionist'], ['services:view', 'reception:access']);
$title = "Services Hub";
renderHeader($title);

// Load dynamic tiers for initial server-side render
$menuTiers = db('menuTiers')->findMany(['where' => ['isDeleted' => false]]) ?: [];
?>

<style>
    @keyframes slideInUp { from { transform: translateY(18px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .tab-content-anim { animation: slideInUp 0s cubic-bezier(.4,1,1,1) both; }

    :root {
        --reception-gold: #c5a059;
        --reception-dark: #0f1110;
        --reception-panel: #121413;
    }

    /* Premium Typography */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,900;1,900&display=swap');
    .font-serif-premium { font-family: 'Playfair Display', serif; }

    /* Tab active state */
    .services-tab-btn { transition: all .25s; border-bottom-width: 2px; }
    .services-tab-btn.active-tab { color: var(--reception-gold); border-bottom-color: var(--reception-gold); }
    .services-tab-btn:not(.active-tab) { color: #9ca3af; border-bottom-color: transparent; }
    .services-tab-btn:not(.active-tab):hover { color: #f3f4f6; }

    /* Custom Input Styles for Reception Spec */
    .ci-input-group { border-bottom: 1px solid rgba(55, 65, 81, 0.5); padding-bottom: 0.5rem; }
    .ci-label { display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em; color: #6b7280; margin-bottom: 0.375rem; }
    .ci-field { width: 100%; background: transparent; border: none; font-size: 0.875rem; color: white; outline: none; padding: 0.125rem 0; }
    .ci-field option { background-color: #121413; color: white; }
    .ci-field::placeholder { color: #374151; }

    /* Stay Summary Box */
    .summary-box { background: rgba(18, 20, 19, 0.6); border: 1px solid rgba(197, 160, 89, 0.15); border-radius: 1rem; padding: 1.5rem; position: relative; }
    .summary-metric { text-align: center; }
    .summary-val { font-size: 1.5rem; font-weight: 900; color: white; }
    .summary-unit { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; margin-top: 0.25rem; }

    /* Payment Buttons */
    .pay-btn { cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 1rem 0.5rem; border: 1px solid #1f2937; border-radius: 0.75rem; transition: all 0.2s; }
    .pay-btn-icon { width: 1.25rem; height: 1.25rem; color: #6b7280; transition: color 0.2s; }
    .pay-btn-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; text-align: center; }
    
    .pay-radio:checked + .pay-btn { background: rgba(197, 160, 89, 0.1); border-color: rgba(197, 160, 89, 0.5); }
    .pay-radio:checked + .pay-btn .pay-btn-icon { color: var(--reception-gold); }
    .pay-radio:checked + .pay-btn .pay-btn-label { color: var(--reception-gold); }

    /* ID Upload Previews */
    .id-preview-box { background: #0f1110; border: 1px solid #1f2937; border-radius: 0.75rem; overflow: hidden; position: relative; }
    .id-preview-img { width: 100%; height: 6rem; object-cover: cover; }
    .id-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); font-size: 9px; font-weight: 700; color: var(--reception-gold); text-align: center; padding: 0.25rem 0; text-transform: uppercase; letter-spacing: 0.1em; }
    .id-remove { position: absolute; top: 0.5rem; right: 0.5rem; width: 1.25rem; height: 1.25rem; background: #ef4444; color: white; border-radius: 99px; display: flex; items-center: center; justify-content: center; font-size: 10px; cursor: pointer; }

    /* Gold Gradient Button */
    .btn-gold { background: linear-gradient(to right, #c5a059, #b59048); color: #0f1110; transition: transform 0.2s, box-shadow 0.2s; }
    .btn-gold:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(197, 160, 89, 0.3); }
    .btn-gold:active { transform: translateY(0); }

    /* Full-viewport services / reception layout */
    #services-hub-shell {
        width: 100%;
        max-width: 100%;
        height: calc(100vh - 60px);
        min-height: 0;
    }
    #services-content-panel {
        min-height: 0;
        width: 100%;
    }
</style>

<div id="services-hub-shell" class="flex flex-col overflow-hidden bg-[#0f1110] w-full">
    <!-- Header / Tab Bar -->
    <header class="px-4 lg:px-6 xl:px-8 pt-4 lg:pt-6 pb-0 border-b border-gray-700/50 bg-gray-800/80 backdrop-blur-xl shrink-0">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-0">
            <div>
                <h1 class="text-2xl font-bold text-gray-200 tracking-tight">Services Hub</h1>
                <p class="text-xs uppercase font-semibold tracking-wider text-gray-500 mt-1">Operations & Logistics</p>
            </div>
            <!-- Tabs -->
            <nav class="flex items-end gap-2 overflow-x-auto pb-0">
                <?php
                $userRole = $_SESSION['role'] ?? 'guest';
                $isAdmin = $userRole === 'admin';
                
                $tabs = [];
                if ($isAdmin) {
                    $tabs = [
                        ['key'=>'rooms',        'label'=>'Rooms',         'icon'=>'building'],
                        ['key'=>'menu-standard','label'=>'Standard Menu', 'icon'=>'utensils'],
                        ['key'=>'vip',          'label'=>'VIP Menus',     'icon'=>'crown'],
                        ['key'=>'reception',    'label'=>'Reception',     'icon'=>'bell'],
                    ];
                } else {
                    $tabs = [
                        ['key'=>'reception',    'label'=>'Reception',     'icon'=>'bell'],
                    ];
                }
                $defaultTab = $tabs[0]['key'];
                
                foreach ($tabs as $t): ?>
                <button onclick="AdminServices.setTab('<?php echo $t['key']; ?>')"
                    data-tab="<?php echo $t['key']; ?>"
                    class="services-tab-btn flex items-center gap-2 px-4 py-3 text-xs font-bold uppercase tracking-wider whitespace-nowrap relative">
                    <i data-lucide="<?php echo $t['icon']; ?>" class="w-4 h-4"></i>
                    <?php echo $t['label']; ?>
                </button>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <!-- Content -->
    <div id="services-content-panel" class="flex-1 overflow-y-auto overflow-x-hidden p-4 lg:p-6 xl:p-8 w-full min-h-0">
        <!-- Populated by admin-services.js -->
    </div>
</div>

<script>
    window.INITIAL_TAB = "<?php echo $defaultTab; ?>";
    window.USER_ROLE = "<?php echo $userRole; ?>";
</script>

<!-- ═══════════════════════════════════════════════════════ MODALS ═══ -->

<!-- Room Modal -->
<div id="room-modal" class="hidden fixed inset-0 z-[100] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-gray-800 w-full max-w-2xl rounded-2xl p-8 border border-gray-700 shadow-2xl animate-in">
        <h2 id="room-modal-title" class="text-xl font-bold text-gray-200 mb-6 border-b border-gray-700/50 pb-4">Add New Room</h2>
        <form onsubmit="AdminServices._saveRoom(event)" class="space-y-5 text-gray-300">
            <input type="hidden" id="room-id">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="lbl">Room Number *</label>
                    <input type="text" id="room-number" required class="inp" placeholder="e.g. 101">
                </div>
                <div class="space-y-1.5">
                    <label class="lbl">Floor *</label>
                    <select id="room-floor" required class="inp appearance-none">
                        <option value="">Select floor…</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="lbl">Room Type</label>
                    <select id="room-type" class="inp appearance-none">
                        <option value="standard">Standard</option>
                        <option value="deluxe">Deluxe</option>
                        <option value="suite">Suite</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="lbl">Category</label>
                    <input type="text" id="room-category" class="inp" placeholder="e.g. Deluxe Suite">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="lbl">Price per Night (Br) *</label>
                    <input type="number" id="room-price" required min="0" class="inp" placeholder="0">
                </div>
                <div class="space-y-1.5">
                    <label class="lbl">Status</label>
                    <select id="room-status" class="inp appearance-none">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="dirty">Dirty</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-700/50 mt-4">
                <button type="button" onclick="document.getElementById('room-modal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 transition-colors border border-gray-700 hover:border-gray-600">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 border border-[#c5a059] hover:bg-[#b59048] transition-colors shadow-sm">Save Room</button>
            </div>
        </form>
    </div>
</div>

<!-- Tier Modal -->
<div id="tier-modal" class="hidden fixed inset-0 z-[100] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-gray-800 w-full max-w-lg rounded-2xl p-8 border border-gray-700 shadow-2xl animate-in">
        <h2 id="tier-modal-title" class="text-xl font-bold text-gray-200 mb-6 border-b border-gray-700/50 pb-4">Create New VIP Tier</h2>
        <form onsubmit="AdminServices._saveTier(event)" class="space-y-5 text-gray-300">
            <input type="hidden" id="tier-id">
            <div class="space-y-1.5">
                <label class="lbl">Tier Name *</label>
                <input type="text" id="tier-name" required class="inp" placeholder="e.g. VVIP">
            </div>
            <div class="space-y-1.5">
                <label class="lbl">Price Increase Percentage (%) *</label>
                <input type="number" id="tier-percentage" required min="1" step="0.1" class="inp" placeholder="e.g. 15">
                <p class="text-[10px] uppercase font-bold tracking-wider text-gray-500 mt-1">This will clone the Standard Menu and increase all prices.</p>
            </div>
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-700/50 mt-4">
                <button type="button" onclick="document.getElementById('tier-modal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 transition-colors border border-gray-700 hover:border-gray-600">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 border border-[#c5a059] hover:bg-[#b59048] transition-colors shadow-sm">Save Tier</button>
            </div>
        </form>
    </div>
</div>

<!-- Menu Item Modal -->
<div id="menu-modal" class="hidden fixed inset-0 z-[100] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6 overflow-y-auto">
    <div class="bg-gray-800 w-full max-w-4xl rounded-2xl p-8 border border-gray-700 shadow-2xl my-auto animate-in text-gray-300">
        <h2 id="menu-modal-title" class="text-xl font-bold text-gray-200 mb-6 border-b border-gray-700/50 pb-4">Add Menu Item</h2>
        <form onsubmit="AdminServices._saveMenuItem(event)" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <input type="hidden" id="menu-item-id">
            <div class="space-y-5">
                <div class="space-y-1.5"><label class="lbl">Name *</label><input type="text" id="menu-name" required class="inp"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5"><label class="lbl">Price (Br) *</label><input type="number" id="menu-price" required class="inp"></div>
                    <div class="space-y-1.5">
                        <label class="lbl">Main Category</label>
                        <select id="menu-main-cat" class="inp appearance-none">
                            <option value="Food">Food</option>
                            <option value="Drinks">Drinks</option>
                            <option value="Services">Services</option>
                            <option value="Other">Room Items / Other</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="lbl">Category</label>
                    <select id="menu-category" class="inp appearance-none"></select>
                </div>
                <div class="space-y-1.5"><label class="lbl">Description</label><textarea id="menu-desc" rows="3" class="inp resize-none"></textarea></div>

                <!-- NEW: Stock Linkage -->
                <div class="p-5 rounded-xl border border-gray-700 bg-gray-900 space-y-4 items-start">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Stock Linkage (Optional)</p>
                    <div class="grid grid-cols-2 gap-4 w-full">
                        <div class="space-y-1.5">
                            <label class="lbl">Link to Stock</label>
                            <select id="menu-stock-id" class="inp appearance-none text-sm">
                                <option value="">No linkage</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="lbl">Deduct per Sale</label>
                            <input type="number" id="menu-stock-consumption" step="0.01" class="inp" placeholder="1.0">
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-5">
                <!-- NEW: Reporting Config -->
                <div class="p-5 rounded-xl border border-gray-700 bg-gray-900 space-y-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Reporting Configuration</p>
                    <div class="grid grid-cols-2 gap-4 w-full">
                        <div class="space-y-1.5">
                            <label class="lbl">Report Unit</label>
                            <select id="menu-report-unit" class="inp appearance-none">
                                <option value="piece">piece</option>
                                <option value="kg">kg</option>
                                <option value="liter">liter</option>
                            </select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="lbl">Amount per Sale</label>
                            <input type="number" id="menu-report-qty" step="0.01" class="inp" placeholder="1.0">
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="lbl">Item Image</label>
                    <div id="image-preview-area" class="h-44 rounded-xl bg-gray-900 border border-dashed border-gray-600 flex flex-col items-center justify-center cursor-pointer overflow-hidden group hover:border-[#c5a059] transition-colors">
                        <i data-lucide="camera" class="w-8 h-8 text-gray-500 group-hover:text-[#c5a059] transition-colors mb-2"></i>
                        <span class="text-xs font-bold uppercase text-gray-500 group-hover:text-[#c5a059]">Click to upload</span>
                        <img id="menu-img-preview" class="hidden absolute inset-0 w-full h-full object-cover rounded-xl">
                    </div>
                    <input type="file" id="menu-img-upload" hidden accept="image/*">
                    <input type="hidden" id="menu-img-base64">
                </div>
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-700/50 mt-4">
                    <button type="button" onclick="document.getElementById('menu-modal').classList.add('hidden')" class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 transition-colors border border-gray-700 hover:border-gray-600">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 border border-[#c5a059] hover:bg-[#b59048] transition-colors shadow-sm">Publish</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Reception Detail Modal -->
<div id="rec-detail-modal" class="hidden fixed inset-0 z-[100] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-gray-800 w-full max-w-2xl rounded-2xl p-8 border border-gray-700 shadow-2xl overflow-y-auto max-h-[90vh]">
        <input type="hidden" id="rec-detail-id-hidden">
        <div class="flex justify-between items-start mb-6 border-b border-gray-700/50 pb-4">
            <div>
                <h3 id="rec-detail-name" class="text-xl font-bold text-gray-200">Guest Name</h3>
                <span id="rec-detail-status" class="text-xs font-bold uppercase tracking-wider text-gray-500"></span>
            </div>
            <button onclick="document.getElementById('rec-detail-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-200">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="rec-detail-body" class="text-gray-300 space-y-4"></div>
    </div>
</div>

<!-- Extend Stay Modal -->
<div id="rec-extend-modal" class="hidden fixed inset-0 z-[110] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-gray-800 w-full max-w-md rounded-2xl p-8 border border-gray-700 shadow-2xl animate-in">
        <h2 class="text-xl font-bold text-gray-200 mb-6 border-b border-gray-700/50 pb-4">Extend Stay</h2>
        <form onsubmit="AdminServices._submitExtension(event)" class="space-y-6">
            <input type="hidden" id="extend-request-id">
            <input type="hidden" id="extend-current-checkout">
            
            <div class="space-y-2">
                <label class="ci-label">Extra Days</label>
                <input type="number" id="extend-extra-days" required min="1" value="1" 
                    class="inp text-lg font-bold text-[#c5a059]"
                    oninput="AdminServices._updateExtendPreview()">
            </div>

            <div class="p-4 rounded-xl bg-gray-900/50 border border-gray-700/50 space-y-3">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-500 font-bold uppercase tracking-wider">Current Checkout</span>
                    <span id="extend-prev-date" class="text-gray-300 font-medium"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-[#c5a059] font-black uppercase tracking-widest">New Checkout</span>
                    <span id="extend-new-date" class="text-lg font-black text-white"></span>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="document.getElementById('rec-extend-modal').classList.add('hidden')" 
                    class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-400 hover:text-white bg-gray-800 border border-gray-700 transition-all">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 border border-[#c5a059] hover:bg-[#b59048] transition-all shadow-lg shadow-[#c5a059]/10">Confirm Extension</button>
            </div>
        </form>
    </div>
</div>

<!-- Re-check In Modal -->
<div id="rec-recheckin-modal" class="hidden fixed inset-0 z-[110] bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4 md:p-6">
    <div class="bg-gray-800 w-full max-w-4xl rounded-2xl border border-gray-700 shadow-2xl animate-in overflow-hidden flex flex-col max-h-[92vh]">
        <div class="px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-gray-700/50 shrink-0">
            <h2 class="text-xl font-bold text-gray-200">Re-check In Guest</h2>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-1">Update guest details, room, stay, and payment before checking in again</p>
        </div>

        <input type="file" id="recheckin-photo-file" accept="image/*" class="sr-only" onchange="AdminServices._rciProfileFileChange(this)">
        <input type="file" id="recheckin-id-front-file" accept="image/*" class="sr-only" onchange="AdminServices._rciIdUpload(this,'front')">
        <input type="file" id="recheckin-id-back-file" accept="image/*" class="sr-only" onchange="AdminServices._rciIdUpload(this,'back')">
        <input type="file" id="recheckin-receipt-file" accept="application/pdf,.pdf" class="sr-only" onchange="AdminServices._rciReceiptFileChange(this)">

        <form onsubmit="AdminServices._submitRecheckIn(event)" class="overflow-y-auto px-6 md:px-8 py-6 space-y-6">
            <input type="hidden" id="recheckin-request-id">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <!-- Guest information -->
                <div class="space-y-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#c5a059] flex items-center gap-2">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i> Guest Information
                    </p>

                    <div class="space-y-2">
                        <label class="ci-label">Guest Name *</label>
                        <input type="text" id="recheckin-name" required class="inp" placeholder="Guest name">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="ci-label">Fayda ID (FAN)</label>
                            <input type="text" id="recheckin-fayda" class="inp" placeholder="1283478638648345">
                        </div>
                        <div class="space-y-2">
                            <label class="ci-label">Phone</label>
                            <input type="text" id="recheckin-phone" class="inp" placeholder="+251978574875">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="ci-label">Profile Photo</label>
                        <div class="flex gap-2">
                            <input type="text" id="recheckin-photo-url" oninput="AdminServices._rciProfileUrlChange(this.value)"
                                class="inp flex-1" placeholder="Paste image URL or upload...">
                            <button type="button" onclick="document.getElementById('recheckin-photo-file').click()"
                                class="shrink-0 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-500 hover:text-white transition-colors">
                                <i data-lucide="camera" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <div id="recheckin-photo-preview" class="hidden relative w-24 h-24 mt-1">
                            <img id="recheckin-photo-img" src="" class="w-full h-full object-cover rounded-xl border border-gray-700">
                            <button type="button" onclick="AdminServices._rciClearProfile()"
                                class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full text-[10px]">✕</button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="ci-label">ID Card Photos</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="relative">
                                <div id="recheckin-id-front-placeholder" onclick="document.getElementById('recheckin-id-front-file').click()"
                                    class="h-24 bg-gray-900/50 border border-dashed border-gray-700 rounded-xl flex flex-col items-center justify-center text-gray-600 hover:text-gray-400 hover:border-[#c5a059]/30 transition-all cursor-pointer">
                                    <i data-lucide="image" class="w-4 h-4 mb-1"></i>
                                    <span class="text-[8px] font-bold uppercase tracking-widest">ID Front</span>
                                </div>
                                <div id="recheckin-id-front-preview" class="hidden h-24 rounded-xl overflow-hidden relative border border-gray-700">
                                    <img id="recheckin-id-front-img" src="" class="w-full h-full object-cover">
                                    <button type="button" onclick="AdminServices._rciClearId('front')"
                                        class="absolute top-1 right-1 w-5 h-5 bg-red-500/80 text-white rounded-full text-[10px]">✕</button>
                                </div>
                            </div>
                            <div class="relative">
                                <div id="recheckin-id-back-placeholder" onclick="document.getElementById('recheckin-id-back-file').click()"
                                    class="h-24 bg-gray-900/50 border border-dashed border-gray-700 rounded-xl flex flex-col items-center justify-center text-gray-600 hover:text-gray-400 hover:border-[#c5a059]/30 transition-all cursor-pointer">
                                    <i data-lucide="image" class="w-4 h-4 mb-1"></i>
                                    <span class="text-[8px] font-bold uppercase tracking-widest">ID Back</span>
                                </div>
                                <div id="recheckin-id-back-preview" class="hidden h-24 rounded-xl overflow-hidden relative border border-gray-700">
                                    <img id="recheckin-id-back-img" src="" class="w-full h-full object-cover">
                                    <button type="button" onclick="AdminServices._rciClearId('back')"
                                        class="absolute top-1 right-1 w-5 h-5 bg-red-500/80 text-white rounded-full text-[10px]">✕</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="ci-label">Notes</label>
                        <textarea id="recheckin-notes" rows="2" class="inp resize-none" placeholder="Additional details or remarks..."></textarea>
                    </div>
                </div>

                <!-- Stay & payment -->
                <div class="space-y-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#c5a059] flex items-center gap-2">
                        <i data-lucide="bed" class="w-3.5 h-3.5"></i> Stay & Payment
                    </p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="ci-label">Floor</label>
                            <select id="recheckin-floor" onchange="AdminServices._recheckinFloorChange(this.value)" class="inp"></select>
                        </div>
                        <div class="space-y-2">
                            <label class="ci-label">Room *</label>
                            <select id="recheckin-room" required onchange="AdminServices._updateRecheckInSummary()" class="inp"></select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="ci-label">Stay Duration (Days) *</label>
                            <input type="number" id="recheckin-duration" required min="1" value="1"
                                class="inp text-lg font-bold text-[#c5a059]"
                                oninput="AdminServices._updateRecheckInSummary()">
                        </div>
                        <div class="space-y-2">
                            <label class="ci-label">Number of Guests</label>
                            <select id="recheckin-guests" class="inp">
                                <option value="1">1 Guest</option>
                                <option value="2">2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-900/50 border border-gray-700/50 space-y-3">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-500 font-bold uppercase tracking-wider">Check In</span>
                            <span id="recheckin-checkin-date" class="text-gray-300 font-medium">Today</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-[#c5a059] font-black uppercase tracking-widest">Check Out</span>
                            <span id="recheckin-checkout-date" class="text-lg font-black text-white">—</span>
                        </div>
                        <div class="flex justify-between items-center text-xs border-t border-gray-800 pt-3">
                            <span class="text-gray-500 font-bold uppercase tracking-wider">Total</span>
                            <span id="recheckin-total" class="text-[#c5a059] font-black">0 ETB</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="ci-label">Payment Method</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex-1">
                                <input type="radio" name="recheckin-payment" value="CASH" class="pay-radio sr-only" checked onchange="AdminServices._toggleRecheckinPaymentFields()">
                                <div class="pay-btn text-[9px] py-2">CASH</div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="recheckin-payment" value="MOBILE BANKING" class="pay-radio sr-only" onchange="AdminServices._toggleRecheckinPaymentFields()">
                                <div class="pay-btn text-[9px] py-2">MOBILE BANKING</div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="recheckin-payment" value="TELEBIRR" class="pay-radio sr-only" onchange="AdminServices._toggleRecheckinPaymentFields()">
                                <div class="pay-btn text-[9px] py-2">TELEBIRR</div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="recheckin-payment" value="CHEQUE" class="pay-radio sr-only" onchange="AdminServices._toggleRecheckinPaymentFields()">
                                <div class="pay-btn text-[9px] py-2">CHEQUE</div>
                            </label>
                        </div>
                    </div>

                    <div id="recheckin-payment-extra" class="hidden space-y-4">
                        <div class="space-y-2">
                            <label class="ci-label">Transaction Number</label>
                            <input type="text" id="recheckin-transaction" class="inp" placeholder="Enter transaction number">
                        </div>
                        <div class="space-y-2">
                            <label class="ci-label">Receipt URL or Upload</label>
                            <div class="flex gap-2">
                                <input type="text" id="recheckin-receipt-url" oninput="AdminServices._rciReceiptChange(this.value)"
                                    class="inp flex-1" placeholder="https://receipt.dashensuperapp.com/...">
                                <button type="button" onclick="document.getElementById('recheckin-receipt-file').click()"
                                    class="shrink-0 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-white transition-colors">
                                    PDF
                                </button>
                            </div>
                        </div>
                        <div id="recheckin-receipt-preview" class="hidden bg-gray-900/50 rounded-xl p-3 border border-gray-700 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <p id="recheckin-receipt-filename" class="text-xs font-bold text-white truncate">Receipt</p>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" onclick="AdminServices.openReceiptFull(document.getElementById('recheckin-receipt-url')?.value)"
                                        class="px-2 py-1 bg-[#c5a059]/10 border border-[#c5a059]/30 rounded text-[9px] font-black uppercase text-[#c5a059]">Open</button>
                                    <button type="button" onclick="AdminServices._rciClearReceipt()"
                                        class="w-6 h-6 rounded bg-red-500/10 border border-red-500/20 text-red-500 text-[10px]">✕</button>
                                </div>
                            </div>
                            <div id="recheckin-receipt-embed-wrap" class="hidden rounded-lg overflow-hidden border border-gray-700 bg-white h-40">
                                <iframe id="recheckin-receipt-iframe" class="w-full h-full bg-white pointer-events-none" title="Receipt preview" src="about:blank"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-700/50">
                <button type="button" onclick="document.getElementById('rec-recheckin-modal').classList.add('hidden')"
                    class="px-5 py-2.5 rounded-lg text-sm font-bold text-gray-400 hover:text-white bg-gray-800 border border-gray-700 transition-all">Cancel</button>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 border border-[#c5a059] hover:bg-[#b59048] transition-all shadow-lg shadow-[#c5a059]/10">Confirm Check-in</button>
            </div>
        </form>
    </div>
</div>

<!-- Receipt Full Preview Modal -->
<div id="receipt-full-modal" class="hidden fixed inset-0 z-[120] bg-gray-900/95 backdrop-blur-sm flex flex-col p-4 md:p-6">
    <div class="flex items-center justify-between gap-4 mb-4 shrink-0">
        <div class="min-w-0">
            <h3 id="receipt-full-title" class="text-lg font-bold text-gray-200 truncate">Receipt Preview</h3>
            <p class="text-[10px] uppercase font-bold tracking-widest text-gray-500 mt-1">Full page view</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a id="receipt-full-external" href="#" target="_blank" rel="noopener"
                class="px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg text-[10px] font-black uppercase tracking-widest text-[#c5a059] hover:text-white transition-colors">
                Open in Tab
            </a>
            <button type="button" onclick="AdminServices.closeReceiptFull()"
                class="w-10 h-10 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 hover:text-white transition-colors flex items-center justify-center">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
    </div>
    <div id="receipt-full-embed-wrap" class="flex-1 min-h-0 rounded-2xl overflow-hidden border border-gray-700 bg-white shadow-2xl">
        <iframe id="receipt-full-iframe" class="w-full h-full" title="Receipt full preview" src="about:blank"></iframe>
    </div>
    <div id="receipt-full-fallback" class="hidden flex-1 min-h-0 rounded-2xl border border-gray-700 bg-gray-800 shadow-2xl flex flex-col items-center justify-center text-center p-8">
        <div class="w-16 h-16 rounded-2xl bg-[#c5a059]/10 border border-[#c5a059]/30 flex items-center justify-center text-[#c5a059] mb-6">
            <i data-lucide="external-link" class="w-8 h-8"></i>
        </div>
        <p class="text-sm font-bold text-gray-200 mb-2">Receipt opens in a new browser tab</p>
        <p class="text-xs text-gray-500 max-w-md mb-6">Banking receipt pages cannot be embedded here. Use the button below to view the full receipt page.</p>
        <a id="receipt-full-fallback-link" href="#" target="_blank" rel="noopener"
            class="px-6 py-3 bg-[#c5a059] text-gray-900 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-[#b59048] transition-colors">Open Receipt Page</a>
    </div>
</div>

<!-- QR Modal -->
<div id="qr-modal" class="hidden fixed inset-0 z-[110] bg-gray-900/90 backdrop-blur-sm flex items-center justify-center p-6 text-gray-300 text-center">
    <div class="bg-gray-800 p-8 rounded-2xl border border-gray-700 shadow-2xl space-y-6 max-w-sm mx-auto">
        <h3 id="qr-room-title" class="text-xl font-bold uppercase text-gray-200">Room QR Code</h3>
        <div id="qr-content" class="bg-white p-4 rounded-xl mx-auto shadow-sm w-[232px] h-[232px] flex items-center justify-center"></div>
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Scan for Digital Room Service</p>
        <div class="flex gap-3 pt-4 border-t border-gray-700/50">
            <button onclick="document.getElementById('qr-modal').classList.add('hidden')" class="flex-1 py-3 text-sm font-bold bg-transparent border border-gray-600 rounded-lg text-gray-400 hover:text-white transition-colors">Close</button>
            <button onclick="window.print()" class="flex-1 py-3 rounded-lg text-sm font-bold bg-[#c5a059] text-gray-900 transition-colors shadow-sm">Print</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ SHARED CSS ═══ -->
<style>
<style>
    .lbl { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; margin-bottom:0.375rem; }
    .inp { width:100%; background:rgb(31 41 55 / 0.5); border:1px solid rgb(55 65 81); border-radius:0.5rem; padding:0.625rem 0.875rem; font-size:0.875rem; color:#f3f4f6; outline:none; transition: all 0.2s; }
    .inp:focus { border-color:#c5a059; background:rgb(31 41 55); }
    #image-preview-area { position: relative; }
    #image-preview-area { position: relative; }
</style>

<!-- ═══════════════════════════════════════════════════════ SCRIPTS ═══ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="public/js/menu-manager.js?v=<?php echo time(); ?>"></script>
<script src="public/js/admin-services.js?v=<?php echo time(); ?>"></script>
<script>
// ── Room Modal Integration ───────────────────────────────────────────────────
AdminServices.openRoomModal = (room = null) => {
    const floorSel = document.getElementById('room-floor');
    floorSel.innerHTML = '<option value="">Select floor…</option>' +
        AdminServices.floors.map(f => `<option value="${f.id}"${room && room.floorId === f.id ? ' selected' : ''}>Floor ${f.floorNumber}</option>`).join('');

    if (room && room.id) {
        document.getElementById('room-modal-title').textContent = 'Edit Room ' + room.roomNumber;
        document.getElementById('room-id').value = room.id;
        document.getElementById('room-number').value = room.roomNumber || '';
        document.getElementById('room-type').value = room.type || 'standard';
        document.getElementById('room-category').value = room.category || '';
        document.getElementById('room-price').value = room.price || '';
        document.getElementById('room-status').value = room.status || 'available';
        document.querySelectorAll('[name="room-tier"]').forEach(el => el.checked = (el.value === (room.roomServiceMenuTier || 'standard')));
    } else {
        document.getElementById('room-modal-title').textContent = 'Add New Room';
        document.getElementById('room-id').value = '';
        ['room-number','room-category','room-price'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('room-type').value = 'standard';
        document.getElementById('room-status').value = 'available';
        document.querySelector('[name="room-tier"][value="standard"]').checked = true;
    }
    document.getElementById('room-modal').classList.remove('hidden');
};

AdminServices._saveRoom = async (e) => {
    e.preventDefault();
    const id = document.getElementById('room-id').value;
    const tier = document.querySelector('[name="room-tier"]:checked')?.value || 'standard';
    const payload = {
        roomNumber: document.getElementById('room-number').value,
        floorId: document.getElementById('room-floor').value,
        type: document.getElementById('room-type').value,
        category: document.getElementById('room-category').value,
        price: parseFloat(document.getElementById('room-price').value),
        status: document.getElementById('room-status').value,
        roomServiceMenuTier: tier
    };
    const method = id ? 'PUT' : 'POST';
    const url = id ? `api/admin/rooms.php?id=${id}` : 'api/admin/rooms.php';
    await AdminServices.api(method, url, payload);
    document.getElementById('room-modal').classList.add('hidden');
    AdminServices.fetchRoomsData();
};

// ── QR Modal ─────────────────────────────────────────────────────────────────
AdminServices.openQRModal = (roomNumber) => {
    document.getElementById('qr-content').innerHTML = '';
    new QRCode(document.getElementById('qr-content'), {
        text: `${location.origin}/room-service/${roomNumber}`,
        width: 200, height: 200, correctLevel: QRCode.CorrectLevel.H
    });
    document.getElementById('qr-room-title').textContent = `Room ${roomNumber} — QR Link`;
    document.getElementById('qr-modal').classList.remove('hidden');
};

AdminServices.openMenuQRModal = (url) => {
    document.getElementById('qr-content').innerHTML = '';
    new QRCode(document.getElementById('qr-content'), {
        text: url,
        width: 200, height: 200, correctLevel: QRCode.CorrectLevel.H
    });
    document.getElementById('qr-room-title').textContent = `Digital Menu — QR Link`;
    document.querySelector('#qr-modal p').textContent = 'Scan for Digital Menu View';
    document.getElementById('qr-modal').classList.remove('hidden');
};

// ── Menu Modal ───────────────────────────────────────────────────────────────
AdminServices.openMenuModal = (item = {}) => {
    const mm = AdminServices.menuManager;
    const catSel = document.getElementById('menu-category');
    catSel.innerHTML = (mm ? mm.state.categories : []).map(c => `<option value="${c.name}">${c.name}</option>`).join('');

    document.getElementById('menu-item-id').value = item.id || '';
    document.getElementById('menu-name').value = item.name || '';
    document.getElementById('menu-price').value = item.price || '';
    document.getElementById('menu-main-cat').value = item.mainCategory || 'Food';
    document.getElementById('menu-category').value = item.category || '';
    document.getElementById('menu-desc').value = item.description || '';
    document.getElementById('menu-img-base64').value = item.image || '';
    document.getElementById('menu-stock-id').value = item.stockItemId || '';
    document.getElementById('menu-stock-consumption').value = item.stockConsumption || '';
    document.getElementById('menu-report-unit').value = item.reportUnit || 'piece';
    document.getElementById('menu-report-qty').value = item.reportQuantity || '';

    // Load available stocks for the dropdown
    AdminServices.fetchActiveStocks();

    const prev = document.getElementById('menu-img-preview');
    if (item.image) { 
        prev.src = item.image; 
        prev.classList.remove('hidden'); 
    } else if (item.id) {
        // Use the image API if id is present and no base64
        const mm = AdminServices.menuManager;
        const col = mm ? mm.config.collection : 'menuItems';
        prev.src = `api/cashier/image.php?id=${encodeURIComponent(item.id)}&collection=${encodeURIComponent(col)}&t=${Date.now()}`;
        prev.classList.remove('hidden');
    } else {
        prev.classList.add('hidden');
    }

    document.getElementById('menu-modal-title').textContent = item.id ? 'Edit Item' : 'Add Menu Item';
    document.getElementById('menu-modal').classList.remove('hidden');
};

AdminServices._saveMenuItem = async (e) => {
    e.preventDefault();
    const id = document.getElementById('menu-item-id').value;
    const method = id ? 'PUT' : 'POST';
    const mm = AdminServices.menuManager;
    const url = id
        ? `api/admin/menu.php?id=${id}&collection=${mm?.config.collection || 'menuItems'}`
        : `api/admin/menu.php?collection=${mm?.config.collection || 'menuItems'}`;
    const imgVal = document.getElementById('menu-img-base64').value;
    const payload = {
        name: document.getElementById('menu-name').value,
        price: parseFloat(document.getElementById('menu-price').value),
        mainCategory: document.getElementById('menu-main-cat').value,
        category: document.getElementById('menu-category').value,
        description: document.getElementById('menu-desc').value,
        stockItemId: document.getElementById('menu-stock-id').value,
        stockConsumption: parseFloat(document.getElementById('menu-stock-consumption').value || 0),
        reportUnit: document.getElementById('menu-report-unit').value,
        reportQuantity: parseFloat(document.getElementById('menu-report-qty').value || 1)
    };
    if (imgVal) payload.image = imgVal;
    await AdminServices.api(method, url, payload);
    document.getElementById('menu-modal').classList.add('hidden');
    if (mm) { await mm.loadData(); mm.render(); }
};

AdminServices.fetchActiveStocks = async () => {
    try {
        const stocks = await AdminServices.api('GET', 'api/stock.php?availableOnly=true');
        const sel = document.getElementById('menu-stock-id');
        const currentVal = sel.value;
        sel.innerHTML = '<option value="">No linkage</option>' + 
            stocks.map(s => `<option value="${s.id}">${s.name} (${s.quantity} ${s.unit})</option>`).join('');
        if (currentVal) sel.value = currentVal;
    } catch(e) {}
};

// ── Image upload (canvas compress) ───────────────────────────────────────────
document.getElementById('image-preview-area').addEventListener('click', () =>
    document.getElementById('menu-img-upload').click());
document.getElementById('menu-img-upload').addEventListener('change', (e) => {
    const file = e.target.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
        const img = new Image();
        img.onload = () => {
            const MAX = 800;
            let w = img.width, h = img.height;
            if (w > h && w > MAX) { h = h * MAX/w; w = MAX; }
            else if (h > MAX) { w = w * MAX/h; h = MAX; }
            const c = document.createElement('canvas');
            c.width = w; c.height = h;
            c.getContext('2d').drawImage(img, 0, 0, w, h);
            const b64 = c.toDataURL('image/jpeg', 0.8);
            document.getElementById('menu-img-base64').value = b64;
            const prev = document.getElementById('menu-img-preview');
            prev.src = b64; prev.classList.remove('hidden');
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
});
</script>

<?php renderFooter(); ?>
