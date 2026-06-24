<?php
/**
 * API: Stock Items
 * Reads from stocks.json (camelCase fields match original MongoDB model)
 */
require_once '../includes/auth.php';

header('Content-Type: application/json');

requireApiAuth(['admin', 'store', 'store_keeper'], ['store:view', 'stock:view']);

function j($data, $code = 200) { http_response_code($code); echo json_encode($data); exit; }

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id     = $_GET['id'] ?? null;
    $source = $_GET['source'] ?? null;
    $availableOnly = filter_var($_GET['availableOnly'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // ── GET ──────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        if ($id) {
            $item = db('stocks')->findUnique(['where' => ['id' => $id]]);
            if (!$item) j(['message' => 'Not found'], 404);
            j($item);
        }

        $all = db('stocks')->findMany([]);
        if ($availableOnly) {
            $all = array_values(array_filter($all, fn($i) => ($i['status'] ?? '') === 'active' && ($i['quantity'] ?? 0) > 0));
        }
        j($all);
    }

    // ── POST (Create) ─────────────────────────────────────────────────────────
    if ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($d['name']) || empty($d['category'])) j(['message' => 'Name and category required'], 400);

        $bulkQty   = floatval($d['storeQuantity'] ?? $d['initialStoreQuantity'] ?? 0);
        $unitPrice = floatval($d['averagePurchasePrice'] ?? 0);

        $item = db('stocks')->create(['data' => [
            'name'                 => trim($d['name']),
            'category'             => $d['category'],
            'unit'                 => $d['unit']     ?? 'pcs',
            'unitType'             => $d['unitType']  ?? 'count',
            'quantity'             => 0,              // POS always starts 0
            'storeQuantity'        => $bulkQty,
            'minLimit'             => floatval($d['minLimit'] ?? 5),
            'storeMinLimit'        => floatval($d['storeMinLimit'] ?? 20),
            'averagePurchasePrice' => $unitPrice,
            'unitCost'             => floatval($d['unitCost'] ?? 0),
            'totalInvestment'      => $bulkQty * $unitPrice,
            'totalPurchased'       => $bulkQty,
            'totalConsumed'        => 0,
            'trackQuantity'        => true,
            'showStatus'           => true,
            'status'               => ($bulkQty > 0) ? 'active' : 'out_of_stock',
            'isVIP'                => false,
            'vipLevel'             => 1,
            'restockHistory'       => [],
        ]]);
        j(['message' => 'Item created', 'item' => $item], 201);
    }

    // ── PUT (Update / Restock) ────────────────────────────────────────────────
    if ($method === 'PUT') {
        if (!$id) j(['message' => 'ID required'], 400);
        $d = json_decode(file_get_contents('php://input'), true) ?? [];

        $item = db('stocks')->findUnique(['where' => ['id' => $id]]);
        if (!$item) j(['message' => 'Not found'], 404);

        if (($d['action'] ?? '') === 'restock') {
            $added     = floatval($d['quantityAdded']);
            $unitPrice = floatval($d['unitPrice'] ?? $item['averagePurchasePrice'] ?? 0);
            
            $newStore  = round(($item['storeQuantity'] ?? 0) + $added, 2);
            $newTotal  = round(($item['totalPurchased']  ?? 0) + $added, 2);
            
            // Recalibrate total investment based on new unit price (user requirement: unit price goes with new one)
            $newInvest = round($newStore * $unitPrice, 2);

            $entry = [
                'id'               => uniqid(),
                'date'             => date('c'),
                'quantityAdded'    => $added,
                'unitPrice'        => $unitPrice,
                'totalPurchaseCost'=> $added * $unitPrice,
                'notes'            => $d['notes'] ?? '',
            ];

            $updated = db('stocks')->update(['where' => ['id' => $id], 'data' => [
                'storeQuantity'        => $newStore,
                'totalPurchased'       => $newTotal,
                'totalInvestment'      => $newInvest,
                'averagePurchasePrice' => $unitPrice,
                'unitCost'             => !empty($d['newUnitCost']) ? floatval($d['newUnitCost']) : $item['unitCost'],
                'status'               => 'active',
                'restockHistory'       => array_merge($item['restockHistory'] ?? [], [$entry]),
            ]]);

            // Log movement for reports
            db('storeLogs')->create(['data' => [
                'stockId' => $id,
                'type' => 'RESTOCK',
                'quantity' => $added,
                'date' => date('c'),
                'notes' => 'Restock via Stock Management'
            ]]);

            j(['message' => 'Restocked and price updated', 'item' => $updated]);
        }

        if (($d['action'] ?? '') === 'decrease') {
            $qty = floatval($d['quantity']);
            $currentPrice = floatval($item['averagePurchasePrice'] ?? 0);
            
            $newStore  = round(max(0, ($item['storeQuantity'] ?? 0) - $qty), 2);
            $reduction = round($qty * $currentPrice, 2);
            $newInvest = round(max(0, ($item['totalInvestment'] ?? 0) - $reduction), 2);

            $updated = db('stocks')->update(['where' => ['id' => $id], 'data' => [
                'storeQuantity'   => $newStore,
                'totalInvestment' => $newInvest,
            ]]);
            j(['message' => 'Stock decreased and expense reduced', 'item' => $updated]);
        }

        // Plain meta update (name, category, unit, unitType, unitCost, minLimit, storeMinLimit, isVIP, vipLevel, quantity, storeQuantity, averagePurchasePrice, totalInvestment)
        $patch = [];
        $fields = ['name','category','unit','unitType','unitCost','minLimit','storeMinLimit','isVIP','vipLevel','quantity','storeQuantity','averagePurchasePrice','totalInvestment','status'];
        foreach ($fields as $f) {
            if (isset($d[$f])) $patch[$f] = is_numeric($d[$f]) ? floatval($d[$f]) : $d[$f];
        }

        // Auto-status update logic
        if (isset($patch['quantity'])) {
            $patch['status'] = $patch['quantity'] > 0 ? 'active' : 'out_of_stock';
        } else if (isset($patch['storeQuantity']) && !isset($patch['status'])) {
             // If store has stuff but POS is 0, it might still be active if trackQuantity is off, 
             // but here we usually follow the POS quantity for status.
        }

        $updated = db('stocks')->update(['where' => ['id' => $id], 'data' => $patch]);

        // After update, ensure totalInvestment is consistent if storeQuantity or averagePurchasePrice changed
        if (isset($patch['storeQuantity']) || isset($patch['averagePurchasePrice'])) {
            $refreshed = db('stocks')->findUnique(['where' => ['id' => $id]]);
            if ($refreshed && !isset($d['totalInvestment'])) {
                $q = floatval($refreshed['storeQuantity'] ?? 0);
                $p = floatval($refreshed['averagePurchasePrice'] ?? 0);
                db('stocks')->update(['where' => ['id' => $id], 'data' => ['totalInvestment' => $q * $p]]);
            }
        }

        j(['message' => 'Updated', 'item' => $updated]);
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    if ($method === 'DELETE') {
        if ($id === 'all' || $id === 'all_store' || $id === 'all_stock') {
            $stocks = db('stocks')->findMany();
            foreach ($stocks as $s) {
                $patch = [];
                if ($id === 'all' || $id === 'all_store') {
                    $patch['storeQuantity'] = 0;
                    $patch['totalInvestment'] = 0;
                }
                if ($id === 'all' || $id === 'all_stock') {
                    $patch['quantity'] = 0;
                    $patch['status'] = 'out_of_stock';
                }
                
                if (!empty($patch)) {
                    if ($id === 'all') {
                        $patch['restockHistory'] = [];
                    }
                    db('stocks')->update(['where' => ['id' => $s['id']], 'data' => $patch]);
                }
            }

            // If global wipe, clear the audit trails too
            if ($id === 'all') {
                db('storeLogs')->deleteMany(['where' => []]);
                db('transferRequests')->deleteMany(['where' => []]);
                // Optional: clear restock history in each stock item if needed, but array_merge happens in PUT.
            }

            j(['message' => 'Requested quantities wiped']);
        }

        if (!$id) j(['message' => 'ID required'], 400);
        if ($source === 'store') {
            db('stocks')->update(['where' => ['id' => $id], 'data' => ['storeQuantity' => 0, 'totalInvestment' => 0]]);
            j(['message' => 'Removed from bulk store']);
        } else {
            db('stocks')->update(['where' => ['id' => $id], 'data' => ['quantity' => 0, 'status' => 'out_of_stock']]);
            j(['message' => 'Removed from active POS stock']);
        }
    }

    j(['message' => 'Method not allowed'], 405);

} catch (Exception $e) {
    j(['message' => $e->getMessage()], 500);
}
