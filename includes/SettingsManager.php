<?php
// includes/SettingsManager.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/JsonDB.php';

class SettingsManager {
    private static $brandingKeys = ['logo_url', 'favicon_url', 'app_name', 'app_tagline'];
    private static $legacyConfigKeys = ['vat_rate', 'enable_cashier_printing', 'enable_cashier_today_revenue'];
    private $storageDir;
    private $settingsFile;
    private $categoriesFile;
    private $tablesFile;
    private $floorsFile;

    public function __construct() {
        // Adjust paths to be absolute and consistent with project root
        $baseDir = dirname(__DIR__);
        $this->storageDir = $baseDir . '/storage';
        $this->settingsFile = $this->storageDir . '/settings.json';
        $this->categoriesFile = $this->storageDir . '/categories.json';
        $this->tablesFile = $this->storageDir . '/tables.json';
        $this->floorsFile = $this->storageDir . '/floors.json';
        
        $this->ensureStorageDir();
        $this->initializeFiles();
    }

    private function ensureStorageDir() {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
        if (!is_dir($this->storageDir . '/uploads')) {
            mkdir($this->storageDir . '/uploads', 0755, true);
        }
    }

    private function initializeFiles() {
        // Initialize settings.json
        if (!file_exists($this->settingsFile)) {
            $defaults = [
                'branding' => [
                    'logo_url' => '',
                    'favicon_url' => '',
                    'app_name' => 'Prime Addis',
                    'app_tagline' => 'Coffee Management',
                    'updated_at' => date('c')
                ],
                'configuration' => [
                    'vat_rate' => 0.08,
                    'enable_cashier_printing' => true,
                    'enable_cashier_today_revenue' => false,
                    'updated_at' => date('c')
                ],
                'version' => '1.0.0'
            ];
            $this->writeJson($this->settingsFile, $defaults);
        }

        // Initialize categories.json
        if (!file_exists($this->categoriesFile)) {
            $defaults = [
                'menu' => [],
                'stock' => [],
                'distribution' => []
            ];
            $this->writeJson($this->categoriesFile, $defaults);
        }

        // Initialize tables.json
        if (!file_exists($this->tablesFile)) {
            $this->writeJson($this->tablesFile, ['tables' => []]);
        }

        // Initialize floors.json
        if (!file_exists($this->floorsFile)) {
            $this->writeJson($this->floorsFile, ['floors' => []]);
        }
    }

    private function writeJson($file, $data) {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($file, $json, LOCK_EX);
    }

    public function readJson($file) {
        if (!file_exists($file)) {
            return null;
        }
        $content = file_get_contents($file);
        return json_decode($content, true);
    }

    // SETTINGS METHODS
    public function getAllSettings() {
        return $this->readJson($this->settingsFile);
    }

    /**
     * Branding with one-time migration from legacy data/settings.json
     */
    public function getBranding() {
        $settings = $this->getAllSettings();
        $branding = $settings['branding'] ?? [];
        $legacy = $this->readLegacySettings();
        $changed = false;

        foreach (self::$brandingKeys as $key) {
            if (empty($branding[$key]) && !empty($legacy[$key])) {
                $branding[$key] = $legacy[$key];
                $changed = true;
            }
        }

        if ($changed) {
            $settings['branding'] = array_merge($settings['branding'] ?? [], $branding, ['updated_at' => date('c')]);
            $this->writeJson($this->settingsFile, $settings);
        }

        return $branding;
    }

    public function getBrandingVars() {
        $b = $this->getBranding();
        $logo = $b['logo_url'] ?? '';
        
        // Provide stable URLs for public headers/SEOs
        $apiLogo = !empty($logo) ? 'api/branding-image.php?type=logo' : '';
        $apiFav  = !empty($b['favicon_url']) ? 'api/branding-image.php?type=favicon' : $apiLogo;

        return [
            'appName' => !empty($b['app_name']) ? $b['app_name'] : 'ABE HOTEL',
            'appTagline' => !empty($b['app_tagline']) ? $b['app_tagline'] : 'HOTEL MANAGEMENT SYSTEM',
            'logoUrl' => $logo, // Still keep raw for internal/admin use
            'publicLogoUrl' => $apiLogo,
            'faviconUrl' => $apiFav,
        ];
    }

    private function readLegacySettings() {
        $map = [];
        try {
            foreach (db('settings')->findMany() as $row) {
                if (!empty($row['key'])) {
                    $map[$row['key']] = $row['value'];
                }
            }
        } catch (Exception $e) {
            error_log('Legacy settings read failed: ' . $e->getMessage());
        }
        return $map;
    }

    private function syncKeyToLegacy($key, $value, $type = 'string') {
        try {
            $db = db('settings');
            $stored = is_bool($value) ? ($value ? 'true' : 'false') : (string)$value;
            $rows = $db->findMany();

            foreach ($rows as $row) {
                if (($row['key'] ?? '') !== $key) continue;

                $where = !empty($row['id']) ? ['id' => $row['id']] : ['key' => $key];
                $db->update([
                    'where' => $where,
                    'data' => [
                        'value' => $stored,
                        'type' => $type,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                ]);
                return;
            }

            $db->create([
                'data' => [
                    'key' => $key,
                    'value' => $stored,
                    'type' => $type,
                ]
            ]);
        } catch (Exception $e) {
            error_log('Legacy settings sync failed: ' . $e->getMessage());
        }
    }

    public function updateSetting($section, $key, $value) {
        $settings = $this->getAllSettings();
        if (!isset($settings[$section])) {
            $settings[$section] = [];
        }
        $settings[$section][$key] = $value;
        $settings[$section]['updated_at'] = date('c');
        $this->writeJson($this->settingsFile, $settings);

        if ($section === 'branding' && in_array($key, self::$brandingKeys, true)) {
            $this->syncKeyToLegacy($key, $value, $key === 'logo_url' || $key === 'favicon_url' ? 'url' : 'string');
        }
        if ($section === 'configuration' && in_array($key, self::$legacyConfigKeys, true)) {
            $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string');
            $this->syncKeyToLegacy($key, $value, $type);
        }

        return $settings;
    }

    public function getSetting($section, $key = null) {
        $settings = $this->getAllSettings();
        if (!isset($settings[$section])) {
            return null;
        }
        return $key ? ($settings[$section][$key] ?? null) : $settings[$section];
    }

    // CATEGORIES METHODS
    public function getCategories($type = null) {
        $categories = $this->readJson($this->categoriesFile);
        return $type ? ($categories[$type] ?? []) : $categories;
    }

    public function addCategory($type, $name, $description = '') {
        $categories = $this->readJson($this->categoriesFile);
        $id = 'cat_' . uniqid();
        
        $categories[$type][] = [
            'id' => $id,
            'name' => $name,
            'type' => $type,
            'description' => $description,
            'created_at' => date('c')
        ];
        
        $this->writeJson($this->categoriesFile, $categories);
        return $categories[$type][count($categories[$type]) - 1];
    }

    public function updateCategory($type, $id, $name, $description = '') {
        $categories = $this->readJson($this->categoriesFile);
        
        foreach ($categories[$type] as &$cat) {
            if ($cat['id'] === $id) {
                $cat['name'] = $name;
                $cat['description'] = $description;
                $cat['updated_at'] = date('c');
                break;
            }
        }
        
        $this->writeJson($this->categoriesFile, $categories);
        return $categories[$type];
    }

    public function deleteCategory($type, $id) {
        $categories = $this->readJson($this->categoriesFile);
        
        $categories[$type] = array_filter($categories[$type], function($cat) use ($id) {
            return $cat['id'] !== $id;
        });
        
        $categories[$type] = array_values($categories[$type]); // Reset keys
        
        $this->writeJson($this->categoriesFile, $categories);
        return true;
    }

    // TABLES METHODS
    public function getTables() {
        $data = $this->readJson($this->tablesFile);
        return $data['tables'] ?? [];
    }

    public function addTable($tableNumber, $capacity, $floor_id = null) {
        $data = $this->readJson($this->tablesFile);
        $id = 'tbl_' . uniqid();
        
        $data['tables'][] = [
            'id' => $id,
            'tableNumber' => $tableNumber,
            'capacity' => (int)$capacity,
            'floor_id' => $floor_id,
            'status' => 'available',
            'created_at' => date('c')
        ];
        
        $this->writeJson($this->tablesFile, $data);
        return $data['tables'][count($data['tables']) - 1];
    }

    public function updateTable($id, $tableNumber, $capacity) {
        $data = $this->readJson($this->tablesFile);
        
        foreach ($data['tables'] as &$table) {
            if ($table['id'] === $id) {
                $table['tableNumber'] = $tableNumber;
                $table['capacity'] = (int)$capacity;
                $table['updated_at'] = date('c');
                break;
            }
        }
        
        $this->writeJson($this->tablesFile, $data);
        return $data['tables'];
    }

    public function deleteTable($id) {
        $data = $this->readJson($this->tablesFile);
        
        $data['tables'] = array_filter($data['tables'], function($table) use ($id) {
            return $table['id'] !== $id;
        });
        
        $data['tables'] = array_values($data['tables']);
        
        $this->writeJson($this->tablesFile, $data);
        return true;
    }

    // FLOORS METHODS
    public function getFloors() {
        $data = $this->readJson($this->floorsFile);
        return $data['floors'] ?? [];
    }

    public function addFloor($floorNumber, $order) {
        $data = $this->readJson($this->floorsFile);
        $id = 'floor_' . uniqid();
        
        $data['floors'][] = [
            'id' => $id,
            'floorNumber' => $floorNumber,
            'order' => (int)$order,
            'created_at' => date('c')
        ];
        
        $this->writeJson($this->floorsFile, $data);
        return $data['floors'][count($data['floors']) - 1];
    }

    public function updateFloor($id, $floorNumber, $order) {
        $data = $this->readJson($this->floorsFile);
        
        foreach ($data['floors'] as &$floor) {
            if ($floor['id'] === $id) {
                $floor['floorNumber'] = $floorNumber;
                $floor['order'] = (int)$order;
                $floor['updated_at'] = date('c');
                break;
            }
        }
        
        $this->writeJson($this->floorsFile, $data);
        return $data['floors'];
    }

    public function deleteFloor($id) {
        $data = $this->readJson($this->floorsFile);
        
        $data['floors'] = array_filter($data['floors'], function($floor) use ($id) {
            return $floor['id'] !== $id;
        });
        
        $data['floors'] = array_values($data['floors']);
        
        // Remove tables associated with this floor
        $tables = $this->readJson($this->tablesFile);
        $tables['tables'] = array_filter($tables['tables'], function($table) use ($id) {
            return $table['floor_id'] !== $id;
        });
        $tables['tables'] = array_values($tables['tables']);
        
        $this->writeJson($this->tablesFile, $tables);
        $this->writeJson($this->floorsFile, $data);
        
        return true;
    }

    // IMAGE UPLOAD METHODS
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
