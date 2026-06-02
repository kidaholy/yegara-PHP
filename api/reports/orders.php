<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';

// Authenticate
requireAuth();

header('Content-Type: application/json');

$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
$includeDeleted = ($_GET['includeDeleted'] ?? 'false') === 'true';

try {
    $orders = db('orders')->get();
    $users = db('users')->get();
    
    // Create user map
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = [
            'id' => $u['id'],
            'name' => $u['name'],
            'role' => $u['role']
        ];
    }

    $filtered = [];
    foreach ($orders as $o) {
        // Soft delete filter
        if (!$includeDeleted && ($o['isDeleted'] ?? false)) continue;
        
        // Date filter
        if ($startDate || $endDate) {
            $created = strtotime($o['createdAt']);
            if ($startDate && $created < strtotime($startDate)) continue;
            // End of day for endDate if only Y-m-d provided
            $endMax = (strlen($endDate) === 10) ? strtotime($endDate . ' 23:59:59') : strtotime($endDate);
            if ($endDate && $created > $endMax) continue;
        }

        // Attach user info if missing or needed for reports
        if (isset($o['createdById']) && isset($userMap[$o['createdById']])) {
            $o['createdBy'] = $userMap[$o['createdById']];
        }

        $filtered[] = $o;
        if (count($filtered) >= $limit) break;
    }

    // Sort by createdAt desc
    usort($filtered, function($a, $b) {
        return strtotime($b['createdAt']) <=> strtotime($a['createdAt']);
    });

    echo json_encode($filtered);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
