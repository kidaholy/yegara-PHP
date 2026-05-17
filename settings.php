<?php
/**
 * Refined Settings / Menu Management Module
 */
require_once 'includes/layout.php';

requireAuth(['admin']);

$title = "System Settings";

// Handle Menu Management Actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add-category') {
        db('menu_categories')->create(['data' => [
            'id' => bin2hex(random_bytes(16)),
            'name' => $_POST['name'],
            'createdAt' => date('Y-m-d H:i:s')
        ]]);
        $message = "Category added.";
    }

    if ($action === 'add-item') {
        db('menu_items')->create(['data' => [
            'id' => bin2hex(random_bytes(16)),
            'name' => $_POST['name'],
            'price' => (float)$_POST['price'],
            'categoryId' => $_POST['categoryId'],
            'mainCategory' => $_POST['mainCategory'],
            'isDeleted' => false,
            'createdAt' => date('Y-m-d H:i:s')
        ]]);
        $message = "Menu item added.";
    }
}

try {
    $categories = db('menu_categories')->findMany(['orderBy' => ['name' => 'asc']]);
    $items = db('menu_items')->findMany(['where' => ['isDeleted' => false], 'orderBy' => ['name' => 'asc']]);
} catch (Exception $e) {
    $categories = []; $items = [];
}

renderHeader($title);
?>

<div class="space-y-10 max-w-[1400px] mx-auto animate-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h1 class="text-3xl font-bold font-playfair tracking-tight text-white">Configurable Offerings</h1>
            <p class="text-xs text-muted-foreground font-medium opacity-50">Standardize your service menu and room types</p>
        </div>
        <div class="flex gap-4">
             <button onclick="document.getElementById('add-cat-modal').classList.remove('hidden')" 
                    class="bg-white/5 border border-white/10 text-white px-6 py-3 rounded-2xl text-xs font-bold hover:bg-white/10 transition-all">
                New Category
            </button>
            <button onclick="document.getElementById('add-item-modal').classList.remove('hidden')" 
                    class="bg-white text-slate-950 px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl">
                Add Menu Item
            </button>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="bg-blue-500/10 border border-blue-500/20 text-blue-500 p-4 rounded-2xl text-xs font-bold flex items-center gap-3">
        <i data-lucide="info" class="w-4 h-4"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Categories List -->
        <div class="lg:col-span-1 space-y-6">
            <h3 class="font-bold text-white text-lg tracking-tight">Access Points (Categories)</h3>
            <div class="space-y-3">
                <?php foreach ($categories as $cat): ?>
                <div class="glass p-4 rounded-2xl border border-white/5 flex items-center justify-between group">
                    <span class="text-sm font-semibold text-slate-300"><?php echo $cat['name']; ?></span>
                    <button class="opacity-0 group-hover:opacity-100 transition-all text-red-500/50 hover:text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Menu Items Table -->
        <div class="lg:col-span-2 glass rounded-[2.5rem] border border-white/5 overflow-hidden shadow-2xl">
            <div class="px-8 py-6 border-b border-white/5 bg-white/[0.01]">
                <h3 class="font-bold text-white text-lg tracking-tight">Active Inventory</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest border-b border-white/5">
                            <th class="px-8 py-5 opacity-40">Item Name</th>
                            <th class="px-8 py-5 opacity-40">Category</th>
                            <th class="px-8 py-5 opacity-40 text-right">Unit Price</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        <?php foreach ($items as $item): ?>
                        <tr class="text-sm hover:bg-white/[0.02] transition-colors">
                            <td class="px-8 py-4 font-bold text-slate-200"><?php echo $item['name']; ?></td>
                            <td class="px-8 py-4">
                                <span class="bg-white/5 px-3 py-1 rounded-lg text-[10px] font-bold uppercase text-muted-foreground border border-white/5">
                                    <?php echo $item['mainCategory']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-4 text-right font-black text-white">
                                <?php echo number_format($item['price'], 2); ?> <span class="opacity-30">Br</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="add-cat-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass w-full max-w-[400px] rounded-[2.5rem] p-10 border border-white/10 shadow-3xl">
        <h2 class="text-2xl font-bold text-white font-playfair mb-6">New Category</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add-category">
            <input type="text" name="name" placeholder="Category Name (e.g. Pasta, Beer)" required 
                   class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-blue-500/50">
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="document.getElementById('add-cat-modal').classList.add('hidden')" class="flex-1 py-4 text-[10px] uppercase font-black text-muted-foreground">Cancel</button>
                <button type="submit" class="flex-1 py-4 bg-white text-slate-950 rounded-2xl text-[10px] font-black uppercase tracking-widest">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Item Modal -->
<div id="add-item-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass w-full max-w-[450px] rounded-[2.5rem] p-10 border border-white/10 shadow-3xl">
        <h2 class="text-2xl font-bold text-white font-playfair mb-6">New Menu Item</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add-item">
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Item Name</label>
                <input type="text" name="name" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-blue-500/50">
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Unit Price (Br)</label>
                <input type="number" step="0.01" name="price" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-blue-500/50">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Category</label>
                    <select name="categoryId" class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white appearance-none">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase text-muted-foreground ml-1">Main Group</label>
                    <select name="mainCategory" class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white appearance-none">
                        <option value="Food">Food (Kitchen)</option>
                        <option value="Drinks">Drinks (Bar)</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-6">
                <button type="button" onclick="document.getElementById('add-item-modal').classList.add('hidden')" class="flex-1 py-4 text-[10px] uppercase font-black text-muted-foreground">Cancel</button>
                <button type="submit" class="flex-1 py-4 bg-white text-slate-950 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-2xl">Confirm Item</button>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
