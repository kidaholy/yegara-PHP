<?php
/**
 * API Endpoint for Floors
 */
require_once '../includes/auth.php';

function sendJson($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!isAuthenticated()) {
    sendJson(['message' => 'Unauthorized'], 401);
}

try {
    $floors = db('floors')->findMany(['where' => ['isDeleted' => false]]);
    sendJson($floors);
} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
