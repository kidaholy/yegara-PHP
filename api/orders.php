<?php
/**
 * API Endpoint to fetch active orders for the Kitchen/Bar
 */
require_once '../includes/auth.php';

// Simple response helper
function sendJson($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Require auth (Chef, Bar, or Admin)
if (!isAuthenticated()) {
    sendJson(['message' => 'Unauthorized'], 401);
}

$user = getCurrentUser();

try {
    $where = [
        'isDeleted' => false,
        'status' => ['not' => 'completed']
    ];

    $mainCategory = $_GET['mainCategory'] ?? null;

    // POST: Create or Update Order
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // CASE A: Status Update
        if (isset($input['id']) && isset($input['status'])) {
            $updated = db('orders')->update([
                'where' => ['id' => $input['id']],
                'data' => ['status' => $input['status']]
            ]);
            sendJson($updated);
        }

        // CASE B: New Order Creation
        if (isset($input['items']) && isset($input['totalAmount'])) {
            // Generate Order Number (Simple sequential-like with timestamp)
            $orderCount = db('orders')->count();
            $orderNumber = "ORD-" . date('ymd') . "-" . str_pad($orderCount + 1, 4, '0', STR_PAD_LEFT);
            
            $orderData = [
                'id' => bin2hex(random_bytes(16)),
                'orderNumber' => $orderNumber,
                'tableNumber' => $input['tableNumber'] ?? 'Buy&Go',
                'paymentMethod' => $input['paymentMethod'] ?? 'cash',
                'totalAmount' => (float)$input['totalAmount'],
                'status' => 'pending',
                'createdAt' => date('Y-m-d H:i:s'),
                'isDeleted' => false,
                'cashierId' => $user['id'] ?? 'unknown'
            ];

            $newOrder = db('orders')->create(['data' => $orderData]);

            // Save items
            foreach ($input['items'] as $item) {
                // Fetch mainCategory for the item to support filtering later
                $menuItem = db('menuItems')->findUnique(['where' => ['id' => $item['menuItemId']]]);
                
                db('orderItems')->create(['data' => [
                    'id' => bin2hex(random_bytes(16)),
                    'orderId' => $orderData['id'],
                    'menuItemId' => $item['menuItemId'],
                    'name' => $item['name'],
                    'quantity' => (int)$item['quantity'],
                    'price' => (float)$item['price'],
                    'notes' => $item['notes'] ?? '',
                    'mainCategory' => $menuItem['mainCategory'] ?? 'Food', // Fallback to Food
                    'isDeleted' => false,
                    'createdAt' => $orderData['createdAt']
                ]]);
            }

            sendJson($newOrder);
        }

        sendJson(['message' => 'Invalid order data'], 400);
    }

    // Default GET: return active orders
    $orders = db('orders')->findMany([
        'where' => $where,
        'orderBy' => ['createdAt' => 'asc']
    ]);

    // Simple population for items (assuming orderItems table)
    $allOrderItems = db('orderItems')->findMany(['where' => ['isDeleted' => false]]);
    $itemsMap = [];
    foreach ($allOrderItems as $item) {
        $itemsMap[$item['orderId']][] = $item;
    }

    foreach ($orders as &$order) {
        $items = $itemsMap[$order['id']] ?? [];
        if ($mainCategory) {
            $items = array_filter($items, function($i) use ($mainCategory) {
                return strtolower($i['mainCategory'] ?? '') === strtolower($mainCategory);
            });
        }
        $order['items'] = array_values($items);
    }

    // Filter out orders that have no items for the requested category
    if ($mainCategory) {
        $orders = array_values(array_filter($orders, function($o) {
            return !empty($o['items']);
        }));
    }

    sendJson($orders);

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
