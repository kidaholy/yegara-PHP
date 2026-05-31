<?php
/**
 * Staff Management - Abe Hotel
 * High-Fidelity "Luxury-First" Edition
 */
require_once 'includes/layout.php';
requireAuth(['admin']); // spec: users:view or admin

$title = "Staff Management";
renderHeader($title);

$currentUser = getCurrentUser();
?>

<div class="min-h-screen w-full bg-[#0f1110] p-6 lg:p-12 flex justify-center">
    <div class="max-w-screen-2xl w-full space-y-12">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            <!-- SIDEBAR (lg:col-span-3) -->
            <div class="lg:col-span-3 space-y-8 sticky top-24">
                
                <!-- Staff Header Card -->
                <div class="glass p-8 rounded-[2.5rem] border border-white/5 bg-[#151716] shadow-2xl relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-[0.03] group-hover:rotate-12 transition-transform duration-1000">
                        <i data-lucide="users" class="w-32 h-32 text-[#d4af37]"></i>
                    </div>
                    
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-[#1a1712] border border-[#d4af37]/20 flex items-center justify-center text-[#d4af37]">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-2xl font-black font-playfair italic text-[#f3cf7a]">Staff</h2>
                            <p id="staff-count" class="text-[9px] uppercase font-black tracking-widest text-gray-500">Total Active Staff: ...</p>
                        </div>
                    </div>

                    <button onclick="openCreateModal()" class="w-full py-4 rounded-2xl bg-gradient-to-r from-[#d4af37] to-[#f3cf7a] text-black font-black text-[11px] uppercase tracking-[0.2em] shadow-[0_10px_30px_rgba(212,175,55,0.2)] hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add New Member
                    </button>
                </div>

                <!-- Permissions Info Card -->
                <div class="glass p-8 rounded-[2.5rem] border border-white/5 bg-[#151716]/40 shadow-xl hidden lg:block relative overflow-hidden">
                    <div class="absolute -left-6 -top-6 opacity-[0.03]">
                        <i data-lucide="shield-check" class="w-24 h-24 text-blue-400"></i>
                    </div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-[#d4af37] mb-4">Security Notice</h3>
                    <p class="text-[10px] text-gray-500 leading-relaxed font-bold uppercase tracking-wider">
                        Staff roles define granular access to orders, financial records, and core infrastructure. Use custom roles for limited agency permissions.
                    </p>
                </div>
            </div>

            <!-- MAIN PANEL (lg:col-span-9) -->
            <div class="lg:col-span-9">
                <!-- Grid Loader -->
                <div id="grid-loader" class="flex flex-col items-center justify-center py-40 animate-pulse">
                    <i data-lucide="refresh-cw" class="w-12 h-12 text-[#d4af37] animate-spin mb-6"></i>
                    <p class="text-[10px] uppercase font-black tracking-[0.4em] text-gray-500">Assembling Team...</p>
                </div>

                <!-- User Grid -->
                <div id="user-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    <!-- Injected by public/js/admin-users.js -->
                </div>
            </div>

        </div>

    </div>
</div>

<!-- CREATE/EDIT MODAL -->
<div id="user-modal" class="fixed inset-0 z-[100] flex items-center justify-center px-4 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-md" onclick="closeModal()"></div>
    
    <div class="glass w-full max-w-xl rounded-[2.5rem] bg-[#151716] border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.8)] relative z-10 overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Head -->
        <div class="p-8 pb-4 flex items-center justify-between">
            <div>
                <h2 id="form-title" class="text-3xl font-black font-playfair italic text-[#f3cf7a]">New Member</h2>
                <p class="text-[9px] uppercase font-black tracking-widest text-gray-500 mt-1">Personnel Configuration</p>
            </div>
            <button onclick="closeModal()" class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-white/40 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-8 pb-10">
            <form id="user-form" onsubmit="handleFormSubmit(event)" class="space-y-8">
                <!-- Identity -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-black tracking-widest text-gray-500 pl-4">Display Name</label>
                        <input type="text" name="name" required class="w-full bg-[#0f1110] border border-white/5 rounded-2xl p-4 text-sm font-bold text-white focus:border-[#d4af37]/50 focus:outline-none transition-all placeholder:text-white/10" placeholder="e.g. John Doe">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] uppercase font-black tracking-widest text-gray-500 pl-4">Email Address</label>
                        <input type="email" name="email" required class="w-full bg-[#0f1110] border border-white/5 rounded-2xl p-4 text-sm font-bold text-white focus:border-[#d4af37]/50 focus:outline-none transition-all placeholder:text-white/10" placeholder="e.g. john@abehotel.com">
                    </div>
                </div>

                <!-- Access Level -->
                <div class="space-y-4">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-500 pl-4">Access Level</label>
                    <div id="role-selector" class="grid grid-cols-4 gap-3">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Floor Assignment (Conditional) -->
                <div id="floor-section" class="space-y-2 hidden">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-500 pl-4">Assigned Floor</label>
                    <select id="floor-select" name="floor-select" class="w-full bg-[#0f1110] border border-white/5 rounded-2xl p-4 text-sm font-bold text-white focus:border-[#d4af37]/50 focus:outline-none transition-all appearance-none">
                        <!-- Injected by JS -->
                    </select>
                </div>

                <!-- Kitchen Categories (Conditional) -->
                <div id="category-section" class="space-y-4 hidden">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-500 pl-4">Assigned Stations</label>
                    <div id="category-list" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Granular Privileges (Conditional) -->
                <div id="permission-section" class="space-y-4 hidden">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-500 pl-4">Granular Privileges</label>
                    <div id="permission-grid" class="grid grid-cols-2 gap-3 p-6 rounded-[2rem] border border-[#d4af37]/10 bg-[#0f1110]/50 max-h-[300px] overflow-y-auto custom-scrollbar">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label class="text-[10px] uppercase font-black tracking-widest text-gray-500 pl-4">Security (Password)</label>
                    <div class="flex gap-3">
                        <input type="text" name="password" class="flex-1 bg-[#0f1110] border border-white/5 rounded-2xl p-4 text-sm font-mono font-bold text-[#d4af37] focus:border-[#d4af37]/50 focus:outline-none transition-all placeholder:text-white/10" placeholder="••••••••">
                        <button type="button" onclick="generatePassword()" class="px-6 rounded-2xl bg-white/5 border border-white/5 text-[10px] font-black uppercase tracking-widest hover:bg-white/10 transition-all">Gen</button>
                    </div>
                    <p class="text-[9px] text-gray-600 pl-4 italic">Leave blank on edit to keep current password.</p>
                </div>

                <!-- Actions -->
                <div class="pt-6 border-t border-white/5 flex gap-4">
                    <button type="button" onclick="closeModal()" class="flex-1 py-4 rounded-2xl bg-white/5 text-gray-400 font-black text-[11px] uppercase tracking-widest hover:text-white hover:bg-white/10 transition-all">Cancel</button>
                    <button type="submit" class="flex-[2] py-4 rounded-2xl bg-gradient-to-r from-[#d4af37] to-[#f3cf7a] text-black font-black text-[11px] uppercase tracking-[0.2em] shadow-[0_10px_30px_rgba(212,175,55,0.2)] hover:scale-[1.02] active:scale-95 transition-all">
                        Save Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- NOTIFICATION CARD -->
<div id="notification-card" class="fixed bottom-12 right-12 z-[200] hidden animate-in slide-in-from-right-10 duration-500">
    <div class="glass p-8 rounded-[2rem] border border-[#d4af37]/30 bg-[#151716] shadow-2xl max-w-xs">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
            </div>
            <div>
                <h4 class="notify-title text-sm font-black text-[#f3cf7a]">Success</h4>
                <p class="text-[9px] uppercase font-black tracking-widest text-gray-500">Credentials Created</p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-[#0f1110] border border-white/5 mb-6">
            <p class="notify-content text-[10px] font-mono text-[#d4af37] leading-relaxed"></p>
        </div>
        <button onclick="closeNotification()" class="w-full py-3 rounded-xl bg-white/5 text-[9px] font-black uppercase tracking-widest hover:bg-white/10 transition-all">Dismiss</button>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.2); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(212, 175, 55, 0.4); }
</style>

<script>
    window.currentUserId = <?php echo json_encode($currentUser['id']); ?>;
</script>
<script src="public/js/admin-users.js"></script>

<?php renderFooter(); ?>
