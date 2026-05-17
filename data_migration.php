<?php
/**
 * Data Migration: JSON -> MySQL
 * Run this ONCE to move all data from your /data/*.json files to the MySQL database.
 */
require_once 'includes/JsonDB.php';
require_once 'includes/DB.php';

echo "--- Abe Hotel Data Migration ---\n";

try {
    // 1. Users
    echo "[1/7] Migrating Users...\n";
    $users = json_db('users')->findMany();
    foreach ($users as $user) {
        db('users')->create(['data' => $user]);
    }

    // 2. Rooms
    echo "[2/7] Migrating Rooms...\n";
    $rooms = json_db('rooms')->findMany();
    foreach ($rooms as $room) {
        db('rooms')->create(['data' => $room]);
    }

    // 3. Stocks
    echo "[3/7] Migrating Stocks...\n";
    $stocks = json_db('stocks')->findMany();
    foreach ($stocks as $stock) {
        db('stocks')->create(['data' => $stock]);
    }

    // 4. Menu Categories
    echo "[4/7] Migrating Menu Categories...\n";
    $categories = json_db('menuCategories')->findMany();
    foreach ($categories as $cat) {
        db('menu_categories')->create(['data' => [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'icon' => $cat['icon'] ?? 'package',
            'description' => $cat['description'] ?? '',
            'createdAt' => $cat['createdAt'] ?? date('Y-m-d H:i:s')
        ]]);
    }

    // 5. Menu Items
    echo "[5/7] Migrating Menu Items...\n";
    $menuItems = json_db('menuItems')->findMany();
    foreach ($menuItems as $item) {
        db('menu_items')->create(['data' => [
            'id' => $item['id'],
            'name' => $item['name'],
            'description' => $item['description'] ?? '',
            'price' => (float)$item['price'],
            'categoryId' => $item['categoryId'],
            'stockItemId' => $item['stockItemId'] ?? null,
            'stockConsumption' => (float)($item['stockConsumption'] ?? 1),
            'mainCategory' => $item['mainCategory'] ?? 'Food',
            'createdAt' => $item['createdAt'] ?? date('Y-m-d H:i:s')
        ]]);
    }

    // 6. Orders
    echo "[6/7] Migrating Orders...\n";
    $orders = json_db('orders')->findMany();
    foreach ($orders as $order) {
        db('orders')->create(['data' => [
            'id' => $order['id'],
            'orderNumber' => $order['orderNumber'],
            'tableNumber' => $order['tableNumber'] ?? 'Buy&Go',
            'paymentMethod' => $order['paymentMethod'] ?? 'cash',
            'totalAmount' => (float)$order['totalAmount'],
            'status' => $order['status'] ?? 'pending',
            'cashierId' => $order['cashierId'] ?? 'unknown',
            'createdAt' => $order['createdAt'] ?? date('Y-m-d H:i:s'],
            'isDeleted' => $order['isDeleted'] ?? false
        ]]);
    }

    // 7. Order Items
    echo "[7/7] Migrating Order Items...\n";
    $orderItems = json_db('orderItems')->findMany();
    foreach ($orderItems as $item) {
        db('order_items')->create(['data' => [
            'id' => $item['id'],
            'orderId' => $item['orderId'],
            'menuItemId' => $item['menuItemId'],
            'name' => $item['name'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['price'],
            'notes' => $item['notes'] ?? '',
            'mainCategory' => $item['mainCategory'] ?? 'Food',
            'createdAt' => $item['createdAt'] ?? date('Y-m-d H:i:s'],
            'isDeleted' => $item['isDeleted'] ?? false
        ]]);
    }

    echo "--- Migration Completed Successfully ---\n";

} catch (Exception $e) {
    echo "[FAIL] Migration Error: " . $e->getMessage() . "\n";
}
