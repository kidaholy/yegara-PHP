<?php
/**
 * Main entry point and router for the PHP Management System
 */
require_once 'includes/auth.php';

// Ensure user is logged in
requireAuth();

$user = getCurrentUser();

// Simple role-based routing
switch ($user['role']) {
    case 'admin':
        header('Location: admin.php');
        break;
    case 'cashier':
        header('Location: cashier.php');
        break;
    case 'chef':
        header('Location: chef.php');
        break;
    case 'bar':
        header('Location: bar.php');
        break;
    case 'reception':
        header('Location: reception.php');
        break;
    default:
        header('Location: unauthorized.php');
        break;
}
exit;
