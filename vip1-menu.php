<?php
require_once 'includes/layout.php';
requireAuth(['admin']);

$title = "VIP 1 Menu Management";
renderHeader($title);
?>

<div class="p-10 space-y-10">
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-3xl font-black font-playfair italic text-white tracking-tight gold-glow">VIP 1 Elite Menu</h1>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#d4af37]/40">Exclusive Culinary Management</p>
        </div>
        <a href="services.php" class="px-6 py-3 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest text-[#d4af37] hover:bg-white/10 transition-all">← Back to Services</a>
    </div>

    <div id="vip-menu-root"></div>
</div>

<!-- Modal shell borrowed from services.php concept -->
<div id="menu-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-6 overflow-y-auto">
    <div class="glass w-full max-w-4xl rounded-[3rem] p-12 border border-white/10 my-auto animate-in">
        <h2 id="menu-modal-title" class="text-3xl font-black text-white italic font-playfair mb-10 gold-glow">Add VIP Item</h2>
        <form onsubmit="AdminServices.saveMenuItem(event)" class="grid grid-cols-1 md:grid-cols-2 gap-10 text-white">
            <input type="hidden" name="id" id="menu-item-id">
            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[9px] font-black uppercase tracking-widest text-[#d4af37]/60 ml-2">Item Name</label>
                    <input type="text" name="name" id="menu-name" required class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 text-sm outline-none focus:border-[#d4af37]/40">
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-[#d4af37]/60 ml-2">Price</label>
                        <input type="number" name="price" id="menu-price" required class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 text-sm outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-[#d4af37]/60 ml-2">Category</label>
                        <select name="category" id="menu-category" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 text-sm outline-none appearance-none">
                            <option value="VIP 1 Special">VIP 1 Special</option>
                        </select>
                    </div>
                </div>
                <textarea name="description" id="menu-desc" placeholder="Gourmet description..." rows="3" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 text-sm outline-none"></textarea>
            </div>
            <div class="space-y-6">
                <div id="image-preview-area" class="w-full h-44 rounded-2xl bg-black/40 border-2 border-dashed border-white/5 flex flex-col items-center justify-center cursor-pointer overflow-hidden group">
                    <i data-lucide="camera" class="w-8 h-8 text-white/20 group-hover:text-[#d4af37]/40 transition-colors"></i>
                    <img id="menu-img-preview" class="hidden w-full h-full object-cover">
                </div>
                <input type="file" id="menu-img-upload" hidden accept="image/*">
                <input type="hidden" name="image" id="menu-img-base64">
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="document.getElementById('menu-modal').classList.add('hidden')" class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest text-white/30 hover:text-white">Cancel</button>
                    <button type="submit" class="flex-1 gold-pill py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-black shadow-2xl">Publish VIP Item</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="public/js/menu-manager.js"></script>
<script>
    // Stub AdminServices to satisfy MenuManager expectation
    window.AdminServices = {
        menuManager: new MenuManager({
            containerId: 'vip-menu-root',
            apiBaseUrl: 'api/admin/menu.php',
            collection: 'vip1Menu',
            categoryType: 'vip1-menu',
            publicMenuUrl: '/public-menu/vip1'
        }),
        openMenuModal: (item = {}) => {
            const modal = document.getElementById('menu-modal');
            document.getElementById('menu-item-id').value = item.id || '';
            document.getElementById('menu-name').value = item.name || '';
            document.getElementById('menu-price').value = item.price || '';
            document.getElementById('menu-desc').value = item.description || '';
            document.getElementById('menu-img-base64').value = item.image || '';
            const prev = document.getElementById('menu-img-preview');
            if (item.image) { prev.src = item.image; prev.classList.remove('hidden'); }
            else prev.classList.add('hidden');
            modal.classList.remove('hidden');
        },
        saveMenuItem: async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const data = Object.fromEntries(fd.entries());
            const id = data.id;
            const method = id ? 'PUT' : 'POST';
            const url = id ? `api/admin/menu.php?id=${id}` : `api/admin/menu.php?collection=vip1Menu`;
            if (!id) data.mainCategory = AdminServices.menuManager.state.activeTab;
            await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(data)});
            document.getElementById('menu-modal').classList.add('hidden');
            await AdminServices.menuManager.loadData();
            AdminServices.menuManager.render();
        }
    };

    AdminServices.menuManager.init();

    // Image helper
    document.getElementById('image-preview-area').onclick = () => document.getElementById('menu-img-upload').click();
    document.getElementById('menu-img-upload').onchange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            document.getElementById('menu-img-base64').value = ev.target.result;
            const prev = document.getElementById('menu-img-preview');
            prev.src = ev.target.result;
            prev.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    };
</script>

<?php renderFooter(); ?>
