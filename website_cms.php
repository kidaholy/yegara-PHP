<?php
/**
 * Website CMS — slide-based admin editor for the public landing page
 */
require_once 'includes/layout.php';
require_once 'includes/social-icons.php';
requireAuth(['admin'], 'settings:update');

$title = 'Website CMS';
renderHeader($title);
?>

<div class="w-full min-h-full bg-[#0a0a0a] text-white p-6 lg:p-10">
    <!-- Top bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
            <p class="text-[10px] font-black uppercase tracking-[0.4em] text-[#c5a059] mb-2">Admin</p>
            <h1 class="text-3xl font-bold tracking-tight">Website CMS</h1>
            <p class="text-sm text-white/40 mt-1">Manage your public landing page — hero, about, services, contact, social & menu gallery.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <button type="button" id="cmsPreviewBtn" class="inline-flex items-center gap-2 px-5 py-2.5 border border-white/15 rounded-xl text-sm font-semibold text-white/70 hover:text-white hover:border-white/30 transition-all">
                <i data-lucide="external-link" class="w-4 h-4"></i> Preview Site
            </button>
            <button type="button" id="cmsSaveBtn" class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#c5a059] text-black rounded-xl text-sm font-black uppercase tracking-wider hover:bg-[#d4b06a] transition-colors shadow-lg shadow-[#c5a059]/20">
                <i data-lucide="save" class="w-4 h-4"></i> Save Changes
            </button>
        </div>
    </div>

    <div class="flex flex-col xl:flex-row gap-8">
        <!-- Slide navigation -->
        <aside class="xl:w-72 shrink-0">
            <div class="sticky top-6 space-y-4">
                <div class="p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-white/30 mb-3">Sections</p>
                    <nav id="cmsSlideNav" class="space-y-1"></nav>
                </div>
                <div class="p-4 rounded-2xl bg-[#c5a059]/5 border border-[#c5a059]/20 text-center">
                    <p class="text-[10px] font-black uppercase tracking-widest text-[#c5a059]/60">Tip</p>
                    <p class="text-xs text-white/50 mt-2 leading-relaxed">Use arrows or sidebar to move between slides. Save when done.</p>
                </div>
            </div>
        </aside>

        <!-- Slide panel -->
        <div class="flex-1 min-w-0">
            <div class="rounded-2xl border border-white/10 bg-white/[0.02] overflow-hidden">
                <!-- Slide header -->
                <div class="flex items-center justify-between px-6 py-5 border-b border-white/5 bg-black/20">
                    <div>
                        <h2 id="cmsSlideLabel" class="text-lg font-bold">About Us</h2>
                        <p id="cmsSlideSubtitle" class="text-sm text-white/40 mt-0.5"></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span id="cmsSlideCounter" class="text-xs font-bold text-white/30 tabular-nums">1 / 6</span>
                        <button type="button" id="cmsPrevSlide" class="p-2 rounded-lg border border-white/10 hover:bg-white/5 text-white/60 hover:text-white transition-colors">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <button type="button" id="cmsNextSlide" class="p-2 rounded-lg border border-white/10 hover:bg-white/5 text-white/60 hover:text-white transition-colors">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Slide content -->
                <div id="cmsSlidePanel" class="p-6 lg:p-8 min-h-[480px]">
                    <div class="flex items-center justify-center h-64 text-white/30">
                        <i data-lucide="loader-2" class="w-8 h-8 animate-spin"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderSocialIconsStylesheet(); ?>
<script src="public/js/admin-website-cms.js"></script>
<?php renderFooter(); ?>
