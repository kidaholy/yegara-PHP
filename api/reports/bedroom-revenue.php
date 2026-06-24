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

    $requests = db('receptionRequests')->findMany(['where' => ['isDeleted' => false]]);

    $revenueStatuses = ['CHECKIN_APPROVED', 'CHECKED_OUT', 'CHECKOUT_APPROVED', 'ACTIVE', 'staying'];
    $revenueByDay = [];
    $totalRevenue = 0;
    $totalBookings = 0;

    foreach ($requests as $req) {
        if (!in_array($req['status'] ?? '', $revenueStatuses, true)) continue;

        $price = floatval($req['roomPrice'] ?? 0);
        if ($price <= 0) continue;

        $dateStr = !empty($req['checkIn']) ? $req['checkIn'] : (!empty($req['approvedAt']) ? $req['approvedAt'] : ($req['updatedAt'] ?? null));
        
        if (!isWithinReportRange($dateStr, $start, $end)) continue;

        $totalRevenue += $price;
        $totalBookings++;
        $date = getBusinessDateForTimestamp($dateStr);
        if ($date) {
            $revenueByDay[$date] = ($revenueByDay[$date] ?? 0) + $price;
        }
    }

    ksort($revenueByDay);
    $dailyChart = [];
    foreach ($revenueByDay as $date => $amount) {
        $dailyChart[] = ['date' => $date, 'revenue' => $amount];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'totalRevenue' => $totalRevenue,
            'totalBookings' => $totalBookings,
            'averageRevenuePerRoom' => $totalBookings > 0 ? $totalRevenue / $totalBookings : 0,
            'dailyRevenue' => $dailyChart,
            'period' => [
                'start' => $start->format(DateTime::ATOM),
                'end' => $end->format(DateTime::ATOM),
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
