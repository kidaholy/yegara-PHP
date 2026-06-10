<?php
/**
 * Shared order helpers
 */

function isRoomServiceOrder(array $order): bool {
    if (($order['type'] ?? '') === 'room_service') {
        return true;
    }
    $dists = array_map('strtolower', (array)($order['distributions'] ?? []));
    if (in_array('room', $dists, true) || in_array('reception', $dists, true)) {
        return true;
    }
    return str_starts_with(strtoupper($order['orderNumber'] ?? ''), 'RM-');
}

function nextDailyOrderNumber(): string {
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');

    $todayOrders = db('orders')->findMany([
        'where' => [
            'createdAt' => ['gte' => $todayStart, 'lte' => $todayEnd],
        ],
    ]);

    $max = 0;
    foreach ($todayOrders as $order) {
        if (isRoomServiceOrder($order)) {
            continue;
        }
        $num = (int)($order['orderNumber'] ?? 0);
        if ($num > $max) {
            $max = $num;
        }
    }

    return (string)($max + 1);
}
