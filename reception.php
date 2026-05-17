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
            db('receptionRequests')->create(['data' => [
                'id' => bin2hex(random_bytes(16)),
                'guestName' => $_POST['guestName'],
                'faydaId' => $faydaId,
                'roomNumber' => $_POST['roomNumber'],
                'checkIn' => date('Y-m-d H:i:s'),
                'stayDays' => (int)$_POST['stayDays'],
                'status' => 'staying',
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
}

try {
    $activeStays = db('receptionRequests')->findMany(['where' => ['status' => 'staying', 'isDeleted' => false]]);
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
                            <div class="font-bold text-slate-200"><?php echo $stay['guestName']; ?></div>
                        </td>
                        <td class="px-8 py-5 font-mono text-xs text-gold/40"><?php echo substr($stay['faydaId'], 0, 4) . " •••• " . substr($stay['faydaId'], -4); ?></td>
                        <td class="px-8 py-5"><span class="bg-gold/5 px-3 py-1 rounded-lg text-gold font-bold text-[10px] border border-gold/10">ROOM <?php echo $stay['roomNumber']; ?></span></td>
                        <td class="px-8 py-5 text-muted-foreground text-xs"><?php echo date('M j, Y H:i', strtotime($stay['checkIn'])); ?></td>
                        <td class="px-8 py-5 text-right">
                            <span class="text-[10px] font-black uppercase text-emerald-500 bg-emerald-500/5 px-2 py-1 rounded-full border border-emerald-500/10">Active Stay</span>
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

<?php renderFooter(); ?>
