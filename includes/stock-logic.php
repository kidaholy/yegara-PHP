<?php
/**
 * stock-logic.php - Unified Stock-Menu Linkage & Deduction Engine
 */

require_once __DIR__ . '/menu-tiers.php';

/**
 * Calculate total stock consumption for a list of order items
 * @param array $items Array of ['menuItemId' => string, 'quantity' => number]
 * @return array Map of stockId => totalAmount
 */
function calculateStockConsumption($items) {
    $consumptionMap = [];
    $collections = getAllMenuCollections();
    
    foreach ($items as $item) {
        $menuItemId = $item['menuItemId'] ?? null;
        $orderQty = (float)($item['quantity'] ?? 0);
        if (!$menuItemId || $orderQty <= 0) continue;

        // 1. Find menu item across all collections
        $menuData = null;
        foreach ($collections as $col) {
            $menuData = db($col)->findUnique(['where' => ['id' => $menuItemId]]);
            if ($menuData) break;
        }

        if (!$menuData) continue;

        // Priority 1: Recipe System
        $recipe = $menuData['recipe'] ?? [];
        if (!empty($recipe)) {
            foreach ($recipe as $ingredient) {
                $stockId = $ingredient['stockItemId'] ?? null;
                // VIP recipe uses 'quantity', standard uses 'quantityRequired'
                $qtyPerItem = (float)($ingredient['quantityRequired'] ?? $ingredient['quantity'] ?? 0);
                
                if ($stockId && $qtyPerItem > 0) {
                    $totalAmount = $qtyPerItem * $orderQty;
                    $consumptionMap[$stockId] = ($consumptionMap[$stockId] ?? 0) + $totalAmount;
                }
            }
            continue; // Skip legacy if recipe exists
        }

        // Priority 2: Legacy Fallback
        $legacyStockId = $menuData['stockItemId'] ?? null;
        $reportQty = (float)($menuData['reportQuantity'] ?? $menuData['stockConsumption'] ?? 0);
        if ($legacyStockId && $reportQty > 0) {
            $totalAmount = $reportQty * $orderQty;
            $consumptionMap[$legacyStockId] = ($consumptionMap[$legacyStockId] ?? 0) + $totalAmount;
        }
    }

    return $consumptionMap;
}

/**
 * Apply stock adjustment (deduct or restore) in bulk
 * @param array $consumptionMap Map of stockId => amount
 * @param int $direction -1 for deduction, 1 for restoration
 */
function applyStockAdjustment($consumptionMap, $direction) {
    foreach ($consumptionMap as $stockId => $amount) {
        $stock = db('stocks')->findUnique(['where' => ['id' => $stockId]]);
        if (!$stock || ($stock['trackQuantity'] ?? true) === false) continue;

        $newQty      = round(max(0, (float)($stock['quantity'] ?? 0) + ($amount * $direction)), 2);
        $newConsumed = round((float)($stock['totalConsumed'] ?? 0) + ($amount * (-$direction)), 2);
        
        $status = ($newQty > 0) ? 'active' : 'out_of_stock';
        if (isset($stock['status']) && $stock['status'] === 'finished') {
            $status = 'finished'; // Preserve finished state if manual
        }

        db('stocks')->update([
            'where' => ['id' => $stockId],
            'data' => [
                'quantity' => $newQty,
                'totalConsumed' => $newConsumed,
                'status' => $status,
                'updatedAt' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}

/**
 * Validate stock availability for a consumption map
 * @param array $consumptionMap
 * @throws Exception with insufficient stock message
 */
function validateStockAvailability($consumptionMap) {
    foreach ($consumptionMap as $stockId => $amount) {
        $stock = db('stocks')->findUnique(['where' => ['id' => $stockId]]);
        if (!$stock || ($stock['trackQuantity'] ?? true) === false) continue;

        $available = (float)($stock['quantity'] ?? 0);
        if ($available < $amount) {
            throw new Exception("Insufficient stock: {$stock['name']}. Required: {$amount} {$stock['unit']}, Available: {$available} {$stock['unit']}");
        }
    }
}
