<?php
require_once 'includes/config.php';
require_once 'includes/JsonDB.php';

$orders = db('orders')->findMany(['take' => 10, 'orderBy' => ['createdAt' => 'desc']]);
echo "Orders Sample (Latest 10):\n";
foreach ($orders as $o) {
    echo "ID: " . $o['id'] . " | createdAt: " . ($o['createdAt'] ?? 'N/A') . " | totalAmount: " . ($o['totalAmount'] ?? 0) . " | status: " . ($o['status'] ?? 'N/A') . "\n";
}

$start = new DateTime('2026-06-23 06:00:00');
$end = new DateTime('2026-06-24 06:00:00');

$countFiltered = 0;
$totalFiltered = 0;
foreach ($orders as $o) {
    if (isset($o['createdAt'])) {
        $dt = new DateTime($o['createdAt']);
        if ($dt >= $start && $dt < $end) {
            $countFiltered++;
            $totalFiltered += floatval($o['totalAmount'] ?? 0);
        }
    }
}

echo "\nFiltering Test (2026-06-23 06:00 to 2026-06-24 06:00) on sample:\n";
echo "Count: $countFiltered\n";
echo "Total: $totalFiltered\n";

// Count all for yesterday business day
$allOrders = db('orders')->findMany();
$yesterdayCount = 0;
$yesterdayTotal = 0;
foreach ($allOrders as $o) {
    if (isset($o['createdAt']) && !($o['isDeleted'] ?? false) && ($o['status'] ?? '') !== 'cancelled') {
        $dt = new DateTime($o['createdAt']);
        if ($dt >= $start && $dt < $end) {
            $yesterdayCount++;
            $yesterdayTotal += floatval($o['totalAmount'] ?? 0);
        }
    }
}
echo "\nFull Yesterday Business Day (2026-06-23 06:00 - 2026-06-24 06:00):\n";
echo "Count: $yesterdayCount\n";
echo "Total: $yesterdayTotal\n";

// Calendar Day June 23rd
$calStart = new DateTime('2026-06-23 00:00:00');
$calEnd = new DateTime('2026-06-23 23:59:59');
$calCount = 0;
$calTotal = 0;
foreach ($allOrders as $o) {
    if (isset($o['createdAt']) && !($o['isDeleted'] ?? false) && ($o['status'] ?? '') !== 'cancelled') {
        $dt = new DateTime($o['createdAt']);
        if ($dt >= $calStart && $dt <= $calEnd) {
            $calCount++;
            $calTotal += floatval($o['totalAmount'] ?? 0);
        }
    }
}
echo "\nFull Calendar June 23rd (00:00 - 23:59):\n";
echo "Count: $calCount\n";
echo "Total: $calTotal\n";
