<?php
/**
 * System Initialization: Schema Creation
 */
require_once 'includes/db_config.php';

try {
    $db = getDB();

    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'receptionist', 'cashier', 'chef', 'bar') NOT NULL,
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            isDeleted BOOLEAN DEFAULT FALSE
        )",
        "CREATE TABLE IF NOT EXISTS rooms (
            id VARCHAR(36) PRIMARY KEY,
            roomNumber VARCHAR(10) UNIQUE NOT NULL,
            tier VARCHAR(50),
            price DECIMAL(10, 2),
            status ENUM('available', 'occupied', 'cleaning', 'maintenance') DEFAULT 'available',
            floor INT,
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS reception_requests (
            id VARCHAR(36) PRIMARY KEY,
            guestName VARCHAR(100) NOT NULL,
            faydaId VARCHAR(16) NOT NULL,
            roomNumber VARCHAR(10),
            checkIn DATETIME,
            stayDays INT,
            status ENUM('staying', 'checked-out', 'cancelled') DEFAULT 'staying',
            idFront VARCHAR(255),
            idBack VARCHAR(255),
            profilePhoto VARCHAR(255),
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            isDeleted BOOLEAN DEFAULT FALSE
        )",
        "CREATE TABLE IF NOT EXISTS menu_categories (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50),
            description TEXT,
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS stocks (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            quantity DECIMAL(10, 2) DEFAULT 0,
            unit VARCHAR(20),
            totalConsumed DECIMAL(10, 2) DEFAULT 0,
            minThreshold DECIMAL(10, 2) DEFAULT 0,
            updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS menu_items (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            categoryId VARCHAR(36),
            stockItemId VARCHAR(36),
            stockConsumption DECIMAL(10, 2) DEFAULT 1,
            mainCategory VARCHAR(50) DEFAULT 'Food',
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoryId) REFERENCES menu_categories(id),
            FOREIGN KEY (stockItemId) REFERENCES stocks(id)
        )",
        "CREATE TABLE IF NOT EXISTS orders (
            id VARCHAR(36) PRIMARY KEY,
            orderNumber VARCHAR(20) UNIQUE NOT NULL,
            tableNumber VARCHAR(50),
            paymentMethod VARCHAR(50),
            totalAmount DECIMAL(10, 2),
            status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
            cashierId VARCHAR(36),
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            isDeleted BOOLEAN DEFAULT FALSE
        )",
        "CREATE TABLE IF NOT EXISTS order_items (
            id VARCHAR(36) PRIMARY KEY,
            orderId VARCHAR(36) NOT NULL,
            menuItemId VARCHAR(36),
            name VARCHAR(100),
            quantity INT,
            price DECIMAL(10, 2),
            notes TEXT,
            mainCategory VARCHAR(50),
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            isDeleted BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (orderId) REFERENCES orders(id)
        )",
        "CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(50) PRIMARY KEY,
            `value` TEXT,
            updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];

    foreach ($queries as $query) {
        $db->exec($query);
    }

    echo "Schema initialized successfully.\n";

} catch (PDOException $e) {
    die("Initialization failed: " . $e->getMessage());
}
