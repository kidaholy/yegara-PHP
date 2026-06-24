<?php
/**
 * CMS API - Handle static website content management
 */
require_once '../includes/auth.php';
require_once '../includes/cms.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    requireAuth(['admin'], ['settings:view', 'settings:update']);
} else {
    requireAuth(['admin'], 'settings:update');
}

// System now uses the db('cms') collection exclusively.

// REMOVED: Automatic reset on missing file to prevent accidental reversion.
// Restoration now requires explicit admin action via the "Load Defaults" button.

if ($method === 'GET') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
    header('Pragma: no-cache'); // HTTP 1.0.
    header('Expires: 0'); // Proxies.
    echo json_encode(getCmsData());
    exit;
}

if ($method === 'POST') {
    $data = getCmsData();

    if (isset($_FILES['image'])) {
        $uploadDir = __DIR__ . '/../assets/cms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
            exit;
        }

        $fileName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            echo json_encode(['success' => true, 'path' => 'assets/cms/' . $fileName]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
        }
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        header('Content-Type: application/json');


        // Reject clearly broken payloads (missing core keys)
        $required = ['hero', 'about', 'services', 'contact', 'social', 'gallery', 'sections'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $input)) {
                echo json_encode(['success' => false, 'message' => "Incomplete save — missing \"$key\" section. Please refresh and try again."]);
                exit;
            }
        }

        $merged = saveCmsPayload($input);
        if (writeCmsData($merged)) {
            echo json_encode(['success' => true, 'data' => $merged]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save data']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
