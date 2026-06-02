<?php
/**
 * API: Stock Items
 * Reads from stocks.json (camelCase fields match original MongoDB model)
 */
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isAuthenticated()) { http_response_code(401); echo json_encode(['message' => 'Unauthorized']); exit; }

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
            $totalCost = floatval($d['totalPurchaseCost']);
            $newStore  = ($item['storeQuantity'] ?? 0) + $added;
            $newTotal  = ($item['totalPurchased']  ?? 0) + $added;
            $newInvest = ($item['totalInvestment'] ?? 0) + $totalCost;
            $newAvg    = $newTotal > 0 ? $newInvest / $newTotal : 0;

            $entry = [
                'id'               => uniqid(),
                'date'             => date('c'),
                'quantityAdded'    => $added,
                'totalPurchaseCost'=> $totalCost,
                'unitCostAtTime'   => $totalCost > 0 && $added > 0 ? $totalCost / $added : 0,
                'notes'            => $d['notes'] ?? '',
            ];

            $updated = db('stocks')->update(['where' => ['id' => $id], 'data' => [
                'storeQuantity'        => $newStore,
                'totalPurchased'       => $newTotal,
                'totalInvestment'      => $newInvest,
                'averagePurchasePrice' => $newAvg,
                'unitCost'             => !empty($d['newUnitCost']) ? floatval($d['newUnitCost']) : $item['unitCost'],
                'status'               => 'active',
                'restockHistory'       => array_merge($item['restockHistory'] ?? [], [$entry]),
            ]]);
            j(['message' => 'Restocked', 'item' => $updated]);
        }

        // Plain meta update (name, category, limits, unit cost, or manual qty)
        $patch = [];
        foreach (['name','category','unit','unitType','unitCost','minLimit','storeMinLimit','isVIP','vipLevel','quantity'] as $f) {
            if (isset($d[$f])) $patch[$f] = is_numeric($d[$f]) ? floatval($d[$f]) : $d[$f];
        }
        $updated = db('stocks')->update(['where' => ['id' => $id], 'data' => $patch]);
        j(['message' => 'Updated', 'item' => $updated]);
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    if ($method === 'DELETE') {
        if (!$id) j(['message' => 'ID required'], 400);
        if ($source === 'store') {
            db('stocks')->update(['where' => ['id' => $id], 'data' => ['storeQuantity' => 0]]);
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
