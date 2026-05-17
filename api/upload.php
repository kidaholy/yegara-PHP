<?php
/**
 * Secure File Upload API
 */
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$targetDir = "../public/uploads/docs/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['file'] ?? null;
    $type = $_POST['type'] ?? 'doc'; // profile, idFront, idBack

    if (!$file) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }

    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '_' . basename($file["name"]);
    $targetFile = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check file size (max 5MB)
    if ($file["size"] > 5000000) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
        exit;
    }

    // Allow certain file formats
    $allowed = ["jpg", "jpeg", "png", "webp"];
    if (!in_array($fileType, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG & WEBP allowed']);
        exit;
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        echo json_encode(['success' => true, 'path' => '/public/uploads/docs/' . $fileName]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
    exit;
}
