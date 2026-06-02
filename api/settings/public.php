<?php
header('Content-Type: application/json');
require_once '../../includes/JsonDB.php';

require_once '../../includes/JsonDB.php';

/**
 * Public Settings API
 * Exposes non-sensitive branding and configuration
 */

try {
    $settings = db('settings')->findMany();
    
    // Map key-value pairs
    $publicData = [];
    $publicKeys = ['logo_url', 'favicon_url', 'app_name', 'app_tagline', 'vat_rate', 'enable_cashier_printing', 'enable_cashier_today_revenue'];
    
    // Set Defaults
    $publicData = [
        'logo_url' => '',
        'favicon_url' => '',
        'app_name' => 'Prime Addis',
        'app_tagline' => 'Coffee Management',
        'vat_rate' => '0.15',
        'enable_cashier_printing' => 'true',
        'enable_cashier_today_revenue' => 'false'
    ];

    foreach ($settings as $s) {
        if (in_array($s['key'], $publicKeys)) {
            $publicData[$s['key']] = $s['value'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $publicData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
