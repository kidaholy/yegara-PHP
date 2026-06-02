<?php
/**
 * Guest API: Place Room Service Order
 */
header('Content-Type: application/json');
require_once '../includes/JsonDB.php';
require_once '../includes/stock-logic.php';

function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['message' => 'Method not allowed'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $tableNumber = $input['tableNumber'] ?? $input['roomNumber'] ?? 'Unknown';
    $orderItems = $input['items'] ?? [];
    $totalAmount = (float)($input['totalAmount'] ?? 0);

    if (empty($orderItems)) {
        sendJson(['message' => 'Order items required'], 400);
    }

    // 1. Calculate and validate stock availability (but DO NOT deduct yet)
    $consumptionMap = calculateStockConsumption($orderItems);
    try {
        validateStockAvailability($consumptionMap);
    } catch (Exception $e) {
        sendJson(['message' => $e->getMessage()], 400);
    }

    // 2. Create Order in "unconfirmed" status
    $orderNumber = 'RM-' . strtoupper(substr(uniqid(), -6));
    $order = db('orders')->create(['data' => [
        'orderNumber' => $orderNumber,
        'tableNumber' => $tableNumber,
        'totalAmount' => $totalAmount,
        'status' => 'unconfirmed',
        'isDeleted' => false,
        'createdAt' => date('Y-m-d H:i:s'),
        'updatedAt' => date('Y-m-d H:i:s'),
        'type' => 'room_service',
        'notes' => $input['notes'] ?? ''
    ]]);

    // 3. Create Order Items
    foreach ($orderItems as $it) {
        db('orderItems')->create(['data' => [
            'orderId' => $order['id'],
            'menuItemId' => $it['menuItemId'],
            'name' => $it['name'],
            'quantity' => $it['quantity'],
            'price' => $it['price'],
            'notes' => $it['notes'] ?? '',
            'isDeleted' => false
        ]]);
    }

    sendJson([
        'status' => 'success', 
        'message' => 'Order submitted for approval',
        'orderNumber' => $orderNumber
    ], 201);

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
