<?php
/**
 * API: Operational Expenses
 */
require_once '../includes/auth.php';
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
            $all = array_values(array_filter($all, function($e) use ($period, $now) {
                $d = strtotime($e['date'] ?? $e['recorded_at'] ?? '');
                if (!$d) return true;
                return match($period) {
                    'today' => date('Y-m-d', $d) === date('Y-m-d'),
                    'week'  => ($now - $d) < 7  * 86400,
                    'month' => ($now - $d) < 30 * 86400,
                    default => true,
                };
            }));
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
            'date'        => $d['date'] ?? date('Y-m-d'),
            'description' => $d['description'] ?? '',
            'recorded_at' => date('c'),
        ]]);
        j(['message'=>'Expense created','item'=>$expense], 201);
    }

    if ($method === 'DELETE') {
        if (!$id) j(['message'=>'ID required'], 400);
        db('operationalExpenses')->delete(['where'=>['id'=>$id]]);
        j(['message'=>'Expense deleted']);
    }

    j(['message'=>'Method not allowed'], 405);
} catch (Exception $e) { j(['message'=>$e->getMessage()], 500); }
