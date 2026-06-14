<?php
require_once 'includes/layout.php';
require_once __DIR__ . '/includes/SettingsManager.php';

$manager = new SettingsManager();
extract($manager->getBrandingVars());

// Get Tier if provided (e.g. menu.php?tier=vip1)
$tierId = $_GET['tier'] ?? 'standard';
$type = ($tierId === 'standard') ? 'menuItems' : "{$tierId}Menu";

$items = db($type)->findMany(['where' => ['isDeleted' => false]]) ?: [];
$categories = db('categories')->findMany(['where' => ['group' => 'menu']]) ?: [];

// Filter out deleted items and unavailable items
$items = array_filter($items, fn($i) => !($i['isDeleted'] ?? false) && ($i['available'] ?? true) !== false);

// SPECIAL: If standard menu, filter OUT anything flagged as VIP or containing 'VIP' in name/category
if ($tierId === 'standard') {
    $items = array_values(array_filter($items, function($i) {
        $name = strtolower($i['name'] ?? '');
        $cat = strtolower($i['category'] ?? '');
        return !(strpos($name, 'vip') !== false || strpos($cat, 'vip') !== false || ($i['isVIP'] ?? false));
    }));
}

// Determine Dynamic Tier Name
$displayTierName = "Standard Menu";
if ($tierId !== 'standard') {
    require_once 'includes/menu-tiers.php';
    foreach (getMenuTiers() as $t) {
        if ($t['filePrefix'] === $tierId) {
            $displayTierName = $t['name'];
            break;
        }
    }
}

$title = $appName . " - " . $displayTierName;
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #070807; color: #f3f4f6; }
        .font-serif { font-family: 'Cormorant Garamond', serif; }
        .luxury-gradient { background: linear-gradient(135deg, #c5a059 0%, #d4af37 50%, #c5a059 100%); }
        .luxury-text { background: linear-gradient(to right, #c5a059, #d4af37, #c5a059); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        ::-webkit-scrollbar { width: 0px; }
        .item-card { animation: fadeIn .6s cubic-bezier(.4,0,.2,1) both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="selection:bg-[#c5a059]/30">

    <!-- Hero Header -->
    <header class="relative h-[40vh] w-full flex items-center justify-center overflow-hidden border-b border-[#c5a059]/20">
        <img src="assets/menu_hero.png" alt="Abe Hotel" class="absolute inset-0 w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-gradient-to-t from-[#070807] via-transparent to-black/60"></div>
        
        <div class="relative z-10 text-center px-6">
            <div class="flex items-center justify-center gap-3 mb-4">
                <span class="w-12 h-[1px] bg-[#c5a059]/40"></span>
                <span class="text-[#c5a059] font-serif italic text-xl">Chef's Selection</span>
                <span class="w-12 h-[1px] bg-[#c5a059]/40"></span>
            </div>
            <h1 class="text-6xl font-serif italic text-[#c5a059] mb-2 drop-shadow-2xl"><?php echo htmlspecialchars($displayTierName); ?></h1>
            <p class="text-[10px] font-black uppercase tracking-[0.5em] text-[#c5a059]/60"><?php echo htmlspecialchars($appName); ?></p>
        </div>
    </header>

    <!-- Navigation / Categories -->
    <nav class="sticky top-0 z-50 bg-[#070807]/80 backdrop-blur-xl border-b border-[#c5a059]/10 pt-6 pb-2 px-6">
        <div class="max-w-screen-xl mx-auto">
            <div class="flex items-center gap-4 mb-6">
                <button onclick="setMainCat('Food')" id="tab-food" class="flex-1 py-3 text-xs font-black uppercase tracking-widest border border-[#c5a059]/30 rounded-xl transition-all duration-300">Food</button>
                <button onclick="setMainCat('Drinks')" id="tab-drinks" class="flex-1 py-3 text-xs font-black uppercase tracking-widest border border-gray-800 text-gray-500 rounded-xl transition-all duration-300">Drinks</button>
            </div>
            
            <!-- Sub Categories (Horizontal Scroll) -->
            <div id="sub-cat-container" class="flex items-center gap-4 overflow-x-auto pb-4 no-scrollbar scroll-smooth">
                <!-- Dynamically populated -->
            </div>
        </div>
    </nav>

    <!-- Main Menu Content -->
    <main class="max-w-screen-xl mx-auto px-6 py-12">
        <div id="menu-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <!-- Dynamically populated -->
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-20 text-center border-t border-gray-900 bg-[#0a0a0a]">
        <div class="w-16 h-16 rounded-full border border-[#c5a059]/30 flex flex-col items-center justify-center p-1 bg-black/40 mx-auto mb-6 shadow-xl">
            <span class="text-[10px] font-black tracking-widest text-[#c5a059] leading-none mb-0.5"><?php echo htmlspecialchars(strtoupper(substr($appName, 0, 3))); ?></span>
        </div>
        <p class="text-[10px] font-bold text-gray-600 uppercase tracking-[0.3em] mb-4 italic px-8">High-End Dining, Curated Spirits, and Unmatched Luxury.</p>
        <div class="flex justify-center gap-6 text-gray-700">
            <i data-lucide="instagram" class="w-4 h-4 hover:text-[#c5a059] transition-colors"></i>
            <i data-lucide="facebook" class="w-4 h-4 hover:text-[#c5a059] transition-colors"></i>
            <i data-lucide="map-pin" class="w-4 h-4 hover:text-[#c5a059] transition-colors"></i>
        </div>
    </footer>

    <script>
        // Deduplicate and Sort
        const rawItems = <?php echo json_encode($items); ?>;
        
        // 1. Deduplicate by (Name + Category + Price)
        const seen = new Set();
        const deduplicated = rawItems.filter(item => {
            const key = `${(item.name||'').trim()}|${(item.category||'').trim()}|${item.price}`;
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        });

        // 2. Sort by menuId
        const allItems = deduplicated.sort((a,b) => (Number(a.menuId)||0) - (Number(b.menuId)||0));
        
        const allCategories = <?php echo json_encode($categories); ?>;
        
        let state = {
            mainCat: 'Food',
            subCat: 'All'
        };

        function setMainCat(cat) {
            state.mainCat = cat;
            state.subCat = 'All';
            updateUI();
        }

        function setSubCat(cat) {
            state.subCat = cat;
            renderItems();
            
            // Highlight sub-cat
            document.querySelectorAll('.sub-cat-btn').forEach(btn => {
                const on = btn.dataset.cat === cat;
                btn.className = `sub-cat-btn flex-shrink-0 px-6 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest border transition-all ${on ? 'bg-[#c5a059]/10 text-[#c5a059] border-[#c5a059]/40 shadow-sm' : 'border-transparent text-gray-500 hover:text-gray-300'}`;
            });
        }

        function updateUI() {
            // Update Main Tabs
            const foodBtn = document.getElementById('tab-food');
            const drinkBtn = document.getElementById('tab-drinks');
            
            [foodBtn, drinkBtn].forEach(b => {
                const on = b.id === `tab-${state.mainCat.toLowerCase()}`;
                b.className = `flex-1 py-3 text-xs font-black uppercase tracking-widest border rounded-xl transition-all duration-300 ${on ? 'bg-[#c5a059]/10 text-[#c5a059] border-[#c5a059]/40 shadow-[0_0_20px_rgba(197,160,89,0.1)]' : 'border-gray-800 text-gray-500 hover:border-gray-700'}`;
            });

            // Render Sub Cats as a Luxury Dropdown
            const filteredByMain = allItems.filter(i => i.mainCategory === state.mainCat);
            const subCats = [...new Set(filteredByMain.map(i => (i.category||'').trim()))]
                .filter(Boolean)
                .sort((a, b) => a.localeCompare(b));
            
            const subContainer = document.getElementById('sub-cat-container');
            subContainer.innerHTML = `
                <div class="relative w-full max-w-xs mx-auto">
                    <select onchange="setSubCat(this.value)" class="w-full appearance-none bg-black/40 border border-[#c5a059]/30 text-[#c5a059] px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] focus:outline-none focus:border-[#c5a059] transition-all cursor-pointer">
                        <option value="All">All Categories</option>
                        ${subCats.map(c => `<option value="${c}">${c.toUpperCase()}</option>`).join('')}
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none opacity-60">
                        <i data-lucide="chevron-down" class="w-3 h-3 text-[#c5a059]"></i>
                    </div>
                </div>
            `;
            renderItems();
            lucide.createIcons();
        }

        function renderItems() {
            const grid = document.getElementById('menu-grid');
            const filtered = allItems.filter(i => i.mainCategory === state.mainCat && (state.subCat === 'All' || (i.category||'').trim() === state.subCat));

            grid.innerHTML = filtered.map((item, idx) => {
                const qtyText = (item.reportQuantity && item.reportUnit) ? `${item.reportQuantity} ${item.reportUnit}` : '';
                return `
                <div class="item-card group" style="animation-delay: ${idx * 0.03}s">
                    <div class="relative h-64 w-full bg-gray-900 rounded-2xl overflow-hidden border border-gray-800 group-hover:border-[#c5a059]/20 transition-all duration-500">
                        ${item.image ? `<img src="${item.image}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 opacity-80 group-hover:opacity-100">` : `
                        <div class="w-full h-full flex items-center justify-center border-t border-[#c5a059]/10">
                            <span class="text-gray-800 font-serif italic text-4xl">${item.name.charAt(0)}</span>
                        </div>`}
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent opacity-80 group-hover:opacity-60 transition-opacity"></div>
                        
                        <div class="absolute top-4 right-4 flex items-center gap-2">
                            <span class="text-[9px] font-black text-[#c5a059] bg-black/60 px-2 py-1 rounded-md border border-[#c5a059]/20 backdrop-blur-sm">#${item.menuId || '?'}</span>
                        </div>

                        <div class="absolute bottom-6 left-6 right-6">
                             <div class="flex items-center justify-between gap-2 mb-1">
                                <p class="text-[9px] uppercase font-bold tracking-[0.2em] text-[#c5a059]">${(item.category || 'General').trim()}</p>
                                ${qtyText ? `<span class="bg-[#c5a059]/10 text-[#c5a059] text-[8px] font-black px-2 py-0.5 rounded-full border border-[#c5a059]/20 uppercase tracking-widest">${qtyText}</span>` : ''}
                             </div>
                             <h3 class="text-lg font-serif italic text-gray-100">${item.name}</h3>
                        </div>
                    </div>
                    <div class="mt-4 flex items-end justify-between px-2">
                        <div class="flex-1 min-w-0 pr-4">
                             <p class="text-[10px] text-gray-600 italic leading-snug line-clamp-2">${item.description || 'Curated with passion using authentic seasonal ingredients.'}</p>
                        </div>
                        <span class="text-lg font-serif italic text-[#c5a059] shrink-0">${Number(item.price).toLocaleString()} Br</span>
                    </div>
                </div>
            `;}).join('');
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateUI();
            lucide.createIcons();
        });
    </script>
</body>
</html>
