<?php
/**
 * API Endpoint for Categories
 * Supports CRUD for ?type=menu|stock|distribution
 */
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/JsonDB.php';

function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!isAuthenticated()) {
    sendJson(['message' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = db('categories');
    
    if ($method === 'GET') {
        $type = $_GET['type'] ?? 'menu';
        $categories = $db->findMany([
            'where' => ['isDeleted' => false, 'type' => $type],
            'orderBy' => ['name' => 'asc']
        ]);
        sendJson($categories);
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['name'])) sendJson(['message' => 'Name is required'], 400);
        
        $id = bin2hex(random_bytes(16));
        $db->create(['data' => [
            'id' => $id,
            'name' => (string)$input['name'],
            'type' => (string)($input['type'] ?? 'menu'),
            'description' => (string)($input['description'] ?? ''),
            'isDeleted' => false,
            'createdAt' => date('Y-m-d H:i:s')
        ]]);
        sendJson(['status' => 'success', 'id' => $id]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? '';
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$id) sendJson(['message' => 'ID is required'], 400);
        
        $db->update(['id' => $id, 'data' => [
            'name' => (string)$input['name']
        ]]);
        sendJson(['status' => 'success']);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if (!$id) sendJson(['message' => 'ID is required'], 400);
        
        // Soft delete
        $db->update(['id' => $id, 'data' => ['isDeleted' => true]]);
        sendJson(['status' => 'success']);
    }
    else {
        sendJson(['message' => 'Method Not Allowed'], 405);
    }

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
