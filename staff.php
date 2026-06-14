<?php
/**
 * Staff Management - Abe Hotel
 * High-Fidelity "Luxury-First" Edition
 */
require_once 'includes/layout.php';
requireAuth(['admin'], 'users:view');

$title = "Staff Management";
renderHeader($title);

$currentUser = getCurrentUser();
?>

<div class="min-h-screen w-full bg-[#0f1110] p-6 lg:p-12 flex justify-center">
    <div class="max-w-screen-2xl w-full space-y-12">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            <!-- SIDEBAR (lg:col-span-3) -->
            <div class="lg:col-span-3 space-y-8 lg:sticky lg:top-24 z-10">
                
                <!-- Staff Header Card -->
                <div class="glass p-6 lg:p-8 rounded-2xl border border-gray-700/50 bg-gray-800/80 relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-[0.03] transition-transform duration-1000">
                        <i data-lucide="users" class="w-32 h-32 text-[#c5a059]"></i>
                    </div>
                    
                    <div class="flex items-center gap-4 mb-6 lg:mb-8">
                        <div class="w-12 h-12 rounded-xl bg-gray-900 border border-gray-700 flex items-center justify-center text-[#c5a059]">
                            <i data-lucide="users" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h2 class="text-xl lg:text-2xl font-bold text-white">Staff</h2>
                            <p id="staff-count" class="text-[10px] lg:text-xs font-semibold uppercase tracking-wider text-gray-500">Total Active Staff: ...</p>
                        </div>
                    </div>

                    <button onclick="openCreateModal()" class="w-full py-3.5 rounded-xl bg-[#c5a059] text-gray-900 font-bold text-xs lg:text-sm tracking-wide hover:bg-[#b08d4a] active:scale-95 transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Add New Member
                    </button>
                </div>

                <!-- Permissions Info Card -->
                <div class="glass p-8 rounded-2xl border border-gray-700/50 bg-gray-800/40 hidden lg:block relative overflow-hidden">
                    <div class="absolute -left-6 -top-6 opacity-[0.03]">
                        <i data-lucide="shield-check" class="w-24 h-24 text-blue-400"></i>
                    </div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-[#c5a059] mb-3">Security Notice</h3>
                    <p class="text-xs text-gray-400 leading-relaxed font-medium">
                        Staff roles define granular access to orders, financial records, and core infrastructure. Use custom roles for limited agency permissions.
                    </p>
                </div>
            </div>

            <!-- MAIN PANEL (lg:col-span-9) -->
            <div class="lg:col-span-9">
                <!-- Grid Loader -->
                <div id="grid-loader" class="flex flex-col items-center justify-center py-40 animate-pulse">
                    <i data-lucide="refresh-cw" class="w-12 h-12 text-[#c5a059] animate-spin mb-6"></i>
                    <p class="text-xs uppercase font-semibold tracking-widest text-gray-500">Assembling Team...</p>
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
    
    <div class="w-full max-w-xl rounded-2xl bg-gray-900 border border-gray-700 shadow-2xl relative z-10 overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Head -->
        <div class="p-8 pb-4 flex items-center justify-between">
            <div>
                <h2 id="form-title" class="text-2xl font-bold text-white">New Member</h2>
                <p class="text-xs uppercase font-semibold tracking-wider text-gray-400 mt-1">Personnel Configuration</p>
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
                        <label class="text-xs font-semibold text-gray-400 pl-2">Display Name</label>
                        <input type="text" name="name" required class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-sm text-white focus:border-[#c5a059] focus:outline-none transition-colors placeholder:text-gray-600" placeholder="e.g. John Doe">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-gray-400 pl-2">Email Address</label>
                        <input type="email" name="email" required class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-sm text-white focus:border-[#c5a059] focus:outline-none transition-colors placeholder:text-gray-600" placeholder="e.g. john@abehotel.com">
                    </div>
                </div>

                <!-- Access Level -->
                <div class="space-y-4">
                    <label class="text-xs font-semibold text-gray-400 pl-2">Access Level</label>
                    <div id="role-selector" class="grid grid-cols-4 gap-3">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Floor Assignment (Conditional) -->
                <div id="floor-section" class="space-y-2 hidden">
                    <label class="text-xs font-semibold text-gray-400 pl-2">Assigned Floor</label>
                    <select id="floor-select" name="floor-select" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-3 text-sm text-white focus:border-[#c5a059] focus:outline-none transition-colors appearance-none">
                        <!-- Injected by JS -->
                    </select>
                </div>

                <!-- Kitchen Categories (Conditional) -->
                <div id="category-section" class="space-y-4 hidden">
                    <label class="text-xs font-semibold text-gray-400 pl-2">Assigned Stations</label>
                    <div id="category-list" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Granular Privileges (Conditional) -->
                <div id="permission-section" class="space-y-4 hidden">
                    <label class="text-xs font-semibold text-gray-400 pl-2">Granular Privileges</label>
                    <div id="permission-grid" class="grid grid-cols-2 gap-3 p-6 rounded-2xl border border-gray-700 bg-gray-800 max-h-[300px] overflow-y-auto custom-scrollbar">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-gray-400 pl-2">Security (Password)</label>
                    <div class="flex gap-3">
                        <input type="text" name="password" class="flex-1 bg-gray-800 border border-gray-700 rounded-lg p-3 text-sm font-mono font-semibold text-gray-200 focus:border-[#c5a059] focus:outline-none transition-colors placeholder:text-gray-600" placeholder="••••••••">
                        <button type="button" onclick="generatePassword()" class="px-5 rounded-lg bg-gray-700 border border-gray-600 text-xs font-bold text-gray-300 hover:bg-gray-600 transition-colors">Gen</button>
                    </div>
                    <p class="text-xs text-gray-500 pl-2 italic">Leave blank on edit to keep current password.</p>
                </div>

                <!-- Actions -->
                <div class="pt-6 border-t border-white/5 flex gap-4">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 rounded-lg bg-gray-800 border border-gray-700 text-gray-400 font-bold text-xs uppercase tracking-wider hover:text-white hover:bg-gray-700 transition-colors">Cancel</button>
                    <button type="submit" class="flex-[2] py-3 rounded-lg bg-[#c5a059] text-gray-900 font-bold text-xs uppercase tracking-wider hover:bg-[#b08d4a] active:scale-95 transition-all">
                        Save Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- NOTIFICATION CARD -->
<div id="notification-card" class="fixed bottom-8 right-8 z-[200] hidden">
    <div class="p-6 rounded-2xl border border-gray-700 bg-gray-900 shadow-2xl max-w-xs">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
            </div>
            <div>
                <h4 class="notify-title text-sm font-bold text-white">Success</h4>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Credentials Created</p>
            </div>
        </div>
        <div class="p-3 rounded-xl bg-gray-800 border border-gray-700 mb-4">
            <p class="notify-content text-xs font-mono text-[#c5a059] leading-relaxed"></p>
        </div>
        <button onclick="closeNotification()" class="w-full py-2.5 rounded-lg bg-gray-800 border border-gray-700 text-xs font-bold text-gray-400 uppercase tracking-wider hover:bg-gray-700 hover:text-white transition-colors">Dismiss</button>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2); border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.4); }
</style>

<script>
    window.currentUserId = <?php echo json_encode($currentUser['id']); ?>;
    window.SUPER_ADMIN_ID = <?php echo json_encode(SUPER_ADMIN_ID); ?>;
</script>
<script src="public/js/admin-users.js?v=1.2"></script>

<?php renderFooter(); ?>
