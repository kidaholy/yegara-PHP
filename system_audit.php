<?php
/**
 * System Audit & Verification
 */
require_once 'includes/db_config.php';

echo "--- Abe Hotel V2 System Audit ---\n";

// 1. MySQL Connectivity
try {
    $db = getDB();
    echo "[PASS] MySQL Connection Successful.\n";
    
    // Check tables
    $tables = ['users', 'rooms', 'reception_requests', 'orders', 'stocks'];
    foreach ($tables as $t) {
        $db->query("SELECT 1 FROM $t LIMIT 1");
        echo "[PASS] Table '$t' exists.\n";
    }
} catch (Exception $e) {
    echo "[FAIL] MySQL Error: " . $e->getMessage() . "\n";
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
