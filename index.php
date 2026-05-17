<?php
/**
 * High-Fidelity Luxury Landing Page for Abe Hotel
 */
require_once 'includes/layout.php';

$user = getCurrentUser();
$title = "Abe Hotel - Management System";

renderHeader($title);
?>

<div class="relative min-h-screen w-full flex flex-col items-center justify-center overflow-hidden bg-[#0a0a0a]">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <img src="assets/welcome_bg.png" alt="Abe Hotel" class="w-full h-full object-cover opacity-60">
        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-transparent to-black/80"></div>
    </div>

    <!-- Header / Navigation -->
    <header class="absolute top-0 w-full px-12 py-10 flex justify-between items-center z-50">
        <div class="flex items-center gap-4">
            <!-- Circular Logo -->
            <div class="w-14 h-14 rounded-full border-2 border-[#c5a059] flex flex-col items-center justify-center p-1 bg-black/40 backdrop-blur-sm shadow-xl">
                <span class="text-[8px] font-black tracking-widest text-[#c5a059] leading-none mb-0.5">ABE</span>
                <span class="text-[6px] font-bold tracking-[0.2em] text-[#c5a059] leading-none">HOTEL</span>
            </div>
            <div class="hidden md:block">
                <h2 class="text-[#c5a059] font-black text-xl italic tracking-tight leading-none mb-1">ABE HOTEL</h2>
                <p class="text-[8px] text-[#c5a059]/60 font-bold uppercase tracking-widest">ምርጥ አገልግሎት ለመስጠት</p>
            </div>
        </div>

        <div class="flex items-center gap-8">
            <button class="w-10 h-10 rounded-full bg-white/10 border border-white/20 flex items-center justify-center text-white/60 hover:bg-white/20 transition-all">
                <i data-lucide="languages" class="w-5 h-5"></i>
            </button>
            <a href="login.php" class="px-10 py-3 border border-[#c5a059] text-[#c5a059] text-xs font-black uppercase tracking-[0.2em] rounded-sm hover:bg-[#c5a059] hover:text-black transition-all shadow-lg backdrop-blur-sm">
                Login
            </a>
        </div>
    </header>

    <!-- Main Hero Content -->
    <main class="relative z-10 flex flex-col items-center text-center px-4 max-w-4xl animate-in fade-in slide-in-from-bottom duration-1000">
        <div class="flex items-center gap-8 mb-4">
            <span class="w-16 h-[1px] bg-[#c5a059]/40 mt-1"></span>
            <span class="font-light italic text-[#c5a059] font-['Cormorant_Garamond'] text-2xl tracking-wide">Welcome to</span>
            <span class="w-16 h-[1px] bg-[#c5a059]/40 mt-1"></span>
        </div>

        <h1 class="font-['Cormorant_Garamond'] text-[160px] leading-none text-[#c5a059] italic font-semibold mb-2 drop-shadow-2xl">
            Abe
        </h1>
        
        <h2 class="text-3xl font-black uppercase tracking-[0.6em] text-[#c5a059] mb-12 drop-shadow-lg scale-x-110">
            Hotel.
        </h2>

        <div class="flex items-center gap-12 mb-16 px-4">
            <span class="w-full h-[1px] bg-white/10"></span>
            <p class="whitespace-nowrap text-xs font-bold tracking-[0.4em] text-white/60 uppercase">
                Refined Accommodation and Authentic Dining.
            </p>
            <span class="w-full h-[1px] bg-white/10"></span>
        </div>

        <!-- Primary Call to Action -->
        <a href="login.php" class="group relative px-20 py-5 bg-gradient-to-r from-[#c5a059] via-[#d4af37] to-[#c5a059] text-black text-xs font-black uppercase tracking-[0.3em] overflow-hidden rounded-md shadow-[0_20px_50px_rgba(197,160,89,0.3)] transition-all hover:scale-105 active:scale-95">
            <span class="relative z-10">Rooms</span>
            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity"></div>
        </a>
    </main>

    <!-- Bottom Ornament -->
    <div class="absolute bottom-12 flex items-center gap-4 text-[10px] font-bold text-white/20 tracking-[0.5em] uppercase">
        <span class="w-10 h-[1px] bg-white/5"></span>
        Experience the Sublime
        <span class="w-10 h-[1px] bg-white/5"></span>
    </div>
</div>

<?php renderFooter(); ?>
