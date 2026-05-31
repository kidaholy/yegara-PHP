<?php
/**
 * API Endpoint for Orders - Handles specific admin actions
 */
require_once '../includes/auth.php';

function sendJson($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!isAuthenticated()) {
    sendJson(['message' => 'Unauthorized'], 401);
}

$user = getCurrentUser();
$isAdmin = ($user['role'] ?? '') === 'admin';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? null;

        // --- SINGLE DELETE (Soft Delete) ---
        if ($action === 'delete') {
            if (!$isAdmin) sendJson(['message' => 'Admin only'], 403);
            $id = $input['id'] ?? null;
            if (!$id) sendJson(['message' => 'ID required'], 400);

            $updated = db('orders')->update([
                'where' => ['id' => $id],
                'data' => [
                    'isDeleted' => true,
                    'status' => 'cancelled',
                    'updatedAt' => date('Y-m-d H:i:s')
                ]
            ]);

            // Restore Stock Logic (Simplified)
            $items = db('orderItems')->findMany(['where' => ['orderId' => $id]]);
            foreach ($items as $item) {
                $menuItem = db('menuItems')->findUnique(['where' => ['id' => $item['menuItemId']]]);
                if ($menuItem && !empty($menuItem['stockItemId'])) {
                    $stock = db('stocks')->findUnique(['where' => ['id' => $menuItem['stockItemId']]]);
                    if ($stock) {
                        $deduction = (float)$item['quantity'] * ($menuItem['stockConsumption'] ?? 1);
                        db('stocks')->update([
                            'where' => ['id' => $stock['id']],
                            'data' => ['quantity' => (float)$stock['quantity'] + $deduction]
                        ]);
                    }
                }
            }
            sendJson(['success' => true]);
        }

        // --- BULK SERVE ---
        if ($action === 'bulk-serve') {
            if (!$isAdmin) sendJson(['message' => 'Admin only'], 403);
            $activeOrders = db('orders')->findMany(['where' => [
                'isDeleted' => false,
                'status' => ['notIn' => ['served', 'completed', 'cancelled']]
            ]]);
            
            foreach ($activeOrders as $o) {
                db('orders')->update([
                    'where' => ['id' => $o['id']],
                    'data' => [
                        'status' => 'served',
                        'servedAt' => date('Y-m-d H:i:s'),
                        'updatedAt' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
            sendJson(['success' => true, 'count' => count($activeOrders)]);
        }

        // --- BULK DELETE (Soft) ---
        if ($action === 'bulk-delete') {
            if (!$isAdmin) sendJson(['message' => 'Admin only'], 403);
            $orders = db('orders')->findMany(['where' => ['isDeleted' => false]]);
            foreach ($orders as $o) {
                db('orders')->update([
                    'where' => ['id' => $o['id']],
                    'data' => [
                        'isDeleted' => true,
                        'status' => 'cancelled',
                        'updatedAt' => date('Y-m-d H:i:s')
                    ]
                ]);
            }
            sendJson(['success' => true, 'count' => count($orders)]);
        }

        // --- EMPTY TRASH (Permanent) ---
        if ($action === 'empty-trash') {
            if (!$isAdmin) sendJson(['message' => 'Admin only'], 403);
            
            // Get IDs of deleted orders to also clean up items
            $deleted = db('orders')->findMany(['where' => ['isDeleted' => true]]);
            $ids = array_map(fn($o) => $o['id'], $deleted);

            if (!empty($ids)) {
                // Permanent remove the orders
                db('orders')->deleteMany(['where' => ['id' => ['in' => $ids]]]);
                // Permanent remove the items associated with these orders
                db('orderItems')->deleteMany(['where' => ['orderId' => ['in' => $ids]]]);
            }

            sendJson(['success' => true, 'count' => count($ids)]);
        }
    }

    // Default GET: Handled by original script logic or existing cashier.php pattern
    sendJson(['message' => 'Method not allowed'], 405);

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
