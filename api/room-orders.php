<?php
/**
 * API for Room Service Order Approval Queue
 */
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/JsonDB.php';


function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

requireAuth(['admin', 'cashier']);

try {
    $db = db('orders');
    
    // Fetch only unconfirmed room orders
    $orders = $db->findMany([
        'where' => [
            'status' => 'unconfirmed',
            'type' => 'room_service',
            'isDeleted' => false
        ],
        'orderBy' => ['createdAt' => 'desc']
    ]);

    // Attach items to each order
    foreach ($orders as &$o) {
        $o['items'] = db('orderItems')->findMany(['where' => ['orderId' => $o['id']]]);
    }
    unset($o);

    sendJson(['status' => 'success', 'data' => $orders, 'total' => count($orders)]);

} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
