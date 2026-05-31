<?php
/**
 * API Endpoint for Users
 * Handles Staff Management CRUD
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

$currentUser = getCurrentUser();
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$ROOT_ADMIN_EMAIL = 'kidayos2014@gmail.com';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $id = $_GET['id'] ?? null;

    // --- GET (List or Single) ---
    if ($method === 'GET') {
        if ($id) {
            $user = db('users')->findUnique(['where' => ['id' => $id]]);
            if (!$user) sendJson(['message' => 'User not found'], 404);
            unset($user['password']); // Never leak hash
            sendJson($user);
        } else {
            $users = db('users')->findMany(['where' => ['isDeleted' => false]]);
            foreach ($users as &$u) {
                unset($u['password']);
            }
            sendJson($users);
        }
    }

    // --- POST (Create) ---
    if ($method === 'POST') {
        if (!$isAdmin) sendJson(['message' => 'Admin only'], 403);
        $input = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($input['name']) || empty($input['email']) || empty($input['password']) || empty($input['role'])) {
            sendJson(['message' => 'Missing required fields'], 400);
        }

        // Check Unique Email
        $exists = db('users')->findUnique(['where' => ['email' => $input['email']]]);
        if ($exists) sendJson(['message' => 'Email already exists'], 400);

        $plain = $input['password'];
        $hashed = password_hash($plain, PASSWORD_BCRYPT);

        $newUser = db('users')->create(['data' => [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $hashed,
            'plainPassword' => $plain,
            'role' => $input['role'],
            'permissions' => ($input['role'] === 'custom') ? ($input['permissions'] ?? []) : null,
            'isActive' => true,
            'floorId' => $input['floorId'] ?? null,
            'assignedCategories' => in_array($input['role'], ['chef', 'bar']) ? ($input['assignedCategories'] ?? []) : null,
            'canManageReception' => $input['canManageReception'] ?? false
        ]]);

        sendJson([
            'message' => 'User created successfully',
            'user' => $newUser,
            'credentials' => ['email' => $newUser['email'], 'password' => $plain]
        ]);
    }

    // --- PUT (Update) ---
    if ($method === 'PUT') {
        if (!$id) sendJson(['message' => 'ID required'], 400);
        if (!$isAdmin) sendJson(['message' => 'Admin only'], 403);
        $input = json_decode(file_get_contents('php://input'), true);

        $target = db('users')->findUnique(['where' => ['id' => $id]]);
        if (!$target) sendJson(['message' => 'User not found'], 404);

        // Protections
        if ($target['email'] === $ROOT_ADMIN_EMAIL && $currentUser['email'] !== $ROOT_ADMIN_EMAIL) {
            sendJson(['message' => 'Only root admin can modify this account'], 403);
        }

        // Cannot deactivate self
        if (isset($input['isActive']) && $input['isActive'] === false && $target['id'] === $currentUser['id']) {
            sendJson(['message' => 'Cannot deactivate yourself'], 400);
        }

        $data = [];
        if (isset($input['name'])) $data['name'] = $input['name'];
        if (isset($input['email'])) $data['email'] = $input['email'];
        if (isset($input['role'])) {
            // Cannot demote root admin
            if ($target['email'] === $ROOT_ADMIN_EMAIL && $input['role'] !== 'admin') {
                sendJson(['message' => 'Cannot change role of root admin'], 400);
            }
            $data['role'] = $input['role'];
        }
        if (isset($input['isActive'])) $data['isActive'] = (bool)$input['isActive'];
        if (isset($input['floorId'])) $data['floorId'] = $input['floorId'];
        if (isset($input['assignedCategories'])) $data['assignedCategories'] = $input['assignedCategories'];
        if (isset($input['permissions'])) $data['permissions'] = $input['permissions'];

        if (!empty($input['password'])) {
            $data['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            $data['plainPassword'] = $input['password'];
        }

        $updated = db('users')->update([
            'where' => ['id' => $id],
            'data' => $data
        ]);

        sendJson(['message' => 'User updated successfully', 'user' => $updated]);
    }

    // --- DELETE ---
    if ($method === 'DELETE') {
        if (!$id) sendJson(['message' => 'ID required'], 400);
        if (!$isAdmin) sendJson(['message' => 'Admin only'], 403);

        $target = db('users')->findUnique(['where' => ['id' => $id]]);
        if (!$target) sendJson(['message' => 'User not found'], 404);

        // Protections
        if ($target['id'] === $currentUser['id']) sendJson(['message' => 'Cannot delete yourself'], 400);
        if ($target['email'] === $ROOT_ADMIN_EMAIL) sendJson(['message' => 'Cannot delete root admin'], 400);

        // Last admin check
        if ($target['role'] === 'admin') {
            $adminCount = db('users')->count(['where' => ['role' => 'admin', 'isDeleted' => false]]);
            if ($adminCount <= 1) sendJson(['message' => 'Cannot delete last admin'], 400);
        }

        // Hard delete
        db('users')->delete(['where' => ['id' => $id]]);
        sendJson(['message' => 'User deleted successfully']);
    }

} catch (Exception $e) {
    sendJson(['message' => $e->getMessage()], 500);
}
