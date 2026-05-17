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
 * Check if a user is logged in
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Require authentication for a page
 */
function requireAuth($roles = []) {
    if (!isAuthenticated()) {
        header('Location: /login.php');
        exit;
    }

    if (!empty($roles)) {
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
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
        'floorId' => $_SESSION['floorId'] ?? null
    ];
}
