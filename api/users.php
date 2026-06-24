<?php
/**
 * API for User/Staff listing
 */
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/JsonDB.php';

function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    requireAuth(['admin', 'cashier', 'receptionist', 'reception'], ['services:view', 'users:view']);
} else {
    requireAuth(['admin'], ['users:create', 'users:update', 'users:delete']);
}

try {
    $db = db('users');

    if ($method === 'GET') {
        $fullMode = isset($_GET['full']) && $_GET['full'] === '1';
        $users = $db->findMany(['where' => ['isDeleted' => false]]);

        if ($fullMode) {
            // Full data for the staff admin grid — exclude hashed password
            $result = [];
            foreach ($users as $u) {
                // HIDE SUPER ADMIN from others (but allow them to see themselves)
                if ($u['id'] === SUPER_ADMIN_ID && !isSuperAdmin()) {
                    continue;
                }
                unset($u['password']);
                $result[] = $u;
            }
            sendJson($result);
        } else {
            // Minimal data for assignment dropdowns
            $minimal = [];
            foreach ($users as $u) {
                if ($u['id'] === SUPER_ADMIN_ID && !isSuperAdmin()) {
                    continue;
                }
                $minimal[] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']];
            }
            sendJson(['status' => 'success', 'data' => $minimal]);
        }
    }

    if ($method === 'POST') {
        requireAuth(['admin'], 'users:create');
        $data = json_decode(file_get_contents('php://input'), true);
        $password = $data['password'] ?? '';
        $hashed = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : '';

        $db->create(['data' => [
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => $hashed,
            'plainPassword'      => $password,
            'role'               => $data['role'] ?? 'cashier',
            'isActive'           => true,
            'floorId'            => $data['floorId'] ?? null,
            'assignedCategories' => $data['assignedCategories'] ?? [],
            'permissions'        => $data['permissions'] ?? [],
        ]]);

        sendJson(['message' => 'User created', 'credentials' => ['email' => $data['email'], 'password' => $password]], 201);
    }

    if ($method === 'PUT') {
        requireAuth(['admin'], 'users:update');
        $id = $_GET['id'] ?? null;
        if (!$id) sendJson(['message' => 'ID required'], 400);
        $data = json_decode(file_get_contents('php://input'), true);

        // If just toggling active status
        if (isset($data['isActive']) && count($data) === 1) {
            // PROTECT SUPER ADMIN from deactivation by OTHERS
            if ($id === SUPER_ADMIN_ID && !isSuperAdmin()) {
                sendJson(['message' => 'Super Admin cannot be deactivated'], 403);
            }
            $db->update(['where' => ['id' => $id], 'data' => ['isActive' => $data['isActive']]]);
            sendJson(['message' => 'Status updated']);
        }

        $update = [];
        if (!empty($data['name']))    $update['name']               = $data['name'];
        if (!empty($data['email']))   $update['email']              = $data['email'];
        if (!empty($data['role']))    $update['role']               = $data['role'];
        if (array_key_exists('floorId', $data)) $update['floorId']  = $data['floorId'];
        if (isset($data['assignedCategories'])) $update['assignedCategories'] = $data['assignedCategories'];
        if (isset($data['permissions']))        $update['permissions']        = $data['permissions'];

        if (!empty($data['password'])) {
            $update['password']      = password_hash($data['password'], PASSWORD_BCRYPT);
            $update['plainPassword'] = $data['password'];
        }

        $db->update(['where' => ['id' => $id], 'data' => $update]);
        sendJson(['message' => 'User updated']);
    }

    if ($method === 'DELETE') {
        requireAuth(['admin'], 'users:delete');
        $id = $_GET['id'] ?? null;
        if (!$id) sendJson(['message' => 'ID required'], 400);

        // PROTECT SUPER ADMIN from deletion
        if ($id === SUPER_ADMIN_ID) {
            sendJson(['message' => 'Super Admin cannot be deleted'], 403);
        }

        $db->update(['where' => ['id' => $id], 'data' => ['isDeleted' => true]]);
        sendJson(['message' => 'User deleted']);
    }

} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
