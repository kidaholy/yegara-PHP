<?php
/**
 * Lightweight bootstrap for cashier POS — matches admin standard menu filtering.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/JsonDB.php';
require_once __DIR__ . '/../../includes/SettingsManager.php';
require_once __DIR__ . '/../../includes/menu-tiers.php';

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
    $tierId = trim($_GET['tier'] ?? '');
    if ($tierId !== '') {
        $tier = getMenuTierById($tierId);
        if ($tier) {
            $collection = getMenuTierCollection($tier);
        }
    }
    if (!isAllowedMenuCollection($collection)) {
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
        // Allow dynamic main categories (e.g. Services, Laundry, etc.)
        if (empty($mainCategory)) {
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

    $hotelRooms = array_values(array_filter(
        db('rooms')->findMany(['where' => ['isDeleted' => false]]),
        fn($r) => ($r['isActive'] ?? true) !== false && trim($r['roomNumber'] ?? '') !== ''
    ));
    usort($hotelRooms, fn($a, $b) => strnatcasecmp((string)($a['roomNumber'] ?? ''), (string)($b['roomNumber'] ?? '')));

    $floorLabelMap = [];
    foreach ($floors as $floor) {
        $floorLabelMap[$floor['id'] ?? ''] = 'FLOOR #' . strtoupper($floor['floorNumber'] ?? '');
    }

    $checkedInGuests = array_values(array_filter(
        db('receptionRequests')->findMany(['where' => ['isDeleted' => false]]),
        fn($g) => ($g['status'] ?? '') === 'CHECKIN_APPROVED' && trim((string)($g['roomNumber'] ?? '')) !== ''
    ));

    $guestByRoom = [];
    foreach ($checkedInGuests as $guest) {
        $key = ltrim((string)($guest['roomNumber'] ?? ''), '0') ?: '0';
        $guestByRoom[$key] = $guest;
        $guestByRoom[(string)($guest['roomNumber'] ?? '')] = $guest;
    }

    $rooms = [];
    foreach ($hotelRooms as $room) {
        $roomNum = (string)$room['roomNumber'];
        $guest = $guestByRoom[$roomNum] ?? $guestByRoom[ltrim($roomNum, '0') ?: '0'] ?? null;
        if (!$guest) continue;

        $floorId = $room['floorId'] ?? '';
        $rooms[] = [
            'id' => $room['id'],
            'roomNumber' => $roomNum,
            'floorId' => $floorId,
            'floorLabel' => $floorLabelMap[$floorId] ?? '',
            'category' => $room['category'] ?? '',
            'guestName' => $guest['guestName'] ?? 'Guest',
            'guestId' => $guest['id'] ?? '',
            'checkIn' => $guest['checkIn'] ?? null,
            'checkOut' => $guest['checkOut'] ?? null,
        ];
    }
    usort($rooms, fn($a, $b) => strnatcasecmp($a['roomNumber'], $b['roomNumber']));

    $tierMeta = null;
    if ($tierId !== '') {
        $tier = getMenuTierById($tierId);
        if ($tier) {
            $tierMeta = [
                'id' => $tier['id'],
                'name' => $tier['name'],
                'percentage' => $tier['percentage'],
            ];
        }
    }

    sendJson([
        'collection' => $collection,
        'menuTier' => $tierMeta,
        'items' => $slimItems,
        'categories' => $categories,
        'distributions' => $distributions,
        'floorPlan' => $floorPlan,
        'rooms' => $rooms,
        'tables' => $tables,
        'branding' => [
            'app_name' => $branding['app_name'] ?? 'ABE HOTEL',
            'app_tagline' => $branding['app_tagline'] ?? '',
        ],
        'configuration' => [
            'enable_cashier_printing' => $manager->getSetting('configuration', 'enable_cashier_printing') ?? true,
        ],
    ]);
} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
