<?php
/**
 * API for Dynamic Menu Tiers Management
 */
header('Content-Type: application/json');
require_once '../../includes/auth.php';
require_once '../../includes/menu-tiers.php';

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
    $db = db('menuTiers');

    if ($method === 'GET') {
        $tiers = getMenuTiers();
        sendJson(['status' => 'success', 'data' => $tiers]);
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['name']) || !isset($input['percentage'])) {
            throw new Exception('Name and percentage increase are required');
        }

        $name = trim($input['name']);
        if ($name === '') {
            throw new Exception('Tier name is required');
        }

        $percentage = (float)$input['percentage'];
        if ($percentage <= 0) {
            throw new Exception('Percentage must be greater than zero');
        }

        $id = bin2hex(random_bytes(8));
        $filePrefix = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . $id;

        $tier = [
            'id' => $id,
            'name' => $name,
            'percentage' => $percentage,
            'filePrefix' => $filePrefix,
            'createdAt' => date('c'),
        ];
        $db->create(['data' => $tier]);

        $items = buildTierMenuFromStandard($percentage, $id);
        writeTierMenuFile($tier, $items);

        sendJson(['status' => 'success', 'data' => $tier]);
    }

    if ($method === 'PUT') {
        $id = $_GET['id'] ?? '';
        if ($id === '') {
            throw new Exception('ID required');
        }

        $tier = getMenuTierById($id);
        if (!$tier) {
            throw new Exception('Tier not found');
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim($input['name'] ?? $tier['name']);
        $percentage = isset($input['percentage']) ? (float)$input['percentage'] : (float)$tier['percentage'];

        if ($name === '') {
            throw new Exception('Tier name is required');
        }
        if ($percentage <= 0) {
            throw new Exception('Percentage must be greater than zero');
        }

        $updated = $db->update([
            'where' => ['id' => $id],
            'data' => [
                'name' => $name,
                'percentage' => $percentage,
            ],
        ]);

        if (isset($input['percentage'])) {
            $items = buildTierMenuFromStandard($percentage, $id);
            writeTierMenuFile($updated, $items);
        }

        sendJson(['status' => 'success', 'data' => $updated]);
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? '';
        if ($id === '') {
            throw new Exception('ID required');
        }

        $tier = getMenuTierById($id);
        if (!$tier) {
            throw new Exception('Tier not found');
        }

        $db->delete(['where' => ['id' => $id]]);
        deleteTierMenuFile($tier);

        sendJson(['status' => 'success']);
    }

    sendJson(['message' => 'Method Not Allowed'], 405);
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
