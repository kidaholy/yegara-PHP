<?php
// api/admin/categories.php

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/SettingsManager.php';
require_once __DIR__ . '/../../includes/auth.php';

$manager = new SettingsManager();

try {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['message' => 'Forbidden']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $type = $_GET['type'] ?? 'menu';
    $id = $_GET['id'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true);

    // GET: Fetch categories by type
    if ($method === 'GET') {
        $categories = $manager->getCategories($type);
        echo json_encode($categories);
    }
    
    // POST: Add category
    else if ($method === 'POST') {
        $name = $input['name'] ?? null;
        $description = $input['description'] ?? '';
        
        if (!$name) {
            http_response_code(400);
            echo json_encode(['message' => 'Name is required']);
            exit;
        }

        $category = $manager->addCategory($type, $name, $description);
        echo json_encode($category);
    }
    
    // PUT: Update category
    else if ($method === 'PUT') {
        $name = $input['name'] ?? null;
        $description = $input['description'] ?? '';
        
        if (!$id || !$name) {
            http_response_code(400);
            echo json_encode(['message' => 'ID and name are required']);
            exit;
        }

        $manager->updateCategory($type, $id, $name, $description);
        echo json_encode(['success' => true]);
    }
    
    // DELETE: Remove category
    else if ($method === 'DELETE') {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['message' => 'ID is required']);
            exit;
        }

        $manager->deleteCategory($type, $id);
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
