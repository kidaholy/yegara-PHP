<?php
// includes/SettingsManager.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/JsonDB.php';

class SettingsManager {
    private static $brandingKeys = ['logo_url', 'favicon_url', 'app_name', 'app_tagline'];
    private static $legacyConfigKeys = ['vat_rate', 'enable_cashier_printing', 'enable_cashier_today_revenue'];

    public function __construct($autoInit = false) {
        if ($autoInit) {
            $this->initializeFiles();
        }
    }

    public function initializeFiles($force = false) {
        // Initialize settings if they don't exist
        if ($force || db('settings')->count(['where' => ['id' => 'settings_branding']]) === 0) {
            db('settings')->create(['data' => [
                'id' => 'settings_branding',
                'app_name' => 'Prime Addis',
                'app_tagline' => 'Coffee Management',
                'logo_url' => '',
                'favicon_url' => '',
                'updated_at' => date('c')
            ]]);
        }
        if ($force || db('settings')->count(['where' => ['id' => 'settings_configuration']]) === 0) {
            db('settings')->create(['data' => [
                'id' => 'settings_configuration',
                'vat_rate' => 0.08,
                'enable_cashier_printing' => true,
                'enable_cashier_today_revenue' => false,
                'updated_at' => date('c')
            ]]);
        }
    }

    // SETTINGS METHODS
    public function getAllSettings() {
        $branding = db('settings')->findUnique(['where' => ['id' => 'settings_branding']]) ?: [];
        $config = db('settings')->findUnique(['where' => ['id' => 'settings_configuration']]) ?: [];
        return [
            'branding' => $branding,
            'configuration' => $config,
            'version' => '1.1.0 (SQLite)'
        ];
    }

    public function getBranding() {
        return db('settings')->findUnique(['where' => ['id' => 'settings_branding']]) ?: [];
    }

    public function getBrandingVars() {
        $b = $this->getBranding();
        $logo = $b['logo_url'] ?? '';
        
        $apiLogo = !empty($logo) ? 'api/branding-image.php?type=logo' : '';
        $apiFav  = !empty($b['favicon_url']) ? 'api/branding-image.php?type=favicon' : $apiLogo;

        return [
            'appName' => !empty($b['app_name']) ? $b['app_name'] : 'ABE HOTEL',
            'appTagline' => !empty($b['app_tagline']) ? $b['app_tagline'] : 'HOTEL MANAGEMENT SYSTEM',
            'logoUrl' => $logo,
            'publicLogoUrl' => $apiLogo,
            'faviconUrl' => $apiFav,
        ];
    }

    public function updateSetting($section, $key, $value) {
        $id = ($section === 'branding') ? 'settings_branding' : 'settings_configuration';
        $item = db('settings')->findUnique(['where' => ['id' => $id]]) ?: ['id' => $id];
        
        $item[$key] = $value;
        $item['updated_at'] = date('c');
        
        db('settings')->updateMany([
            'where' => ['id' => $id],
            'data' => $item
        ]);

        return $this->getAllSettings();
    }

    public function getSetting($section, $key = null) {
        $id = ($section === 'branding') ? 'settings_branding' : 'settings_configuration';
        $settings = db('settings')->findUnique(['where' => ['id' => $id]]) ?: [];
        return $key ? ($settings[$key] ?? null) : $settings;
    }

    // CATEGORIES METHODS
    public function getCategories($type = null) {
        $where = $type ? ['type' => $type] : [];
        $all = db('categories')->findMany(['where' => $where]);
        
        if ($type) return $all;
        
        $grouped = [];
        foreach ($all as $cat) {
            $grp = $cat['type'] ?? ($cat['group'] ?? 'menu');
            $grouped[$grp][] = $cat;
        }
        return $grouped;
    }

    public function addCategory($type, $name, $description = '') {
        $newItem = [
            'name' => $name,
            'group' => $type,
            'type' => $type,
            'description' => $description,
            'created_at' => date('c')
        ];
        return db('categories')->create(['data' => $newItem]);
    }

    public function updateCategory($type, $id, $name, $description = '') {
        return db('categories')->update([
            'where' => ['id' => $id],
            'data' => [
                'name' => $name,
                'description' => $description,
                'updated_at' => date('c')
            ]
        ]);
    }

    public function deleteCategory($type, $id) {
        db('categories')->delete(['where' => ['id' => $id]]);
        return true;
    }

    // TABLES METHODS
    public function getTables() {
        return db('tables')->findMany();
    }

    public function addTable($tableNumber, $capacity, $floor_id = null) {
        return db('tables')->create(['data' => [
            'tableNumber' => $tableNumber,
            'capacity' => (int)$capacity,
            'floor_id' => $floor_id,
            'status' => 'available',
            'created_at' => date('c')
        ]]);
    }

    public function updateTable($id, $tableNumber, $capacity) {
        return db('tables')->update([
            'where' => ['id' => $id],
            'data' => [
                'tableNumber' => $tableNumber,
                'capacity' => (int)$capacity,
                'updated_at' => date('c')
            ]
        ]);
    }

    public function deleteTable($id) {
        db('tables')->delete(['where' => ['id' => $id]]);
        return true;
    }

    // FLOORS METHODS
    public function getFloors() {
        return db('floors')->findMany(['orderBy' => ['order' => 'asc']]);
    }

    public function addFloor($floorNumber, $order) {
        return db('floors')->create(['data' => [
            'floorNumber' => $floorNumber,
            'order' => (int)$order,
            'created_at' => date('c')
        ]]);
    }

    public function updateFloor($id, $floorNumber, $order) {
        return db('floors')->update([
            'where' => ['id' => $id],
            'data' => [
                'floorNumber' => $floorNumber,
                'order' => (int)$order,
                'updated_at' => date('c')
            ]
        ]);
    }

    public function deleteFloor($id) {
        db('floors')->delete(['where' => ['id' => $id]]);
        // Also remove tables associated with this floor
        db('tables')->deleteMany(['where' => ['floor_id' => $id]]);
        return true;
    }

    // IMAGE UPLOAD METHODS (No changes needed, these are logic-only)
    public function uploadImage($file, $type = 'logo') {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }

        $mime = $file['type'] ?? '';
        if (!$mime || $mime === 'application/octet-stream') {
            $info = @getimagesize($file['tmp_name']);
            $mime = $info['mime'] ?? '';
        }
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/jpg', 'image/pjpeg'];
        if (!in_array($mime, $allowed, true)) {
            throw new Exception('Invalid image type. Use JPG, PNG, WebP, or GIF.');
        }
        $file['type'] = $mime;

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('File too large');
        }

        $dims = $type === 'favicon' ? [64, 64, 85] : [200, 200, 90];
        return $this->imageFileToDataUrl($file['tmp_name'], $file['type'], $dims[0], $dims[1], $dims[2]);
    }

    public function uploadLogoAndFavicon($file) {
        $logo = $this->uploadImage($file, 'logo');
        $favicon = $this->imageFileToDataUrl($file['tmp_name'], $file['type'], 64, 64, 85);
        $this->updateSetting('branding', 'logo_url', $logo);
        $this->updateSetting('branding', 'favicon_url', $favicon);
        return ['logo_url' => $logo, 'favicon_url' => $favicon];
    }

    private function imageFileToDataUrl($filePath, $mime, $maxWidth = 200, $maxHeight = 200, $quality = 90) {
        if (!function_exists('imagecreatefromstring')) {
            return $this->fileToDataUrlFallback($filePath);
        }

        $image = $this->compressImage($filePath, $maxWidth, $maxHeight, $quality);
        return 'data:image/jpeg;base64,' . base64_encode($image);
    }

    private function fileToDataUrlFallback($filePath) {
        $raw = file_get_contents($filePath);
        if ($raw === false || $raw === '') {
            throw new Exception('Could not read uploaded image');
        }

        $info = @getimagesize($filePath);
        if ($info === false) {
            throw new Exception('Invalid image file');
        }

        $mime = $info['mime'] ?? 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    private function compressImage($filePath, $maxWidth = 200, $maxHeight = 200, $quality = 90) {
        if (!function_exists('imagecreatefromstring')) {
            return file_get_contents($filePath);
        }

        $image = imagecreatefromstring(file_get_contents($filePath));
        if ($image === false) {
            throw new Exception('Could not process image');
        }

        list($width, $height) = getimagesize($filePath);

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = max(1, (int)($width * $ratio));
        $newHeight = max(1, (int)($height * $ratio));

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($thumb, null, $quality);
        $output = ob_get_clean();

        imagedestroy($image);
        imagedestroy($thumb);

        return $output;
    }
}
