<?php
/**
 * Branded social media icons — shared config for CMS admin & public site
 */
function normalizeSocialPlatform($platform) {
    $aliases = ['send' => 'telegram'];
    return $aliases[$platform] ?? $platform;
}

function getSocialPlatformConfig() {
    return [
        'facebook' => [
            'label' => 'Facebook',
            'class' => 'social-brand-facebook',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'class' => 'social-brand-instagram',
        ],
        'twitter' => [
            'label' => 'Twitter / X',
            'class' => 'social-brand-twitter',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'class' => 'social-brand-youtube',
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'class' => 'social-brand-linkedin',
        ],
        'tiktok' => [
            'label' => 'TikTok',
            'class' => 'social-brand-tiktok',
        ],
        'telegram' => [
            'label' => 'Telegram',
            'class' => 'social-brand-telegram',
        ],
        'message-circle' => [
            'label' => 'WhatsApp',
            'class' => 'social-brand-whatsapp',
        ],
        'globe' => [
            'label' => 'Website',
            'class' => 'social-brand-website',
        ],
        'mail' => [
            'label' => 'Email',
            'class' => 'social-brand-email',
        ],
        'custom' => [
            'label' => 'Custom',
            'class' => 'social-brand-custom',
        ],
    ];
}

function getSocialPlatformLabel($platform, $item = []) {
    if (!empty($item['platform_label'])) {
        return $item['platform_label'];
    }
    $platform = normalizeSocialPlatform($platform);
    $config = getSocialPlatformConfig();
    return $config[$platform]['label'] ?? ucfirst(str_replace('-', ' ', $platform));
}

function getSocialPlatformClass($platform) {
    $platform = normalizeSocialPlatform($platform);
    $config = getSocialPlatformConfig();
    return $config[$platform]['class'] ?? 'social-brand-website';
}

function getSocialIconSvg($platform) {
    $platform = normalizeSocialPlatform($platform);
    $icons = [
        'facebook' => '<path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.748c0-.855.192-1.136 1.136-1.136h2.864v-4h-3.864c-3.432 0-5.136 1.703-5.136 5.136v1.748z"/>',
        'instagram' => '<path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 3.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11zm0 2a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7zm5.75-3.25a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5z"/>',
        'twitter' => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
        'youtube' => '<path d="M21.8 8.001a2.5 2.5 0 0 0-1.76-1.77C18.36 6 12 6 12 6s-6.36 0-8.04.231A2.5 2.5 0 0 0 2.2 8.001 26.3 26.3 0 0 0 2 12a26.3 26.3 0 0 0 .2 3.999 2.5 2.5 0 0 0 1.76 1.77C5.64 18 12 18 12 18s6.36 0 8.04-.231a2.5 2.5 0 0 0 1.76-1.77A26.3 26.3 0 0 0 22 12a26.3 26.3 0 0 0-.2-3.999zM10 15.5v-7l6 3.5-6 3.5z"/>',
        'linkedin' => '<path d="M4.98 3.5a2.25 2.25 0 1 1 0 4.5 2.25 2.25 0 0 1 0-4.5zM3 8.75h3.96V21H3V8.75zm7.53 0H14.5v1.67h.05c.55-1.04 1.9-2.14 3.91-2.14 4.18 0 4.95 2.75 4.95 6.33V21h-4v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94V21h-4V8.75z"/>',
        'tiktok' => '<path d="M16.6 5.82s.51.5 0 0A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 0 1-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.69V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.1 4.1 0 0 1-1-.48z"/>',
        'telegram' => '<path d="M20.665 3.717l-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701-.33 4.955c.488 0 .705-.223.978-.488l2.35-2.285 4.888 3.61c.9.497 1.55.241 1.774-.838l3.203-15.1c.33-1.32-.505-1.925-1.371-1.533z"/>',
        'message-circle' => '<path d="M12 2a9 9 0 0 0-7.74 13.6L2 22l6.55-2.12A9 9 0 1 0 12 2zm0 2a7 7 0 0 1 5.6 11.2l-.35.46.12.74.55-1.78 1.78-.55-.74-.12-.46-.35A7 7 0 0 1 12 4zm-3.5 4.5a.75.75 0 0 0 0 1.5h7a.75.75 0 0 0 0-1.5h-7zm0 3a.75.75 0 0 0 0 1.5h4.5a.75.75 0 0 0 0-1.5H8.5z"/>',
        'globe' => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm7.93 9h-3.18a15.7 15.7 0 0 0-1.2-4.96A8.03 8.03 0 0 1 19.93 11zM12 4c.95 1.57 1.63 3.36 1.93 5.25h-3.86C10.37 7.36 11.05 5.57 12 4zM8.45 6.04A15.7 15.7 0 0 0 7.25 11H4.07a8.03 8.03 0 0 1 4.38-4.96zM4.07 13h3.18c.2 1.74.74 3.38 1.55 4.82A8.03 8.03 0 0 1 4.07 13zm7.93 7c-.95-1.57-1.63-3.36-1.93-5.25h3.86c-.3 1.89-.98 3.68-1.93 5.25zm3.12-5.25h3.18a8.03 8.03 0 0 1-4.38 4.96c.81-1.44 1.35-3.08 1.55-4.96zm1.38-2H16.3a13.6 13.6 0 0 0-1.38-4.01A8.02 8.02 0 0 1 17.5 11zM11.08 6.99A13.6 13.6 0 0 0 9.7 11H6.5a8.02 8.02 0 0 1 4.58-4.01zM6.5 13h3.2c.3 1.45.86 2.8 1.58 3.99A8.02 8.02 0 0 1 6.5 13z"/>',
        'mail' => '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 2v.01L12 13l8-6.99V6H4zm16 12V9.25l-7.4 5.55a1 1 0 0 1-1.2 0L4 9.25V18h16z"/>',
        'custom' => '<path d="M11 5h2v6h6v2h-6v6h-2v-6h-6v-2h6z"/>',
    ];

    $path = $icons[$platform] ?? $icons['globe'];
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white" style="display:block;width:100%;height:100%;" aria-hidden="true">' . $path . '</svg>';
}

/**
 * Render a branded social icon badge (gradient circle + white glyph)
 */
function renderSocialIconBadge($platform, $options = [], $item = []) {
    $size = isset($options['size']) ? $options['size'] : 'md';
    $active = !empty($options['active']);
    $muted = !empty($options['muted']);
    $class = getSocialPlatformClass($platform);
    $label = htmlspecialchars(getSocialPlatformLabel($platform, $item));

    $sizeClass = 'social-icon-md';
    switch ($size) {
        case 'sm': $sizeClass = 'social-icon-sm'; break;
        case 'lg': $sizeClass = 'social-icon-lg'; break;
        case 'contact': $sizeClass = 'social-icon-contact'; break;
    }

    $stateClass = $active ? 'social-icon-active' : ($muted ? 'social-icon-muted' : '');
    $extra = htmlspecialchars(isset($options['extraClass']) ? $options['extraClass'] : '');
    $svg = getSocialIconSvg($platform);

    return '<span class="social-icon-badge ' . $sizeClass . ' ' . $class . ' ' . $stateClass . ' ' . $extra . '" title="' . $label . '" aria-label="' . $label . '" style="display:inline-flex;align-items:center;justify-content:center;border-radius:9999px;overflow:hidden;background:#333;">
    <span class="social-icon-glow"></span>
    <span class="social-icon-surface"></span>
    <span class="social-icon-glyph-wrapper" style="position:relative;z-index:10;display:block;width:1.25rem;height:1.25rem;color:white;fill:white;">
        ' . $svg . '
    </span>
</span>';
}

function renderSocialIconsStylesheet() {
    echo '<link rel="stylesheet" href="public/css/social-icons.css">';
}
