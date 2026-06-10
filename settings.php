<?php
// settings.php
/**
 * Admin Settings Hub — COMPLETE Luxury UI Reproduction (Merged Branding, Categories, Tables)
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/SettingsManager.php';

// Auth: admin or specifically permitted
requireAuth(['admin']);

$manager = new SettingsManager();
$settings = $manager->getAllSettings();

// Fetch all data
$menuCategories = $manager->getCategories('menu');
$stockCategories = $manager->getCategories('stock');
$distributionCategories = $manager->getCategories('distribution');
$tables = $manager->getTables();
$floors = $manager->getFloors();

// Extract branding and config
$branding = $settings['branding'] ?? [];
$config = $settings['configuration'] ?? [];

renderHeader("Settings");
?>

<div class="min-h-screen bg-[#0a0a0a] text-gray-300 font-sans selection:bg-[#d4af37]/30 pb-20">
    <div class="max-w-[1400px] mx-auto px-6 lg:px-10">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 pt-10">
            
            <!-- LEFT SIDEBAR: Branding Preview -->
            <aside class="lg:col-span-4 space-y-10 lg:sticky lg:top-24 h-fit">
                
                <div class="relative">
                    <div class="absolute -left-4 top-0 w-1 h-6 bg-[#d4af37]"></div>
                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-white/90 italic font-playfair">Logo Preview</h2>
                </div>

                <!-- Current Logo Card -->
                <div class="bg-[#121212]/80 border border-white/5 rounded-[2rem] p-10 text-center shadow-2xl relative overflow-hidden group">
                     <div class="absolute top-4 left-0 right-0 text-[8px] font-black uppercase tracking-[0.2em] text-[#d4af37]/50">Current Logo</div>
                     <div class="relative z-10 space-y-6 pt-4">
                         <img id="sidebarLogoPreview" src="<?php echo htmlspecialchars($branding['logo_url'] ?? ''); ?>" 
                              class="w-24 h-24 mx-auto object-contain transition-transform duration-700 group-hover:scale-110" alt="Logo">
                         <div>
                             <h3 id="sidebarAppName" class="text-2xl font-black font-playfair italic text-[#f3cf7a] leading-tight text-center"><?php echo htmlspecialchars($branding['app_name'] ?? 'ABE HOTEL'); ?></h3>
                             <p id="sidebarAppTagline" class="text-[9px] font-black uppercase tracking-[0.3em] text-gray-600 mt-1"><?php echo htmlspecialchars($branding['app_tagline'] ?? 'HOTEL MANAGEMENT SYSTEM'); ?></p>
                         </div>
                     </div>
                </div>

                <!-- Preview in Navigation -->
                <div class="space-y-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-700 px-4 italic text-center">Preview in Navigation</p>
                    <div class="bg-[#121212] border border-white/5 rounded-3xl p-6 flex items-center justify-between shadow-xl">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-black flex items-center justify-center border border-white/10 p-1.5">
                                <img id="navLogoPreview" src="<?php echo htmlspecialchars($branding['logo_url'] ?? ''); ?>" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <p id="navAppName" class="text-[11px] font-black text-white leading-none tracking-tight"><?php echo htmlspecialchars($branding['app_name'] ?? 'ABE HOTEL'); ?></p>
                                <p class="text-[7px] font-bold text-gray-600 uppercase tracking-widest mt-1 text-center">Navigation Bar</p>
                            </div>
                        </div>
                        <div class="flex gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-white/5"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-white/5"></div>
                        </div>
                    </div>
                </div>

                <!-- Browser Tab Preview -->
                <div class="space-y-4">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-700 px-4 italic text-center">Browser Tab Preview</p>
                    <div class="bg-[#1a1a1a] rounded-t-2xl py-3 px-5 border-l border-r border-t border-white/10 flex items-center gap-4 w-[280px] mx-auto shadow-2xl relative">
                        <img id="tabFaviconPreview" src="<?php echo htmlspecialchars($branding['favicon_url'] ?? ($branding['logo_url'] ?? '')); ?>" class="w-4 h-4 object-contain">
                        <span id="tabAppName" class="text-[10px] font-bold text-gray-400 truncate tracking-tight"><?php echo htmlspecialchars($branding['app_name'] ?? 'Abe Hotel - Management System'); ?></span>
                        <button class="absolute right-4 text-gray-700 hover:text-white transition-colors">✕</button>
                    </div>
                </div>

                <!-- Logo Tips Card -->
                <div class="bg-black/40 border border-white/5 rounded-[2.5rem] p-10 relative overflow-hidden shadow-2xl">
                    <h3 class="flex items-center gap-4 text-xs font-black uppercase tracking-widest text-[#d4af37] mb-10">
                        <i data-lucide="help-circle" class="w-4 h-4 text-[#d4af37]"></i>
                        Logo Tips
                    </h3>
                    <ul class="space-y-6">
                        <li class="flex items-center gap-4 group">
                            <div class="w-5 h-5 rounded-full border border-[#d4af37]/30 flex items-center justify-center text-[8px] font-black text-[#d4af37]/50 group-hover:bg-[#d4af37] group-hover:text-black transition-all">01</div>
                            <span class="text-[10px] font-bold text-gray-500 leading-tight">Use square images (1:1 ratio)</span>
                        </li>
                        <li class="flex items-center gap-4 group">
                            <div class="w-5 h-5 rounded-full border border-[#d4af37]/30 flex items-center justify-center text-[8px] font-black text-[#d4af37]/50 group-hover:bg-[#d4af37] group-hover:text-black transition-all">02</div>
                            <span class="text-[10px] font-bold text-gray-500 leading-tight">Minimum 200x200 pixels</span>
                        </li>
                        <li class="flex items-center gap-4 group">
                            <div class="w-5 h-5 rounded-full border border-[#d4af37]/30 flex items-center justify-center text-[8px] font-black text-[#d4af37]/50 group-hover:bg-[#d4af37] group-hover:text-black transition-all">03</div>
                            <span class="text-[10px] font-bold text-gray-500 leading-tight">PNG, JPG, GIF, or WebP format</span>
                        </li>
                        <li class="flex items-center gap-4 group">
                            <div class="w-5 h-5 rounded-full border border-[#d4af37]/30 flex items-center justify-center text-[8px] font-black text-[#d4af37]/50 group-hover:bg-[#d4af37] group-hover:text-black transition-all">04</div>
                            <span class="text-[10px] font-bold text-gray-500 leading-tight">Max upload size: 5MB</span>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- RIGHT PANEL: Forms -->
            <main class="lg:col-span-8">
                
                <div class="bg-[#111111] border border-white/5 rounded-[3rem] p-12 lg:p-16 h-full relative shadow-3xl">
                    
                    <div id="alert" class="alert hidden mb-10 p-6 rounded-2xl text-xs font-black uppercase tracking-widest text-center shadow-2xl"></div>

                    <!-- Main Navigation Tabs -->
                    <div class="flex gap-10 border-b border-white/5 mb-16 px-4">
                        <button onclick="AdminSettings.switchTab('branding')" id="tab-branding" class="tab-btn active pb-6 text-[10px] font-black uppercase tracking-[0.2em] transition-all border-b-2 border-[#d4af37] text-white">Branding</button>
                        <button onclick="AdminSettings.switchTab('categories')" id="tab-categories" class="tab-btn pb-6 text-[10px] font-black uppercase tracking-[0.2em] transition-all border-b-2 border-transparent text-gray-600 hover:text-white">Categories</button>
                        <button onclick="AdminSettings.switchTab('tables')" id="tab-tables" class="tab-btn pb-6 text-[10px] font-black uppercase tracking-[0.2em] transition-all border-b-2 border-transparent text-gray-600 hover:text-white">Tables</button>
                    </div>

                    <!-- TAB CONTENT: Branding -->
                    <div id="branding-section" class="tab-content animate-fadeIn space-y-20">
                        
                        <div class="flex items-center justify-between mb-10">
                            <h3 class="text-2xl font-black font-playfair italic text-[#f3cf7a]">System Branding</h3>
                            <button onclick="AdminSettings.saveAll()" class="px-10 py-4 bg-[#d4af37] text-black text-[10px] font-black uppercase tracking-[0.3em] rounded-xl hover:bg-[#f3cf7a] transition-all shadow-xl shadow-[#d4af37]/10 group">
                                <i data-lucide="save" class="w-4 h-4 inline-block mr-2 group-hover:scale-110"></i> SAVE BRANDING
                            </button>
                        </div>

                        <!-- Logo Upload Section -->
                        <div class="space-y-10">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/60">Logo Upload</h4>
                                <div class="flex p-1 bg-black/40 rounded-xl border border-white/5 gap-1">
                                    <button onclick="AdminSettings.setUploadMode('url')" id="mode-url" class="px-6 py-2 rounded-lg text-[8px] font-black uppercase tracking-widest transition-all bg-[#d4af37] text-black shadow-lg">
                                        <i data-lucide="link-2" class="w-3 h-3 inline-block mr-2"></i> URL
                                    </button>
                                    <button onclick="AdminSettings.setUploadMode('file')" id="mode-file" class="px-6 py-2 rounded-lg text-[8px] font-black uppercase tracking-widest transition-all text-gray-500 hover:text-white">
                                        <i data-lucide="file-up" class="w-3 h-3 inline-block mr-2"></i> Upload File
                                    </button>
                                </div>
                            </div>

                            <div id="url-input-container" class="space-y-6 transition-all duration-500">
                                <input type="url" id="logoUrlInput" value="<?php echo htmlspecialchars($branding['logo_url'] ?? ''); ?>" 
                                    placeholder="Enter image URL..." 
                                    class="w-full bg-black border border-white/5 rounded-2xl px-8 py-5 text-sm text-[#f3cf7a] focus:outline-none focus:border-[#d4af37]/50 placeholder:text-gray-800 shadow-inner">
                            </div>

                            <div id="file-input-container" class="hidden relative group" onclick="document.getElementById('logoFileInput').click()">
                                <div class="border-2 border-dashed border-[#d4af37]/10 rounded-[2.5rem] p-12 text-center hover:border-[#d4af37]/30 transition-all cursor-pointer bg-black/20">
                                    <div class="w-16 h-16 bg-[#d4af37]/5 rounded-2xl flex items-center justify-center mx-auto mb-6 text-[#d4af37]/40 group-hover:text-[#d4af37] transition-all">
                                        <i data-lucide="upload-cloud" class="w-8 h-8"></i>
                                    </div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-white/60 group-hover:text-white transition-all">Drop your logo or click to upload</p>
                                    <input type="file" id="logoFileInput" accept="image/*" class="hidden" onchange="AdminSettings.handleFileUpload(event)">
                                </div>
                            </div>
                        </div>

                        <!-- General App Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                             <div class="space-y-4">
                                <label class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/60">Application Name</label>
                                <input type="text" id="appNameInput" value="<?php echo htmlspecialchars($branding['app_name'] ?? ''); ?>"
                                    class="w-full bg-black border border-white/5 rounded-2xl px-8 py-5 text-sm font-bold text-white focus:outline-none focus:border-[#d4af37]/50 shadow-inner">
                             </div>
                             <div class="space-y-4">
                                <label class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/60">Application Tagline</label>
                                <input type="text" id="appTaglineInput" value="<?php echo htmlspecialchars($branding['app_tagline'] ?? ''); ?>"
                                    class="w-full bg-black border border-white/5 rounded-2xl px-8 py-5 text-sm font-bold text-white focus:outline-none focus:border-[#d4af37]/50 shadow-inner">
                             </div>
                        </div>

                        <!-- VAT & Config -->
                        <div class="space-y-12">
                             <div class="space-y-8">
                                 <label class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/60">Taxation (VAT) Rate</label>
                                 <div class="flex items-center gap-6">
                                     <input type="number" id="vatRateInput" step="0.01" value="<?php echo htmlspecialchars($config['vat_rate'] ?? 0.15); ?>"
                                         class="flex-1 bg-black border border-white/5 rounded-2xl px-8 py-5 text-sm font-black text-[#f3cf7a] focus:outline-none focus:border-[#d4af37] shadow-inner text-center">
                                     <div class="w-24 h-16 bg-[#d4af37]/5 border border-[#d4af37]/20 rounded-2xl flex items-center justify-center text-xl font-black text-[#d4af37]">
                                         <span id="vatDisplay"><?php echo (int)(($config['vat_rate'] ?? 0.15) * 100); ?>%</span>
                                     </div>
                                 </div>
                             </div>

                             <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                 <!-- Toggle 1 -->
                                 <div class="p-8 bg-black/20 border border-white/5 rounded-[2rem] flex items-center justify-between group">
                                     <div>
                                         <p class="text-[10px] font-black uppercase tracking-widest text-white mb-1 leading-none">Cashier Printing</p>
                                         <p class="text-[8px] font-bold text-gray-700 uppercase">Auto-print after checkout</p>
                                     </div>
                                     <label class="relative inline-flex items-center cursor-pointer">
                                         <input type="checkbox" id="printToggle" class="sr-only peer" <?php echo ($config['enable_cashier_printing'] ? 'checked' : ''); ?>>
                                         <div class="w-12 h-6 bg-white/5 border border-white/10 rounded-full peer-checked:bg-[#4ade80]/20 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white/10 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-6 peer-checked:after:bg-[#4ade80] shadow-xl"></div>
                                     </label>
                                 </div>
                                 <!-- Toggle 2 -->
                                 <div class="p-8 bg-black/20 border border-white/5 rounded-[2rem] flex items-center justify-between group">
                                     <div>
                                         <p class="text-[10px] font-black uppercase tracking-widest text-white mb-1 leading-none">Revenue Visibility</p>
                                         <p class="text-[8px] font-bold text-gray-700 uppercase">Allow cashiers to see totals</p>
                                     </div>
                                     <label class="relative inline-flex items-center cursor-pointer">
                                         <input type="checkbox" id="revenueToggle" class="sr-only peer" <?php echo ($config['enable_cashier_today_revenue'] ? 'checked' : ''); ?>>
                                         <div class="w-12 h-6 bg-white/5 border border-white/10 rounded-full peer-checked:bg-[#4ade80]/20 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white/10 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-6 peer-checked:after:bg-[#4ade80] shadow-xl"></div>
                                     </label>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <!-- TAB CONTENT: Categories -->
                    <div id="categories-section" class="tab-content hidden animate-fadeIn space-y-12">
                        
                        <div class="flex p-1.5 bg-black/40 rounded-[1.5rem] border border-white/5 gap-2 max-w-2xl mx-auto shadow-2xl">
                            <button onclick="AdminSettings.switchCategoryType('menu')" id="btn-cat-menu" class="flex-1 py-3.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all bg-[#d4af37] text-black shadow-lg">
                                <i data-lucide="book-open" class="w-3.5 h-3.5 inline-block mr-2"></i> MENU CATEGORIES
                            </button>
                            <button onclick="AdminSettings.switchCategoryType('stock')" id="btn-cat-stock" class="flex-1 py-3.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all text-gray-600 hover:text-white">
                                <i data-lucide="package" class="w-3.5 h-3.5 inline-block mr-2"></i> STOCK CATEGORIES
                            </button>
                            <button onclick="AdminSettings.switchCategoryType('distribution')" id="btn-cat-dist" class="flex-1 py-3.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all text-gray-600 hover:text-white">
                                <i data-lucide="truck" class="w-3.5 h-3.5 inline-block mr-2"></i> DISTRIBUTION
                            </button>
                        </div>

                        <div class="bg-black/30 border border-white/5 rounded-[2rem] p-10 space-y-8 shadow-inner">
                            <h4 id="cat-form-title" class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/60">— ADD NEW MENU CATEGORY</h4>
                            <div class="flex gap-4">
                                <input type="text" id="categoryNameInput" placeholder="Category Name..." 
                                    class="flex-1 bg-black border border-white/10 rounded-2xl px-6 py-5 text-sm text-white focus:outline-none focus:border-[#d4af37]/50 shadow-inner">
                                <button onclick="AdminSettings.addCategory()" class="px-10 bg-[#d4af37] text-black text-[9px] font-black uppercase tracking-widest rounded-2xl hover:bg-[#f3cf7a] shadow-2xl shadow-[#d4af37]/10">
                                    ADD CATEGORY
                                </button>
                            </div>
                        </div>

                        <div id="categoriesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <!-- Populated via JS -->
                        </div>
                    </div>

                    <!-- TAB CONTENT: Tables & Floors -->
                    <div id="tables-section" class="tab-content hidden animate-fadeIn space-y-16">
                        
                        <div class="space-y-10">
                             <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/60">— MANAGE FLOORS</h3>
                             <div class="flex gap-4 p-10 bg-black/40 rounded-[2.5rem] border border-white/5 shadow-inner">
                                 <input type="text" id="floorNumberInput" placeholder="Floor Number (e.g. #1)" class="flex-1 bg-black border border-white/10 rounded-2xl px-6 py-5 text-sm">
                                 <input type="number" id="floorOrderInput" placeholder="0" class="w-24 bg-black border border-white/10 rounded-2xl px-6 py-5 text-sm text-center">
                                 <button onclick="AdminSettings.addFloor()" class="px-8 bg-[#d4af37] text-black text-[9px] font-black uppercase tracking-widest rounded-2xl shadow-xl">ADD FLOOR</button>
                             </div>
                             <div id="floorsGrid" class="flex flex-wrap gap-4 px-4">
                                 <!-- Populated via JS -->
                             </div>
                        </div>

                        <div class="space-y-10">
                             <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/60">— ADD NEW TABLE</h3>
                             <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-10 bg-black/40 rounded-[2.5rem] border border-white/5 shadow-inner">
                                 <input type="text" id="tableNumberInput" placeholder="Number (e.g. T-01)" class="bg-black border border-white/10 rounded-2xl px-6 py-5 text-sm">
                                 <input type="number" id="tableCapacityInput" placeholder="Seats" class="bg-black border border-white/10 rounded-2xl px-6 py-5 text-sm text-center">
                                 <button onclick="AdminSettings.addTable()" class="bg-[#d4af37] text-black text-[9px] font-black uppercase tracking-widest rounded-2xl shadow-xl">ADD TABLE</button>
                             </div>
                        </div>

                        <div class="space-y-10">
                             <div class="flex items-center justify-between px-4">
                                 <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-white/50 italic font-playfair">UNIVERSAL TABLES</h3>
                                 <span id="tablesCounter" class="px-4 py-1.5 bg-[#4ade80]/10 border border-[#4ade80]/20 rounded-xl text-[9px] font-black text-[#4ade80] uppercase tracking-widest">0 TOTAL</span>
                             </div>
                             <div id="tablesGrid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-6">
                                 <!-- Populated via JS -->
                             </div>
                        </div>

                    </div>

                </div>
            </main>

        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@1,900&display=swap');

.alert-success { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.2); }
.alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

.category-card, .table-card { transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
.category-card:hover, .table-card:hover { transform: translateY(-10px) scale(1.02); box-shadow: 0 30px 60px -20px rgba(0,0,0,0.8); border-color: rgba(212, 175, 55, 0.3); }

input:focus { box-shadow: 0 0 40px rgba(212, 175, 55, 0.05); }
.tab-btn.active { text-shadow: 0 0 15px rgba(212, 175, 55, 0.4); }
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
        }
    },

    switchTab(tab) {
        this.state.activeTab = tab;
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active', 'text-white', 'border-[#d4af37]');
            b.classList.add('text-gray-600', 'border-transparent');
        });
        const btn = document.getElementById(`tab-${tab}`);
        if(btn) btn.classList.add('active', 'text-white', 'border-[#d4af37]');
        
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        const section = document.getElementById(`${tab}-section`);
        if(section) section.classList.remove('hidden');

        if(tab === 'categories') this.renderCategories();
        if(tab === 'tables') this.renderTables();
    },

    setUploadMode(mode) {
        this.state.uploadMode = mode;
        const btnUrl = document.getElementById('mode-url');
        const btnFile = document.getElementById('mode-file');
        const urlIn = document.getElementById('url-input-container');
        const fileIn = document.getElementById('file-input-container');

        if(mode === 'url') {
            btnUrl.className = "px-6 py-2 rounded-lg text-[8px] font-black uppercase tracking-widest transition-all bg-[#d4af37] text-black shadow-lg";
            btnFile.className = "px-6 py-2 rounded-lg text-[8px] font-black uppercase tracking-widest transition-all text-gray-500 hover:text-white";
            urlIn.classList.remove('hidden');
            fileIn.classList.add('hidden');
        } else {
            btnFile.className = "px-6 py-2 rounded-lg text-[8px] font-black uppercase tracking-widest transition-all bg-[#d4af37] text-black shadow-lg";
            btnUrl.className = "px-6 py-2 rounded-lg text-[8px] font-black uppercase tracking-widest transition-all text-gray-500 hover:text-white";
            urlIn.classList.add('hidden');
            fileIn.classList.remove('hidden');
        }
    },

    async handleFileUpload(e) {
        const file = e.target.files[0];
        if(!file) return;
        const fd = new FormData();
        fd.append('file', file);
        try {
            const res = await fetch('api/admin/settings.php?type=logo', { method: 'POST', body: fd });
            const data = await res.json();
            if(data.success) {
                this.updatePreviews(data.url);
                this.showAlert('Premium Logo Synced');
            }
        } catch (err) { this.showAlert(err.message, 'error'); }
    },

    updatePreviews(url) {
        ['sidebarLogoPreview', 'navLogoPreview', 'tabFaviconPreview', 'logoUrlInput'].forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                if(el.tagName === 'INPUT') el.value = url;
                else el.src = url;
            }
        });
    },

    // --- CATEGORIES ---
    switchCategoryType(type) {
        this.state.activeCategoryType = type;
        ['menu', 'stock', 'distribution'].forEach(t => {
            const btn = document.getElementById(`btn-cat-${t.substring(0,4)}`);
            if(btn) btn.className = "flex-1 py-3.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all text-gray-600 hover:text-white";
        });
        const activeBtn = document.getElementById(`btn-cat-${type.substring(0,4)}`);
        if(activeBtn) activeBtn.className = "flex-1 py-3.5 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all bg-[#d4af37] text-black shadow-lg";
        document.getElementById('cat-form-title').textContent = `— ADD NEW ${type.toUpperCase()} CATEGORY`;
        this.renderCategories();
    },

    renderCategories() {
        const grid = document.getElementById('categoriesGrid');
        const items = this.state.data.categories[this.state.activeCategoryType];
        grid.innerHTML = items.map(cat => `
            <div class="category-card bg-[#1a1a1a]/60 border border-white/5 rounded-[2.5rem] p-10 relative overflow-hidden group shadow-xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h5 class="text-[13px] font-black text-[#f3cf7a] uppercase tracking-widest mb-3 leading-tight">${cat.name}</h5>
                        <span class="px-2.5 py-1 bg-white/5 border border-white/10 rounded-lg text-[8px] font-black uppercase text-gray-600 tracking-[0.2em]">
                            ${this.state.activeCategoryType === 'menu' ? 'MENU' : 'STOCK'}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="AdminSettings.deleteCategory('${cat.id}')" class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-600 hover:text-white hover:bg-white/10 transition-all">
                             <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                        <button class="w-10 h-10 rounded-xl bg-[#d4af37]/10 border border-[#d4af37]/30 flex items-center justify-center text-[#d4af37] hover:bg-[#d4af37] hover:text-black transition-all">
                             <i data-lucide="pencil" class="w-4 h-4"></i>
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
            <div class="px-6 py-4 bg-black/40 border border-white/5 rounded-[1.5rem] flex items-center gap-6 group shadow-lg">
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#d4af37]">Floor ${f.floorNumber}</span>
                <div class="flex items-center gap-3 opacity-0 group-hover:opacity-100 transition-all">
                    <button class="text-gray-700 hover:text-white"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                    <button onclick="AdminSettings.deleteFloor('${f.id}')" class="text-red-500/30 hover:text-red-500"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                </div>
            </div>
        `).join('');

        const tGrid = document.getElementById('tablesGrid');
        tGrid.innerHTML = this.state.data.tables.map(t => `
            <div class="table-card bg-[#1a1a1a]/60 border border-white/5 rounded-[2rem] p-10 text-center group relative shadow-2xl">
                <p class="text-[18px] font-black text-[#f3cf7a] mb-2 tracking-tighter">${t.tableNumber}</p>
                <div class="absolute top-4 right-4 flex flex-col gap-3 opacity-0 group-hover:opacity-100 transition-all scale-90">
                    <button class="text-white/20 hover:text-[#d4af37]"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                    <button onclick="AdminSettings.deleteTable('${t.id}')" class="text-white/20 hover:text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </div>
            </div>
        `).join('');
        document.getElementById('tablesCounter').textContent = `${this.state.data.tables.length} TOTAL`;
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

    showAlert(msg, type = 'success') {
        const el = document.getElementById('alert');
        el.textContent = msg;
        el.className = `alert block mb-10 p-6 rounded-3xl text-[10px] font-black uppercase tracking-[0.3em] text-center shadow-2xl alert-${type}`;
        setTimeout(() => el.classList.add('hidden'), 4000);
    },

    async saveAll() {
        const payload = {
            branding: {
                app_name: document.getElementById('appNameInput').value,
                app_tagline: document.getElementById('appTaglineInput').value,
                logo_url: document.getElementById('logoUrlInput').value,
                favicon_url: (document.getElementById('logoUrlInput').value) // Fallback to logo
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
AdminSettings.switchTab('branding');
lucide.createIcons();
</script>

<?php renderFooter(); ?>
