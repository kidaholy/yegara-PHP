<?php
/**
 * API for Room Management
 */
header('Content-Type: application/json');
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';


function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$role = $_SESSION['role'] ?? '';

    if ($method === 'GET') {
        requireAuth(['admin', 'reception', 'receptionist'], ['settings:view', 'services:view', 'reception:access']);
    } else {
        requireAuth(['admin'], ['settings:update', 'services:update']);
    }

try {
    $db = db('rooms');

    if ($method === 'GET') {
        $rooms = $db->findMany(['where' => ['isDeleted' => false], 'orderBy' => ['roomNumber' => 'asc']]);
        sendJson(['status' => 'success', 'data' => $rooms]);
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['roomNumber'])) throw new Exception("roomNumber is required");
        
        $id = bin2hex(random_bytes(16));
        $db->create(['data' => [
            'id' => $id,
            'roomNumber' => (string)$input['roomNumber'],
            'floorId' => $input['floorId'] ?? '',
            'type' => $input['type'] ?? 'standard',
            'category' => $input['category'] ?? 'Standard',
            'price' => (float)$input['price'],
            'status' => $input['status'] ?? 'available',
            'roomServiceMenuTier' => $input['roomServiceMenuTier'] ?? 'standard',
            'isDeleted' => false
        ]]);
        sendJson(['status' => 'success', 'id' => $id]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? '';
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$id) throw new Exception("ID required");

        $db->update(['where' => ['id' => $id], 'data' => [
            'roomNumber' => (string)$input['roomNumber'],
            'floorId' => $input['floorId'] ?? '',
            'type' => $input['type'] ?? 'standard',
            'category' => $input['category'] ?? 'Standard',
            'price' => (float)$input['price'],
            'status' => $input['status'] ?? 'available',
            'roomServiceMenuTier' => $input['roomServiceMenuTier'] ?? 'standard'
        ]]);
        sendJson(['status' => 'success']);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if (!$id) throw new Exception("ID required");
        
        // Soft delete
        $db->update(['where' => ['id' => $id], 'data' => ['isDeleted' => true]]);
        sendJson(['status' => 'success']);
    }
    else {
        sendJson(['message' => 'Method Not Allowed'], 405);
    }
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
