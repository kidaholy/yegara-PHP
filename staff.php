<?php
/**
 * Refined Staff Management Module
 */
require_once 'includes/layout.php';

requireAuth(['admin']);

$title = "Staff Management";

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        db('users')->create(['data' => [
            'id' => bin2hex(random_bytes(16)),
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => password_hash($_POST['password'], PASSWORD_BCRYPT),
            'role' => $_POST['role'],
            'isActive' => true,
            'createdAt' => date('Y-m-d H:i:s')
        ]]);
        $message = "Staff member created successfully.";
    }

    if ($action === 'toggle') {
        $id = $_POST['id'];
        $user = db('users')->findUnique(['where' => ['id' => $id]]);
        db('users')->update([
            'where' => ['id' => $id],
            'data' => ['isActive' => !($user['isActive'] ?? true)]
        ]);
    }
}

try {
    $allStaff = db('users')->findMany(['orderBy' => ['name' => 'asc']]);
} catch (Exception $e) {
    $allStaff = [];
}

renderHeader($title);
?>

<div class="space-y-10 max-w-[1400px] mx-auto animate-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h1 class="text-3xl font-bold font-playfair tracking-tight text-white">Staff Management</h1>
            <p class="text-xs text-muted-foreground font-medium opacity-50">Manage access and account status for all employees</p>
        </div>
        <button onclick="document.getElementById('add-staff-modal').classList.toggle('hidden')" 
                class="bg-white text-slate-950 px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-3 shadow-2xl">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Add Employee
        </button>
    </div>

    <?php if ($message): ?>
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 p-4 rounded-2xl text-xs font-bold flex items-center gap-3">
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <!-- Staff Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($allStaff as $staff): 
            $isActive = $staff['isActive'] ?? true;
        ?>
        <div class="glass p-6 rounded-[2rem] border border-white/5 flex flex-col items-center text-center relative group overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-purple-500 <?php echo $isActive ? 'opacity-50' : 'opacity-0'; ?>"></div>
            
            <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-slate-800 to-slate-900 border border-white/10 flex items-center justify-center text-2xl font-bold text-white mb-4 relative">
                <?php echo strtoupper(substr($staff['name'], 0, 1)); ?>
                <div class="absolute bottom-0 right-0 w-5 h-5 rounded-full border-[3px] border-[#030712] <?php echo $isActive ? 'bg-emerald-500' : 'bg-slate-600'; ?>"></div>
            </div>

            <h3 class="font-bold text-white tracking-tight"><?php echo $staff['name']; ?></h3>
            <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground opacity-40 mb-6"><?php echo $staff['role']; ?></p>

            <div class="w-full space-y-2 mt-auto">
                <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/[0.03] border border-white/5 text-[10px] font-medium text-muted-foreground">
                    <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                    <span class="truncate"><?php echo $staff['email']; ?></span>
                </div>
                <form method="POST" class="w-full">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?php echo $staff['id']; ?>">
                    <button type="submit" class="w-full py-2.5 rounded-xl border border-white/5 text-[10px] font-bold uppercase tracking-wider transition-all <?php echo $isActive ? 'text-red-400 hover:bg-red-500/10 hover:border-red-500/20' : 'text-emerald-400 hover:bg-emerald-500/10 hover:border-emerald-500/20'; ?>">
                        <?php echo $isActive ? 'Suspend Access' : 'Restore Access'; ?>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Simple Modal -->
<div id="add-staff-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="glass w-full max-w-[450px] rounded-[2.5rem] p-10 border border-white/10 shadow-3xl animate-in fade-in zoom-in duration-300">
        <h2 class="text-2xl font-bold text-white font-playfair mb-8">Add New Employee</h2>
        <form method="POST" class="space-y-5">
            <input type="hidden" name="action" value="create">
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Full Name</label>
                <input type="text" name="name" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-blue-500/50">
            </div>
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Email Address</label>
                <input type="email" name="email" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-blue-500/50">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Password</label>
                    <input type="password" name="password" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-blue-500/50">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Role</label>
                    <select name="role" class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-blue-500/50 appearance-none">
                        <option value="cashier">Cashier</option>
                        <option value="chef">Chef</option>
                        <option value="bar">Bar</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="pt-6 flex gap-3">
                <button type="button" onclick="document.getElementById('add-staff-modal').classList.add('hidden')" class="flex-1 py-4 rounded-2xl border border-white/5 text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:bg-white/5">Cancel</button>
                <button type="submit" class="flex-1 py-4 bg-white text-slate-950 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-2xl">Confirm Staff</button>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>
