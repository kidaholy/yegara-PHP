<?php
// api/admin/settings.php

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/SettingsManager.php';
require_once __DIR__ . '/../../includes/auth.php';

$manager = new SettingsManager();

try {
    // Verify admin role using the project's existing auth method
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    // GET: Fetch all settings
    if ($method === 'GET') {
        $settings = $manager->getAllSettings();
        
        // Transform to match frontend format
        $response = [
            'logo_url' => $settings['branding']['logo_url'] ?? '',
            'favicon_url' => $settings['branding']['favicon_url'] ?? '',
            'app_name' => $settings['branding']['app_name'] ?? 'Prime Addis',
            'app_tagline' => $settings['branding']['app_tagline'] ?? 'Coffee Management',
            'vat_rate' => $settings['configuration']['vat_rate'] ?? 0.08,
            'enable_cashier_printing' => $settings['configuration']['enable_cashier_printing'] ?? true,
            'enable_cashier_today_revenue' => $settings['configuration']['enable_cashier_today_revenue'] ?? false
        ];
        
        echo json_encode($response);
    }
    
    // PUT: Update a setting
    else if ($method === 'PUT') {
        $key = $input['key'] ?? null;
        $value = $input['value'] ?? null;
        $type = $input['type'] ?? 'string';

        if (!$key || $value === null) {
            http_response_code(400);
            echo json_encode(['message' => 'Key and value are required']);
            exit;
        }

        // Determine section (branding or configuration)
        $brandingKeys = ['logo_url', 'favicon_url', 'app_name', 'app_tagline'];
        $section = in_array($key, $brandingKeys) ? 'branding' : 'configuration';

        // Type conversion
        if ($type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } else if ($type === 'number') {
            $value = (float)$value;
        }

        $manager->updateSetting($section, $key, $value);

        echo json_encode([
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'updated_at' => date('c')
        ]);
    }
    
    // POST: Upload image
    else if ($method === 'POST' && isset($_FILES['file'])) {
        $type = $_GET['type'] ?? 'logo';
        
        try {
            $base64 = $manager->uploadImage($_FILES['file']);
            
            if ($type === 'logo') {
                $manager->updateSetting('branding', 'logo_url', $base64);
            } else if ($type === 'favicon') {
                $manager->updateSetting('branding', 'favicon_url', $base64);
            }

            echo json_encode([
                'success' => true,
                'url' => $base64,
                'type' => $type
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['message' => $e->getMessage()]);
        }
    }
    
    else {
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => $e->getMessage()]);
}
