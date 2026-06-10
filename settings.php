<?php
// settings.php
/**
 * Admin Settings Hub — Branding, Configuration, Categories, & Floors/Tables
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';
require_once 'includes/SettingsManager.php';

// Auth: admin or specifically permitted
requireAuth(['admin']);

$manager = new SettingsManager();
$settings = $manager->getAllSettings();
$categories = $manager->getCategories('menu');
$tables = $manager->getTables();
$floors = $manager->getFloors();

// Extract branding and config
$branding = $settings['branding'] ?? [];
$config = $settings['configuration'] ?? [];

renderHeader("System Settings");
?>

<div class="min-h-screen bg-[#0f1110] text-gray-300 font-sans selection:bg-[#d4af37]/30 p-6 lg:p-10">
    <div class="max-w-[1400px] mx-auto">
        
        <div class="header mb-10">
            <h1 class="text-3xl font-black font-playfair italic text-[#f3cf7a] mb-2">⚙️ System Settings</h1>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-gray-500">Manage Branding, Configuration, Categories, and Tables</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- LEFT SIDEBAR: Live Preview (lg:col-span-4) -->
            <aside class="lg:col-span-4 space-y-8 lg:sticky lg:top-24 h-fit">
                <div class="glass p-10 rounded-[3rem] border border-white/5 bg-[#151716] shadow-3xl text-center relative overflow-hidden group">
                     <!-- Preview Label -->
                     <div class="absolute top-6 left-6 text-[8px] font-black uppercase tracking-widest text-gray-700">Live Branding Preview</div>
                     
                     <!-- Actual Logo Preview -->
                     <div class="relative z-10 py-6">
                         <div id="preview-logo-container" class="w-32 h-32 mx-auto mb-6 flex items-center justify-center transition-transform duration-500 group-hover:scale-105">
                             <img id="logoPreview" src="<?php echo htmlspecialchars($branding['logo_url'] ?? ''); ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                         </div>
                         <h2 id="preview-app-name" class="text-2xl font-black font-playfair italic text-[#f3cf7a] mb-1"><?php echo htmlspecialchars($branding['app_name'] ?? 'Prime Addis'); ?></h2>
                         <p id="preview-app-tagline" class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-500"><?php echo htmlspecialchars($branding['app_tagline'] ?? 'Coffee Management'); ?></p>
                     </div>

                     <!-- Mock Browser Tab Preview -->
                     <div class="mt-8 text-left border-t border-white/5 pt-8">
                         <p class="text-[9px] font-black uppercase tracking-widest text-gray-700 mb-4 px-2">Browser Tab</p>
                         <div class="bg-[#1a1a1a] rounded-t-xl py-2 px-4 border-l border-r border-t border-white/10 flex items-center gap-3 w-48 mx-auto lg:mx-0">
                             <img id="tabFaviconPreview" src="<?php echo htmlspecialchars($branding['favicon_url'] ?? ''); ?>" class="w-3.5 h-3.5 object-contain">
                             <span id="preview-tab-name" class="text-[10px] font-bold text-gray-400 truncate tracking-tight"><?php echo htmlspecialchars($branding['app_name'] ?? 'Prime Addis'); ?></span>
                         </div>
                     </div>
                </div>

                <div class="glass p-8 rounded-[2.5rem] border border-white/5 bg-black/20">
                    <h3 class="flex items-center gap-3 text-xs font-black uppercase tracking-widest text-[#d4af37] mb-6">
                         💡 Branding Tips
                    </h3>
                    <ul class="space-y-4 text-[10px] font-bold text-gray-500 leading-relaxed">
                        <li class="flex gap-3"><span class="text-[#d4af37]">01</span> Use square (1:1) icons for favicons.</li>
                        <li class="flex gap-3"><span class="text-[#d4af37]">02</span> PNG with transparency works best.</li>
                        <li class="flex gap-3"><span class="text-[#d4af37]">03</span> Logo scales automatically for POS and Receipts.</li>
                    </ul>
                </div>
            </aside>

            <!-- RIGHT PANEL: Tabs & Forms (lg:col-span-8) -->
            <main class="lg:col-span-8 space-y-8">
                
                <div class="glass rounded-[2.5rem] border border-white/5 bg-[#151716] shadow-3xl overflow-hidden p-8 lg:p-12">
                    <div id="alert" class="alert hidden mb-8 p-4 rounded-xl text-xs font-black uppercase tracking-widest"></div>

                    <div class="flex gap-6 border-b border-white/5 mb-10 overflow-x-auto no-scrollbar">
                        <button onclick="switchTab('branding')" class="tab-btn active pb-4 text-[10px] font-black uppercase tracking-widest transition-all border-b-2 border-[#d4af37] text-white">🎨 Branding</button>
                        <button onclick="switchTab('configuration')" class="tab-btn pb-4 text-[10px] font-black uppercase tracking-widest transition-all border-b-2 border-transparent text-gray-500 hover:text-white">⚙️ Config</button>
                        <button onclick="switchTab('categories')" class="tab-btn pb-4 text-[10px] font-black uppercase tracking-widest transition-all border-b-2 border-transparent text-gray-500 hover:text-white">📂 Categories</button>
                        <button onclick="switchTab('tables')" class="tab-btn pb-4 text-[10px] font-black uppercase tracking-widest transition-all border-b-2 border-transparent text-gray-500 hover:text-white">🪑 Tables</button>
                    </div>

                    <!-- TAB: Branding -->
                    <div id="branding" class="tab-content block animate-fadeIn">
                        <form id="brandingForm" onsubmit="handleBrandingSubmit(event)" class="space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="form-group">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-600 mb-3">Application Name</label>
                                    <input type="text" name="app_name" value="<?php echo htmlspecialchars($branding['app_name'] ?? ''); ?>" 
                                        class="w-full bg-black/40 border border-white/5 rounded-2xl px-5 py-3.5 text-sm text-white focus:outline-none focus:border-[#d4af37]/50 transition-all">
                                </div>
                                <div class="form-group">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-600 mb-3">Application Tagline</label>
                                    <input type="text" name="app_tagline" value="<?php echo htmlspecialchars($branding['app_tagline'] ?? ''); ?>"
                                        class="w-full bg-black/40 border border-white/5 rounded-2xl px-5 py-3.5 text-sm text-white focus:outline-none focus:border-[#d4af37]/50 transition-all">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-600 mb-3">Logo Upload</label>
                                <div class="relative group">
                                    <div class="border-2 border-dashed border-white/10 rounded-[2rem] p-10 text-center hover:border-[#d4af37]/30 transition-all cursor-pointer bg-black/20"
                                         onclick="document.getElementById('logoInput').click()">
                                        <div class="text-3xl mb-4">📤</div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Click to upload or drag logo</p>
                                        <p class="text-[8px] font-bold text-gray-600 mt-2">JPG, PNG, WEBP • MAX 5MB</p>
                                    </div>
                                    <input type="file" id="logoInput" accept="image/*" class="hidden" onchange="handleLogoUpload(event)">
                                </div>
                            </div>

                            <button type="submit" class="w-full py-4 bg-[#d4af37] text-black text-[10px] font-black uppercase tracking-[0.3em] rounded-2xl hover:bg-[#f3cf7a] transition-all shadow-xl shadow-[#d4af37]/10">💾 Save Branding</button>
                        </form>
                    </div>

                    <!-- TAB: Configuration -->
                    <div id="configuration" class="tab-content hidden animate-fadeIn">
                        <form id="configForm" onsubmit="handleConfigSubmit(event)" class="space-y-10">
                            <div class="form-group">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-600 mb-4">VAT Rate (%)</label>
                                <div class="relative">
                                    <input type="number" name="vat_rate" step="0.01" value="<?php echo htmlspecialchars($config['vat_rate'] ?? 0.08); ?>"
                                        class="w-full bg-black/40 border border-white/5 rounded-2xl px-5 py-4 text-xl font-bold text-[#f3cf7a] focus:outline-none focus:border-[#d4af37]/50 transition-all">
                                    <span class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-500 font-black">%</span>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <label class="flex items-center justify-between p-6 bg-black/20 rounded-[1.5rem] border border-white/5 cursor-pointer group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">🖨️</div>
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-white">Enable Cashier Printing</p>
                                            <p class="text-[8px] font-bold text-gray-600 mt-1">Automatically trigger printer for new orders</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="enable_cashier_printing" class="w-5 h-5 rounded-md border-white/10 bg-white/5 text-[#d4af37] focus:ring-[#d4af37]" <?php echo ($config['enable_cashier_printing'] ? 'checked' : ''); ?>>
                                </label>

                                <label class="flex items-center justify-between p-6 bg-black/20 rounded-[1.5rem] border border-white/5 cursor-pointer group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">💰</div>
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-widest text-white">Show Revenue to Cashiers</p>
                                            <p class="text-[8px] font-bold text-gray-600 mt-1">Allow staff to see daily totals in POS</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="enable_cashier_today_revenue" class="w-5 h-5 rounded-md border-white/10 bg-white/5 text-[#d4af37] focus:ring-[#d4af37]" <?php echo ($config['enable_cashier_today_revenue'] ? 'checked' : ''); ?>>
                                </label>
                            </div>

                            <button type="submit" class="w-full py-4 bg-[#d4af37] text-black text-[10px] font-black uppercase tracking-[0.3em] rounded-2xl hover:bg-[#f3cf7a] transition-all shadow-xl">💾 Update Logic</button>
                        </form>
                    </div>

                    <!-- TAB: Categories -->
                    <div id="categories" class="tab-content hidden animate-fadeIn">
                        <div class="flex gap-4 mb-10">
                            <input type="text" id="categoryName" placeholder="New Category Name..." 
                                class="flex-1 bg-black/40 border border-white/5 rounded-2xl px-5 py-3.5 text-sm">
                            <button onclick="addCategory()" class="px-8 bg-[#d4af37] text-black text-[10px] font-black uppercase tracking-widest rounded-2xl hover:bg-[#f3cf7a] transition-all">➕ Add</button>
                        </div>

                        <div class="overflow-hidden rounded-[2rem] border border-white/5">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-black/40 text-[#d4af37] uppercase font-black tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4">Category Name</th>
                                        <th class="px-6 py-4">Type</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="categoriesList" class="divide-y divide-white/5">
                                    <?php foreach($categories as $cat): ?>
                                    <tr class="hover:bg-white/5 transition-all">
                                        <td class="px-6 py-5 font-bold text-white"><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td class="px-6 py-5 uppercase text-[9px] font-black text-gray-600"><?php echo htmlspecialchars($cat['type']); ?></td>
                                        <td class="px-6 py-5 text-right">
                                            <button onclick="deleteCategory('<?php echo $cat['id']; ?>')" class="text-red-500/50 hover:text-red-500 font-black uppercase text-[8px] tracking-widest">Remove</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: Tables -->
                    <div id="tables" class="tab-content hidden animate-fadeIn">
                        <div class="grid grid-cols-3 gap-4 mb-10">
                            <input type="text" id="tableNumber" placeholder="Table # (e.g. T1)" class="bg-black/40 border border-white/5 rounded-xl px-5 py-3.5 text-sm">
                            <input type="number" id="tableCapacity" placeholder="Cap." class="bg-black/40 border border-white/5 rounded-xl px-5 py-3.5 text-sm">
                            <button onclick="addTable()" class="bg-[#d4af37] text-black text-[10px] font-black uppercase tracking-widest rounded-xl">➕ Add</button>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="tablesList">
                            <?php foreach($tables as $table): ?>
                            <div class="p-6 bg-black/20 border border-white/5 rounded-[1.5rem] relative group">
                                <p class="text-2xl font-black text-[#f3cf7a] mb-1"><?php echo htmlspecialchars($table['tableNumber']); ?></p>
                                <p class="text-[9px] font-black uppercase text-gray-600"><?php echo $table['capacity']; ?> Seats</p>
                                <button onclick="deleteTable('<?php echo $table['id']; ?>')" class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 text-red-500 text-xs">✕</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </main>

        </div>
    </div>
</div>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.alert.hidden { display: none; }
.alert-success { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.2); }
.alert-error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }
</style>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('active', 'text-white', 'border-[#d4af37]');
        el.classList.add('text-gray-500', 'border-transparent');
    });

    const activeTab = document.getElementById(tabId);
    activeTab.classList.remove('hidden');
    
    event.target.classList.add('active', 'text-white', 'border-[#d4af37]');
    event.target.classList.remove('text-gray-500', 'border-transparent');
}

function showAlert(msg, type = 'success') {
    const alert = document.getElementById('alert');
    alert.textContent = msg;
    alert.className = `alert block mb-8 p-4 rounded-xl text-xs font-black uppercase tracking-widest alert-${type}`;
    setTimeout(() => alert.classList.add('hidden'), 5000);
}

async function handleBrandingSubmit(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const updates = [
        { key: 'app_name', value: fd.get('app_name') },
        { key: 'app_tagline', value: fd.get('app_tagline') }
    ];

    try {
        for(let up of updates) {
            await fetch('api/admin/settings.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...up, type: 'string' })
            });
        }
        showAlert('Branding Updated Successfully');
        location.reload();
    } catch (err) {
        showAlert(err.message, 'error');
    }
}

async function handleConfigSubmit(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const updates = [
        { key: 'vat_rate', value: fd.get('vat_rate'), type: 'number' },
        { key: 'enable_cashier_printing', value: fd.get('enable_cashier_printing') === 'on', type: 'boolean' },
        { key: 'enable_cashier_today_revenue', value: fd.get('enable_cashier_today_revenue') === 'on', type: 'boolean' }
    ];

    try {
        for(let up of updates) {
            await fetch('api/admin/settings.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(up)
            });
        }
        showAlert('POS Logic Updated Successfully');
        location.reload();
    } catch (err) {
        showAlert(err.message, 'error');
    }
}

async function handleLogoUpload(e) {
    const file = e.target.files[0];
    if(!file) return;

    const fd = new FormData();
    fd.append('file', file);

    try {
        const res = await fetch('api/admin/settings.php?type=logo', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if(data.success) {
            document.getElementById('logoPreview').src = data.url;
            document.getElementById('tabFaviconPreview').src = data.url;
            showAlert('Logo Uploaded Successfully');
        }
    } catch (err) {
        showAlert(err.message, 'error');
    }
}

async function addCategory() {
    const name = document.getElementById('categoryName').value;
    if(!name) return;
    try {
        await fetch('api/admin/categories.php?type=menu', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name })
        });
        location.reload();
    } catch (err) { showAlert(err.message, 'error'); }
}

async function deleteCategory(id) {
    if(!confirm('Delete Category?')) return;
    try {
        await fetch(`api/admin/categories.php?type=menu&id=${id}`, { method: 'DELETE' });
        location.reload();
    } catch (err) { showAlert(err.message, 'error'); }
}

async function addTable() {
    const num = document.getElementById('tableNumber').value;
    const cap = document.getElementById('tableCapacity').value;
    if(!num || !cap) return;
    try {
        await fetch('api/admin/tables.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tableNumber: num, capacity: parseInt(cap) })
        });
        location.reload();
    } catch (err) { showAlert(err.message, 'error'); }
}

async function deleteTable(id) {
    if(!confirm('Delete Table?')) return;
    try {
        await fetch(`api/admin/tables.php?id=${id}`, { method: 'DELETE' });
        location.reload();
    } catch (err) { showAlert(err.message, 'error'); }
}
</script>

<?php renderFooter(); ?>
