<?php
/**
 * Reception & Guest Lifecycle Module
 */
require_once 'includes/layout.php';

requireAuth(['admin', 'receptionist']);

$title = "Reception Hub";

// Handle Check-in
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'check-in') {
        // Fayda ID Validation (16 digits)
        $faydaId = $_POST['faydaId'];
        if (!preg_match('/^[0-9]{16}$/', $faydaId)) {
            $message = "Error: Fayda ID must be exactly 16 digits.";
        } else {
            db('reception_requests')->create(['data' => [
                'id' => bin2hex(random_bytes(16)),
                'guestName' => $_POST['guestName'],
                'faydaId' => $faydaId,
                'roomNumber' => $_POST['roomNumber'],
                'checkIn' => date('Y-m-d H:i:s'),
                'stayDays' => (int)$_POST['stayDays'],
                'status' => 'staying',
                'idFront' => $_POST['idFront'] ?? null,
                'idBack' => $_POST['idBack'] ?? null,
                'profilePhoto' => $_POST['profilePhoto'] ?? null,
                'createdAt' => date('Y-m-d H:i:s'),
                'isDeleted' => false
            ]]);
            // Update room status
            db('rooms')->update([
                'where' => ['roomNumber' => $_POST['roomNumber']],
                'data' => ['status' => 'occupied']
            ]);
            $message = "Guest checked in successfully.";
        }
    }

    if ($_POST['action'] === 'extend') {
        $stayId = $_POST['stayId'];
        $extraDays = (int)$_POST['extraDays'];
        $stay = db('reception_requests')->findUnique(['where' => ['id' => $stayId]]);
        if ($stay) {
            db('reception_requests')->update([
                'where' => ['id' => $stayId],
                'data' => ['stayDays' => $stay['stayDays'] + $extraDays]
            ]);
            $message = "Stay extended by $extraDays days.";
        }
    }

    if ($_POST['action'] === 'checkout') {
        $stayId = $_POST['stayId'];
        $stay = db('reception_requests')->update([
            'where' => ['id' => $stayId],
            'data' => ['status' => 'checked-out']
        ]);
        // Free the room
        db('rooms')->update([
            'where' => ['roomNumber' => $stay['roomNumber']],
            'data' => ['status' => 'cleaning']
        ]);
        $message = "Check-out completed. Room set to cleaning.";
    }
}

try {
    $activeStays = db('reception_requests')->findMany(['where' => ['status' => 'staying', 'isDeleted' => false]]);
    $rooms = db('rooms')->findMany(['orderBy' => ['roomNumber' => 'asc']]);
} catch (Exception $e) {
    $activeStays = []; $rooms = [];
}

renderHeader($title);
?>

<div class="space-y-10 max-w-[1400px] mx-auto animate-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h1 class="text-3xl font-bold font-playfair tracking-tight text-white gold-glow">Reception Hub</h1>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gold/40">Guest lifecycle & stay management</p>
        </div>
        <button onclick="document.getElementById('checkin-modal').classList.toggle('hidden')" 
                class="gold-btn px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest text-charcoal shadow-2xl">
            Digital Check-In
        </button>
    </div>

    <?php if ($message): ?>
    <div class="glass p-4 rounded-2xl text-xs font-bold flex items-center gap-3 <?php echo strpos($message, 'Error') !== false ? 'border-red-500/30 text-red-400' : 'border-gold/30 text-gold'; ?>">
        <i data-lucide="<?php echo strpos($message, 'Error') !== false ? 'alert-circle' : 'check-circle'; ?>" class="w-4 h-4"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="glass p-6 rounded-[2rem] border border-white/5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gold/30 mb-2">Total In-House</p>
            <h3 class="text-3xl font-black text-white font-mono"><?php echo count($activeStays); ?></h3>
        </div>
        <div class="glass p-6 rounded-[2rem] border border-white/5">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gold/30 mb-2">Available Rooms</p>
            <h3 class="text-3xl font-black text-white font-mono"><?php 
                echo count(array_filter($rooms, fn($r) => ($r['status'] ?? '') === 'available')); 
            ?></h3>
        </div>
    </div>

    <!-- Active Stays Table -->
    <div class="glass rounded-[2.5rem] overflow-hidden shadow-2xl border border-white/5">
        <div class="px-8 py-6 border-b border-white/5 bg-white/[0.01]">
            <h3 class="font-bold text-white tracking-tight">Active Room Occupancy</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[10px] font-bold text-gold/30 uppercase tracking-widest border-b border-white/5">
                        <th class="px-8 py-5">Guest Name</th>
                        <th class="px-8 py-5">Fayda ID</th>
                        <th class="px-8 py-5">Room #</th>
                        <th class="px-8 py-5">Check-In</th>
                        <th class="px-8 py-5 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.02]">
                    <?php foreach ($activeStays as $stay): ?>
                    <tr class="text-sm hover:bg-white/[0.01] transition-colors group">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gold/10 border border-gold/20 overflow-hidden flex items-center justify-center">
                                    <?php if ($stay['profilePhoto']): ?>
                                        <img src=".<?php echo $stay['profilePhoto']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i data-lucide="user" class="w-4 h-4 text-gold/40"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="font-bold text-slate-200"><?php echo $stay['guestName']; ?></div>
                            </div>
                        </td>
                        <td class="px-8 py-5 font-mono text-xs text-gold/40"><?php echo substr($stay['faydaId'], 0, 4) . " •••• " . substr($stay['faydaId'], -4); ?></td>
                        <td class="px-8 py-5"><span class="bg-gold/5 px-3 py-1 rounded-lg text-gold font-bold text-[10px] border border-gold/10">ROOM <?php echo $stay['roomNumber']; ?></span></td>
                        <td class="px-8 py-5 text-muted-foreground text-xs"><?php echo date('M j, Y H:i', strtotime($stay['checkIn'])); ?></td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="extend">
                                    <input type="hidden" name="stayId" value="<?php echo $stay['id']; ?>">
                                    <input type="hidden" name="extraDays" value="1">
                                    <button type="submit" class="p-2 rounded-lg bg-gold/5 text-gold border border-gold/10 hover:bg-gold/10 transition-colors" title="Extend 1 Day">
                                        <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <form method="POST" class="inline" onsubmit="return confirm('Confirm check-out?')">
                                    <input type="hidden" name="action" value="checkout">
                                    <input type="hidden" name="stayId" value="<?php echo $stay['id']; ?>">
                                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-500/10 text-emerald-500 border border-emerald-500/10 hover:bg-emerald-500/20 text-[10px] font-black uppercase tracking-widest">Check Out</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Check-In Modal -->
<div id="checkin-modal" class="hidden fixed inset-0 z-[100] bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="glass w-full max-w-[500px] rounded-[2.5rem] p-10 border border-gold/10 animate-in fade-in zoom-in duration-300">
        <h2 class="text-2xl font-bold text-white font-playfair mb-8 gold-glow">Guest Intake (Digital)</h2>
        <form method="POST" class="space-y-5">
            <input type="hidden" name="action" value="check-in">
            
            <div class="flex items-center gap-6 mb-6">
                <div class="relative group">
                    <div id="profile-preview" class="w-24 h-24 rounded-2xl bg-gold/5 border-2 border-dashed border-gold/20 flex flex-col items-center justify-center text-gold/40 group-hover:bg-gold/10 transition-all cursor-pointer overflow-hidden">
                        <i data-lucide="camera" class="w-8 h-8 mb-1"></i>
                        <span class="text-[8px] font-black uppercase tracking-widest">Profile</span>
                    </div>
                    <input type="file" id="profile-upload" accept="image/*" class="hidden">
                    <input type="hidden" name="profilePhoto" id="profile-hidden">
                </div>
                <div class="flex-1 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative group">
                            <div id="idFront-preview" class="h-24 rounded-2xl bg-white/5 border border-white/10 flex flex-col items-center justify-center text-white/20 group-hover:bg-white/10 transition-all cursor-pointer overflow-hidden">
                                <i data-lucide="file-text" class="w-6 h-6 mb-1"></i>
                                <span class="text-[8px] font-black uppercase tracking-widest">ID FRONT</span>
                            </div>
                            <input type="file" id="idFront-upload" accept="image/*" class="hidden">
                            <input type="hidden" name="idFront" id="idFront-hidden">
                        </div>
                        <div class="relative group">
                            <div id="idBack-preview" class="h-24 rounded-2xl bg-white/5 border border-white/10 flex flex-col items-center justify-center text-white/20 group-hover:bg-white/10 transition-all cursor-pointer overflow-hidden">
                                <i data-lucide="file-text" class="w-6 h-6 mb-1"></i>
                                <span class="text-[8px] font-black uppercase tracking-widest">ID BACK</span>
                            </div>
                            <input type="file" id="idBack-upload" accept="image/*" class="hidden">
                            <input type="hidden" name="idBack" id="idBack-hidden">
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold uppercase tracking-widest text-gold/40 ml-1">Guest Full Name</label>
                <input type="text" name="guestName" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-gold/50">
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-bold uppercase tracking-widest text-gold/40 ml-1">Fayda ID (16 Digits)</label>
                <input type="text" name="faydaId" pattern="[0-9]{16}" maxlength="16" required placeholder="0000 0000 0000 0000"
                       class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white font-mono focus:outline-none focus:border-gold/50">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gold/40 ml-1">Room Allocation</label>
                    <select name="roomNumber" class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white appearance-none focus:outline-none focus:border-gold/50">
                        <?php foreach ($rooms as $room): 
                            if (($room['status'] ?? '') === 'available'): ?>
                        <option value="<?php echo $room['roomNumber']; ?>">Room <?php echo $room['roomNumber']; ?> (Floor <?php echo $room['floor'] ?? '?'; ?>)</option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gold/40 ml-1">Stay Duration</label>
                    <input type="number" name="stayDays" value="1" min="1" required class="w-full bg-white/5 border border-white/10 rounded-2xl py-3 px-4 text-sm text-white focus:outline-none focus:border-gold/50">
                </div>
            </div>

            <div class="pt-6 flex gap-3">
                <button type="button" onclick="document.getElementById('checkin-modal').classList.add('hidden')" class="flex-1 py-4 text-[10px] uppercase font-black tracking-widest text-gold/30 hover:text-gold transition-colors">Cancel</button>
                <button type="submit" class="gold-btn flex-1 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest text-charcoal">Complete Check-In</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Asset Upload Logic
    const setupUpload = (btnId, hiddenId, previewId) => {
        const btn = document.getElementById(btnId);
        const preview = document.getElementById(previewId);
        
        preview.addEventListener('click', () => btn.click());
        
        btn.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            
            preview.innerHTML = '<i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>';
            lucide.createIcons();
            
            try {
                const resp = await fetch('api/upload.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await resp.json();
                if (res.success) {
                    document.getElementById(hiddenId).value = res.path;
                    preview.innerHTML = `<img src=".${res.path}" class="w-full h-full object-cover">`;
                } else {
                    alert(res.message);
                }
            } catch (err) {
                alert('Upload error');
            }
        });
    };

    setupUpload('profile-upload', 'profile-hidden', 'profile-preview');
    setupUpload('idFront-upload', 'idFront-hidden', 'idFront-preview');
    setupUpload('idBack-upload', 'idBack-hidden', 'idBack-preview');
</script>

<?php renderFooter(); ?>
