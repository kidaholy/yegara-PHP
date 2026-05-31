<?php
/**
 * API Endpoint for Business Metrics
 * Returns real-time dashboard data
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

try {
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd   = date('Y-m-d 23:59:59');

    // Parallel fetch simulation (sequential for PHP)
    $orders = db('orders')->findMany(['where' => [
        'createdAt' => ['gte' => $todayStart, 'lte' => $todayEnd],
        'isDeleted' => false
    ]]);

    $stocks = db('stocks')->findMany([]);
    
    // 1. Revenue Calculations
    $todayRevenue = 0;
    $completedCount = 0;
    foreach ($orders as $o) {
        $status = strtolower($o['status'] ?? '');
        if ($status !== 'cancelled') {
            $todayRevenue += (float)($o['totalAmount'] ?? 0);
        }
        if (in_array($status, ['completed', 'served'])) {
            $completedCount++;
        }
    }

    $todayOrdersCount = count($orders);
    $avgOrderValue = $todayOrdersCount > 0 ? $todayRevenue / $todayOrdersCount : 0;

    // 2. Stock Alerts
    // We only alert if quantity <= minLimit and trackQuantity is true (implied by presence of minLimit)
    $lowStockAlerts = [];
    foreach ($stocks as $s) {
        $current = (float)($s['quantity'] ?? 0);
        $min = (float)($s['minLimit'] ?? 0);
        if ($min > 0 && $current <= $min) {
            $lowStockAlerts[] = [
                'name' => $s['name'] ?? 'Unknown Item',
                'current' => $current,
                'minimum' => $min,
                'unit' => $s['unit'] ?? 'pcs',
                'urgency' => ($current == 0) ? 'critical' : 'warning'
            ];
        }
    }

    // Response structure matching Next.js spec
    $response = [
        'realTimeMetrics' => [
            'todayRevenue' => $todayRevenue,
            'todayOrders' => $todayOrdersCount,
            'averageOrderValue' => $avgOrderValue,
            'activeOrders' => 0 // Mocked for now
        ],
        'operationalMetrics' => [
            'customerSatisfaction' => [
                'completedOrders' => $completedCount
            ]
        ],
        'inventoryInsights' => [
            'lowStockAlerts' => $lowStockAlerts
        ],
        'lastUpdated' => date('Y-m-d H:i:s')
    ];

    sendJson($response);

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
