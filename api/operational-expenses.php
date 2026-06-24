<?php
/**
 * API: Operational Expenses
 */
require_once '../includes/auth.php';
require_once '../includes/report-dates.php'; // Add this for business date logic
header('Content-Type: application/json');
if (!isAuthenticated()) { http_response_code(401); echo json_encode(['message'=>'Unauthorized']); exit; }
function j($d,$c=200){http_response_code($c);echo json_encode($d);exit;}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id = $_GET['id'] ?? null;

    if ($method === 'GET') {
        $all = db('operationalExpenses')->findMany([]);
        $period = $_GET['period'] ?? 'all';
        if ($period !== 'all') {
            $now = time();
                $range = resolveReportDateRange($period);
                $start = $range['start'];
                $end = $range['end'];
                $eDate = new DateTime($e['date'] ?? $e['recorded_at'] ?? '');
                return $eDate >= $start && $eDate < $end;
        }
        j($all ?: []);
    }

    if ($method === 'POST') {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($d['name'])) j(['message'=>'Name required'], 400);
        $unitCost = floatval($d['unitCost'] ?? $d['unit_cost'] ?? 0);
        $qty      = floatval($d['quantity'] ?? 0);
        $expense = db('operationalExpenses')->create(['data' => [
            'name'        => trim($d['name']),
            'category'    => $d['category'] ?? 'General',
            'unit_cost'   => $unitCost,
            'quantity'    => $qty,
            'unit'        => $d['unit'] ?? 'pcs',
            'amount'      => $unitCost * $qty,
            'date'        => $d['date'] ?? getActiveBusinessDate(),
            'description' => $d['description'] ?? '',
            'recorded_at' => date('Y-m-d H:i:s'),
        ]]);
        j(['message'=>'Expense created','item'=>$expense], 201);
    }

    if ($method === 'PUT') {
        if (!$id) j(['message'=>'ID required'], 400);
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $expense = db('operationalExpenses')->findUnique(['where'=>['id'=>$id]]);
        if (!$expense) j(['message'=>'Expense not found'], 404);

        if (($d['action'] ?? '') === 'decrease') {
            $qtyToRemove = floatval($d['quantity']);
            $newQty = max(0, floatval($expense['quantity'] ?? 0) - $qtyToRemove);
            $unitCost = floatval($expense['unit_cost'] ?? 0);
            
            $updated = db('operationalExpenses')->update(['where'=>['id'=>$id], 'data' => [
                'quantity' => $newQty,
                'amount'   => $newQty * $unitCost
            ]]);
            j(['message'=>'Expense decreased', 'item'=>$updated]);
        }

        if (($d['action'] ?? '') === 'restock') {
            $qtyToAdd = floatval($d['quantityAdded']);
            $unitCost = floatval($d['unitPrice'] ?? $expense['unit_cost'] ?? 0);
            $newQty   = ($expense['quantity'] ?? 0) + $qtyToAdd;
            
            $updated = db('operationalExpenses')->update(['where'=>['id'=>$id], 'data' => [
                'quantity'  => $newQty,
                'unit_cost' => $unitCost,
                'amount'    => $newQty * $unitCost
            ]]);
            j(['message'=>'Expense restocked and price updated', 'item'=>$updated]);
        }

        // Generic update
        $patch = [];
        foreach (['name','category','unit_cost','quantity','unit','date','description'] as $f) {
            if (isset($d[$f])) $patch[$f] = is_numeric($d[$f]) ? floatval($d[$f]) : $d[$f];
        }
        if (isset($patch['unit_cost']) || isset($patch['quantity'])) {
            $u = floatval($patch['unit_cost'] ?? $expense['unit_cost']);
            $q = floatval($patch['quantity'] ?? $expense['quantity']);
            $patch['amount'] = $u * $q;
        }

        $updated = db('operationalExpenses')->update(['where'=>['id'=>$id], 'data'=>$patch]);
        j(['message'=>'Expense updated', 'item'=>$updated]);
    }

    if ($method === 'DELETE') {
        if (!$id) j(['message'=>'ID required'], 400);
        db('operationalExpenses')->delete(['where'=>['id'=>$id]]);
        j(['message'=>'Expense deleted']);
    }

    j(['message'=>'Method not allowed'], 405);
} catch (Exception $e) { j(['message'=>$e->getMessage()], 500); }
