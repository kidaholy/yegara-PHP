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
if (!in_array($role, ['chef', 'admin'], true)) {
    sendJson(['message' => 'Forbidden'], 403);
}

try {
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');
    $categoryFilter = trim($_GET['category'] ?? '');

    $orders = db('orders')->findMany([
        'where' => [
            'isDeleted' => false,
            'createdAt' => ['gte' => $todayStart, 'lte' => $todayEnd],
            'status' => ['in' => ['pending', 'preparing']],
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
        $foodItems = array_values(array_filter(
            $itemsMap[$order['id']] ?? [],
            fn($item) => strtolower($item['mainCategory'] ?? 'food') === 'food'
        ));

        if (empty($foodItems)) {
            continue;
        }

        foreach ($foodItems as $item) {
            $cat = trim($item['category'] ?? '');
            if ($cat !== '') {
                $categories[$cat] = true;
            }
        }

        if ($categoryFilter !== '') {
            $hasCategory = false;
            foreach ($foodItems as $item) {
                if (strcasecmp(trim($item['category'] ?? ''), $categoryFilter) === 0) {
                    $hasCategory = true;
                    break;
                }
            }
            if (!$hasCategory) {
                continue;
            }
            $foodItems = array_values(array_filter(
                $foodItems,
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
                'status' => strtolower($item['status'] ?? $order['status'] ?? 'pending'),
                'notes' => $item['notes'] ?? '',
            ], $foodItems),
        ];
    }

    usort($queue, fn($a, $b) => strtotime($a['createdAt'] ?? 'now') - strtotime($b['createdAt'] ?? 'now'));

    $menuItems = db('menuItems')->findMany(['where' => ['isDeleted' => false]]);
    foreach ($menuItems as $item) {
        if (strtolower($item['mainCategory'] ?? '') !== 'food') {
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
