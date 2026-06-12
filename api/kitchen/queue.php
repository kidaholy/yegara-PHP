<?php
/**
 * Kitchen queue API — food orders for chef/admin display
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/order-utils.php';

header('Content-Type: application/json');

function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!isAuthenticated()) {
    sendJson(['message' => 'Unauthorized'], 401);
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['chef', 'bar', 'admin', 'display'], true)) {
    sendJson(['message' => 'Forbidden'], 403);
}

try {
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');
    $categoryFilter = trim($_GET['category'] ?? '');
    $mainCategory = strtolower(trim($_GET['mainCategory'] ?? 'food'));

    // Drinks can be 'served' at POS but still visible in Bar queue for fulfillment.
    // Food orders that are 'served' are considered finalized for the kitchen.
    $allowedOrderStatuses = ['pending', 'preparing'];
    if ($mainCategory === 'drinks') {
        $allowedOrderStatuses[] = 'served';
    }

    $orders = db('orders')->findMany([
        'where' => [
            'isDeleted' => false,
            'createdAt' => ['gte' => $todayStart, 'lte' => $todayEnd],
            'status' => ['in' => $allowedOrderStatuses],
        ],
    ]);

    $orders = array_values(array_filter($orders, fn($o) => !isRoomServiceOrder($o)));

    $orderIds = array_map(fn($o) => $o['id'], $orders);
    $itemsMap = [];
    if (!empty($orderIds)) {
        $orderItems = db('orderItems')->findMany([
            'where' => ['orderId' => ['in' => $orderIds], 'isDeleted' => false],
        ]);
        foreach ($orderItems as $item) {
            $itemsMap[$item['orderId']][] = $item;
        }
    }

    $categories = [];
    $queue = [];

    foreach ($orders as $order) {
        $scopedItems = array_values(array_filter(
            $itemsMap[$order['id']] ?? [],
            fn($item) => strtolower($item['mainCategory'] ?? 'food') === $mainCategory && strtolower($item['status'] ?? '') !== 'completed'
        ));

        if (empty($scopedItems)) {
            continue;
        }

        foreach ($scopedItems as $item) {
            $cat = trim($item['category'] ?? '');
            if ($cat !== '') {
                $categories[$cat] = true;
            }
        }

        if ($categoryFilter !== '') {
            $hasCategory = false;
            foreach ($scopedItems as $item) {
                if (strcasecmp(trim($item['category'] ?? ''), $categoryFilter) === 0) {
                    $hasCategory = true;
                    break;
                }
            }
            if (!$hasCategory) {
                continue;
            }
            $scopedItems = array_values(array_filter(
                $scopedItems,
                fn($item) => strcasecmp(trim($item['category'] ?? ''), $categoryFilter) === 0
            ));
        }

        $floorLabel = 'GROUND';
        if (!empty($order['floorNumber'])) {
            $floorLabel = strtoupper($order['floorNumber']);
            if (!str_starts_with($floorLabel, 'FLOOR')) {
                $floorLabel = 'FLOOR #' . $floorLabel;
            }
        }

        $tableLabel = $order['tableNumber'] ?? '—';
        if ($tableLabel !== 'Buy&Go' && !str_starts_with(strtoupper($tableLabel), 'T#')) {
            $tableLabel = 'T#' . $tableLabel;
        }

        $queue[] = [
            'id' => $order['id'],
            'orderNumber' => $order['orderNumber'] ?? '',
            'menuTierName' => $order['menuTierName'] ?? 'Standard',
            'status' => strtolower($order['status'] ?? 'pending'),
            'tableNumber' => $order['tableNumber'] ?? '',
            'tableLabel' => $tableLabel,
            'floorNumber' => $order['floorNumber'] ?? '',
            'floorLabel' => $floorLabel,
            'createdAt' => $order['createdAt'] ?? '',
            'items' => array_map(fn($item) => [
                'id' => $item['id'] ?? '',
                'menuId' => $item['menuId'] ?? '',
                'name' => $item['name'] ?? '',
                'quantity' => (int)($item['quantity'] ?? 1),
                'category' => $item['category'] ?? '',
                'status' => strtolower($item['status'] ?? ($mainCategory === 'drinks' ? 'ready' : 'pending')),
                'notes' => $item['notes'] ?? '',
            ], $scopedItems),
        ];
    }

    usort($queue, fn($a, $b) => strtotime($b['createdAt'] ?? 'now') - strtotime($a['createdAt'] ?? 'now'));

    $menuItems = db('menuItems')->findMany(['where' => ['isDeleted' => false]]);
    foreach ($menuItems as $item) {
        if (strtolower($item['mainCategory'] ?? 'food') !== $mainCategory) {
            continue;
        }
        $cat = trim($item['category'] ?? '');
        if ($cat !== '') {
            $categories[$cat] = true;
        }
    }

    $categoryList = array_keys($categories);
    usort($categoryList, 'strcasecmp');

    sendJson([
        'status' => 'success',
        'data' => [
            'queue' => $queue,
            'queueCount' => count($queue),
            'categories' => $categoryList,
        ],
    ]);
} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
