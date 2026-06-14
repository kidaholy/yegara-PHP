<?php
require_once 'includes/config.php';
require_once 'includes/JsonDB.php'; // This bridges to SqliteDB

echo "### SCHEMA & ROW COUNTS ###\n";
$db = new PDO('sqlite:data/database.sqlite');
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $count = $db->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn();
    echo "Table: $table (Rows: $count)\n";
}

echo "\n### ATTEMPTING db('menu') ###\n";
try {
    $menu = db('menu')->findMany(['take' => 5]);
    echo "Found " . count($menu) . " items in db('menu')\n";
    print_r($menu);
} catch (Exception $e) {
    echo "Error calling db('menu'): " . $e->getMessage() . "\n";
}

echo "\n### ATTEMPTING db('menuItems') ###\n";
try {
    $items = db('menuItems')->findMany(['take' => 5]);
    echo "Found " . count($items) . " items in db('menuItems')\n";
    print_r($items);
} catch (Exception $e) {
    echo "Error calling db('menuItems'): " . $e->getMessage() . "\n";
}
