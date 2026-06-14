/**
 * Website CMS Admin — slide-based content editor
 */
const WebsiteCMS = {
    data: null,
    currentSlide: 0,
    slides: ['hero', 'about', 'services', 'contact', 'social', 'gallery', 'seo'],
    slideLabels: {
        hero: 'Hero Banner',
        about: 'About Us',
        services: 'Services',
        contact: 'Contact Us',
        social: 'Social Media',
        gallery: 'Menu Gallery',
        seo: 'Google Indexing & SEO',
    },
    slideSubtitles: {
        hero: 'Landing page headline, background & call-to-action',
        about: 'Tell your story with text and a featured image',
        services: 'Showcase hotel amenities and offerings',
        contact: 'Phone, email and address for guests',
        social: 'Choose 1–4 platforms and add profile links for the contact card',
        gallery: 'Upload menu photos in an attractive gallery',
        seo: 'Meta description, keywords and social sharing settings',
    },
    socialPlatforms: [
        { id: 'facebook', label: 'Facebook', brand: 'social-brand-facebook' },
        { id: 'instagram', label: 'Instagram', brand: 'social-brand-instagram' },
        { id: 'twitter', label: 'Twitter / X', brand: 'social-brand-twitter' },
        { id: 'youtube', label: 'YouTube', brand: 'social-brand-youtube' },
        { id: 'linkedin', label: 'LinkedIn', brand: 'social-brand-linkedin' },
        { id: 'tiktok', label: 'TikTok', brand: 'social-brand-tiktok' },
        { id: 'telegram', label: 'Telegram', brand: 'social-brand-telegram' },
        { id: 'message-circle', label: 'WhatsApp', brand: 'social-brand-whatsapp' },
        { id: 'globe', label: 'Website', brand: 'social-brand-website' },
        { id: 'mail', label: 'Email', brand: 'social-brand-email' },
        { id: 'custom', label: 'Other Content', brand: 'social-brand-custom' },
    ],
    maxSocialPlatforms: 4,
    serviceIcons: [
        'bed-double', 'utensils-crossed', 'wine', 'coffee', 'concierge-bell',
        'car', 'wifi', 'sparkles', 'heart', 'map-pin', 'phone', 'star',
    ],

    async init() {
        await this.load();
        this.renderNav();
        this.renderSlide();
        this.bindGlobal();
        lucide.createIcons();
    },

    async load() {
        const res = await fetch('api/website-cms.php');
        this.data = await res.json();
        if (Array.isArray(this.data.social)) {
            this.data.social = this.data.social
                .slice(0, this.maxSocialPlatforms)
                .map(s => ({
                    ...s,
                    platform: this.normalizeSocialPlatform(s.platform),
                }));
        }
    },

    bindGlobal() {
        document.getElementById('cmsSaveBtn')?.addEventListener('click', () => this.save());
        document.getElementById('cmsPreviewBtn')?.addEventListener('click', () => window.open('index.php', '_blank'));

        document.getElementById('cmsPrevSlide')?.addEventListener('click', () => this.goSlide(this.currentSlide - 1));
        document.getElementById('cmsNextSlide')?.addEventListener('click', () => this.goSlide(this.currentSlide + 1));
    },

    goSlide(index) {
        if (index < 0 || index >= this.slides.length) return;
        this.collectCurrentSlide();
        const prev = this.currentSlide;
        this.currentSlide = index;
        if (prev !== index) {
            this.persist(false);
        }
        this.renderNav();
        this.renderSlide();
        lucide.createIcons();
    },

    renderNav() {
        const nav = document.getElementById('cmsSlideNav');
        const label = document.getElementById('cmsSlideLabel');
        const subtitle = document.getElementById('cmsSlideSubtitle');
        const counter = document.getElementById('cmsSlideCounter');
        const key = this.slides[this.currentSlide];

        if (label) label.textContent = this.slideLabels[key];
        if (subtitle) subtitle.textContent = this.slideSubtitles[key];
        if (counter) counter.textContent = `${this.currentSlide + 1} / ${this.slides.length}`;

        if (!nav) return;
        nav.innerHTML = this.slides.map((slide, i) => {
            const active = i === this.currentSlide;
            return `
                <button type="button" onclick="WebsiteCMS.goSlide(${i})"
                    class="cms-nav-btn flex items-center gap-3 w-full px-4 py-3 rounded-xl text-left transition-all ${active ? 'bg-[#c5a059]/15 border border-[#c5a059]/40 text-[#c5a059]' : 'border border-transparent text-white/50 hover:text-white hover:bg-white/5'}">
                    <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-black ${active ? 'bg-[#c5a059] text-black' : 'bg-white/5'}">${i + 1}</span>
                    <span class="text-sm font-semibold">${this.slideLabels[slide]}</span>
                </button>`;
        }).join('');
    },

    renderSlide() {
        const panel = document.getElementById('cmsSlidePanel');
        if (!panel || !this.data) return;
        const key = this.slides[this.currentSlide];
        const renderers = {
            hero: () => this.renderHero(),
            about: () => this.renderAbout(),
            services: () => this.renderServices(),
            contact: () => this.renderContact(),
            social: () => this.renderSocial(),
            gallery: () => this.renderGallery(),
            seo: () => this.renderSeo(),
        };
        panel.innerHTML = renderers[key]?.() || '';
        lucide.createIcons();
    },

    field(label, id, value, type = 'text', placeholder = '') {
        const cls = 'w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-white/30 focus:border-[#c5a059]/50 focus:outline-none transition-colors';
        if (type === 'textarea') {
            return `
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.25em] text-white/40">${label}</label>
                    <textarea id="${id}" rows="5" placeholder="${placeholder}" class="${cls} resize-y min-h-[120px]">${this.esc(value)}</textarea>
                </div>`;
        }
        return `
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-[0.25em] text-white/40">${label}</label>
                <input type="${type}" id="${id}" value="${this.esc(value)}" placeholder="${placeholder}" class="${cls}">
            </div>`;
    },

    imageUpload(label, currentPath, onUploadKey) {
        return `
            <div class="space-y-3">
                <label class="text-[10px] font-black uppercase tracking-[0.25em] text-white/40">${label}</label>
                <div class="flex flex-col sm:flex-row gap-4 items-start">
                    <div class="w-full sm:w-48 aspect-video rounded-xl overflow-hidden border border-white/10 bg-black/40 shrink-0">
                        <img src="${this.esc(currentPath)}" class="w-full h-full object-cover" onerror="this.src='assets/welcome_bg.png'">
                    </div>
                    <label class="inline-flex items-center gap-2 px-5 py-3 bg-white/5 hover:bg-[#c5a059]/20 border border-white/10 hover:border-[#c5a059]/40 rounded-xl cursor-pointer text-sm font-semibold text-white/70 hover:text-[#c5a059] transition-all">
                        <i data-lucide="upload" class="w-4 h-4"></i> Upload Image
                        <input type="file" accept="image/*" class="hidden" onchange="WebsiteCMS.uploadImage(event, '${onUploadKey}')">
                    </label>
                </div>
            </div>`;
    },

    sectionHeader(title, subtitle) {
        return `
            <div class="mb-8 pb-6 border-b border-white/5">
                <h3 class="text-xl font-bold text-white">${title}</h3>
                <p class="text-sm text-white/40 mt-1">${subtitle}</p>
            </div>`;
    },

    renderHero() {
        const h = this.data.hero || {};
        const v = this.data.sections?.vision || {};
        return `
            ${this.sectionHeader('Hero Banner', 'First impression on the landing page')}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-5">
                    ${this.field('Eyebrow Text', 'hero_eyebrow', h.eyebrow || '', 'text', 'Welcome to')}
                    ${this.field('Headline', 'hero_headline', h.headline || '', 'text', 'Hotel Name')}
                    ${this.field('Subtitle', 'hero_subtitle', h.subtitle || '', 'textarea')}
                    ${this.field('Button Text', 'hero_cta_text', h.cta_text || '', 'text', 'Discover More')}
                    ${this.field('Button Link', 'hero_cta_link', h.cta_link || '', 'text', '#services')}
                </div>
                <div class="space-y-5">
                    ${this.imageUpload('Background Image', h.background_image || 'assets/welcome_bg.png', 'hero.background_image')}
                    <div class="p-5 rounded-xl bg-white/[0.03] border border-white/5 space-y-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-[#c5a059]">Footer Vision (optional)</p>
                        ${this.field('Vision Title', 'vision_title', v.title || '')}
                        ${this.field('Vision Text', 'vision_content', v.content || '', 'textarea')}
                    </div>
                </div>
            </div>`;
    },

    renderAbout() {
        const a = this.data.about || {};
        const sec = this.data.sections?.about || {};
        return `
            ${this.sectionHeader('About Us', 'Your hotel story and featured photo')}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="space-y-5">
                    ${this.field('Section Badge', 'about_badge', sec.badge || '', 'text', 'The Heritage')}
                    ${this.field('Section Title', 'about_title', a.title || '')}
                    ${this.field('Content', 'about_content', a.content || '', 'textarea')}
                </div>
                <div>${this.imageUpload('Featured Image', a.image || '', 'about.image')}</div>
            </div>`;
    },

    renderServices() {
        const sec = this.data.sections?.services || {};
        const items = this.data.services || [];
        return `
            ${this.sectionHeader('Services', 'Add cards with icons and images')}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                ${this.field('Section Badge', 'services_badge', sec.badge || '')}
                ${this.field('Section Title', 'services_title', sec.title || '')}
                ${this.field('Section Subtitle', 'services_subtitle', sec.subtitle || '')}
            </div>
            <div class="flex justify-end mb-4">
                <button type="button" onclick="WebsiteCMS.addService()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#c5a059] text-black text-xs font-black uppercase tracking-wider rounded-xl hover:bg-[#d4b06a] transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Service
                </button>
            </div>
            <div class="space-y-4">
                ${items.map((s, i) => this.serviceCard(s, i)).join('')}
            </div>`;
    },

    serviceCard(s, i) {
        const iconOptions = this.serviceIcons.map(ic =>
            `<option value="${ic}" ${s.icon === ic ? 'selected' : ''}>${ic}</option>`
        ).join('');
        return `
            <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/10 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-black uppercase tracking-widest text-[#c5a059]">Service ${i + 1}</span>
                    <button type="button" onclick="WebsiteCMS.removeService(${i})" class="p-2 text-red-400/70 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" data-svc="${i}" data-field="title" value="${this.esc(s.title)}" placeholder="Title" class="cms-svc-input w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
                    <select data-svc="${i}" data-field="icon" class="cms-svc-input w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
                        ${iconOptions}
                    </select>
                </div>
                <textarea data-svc="${i}" data-field="description" rows="2" placeholder="Description" class="cms-svc-input w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white resize-none">${this.esc(s.description)}</textarea>
                <div class="flex flex-col sm:flex-row gap-4 items-start">
                    <div class="w-32 h-20 rounded-lg overflow-hidden border border-white/10 shrink-0">
                        <img src="${this.esc(s.image)}" class="w-full h-full object-cover">
                    </div>
                    <label class="inline-flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 rounded-xl cursor-pointer text-xs font-semibold text-white/60 hover:text-[#c5a059] transition-colors">
                        <i data-lucide="image" class="w-4 h-4"></i> Change Photo
                        <input type="file" accept="image/*" class="hidden" onchange="WebsiteCMS.uploadServiceImage(event, ${i})">
                    </label>
                </div>
            </div>`;
    },

    renderContact() {
        const c = this.data.contact || {};
        const sec = this.data.sections?.contact || {};
        return `
            ${this.sectionHeader('Contact Us', 'How guests can reach you')}
            <div class="max-w-xl space-y-5">
                ${this.field('Section Title', 'contact_section_title', sec.title || 'Contact Us')}
                ${this.field('Phone', 'contact_phone', c.phone || '', 'tel')}
                ${this.field('Email', 'contact_email', c.email || '', 'email')}
                ${this.field('Address', 'contact_address', c.address || '', 'textarea')}
            </div>`;
    },

    socialIconSvgs: {
        facebook: '<path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.748c0-.855.192-1.136 1.136-1.136h2.864v-4h-3.864c-3.432 0-5.136 1.703-5.136 5.136v1.748z"/>',
        instagram: '<path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 3.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11zm0 2a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7zm5.75-3.25a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5z"/>',
        twitter: '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
        youtube: '<path d="M21.8 8.001a2.5 2.5 0 0 0-1.76-1.77C18.36 6 12 6 12 6s-6.36 0-8.04.231A2.5 2.5 0 0 0 2.2 8.001 26.3 26.3 0 0 0 2 12a26.3 26.3 0 0 0 .2 3.999 2.5 2.5 0 0 0 1.76 1.77C5.64 18 12 18 12 18s6.36 0 8.04-.231a2.5 2.5 0 0 0 1.76-1.77A26.3 26.3 0 0 0 22 12a26.3 26.3 0 0 0-.2-3.999zM10 15.5v-7l6 3.5-6 3.5z"/>',
        linkedin: '<path d="M4.98 3.5a2.25 2.25 0 1 1 0 4.5 2.25 2.25 0 0 1 0-4.5zM3 8.75h3.96V21H3V8.75zm7.53 0H14.5v1.67h.05c.55-1.04 1.9-2.14 3.91-2.14 4.18 0 4.95 2.75 4.95 6.33V21h-4v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94V21h-4V8.75z"/>',
        tiktok: '<path d="M16.6 5.82s.51.5 0 0A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 0 1-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.69V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.1 4.1 0 0 1-1-.48z"/>',
        telegram: '<path d="M20.665 3.717l-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701-.33 4.955c.488 0 .705-.223.978-.488l2.35-2.285 4.888 3.61c.9.497 1.55.241 1.774-.838l3.203-15.1c.33-1.32-.505-1.925-1.371-1.533z"/>',
        'message-circle': '<path d="M12 2a9 9 0 0 0-7.74 13.6L2 22l6.55-2.12A9 9 0 1 0 12 2zm0 2a7 7 0 0 1 5.6 11.2l-.35.46.12.74.55-1.78 1.78-.55-.74-.12-.46-.35A7 7 0 0 1 12 4zm-3.5 4.5a.75.75 0 0 0 0 1.5h7a.75.75 0 0 0 0-1.5h-7zm0 3a.75.75 0 0 0 0 1.5h4.5a.75.75 0 0 0 0-1.5H8.5z"/>',
        globe: '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm7.93 9h-3.18a15.7 15.7 0 0 0-1.2-4.96A8.03 8.03 0 0 1 19.93 11zM12 4c.95 1.57 1.63 3.36 1.93 5.25h-3.86C10.37 7.36 11.05 5.57 12 4zM8.45 6.04A15.7 15.7 0 0 0 7.25 11H4.07a8.03 8.03 0 0 1 4.38-4.96zM4.07 13h3.18c.2 1.74.74 3.38 1.55 4.82A8.03 8.03 0 0 1 4.07 13zm7.93 7c-.95-1.57-1.63-3.36-1.93-5.25h3.86c-.3 1.89-.98 3.68-1.93 5.25zm3.12-5.25h3.18a8.03 8.03 0 0 1-4.38 4.96c.81-1.44 1.35-3.08 1.55-4.96zm1.38-2H16.3a13.6 13.6 0 0 0-1.38-4.01A8.02 8.02 0 0 1 17.5 11zM11.08 6.99A13.6 13.6 0 0 0 9.7 11H6.5a8.02 8.02 0 0 1 4.58-4.01zM6.5 13h3.2c.3 1.45.86 2.8 1.58 3.99A8.02 8.02 0 0 1 6.5 13z"/>',
        mail: '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 2v.01L12 13l8-6.99V6H4zm16 12V9.25l-7.4 5.55a1 1 0 0 1-1.2 0L4 9.25V18h16z"/>',
        custom: '<path d="M11 5h2v6h6v2h-6v6h-2v-6h-6v-2h6z"/>',
    },

    normalizeSocialPlatform(platform) {
        return platform === 'send' ? 'telegram' : platform;
    },

    socialIconBadge(platform, { size = 'md', active = false, muted = false } = {}) {
        platform = this.normalizeSocialPlatform(platform);
        const meta = this.socialPlatforms.find(p => p.id === platform);
        const brand = meta?.brand || 'social-brand-website';
        const sizeClass = size === 'sm' ? 'social-icon-sm'
            : (size === 'lg' ? 'social-icon-lg'
            : (size === 'contact' ? 'social-icon-contact' : 'social-icon-md'));
        const stateClass = active ? 'social-icon-active' : (muted ? 'social-icon-muted' : '');
        const path = this.socialIconSvgs[platform] || this.socialIconSvgs.globe;
        return `
            <span class="social-icon-badge ${sizeClass} ${brand} ${stateClass}">
                <span class="social-icon-glow"></span>
                <span class="social-icon-surface"></span>
                <svg class="social-icon-glyph" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">${path}</svg>
            </span>`;
    },

    renderSocial() {
        const sec = this.data.sections?.social || {};
        const items = this.data.social || [];
        const count = items.length;
        const atMax = count >= this.maxSocialPlatforms;
        const used = new Set(items.map(s => this.normalizeSocialPlatform(s.platform)));
        const pickerPlatforms = this.socialPlatforms.filter(p => p.id !== 'custom');
        const picker = pickerPlatforms.map(p => {
            const active = used.has(p.id);
            const disabled = atMax && !active;
            return `
                <button type="button"
                    onclick="WebsiteCMS.toggleSocialPlatform('${p.id}')"
                    ${disabled ? 'disabled' : ''}
                    aria-pressed="${active}"
                    class="social-pick-card social-pick flex flex-col items-center gap-3 p-4 rounded-xl border transition-all ${p.brand} ${active ? 'is-active border-white/20 bg-white/[0.04]' : 'border-white/10 bg-white/[0.02] hover:border-white/20'} ${disabled ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''}">
                    ${this.socialIconBadge(p.id, { size: 'lg', active, muted: !active })}
                    <span class="text-[10px] font-bold uppercase tracking-wider ${active ? 'text-white' : 'text-white/40'}">${p.label}</span>
                </button>`;
        }).join('');

        return `
            ${this.sectionHeader('Social Media', 'Select up to 4 platforms — they appear as linked icons in the contact card')}
            ${this.field('Section Title', 'social_section_title', sec.title || 'Follow Us')}
            <div class="mb-8">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-white/40">Choose Platforms</p>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] ${atMax ? 'text-[#c5a059]' : 'text-white/30'}">${count} / ${this.maxSocialPlatforms} selected</p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">${picker}</div>
            </div>
            <div class="space-y-3" id="socialLinksList">
                ${items.length ? items.map((s, i) => this.socialRow(s, i)).join('') : '<p class="text-sm text-white/30 italic">Select 1–4 platforms above, then paste each profile link.</p>'}
            </div>
            <div class="mt-4 flex justify-start">
                <button type="button"
                    onclick="WebsiteCMS.addCustomSocialLink()"
                    ${atMax ? 'disabled' : ''}
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-white/40 hover:text-[#c5a059] hover:border-[#c5a059]/40 transition-all ${atMax ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''}">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Custom Link
                </button>
            </div>`;
    },

    socialRow(s, i) {
        const platform = this.normalizeSocialPlatform(s.platform);
        const meta = this.socialPlatforms.find(p => p.id === platform);
        const isCustom = platform === 'custom';
        return `
            <div class="flex items-center gap-4 p-4 rounded-xl bg-white/[0.03] border border-white/10 group">
                ${this.socialIconBadge(platform, { size: 'md', active: true })}
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-1.5">
                        ${isCustom ? 
                            `<input type="text" data-social-label="${i}" value="${this.esc(s.platform_label || 'Custom')}" placeholder="Platform Name" class="bg-transparent border-none p-0 text-[10px] font-black uppercase tracking-widest text-[#c5a059] outline-none focus:ring-0">` :
                            `<p class="text-[10px] font-black text-white/40 uppercase tracking-widest">${meta?.label || s.platform}</p>`
                        }
                        <button type="button" onclick="WebsiteCMS.removeSocialLink(${i})" class="text-white/40 hover:text-red-400 p-1.5 transition-colors">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <input type="url" data-social="${i}" value="${this.esc(s.link)}" placeholder="https://..." class="w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-[#c5a059]/50 outline-none transition-colors">
                </div>
            </div>`;
    },

    renderGallery() {
        const sec = this.data.sections?.gallery || {};
        const items = this.data.gallery || [];
        return `
            ${this.sectionHeader('Menu Gallery', 'Upload photos of menus and dishes')}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                ${this.field('Section Badge', 'gallery_badge', sec.badge || '')}
                ${this.field('Section Title', 'gallery_title', sec.title || '')}
                ${this.field('Section Subtitle', 'gallery_subtitle', sec.subtitle || '')}
            </div>
            <div class="flex justify-end mb-4">
                <button type="button" onclick="WebsiteCMS.addGalleryItem()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#c5a059] text-black text-xs font-black uppercase tracking-wider rounded-xl hover:bg-[#d4b06a] transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> Add Photo
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                ${items.map((g, i) => this.galleryCard(g, i)).join('')}
            </div>`;
    },

    galleryCard(g, i) {
        return `
            <div class="rounded-2xl overflow-hidden border border-white/10 bg-white/[0.02] group">
                <div class="aspect-[4/3] relative">
                    <img src="${this.esc(g.image)}" class="w-full h-full object-cover">
                    <button type="button" onclick="WebsiteCMS.removeGalleryItem(${i})" class="absolute top-2 right-2 p-2 bg-black/60 rounded-lg text-red-400 opacity-0 group-hover:opacity-100 transition-opacity">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <input type="text" data-gal="${i}" value="${this.esc(g.title)}" placeholder="Caption" class="cms-gal-input w-full bg-black/40 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                    <label class="inline-flex items-center gap-2 text-xs text-white/50 cursor-pointer hover:text-[#c5a059]">
                        <i data-lucide="refresh-cw" class="w-3 h-3"></i> Replace
                        <input type="file" accept="image/*" class="hidden" onchange="WebsiteCMS.uploadGalleryImage(event, ${i})">
                    </label>
                </div>
            </div>`;
    },

    toggleSocialPlatform(platform) {
        if (platform === 'custom') {
            this.addCustomSocialLink();
            return;
        }
        this.collectCurrentSlide();
        const items = this.data.social || [];
        const normalized = this.normalizeSocialPlatform(platform);
        const index = items.findIndex(s => this.normalizeSocialPlatform(s.platform) === normalized);
        if (index >= 0) {
            items.splice(index, 1);
        } else {
            if (items.length >= this.maxSocialPlatforms) {
                return;
            }
            items.push({ platform: normalized, link: '', platform_label: '' });
        }
        this.data.social = items;
        this.renderSlide();
    },

    addCustomSocialLink() {
        this.collectCurrentSlide();
        const items = this.data.social || [];
        if (items.length >= this.maxSocialPlatforms) {
            return;
        }
        items.push({ platform: 'custom', link: '', platform_label: 'Other' });
        this.data.social = items;
        this.renderSlide();
    },

    removeSocialLink(i) {
        // No confirm needed here as per common patterns for lists, or keep it if it's destructive
        this.collectCurrentSlide();
        this.data.social.splice(i, 1);
        this.renderSlide();
    },

    renderSeo() {
        const seo = this.data.seo || {};
        return `
            ${this.sectionHeader('Search Optimization', 'Settings to help Google find and index your website correctly.')}
            <div class="space-y-6">
                ${this.field('Meta Description', 'seo_description', seo.description || '', 'textarea', 'Search engines show this in search results...')}
                ${this.field('Keywords', 'seo_keywords', seo.keywords || '', 'text', 'keyword1, keyword2, keyword3...')}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        ${this.field('Sharing Image URL', 'seo_og_image', seo.og_image || '', 'url', 'assets/cms/defaults/hero.png')}
                        <p class="text-[10px] text-white/30 italic">Commonly used for Facebook/Twitter link previews.</p>
                    </div>
                    <div class="space-y-4">
                        ${this.field('Google Verification Content', 'seo_google_verification', seo.google_verification || '', 'text', 'googlee0ab3...')}
                        <p class="text-[10px] text-white/30 italic">Only the code part (e.g., googlee0ab378d64584852).</p>
                    </div>
                </div>
                <div class="p-5 rounded-2xl bg-white/[0.03] border border-white/10 mt-8">
                    <div class="flex items-center gap-3 mb-4">
                        <i data-lucide="info" class="w-5 h-5 text-[#c5a059]"></i>
                        <h4 class="text-sm font-bold text-white uppercase tracking-wider">Indexing Status</h4>
                    </div>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 text-xs text-white/50">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500/70"></i>
                            Meta Robots: <strong>index, follow</strong> (Active)
                        </li>
                        <li class="flex items-center gap-3 text-xs text-white/50">
                            <i data-lucide="check-circle" class="w-4 h-4 text-green-500/70"></i>
                            Sitemap XML: <strong>Generated</strong> (/sitemap.php)
                        </li>
                        <li class="flex items-center gap-3 text-xs text-white/50">
                            <i data-lucide="link" class="w-4 h-4 text-[#c5a059]"></i>
                            Canonical Link: <strong>Set to Root</strong>
                        </li>
                    </ul>
                </div>
            </div>`;
    },

    addService() {
        this.collectCurrentSlide();
        this.data.services.push({
            id: Date.now().toString(),
            title: 'New Service',
            description: '',
            icon: 'star',
            image: 'assets/welcome_bg.png',
        });
        this.renderSlide();
    },

    removeService(i) {
        if (!confirm('Remove this service?')) return;
        this.collectCurrentSlide();
        this.data.services.splice(i, 1);
        this.renderSlide();
    },

    addGalleryItem() {
        this.collectCurrentSlide();
        this.data.gallery.push({
            id: Date.now().toString(),
            title: 'Menu Item',
            image: 'assets/welcome_bg.png',
        });
        this.renderSlide();
    },

    removeGalleryItem(i) {
        if (!confirm('Remove this photo?')) return;
        this.collectCurrentSlide();
        this.data.gallery.splice(i, 1);
        this.renderSlide();
    },

    setNested(obj, path, value) {
        const keys = path.split('.');
        let cur = obj;
        for (let i = 0; i < keys.length - 1; i++) {
            if (!cur[keys[i]]) cur[keys[i]] = {};
            cur = cur[keys[i]];
        }
        cur[keys[keys.length - 1]] = value;
    },

    async uploadImage(event, path) {
        const file = event.target.files?.[0];
        if (!file) return;
        this.collectCurrentSlide();
        const fd = new FormData();
        fd.append('image', file);
        const res = await fetch('api/website-cms.php', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.success) {
            this.setNested(this.data, path, result.path);
            const saved = await this.persist(false);
            this.renderSlide();
            if (saved) this.showToast('Image uploaded & saved');
            else alert('Image uploaded but save failed — click Save Changes');
        } else {
            alert(result.message || 'Upload failed');
        }
    },

    async uploadServiceImage(event, index) {
        const file = event.target.files?.[0];
        if (!file) return;
        this.collectCurrentSlide();
        const fd = new FormData();
        fd.append('image', file);
        const res = await fetch('api/website-cms.php', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.success) {
            this.data.services[index].image = result.path;
            const saved = await this.persist(false);
            this.renderSlide();
            if (saved) this.showToast('Image uploaded & saved');
            else alert('Image uploaded but save failed — click Save Changes');
        }
    },

    async uploadGalleryImage(event, index) {
        const file = event.target.files?.[0];
        if (!file) return;
        this.collectCurrentSlide();
        const fd = new FormData();
        fd.append('image', file);
        const res = await fetch('api/website-cms.php', { method: 'POST', body: fd });
        const result = await res.json();
        if (result.success) {
            this.data.gallery[index].image = result.path;
            const saved = await this.persist(false);
            this.renderSlide();
            if (saved) this.showToast('Image uploaded & saved');
            else alert('Image uploaded but save failed — click Save Changes');
        }
    },

    /** Read a field only if it exists on the current slide (never wipe other slides). */
    fieldVal(id) {
        const el = document.getElementById(id);
        return el ? el.value : null;
    },

    /** Merge text edits from the visible slide into in-memory this.data only. */
    collectCurrentSlide() {
        if (!this.data) return;
        const key = this.slides[this.currentSlide];
        if (!this.data.sections) this.data.sections = {};

        if (key === 'hero') {
            if (!this.data.hero) this.data.hero = {};
            const h = this.data.hero;
            const eyebrow = this.fieldVal('hero_eyebrow');
            const headline = this.fieldVal('hero_headline');
            const subtitle = this.fieldVal('hero_subtitle');
            const ctaText = this.fieldVal('hero_cta_text');
            const ctaLink = this.fieldVal('hero_cta_link');
            if (eyebrow !== null) h.eyebrow = eyebrow;
            if (headline !== null) h.headline = headline;
            if (subtitle !== null) h.subtitle = subtitle;
            if (ctaText !== null) h.cta_text = ctaText;
            if (ctaLink !== null) h.cta_link = ctaLink;
            if (!this.data.sections.vision) this.data.sections.vision = {};
            const vTitle = this.fieldVal('vision_title');
            const vContent = this.fieldVal('vision_content');
            if (vTitle !== null) this.data.sections.vision.title = vTitle;
            if (vContent !== null) this.data.sections.vision.content = vContent;
        }

        if (key === 'about') {
            if (!this.data.about) this.data.about = {};
            const badge = this.fieldVal('about_badge');
            const title = this.fieldVal('about_title');
            const content = this.fieldVal('about_content');
            if (badge !== null) {
                if (!this.data.sections.about) this.data.sections.about = {};
                this.data.sections.about.badge = badge;
            }
            if (title !== null) {
                this.data.about.title = title;
                if (!this.data.sections.about) this.data.sections.about = {};
                this.data.sections.about.title = title;
            }
            if (content !== null) this.data.about.content = content;
        }

        if (key === 'services') {
            if (!this.data.sections.services) this.data.sections.services = {};
            const badge = this.fieldVal('services_badge');
            const title = this.fieldVal('services_title');
            const subtitle = this.fieldVal('services_subtitle');
            if (badge !== null) this.data.sections.services.badge = badge;
            if (title !== null) this.data.sections.services.title = title;
            if (subtitle !== null) this.data.sections.services.subtitle = subtitle;

            if (!Array.isArray(this.data.services)) this.data.services = [];
            document.querySelectorAll('.cms-svc-input').forEach(el => {
                const i = parseInt(el.dataset.svc, 10);
                const field = el.dataset.field;
                if (this.data.services[i]) this.data.services[i][field] = el.value;
            });
        }

        if (key === 'contact') {
            if (!this.data.contact) this.data.contact = {};
            const sectionTitle = this.fieldVal('contact_section_title');
            const phone = this.fieldVal('contact_phone');
            const email = this.fieldVal('contact_email');
            const address = this.fieldVal('contact_address');
            if (sectionTitle !== null) {
                if (!this.data.sections.contact) this.data.sections.contact = {};
                this.data.sections.contact.title = sectionTitle;
            }
            if (phone !== null) this.data.contact.phone = phone;
            if (email !== null) this.data.contact.email = email;
            if (address !== null) this.data.contact.address = address;
        }

        if (key === 'social') {
            const sectionTitle = this.fieldVal('social_section_title');
            if (sectionTitle !== null) {
                if (!this.data.sections.social) this.data.sections.social = {};
                this.data.sections.social.title = sectionTitle;
            }
            if (!Array.isArray(this.data.social)) this.data.social = [];
            document.querySelectorAll('[data-social]').forEach(el => {
                const i = parseInt(el.dataset.social, 10);
                if (this.data.social[i]) {
                    this.data.social[i].link = el.value;
                    const labelEl = document.querySelector(`[data-social-label="${i}"]`);
                    if (labelEl) this.data.social[i].platform_label = labelEl.value;
                }
            });
        }

        if (key === 'gallery') {
            if (!this.data.sections.gallery) this.data.sections.gallery = {};
            const badge = this.fieldVal('gallery_badge');
            const title = this.fieldVal('gallery_title');
            const subtitle = this.fieldVal('gallery_subtitle');
            if (badge !== null) this.data.sections.gallery.badge = badge;
            if (title !== null) this.data.sections.gallery.title = title;
            if (subtitle !== null) this.data.sections.gallery.subtitle = subtitle;

            if (!Array.isArray(this.data.gallery)) this.data.gallery = [];
            document.querySelectorAll('.cms-gal-input').forEach(el => {
                const i = parseInt(el.dataset.gal, 10);
                if (this.data.gallery[i]) this.data.gallery[i].title = el.value;
            });
        }

        if (key === 'seo') {
            if (!this.data.seo) this.data.seo = {};
            const description = this.fieldVal('seo_description');
            const keywords = this.fieldVal('seo_keywords');
            const og_image = this.fieldVal('seo_og_image');
            const google_verification = this.fieldVal('seo_google_verification');
            if (description !== null) this.data.seo.description = description;
            if (keywords !== null) this.data.seo.keywords = keywords;
            if (og_image !== null) this.data.seo.og_image = og_image;
            if (google_verification !== null) this.data.seo.google_verification = google_verification;
        }
    },

    async persist(showErrors = true) {
        this.collectCurrentSlide();
        if (Array.isArray(this.data.social)) {
            this.data.social = this.data.social.slice(0, this.maxSocialPlatforms);
        }
        try {
            const res = await fetch('api/website-cms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.data),
            });
            const result = await res.json();
            if (result.success) {
                if (result.data) this.data = result.data;
                return true;
            }
            if (showErrors) alert(result.message || 'Save failed');
            return false;
        } catch (e) {
            if (showErrors) alert('Save failed — check your connection');
            return false;
        }
    },


    async save() {
        const btn = document.getElementById('cmsSaveBtn');
        const orig = btn?.innerHTML;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Saving…';
            lucide.createIcons();
        }
        const ok = await this.persist(true);
        if (ok) this.showToast('All changes saved successfully');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = orig || '';
            lucide.createIcons();
        }
    },

    showToast(msg) {
        let t = document.getElementById('cmsToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'cmsToast';
            t.className = 'fixed bottom-6 right-6 z-[100] px-5 py-3 bg-[#c5a059] text-black text-sm font-bold rounded-xl shadow-2xl transform translate-y-20 opacity-0 transition-all duration-300';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        requestAnimationFrame(() => {
            t.classList.remove('translate-y-20', 'opacity-0');
        });
        setTimeout(() => t.classList.add('translate-y-20', 'opacity-0'), 2500);
    },

    esc(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    },
};

document.addEventListener('DOMContentLoaded', () => WebsiteCMS.init());
