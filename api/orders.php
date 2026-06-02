<?php
/**
 * API Endpoint for Orders - Handles specific admin actions
 */
require_once '../includes/auth.php';
require_once '../includes/stock-logic.php';

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
    // ── GET: list orders (optionally filtered) ──────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $statusFilter = $_GET['status'] ?? null;
        $where = ['isDeleted' => false];
        if ($statusFilter) $where['status'] = $statusFilter;

        $orders = db('orders')->findMany(['where' => $where]);

        // Attach items to each order
        foreach ($orders as &$o) {
            $o['items'] = db('orderItems')->findMany(['where' => ['orderId' => $o['id']]]);
        }
        unset($o);

        sendJson(['status' => 'success', 'data' => $orders]);
    }

    // ── PUT: update order status (approve/deny from Room Orders tab) ────────
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = $_GET['id'] ?? null;
        if (!$id) sendJson(['message' => 'Order ID required'], 400);

        $input = json_decode(file_get_contents('php://input'), true);
        $newStatus = $input['status'] ?? null;
        if (!$newStatus) sendJson(['message' => 'Status required'], 400);

        $order = db('orders')->findUnique(['where' => ['id' => $id]]);
        if (!$order) sendJson(['message' => 'Order not found'], 404);
        $oldStatus = $order['status'] ?? 'unknown';

        $updateData = [
            'status' => $newStatus,
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        // --- STOCK LOGIC ---
        $items = db('orderItems')->findMany(['where' => ['orderId' => $id]]);
        $consumptionMap = calculateStockConsumption($items);

        // Deduct stock on approval (unconfirmed -> pending)
        if ($newStatus === 'pending' && $oldStatus === 'unconfirmed') {
            try {
                validateStockAvailability($consumptionMap);
                applyStockAdjustment($consumptionMap, -1);
            } catch (Exception $e) {
                sendJson(['message' => $e->getMessage()], 400);
            }
        }

        // Restore stock on cancellation (if it was already deducted)
        if ($newStatus === 'cancelled' && in_array($oldStatus, ['pending', 'served', 'completed'])) {
            applyStockAdjustment($consumptionMap, 1);
            $updateData['isDeleted'] = true;
        }

        db('orders')->update([
            'where' => ['id' => $id],
            'data' => $updateData
        ]);

        sendJson(['status' => 'success']);
    }

    // ── POST: admin bulk actions ────────────────────────────────────────────
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

            $order = db('orders')->findUnique(['where' => ['id' => $id]]);
            if ($order && !in_array($order['status'], ['cancelled', 'unconfirmed'])) {
                $items = db('orderItems')->findMany(['where' => ['orderId' => $id]]);
                $consumptionMap = calculateStockConsumption($items);
                applyStockAdjustment($consumptionMap, 1); // Restore stock
            }

            $updated = db('orders')->update([
                'where' => ['id' => $id],
                'data' => [
                    'isDeleted' => true,
                    'status' => 'cancelled',
                    'updatedAt' => date('Y-m-d H:i:s')
                ]
            ]);
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

        // --- CREATE ORDER (No action provided) ---
        if (!$action) {
            $tableNumber = $input['tableNumber'] ?? 'Buy&Go';
            $paymentMethod = $input['paymentMethod'] ?? 'cash';
            $totalAmount = (float)($input['totalAmount'] ?? 0);
            $orderItems = $input['items'] ?? [];

            if (empty($orderItems)) sendJson(['message' => 'Order items required'], 400);

            // Fetch and validate stock
            $consumptionMap = calculateStockConsumption($orderItems);
            try {
                validateStockAvailability($consumptionMap);
            } catch (Exception $e) {
                sendJson(['message' => $e->getMessage()], 400);
            }

            // Create Order
            $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -6));
            $order = db('orders')->create(['data' => [
                'orderNumber' => $orderNumber,
                'tableNumber' => $tableNumber,
                'paymentMethod' => $paymentMethod,
                'totalAmount' => $totalAmount,
                'status' => 'pending', 
                'isDeleted' => false,
                'createdBy' => ['id' => $user['id'] ?? 'pos', 'name' => $user['name'] ?? 'Cashier'],
                'createdAt' => date('Y-m-d H:i:s'),
                'updatedAt' => date('Y-m-d H:i:s'),
                'type' => 'cashier'
            ]]);

            // Create Order Items
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

            // Deduct Stock
            applyStockAdjustment($consumptionMap, -1);

            sendJson(['success' => true, 'orderNumber' => $orderNumber, 'id' => $order['id']], 201);
        }
    }

    // Default GET: Handled by original script logic or existing cashier.php pattern
    sendJson(['message' => 'Method not allowed'], 405);

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
