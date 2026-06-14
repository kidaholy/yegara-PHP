<?php
/**
 * Unauthorized Access Page
 */
require_once 'includes/layout.php';

// If not logged in, they shouldn't even be here, but just in case
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

renderHeader('Access Denied');
?>

<div class="min-h-[80vh] flex items-center justify-center p-4">
    <div class="glass max-w-md w-full p-10 rounded-3xl border border-red-500/20 bg-red-500/5 text-center space-y-8 animate-in fade-in zoom-in duration-500">
        <!-- Error Icon -->
        <div class="mx-auto w-24 h-24 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500 shadow-[0_0_50px_-12px_rgba(239,68,68,0.3)]">
            <i data-lucide="shield-alert" class="w-12 h-12"></i>
        </div>

        <!-- Message -->
        <div class="space-y-3">
            <h1 class="text-4xl font-black text-white tracking-tighter uppercase italic">Access Denied</h1>
            <p class="text-red-400 font-bold uppercase tracking-widest text-xs">Sorry, you are not permitted to view this page</p>
            <p class="text-gray-500 text-sm leading-relaxed max-w-[280px] mx-auto">Your account privileges do not allow access to the requested resource. Please contact your administrator if you believe this is an error.</p>
        </div>

        <!-- Actions -->
        <div class="pt-4 flex flex-col gap-3">
            <a href="index.php" class="w-full py-4 rounded-xl bg-white/5 border border-white/10 text-white font-black uppercase tracking-widest text-xs hover:bg-white/10 transition-all flex items-center justify-center gap-2">
                <i data-lucide="home" class="w-4 h-4"></i> Return to Dashboard
            </a>
            <button onclick="history.back()" class="w-full py-4 rounded-xl bg-red-500 hover:bg-red-600 text-white font-black uppercase tracking-widest text-xs transition-all shadow-lg flex items-center justify-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Go Back
            </button>
        </div>

        <!-- Help Link -->
        <p class="text-[10px] text-gray-600 font-bold uppercase tracking-wider">
            Logged in as <span class="text-gray-400"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
        </p>
    </div>
</div>

<?php renderFooter(); ?>
