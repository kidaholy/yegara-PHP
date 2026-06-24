<?php
// api/admin/tables.php

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

    // GET: Fetch all tables
    if ($method === 'GET') {
        $tables = $manager->getTables();
        echo json_encode($tables);
    }
    
    // POST: Add table
    else if ($method === 'POST') {
        $tableNumber = $input['tableNumber'] ?? null;
        $capacity = $input['capacity'] ?? null;
        $floor_id = $input['floor_id'] ?? null;

        if (!$tableNumber || !$capacity) {
            http_response_code(400);
            echo json_encode(['message' => 'Table number and capacity are required']);
            exit;
        }

        $table = $manager->addTable($tableNumber, $capacity, $floor_id);
        echo json_encode($table);
    }
    
    // PUT: Update table
    else if ($method === 'PUT') {
        $tableNumber = $input['tableNumber'] ?? null;
        $capacity = $input['capacity'] ?? null;

        if (!$id || !$tableNumber || !$capacity) {
            http_response_code(400);
            echo json_encode(['message' => 'ID, table number, and capacity are required']);
            exit;
        }

        $manager->updateTable($id, $tableNumber, $capacity);
        echo json_encode(['success' => true]);
    }
    
    // DELETE: Remove table
    else if ($method === 'DELETE') {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['message' => 'ID is required']);
            exit;
        }

        $manager->deleteTable($id);
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
