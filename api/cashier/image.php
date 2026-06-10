<?php
/**
 * Serve a single menu item image — lazy-loaded by cashier (20 per page).
 */
require_once __DIR__ . '/../../includes/auth.php';

if (!isAuthenticated()) {
    http_response_code(401);
    exit;
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['cashier', 'admin'], true)) {
    http_response_code(403);
    exit;
}

$id = $_GET['id'] ?? '';
if ($id === '') {
    http_response_code(400);
    exit;
}

$item = db('menuItems')->findUnique(['where' => ['id' => $id]]);
if (!$item || ($item['isDeleted'] ?? false)) {
    http_response_code(404);
    exit;
}

$image = trim($item['image'] ?? '');
if ($image === '') {
    http_response_code(404);
    exit;
}

// External URL — redirect
if (preg_match('#^https?://#i', $image)) {
    header('Location: ' . $image, true, 302);
    exit;
}

// Data URI (base64 embedded in menuItems.json)
if (preg_match('#^data:(image/[^;]+);base64,(.+)$#s', $image, $m)) {
    $binary = base64_decode($m[2], true);
    if ($binary === false) {
        http_response_code(500);
        exit;
    }
    header('Content-Type: ' . $m[1]);
    header('Cache-Control: public, max-age=604800');
    header('Content-Length: ' . strlen($binary));
    echo $binary;
    exit;
}

// Relative file path under project
$baseDir = dirname(__DIR__, 2);
$path = $image;
if ($path[0] === '/') {
    $full = $baseDir . $path;
} else {
    $full = $baseDir . '/' . ltrim($path, '/');
}

if (is_file($full)) {
    $mime = mime_content_type($full) ?: 'image/jpeg';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=604800');
    readfile($full);
    exit;
}

http_response_code(404);
