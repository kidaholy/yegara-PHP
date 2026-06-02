<?php
/**
 * Admin Settings Hub — Branding, Categories, & POS Layout
 */
require_once 'includes/layout.php';
require_once 'includes/auth.php';

// Auth: admin or specifically permitted
requireAuth(['admin']); // Simple admin check for now, can be expanded to permissions

renderHeader("System Settings");

// Fetch initial public settings for the form
$publicSettings = [];
try {
    require_once 'includes/JsonDB.php';
    $all = db('settings')->findMany();
    foreach ($all as $s) {
        $publicSettings[$s['key']] = $s['value'];
    }
} catch (Exception $e) {}

?>

<script>
  window.currentSettings = <?= json_encode($publicSettings) ?>;
</script>

<div class="min-h-screen bg-[#0f1110] text-gray-300 font-sans selection:bg-[#d4af37]/30">
    <div class="max-w-[1600px] mx-auto px-4 lg:px-10 py-10">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- LEFT SIDEBAR: Live Preview (lg:col-span-4) -->
            <aside class="lg:col-span-4 space-y-8 lg:sticky lg:top-24 h-fit order-2 lg:order-1 mt-10 lg:mt-0">
                <div class="glass p-10 rounded-[3rem] border border-white/5 bg-[#151716] shadow-3xl text-center relative overflow-hidden group">
                     <!-- Preview Label -->
                     <div class="absolute top-6 left-6 text-[8px] font-black uppercase tracking-widest text-gray-700">Live Branding Preview</div>
                     
                     <!-- Actual Logo Preview -->
                     <div class="relative z-10 py-6">
                         <div id="preview-logo-container" class="w-32 h-32 mx-auto mb-6 flex items-center justify-center transition-transform duration-500 group-hover:scale-105">
                             <img id="preview-logo" src="" alt="Logo" class="max-w-full max-h-full object-contain">
                         </div>
                         <h2 id="preview-app-name" class="text-2xl font-black font-playfair italic text-[#f3cf7a] mb-1">...</h2>
                         <p id="preview-app-tagline" class="text-[10px] uppercase font-black tracking-[0.3em] text-gray-500">...</p>
                     </div>

                     <!-- Mock Navbar Preview -->
                     <div class="mt-10 pt-10 border-t border-white/5 text-left">
                         <p class="text-[9px] font-black uppercase tracking-widest text-gray-700 mb-4 px-2">Navigation Preview</p>
                         <div class="bg-black/40 rounded-2xl p-4 border border-white/5 flex items-center justify-between">
                             <div class="flex items-center gap-3">
                                 <img id="preview-nav-logo" src="" class="w-8 h-8 object-contain">
                                 <span id="preview-nav-name" class="text-xs font-black text-white">...</span>
                             </div>
                             <div class="flex gap-2">
                                 <div class="w-4 h-4 rounded-full bg-white/5"></div>
                                 <div class="w-4 h-4 rounded-full bg-white/5"></div>
                             </div>
                         </div>
                     </div>

                     <!-- Mock Browser Tab Preview -->
                     <div class="mt-8 text-left">
                         <p class="text-[9px] font-black uppercase tracking-widest text-gray-700 mb-4 px-2">Browser Tab</p>
                         <div class="bg-[#1a1a1a] rounded-t-xl py-2 px-4 border-l border-r border-t border-white/10 flex items-center gap-3 w-48">
                             <img id="preview-favicon" src="" class="w-3.5 h-3.5 object-contain">
                             <span id="preview-tab-name" class="text-[10px] font-bold text-gray-400 truncate tracking-tight">...</span>
                         </div>
                     </div>
                </div>

                <!-- Branding Tips Card -->
                <div class="glass p-8 rounded-[2.5rem] border border-white/5 bg-black/20">
                    <h3 class="flex items-center gap-3 text-xs font-black uppercase tracking-widest text-[#d4af37] mb-6">
                        <i data-lucide="info" class="w-4 h-4"></i> Logo Design Tips
                    </h3>
                    <ul class="space-y-4 text-[10px] font-bold text-gray-500 leading-relaxed">
                        <li class="flex gap-3"><span class="text-[#d4af37]">01</span> Use square (1:1) aspect ratio for best fit.</li>
                        <li class="flex gap-3"><span class="text-[#d4af37]">02</span> Minimum resolution of 200x200px recommended.</li>
                        <li class="flex gap-3"><span class="text-[#d4af37]">03</span> PNG or WEBP with transparent background is ideal.</li>
                        <li class="flex gap-3"><span class="text-[#d4af37]">04</span> File size limit is 500KB after compression.</li>
                    </ul>
                </div>
            </aside>

            <!-- RIGHT PANEL: Tabs & Forms (lg:col-span-8) -->
            <main class="lg:col-span-8 space-y-8 order-1 lg:order-2">
                
                <!-- Main Card Header & Navigation -->
                <div class="glass rounded-[2.5rem] border border-white/5 bg-[#151716] shadow-3xl overflow-hidden">
                    <div class="px-8 pt-8 pb-0 border-b border-white/5">
                         <h2 class="text-2xl font-black font-playfair italic text-[#f3cf7a] mb-8">Management Hub</h2>
                         <div class="flex gap-8 overflow-x-auto no-scrollbar">
                             <button onclick="AdminSettings.setTab('branding')" id="tab-btn-branding"
                                 class="settings-tab-btn pb-5 text-[10px] font-black uppercase tracking-[0.2em] transition-all border-b-2 border-[#d4af37] text-white">Branding & Logic</button>
                             <button onclick="AdminSettings.setTab('categories')" id="tab-btn-categories"
                                 class="settings-tab-btn pb-5 text-[10px] font-black uppercase tracking-[0.2em] transition-all border-b-2 border-transparent text-gray-500 hover:text-white">Categories</button>
                             <button onclick="AdminSettings.setTab('tables')" id="tab-btn-tables"
                                 class="settings-tab-btn pb-5 text-[10px] font-black uppercase tracking-[0.2em] transition-all border-b-2 border-transparent text-gray-500 hover:text-white">Floors & Tables</button>
                         </div>
                    </div>

                    <div id="settings-content-panel" class="p-8 lg:p-12 min-h-[600px]">
                        <!-- Injected by public/js/admin-settings.js -->
                         <div class="flex flex-col items-center justify-center py-40 animate-pulse text-gray-700">
                             <i data-lucide="loader-2" class="w-12 h-12 animate-spin mb-6 stroke-[1px]"></i>
                             <p class="text-[10px] font-black uppercase tracking-[0.5em]">Initializing Config...</p>
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

/* Custom Toggle Switch for Luxury UI */
.toggle-pill {
    @apply relative w-12 h-6 rounded-full transition-all duration-500 cursor-pointer border border-white/10;
}
.toggle-pill.active {
    @apply bg-[#4ade80]/20 border-[#4ade80]/30;
}
.toggle-pill .dot {
    @apply absolute top-1 left-1 w-3.5 h-3.5 rounded-full bg-gray-600 transition-all duration-500;
}
.toggle-pill.active .dot {
    @apply left-7 bg-[#4ade80] shadow-[0_0_10px_rgba(74,222,128,0.4)];
}

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.tab-content-anim { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>

<script src="public/js/admin-settings.js"></script>
<?php renderFooter(); ?>
