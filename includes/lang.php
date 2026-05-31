<?php
/**
 * Localization Engine for Abe Hotel & Spa
 */

session_start();

// Supported languages
$supportedLangs = ['en', 'am'];
$currentLang = $_SESSION['lang'] ?? 'en';

// Simple switcher logic
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
    $currentLang = $_GET['lang'];
}

$translations = [
    'en' => [
        'dashboard' => 'Dashboard',
        'reception' => 'Reception',
        'kitchen' => 'Kitchen',
        'bar_monitor' => 'Bar Monitor',
        'cashier_pos' => 'Cashier POS',
        'strategic_reports' => 'Strategic Reports',
        'staff_directory' => 'Staff Directory',
        'menu_settings' => 'Menu Settings',
        'sign_out' => 'Sign Out',
        'welcome_back' => 'Welcome Back',
        'active_stays' => 'Active Stays',
        'available_rooms' => 'Available Rooms',
        'total_revenue' => 'Total Revenue'
    ],
    'am' => [
        'dashboard' => 'ዳሽቦርድ',
        'reception' => 'መቀበያ',
        'kitchen' => 'ወጥ ቤት',
        'bar_monitor' => 'ባር ሞኒተር',
        'cashier_pos' => 'የሂሳብ መክፈያ',
        'strategic_reports' => 'ሪፖርቶች',
        'staff_directory' => 'የሰራተኞች ዝርዝር',
        'menu_settings' => 'የሜኑ ቅንብሮች',
        'sign_out' => 'ውጣ',
        'welcome_back' => 'እንኳን ደህና መጡ',
        'active_stays' => 'ንቁ ማቆያ',
        'available_rooms' => 'ክፍት ክፍሎች',
        'total_revenue' => 'ጠቅላላ ገቢ'
    ]
];

/**
 * Translation helper function
 * Supports simple keys or dot-notation files (e.g. 'admin_orders.title')
 */
function __($key) {
    global $translations, $currentLang;
    
    if (strpos($key, '.') !== false) {
        list($file, $actualKey) = explode('.', $key, 2);
        static $fileCache = [];
        if (!isset($fileCache[$file])) {
            $filePath = __DIR__ . "/../lang/{$currentLang}/{$file}.php";
            $fileCache[$file] = file_exists($filePath) ? include $filePath : [];
        }
        return $fileCache[$file][$actualKey] ?? $actualKey;
    }

    return $translations[$currentLang][$key] ?? $key;
}
