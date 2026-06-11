<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';
require_once '../../includes/report-dates.php';
require_once '../../includes/stock-logic.php';

requireApiAuth(['admin', 'reception', 'store', 'cashier']);

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

    $stockMap = [];
    foreach ($stocks as $stock) {
        if ($stock['isDeleted'] ?? false) continue;
        $stockMap[$stock['id']] = $stock;
    }

    // Period consumption from order line items
    $periodConsumption = [];
    $totalConsumptionValue = 0;
    $totalItemsConsumed = 0;

    foreach ($orders as $order) {
        if ($order['isDeleted'] ?? false) continue;
        if (($order['status'] ?? '') === 'cancelled') continue;
        if (!isWithinReportRange($order['createdAt'] ?? null, $start, $end)) continue;

        $lineItems = [];
        foreach ($order['items'] ?? [] as $item) {
            if ($item['isDeleted'] ?? false) continue;
            $lineItems[] = [
                'menuItemId' => $item['menuItemId'] ?? null,
                'quantity' => $item['quantity'] ?? 0,
            ];
        }

        $consumption = calculateStockConsumption($lineItems);
        foreach ($consumption as $stockId => $qty) {
            if (!isset($stockMap[$stockId])) continue;
            $periodConsumption[$stockId] = ($periodConsumption[$stockId] ?? 0) + $qty;
            $unitCost = (float)($stockMap[$stockId]['unitCost'] ?? $stockMap[$stockId]['averagePurchasePrice'] ?? 0);
            $totalConsumptionValue += $qty * $unitCost;
            $totalItemsConsumed += $qty;
        }
    }

    // Store movements in period
    $storeInByStock = [];
    $storeOutByStock = [];
    foreach ($storeLogs as $log) {
        $logDate = $log['date'] ?? $log['createdAt'] ?? null;
        if (!isWithinReportRange($logDate, $start, $end)) continue;
        
        $stockId = $log['stockId'] ?? null;
        if (!$stockId) continue;

        $type = $log['type'] ?? '';
        $qty = (float)($log['quantity'] ?? 0);

        if ($type === 'RESTOCK' || $type === 'PURCHASE') {
            $storeInByStock[$stockId] = ($storeInByStock[$stockId] ?? 0) + $qty;
        } else if ($type === 'TRANSFER_OUT' || $type === 'TRANSFER') {
            $storeOutByStock[$stockId] = ($storeOutByStock[$stockId] ?? 0) + $qty;
        }
    }

    $analysis = [];
    foreach ($stockMap as $stockId => $stock) {
        $closingStock = (float)($stock['quantity'] ?? 0);
        $consumed = (float)($periodConsumption[$stockId] ?? 0);
        $openingStock = $closingStock + $consumed;
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
            'openingStock' => $openingStock,
            'closingStock' => $closingStock,
            'consumed' => $consumed,
            'weightedAvgCost' => $weightedAvgCost,
            'currentUnitCost' => $currentUnitCost,
            'storeQuantity' => $storeQuantity,
            'storeClosingValue' => $storeQuantity * $weightedAvgCost,
            'storeIn' => $storeIn,
            'storeOut' => $storeOut,
            'transferred' => $storeOut,
            'isLowStock' => $minLimit > 0 ? $closingStock <= $minLimit : $closingStock < 5,
            'quantity' => $consumed,
            'totalValue' => $consumed * $weightedAvgCost,
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
