<?php
/**
 * API: Admin Instant Store → Stock Transfer
 */
require_once '../../includes/auth.php';
header('Content-Type: application/json');
if (!isAuthenticated()) { http_response_code(401); echo json_encode(['message'=>'Unauthorized']); exit; }
function j($d,$c=200){http_response_code($c);echo json_encode($d);exit;}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') j(['message'=>'Method not allowed'], 405);

    $user = getCurrentUser();
    if ($user['role'] !== 'admin') j(['message'=>'Admin only'], 403);

    $d = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($d['stockId']) || !isset($d['quantity'])) j(['message'=>'stockId and quantity required'], 400);

    $stockId = $d['stockId'];
    $qty     = floatval($d['quantity']);

    $stock = db('stocks')->findUnique(['where'=>['id'=>$stockId]]);
    if (!$stock) j(['message'=>'Item not found'], 404);

    $available = floatval($stock['storeQuantity'] ?? 0);
    if ($qty <= 0) j(['message'=>'Quantity must be positive'], 400);
    if ($qty > $available) j(['message'=>"Only {$available} {$stock['unit']} available in store. Cannot transfer {$qty}."], 400);

    $newStore  = $available - $qty;
    $newActive = floatval($stock['quantity'] ?? 0) + $qty;

    $unitPrice = floatval($stock['averagePurchasePrice'] ?? 0);
    $newInvest = max(0, floatval($stock['totalInvestment'] ?? 0) - ($qty * $unitPrice));

    db('stocks')->update(['where'=>['id'=>$stockId], 'data'=>[
        'storeQuantity' => $newStore,
        'quantity'      => $newActive,
        'totalInvestment' => $newInvest,
        'status'        => 'active',
    ]]);

    // Log the transfer
    db('storeLogs')->create(['data'=>[
        'stockId'   => $stockId,
        'itemName'  => $stock['name'],
        'type'      => 'TRANSFER',
        'quantity'  => $qty,
        'notes'     => $d['notes'] ?? '',
        'by'        => $user['name'] ?? '',
        'date'      => date('c'),
    ]]);

    j(['message' => "Transferred {$qty} {$stock['unit']} of {$stock['name']} to active POS stock."]);

} catch (Exception $e) { j(['message'=>$e->getMessage()], 500); }
