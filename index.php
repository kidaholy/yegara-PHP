<?php
/**
 * Luxury Welcome Landing Page for Abe Hotel & Spa
 */
require_once 'includes/layout.php';

$user = getCurrentUser();
$title = "Welcome";

renderHeader($title);
?>

<div class="h-[calc(100vh-140px)] flex flex-col items-center justify-center p-4 relative overflow-hidden">
    <!-- Hero Section -->
    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-16 items-center z-10">
        <div class="space-y-8 text-left">
            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-2xl bg-gold/10 border border-gold/20 text-gold text-[10px] font-black uppercase tracking-widest animate-in slide-in-from-left duration-700">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                Luxury Hospitality Experience
            </div>
            
            <div class="space-y-4">
                <h1 class="text-6xl lg:text-8xl font-black font-playfair text-white tracking-tighter leading-none animate-in slide-in-from-left duration-1000 delay-100">
                    Prime <span class="text-gold">Addis</span>
                </h1>
                <p class="text-lg text-muted-foreground font-medium max-w-lg leading-relaxed animate-in slide-in-from-left duration-1000 delay-200">
                    A masterpiece of modern hospitality engineering. Seamlessly managing stays, culinary excellence, and executive intelligence.
                </p>
            </div>

            <div class="flex flex-wrap gap-4 pt-4 animate-in slide-in-from-left duration-1000 delay-300">
                <?php if ($user): ?>
                    <a href="admin.php" class="px-8 py-4 bg-white text-slate-950 rounded-2xl font-black uppercase tracking-widest text-xs hover:scale-[1.05] active:scale-[0.95] transition-all shadow-2xl flex items-center gap-3">
                        Enter System Intelligence
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                    <a href="logout.php" class="px-8 py-4 bg-white/5 border border-white/10 text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-white/10 transition-all">
                        Switch Account
                    </a>
                <?php else: ?>
                    <a href="login.php" class="px-8 py-4 bg-white text-slate-950 rounded-2xl font-black uppercase tracking-widest text-xs hover:scale-[1.05] active:scale-[0.95] transition-all shadow-2xl flex items-center gap-3">
                        Secure Authentication
                        <i data-lucide="lock" class="w-4 h-4"></i>
                    </a>
                    <button class="px-8 py-4 bg-white/5 border border-white/10 text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-white/10 transition-all">
                        Guest Portal
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Visual Bento -->
        <div class="hidden lg:grid grid-cols-2 gap-4 animate-in zoom-in duration-1000 delay-500">
            <div class="glass p-8 rounded-[3rem] border border-white/5 aspect-square flex flex-col justify-end translate-y-8">
                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-500 mb-6">
                    <i data-lucide="key-round" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-white text-xl">Reception</h3>
                <p class="text-xs text-muted-foreground mt-2">ID Vault & Stay Control</p>
            </div>
            <div class="glass p-8 rounded-[3rem] border border-white/5 aspect-square flex flex-col justify-end">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 mb-6">
                    <i data-lucide="utensils" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-white text-xl">Kitchen</h3>
                <p class="text-xs text-muted-foreground mt-2">Active KDS Service</p>
            </div>
            <div class="glass p-8 rounded-[3rem] border border-white/5 aspect-square flex flex-col justify-end translate-y-8">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500 mb-6">
                    <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-white text-xl">Insights</h3>
                <p class="text-xs text-muted-foreground mt-2">Financial Intelligence</p>
            </div>
            <div class="glass p-8 rounded-[3rem] border border-white/5 aspect-square flex flex-col justify-end">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-500 mb-6">
                    <i data-lucide="beer" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-white text-xl">Bar</h3>
                <p class="text-xs text-muted-foreground mt-2">Kiosk Pours</p>
            </div>
        </div>
    </div>

    <!-- Background Decoration -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gold/5 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex items-center gap-3 text-[10px] font-bold uppercase tracking-[0.4em] text-white/20">
        <span class="w-12 h-[1px] bg-white/10"></span>
        Prime Addis V2 · Management Edition
        <span class="w-12 h-[1px] bg-white/10"></span>
    </div>
</div>

<?php renderFooter(); ?>
