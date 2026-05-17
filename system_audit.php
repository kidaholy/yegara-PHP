<?php
/**
 * System Audit & Verification
 */
require_once 'includes/JsonDB.php';

echo "--- Abe Hotel V2 System Audit ---\n";

// 1. Data Integrity Audit (JSON)
try {
    echo "[INFO] Verifying JSON Data Tables...\n";
    
    $tables = ['users', 'rooms', 'receptionRequests', 'orders', 'orderItems', 'menuCategories', 'menuItems', 'stocks'];
    foreach ($tables as $t) {
        $count = db($t)->count();
        echo "[PASS] Table '$t' active ($count records).\n";
    }
} catch (Exception $e) {
    echo "[FAIL] Data Error: " . $e->getMessage() . "\n";
}

// 2. File Permissions
$uploadsDir = 'public/uploads/docs/';
if (is_writable($uploadsDir)) {
    echo "[PASS] Uploads directory is writable.\n";
} else {
    echo "[WARNING] Uploads directory is NOT writable. Run: chmod 777 $uploadsDir\n";
}

// 3. PHP Version
echo "[INFO] Running PHP " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
    echo "[PASS] PHP 8.x detected.\n";
} else {
    echo "[WARNING] System designed for PHP 8.x. Current version is " . PHP_VERSION . "\n";
}

echo "--- Audit Complete ---\n";
