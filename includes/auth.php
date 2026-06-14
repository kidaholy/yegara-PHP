<?php
/**
 * Authentication and Session management for PHP
 */

require_once 'config.php';
require_once 'JsonDB.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => SESSION_LIFETIME,
        'cookie_httponly' => true,
        'use_strict_mode' => true,
    ]);
}

/**
 * Super Admin Protection Constants
 */
define('SUPER_ADMIN_ID', '69c45a78ba1175fcacd0abd3');


/**
 * Check if a user is logged in
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Require authentication for a page
 */
function requireAuth($roles = [], $requiredPermission = null) {
    if (!isAuthenticated()) {
        header('Location: /login.php');
        exit;
    }

    if (!empty($roles) || $requiredPermission !== null) {
        $userRole = $_SESSION['role'] ?? '';
        $userPermissions = $_SESSION['permissions'] ?? [];

        // Admin always has access to everything
        if ($userRole === 'admin') {
            return;
        }

        $roleMatch = !empty($roles) && in_array($userRole, $roles);
        
        $permissionMatch = false;
        if ($requiredPermission !== null) {
            $requiredPermissions = is_array($requiredPermission) ? $requiredPermission : [$requiredPermission];
            foreach ($requiredPermissions as $rp) {
                if (in_array($rp, $userPermissions)) {
                    $permissionMatch = true;
                    break;
                }
            }
        }

        if (!$roleMatch && !$permissionMatch) {
            header('Location: /unauthorized.php');
            exit;
        }
    }

    // Refresh user data to check if account is active
    try {
        $user = db('users')->findUnique([
            'where' => ['id' => $_SESSION['user_id']]
        ]);

        if (!$user || (isset($user['isActive']) && $user['isActive'] === false)) {
            logout();
            header('Location: /login.php?error=deactivated');
            exit;
        }
    } catch (Exception $e) {
        // If DB is unreachable, trust session but log error
        error_log("Auth DB check failed: " . $e->getMessage());
    }
}

/**
 * Require authentication for JSON API endpoints (returns JSON instead of redirecting)
 */
function requireApiAuth($roles = [], $requiredPermission = null) {
    header('Content-Type: application/json');

    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    if (!empty($roles) || $requiredPermission !== null) {
        $userRole = $_SESSION['role'] ?? '';
        $userPermissions = $_SESSION['permissions'] ?? [];

        // Admin always has access to everything
        if ($userRole === 'admin') {
            return;
        }

        $roleMatch = !empty($roles) && in_array($userRole, $roles);
        
        $permissionMatch = false;
        if ($requiredPermission !== null) {
            $requiredPermissions = is_array($requiredPermission) ? $requiredPermission : [$requiredPermission];
            foreach ($requiredPermissions as $rp) {
                if (in_array($rp, $userPermissions)) {
                    $permissionMatch = true;
                    break;
                }
            }
        }

        if (!$roleMatch && !$permissionMatch) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
            exit;
        }
    }

    try {
        $user = db('users')->findUnique([
            'where' => ['id' => $_SESSION['user_id']]
        ]);

        if (!$user || (isset($user['isActive']) && $user['isActive'] === false)) {
            logout();
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Account deactivated']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Auth DB check failed: " . $e->getMessage());
    }
}

/**
 * Attempt to log in a user
 */
function login($email, $password) {
    try {
        $user = db('users')->findUnique([
            'where' => ['email' => $email]
        ]);

        if ($user && password_verify($password, $user['password'])) {
            if (isset($user['isActive']) && $user['isActive'] === false) {
                return ['success' => false, 'message' => 'Account deactivated'];
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['permissions'] = is_array($user['permissions'] ?? []) 
                ? ($user['permissions'] ?? []) 
                : (json_decode($user['permissions'] ?? '[]', true) ?: []);
            $_SESSION['floorId'] = $user['floorId'] ?? null;
            
            return ['success' => true, 'user' => $user];
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
    }

    return ['success' => false, 'message' => 'Invalid email or password'];
}

/**
 * Log out the current user
 */
function logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Get the current user data
 */
function getCurrentUser() {
    if (!isAuthenticated()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'permissions' => $_SESSION['permissions'] ?? [],
        'floorId' => $_SESSION['floorId'] ?? null
    ];
}

/**
 * Check if the current user has a specific permission
 */
function hasPermission($permission) {
    if (!isAuthenticated()) return false;
    if (($_SESSION['role'] ?? '') === 'admin') return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        $permissions = is_string($permissions) ? (json_decode($permissions, true) ?: []) : [];
    }
    return in_array($permission, $permissions);
}

/**
 * Check if the current user or a specific ID is the protected Super Admin
 */
function isSuperAdmin($userId = null) {
    if ($userId === null) {
        if (!isAuthenticated()) return false;
        $userId = $_SESSION['user_id'];
    }
    return $userId === SUPER_ADMIN_ID;
}


/**
 * Check if the current user has any permission matching a pattern (e.g. 'reports:*')
 */
function hasPermissionPattern($pattern) {
    if (!isAuthenticated()) return false;
    if (($_SESSION['role'] ?? '') === 'admin') return true;
    $permissions = $_SESSION['permissions'] ?? [];
    if (!is_array($permissions)) {
        $permissions = is_string($permissions) ? (json_decode($permissions, true) ?: []) : [];
    }
    return !empty(preg_grep($pattern, $permissions));
}
