<?php
/**
 * Data Migration: JSON -> MySQL
 */
require_once 'includes/JsonDB.php';
require_once 'includes/DB.php';

try {
    // 1. Users
    $users = db('users')->findMany(); // Using old JsonDB helper first
    foreach ($users as $user) {
        DB::table('users')->create(['data' => $user]);
    }
    echo "Users migrated.\n";

    // 2. Rooms
    $rooms = db('rooms')->findMany();
    foreach ($rooms as $room) {
        DB::table('rooms')->create(['data' => $room]);
    }
    echo "Rooms migrated.\n";

    // 3. Stocks
    $stocks = db('stocks')->findMany();
    foreach ($stocks as $stock) {
        DB::table('stocks')->create(['data' => $stock]);
    }
    echo "Stocks migrated.\n";

    // 4. Menu Categories
    $categories = db('menuCategories')->findMany();
    foreach ($categories as $cat) {
        DB::table('menu_categories')->create(['data' => $cat]);
    }
    echo "Categories migrated.\n";

    // 5. Menu Items
    $menuItems = db('menuItems')->findMany();
    foreach ($menuItems as $item) {
        // Adjust category mapping if needed
        DB::table('menu_items')->create(['data' => [
            'id' => $item['id'],
            'name' => $item['name'],
            'description' => $item['description'] ?? '',
            'price' => $item['price'],
            'categoryId' => $item['categoryId'],
            'stockItemId' => $item['stockItemId'] ?? null,
            'stockConsumption' => $item['stockConsumption'] ?? 1,
            'mainCategory' => $item['mainCategory'] ?? 'Food'
        ]]);
    }
    echo "Menu items migrated.\n";

    // 6. Orders
    $orders = db('orders')->findMany();
    foreach ($orders as $order) {
        DB::table('orders')->create(['data' => $order]);
    }
    echo "Orders migrated.\n";

    // 7. Order Items
    $orderItems = db('orderItems')->findMany();
    foreach ($orderItems as $item) {
        DB::table('order_items')->create(['data' => $item]);
    }
    echo "Order items migrated.\n";

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
