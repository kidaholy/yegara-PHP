<?php
/**
 * Lightweight bootstrap for cashier POS — matches admin standard menu filtering.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/SettingsManager.php';

header('Content-Type: application/json');

function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function isVipMenuItem(array $item): bool {
    $name = strtolower($item['name'] ?? '');
    $cat = strtolower($item['category'] ?? '');
    return strpos($name, 'vip') !== false
        || strpos($cat, 'vip') !== false
        || ($item['isVIP'] ?? false);
}

if (!isAuthenticated()) {
    sendJson(['message' => 'Unauthorized'], 401);
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['cashier', 'admin'], true)) {
    sendJson(['message' => 'Forbidden'], 403);
}

try {
    $collection = $_GET['collection'] ?? 'menuItems';
    if (!in_array($collection, ['menuItems', 'vip1Menu', 'vip2Menu'], true)) {
        $collection = 'menuItems';
    }

    $stocks = db('stocks')->findMany([]);
    $finishedStockIds = array_map(
        fn($s) => $s['id'],
        array_filter($stocks, fn($s) =>
            ($s['status'] ?? '') === 'finished' ||
            ((isset($s['trackQuantity']) ? $s['trackQuantity'] : true)
                && (float)($s['quantity'] ?? 0) <= 0
                && ($s['status'] ?? '') === 'out_of_stock')
        )
    );

    $items = db($collection)->findMany([
        'where' => ['isDeleted' => false],
        'orderBy' => ['menuId' => 'asc'],
    ]);

    $slimItems = [];

    foreach ($items as $item) {
        if ($collection === 'menuItems' && isVipMenuItem($item)) continue;
        if (($item['available'] ?? true) === false) continue;

        if (!empty($item['stockItemId']) && in_array($item['stockItemId'], $finishedStockIds, true)) {
            continue;
        }

        if (!empty($item['recipe'])) {
            $skip = false;
            foreach ($item['recipe'] as $ing) {
                if (!empty($ing['stockItemId']) && in_array($ing['stockItemId'], $finishedStockIds, true)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;
        }

        $mainCategory = $item['mainCategory'] ?? 'Food';
        if (!in_array($mainCategory, ['Food', 'Drinks'], true)) {
            $mainCategory = 'Food';
        }

        $image = trim($item['image'] ?? '');

        $slimItems[] = [
            'id' => $item['id'],
            'menuId' => (string)($item['menuId'] ?? ''),
            'name' => $item['name'],
            'price' => (float)($item['price'] ?? 0),
            'category' => $item['category'] ?? 'General',
            'mainCategory' => $mainCategory,
            'hasImage' => $image !== '',
            'distributions' => $item['distributions'] ?? [],
        ];
    }

    $categories = db('categories')->findMany([
        'where' => ['type' => 'menu'],
        'orderBy' => ['name' => 'asc'],
    ]);
    $categories = array_values(array_filter($categories, fn($c) => !($c['isDeleted'] ?? false)));
    $categories = array_map(fn($c) => [
        'id' => $c['id'],
        'name' => $c['name'],
    ], $categories);

    $distributions = db('categories')->findMany([
        'where' => ['type' => 'distribution'],
        'orderBy' => ['name' => 'asc'],
    ]);
    $distributions = array_values(array_filter($distributions, fn($c) => !($c['isDeleted'] ?? false)));
    $distributions = array_map(fn($c) => ['id' => $c['id'], 'name' => $c['name']], $distributions);

    $manager = new SettingsManager();
    $branding = $manager->getBranding();

    $floors = $manager->getFloors();
    $allTables = array_values(array_filter($manager->getTables(), fn($t) =>
        ($t['status'] ?? 'active') !== 'inactive' && trim($t['tableNumber'] ?? '') !== ''
    ));

    usort($floors, function ($a, $b) {
        $aNum = strtoupper($a['floorNumber'] ?? '');
        $bNum = strtoupper($b['floorNumber'] ?? '');
        if ($aNum === 'GROUND') return -1;
        if ($bNum === 'GROUND') return 1;
        return strcasecmp($aNum, $bNum);
    });

    $assignedIds = [];
    $floorPlan = [];

    foreach ($floors as $floor) {
        $floorTables = [];
        foreach ($allTables as $table) {
            if (($table['floor_id'] ?? '') !== ($floor['id'] ?? '')) continue;
            $assignedIds[$table['id']] = true;
            $floorTables[] = [
                'id' => $table['id'],
                'tableNumber' => $table['tableNumber'],
                'capacity' => (int)($table['capacity'] ?? 4),
            ];
        }
        usort($floorTables, fn($a, $b) => strnatcasecmp($a['tableNumber'], $b['tableNumber']));
        $floorPlan[] = [
            'id' => $floor['id'],
            'floorNumber' => $floor['floorNumber'],
            'label' => 'FLOOR #' . strtoupper($floor['floorNumber']),
            'tables' => $floorTables,
        ];
    }

    $unassigned = array_values(array_filter($allTables, fn($t) => !isset($assignedIds[$t['id']])));
    if (!empty($unassigned) && !empty($floorPlan)) {
        $groundIdx = 0;
        foreach ($floorPlan as $i => $fp) {
            if (stripos($fp['floorNumber'], 'GROUND') !== false) {
                $groundIdx = $i;
                break;
            }
        }
        foreach ($unassigned as $table) {
            $floorPlan[$groundIdx]['tables'][] = [
                'id' => $table['id'],
                'tableNumber' => $table['tableNumber'],
                'capacity' => (int)($table['capacity'] ?? 4),
            ];
        }
        usort($floorPlan[$groundIdx]['tables'], fn($a, $b) => strnatcasecmp($a['tableNumber'], $b['tableNumber']));
    }

    $tables = array_map(fn($t) => $t['tableNumber'], $allTables);

    sendJson([
        'collection' => $collection,
        'items' => $slimItems,
        'categories' => $categories,
        'distributions' => $distributions,
        'floorPlan' => $floorPlan,
        'tables' => $tables,
        'branding' => [
            'app_name' => $branding['app_name'] ?? 'ABE HOTEL',
            'app_tagline' => $branding['app_tagline'] ?? '',
        ],
    ]);
} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
