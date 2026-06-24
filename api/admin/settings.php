<?php
// api/admin/settings.php

ini_set('display_errors', '0');
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/SettingsManager.php';
require_once __DIR__ . '/../../includes/auth.php';

$manager = new SettingsManager();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        requireApiAuth(['admin'], 'settings:view');
    } else {
        requireApiAuth(['admin'], 'settings:update');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // GET: Fetch all settings
    if ($method === 'GET') {
        $branding = $manager->getBranding();
        $config = $manager->getSetting('configuration') ?? [];
        
        $response = [
            'logo_url' => $branding['logo_url'] ?? '',
            'favicon_url' => $branding['favicon_url'] ?? ($branding['logo_url'] ?? ''),
            'app_name' => $branding['app_name'] ?? 'ABE HOTEL',
            'app_tagline' => $branding['app_tagline'] ?? 'HOTEL MANAGEMENT SYSTEM',
            'vat_rate' => $config['vat_rate'] ?? 0.08,
            'enable_cashier_printing' => $config['enable_cashier_printing'] ?? true,
            'enable_cashier_today_revenue' => $config['enable_cashier_today_revenue'] ?? false
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

        $response = [
            'success' => true,
            'key' => $key,
            'type' => $type,
            'updated_at' => date('c')
        ];
        if (!in_array($key, ['logo_url', 'favicon_url'], true)) {
            $response['value'] = $value;
        }
        echo json_encode($response);
    }
    
    // POST: Upload image
    else if ($method === 'POST' && isset($_FILES['file'])) {
        $type = $_GET['type'] ?? 'logo';
        
        try {
            if ($type === 'logo') {
                $uploaded = $manager->uploadLogoAndFavicon($_FILES['file']);
                echo json_encode([
                    'success' => true,
                    'url' => $uploaded['logo_url'],
                    'favicon_url' => $uploaded['favicon_url'],
                    'type' => $type
                ]);
            } else {
                $base64 = $manager->uploadImage($_FILES['file'], 'favicon');
                $manager->updateSetting('branding', 'favicon_url', $base64);
                echo json_encode([
                    'success' => true,
                    'url' => $base64,
                    'favicon_url' => $base64,
                    'type' => $type
                ]);
            }
            exit;
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
