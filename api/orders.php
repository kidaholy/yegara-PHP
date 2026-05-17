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

    // If it's a POST request, handle status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $orderId = $input['id'] ?? null;
        $newStatus = $input['status'] ?? null;

        if (!$orderId || !$newStatus) {
            sendJson(['message' => 'Missing ID or status'], 400);
        }

        $updated = db('orders')->update([
            'where' => ['id' => $orderId],
            'data' => ['status' => $newStatus]
        ]);

        sendJson($updated);
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
        $order['items'] = $itemsMap[$order['id']] ?? [];
    }

    sendJson($orders);

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
