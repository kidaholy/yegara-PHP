<?php
/**
 * API for Reception Lifecycle management
 */
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/JsonDB.php';


function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

requireAuth(['admin', 'receptionist']);

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = db('receptionRequests');

    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $request = $db->findUnique(['where' => ['id' => $id]]);
            sendJson(['status' => 'success', 'data' => $request]);
        }
        
        $limit = (int)($_GET['limit'] ?? 500);
        $requests = $db->findMany(['where' => ['isDeleted' => false], 'orderBy' => ['createdAt' => 'desc'], 'take' => $limit]);
        
        // Exclude large image data for list view unless specifically asked
        $minimal = array_map(function($r) {
            unset($r['idPhotoFront'], $r['idPhotoBack']);
            return $r;
        }, $requests);

        sendJson(['status' => 'success', 'data' => $minimal, 'total' => count($minimal)]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? '';
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$id) throw new Exception("ID required");

        $data = [];
        if (isset($input['status'])) $data['status'] = $input['status'];
        if (isset($input['reviewNote'])) $data['reviewNote'] = $input['reviewNote'];
        if (isset($input['checkOut'])) $data['checkOut'] = $input['checkOut'];

        $db->update(['where' => ['id' => $id], 'data' => $data]);
        sendJson(['status' => 'success']);
    }
    elseif ($method === 'DELETE') {
        // Multi-use DELETE
        if (isset($_GET['action']) && $_GET['action'] === 'wipe') {
            requireAuth(['admin']);
            $db->deleteMany(['where' => []]);
            sendJson(['status' => 'success', 'message' => 'All requests cleared']);
        }

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
