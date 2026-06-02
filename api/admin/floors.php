<?php
header('Content-Type: application/json');
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';

require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';

requireAuth(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = db('floors');
    
    if ($method === 'GET') {
        $floors = $db->findMany(['orderBy' => ['order' => 'asc']]);
        echo json_encode(['status' => 'success', 'data' => $floors]);
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['floorNumber'])) throw new Exception("floorNumber is required");
        
        $id = bin2hex(random_bytes(16));
        $db->create(['data' => [
            'id' => $id,
            'floorNumber' => (string)$input['floorNumber'],
            'order' => (int)($input['order'] ?? 0),
            'createdAt' => date('Y-m-d H:i:s')
        ]]);
        echo json_encode(['status' => 'success', 'data' => ['id' => $id]]);
    }
    elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) throw new Exception("id is required");
        
        $db->update(['id' => $input['id'], 'data' => [
            'floorNumber' => (string)$input['floorNumber'],
            'order' => (int)($input['order'] ?? 0)
        ]]);
        echo json_encode(['status' => 'success']);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if (!$id) throw new Exception("id is required");
        $db->delete(['id' => $id]);
        echo json_encode(['status' => 'success']);
    }
    else {
        http_response_code(405);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
