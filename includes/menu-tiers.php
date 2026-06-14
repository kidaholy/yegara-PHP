<?php
/**
 * Dynamic VIP / premium menu tier helpers
 */

require_once __DIR__ . '/JsonDB.php';

function getMenuTiers(): array {
    try {
        $tiers = db('menuTiers')->findMany(['where' => ['isDeleted' => false]]);
    } catch (Exception $e) {
        return [];
    }
    usort($tiers, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
    return $tiers;
}

function getMenuTierById(string $id): ?array {
    if ($id === '') {
        return null;
    }
    try {
        return db('menuTiers')->findFirst(['where' => ['id' => $id, 'isDeleted' => false]]);
    } catch (Exception $e) {
        return null;
    }
}

function getMenuTierCollection(array $tier): string {
    return ($tier['filePrefix'] ?? '') . 'Menu';
}

function getAllMenuCollections(): array {
    $collections = ['menuItems'];
    foreach (getMenuTiers() as $tier) {
        $collections[] = getMenuTierCollection($tier);
    }
    return array_values(array_unique($collections));
}

function isAllowedMenuCollection(string $collection): bool {
    if ($collection === 'menuItems') {
        return true;
    }
    foreach (getMenuTiers() as $tier) {
        if ($collection === getMenuTierCollection($tier)) {
            return true;
        }
    }
    return false;
}

function findMenuItemInCollections(string $menuItemId, ?array $tier = null): ?array {
    if ($tier) {
        $collection = getMenuTierCollection($tier);
        return db($collection)->findUnique(['where' => ['id' => $menuItemId]]) ?: null;
    }

    $item = db('menuItems')->findUnique(['where' => ['id' => $menuItemId]]);
    if ($item) {
        return $item;
    }

    foreach (getMenuTiers() as $t) {
        $item = db(getMenuTierCollection($t))->findUnique(['where' => ['id' => $menuItemId]]);
        if ($item) {
            return $item;
        }
    }

    return null;
}

function buildTierMenuFromStandard(float $percentage, string $tierId): array {
    $standardItems = db('menuItems')->findMany(['where' => ['isDeleted' => false]]);
    $multiplier = 1 + ($percentage / 100);
    $items = [];

    foreach ($standardItems as $item) {
        $name = strtolower($item['name'] ?? '');
        $cat = strtolower($item['category'] ?? '');
        if (strpos($name, 'vip') !== false || strpos($cat, 'vip') !== false || ($item['isVIP'] ?? false)) {
            continue;
        }

        $newItem = $item;
        $newItem['price'] = round((float)($item['price'] ?? 0) * $multiplier, 2);
        $newItem['tier'] = $tierId;
        $newItem['menuTierId'] = $tierId;
        $items[] = $newItem;
    }

    return $items;
}

function writeTierMenuFile(array $tier, array $items): void {
    $collection = getMenuTierCollection($tier);
    // Delete existing to replace
    db($collection)->deleteMany(['where' => ['id' => ['not' => '']]]);
    foreach ($items as $item) {
        db($collection)->create(['data' => $item]);
    }
}

function deleteTierMenuFile(array $tier): void {
    $collection = getMenuTierCollection($tier);
    // In SQLite, we can just drop the table or delete all records
    // Dropping tables dynamically might be risky, so we'll just clear it
    try {
        db($collection)->deleteMany(['where' => ['id' => ['not' => '']]]);
    } catch (Exception $e) {
        // Table might not exist or already be gone
    }
}
