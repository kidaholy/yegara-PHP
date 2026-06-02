<?php
header('Content-Type: application/json');
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';

header('Content-Type: application/json');
require_once '../../includes/auth.php';
require_once '../../includes/JsonDB.php';

// Admin Only
requireAuth(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $settings = db('settings')->findMany();
        echo json_encode(['status' => 'success', 'data' => $settings]);
    } 
    elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['key'])) {
            throw new Exception("Invalid input. 'key' is required.");
        }

        $key = $input['key'];
        $value = $input['value'] ?? '';
        $type = $input['type'] ?? 'string';
        $description = $input['description'] ?? '';

        $db = db('settings');
        $found = $db->findMany(['where' => ['key' => $key]]);
        $existing = count($found) > 0 ? $found[0] : null;

        if ($existing) {
            $db->update(['id' => $existing['id'], 'data' => [
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'updated_at' => date('Y-m-d H:i:s')
            ]]);
        } else {
            $db->create(['data' => [
                'id' => bin2hex(random_bytes(16)),
                'key' => $key,
                'value' => $value,
                'type' => $type,
                'description' => $description,
                'updated_at' => date('Y-m-d H:i:s')
            ]]);
        }

        echo json_encode(['status' => 'success', 'message' => "Setting '$key' updated."]);
    }
    else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
