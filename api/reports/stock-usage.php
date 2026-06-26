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
    $stockMap = [];
    foreach ($stocks as $stock) {
        if ($stock['isDeleted'] ?? false) continue;
        $stockMap[$stock['id']] = $stock;
    }

    // Pre-cache all menu item stock links to avoid repetitive DB hits inside calculateStockConsumption
    $menuStockLinks = [];
    $collections = getAllMenuCollections();
    foreach ($collections as $col) {
        $items = db($col)->findMany();
        foreach ($items as $m) {
            $recipe = $m['recipe'] ?? [];
            if (!empty($recipe)) {
                $menuStockLinks[$m['id']] = [];
                foreach ($recipe as $ing) {
                    $sid = $ing['stockItemId'] ?? null;
                    $q = (float)($ing['quantityRequired'] ?? $ing['quantity'] ?? 0);
                    if ($sid && $q > 0) $menuStockLinks[$m['id']][] = ['id' => $sid, 'qty' => $q];
                }
            } else {
                $sid = $m['stockItemId'] ?? null;
                $q = (float)($m['reportQuantity'] ?? $m['stockConsumption'] ?? 0);
                if ($sid && $q > 0) $menuStockLinks[$m['id']] = [['id' => $sid, 'qty' => $q]];
            }
        }
    }

    $orders = db('orders')->findMany();
    $allOrderItems = db('orderItems')->findMany(); 
    $storeLogs = db('storeLogs')->findMany();

    // Index items by orderId for fast lookup
    $itemsByOrder = [];
    foreach ($allOrderItems as $it) {
        if ($it['isDeleted'] ?? false) continue;
        $itemsByOrder[$it['orderId']][] = $it;
    }

    $periodConsumption = [];
    $postPeriodConsumption = []; 
    $totalConsumptionValue = 0;
    $totalItemsConsumed = 0;

    foreach ($orders as $order) {
        if ($order['isDeleted'] ?? false) continue;
        if (($order['status'] ?? '') === 'cancelled') continue;
        
        $orderDateStr = $order['createdAt'] ?? null;
        if (!$orderDateStr) continue;
        $orderDate = new DateTime($orderDateStr);

        // Efficiency: Only calculate consumption for relevant orders
        $isWithin = ($orderDate >= $start && $orderDate < $end);
        $isPost = ($orderDate >= $end);

        if (!$isWithin && !$isPost) continue;

        $lineItems = $itemsByOrder[$order['id']] ?? [];
        
        // Fast consumption calculation using our cached menu links
        $consumption = [];
        foreach ($lineItems as $li) {
            $mid = $li['menuItemId'] ?? null;
            $qty = (float)($li['quantity'] ?? 0);
            if (!$mid || $qty <= 0) continue;
            
            $links = $menuStockLinks[$mid] ?? [];
            foreach ($links as $link) {
                $sid = $link['id'];
                $consumption[$sid] = ($consumption[$sid] ?? 0) + ($link['qty'] * $qty);
            }
        }

        if ($isWithin) {
            foreach ($consumption as $stockId => $qty) {
                if (!isset($stockMap[$stockId])) continue;
                $periodConsumption[$stockId] = ($periodConsumption[$stockId] ?? 0) + $qty;
                $unitCost = (float)($stockMap[$stockId]['unitCost'] ?? $stockMap[$stockId]['averagePurchasePrice'] ?? 0);
                $totalConsumptionValue += $qty * $unitCost;
                $totalItemsConsumed += $qty;
            }
        } else if ($isPost) {
            foreach ($consumption as $stockId => $qty) {
                $postPeriodConsumption[$stockId] = ($postPeriodConsumption[$stockId] ?? 0) + $qty;
            }
        }
    }

    // Store movements (restocks)
    $storeInByStock = [];
    $storeOutByStock = [];
    $postPeriodStoreIn = [];
    $postPeriodStoreOut = [];

    foreach ($storeLogs as $log) {
        $logDateStr = $log['date'] ?? $log['createdAt'] ?? null;
        if (!$logDateStr) continue;
        $logDate = new DateTime($logDateStr);
        
        $stockId = $log['stockId'] ?? null;
        if (!$stockId) continue;
        $type = $log['type'] ?? '';
        $qty = (float)($log['quantity'] ?? 0);

        if ($logDate >= $start && $logDate < $end) {
            if ($type === 'RESTOCK' || $type === 'PURCHASE') {
                $storeInByStock[$stockId] = ($storeInByStock[$stockId] ?? 0) + $qty;
            } else if ($type === 'TRANSFER_OUT' || $type === 'TRANSFER') {
                $storeOutByStock[$stockId] = ($storeOutByStock[$stockId] ?? 0) + $qty;
            }
        } elseif ($logDate >= $end) {
            if ($type === 'RESTOCK' || $type === 'PURCHASE') {
                $postPeriodStoreIn[$stockId] = ($postPeriodStoreIn[$stockId] ?? 0) + $qty;
            } else if ($type === 'TRANSFER_OUT' || $type === 'TRANSFER') {
                $postPeriodStoreOut[$stockId] = ($postPeriodStoreOut[$stockId] ?? 0) + $qty;
            }
        }
    }

    $analysis = [];
    foreach ($stockMap as $stockId => $stock) {
        $currentPosStock = (float)($stock['quantity'] ?? 0);
        $consumedInPeriod = (float)($periodConsumption[$stockId] ?? 0);
        
        // POS Stock Backtracking
        $consumedSince = (float)($postPeriodConsumption[$stockId] ?? 0);
        // TRANSFER_OUT from Store increases POS stock
        $posRestockedSince = (float)($postPeriodStoreOut[$stockId] ?? 0); 
        $closingPosStock = $currentPosStock + $consumedSince - $posRestockedSince;
        
        $openingPosStock = $closingPosStock + $consumedInPeriod;
        
        // Store Stock Backtracking
        $currentStoreQuantity = (float)($stock['storeQuantity'] ?? 0);
        $storeInSinceEnd = (float)($postPeriodStoreIn[$stockId] ?? 0);
        $storeOutSinceEnd = (float)($postPeriodStoreOut[$stockId] ?? 0);
        $closingStoreQuantity = $currentStoreQuantity - $storeInSinceEnd + $storeOutSinceEnd;

        $weightedAvgCost = (float)($stock['averagePurchasePrice'] ?? $stock['unitCost'] ?? 0);
        $currentUnitCost = (float)($stock['unitCost'] ?? $weightedAvgCost);
        $minLimit = (float)($stock['minLimit'] ?? 0);
        $storeIn = (float)($storeInByStock[$stockId] ?? 0);
        $storeOut = (float)($storeOutByStock[$stockId] ?? 0);

        $analysis[] = [
            'id' => $stockId,
            'name' => $stock['name'] ?? 'Unknown',
            'category' => $stock['category'] ?? 'General',
            'unit' => $stock['unit'] ?? 'pcs',
            'openingStock' => round($openingPosStock, 2),
            'closingStock' => round($closingPosStock, 2),
            'consumed' => round($consumedInPeriod, 2),
            'weightedAvgCost' => $weightedAvgCost,
            'currentUnitCost' => $currentUnitCost,
            // Remaining POS stock investment value at end of selected period
            'remainingInvestmentValue' => round($closingPosStock * $weightedAvgCost, 2),
            'storeQuantity' => round($closingStoreQuantity, 2),
            'storeClosingValue' => round($closingStoreQuantity * $weightedAvgCost, 2),
            'storeIn' => $storeIn,
            'storeOut' => $storeOut,
            'transferred' => $storeOut,
            'isLowStock' => $minLimit > 0 ? $closingPosStock <= $minLimit : $closingPosStock < 5,
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
