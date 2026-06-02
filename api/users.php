<?php
/**
 * API for User/Staff listing (Required for assignments)
 */
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/JsonDB.php';


function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

requireAuth(['admin', 'cashier', 'receptionist']);

try {
    $db = db('users');
    $users = $db->findMany(['where' => ['isDeleted' => false]]);
    
    // Return only necessary fields for assignment
    $minimal = array_map(function($u) {
        return [
            'id' => $u['id'],
            'name' => $u['name'],
            'role' => $u['role']
        ];
    }, $users);

    sendJson(['status' => 'success', 'data' => $minimal]);

} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
