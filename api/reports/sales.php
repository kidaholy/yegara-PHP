<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';

header('Content-Type: application/json');

// Only admins can view reports
requireAuth(['admin']);

try {
    $period = $_GET['period'] ?? 'week';
    $startDate = $_GET['startDate'] ?? null;
    $endDate = $_GET['endDate'] ?? null;

    // Calculate dates based on period if not custom
    $start = null;
    $end = new DateTime();
    
    if ($startDate && $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->setTime(23, 59, 59);
    } else {
        $start = new DateTime();
        switch ($period) {
            case 'today': $start->setTime(0, 0, 0); break;
            case 'week': $start->modify('-7 days')->setTime(0, 0, 0); break;
            case 'month': $start->modify('-30 days')->setTime(0, 0, 0); break;
            case 'year': $start->modify('-365 days')->setTime(0, 0, 0); break;
            default: $start->modify('-7 days')->setTime(0, 0, 0);
        }
    }

    $allOrders = db('orders')->findMany();
    $allDailyExpenses = db('dailyExpenses')->findMany();
    $allOperationalExpenses = db('operationalExpenses')->findMany();
    $allRestocks = db('stockRestockEntries')->findMany();
    
    $filteredOrders = [];
    $totalRevenue = 0;
    $orderStats = ['total' => 0, 'completed' => 0, 'pending' => 0, 'cancelled' => 0, 'served' => 0];
    $paymentStats = [];

    foreach ($allOrders as $order) {
        $createdAt = new DateTime($order['createdAt']);
        if ($createdAt >= $start && $createdAt <= $end) {
            $filteredOrders[] = $order;
            $orderStats['total']++;
            $status = $order['status'] ?? 'pending';
            if (isset($orderStats[$status])) $orderStats[$status]++;

            if ($status !== 'cancelled') {
                $totalRevenue += floatval($order['totalAmount'] ?? 0);
                $pm = $order['paymentMethod'] ?? 'cash';
                if (!isset($paymentStats[$pm])) $paymentStats[$pm] = 0;
                $paymentStats[$pm] += floatval($order['totalAmount'] ?? 0);
            }
        }
    }

    $totalOtherExpenses = 0;
    foreach ($allDailyExpenses as $expense) {
        $dateStr = $expense['date'] ?? $expense['createdAt'];
        $date = new DateTime($dateStr);
        if ($date >= $start && $date <= $end) $totalOtherExpenses += floatval($expense['amount'] ?? 0);
    }

    $totalOperationalExpenses = 0;
    foreach ($allOperationalExpenses as $expense) {
        $dateStr = $expense['date'] ?? $expense['createdAt'];
        $date = new DateTime($dateStr);
        if ($date >= $start && $date <= $end) $totalOperationalExpenses += floatval($expense['amount'] ?? 0);
    }

    $periodStockInvestment = 0;
    foreach ($allRestocks as $entry) {
        $date = new DateTime($entry['createdAt']);
        if ($date >= $start && $date <= $end) $periodStockInvestment += floatval($entry['totalCost'] ?? 0);
    }

    $totalExpenses = $totalOtherExpenses + $totalOperationalExpenses + $periodStockInvestment;
    $netProfit = $totalRevenue - $totalExpenses;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'period' => $period,
            'startDate' => $start->format(DateTime::ISO8601),
            'endDate' => $end->format(DateTime::ISO8601),
            'summary' => [
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $orderStats['total'],
                'completedOrders' => $orderStats['completed'] + $orderStats['served'],
                'pendingOrders' => $orderStats['pending'],
                'cancelledOrders' => $orderStats['cancelled'],
                'paymentStats' => $paymentStats,
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
