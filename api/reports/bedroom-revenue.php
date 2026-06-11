<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';
require_once '../../includes/report-dates.php';

requireApiAuth(['admin', 'reception', 'store', 'cashier']);

try {
    $period = $_GET['period'] ?? 'week';
    $range = resolveReportDateRange($period, $_GET['startDate'] ?? null, $_GET['endDate'] ?? null);
    $startDate = $range['startDate'];
    $endDate = $range['endDate'];

    $requests = db('receptionRequests')->findMany(['where' => ['isDeleted' => false]]);

    $revenueStatuses = ['CHECKIN_APPROVED', 'CHECKED_OUT', 'CHECKOUT_APPROVED', 'check_in', 'checked-out'];
    $revenueByDay = [];
    $totalRevenue = 0;
    $totalBookings = 0;

    foreach ($requests as $req) {
        if ($req['isDeleted'] ?? false) continue;
        if (!in_array($req['status'] ?? '', $revenueStatuses, true)) continue;

        $price = floatval($req['roomPrice'] ?? 0);
        if ($price <= 0) continue;

        $date = null;
        if (!empty($req['checkIn'])) {
            $date = date('Y-m-d', strtotime($req['checkIn']));
        } elseif (!empty($req['approvedAt'])) {
            $date = date('Y-m-d', strtotime($req['approvedAt']));
        } else {
            $date = !empty($req['updatedAt']) ? date('Y-m-d', strtotime($req['updatedAt'])) : null;
        }

        if (!$date || $date < $startDate || $date > $endDate) continue;

        $totalRevenue += $price;
        $totalBookings++;
        $revenueByDay[$date] = ($revenueByDay[$date] ?? 0) + $price;
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
                'start' => $startDate,
                'end' => $endDate
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
