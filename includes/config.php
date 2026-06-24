<?php
/**
 * Configuration file for the PHP Management System
 */

// Define directory paths
define('BASE_DIR', dirname(__DIR__));
define('DATA_DIR', BASE_DIR . '/data');
define('INCLUDES_DIR', BASE_DIR . '/includes');
define('PAGES_DIR', BASE_DIR . '/pages');
define('API_DIR', BASE_DIR . '/api');

// Timezone settings
date_default_timezone_set('Africa/Addis_Ababa'); // Align with user's local time (+3:00)

// Authentication settings
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 days in seconds

// Production Settings (Disable display errors to keep UI clean)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
define('JWT_SECRET', 'your-secret-key-change-this-in-production'); // Kept for consistency if needed

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

/**
 * Basic environment variable loader
 */
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

// Load existing .env if present
if (file_exists(dirname(BASE_DIR) . '/.env.local')) {
    loadEnv(dirname(BASE_DIR) . '/.env.local');
}
