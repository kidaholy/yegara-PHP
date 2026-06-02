<?php
/**
 * StockService - Two-Tier Inventory Logic
 */
require_once 'JsonDB.php';

class StockService {
    /**
     * Restock items into Bulk Storage
     */
    public static function addToStore($id, $qtyAdded, $totalCost, $notes = '') {
        $item = db('stock_items')->findUnique(['where' => ['id' => $id]]);
        if (!$item) throw new Exception("Item not found");

        $newStoreQty = ($item['store_quantity'] ?? 0) + $qtyAdded;
        $newTotalPurchased = ($item['total_purchased'] ?? 0) + $qtyAdded;
        $newTotalInvestment = ($item['total_investment'] ?? 0) + $totalCost;
        
        // Calculate new weighted average price
        $newAvgPrice = ($newTotalPurchased > 0) ? ($newTotalInvestment / $newTotalPurchased) : 0;

        return db('stock_items')->update([
            'where' => ['id' => $id],
            'data' => [
                'store_quantity' => $newStoreQty,
                'total_purchased' => $newTotalPurchased,
                'total_investment' => $newTotalInvestment,
                'average_purchase_price' => $newAvgPrice,
                'status' => 'active'
            ]
        ]);
    }

    /**
     * Move items from Store (Bulk) to Stock (Active POS)
     */
    public static function moveToStock($id, $qtyToMove) {
        $item = db('stock_items')->findUnique(['where' => ['id' => $id]]);
        if (!$item) throw new Exception("Item not found");

        if (($item['store_quantity'] ?? 0) < $qtyToMove) {
            throw new Exception("Insufficient bulk storage quantity");
        }

        $newStoreQty = $item['store_quantity'] - $qtyToMove;
        $newStockQty = ($item['quantity'] ?? 0) + $qtyToMove;

        return db('stock_items')->update([
            'where' => ['id' => $id],
            'data' => [
                'store_quantity' => $newStoreQty,
                'quantity' => $newStockQty,
                'status' => ($newStockQty > 0) ? 'active' : $item['status']
            ]
        ]);
    }

    /**
     * Record consumption from Active POS Stock
     */
    public static function consume($id, $qtyUsed) {
        $item = db('stock_items')->findUnique(['where' => ['id' => $id]]);
        if (!$item) throw new Exception("Item not found");

        $newStockQty = max(0, ($item['quantity'] ?? 0) - $qtyUsed);
        $newTotalConsumed = ($item['total_consumed'] ?? 0) + $qtyUsed;
        
        $status = $item['status'];
        if ($newStockQty <= 0) $status = 'out_of_stock';

        return db('stock_items')->update([
            'where' => ['id' => $id],
            'data' => [
                'quantity' => $newStockQty,
                'total_consumed' => $newTotalConsumed,
                'status' => $status
            ]
        ]);
    }
}
