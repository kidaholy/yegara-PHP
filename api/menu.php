<?php
/**
 * API Endpoint to fetch categories and menu items
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
    $type = $_GET['type'] ?? 'all';

    if ($type === 'categories') {
        $categories = db('menu_categories')->findMany(['orderBy' => ['name' => 'asc']]);
        sendJson($categories);
    }

    if ($type === 'items') {
        $categoryId = $_GET['categoryId'] ?? null;
        $where = ['isDeleted' => false];
        if ($categoryId) {
            $where['categoryId'] = $categoryId;
        }
        $items = db('menu_items')->findMany(['where' => $where, 'orderBy' => ['name' => 'asc']]);
        sendJson($items);
    }

    // Default: return both
    $categories = db('menu_categories')->findMany(['orderBy' => ['name' => 'asc']]);
    $items = db('menu_items')->findMany(['where' => ['isDeleted' => false], 'orderBy' => ['name' => 'asc']]);
    
    sendJson([
        'categories' => $categories,
        'items' => $items
    ]);

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
