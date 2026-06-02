<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';

// Authenticate and check for admin/reception role
requireAuth(['admin', 'reception']);

header('Content-Type: application/json');

$period = $_GET['period'] ?? 'week';

// Resolve date range from period or explicit dates
switch ($period) {
    case 'today':  $startDate = date('Y-m-d'); $endDate = $startDate; break;
    case 'week':   $startDate = date('Y-m-d', strtotime('-7 days')); $endDate = date('Y-m-d'); break;
    case 'month':  $startDate = date('Y-m-01'); $endDate = date('Y-m-t'); break;
    case 'year':   $startDate = date('Y-01-01'); $endDate = date('Y-12-31'); break;
    default:
        $startDate = $_GET['startDate'] ?? date('Y-m-d');
        $endDate   = $_GET['endDate']   ?? date('Y-m-d');
}

try {
    $requests = db('receptionRequests')->findMany(['where' => ['isDeleted' => false]]);
    $rooms    = db('rooms')->findMany(['where' => ['isDeleted' => false]]);
    
    // Create room lookup map
    $roomMap = [];
    foreach ($rooms as $room) {
        $roomMap[$room['id']] = $room;
    }

    $revenueByDay = [];
    $totalRevenue = 0;
    $totalBookings = 0;
    $occupancyData = []; // To store room-specific breakdown if we can link them

    foreach ($requests as $req) {
        if ($req['isDeleted']) continue;
        
        $updatedAt = $req['updatedAt'] ?? $req['createdAt'];
        $date = date('Y-m-d', strtotime($updatedAt));
        
        if ($date >= $startDate && $date <= $endDate) {
            // Assume the request is confirmed or pending check-in implies revenue
            $price = floatval($req['roomPrice'] ?? 0);
            
            $totalRevenue += $price;
            $totalBookings++;
            
            if (!isset($revenueByDay[$date])) {
                $revenueByDay[$date] = 0;
            }
            $revenueByDay[$date] += $price;

            // Note: Since receptionRequests.json lacks roomId, 
            // we might need a separate collection for room status history
        }
    }

    // Sort days
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
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
