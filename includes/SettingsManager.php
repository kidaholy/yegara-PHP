<?php
// includes/SettingsManager.php

class SettingsManager {
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

    public function updateSetting($section, $key, $value) {
        $settings = $this->getAllSettings();
        if (!isset($settings[$section])) {
            $settings[$section] = [];
        }
        $settings[$section][$key] = $value;
        $settings[$section]['updated_at'] = date('c');
        $this->writeJson($this->settingsFile, $settings);
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

    public function addTable($tableNumber, $capacity, $floor_id) {
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

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            throw new Exception('Invalid image type');
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception('File too large');
        }

        // Compress image
        $image = $this->compressImage($file['tmp_name']);
        $base64 = 'data:' . $file['type'] . ';base64,' . base64_encode($image);
        
        return $base64;
    }

    private function compressImage($filePath, $maxWidth = 200, $maxHeight = 200, $quality = 90) {
        $image = imagecreatefromstring(file_get_contents($filePath));
        
        list($width, $height) = getimagesize($filePath);
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        
        // Handle transparency
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        ob_start();
        imagejpeg($thumb, null, $quality); // JPEG doesn't support alpha, use png for alpha if needed
        $output = ob_get_clean();
        
        imagedestroy($image);
        imagedestroy($thumb);
        
        return $output;
    }
}
