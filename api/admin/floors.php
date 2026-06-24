<?php
// api/admin/floors.php

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/SettingsManager.php';
require_once __DIR__ . '/../../includes/auth.php';

$manager = new SettingsManager();

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        requireAuth(['admin', 'reception', 'receptionist'], ['settings:view', 'services:view', 'reception:access']);
    } else {
        requireAuth(['admin'], ['settings:update', 'services:update']);
    }
    $id = $_GET['id'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true);

    // GET: Fetch all floors
    if ($method === 'GET') {
        $floors = $manager->getFloors();
        echo json_encode(['status' => 'success', 'data' => $floors]);
    }
    
    // POST: Add floor
    else if ($method === 'POST') {
        $floorNumber = $input['floorNumber'] ?? null;
        $order = $input['order'] ?? 0;

        if (!$floorNumber) {
            http_response_code(400);
            echo json_encode(['message' => 'Floor number is required']);
            exit;
        }

        $floor = $manager->addFloor($floorNumber, (int)$order);
        echo json_encode($floor);
    }
    
    // PUT: Update floor
    else if ($method === 'PUT') {
        $floorNumber = $input['floorNumber'] ?? null;
        $order = $input['order'] ?? 0;

        if (!$id || !$floorNumber) {
            http_response_code(400);
            echo json_encode(['message' => 'ID and floor number are required']);
            exit;
        }

        $manager->updateFloor($id, $floorNumber, (int)$order);
        echo json_encode(['success' => true]);
    }
    
    // DELETE: Remove floor
    else if ($method === 'DELETE') {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['message' => 'ID is required']);
            exit;
        }

        $manager->deleteFloor($id);
        echo json_encode(['success' => true]);
    }
    
    else {
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => $e->getMessage()]);
}
