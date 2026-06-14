<?php
/**
 * Website CMS helper — load defaults + merge stored content
 */
function getDefaultCmsData(): array {
    return [
        'hero' => [
            'eyebrow' => 'Welcome to',
            'headline' => 'Abe Hotel',
            'subtitle' => 'Refined accommodation and authentic dining — where comfort meets Ethiopian hospitality.',
            'background_image' => 'assets/cms/defaults/hero.png',
            'cta_text' => 'Discover More',
            'cta_link' => '#services',
        ],
        'sections' => [
            'about' => [
                'badge' => 'The Heritage',
                'title' => 'About Abe Hotel',
            ],
            'services' => [
                'badge' => 'Exceptional Hospitality',
                'title' => 'Our Services',
                'subtitle' => 'From elegant rooms to authentic cuisine — everything you need for a memorable stay.',
            ],
            'gallery' => [
                'badge' => 'Culinary Masterpieces',
                'title' => 'Menu Gallery',
                'subtitle' => 'Explore our signature dishes, traditional flavors, and artisan beverages.',
            ],
            'contact' => [
                'title' => 'Contact Us',
            ],
            'social' => [
                'title' => 'Follow Us',
            ],
            'vision' => [
                'title' => 'Our Vision',
                'content' => 'To redefine hospitality by blending timeless Ethiopian traditions with contemporary comfort, ensuring every guest experience is nothing short of sublime.',
            ],
        ],
        'about' => [
            'title' => 'About Abe Hotel',
            'content' => "Abe Hotel offers a warm welcome in the heart of the city. Our thoughtfully designed rooms, attentive staff, and renowned restaurant create the perfect setting for both business travelers and families.\n\nWhether you are here for a short visit or an extended stay, we are committed to making you feel at home from the moment you arrive.",
            'image' => 'assets/cms/defaults/about.jpg',
        ],
        'services' => [
            [
                'id' => 'svc-rooms',
                'title' => 'Luxury Rooms',
                'description' => 'Spacious, elegantly furnished rooms with modern amenities and serene comfort.',
                'icon' => 'bed-double',
                'image' => 'assets/cms/defaults/service-rooms.jpg',
            ],
            [
                'id' => 'svc-dining',
                'title' => 'Authentic Dining',
                'description' => 'Savor local and international dishes prepared by our expert chefs.',
                'icon' => 'utensils-crossed',
                'image' => 'assets/cms/defaults/service-dining.png',
            ],
            [
                'id' => 'svc-events',
                'title' => 'Events & Meetings',
                'description' => 'Flexible event spaces for conferences, celebrations, and private gatherings.',
                'icon' => 'concierge-bell',
                'image' => 'assets/cms/defaults/gallery-2.png',
            ],
        ],
        'contact' => [
            'phone' => '+251 900 000 000',
            'email' => 'info@abehotel.com',
            'address' => 'Dilla, Ethiopia',
        ],
        'social' => [
            ['platform' => 'facebook', 'link' => 'https://facebook.com'],
            ['platform' => 'instagram', 'link' => 'https://instagram.com'],
            ['platform' => 'telegram', 'link' => 'https://t.me'],
        ],
        'gallery' => [
            ['id' => 'gal-1', 'title' => 'Chef\'s Special', 'image' => 'assets/cms/defaults/gallery-1.jpg'],
            ['id' => 'gal-2', 'title' => 'Traditional Platter', 'image' => 'assets/cms/defaults/gallery-2.png'],
            ['id' => 'gal-3', 'title' => 'House Favorite', 'image' => 'assets/cms/defaults/gallery-3.jpg'],
            ['id' => 'gal-4', 'title' => 'Signature Dish', 'image' => 'assets/cms/defaults/gallery-4.png'],
        ],
        'seo' => [
            'description' => 'Abe Hotel & Spa in Dilla, Ethiopia offers refined accommodation and authentic dining. The best hotel choice in Dilla city center for business and leisure.',
            'keywords' => 'Abe Hotel Dilla, Hotel in Dilla Ethiopia, Best Hotel Dilla, Dilla Ethiopia Accommodation, Ethiopia Hospitality, Luxury Rooms Dilla',
            'og_image' => 'assets/cms/defaults/hero.png',
            'google_verification' => '',
        ],
    ];
}

function getCmsData(): array {
    $defaults = getDefaultCmsData();
    
    try {
        $stored = db('cms')->findUnique(['where' => ['id' => 'website_config']]);
        if (!$stored) {
            // Check for legacy record without the 'website_config' ID if migration created it differently
            $all = db('cms')->findMany(['take' => 1]);
            $stored = $all[0] ?? null;
        }

        if (!$stored) {
            $defaults['__is_default'] = true;
            return $defaults;
        }
        
        // The data is actually stored as the record itself in our SqliteDB implementation
        // but we need to remove the internal id/createdAt/etc if they exist
        unset($stored['id'], $stored['createdAt'], $stored['updatedAt'], $stored['isDeleted']);
    } catch (Exception $e) {
        error_log("[CMS] Database read failed: " . $e->getMessage());
        $defaults['__is_default'] = true;
        return $defaults;
    }
    
    // Merging logic:
    
    // Merging logic:
    // 1. Start with defaults
    // 2. Overlay stored data. We use array_replace_recursive for nested objects (sections, hero, about, contact, seo)
    // 3. BUT for lists (services, gallery, social), we want to COMPLETELY use the stored version if it exists
    
    $out = array_replace_recursive($defaults, $stored);
    
    // Explicitly override list keys so deletions/reorders work correctly
    foreach (['social', 'services', 'gallery'] as $listKey) {
        if (isset($stored[$listKey]) && is_array($stored[$listKey])) {
            $out[$listKey] = $stored[$listKey];
        }
    }
    
    return $out;
}

function cmsSection(array $cms, string $key): array {
    return $cms['sections'][$key] ?? [];
}

/**
 * Validate and merge incoming CMS payload onto existing stored data.
 */
function saveCmsPayload(array $input): array {
    $existing = getCmsData();
    $defaults = getDefaultCmsData();
    $out = $existing;

    foreach (['hero', 'about', 'contact', 'sections', 'seo'] as $key) {
        if (isset($input[$key]) && is_array($input[$key])) {
            $out[$key] = array_replace_recursive($out[$key] ?? ($defaults[$key] ?? []), $input[$key]);
        }
    }

    foreach (['services', 'gallery'] as $key) {
        if (isset($input[$key]) && is_array($input[$key])) {
            $out[$key] = $input[$key];
        }
    }

    if (isset($input['social']) && is_array($input['social'])) {
        $out['social'] = array_slice(array_values($input['social']), 0, 4);
    }

    foreach (array_keys($defaults) as $key) {
        if (!isset($out[$key])) {
            $out[$key] = $defaults[$key];
        }
    }

    return $out;
}

function writeCmsData(array $data): bool {
    // Remove internal flags before writing
    if (isset($data['__is_default'])) {
        unset($data['__is_default']);
    }

    try {
        db('cms')->updateMany([
            'where' => ['id' => ['not' => '']], // Update all or replace
            'data' => $data
        ]);
        
        // If nothing was updated, create it
        if (db('cms')->count() === 0) {
            $data['id'] = 'website_config';
            db('cms')->create(['data' => $data]);
        }
        return true;
    } catch (Exception $e) {
        error_log("[CMS] Database write failed: " . $e->getMessage());
        return false;
    }
}

function resetCmsToDefaults(): array {
    $defaults = getDefaultCmsData();
    writeCmsData($defaults);
    return $defaults;
}
