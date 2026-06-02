<?php
/**
 * API: Fixed Assets — matches real fixedAssets.json schema (camelCase)
 */
require_once '../includes/auth.php';
header('Content-Type: application/json');
if (!isAuthenticated()) { http_response_code(401); echo json_encode(['message'=>'Unauthorized']); exit; }
function j($d,$c=200){http_response_code($c);echo json_encode($d);exit;}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id     = $_GET['id'] ?? null;

    if ($method === 'GET') {
        $all = db('fixedAssets')->findMany([]);
        j($all ?: []);
    }

    if ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($d['name'])) j(['message'=>'Name required'], 400);

        $qty   = floatval($d['quantity'] ?? 1);
        $price = floatval($d['unitPrice'] ?? 0);

        $item = db('fixedAssets')->create(['data' => [
            'name'          => trim($d['name']),
            'category'      => $d['category'] ?? 'General',
            'quantity'      => $qty,
            'unitPrice'     => $price,
            'totalValue'    => $qty * $price,
            'totalInvested' => $qty * $price,
            'purchaseDate'  => $d['purchaseDate'] ?? date('Y-m-d') . 'T00:00:00.000Z',
            'status'        => 'active',
            'notes'         => $d['notes'] ?? '',
            'dismissals'    => [],
        ]]);
        j(['message'=>'Asset created', 'item'=>$item], 201);
    }

    if ($method === 'PUT') {
        if (!$id) j(['message'=>'ID required'], 400);
        $d     = json_decode(file_get_contents('php://input'), true) ?? [];
        $asset = db('fixedAssets')->findUnique(['where'=>['id'=>$id]]);
        if (!$asset) j(['message'=>'Not found'], 404);

        if (($d['action']??'') === 'dismiss') {
            $qty       = floatval($d['quantity'] ?? 0);
            $valueLost = floatval($d['valueLost'] ?? 0);
            $newDismissals = array_merge($asset['dismissals'] ?? [], [[
                'date'      => date('c'),
                'quantity'  => $qty,
                'reason'    => $d['reason'] ?? '',
                'valueLost' => $valueLost,
            ]]);
            $remaining  = max(0, ($asset['quantity'] ?? 0) - $qty);
            $newStatus  = $remaining <= 0 ? 'dismissed' : 'partial';
            $updated = db('fixedAssets')->update(['where'=>['id'=>$id], 'data'=>[
                'quantity'   => $remaining,
                'totalValue' => max(0, ($asset['totalValue']??0) - $valueLost),
                'status'     => $newStatus,
                'dismissals' => $newDismissals,
            ]]);
            j(['message'=>'Asset dismissed', 'item'=>$updated]);
        }

        // Generic update
        $patch = [];
        foreach (['name','category','notes','status'] as $f) { if (isset($d[$f])) $patch[$f] = $d[$f]; }
        if (isset($d['quantity'])) {
            $patch['quantity']   = floatval($d['quantity']);
            $patch['totalValue'] = $patch['quantity'] * ($asset['unitPrice'] ?? 0);
        }
        $updated = db('fixedAssets')->update(['where'=>['id'=>$id], 'data'=>$patch]);
        j(['message'=>'Updated', 'item'=>$updated]);
    }

    if ($method === 'DELETE') {
        if (!$id) j(['message'=>'ID required'], 400);
        db('fixedAssets')->delete(['where'=>['id'=>$id]]);
        j(['message'=>'Asset deleted']);
    }

    j(['message'=>'Method not allowed'], 405);
} catch (Exception $e) { j(['message'=>$e->getMessage()], 500); }
