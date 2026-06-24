<?php
/**
 * API Endpoint for Business Metrics
 * Returns real-time dashboard data
 */
require_once '../includes/auth.php';
require_once '../includes/JsonDB.php';
require_once '../includes/report-dates.php';

function sendJson($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!isAuthenticated()) {
    sendJson(['message' => 'Unauthorized'], 401);
}

requireApiAuth(['admin'], ['overview:view', 'orders:view', 'stock:view', 'reports:financial_summary']);

try {
    $range = resolveReportDateRange('today');
    $todayStart = $range['start']->format('Y-m-d H:i:s');
    $todayEnd   = $range['end']->format('Y-m-d H:i:s');

    // Parallel fetch simulation (sequential for PHP)
    $orders = db('orders')->findMany(['where' => [
        'createdAt' => ['gte' => $todayStart, 'lte' => $todayEnd],
        'isDeleted' => false
    ]]);

    // Fetch active orders for dashboard display (Today's only)
    $activeOrders = db('orders')->findMany([
        'where' => [
            'status' => ['nin' => ['completed', 'served', 'cancelled']],
            'createdAt' => ['gte' => $todayStart, 'lte' => $todayEnd],
            'isDeleted' => false
        ],
        'orderBy' => ['createdAt' => 'desc']
    ]);
    
    // Fetch items for active orders
    if (!empty($activeOrders)) {
        $activeOrderIds = array_map(fn($o) => $o['id'], $activeOrders);
        $orderItems = db('orderItems')->findMany([
            'where' => ['orderId' => ['in' => $activeOrderIds]]
        ]);
        $itemsMap = [];
        foreach ($orderItems as $it) $itemsMap[$it['orderId']][] = $it;
        foreach ($activeOrders as &$ao) $ao['items'] = $itemsMap[$ao['id']] ?? [];
    }

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
            'activeOrders' => count($activeOrders),
            'recentActive' => $activeOrders
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
