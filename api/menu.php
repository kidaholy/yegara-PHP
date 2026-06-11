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
    $collection = $_GET['collection'] ?? 'menuItems';
    require_once '../includes/menu-tiers.php';
    if (!isAllowedMenuCollection($collection)) $collection = 'menuItems';

    $stocks = db('stocks')->findMany([]);
    $finishedStockIds = array_map(fn($s) => $s['id'], array_filter($stocks, fn($s) => 
        ($s['status'] ?? '') === 'finished' || 
        ((isset($s['trackQuantity']) ? $s['trackQuantity'] : true) && (float)($s['quantity'] ?? 0) <= 0 && ($s['status'] ?? '') === 'out_of_stock')
    ));

    if ($type === 'categories') {
        $categories = db('menuCategories')->findMany(['orderBy' => ['name' => 'asc']]);
        sendJson($categories);
    }

    $filterItems = function($items) use ($finishedStockIds) {
        return array_values(array_filter($items, function($item) use ($finishedStockIds) {
            if ($item['isDeleted'] ?? false) return false;
            
            // Legacy link check
            if (!empty($item['stockItemId']) && in_array($item['stockItemId'], $finishedStockIds)) {
                return false;
            }
            
            // Recipe check: If any ingredient is finished, hide the item
            if (!empty($item['recipe'])) {
                foreach ($item['recipe'] as $ing) {
                    if (!empty($ing['stockItemId']) && in_array($ing['stockItemId'], $finishedStockIds)) {
                        return false;
                    }
                }
            }
            
            return true;
        }));
    };

    if ($type === 'items') {
        $categoryId = $_GET['categoryId'] ?? null;
        $where = ['isDeleted' => false];
        if ($categoryId) $where['categoryId'] = $categoryId;
        
        $items = db($collection)->findMany(['where' => $where, 'orderBy' => ['name' => 'asc']]);
        sendJson($filterItems($items));
    }

    // Default: return both
    $categories = db('menuCategories')->findMany(['orderBy' => ['name' => 'asc']]);
    $items = db($collection)->findMany(['where' => ['isDeleted' => false], 'orderBy' => ['name' => 'asc']]);
    
    sendJson([
        'categories' => $categories,
        'items' => $filterItems($items)
    ]);

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
