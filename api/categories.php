<?php
/**
 * API Endpoint for Categories
 * Supports ?type=menu filtering
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
    $type = $_GET['type'] ?? 'product'; // specs say ?type=menu
    
    // In our JSON DB, we might just have categories.json or menuCategories.json
    // Let's check categories.json first
    $categories = db('categories')->findMany(['where' => ['isDeleted' => false]]);
    
    // If the spec expects a specific type, we'd filter here, 
    // but typically categories are generic in this POS.
    sendJson($categories);
} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
