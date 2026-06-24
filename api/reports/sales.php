<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';
require_once '../../includes/report-dates.php';

requireApiAuth(['admin', 'reception', 'store', 'cashier'], [
    'reports:view', 
    'reports:financial_summary', 
    'reports:order_history', 
    'reports:inventory_investment', 
    'reports:store_investment', 
    'reports:menu_item_sales', 
    'reports:cashier_insights'
]);

try {
    $period = $_GET['period'] ?? 'week';
    $range = resolveReportDateRange($period, $_GET['startDate'] ?? null, $_GET['endDate'] ?? null);
    $start = $range['start'];
    $end = $range['end'];

    $allOrders = db('orders')->findMany();
    $allDailyExpenses = db('dailyExpenses')->findMany();
    $allOperationalExpenses = db('operationalExpenses')->findMany();
    $allRestocks = db('stockRestockEntries')->findMany();
    $allReception = db('receptionRequests')->findMany(['where' => ['isDeleted' => false]]);
    
    $filteredOrders = [];
    $totalRevenue = 0;
    $totalOrderRevenue = 0;
    $totalReceptionRevenue = 0;
    
    $categoryStats = ['Food' => 0, 'Drink' => 0, 'Other' => 0];
    $cashierStats = [];
    $orderStats = ['total' => 0, 'completed' => 0, 'pending' => 0, 'cancelled' => 0, 'served' => 0];
    $paymentStats = [];

    foreach ($allOrders as $order) {
        if ($order['isDeleted'] ?? false) continue;
        if (!isWithinReportRange($order['createdAt'] ?? null, $start, $end)) continue;

        $filteredOrders[] = $order;
        $orderStats['total']++;
        $status = $order['status'] ?? 'pending';
        if (isset($orderStats[$status])) $orderStats[$status]++;

        if ($status !== 'cancelled') {
            $orderTotal = floatval($order['totalAmount'] ?? 0);
            $totalOrderRevenue += $orderTotal;
            
            // Payment method grouping (apply to full amount since it's cash/bank collected)
            $pm = $order['paymentMethod'] ?? 'cash';
            if (!isset($paymentStats[$pm])) $paymentStats[$pm] = 0;
            $paymentStats[$pm] += floatval($order['totalAmount'] ?? 0);

            // Cashier/Waiter grouping
            $cashierName = $order['createdBy']['name'] ?? 'Unknown';
            if (!isset($cashierStats[$cashierName])) $cashierStats[$cashierName] = 0;
            $cashierStats[$cashierName] += floatval($order['totalAmount'] ?? 0);

            // Category logic for the REMAINING POS portion
            if ($orderTotal > 0) {
                $cat = $order['category'] ?? 'Other';
                $catMap = [
                    'Food' => 'Food', 'FOOD' => 'Food', 'Buffet' => 'Food', 'Kitchen' => 'Food',
                    'Drink' => 'Drink', 'DRINK' => 'Drink', 'Bar' => 'Drink', 'Soft Drinks' => 'Drink', 'Beer' => 'Drink', 'Wine' => 'Drink'
                ];
                $mappedCat = $catMap[$cat] ?? 'Other';
                $categoryStats[$mappedCat] += $orderTotal;
            }
        }
    }

    $revenueStatuses = ['CHECKIN_APPROVED', 'CHECKED_OUT', 'CHECKOUT_APPROVED', 'ACTIVE', 'staying'];
    foreach ($allReception as $req) {
        if (!in_array($req['status'] ?? '', $revenueStatuses, true)) continue;
        
        $price = floatval($req['roomPrice'] ?? 0);
        $dateStr = !empty($req['checkIn']) ? $req['checkIn'] : (!empty($req['approvedAt']) ? $req['approvedAt'] : ($req['updatedAt'] ?? null));
        
        if (!isWithinReportRange($dateStr, $start, $end)) continue;
        
        $totalReceptionRevenue += $price;
        $pm = 'reception';
        if (!isset($paymentStats[$pm])) $paymentStats[$pm] = 0;
        $paymentStats[$pm] += $price;
    }

    $totalRevenue = $totalOrderRevenue + $totalReceptionRevenue;

    $totalOtherExpenses = 0;
    foreach ($allDailyExpenses as $expense) {
        $dateStr = $expense['date'] ?? $expense['createdAt'] ?? null;
        if (!isWithinReportRange($dateStr, $start, $end)) continue;
        $totalOtherExpenses += floatval($expense['amount'] ?? 0);
    }

    $totalOperationalExpenses = 0;
    foreach ($allOperationalExpenses as $expense) {
        $dateStr = $expense['date'] ?? $expense['createdAt'] ?? null;
        if (!isWithinReportRange($dateStr, $start, $end)) continue;
        $totalOperationalExpenses += floatval($expense['amount'] ?? 0);
    }

    $periodStockInvestment = 0;
    foreach ($allRestocks as $entry) {
        if (!isWithinReportRange($entry['createdAt'] ?? null, $start, $end)) continue;
        $periodStockInvestment += floatval($entry['totalCost'] ?? 0);
    }

    $totalExpenses = $totalOtherExpenses + $totalOperationalExpenses + $periodStockInvestment;
    $netProfit = $totalRevenue - $totalExpenses;

    usort($filteredOrders, fn($a, $b) => strtotime($b['createdAt'] ?? 0) <=> strtotime($a['createdAt'] ?? 0));

    echo json_encode([
        'status' => 'success',
        'data' => [
            'period' => $period,
            'startDate' => $start->format(DateTime::ATOM),
            'endDate' => $end->format(DateTime::ATOM),
            'summary' => [
                'totalRevenue' => $totalRevenue,
                'orderRevenue' => $totalOrderRevenue,
                'receptionRevenue' => $totalReceptionRevenue,
                'totalOrders' => $orderStats['total'],
                'completedOrders' => $orderStats['completed'] + $orderStats['served'],
                'pendingOrders' => $orderStats['pending'],
                'cancelledOrders' => $orderStats['cancelled'],
                'paymentStats' => $paymentStats,
                'categoryStats' => $categoryStats,
                'cashierStats' => $cashierStats,
                'totalOtherExpenses' => $totalOtherExpenses,
                'totalOperationalExpenses' => $totalOperationalExpenses,
                'periodStockInvestment' => $periodStockInvestment,
                'totalExpenses' => $totalExpenses,
                'periodNetProfit' => $netProfit
            ],
            'orders' => array_slice($filteredOrders, 0, 100)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
