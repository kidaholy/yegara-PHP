<?php
/**
 * API for Menu Hub — Standard, VIP1, VIP2
 */
header('Content-Type: application/json');
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';


function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

requireAuth(['admin']);

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['collection'] ?? 'menuItems'; // menuItems, vip1Menu, vip2Menu

try {
    $db = db($type);

    if ($method === 'GET') {
        $items = $db->findMany(['where' => ['isDeleted' => false], 'orderBy' => ['menuId' => 'asc']]);
        
        // Spec: Standard menu GET filters OUT VIP items
        if ($type === 'menuItems') {
            $items = array_values(array_filter($items, function($i) {
                $name = strtolower($i['name'] ?? '');
                $cat = strtolower($i['category'] ?? '');
                return !(strpos($name, 'vip') !== false || strpos($cat, 'vip') !== false || ($i['isVIP'] ?? false));
            }));
        }

        sendJson(['status' => 'success', 'data' => $items, 'total' => count($items)]);
    }
    elseif ($method === 'POST') {
        $action = $_GET['action'] ?? null;
        
        if ($action === 'normalize') {
            // Re-index logic
            $items = $db->findMany(['where' => ['isDeleted' => false], 'orderBy' => ['menuId' => 'asc']]);
            foreach ($items as $idx => $it) {
                $db->update(['where' => ['id' => $it['id']], 'data' => ['menuId' => (string)($idx + 1)]]);
            }
            sendJson(['status' => 'success']);
        }
        
        if ($action === 'swap') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id1 = $input['menuId1'] ?? null;
            $id2 = $input['menuId2'] ?? null;
            if (!$id1 || !$id2) throw new Exception("Both menu IDs required for swap");

            $item1 = $db->findFirst(['where' => ['menuId' => (string)$id1, 'isDeleted' => false]]);
            $item2 = $db->findFirst(['where' => ['menuId' => (string)$id2, 'isDeleted' => false]]);
            
            if ($item1 && $item2) {
                $db->update(['where' => ['id' => $item1['id']], 'data' => ['menuId' => (string)$id2]]);
                $db->update(['where' => ['id' => $item2['id']], 'data' => ['menuId' => (string)$id1]]);
            }
            sendJson(['status' => 'success']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['name'])) throw new Exception("Name required");

        $id = bin2hex(random_bytes(16));
        $db->create(['data' => [
            'id' => $id,
            'menuId' => (string)($input['menuId'] ?? (count($db->findMany(['where' => ['isDeleted' => false]])) + 1)),
            'name' => $input['name'],
            'mainCategory' => $input['mainCategory'] ?? 'Food',
            'category' => $input['category'] ?? 'General',
            'price' => (float)$input['price'],
            'description' => $input['description'] ?? '',
            'image' => $input['image'] ?? null,
            'preparationTime' => $input['preparationTime'] ?? '',
            'available' => (bool)($input['available'] ?? true),
            'reportUnit' => $input['reportUnit'] ?? 'piece',
            'reportQuantity' => (float)($input['reportQuantity'] ?? 1),
            'stockItemId' => $input['stockItemId'] ?? null,
            'stockConsumption' => (float)($input['stockConsumption'] ?? 0),
            'distributions' => $input['distributions'] ?? [],
            'isDeleted' => false
        ]]);
        sendJson(['status' => 'success', 'id' => $id]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? '';
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$id) throw new Exception("ID required");

        $db->update(['where' => ['id' => $id], 'data' => $input]);
        sendJson(['status' => 'success']);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if (!$id) throw new Exception("ID required");
        $db->update(['where' => ['id' => $id], 'data' => ['isDeleted' => true]]);
        sendJson(['status' => 'success']);
    }
    else {
        sendJson(['message' => 'Method Not Allowed'], 405);
    }
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
