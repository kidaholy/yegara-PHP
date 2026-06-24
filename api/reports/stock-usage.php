<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';
require_once '../../includes/report-dates.php';
require_once '../../includes/stock-logic.php';

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
    $startDate = $range['startDate'];
    $endDate = $range['endDate'];

    $stocks = db('stocks')->findMany();
    $orders = db('orders')->findMany();
    $storeLogs = db('storeLogs')->findMany();
    $allOrderItems = db('orderItems')->findMany(); // Added: Fetch separate items

    $stockMap = [];
    foreach ($stocks as $stock) {
        if ($stock['isDeleted'] ?? false) continue;
        $stockMap[$stock['id']] = $stock;
    }

    // Index items by orderId for fast lookup
    $itemsByOrder = [];
    foreach ($allOrderItems as $it) {
        if ($it['isDeleted'] ?? false) continue;
        $itemsByOrder[$it['orderId']][] = $it;
    }

    // Period consumption from order line items
    $periodConsumption = [];
    $postPeriodConsumption = []; // From period end to NOW
    $totalConsumptionValue = 0;
    $totalItemsConsumed = 0;

    $now = new DateTime();

    foreach ($orders as $order) {
        if ($order['isDeleted'] ?? false) continue;
        if (($order['status'] ?? '') === 'cancelled') continue;
        
        $orderDate = new DateTime($order['createdAt'] ?? 'now');
        $lineItems = $itemsByOrder[$order['id']] ?? [];
        $consumption = calculateStockConsumption($lineItems);

        if ($orderDate >= $start && $orderDate <= $end) {
            foreach ($consumption as $stockId => $qty) {
                if (!isset($stockMap[$stockId])) continue;
                $periodConsumption[$stockId] = ($periodConsumption[$stockId] ?? 0) + $qty;
                $unitCost = (float)($stockMap[$stockId]['unitCost'] ?? $stockMap[$stockId]['averagePurchasePrice'] ?? 0);
                $totalConsumptionValue += $qty * $unitCost;
                $totalItemsConsumed += $qty;
            }
        } elseif ($orderDate > $end) {
            foreach ($consumption as $stockId => $qty) {
                $postPeriodConsumption[$stockId] = ($postPeriodConsumption[$stockId] ?? 0) + $qty;
            }
        }
    }

    // Store movements (restocks)
    $storeInByStock = [];
    $postPeriodRestock = [];
    $storeOutByStock = [];
    foreach ($storeLogs as $log) {
        $logDateStr = $log['date'] ?? $log['createdAt'] ?? null;
        if (!$logDateStr) continue;
        $logDate = new DateTime($logDateStr);
        
        $stockId = $log['stockId'] ?? null;
        if (!$stockId) continue;
        $type = $log['type'] ?? '';
        $qty = (float)($log['quantity'] ?? 0);

        if ($logDate >= $start && $logDate <= $end) {
            if ($type === 'RESTOCK' || $type === 'PURCHASE') {
                $storeInByStock[$stockId] = ($storeInByStock[$stockId] ?? 0) + $qty;
            } else if ($type === 'TRANSFER_OUT' || $type === 'TRANSFER') {
                $storeOutByStock[$stockId] = ($storeOutByStock[$stockId] ?? 0) + $qty;
            }
        } elseif ($logDate > $end) {
            // We need to know how much was TRANSFER_IN (from store to POS) to reverse current POS stock
            // Wait, calculateStockConsumption already looks at what was SOLD from POS.
            // If movement is RESTOCK (into Store), it doesn't affect POS quantity.
            // If movement is TRANSFER_OUT (from Store to POS), it REDUCES Store and INCREASES POS.
            if ($type === 'TRANSFER_OUT' || $type === 'TRANSFER') {
                $postPeriodRestock[$stockId] = ($postPeriodRestock[$stockId] ?? 0) + $qty;
            }
        }
    }

    $analysis = [];
    foreach ($stockMap as $stockId => $stock) {
        $currentPosStock = (float)($stock['quantity'] ?? 0);
        $consumedInPeriod = (float)($periodConsumption[$stockId] ?? 0);
        
        // Calculate Closing Stock at End of Period
        // currentStock = closingStock - consumedSince + restockedSince
        // so closingStock = currentStock + consumedSince - restockedSince
        $consumedSince = (float)($postPeriodConsumption[$stockId] ?? 0);
        $restockedSince = (float)($postPeriodRestock[$stockId] ?? 0);
        $closingStock = $currentPosStock + $consumedSince - $restockedSince;
        
        $openingStock = $closingStock + $consumedInPeriod;
        
        $weightedAvgCost = (float)($stock['averagePurchasePrice'] ?? $stock['unitCost'] ?? 0);
        $currentUnitCost = (float)($stock['unitCost'] ?? $weightedAvgCost);
        $storeQuantity = (float)($stock['storeQuantity'] ?? 0);
        $minLimit = (float)($stock['minLimit'] ?? 0);
        $storeIn = (float)($storeInByStock[$stockId] ?? 0);
        $storeOut = (float)($storeOutByStock[$stockId] ?? 0);

        $analysis[] = [
            'id' => $stockId,
            'name' => $stock['name'] ?? 'Unknown',
            'category' => $stock['category'] ?? 'General',
            'unit' => $stock['unit'] ?? 'pcs',
            'openingStock' => round($openingStock, 2),
            'closingStock' => round($closingStock, 2),
            'consumed' => round($consumedInPeriod, 2),
            'weightedAvgCost' => $weightedAvgCost,
            'currentUnitCost' => $currentUnitCost,
            'storeQuantity' => $storeQuantity,
            'storeClosingValue' => (float)($stock['totalInvestment'] ?? ($storeQuantity * $weightedAvgCost)),
            'storeIn' => $storeIn,
            'storeOut' => $storeOut,
            'transferred' => $storeOut,
            'isLowStock' => $minLimit > 0 ? $closingStock <= $minLimit : $closingStock < 5,
            'quantity' => $consumedInPeriod,
            'totalValue' => $consumedInPeriod * $weightedAvgCost,
        ];
    }

    usort($analysis, fn($a, $b) => $b['consumed'] <=> $a['consumed']);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'totalConsumptionValue' => $totalConsumptionValue,
            'totalItemsConsumed' => $totalItemsConsumed,
            'topConsumedItems' => array_slice($analysis, 0, 10),
            'stockAnalysis' => $analysis,
            'period' => [
                'startDate' => $startDate,
                'endDate' => $endDate
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
