<?php
/**
 * API: Inventory Transfer Requests
 */
require_once '../includes/auth.php';
header('Content-Type: application/json');
requireApiAuth(['admin', 'store', 'store_keeper'], 'store:view');
function j($d,$c=200){http_response_code($c);echo json_encode($d);exit;}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id = $_GET['id'] ?? null;

    if ($method === 'GET') {
        $all = db('transferRequests')->findMany([]);
        j($all ?: []);
    }

    if ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($d['stockId']) || empty($d['quantity'])) j(['message'=>'stockId and quantity required'], 400);
        $user = getCurrentUser();
        $req = db('transferRequests')->create(['data' => [
            'stockId'     => $d['stockId'],
            'quantity'    => floatval($d['quantity']),
            'notes'       => $d['notes'] ?? '',
            'status'      => 'pending',
            'requestedBy' => $user['name'] ?? 'Unknown',
            'requestedById'=> $user['id'] ?? '',
            'createdAt'   => date('c'),
        ]]);
        j(['message'=>'Transfer request submitted','item'=>$req], 201);
    }

    if ($method === 'PATCH') {
        if (!$id) j(['message'=>'ID required'], 400);
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $d['action'] ?? '';
        $req = db('transferRequests')->findUnique(['where'=>['id'=>$id]]);
        if (!$req) j(['message'=>'Not found'], 404);

        if ($action === 'approved') {
            // Perform actual transfer
            $stock = db('stocks')->findUnique(['where'=>['id'=>$req['stockId']]]);
            if ($stock) {
                $qty       = floatval($req['quantity']);
                $newStore  = round(max(0, ($stock['storeQuantity']??0) - $qty), 2);
                $newActive = round(($stock['quantity']??0) + $qty, 2);
                $unitPrice = floatval($stock['averagePurchasePrice'] ?? 0);
                $newInvest = round(max(0, floatval($stock['totalInvestment'] ?? 0) - ($qty * $unitPrice)), 2);
                db('stocks')->update(['where'=>['id'=>$req['stockId']], 'data'=>[
                    'storeQuantity'   => $newStore,
                    'quantity'        => $newActive,
                    'totalInvestment' => $newInvest,
                    'status'          => 'active',
                ]]);

                // Log movement for reports
                db('storeLogs')->create(['data' => [
                    'stockId' => $req['stockId'],
                    'type' => 'TRANSFER_OUT',
                    'quantity' => $qty,
                    'date' => date('c'),
                    'notes' => 'Store Transfer approved'
                ]]);
            }
            db('transferRequests')->update(['where'=>['id'=>$id], 'data'=>['status'=>'approved','approvedAt'=>date('c')]]);
            j(['message'=>'Transfer approved and executed']);
        }

        if ($action === 'denied') {
            db('transferRequests')->update(['where'=>['id'=>$id], 'data'=>['status'=>'denied','denialReason'=>$d['reason']??'','deniedAt'=>date('c')]]);
            j(['message'=>'Transfer denied']);
        }

        j(['message'=>'Unknown action'], 400);
    }

    j(['message'=>'Method not allowed'], 405);
} catch (Exception $e) { j(['message'=>$e->getMessage()], 500); }
