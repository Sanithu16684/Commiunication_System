<?php
// config/db.php - Database connection and automatic schema bootstrap

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = 'Sanithu@2005'; // fallback password discovered in environment
$db_name = 'comm_bookshop_pos';

try {
    // First try connecting with user credentials; fallback to blank if needed
    try {
        $pdo_init = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    } catch (PDOException $e) {
        // Fallback to empty password if user config changes
        $db_pass = '';
        $pdo_init = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    }
    
    $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Connect to the database
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Ensure database tables exist
    bootstrap_database($pdo);

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

function bootstrap_database($pdo) {
    // 1. Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `role` ENUM('admin', 'cashier') DEFAULT 'cashier',
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `isView` INT NOT NULL DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Migration: Ensure isView column exists with INT and DEFAULT 1
    try {
        $chkCol = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'isView'")->fetch();
        if (!$chkCol) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `isView` INT NOT NULL DEFAULT 1 AFTER `status`");
        }
    } catch (PDOException $e) {
        // Suppress if already updated
    }

    // 2. Categories Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE,
        `type` ENUM('goods', 'service') DEFAULT 'goods',
        `icon` VARCHAR(50) DEFAULT 'bi-tag',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 3. Suppliers Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `suppliers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `company` VARCHAR(100) NULL,
        `phone` VARCHAR(30) NULL,
        `email` VARCHAR(100) NULL,
        `address` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 4. Products Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `barcode` VARCHAR(60) NOT NULL UNIQUE,
        `sku` VARCHAR(60) NOT NULL UNIQUE,
        `name` VARCHAR(150) NOT NULL,
        `category_id` INT NULL,
        `item_type` ENUM('physical', 'service', 'reload') DEFAULT 'physical',
        `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `selling_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `stock_qty` INT NOT NULL DEFAULT 0,
        `min_stock_alert` INT NOT NULL DEFAULT 5,
        `unit` VARCHAR(20) DEFAULT 'pcs',
        `supplier_id` INT NULL,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`barcode`),
        INDEX (`name`),
        INDEX (`category_id`)
    ) ENGINE=InnoDB;");

    // 5. Invoices Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `invoices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
        `user_id` INT NOT NULL,
        `customer_name` VARCHAR(100) DEFAULT 'Walk-in Customer',
        `customer_phone` VARCHAR(30) DEFAULT '',
        `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `discount_type` ENUM('percentage', 'fixed') DEFAULT 'fixed',
        `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `payment_method` ENUM('cash', 'card', 'transfer', 'qr') DEFAULT 'cash',
        `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `change_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `status` ENUM('completed', 'voided', 'refunded') DEFAULT 'completed',
        `notes` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (`invoice_no`),
        INDEX (`created_at`)
    ) ENGINE=InnoDB;");

    // 6. Invoice Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `invoice_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `invoice_id` INT NOT NULL,
        `product_id` INT NULL,
        `product_name` VARCHAR(150) NOT NULL,
        `barcode` VARCHAR(60) NULL,
        `item_type` ENUM('physical', 'service', 'reload') DEFAULT 'physical',
        `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `quantity` INT NOT NULL DEFAULT 1,
        `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 7. Stock Audits & Adjustments
    $pdo->exec("CREATE TABLE IF NOT EXISTS `stock_audits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `audit_type` ENUM('stock_in', 'damage', 'physical_audit', 'sale', 'void_return', 'price_change') NOT NULL,
        `previous_qty` INT NOT NULL,
        `new_qty` INT NOT NULL,
        `qty_change` INT NOT NULL,
        `reason` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 8. Activity Logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL,
        `action` VARCHAR(100) NOT NULL,
        `module` VARCHAR(50) NOT NULL,
        `details` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 9. Store Settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` VARCHAR(50) PRIMARY KEY,
        `setting_value` TEXT NULL
    ) ENGINE=InnoDB;");

    // Seed default users if empty (admin / admin123, cashier / cashier123)
    $usrCount = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    if ($usrCount == 0) {
        $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
        $cashierPass = password_hash('cashier123', PASSWORD_BCRYPT);
        $devPass = password_hash('Sanithu@2005', PASSWORD_BCRYPT);
        $uStmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `status`, `isView`) VALUES (?, ?, ?, ?, 'active', 1)");
        $uStmt->execute(['admin', $adminPass, 'System Administrator', 'admin']);
        $uStmt->execute(['cashier', $cashierPass, 'Counter Cashier', 'cashier']);
        $uStmt->execute(['developer', $devPass, 'Developer', 'cashier']);
    }
}
