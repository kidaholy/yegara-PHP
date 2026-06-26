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

$period = $_GET['period'] ?? null;
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1000;
$includeDeleted = ($_GET['includeDeleted'] ?? 'false') === 'true';

try {
    if ($period || ($startDate && $endDate)) {
        $range = resolveReportDateRange($period ?? 'week', $startDate, $endDate);
        $start = $range['start'];
        $end = $range['end'];
    } elseif ($startDate) {
        $start = new DateTime($startDate);
        $end = new DateTime();
        $end->setTime(23, 59, 59);
    } else {
        $range = resolveReportDateRange('month');
        $start = $range['start'];
        $end = $range['end'];
    }

    $orders = db('orders')->findMany();
    $users = db('users')->findMany();
    $allItems = db('orderItems')->findMany();
    
    $userMap = [];
    foreach ($users as $u) {
        $userMap[$u['id']] = [
            'id' => $u['id'],
            'name' => $u['name'],
            'role' => $u['role']
        ];
    }

    $itemsByOrder = [];
    foreach ($allItems as $it) {
        if ($it['isDeleted'] ?? false) continue;
        $itemsByOrder[$it['orderId']][] = $it;
    }

    $filtered = [];
    foreach ($orders as $o) {
        if (!$includeDeleted && ($o['isDeleted'] ?? false)) continue;
        if (!isWithinReportRange($o['createdAt'] ?? null, $start, $end)) continue;

        if (isset($o['createdById']) && isset($userMap[$o['createdById']])) {
            $o['createdBy'] = $userMap[$o['createdById']];
        } elseif (!isset($o['createdBy']) && isset($o['createdById'])) {
            $o['createdBy'] = ['name' => 'Unknown Cashier'];
        }

        // Use pre-indexed items
        $o['items'] = $itemsByOrder[$o['id']] ?? [];

        $filtered[] = $o;
    }

    usort($filtered, fn($a, $b) => strtotime($b['createdAt'] ?? 0) <=> strtotime($a['createdAt'] ?? 0));

    echo json_encode(array_slice($filtered, 0, $limit));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
