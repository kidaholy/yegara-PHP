<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';
require_once '../../includes/report-dates.php';

requireApiAuth(['admin', 'reception', 'store', 'cashier'], [
    'reports:view', 
    'reports:menu_item_sales'
]);

try {
    $period = $_GET['period'] ?? 'week';
    $range = resolveReportDateRange($period, $_GET['startDate'] ?? null, $_GET['endDate'] ?? null);
    $start = $range['start'];
    $end = $range['end'];

    $allOrders = db('orders')->findMany();
    $allOrderItems = db('orderItems')->findMany();
    $users = db('users')->findMany();

    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = $u['name'] ?? 'Unknown';
    }

    $itemsByOrderId = [];
    foreach ($allOrderItems as $it) {
        if ($it['isDeleted'] ?? false) continue;
        $oid = $it['orderId'] ?? null;
        if (!$oid) continue;
        $itemsByOrderId[$oid][] = $it;
    }

    $menuSales = [];
    $cashierStats = [];
    
    // Aggregation Logic (Moved from Frontend)
    foreach ($allOrders as $o) {
        if ($o['isDeleted'] ?? false) continue;
        if (($o['status'] ?? '') === 'cancelled') continue;
        
        $orderDateStr = $o['createdAt'] ?? null;
        if (!$orderDateStr) continue;
        if (!isWithinReportRange($orderDateStr, $start, $end)) continue;

        $cashierName = $o['createdBy']['name'] ?? ($userMap[$o['createdById'] ?? ''] ?? 'Unknown');
        
        if (!isset($cashierStats[$cashierName])) {
            $cashierStats[$cashierName] = [
                'amount' => 0, 
                'count' => 0, 
                'food' => 0, 
                'drinks' => 0, 
                'foodCount' => 0, 
                'drinksCount' => 0
            ];
        }

        $items = $itemsByOrderId[$o['id']] ?? [];
        $hasFood = false;
        $hasDrinks = false;

        foreach ($items as $item) {
            $qty = (float)($item['quantity'] ?? 0);
            $price = (float)($item['price'] ?? 0);
            $revenue = $qty * $price;
            $mainCat = $item['mainCategory'] ?? 'Food';
            $isFood = (strtolower($mainCat) === 'food');
            $isDrink = (strtolower($mainCat) === 'drinks' || strtolower($mainCat) === 'drink');

            $key = ($item['name'] ?? 'Unknown') . '|' . $cashierName;
            if (!isset($menuSales[$key])) {
                $menuSales[$key] = [
                    'name' => $item['name'] ?? 'Unknown',
                    'cashier' => $cashierName,
                    'category' => $item['category'] ?? 'General',
                    'mainCategory' => $mainCat,
                    'quantity' => 0,
                    'revenue' => 0
                ];
            }
            $menuSales[$key]['quantity'] += $qty;
            $menuSales[$key]['revenue'] += $revenue;

            if ($isFood) {
                $cashierStats[$cashierName]['food'] += $revenue;
                $hasFood = true;
            } else if ($isDrink) {
                $cashierStats[$cashierName]['drinks'] += $revenue;
                $hasDrinks = true;
            }
        }

        $orderTotal = (float)($o['totalAmount'] ?? 0);
        $cashierStats[$cashierName]['amount'] += $orderTotal;
        $cashierStats[$cashierName]['count'] += 1;
        if ($hasFood) $cashierStats[$cashierName]['foodCount'] += 1;
        if ($hasDrinks) $cashierStats[$cashierName]['drinksCount'] += 1;
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'menuItemSales' => array_values($menuSales),
            'cashierStats' => $cashierStats,
            'summary' => [
                'totalOrders' => count($allOrders), // Total for period info
                'processed' => count($allOrders)
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
