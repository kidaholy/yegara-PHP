<?php
header('Content-Type: application/json');
require_once '../../includes/SettingsManager.php';

/**
 * Public Settings API — non-sensitive branding and configuration
 */
try {
    $manager = new SettingsManager();
    $branding = $manager->getBranding();
    $config = $manager->getSetting('configuration') ?? [];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'logo_url' => $branding['logo_url'] ?? '',
            'favicon_url' => $branding['favicon_url'] ?? ($branding['logo_url'] ?? ''),
            'app_name' => $branding['app_name'] ?? 'ABE HOTEL',
            'app_tagline' => $branding['app_tagline'] ?? 'HOTEL MANAGEMENT SYSTEM',
            'vat_rate' => (string)($config['vat_rate'] ?? '0.15'),
            'enable_cashier_printing' => ($config['enable_cashier_printing'] ?? true) ? 'true' : 'false',
            'enable_cashier_today_revenue' => ($config['enable_cashier_today_revenue'] ?? false) ? 'true' : 'false',
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
