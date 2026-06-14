<?php
// settings.php
/**
 * Admin Settings Hub — COMPLETE Luxury UI Reproduction (Merged Branding, Categories, Tables)
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/SettingsManager.php';

// Auth: admin or specifically permitted
requireAuth(['admin'], 'settings:view');

$manager = new SettingsManager();
$settings = $manager->getAllSettings();

// Fetch all data
$menuCategories = $manager->getCategories('menu');
$stockCategories = $manager->getCategories('stock');
$distributionCategories = $manager->getCategories('distribution');
$tables = $manager->getTables();
$floors = $manager->getFloors();

// Extract branding and config (includes legacy migration)
$branding = $manager->getBranding();
$config = $settings['configuration'] ?? [];

renderHeader("Settings");
?>

<div class="max-w-screen-2xl w-full flex flex-col min-h-[calc(100vh-theme(space.4))] bg-[#0f1110] rounded-2xl mt-2 mb-2 lg:ml-2 overflow-y-auto">

    <!-- Header -->
    <div class="px-8 pt-8 pb-6 border-b border-gray-700/50 bg-gray-800/80 shrink-0 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-200 tracking-tight">Settings</h1>
            <p class="text-xs uppercase font-semibold tracking-wider text-gray-500 mt-1">System Configuration</p>
        </div>
        <a href="https://s16387.fra1.stableserver.net:2096/cpsess6687300317/3rdparty/roundcube/?_task=mail&_mbox=INBOX" 
           target="_blank" 
           class="flex items-center gap-2 px-4 py-2.5 bg-gray-700 hover:bg-gray-600 text-gray-200 text-xs font-bold uppercase tracking-wider rounded-lg transition-colors border border-gray-600">
            <i data-lucide="mail" class="w-4 h-4 text-[#c5a059]"></i>
            Check Email
        </a>
    </div>

    <div class="p-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <!-- LEFT SIDEBAR -->
            <aside class="lg:col-span-3 space-y-5 lg:sticky lg:top-8 h-fit">

                <!-- Current Logo Card -->
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-6 text-center relative overflow-hidden group">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-4">Logo Preview</p>
                    <div class="w-20 h-20 mx-auto rounded-full overflow-hidden border border-gray-600 bg-gray-900 transition-transform duration-500 group-hover:scale-105">
                        <img id="sidebarLogoPreview" src="<?php echo htmlspecialchars($branding['logo_url'] ?? ''); ?>"
                             class="w-full h-full object-cover" alt="Logo">
                    </div>
                    <h3 id="sidebarAppName" class="text-base font-bold text-gray-200 mt-3"><?php echo htmlspecialchars($branding['app_name'] ?? 'ABE HOTEL'); ?></h3>
                    <p id="sidebarAppTagline" class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($branding['app_tagline'] ?? 'HOTEL MANAGEMENT SYSTEM'); ?></p>
                </div>

                <!-- Nav Preview -->
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Preview in Navigation</p>
                    <div class="flex items-center gap-3 bg-gray-900 rounded-lg p-3">
                        <div class="w-8 h-8 rounded-full overflow-hidden bg-gray-700 border border-gray-600 flex-shrink-0">
                            <img id="navLogoPreview" src="<?php echo htmlspecialchars($branding['logo_url'] ?? ''); ?>" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p id="navAppName" class="text-xs font-bold text-gray-200 leading-none"><?php echo htmlspecialchars($branding['app_name'] ?? 'ABE HOTEL'); ?></p>
                            <p class="text-xs text-gray-600 mt-0.5">Navbar</p>
                        </div>
                    </div>
                </div>

                <!-- Browser Tab Preview -->
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Browser Tab</p>
                    <div class="bg-gray-900 rounded-t-lg py-2.5 px-4 border border-gray-700 flex items-center gap-2">
                        <img id="tabFaviconPreview" src="<?php echo htmlspecialchars($branding['favicon_url'] ?? ($branding['logo_url'] ?? '')); ?>" class="w-4 h-4 object-contain">
                        <span id="tabAppName" class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($branding['app_name'] ?? 'Hotel Management'); ?></span>
                    </div>
                </div>

                <!-- Admin Mailbox -->
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-5 space-y-3">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#c5a059]">
                        <i data-lucide="mail" class="w-4 h-4"></i> Admin Mailbox
                    </h3>
                    <p class="text-[10px] text-gray-500 leading-relaxed">Access the official hotel webmail system via Roundcube.</p>
                    <a href="https://s16387.fra1.stableserver.net:2096/cpsess6687300317/3rdparty/roundcube/?_task=mail&_mbox=INBOX" 
                       target="_blank"
                       class="flex items-center justify-center gap-2 w-full py-2 bg-gray-900 border border-gray-700 rounded-lg text-[10px] font-bold uppercase text-gray-400 hover:text-[#c5a059] hover:border-[#c5a059]/50 transition-all">
                        <i data-lucide="external-link" class="w-3 h-3"></i> Open Webmail
                    </a>
                </div>

                <!-- Logo Tips -->
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl p-5 space-y-3">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#c5a059]">
                        <i data-lucide="help-circle" class="w-4 h-4"></i> Logo Tips
                    </h3>
                    <ul class="space-y-3">
                        <?php foreach (['Use square images (1:1 ratio)', 'Minimum 200×200 pixels', 'PNG, JPG, GIF, or WebP format', 'Max upload size: 5MB'] as $i => $tip): ?>
                        <li class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full border border-gray-600 flex items-center justify-center text-xs font-bold text-gray-500 shrink-0"><?php echo str_pad($i+1, 2, '0', STR_PAD_LEFT); ?></div>
                            <span class="text-xs text-gray-400 leading-tight"><?php echo $tip; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <!-- RIGHT PANEL -->
            <main class="lg:col-span-9">
                <div class="bg-gray-800 border border-gray-700/50 rounded-xl overflow-hidden">

                    <div id="alert" class="alert hidden p-4 text-sm font-bold text-center"></div>

                    <!-- Tab Nav -->
                    <div class="flex gap-1 border-b border-gray-700/50 px-6 pt-5 overflow-x-auto no-scrollbar">
                        <button onclick="AdminSettings.switchTab('branding')" id="tab-branding"
                            class="tab-btn px-4 py-2.5 text-xs font-bold uppercase tracking-wider border-b-2 border-[#c5a059] text-[#c5a059] transition-all">Branding</button>
                        <button onclick="AdminSettings.switchTab('categories')" id="tab-categories"
                            class="tab-btn px-4 py-2.5 text-xs font-bold uppercase tracking-wider border-b-2 border-transparent text-gray-500 hover:text-gray-200 transition-all">Categories</button>
                        <button onclick="AdminSettings.switchTab('tables')" id="tab-tables"
                            class="tab-btn px-4 py-2.5 text-xs font-bold uppercase tracking-wider border-b-2 border-transparent text-gray-500 hover:text-gray-200 transition-all">Tables & Floors</button>
                    </div>

                    <!-- TAB: Branding -->
                    <div id="branding-section" class="tab-content p-8 space-y-8">

                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-200">System Branding</h3>
                            <button onclick="AdminSettings.saveAll()" class="flex items-center gap-2 px-5 py-2.5 bg-[#c5a059] text-gray-900 text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-[#b59048] transition-colors shadow-sm">
                                <i data-lucide="save" class="w-4 h-4"></i> Save Branding
                            </button>
                        </div>

                        <!-- Logo Upload -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-400">Logo Upload</label>
                                <div class="flex p-1 bg-gray-900 rounded-lg border border-gray-700 gap-1">
                                    <button onclick="AdminSettings.setUploadMode('url')" id="mode-url"
                                        class="px-4 py-1.5 rounded-md text-xs font-bold uppercase transition-all bg-[#c5a059] text-gray-900 shadow">
                                        <i data-lucide="link-2" class="w-3 h-3 inline-block mr-1"></i> URL
                                    </button>
                                    <button onclick="AdminSettings.setUploadMode('file')" id="mode-file"
                                        class="px-4 py-1.5 rounded-md text-xs font-bold uppercase transition-all text-gray-500 hover:text-gray-200">
                                        <i data-lucide="file-up" class="w-3 h-3 inline-block mr-1"></i> Upload
                                    </button>
                                </div>
                            </div>
                            <div id="url-input-container" class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Logo Image URL</label>
                                    <input type="url" id="logoUrlInput" value="<?php echo htmlspecialchars($branding['logo_url'] ?? ''); ?>"
                                        placeholder="Enter image URL..."
                                        oninput="AdminSettings.updatePreviews(this.value)"
                                        class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059] transition-colors">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-500">Favicon URL (Tab Icon)</label>
                                    <div class="flex gap-4">
                                        <div class="w-11 h-11 rounded-lg border border-gray-700 bg-gray-900 flex items-center justify-center overflow-hidden shrink-0">
                                            <img id="faviconPreview" src="<?php echo htmlspecialchars($branding['favicon_url'] ?? ($branding['logo_url'] ?? '')); ?>"
                                                class="w-6 h-6 object-contain"
                                                onerror="this.src='/assets/favicon.ico'">
                                        </div>
                                        <input type="url" id="faviconUrlInput" value="<?php echo htmlspecialchars($branding['favicon_url'] ?? ($branding['logo_url'] ?? '')); ?>"
                                            placeholder="Enter favicon URL..."
                                            oninput="document.getElementById('faviconPreview').src = this.value"
                                            class="flex-1 bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059] transition-colors">
                                    </div>
                                </div>
                            </div>
                            <div id="file-input-container" class="hidden relative group">
                                <div id="logo-drop-zone" onclick="document.getElementById('logoFileInput').click()"
                                    class="border-2 border-dashed border-gray-600 rounded-xl p-10 text-center hover:border-[#c5a059] transition-all cursor-pointer">
                                    <i data-lucide="upload-cloud" class="w-8 h-8 mx-auto text-gray-500 group-hover:text-[#c5a059] transition-colors mb-3"></i>
                                    <p id="logo-upload-label" class="text-xs font-bold uppercase text-gray-500 group-hover:text-gray-200 transition-colors">Drop logo or click to upload</p>
                                    <p class="text-[10px] text-gray-600 mt-2">JPG, PNG, WebP, GIF · Max 5MB</p>
                                    <input type="file" id="logoFileInput" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="AdminSettings.handleFileUpload(event)">
                                </div>
                            </div>
                        </div>

                        <!-- App Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-400">Application Name</label>
                                <input type="text" id="appNameInput" value="<?php echo htmlspecialchars($branding['app_name'] ?? ''); ?>"
                                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059] transition-colors">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-400">Application Tagline</label>
                                <input type="text" id="appTaglineInput" value="<?php echo htmlspecialchars($branding['app_tagline'] ?? ''); ?>"
                                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059] transition-colors">
                            </div>
                        </div>

                        <!-- VAT & Config -->
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-400">Taxation (VAT) Rate</label>
                                <div class="flex items-center gap-4">
                                    <input type="number" id="vatRateInput" step="0.01" value="<?php echo htmlspecialchars($config['vat_rate'] ?? 0.15); ?>"
                                        class="flex-1 bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-sm font-bold text-[#c5a059] focus:outline-none focus:border-[#c5a059] transition-colors text-center">
                                    <div class="w-20 h-11 bg-gray-900 border border-gray-700 rounded-lg flex items-center justify-center text-base font-bold text-[#c5a059]">
                                        <span id="vatDisplay"><?php echo (int)(($config['vat_rate'] ?? 0.15) * 100); ?>%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="p-5 bg-gray-900 border border-gray-700/50 rounded-xl flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-bold text-gray-200">Cashier Printing</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Auto-print after checkout</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="printToggle" class="sr-only peer" <?php echo ($config['enable_cashier_printing'] ? 'checked' : ''); ?>>
                                        <div class="w-11 h-6 bg-gray-700 border border-gray-600 rounded-full peer-checked:bg-emerald-600/30 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-gray-400 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5 peer-checked:after:bg-emerald-400"></div>
                                    </label>
                                </div>
                                <div class="p-5 bg-gray-900 border border-gray-700/50 rounded-xl flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-bold text-gray-200">Revenue Visibility</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Allow cashiers to see totals</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="revenueToggle" class="sr-only peer" <?php echo ($config['enable_cashier_today_revenue'] ? 'checked' : ''); ?>>
                                        <div class="w-11 h-6 bg-gray-700 border border-gray-600 rounded-full peer-checked:bg-emerald-600/30 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-gray-400 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5 peer-checked:after:bg-emerald-400"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB: Categories -->
                    <div id="categories-section" class="tab-content hidden p-8 space-y-6">

                        <!-- Sub-tabs -->
                        <div class="flex p-1 bg-gray-900 rounded-lg border border-gray-700 gap-1">
                            <button onclick="AdminSettings.switchCategoryType('menu')" id="btn-cat-menu"
                                class="flex-1 py-2.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all bg-[#c5a059] text-gray-900 shadow">
                                <i data-lucide="book-open" class="w-3.5 h-3.5 inline-block mr-1.5"></i> Menu
                            </button>
                            <button onclick="AdminSettings.switchCategoryType('stock')" id="btn-cat-stoc"
                                class="flex-1 py-2.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all text-gray-500 hover:text-gray-200">
                                <i data-lucide="package" class="w-3.5 h-3.5 inline-block mr-1.5"></i> Stock
                            </button>
                            <button onclick="AdminSettings.switchCategoryType('distribution')" id="btn-cat-dist"
                                class="flex-1 py-2.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all text-gray-500 hover:text-gray-200">
                                <i data-lucide="truck" class="w-3.5 h-3.5 inline-block mr-1.5"></i> Distribution
                            </button>
                        </div>

                        <!-- Add form -->
                        <div class="p-5 bg-gray-900 border border-gray-700/50 rounded-xl space-y-3">
                            <h4 id="cat-form-title" class="text-xs font-bold uppercase tracking-wider text-gray-400">Add New Menu Category</h4>
                            <div class="flex gap-3">
                                <input type="text" id="categoryNameInput" placeholder="Category name..."
                                    class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059] transition-colors">
                                <button onclick="AdminSettings.addCategory()"
                                    class="px-5 py-2.5 bg-[#c5a059] text-gray-900 text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-[#b59048] transition-colors shadow-sm whitespace-nowrap">
                                    Add Category
                                </button>
                            </div>
                        </div>

                        <div id="categoriesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"></div>
                    </div>

                    <!-- TAB: Tables & Floors -->
                    <div id="tables-section" class="tab-content hidden p-8 space-y-8">

                        <!-- Floors -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Manage Floors</h3>
                            <div class="flex gap-3 p-5 bg-gray-900 border border-gray-700/50 rounded-xl">
                                <input type="text" id="floorNumberInput" placeholder="Floor Number (e.g. #1)"
                                    class="flex-1 bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059] transition-colors">
                                <input type="number" id="floorOrderInput" placeholder="Order"
                                    class="w-24 bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-sm text-center text-gray-200 focus:outline-none focus:border-[#c5a059]">
                                <button onclick="AdminSettings.addFloor()"
                                    class="px-5 py-2.5 bg-[#c5a059] text-gray-900 text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-[#b59048] shadow-sm whitespace-nowrap transition-colors">Add Floor</button>
                            </div>
                            <div id="floorsGrid" class="flex flex-wrap gap-3"></div>
                        </div>

                        <!-- Tables -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">Add New Table</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-5 bg-gray-900 border border-gray-700/50 rounded-xl">
                                <input type="text" id="tableNumberInput" placeholder="Number (e.g. T-01)"
                                    class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059] transition-colors">
                                <input type="number" id="tableCapacityInput" placeholder="Seats"
                                    class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-sm text-center text-gray-200 focus:outline-none focus:border-[#c5a059]">
                                <button onclick="AdminSettings.addTable()"
                                    class="bg-[#c5a059] text-gray-900 text-xs font-bold uppercase tracking-wider rounded-lg hover:bg-[#b59048] shadow-sm transition-colors py-2.5">Add Table</button>
                            </div>
                        </div>

                        <!-- Table Grid -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">All Tables</h3>
                                <span id="tablesCounter" class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-xs font-bold text-emerald-400 uppercase">0 Total</span>
                            </div>
                            <div id="tablesGrid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4"></div>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- UNIVERSAL EDIT MODAL -->
<div id="editModal" class="hidden fixed inset-0 z-[500] flex items-center justify-center p-6 lg:p-12">
    <div onclick="AdminSettings.closeEditModal()" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-md glass p-8 rounded-2xl border border-gray-700 bg-gray-900 shadow-2xl animate-fadeIn">
        <div class="flex items-center justify-between mb-8">
            <h3 id="editModalTitle" class="text-xl font-bold text-white tracking-tight">Edit Entity</h3>
            <button onclick="AdminSettings.closeEditModal()" class="text-gray-500 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="editModalContent" class="space-y-6">
            <!-- Dynamic fields go here -->
        </div>
        <div class="mt-8 pt-6 border-t border-gray-700/50 flex gap-3">
            <button onclick="AdminSettings.closeEditModal()" class="flex-1 py-3 rounded-xl bg-gray-800 text-gray-400 text-xs font-bold uppercase tracking-widest hover:bg-gray-750 transition-all">Cancel</button>
            <button onclick="AdminSettings.saveEdit()" class="flex-1 py-3 rounded-xl bg-[#c5a059] text-gray-900 text-xs font-bold uppercase tracking-widest hover:bg-[#b59048] transition-all shadow-lg">Save Changes</button>
        </div>
    </div>
</div>


<style>
.alert-success { background: rgba(74,222,128,.1); color: #4ade80; border: 1px solid rgba(74,222,128,.2); border-radius: 0.5rem; }
.alert-error { background: rgba(239,68,68,.1); color: #ef4444; border: 1px solid rgba(239,68,68,.2); border-radius: 0.5rem; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.4s cubic-bezier(.16,1,.3,1) forwards; }
.category-card, .table-card { transition: all .3s; }
.category-card:hover { border-color: rgba(197,160,89,.3) !important; }
</style>

<script>
const AdminSettings = {
    state: {
        activeTab: 'branding',
        activeCategoryType: 'menu',
        uploadMode: 'url',
        data: {
            categories: {
                menu: <?php echo json_encode($menuCategories); ?>,
                stock: <?php echo json_encode($stockCategories); ?>,
                distribution: <?php echo json_encode($distributionCategories); ?>
            },
            floors: <?php echo json_encode($floors); ?>,
            tables: <?php echo json_encode($tables); ?>
        },
        editingItem: null,
        editingType: null // 'category', 'floor', 'table'
    },

    switchTab(tab) {
        this.state.activeTab = tab;
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('text-[#c5a059]', 'border-[#c5a059]');
            b.classList.add('text-gray-500', 'border-transparent');
        });
        const btn = document.getElementById(`tab-${tab}`);
        if (btn) {
            btn.classList.remove('text-gray-500', 'border-transparent');
            btn.classList.add('text-[#c5a059]', 'border-[#c5a059]');
        }
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        const section = document.getElementById(`${tab}-section`);
        if (section) section.classList.remove('hidden');

        if (tab === 'categories') this.renderCategories();
        if (tab === 'tables') this.renderTables();
    },

    setUploadMode(mode) {
        this.state.uploadMode = mode;
        const btnUrl  = document.getElementById('mode-url');
        const btnFile = document.getElementById('mode-file');
        const urlIn   = document.getElementById('url-input-container');
        const fileIn  = document.getElementById('file-input-container');
        const on  = 'px-4 py-1.5 rounded-md text-xs font-bold uppercase transition-all bg-[#c5a059] text-gray-900 shadow';
        const off = 'px-4 py-1.5 rounded-md text-xs font-bold uppercase transition-all text-gray-500 hover:text-gray-200';
        if (mode === 'url') {
            btnUrl.className = on; btnFile.className = off;
            urlIn.classList.remove('hidden'); fileIn.classList.add('hidden');
        } else {
            btnFile.className = on; btnUrl.className = off;
            urlIn.classList.add('hidden'); fileIn.classList.remove('hidden');
        }
    },

    async handleFileUpload(e) {
        const file = (e.target && e.target.files && e.target.files[0]) || (e.dataTransfer && e.dataTransfer.files[0]);
        if (!file) return;
        await this.processLogoFile(file);
        if (e.target) e.target.value = '';
    },

    async processLogoFile(file) {
        if (!file.type.startsWith('image/')) {
            this.showAlert('Please choose an image file', 'error');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            this.showAlert('File too large (max 5MB)', 'error');
            return;
        }

        const label = document.getElementById('logo-upload-label');
        if (label) label.textContent = 'Uploading...';

        try {
            const logo = await this.compressImageFile(file, 200);
            const favicon = await this.compressImageFile(file, 64);

            await this.putSetting('logo_url', logo);
            await this.putSetting('favicon_url', favicon);

            this.updatePreviews(logo, favicon);
            this.showAlert('Logo and favicon uploaded successfully');
        } catch (err) {
            this.showAlert(err.message || 'Upload failed', 'error');
        } finally {
            if (label) label.textContent = 'Drop logo or click to upload';
        }
    },

    compressImageFile(file, size) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = size;
                    canvas.height = size;
                    const ctx = canvas.getContext('2d');
                    const scale = Math.max(size / img.width, size / img.height);
                    const w = img.width * scale;
                    const h = img.height * scale;
                    ctx.drawImage(img, (size - w) / 2, (size - h) / 2, w, h);
                    resolve(canvas.toDataURL('image/jpeg', 0.9));
                };
                img.onerror = () => reject(new Error('Could not read image file'));
                img.src = reader.result;
            };
            reader.onerror = () => reject(new Error('Could not read file'));
            reader.readAsDataURL(file);
        });
    },

    async putSetting(key, value) {
        const res = await fetch('api/admin/settings.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ key, value, type: 'string' })
        });
        const text = await res.text();
        let data = {};
        try { data = JSON.parse(text); } catch {
            throw new Error('Server returned an invalid response. Please try again.');
        }
        if (!res.ok) throw new Error(data.message || `Failed to save ${key}`);
        return data;
    },

    updatePreviews(url, faviconUrl = null) {
        const fav = faviconUrl || document.getElementById('faviconUrlInput')?.value || url;
        ['sidebarLogoPreview', 'navLogoPreview', 'logoUrlInput'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (el.tagName === 'INPUT') el.value = url;
                else el.src = url;
            }
        });
        const favEl = document.getElementById('faviconPreview');
        const favInput = document.getElementById('faviconUrlInput');
        if (favEl) favEl.src = fav;
        if (favInput) favInput.value = fav;
    },

    // --- CATEGORIES ---
    switchCategoryType(type) {
        this.state.activeCategoryType = type;
        const keys = ['menu','stock','distribution'];
        const mapId = { menu: 'menu', stock: 'stoc', distribution: 'dist' };
        const on  = 'flex-1 py-2.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all bg-[#c5a059] text-gray-900 shadow';
        const off = 'flex-1 py-2.5 rounded-md text-xs font-bold uppercase tracking-wider transition-all text-gray-500 hover:text-gray-200';
        keys.forEach(t => {
            const btn = document.getElementById(`btn-cat-${mapId[t]}`);
            if (btn) btn.className = t === type ? on : off;
        });
        document.getElementById('cat-form-title').textContent = `Add New ${type.charAt(0).toUpperCase() + type.slice(1)} Category`;
        this.renderCategories();
    },

    renderCategories() {
        const grid  = document.getElementById('categoriesGrid');
        const items = this.state.data.categories[this.state.activeCategoryType];
        const typeLabel = this.state.activeCategoryType.charAt(0).toUpperCase() + this.state.activeCategoryType.slice(1);
        grid.innerHTML = items.map(cat => `
            <div class="category-card bg-gray-900 border border-gray-700/50 rounded-xl p-5 relative overflow-hidden group hover:border-gray-600">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h5 class="text-sm font-bold text-gray-200 truncate">${cat.name}</h5>
                        <span class="mt-1.5 inline-block px-2.5 py-0.5 bg-gray-800 border border-gray-700 rounded-lg text-xs font-bold uppercase text-gray-500">${typeLabel}</span>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <button onclick="AdminSettings.deleteCategory('${cat.id}')" class="w-8 h-8 rounded-lg bg-gray-800 border border-gray-700 flex items-center justify-center text-gray-500 hover:text-red-400 hover:border-red-500/30 transition-all">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                        <button onclick="AdminSettings.openEditModal('category', '${cat.id}')" class="w-8 h-8 rounded-lg bg-[#c5a059]/10 border border-[#c5a059]/20 flex items-center justify-center text-[#c5a059] hover:bg-[#c5a059] hover:text-gray-900 transition-all">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
        lucide.createIcons();
    },

    async addCategory() {
        const name = document.getElementById('categoryNameInput').value;
        const type = this.state.activeCategoryType;
        if(!name) return;
        try {
            const res = await fetch(`api/admin/categories.php?type=${type}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });
            const newCat = await res.json();
            this.state.data.categories[type].unshift(newCat);
            document.getElementById('categoryNameInput').value = '';
            this.renderCategories();
            this.showAlert('Category Manifested');
        } catch (err) { this.showAlert(err.message, 'error'); }
    },

    async deleteCategory(id) {
        if(!confirm('Archive this entity?')) return;
        try {
            await fetch(`api/admin/categories.php?type=${this.state.activeCategoryType}&id=${id}`, { method: 'DELETE' });
            this.state.data.categories[this.state.activeCategoryType] = this.state.data.categories[this.state.activeCategoryType].filter(c => c.id !== id);
            this.renderCategories();
            this.showAlert('Entity Archived');
        } catch (err) { this.showAlert(err.message, 'error'); }
    },

    // --- TABLES & FLOORS ---
    renderTables() {
        const fGrid = document.getElementById('floorsGrid');
        fGrid.innerHTML = this.state.data.floors.map(f => `
            <div class="px-4 py-2.5 bg-gray-800 border border-gray-700 rounded-lg flex items-center gap-4 group">
                <span class="text-xs font-bold text-[#c5a059] uppercase">Floor ${f.floorNumber}</span>
                <div class="flex items-center gap-2 ml-auto opacity-0 group-hover:opacity-100 transition-all">
                    <button onclick="AdminSettings.openEditModal('floor', '${f.id}')" class="text-gray-600 hover:text-gray-200 transition-colors"><i data-lucide="pencil" class="w-3 h-3"></i></button>
                    <button onclick="AdminSettings.deleteFloor('${f.id}')" class="text-red-500/40 hover:text-red-400 transition-colors"><i data-lucide="trash-2" class="w-3 h-3"></i></button>
                </div>
            </div>
        `).join('');

        const tGrid = document.getElementById('tablesGrid');
        tGrid.innerHTML = this.state.data.tables.map(t => `
            <div class="table-card bg-gray-900 border border-gray-700/50 rounded-xl p-5 text-center group relative hover:border-gray-600">
                <p class="text-lg font-bold text-gray-200 truncate">${t.tableNumber}</p>
                <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all">
                    <button onclick="AdminSettings.openEditModal('table', '${t.id}')" class="text-gray-600 hover:text-[#c5a059] transition-colors"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                    <button onclick="AdminSettings.deleteTable('${t.id}')" class="text-gray-600 hover:text-red-400 transition-colors"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                </div>
            </div>
        `).join('');
        document.getElementById('tablesCounter').textContent = `${this.state.data.tables.length} Total`;
        lucide.createIcons();
    },

    async addFloor() {
        const num = document.getElementById('floorNumberInput').value;
        const ord = document.getElementById('floorOrderInput').value || 0;
        if(!num) return;
        try {
            const res = await fetch('api/admin/floors.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ floorNumber: num, order: ord })
            });
            const data = await res.json();
            this.state.data.floors.push(data);
            this.renderTables();
            this.showAlert('Level Protocol Active');
        } catch (err) { this.showAlert(err.message, 'error'); }
    },

    async deleteFloor(id) {
        if(!confirm('Delete this level?')) return;
        try {
            await fetch(`api/admin/floors.php?id=${id}`, { method: 'DELETE' });
            this.state.data.floors = this.state.data.floors.filter(f => f.id !== id);
            this.renderTables();
            this.showAlert('Level Excised');
        } catch (err) { this.showAlert(err.message, 'error'); }
    },

    async addTable() {
        const num = document.getElementById('tableNumberInput').value;
        const cap = document.getElementById('tableCapacityInput').value;
        if(!num) return;
        try {
            const res = await fetch('api/admin/tables.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tableNumber: num, capacity: cap })
            });
            const data = await res.json();
            this.state.data.tables.unshift(data);
            this.renderTables();
            this.showAlert('Static Point Anchored');
        } catch (err) { this.showAlert(err.message, 'error'); }
    },

    async deleteTable(id) {
        if(!confirm('Decommission unit?')) return;
        try {
            await fetch(`api/admin/tables.php?id=${id}`, { method: 'DELETE' });
            this.state.data.tables = this.state.data.tables.filter(t => t.id !== id);
            this.renderTables();
            this.showAlert('Unit Decommissioned');
        } catch (err) { this.showAlert(err.message, 'error'); }
    },

    openEditModal(type, id) {
        this.state.editingType = type;
        const modal = document.getElementById('editModal');
        const title = document.getElementById('editModalTitle');
        const content = document.getElementById('editModalContent');
        
        let item = null;
        let fields = '';

        if (type === 'category') {
            item = this.state.data.categories[this.state.activeCategoryType].find(c => c.id === id);
            title.textContent = `Edit ${this.state.activeCategoryType.charAt(0).toUpperCase() + this.state.activeCategoryType.slice(1)} Category`;
            fields = `
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Category Name</label>
                        <input type="text" id="editName" value="${item.name}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059]">
                    </div>
                </div>`;
        } else if (type === 'floor') {
            item = this.state.data.floors.find(f => f.id === id);
            title.textContent = 'Edit Floor';
            fields = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Floor Number</label>
                            <input type="text" id="editFloorNumber" value="${item.floorNumber}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059]">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Sort Order</label>
                            <input type="number" id="editFloorOrder" value="${item.order || 0}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059]">
                        </div>
                    </div>
                </div>`;
        } else if (type === 'table') {
            item = this.state.data.tables.find(t => t.id === id);
            title.textContent = 'Edit Table';
            fields = `
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Table Number</label>
                        <input type="text" id="editTableNumber" value="${item.tableNumber}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059]">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Capacity (Seats)</label>
                        <input type="number" id="editTableCapacity" value="${item.capacity}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-200 focus:outline-none focus:border-[#c5a059]">
                    </div>
                </div>`;
        }

        this.state.editingItem = item;
        content.innerHTML = fields;
        modal.classList.remove('hidden');
        lucide.createIcons();
    },

    closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        this.state.editingItem = null;
        this.state.editingType = null;
    },

    async saveEdit() {
        if (!this.state.editingItem) return;
        const id = this.state.editingItem.id;
        const type = this.state.editingType;
        let url = '';
        let payload = {};

        try {
            if (type === 'category') {
                url = `api/admin/categories.php?type=${this.state.activeCategoryType}&id=${id}`;
                payload = { name: document.getElementById('editName').value };
            } else if (type === 'floor') {
                url = `api/admin/floors.php?id=${id}`;
                payload = { floorNumber: document.getElementById('editFloorNumber').value, order: parseInt(document.getElementById('editFloorOrder').value) };
            } else if (type === 'table') {
                url = `api/admin/tables.php?id=${id}`;
                payload = { tableNumber: document.getElementById('editTableNumber').value, capacity: parseInt(document.getElementById('editTableCapacity').value) };
            }

            const res = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!res.ok) throw new Error('Update failed');

            // Update local state
            if (type === 'category') {
                const list = this.state.data.categories[this.state.activeCategoryType];
                const idx = list.findIndex(c => c.id === id);
                list[idx] = { ...list[idx], ...payload };
                this.renderCategories();
            } else if (type === 'floor') {
                const idx = this.state.data.floors.findIndex(f => f.id === id);
                this.state.data.floors[idx] = { ...this.state.data.floors[idx], ...payload };
                this.renderTables();
            } else if (type === 'table') {
                const idx = this.state.data.tables.findIndex(t => t.id === id);
                this.state.data.tables[idx] = { ...this.state.data.tables[idx], ...payload };
                this.renderTables();
            }

            this.showAlert('Entity Data Updated');
            this.closeEditModal();
        } catch (err) {
            this.showAlert(err.message, 'error');
        }
    },

    showAlert(msg, type = 'success') {
        const el = document.getElementById('alert');
        el.textContent = msg;
        el.className = `alert block p-4 text-sm font-bold text-center alert-${type}`;
        el.classList.remove('hidden');
        setTimeout(() => el.classList.add('hidden'), 4000);
    },

    setupLogoDropZone() {
        const zone = document.getElementById('logo-drop-zone');
        if (!zone) return;
        ['dragenter', 'dragover'].forEach(evt => {
            zone.addEventListener(evt, (e) => {
                e.preventDefault();
                zone.classList.add('border-[#c5a059]', 'bg-[#c5a059]/5');
            });
        });
        ['dragleave', 'drop'].forEach(evt => {
            zone.addEventListener(evt, (e) => {
                e.preventDefault();
                zone.classList.remove('border-[#c5a059]', 'bg-[#c5a059]/5');
            });
        });
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            const file = e.dataTransfer?.files?.[0];
            if (file) this.processLogoFile(file);
        });
    },

    async saveAll() {
        const payload = {
            branding: {
                app_name: document.getElementById('appNameInput').value,
                app_tagline: document.getElementById('appTaglineInput').value,
                logo_url: document.getElementById('logoUrlInput').value,
                favicon_url: document.getElementById('faviconUrlInput').value || document.getElementById('logoUrlInput').value
            },
            configuration: {
                vat_rate: parseFloat(document.getElementById('vatRateInput').value),
                enable_cashier_printing: document.getElementById('printToggle').checked,
                enable_cashier_today_revenue: document.getElementById('revenueToggle').checked
            }
        };
        try {
            for(let k in payload.branding) await fetch('api/admin/settings.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ key: k, value: payload.branding[k], type: 'string' }) });
            for(let k in payload.configuration) await fetch('api/admin/settings.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ key: k, value: payload.configuration[k], type: typeof payload.configuration[k] === 'number' ? 'number' : 'boolean' }) });
            this.showAlert('Core Manifest Synchronized');
            setTimeout(() => location.reload(), 1000);
        } catch (err) { this.showAlert(err.message, 'error'); }
    }
};

// VAT Badge Live
document.getElementById('vatRateInput').addEventListener('input', (e) => {
    const val = parseFloat(e.target.value) || 0;
    document.getElementById('vatDisplay').textContent = Math.round(val * 100) + '%';
});

// Init
AdminSettings.setupLogoDropZone();
AdminSettings.switchTab('branding');
lucide.createIcons();
</script>

<?php renderFooter(); ?>
