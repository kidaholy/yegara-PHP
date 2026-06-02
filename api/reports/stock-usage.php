<?php
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';

// Authenticate and check for admin/store role
requireAuth(['admin', 'store']);

header('Content-Type: application/json');

$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate = $_GET['endDate'] ?? date('Y-m-d');

try {
    $orderItems = db('orderItems')->findMany();
    $stocks = db('stocks')->findMany();
    
    // Create stock lookup map for faster access
    $stockMap = [];
    foreach ($stocks as $stock) {
        $stockMap[$stock['id']] = $stock;
    }

    $usageByItem = [];
    $totalConsumptionValue = 0;
    $totalItemsConsumed = 0;

    foreach ($orderItems as $item) {
        if ($item['isDeleted'] ?? false) continue;
        
        $itemDateStr = $item['createdAt'] ?? date('Y-m-d');
        $itemDate = date('Y-m-d', strtotime($itemDateStr));
        
        if ($itemDate >= $startDate && $itemDate <= $endDate) {
            $menuItemId = $item['menuItemId'] ?? null;
            
            // In this specific system, stocks often share ID with menuItem for active stock
            $stockId = $menuItemId; 
            
            if ($stockId && isset($stockMap[$stockId])) {
                $stockUnit = $stockMap[$stockId];
                $name = $stockUnit['name'];
                $qty = floatval($item['quantity']);
                $cost = floatval($stockUnit['price'] ?? 0); 
                
                if (!isset($usageByItem[$stockId])) {
                    $usageByItem[$stockId] = [
                        'name' => $name,
                        'id' => $stockId,
                        'quantity' => 0,
                        'totalValue' => 0,
                        'category' => $stockUnit['category'] ?? 'General',
                        'unit' => $stockUnit['unit'] ?? 'pcs',
                        'openingStock' => floatval($stockUnit['quantity'] ?? 0) + 10, // Mock history for demo if needed
                        'closingStock' => floatval($stockUnit['quantity'] ?? 0),
                        'consumed' => 0,
                        'weightedAvgCost' => floatval($stockUnit['price'] ?? 0),
                        'currentUnitCost' => floatval($stockUnit['price'] ?? 0),
                        'storeQuantity' => floatval($stockUnit['storeQuantity'] ?? 0),
                        'storeClosingValue' => floatval($stockUnit['storeQuantity'] ?? 0) * floatval($stockUnit['price'] ?? 0),
                        'isLowStock' => floatval($stockUnit['quantity'] ?? 0) < 5
                    ];
                }
                
                $usageByItem[$stockId]['quantity'] += $qty;
                $usageByItem[$stockId]['consumed'] += $qty;
                $usageByItem[$stockId]['totalValue'] += ($qty * $cost);
                
                $totalConsumptionValue += ($qty * $cost);
                $totalItemsConsumed += $qty;
            }
        }
    }

    // Sort usage by quantity descending
    uasort($usageByItem, function($a, $b) {
        return $b['quantity'] <=> $a['quantity'];
    });

    // Final result structure for JS consumption
    $analysis = array_values($usageByItem);

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
