-- ============================================================
-- Metro POS & Communication System - Database Backup
-- Database: `comm_bookshop_pos`
-- Generated: 2026-10-17 08:27:46
-- Tables Count: 9
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 1;
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Table structure for `activity_logs`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `activity_logs` (8 rows)
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `details`, `ip_address`, `created_at`) VALUES
  ('1', '3', 'Cleared All Data', 'System', 'Cleared all operational data. User accounts and store settings were preserved. Safety snapshot: safety_snapshot_before_clear_data_2026-10-17_075128.sql', '::1', '2026-10-17 11:21:29'),
  ('3', '1', 'User Login', 'Auth', 'User admin logged in successfully', '::1', '2026-10-17 11:28:17'),
  ('4', '1', 'User Login', 'Auth', 'User admin logged in successfully', '::1', '2026-10-17 11:28:39'),
  ('5', '1', 'User Login', 'Auth', 'User admin logged in successfully', '::1', '2026-10-17 11:34:39'),
  ('6', '1', 'User Login', 'Auth', 'User admin logged in successfully', '::1', '2026-10-17 11:43:16'),
  ('7', '1', 'User Login', 'Auth', 'User admin logged in successfully', '::1', '2026-10-17 11:53:23'),
  ('8', '1', 'Restored Database', 'Backup', 'Restored database from stored backup: starter_catalog_default.sql. Executed: 13 statements. Safety snapshot: safety_snapshot_before_restore_2026-10-17_082323.sql', '::1', '2026-10-17 11:53:23'),
  ('9', '1', 'User Login', 'Auth', 'User admin logged in successfully', '::1', '2026-10-17 11:57:46');

-- ------------------------------------------------------------
-- Table structure for `categories`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('goods','service') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'goods',
  `icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'bi-tag',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `categories` (10 rows)
INSERT INTO `categories` (`id`, `name`, `type`, `icon`, `created_at`) VALUES
  ('1', 'Books & Magazines', 'goods', 'bi-book', '2026-10-17 11:38:39'),
  ('2', 'Stationery & Pens', 'goods', 'bi-pencil', '2026-10-17 11:38:39'),
  ('3', 'Books & Novels', 'goods', 'bi-book', '2026-10-17 11:41:19'),
  ('4', 'Exercise Books & Notebooks', 'goods', 'bi-journal-bookmark', '2026-10-17 11:41:19'),
  ('5', 'Stationery & Writing', 'goods', 'bi-pen', '2026-10-17 11:41:19'),
  ('6', 'Office & School Supplies', 'goods', 'bi-briefcase', '2026-10-17 11:41:19'),
  ('7', 'Art & Craft Materials', 'goods', 'bi-palette', '2026-10-17 11:41:19'),
  ('8', 'Printing & Photocopy Services', 'service', 'bi-printer', '2026-10-17 11:41:19'),
  ('9', 'Lamination & Binding', 'service', 'bi-shield-shaded', '2026-10-17 11:41:19'),
  ('17', 'Mobile Reload & Telecom Services', 'service', 'bi-phone', '2026-10-17 11:42:20');

-- ------------------------------------------------------------
-- Table structure for `invoice_items`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE `invoice_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_type` enum('physical','service','reload') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'physical',
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` int NOT NULL DEFAULT '1',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table structure for `invoices`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `customer_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Walk-in Customer',
  `customer_phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_type` enum('percentage','fixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'fixed',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('cash','card','transfer','qr') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'cash',
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `change_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('completed','voided','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `invoice_no_2` (`invoice_no`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table structure for `products`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `barcode` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `item_type` enum('physical','service','reload') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'physical',
  `cost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_qty` int NOT NULL DEFAULT '0',
  `min_stock_alert` int NOT NULL DEFAULT '5',
  `unit` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pcs',
  `supplier_id` int DEFAULT NULL,
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  UNIQUE KEY `sku` (`sku`),
  KEY `barcode_2` (`barcode`),
  KEY `name` (`name`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `products` (25 rows)
INSERT INTO `products` (`id`, `barcode`, `sku`, `name`, `category_id`, `item_type`, `cost_price`, `selling_price`, `stock_qty`, `min_stock_alert`, `unit`, `supplier_id`, `status`, `created_at`, `updated_at`) VALUES
  ('1', '9789551234567', 'BK-ATLAS-CR80', 'Atlas CR 80 Pages Single Rule Exercise Book', '4', 'physical', '120.00', '160.00', '75', '15', 'pcs', '2', 'active', '2026-10-17 11:38:39', '2026-10-17 11:42:20'),
  ('2', 'SRV-XER-BW', 'SRV-XER-BW', 'B&W Photocopy (Single Side)', '8', 'service', '2.50', '10.00', '99999', '0', 'pages', '3', 'active', '2026-10-17 11:38:39', '2026-10-17 11:42:20'),
  ('4', 'SRV-XER-DS', 'SRV-XER-DS', 'B&W Photocopy (Double Side)', '8', 'service', '4.00', '15.00', '99999', '0', 'pages', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('5', 'SRV-PRT-CLR', 'SRV-PRT-CLR', 'Color Printout High Quality A4', '8', 'service', '12.00', '40.00', '99999', '0', 'pages', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('6', 'SRV-LAM-A4', 'SRV-LAM-A4', 'A4 Thermal Document Lamination', '9', 'service', '35.00', '100.00', '99999', '0', 'doc', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('7', 'SRV-BIND-SP', 'SRV-BIND-SP', 'Spiral Document Binding (Up to 100 pages)', '9', 'service', '60.00', '180.00', '99999', '0', 'doc', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('8', 'SRV-SCAN-PDF', 'SRV-SCAN-PDF', 'Document Scanning & Email (Per Page)', '8', 'service', '0.00', '30.00', '99999', '0', 'pages', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('9', 'TEL-DIA-REL', 'TEL-DIA-REL', 'Dialog Mobile Reload / DTV Bill Payment', '17', 'reload', '95.00', '100.00', '99999', '0', 'unit', '5', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('10', 'TEL-MOB-REL', 'TEL-MOB-REL', 'Mobitel Mobile Reload / Bill Payment', '17', 'reload', '95.00', '100.00', '99999', '0', 'unit', '5', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('12', '9789551234028', 'BK-ATLAS-CR120', 'Atlas CR 120 Pages Square Rule Book', '4', 'physical', '170.00', '220.00', '60', '10', 'pcs', '2', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('13', '9789551234035', 'BK-ATLAS-CR200', 'Atlas CR 200 Pages Single Rule Hardcover Book', '4', 'physical', '260.00', '340.00', '45', '8', 'pcs', '2', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('14', '9789551234042', 'BK-DRAW-A4', 'Atlas A4 Drawing Book 40 Pages', '7', 'physical', '110.00', '150.00', '40', '10', 'pcs', '2', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('15', '4792011001015', 'PEN-ATLAS-CHOC-BLU', 'Atlas Choc Ballpoint Pen 0.7mm (Blue)', '5', 'physical', '22.00', '35.00', '250', '50', 'pcs', '2', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('16', '4792011001022', 'PEN-ATLAS-CHOC-RED', 'Atlas Choc Ballpoint Pen 0.7mm (Red)', '5', 'physical', '22.00', '35.00', '180', '30', 'pcs', '2', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('17', '4792011001039', 'PEN-ATLAS-CHOC-BLK', 'Atlas Choc Ballpoint Pen 0.7mm (Black)', '5', 'physical', '22.00', '35.00', '150', '30', 'pcs', '2', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('18', '8901234567890', 'PEN-PILOT-G2', 'Pilot G2 0.7mm Retractable Gel Ink Pen (Black)', '5', 'physical', '280.00', '380.00', '35', '5', 'pcs', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('19', '4792011002012', 'PENCIL-NATARAJ-10', 'Nataraj 2B Velvet Writing Pencils (Pack of 10)', '5', 'physical', '140.00', '200.00', '50', '10', 'pack', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('20', '4792011003019', 'ERA-PELIKAN-DF', 'Pelikan Dust-Free High Grade Vinyl Eraser', '5', 'physical', '30.00', '50.00', '90', '20', 'pcs', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('21', '4792011004016', 'RUL-MAPED-30CM', 'Maped 30cm Shatterproof Transparent Ruler', '6', 'physical', '55.00', '90.00', '65', '10', 'pcs', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('22', '8901030012345', 'GLUE-FEVI-50G', 'Fevicol MR Synthetic Craft Adhesive Glue 50g', '7', 'physical', '75.00', '110.00', '40', '8', 'bottle', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('23', '8901030098765', 'SCIS-MAPED-13', 'Maped Student Safety Scissors 13cm', '6', 'physical', '120.00', '180.00', '30', '5', 'pcs', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('24', '8902001004561', 'PAP-DOUBLE-A4', 'Double A A4 80gsm Premium Copier Paper Ream (500 Sheets)', '6', 'physical', '1650.00', '1950.00', '25', '5', 'ream', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('25', '4791001005555', 'STAP-KANG-10', 'Kangaro No. 10 Office Stapler with 1000 Pins', '6', 'physical', '320.00', '450.00', '20', '4', 'set', '3', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('26', '9789552119999', 'BK-SIN-ENG-DIC', 'Gunasena Sinhala-English Pocket Comprehensive Dictionary', '3', 'physical', '650.00', '850.00', '15', '3', 'copy', '4', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20'),
  ('27', '9789556520011', 'BK-MADOL-DOOVA', 'Madol Doova (Original Sinhala Classic by Martin Wickramasinghe)', '3', 'physical', '350.00', '450.00', '20', '4', 'copy', '4', 'active', '2026-10-17 11:42:20', '2026-10-17 11:42:20');

-- ------------------------------------------------------------
-- Table structure for `settings`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `settings` (8 rows)
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('currency_symbol', 'Rs.'),
  ('receipt_footer', 'Thank you for your patronage! Please retain this receipt for returns/warranty within 7 days.'),
  ('store_address', 'No. 45, Main Street, Colombo 03, Sri Lanka'),
  ('store_email', 'metrobooks.comm@gmail.com'),
  ('store_name', 'Communication & Bookshop'),
  ('store_phone', '+94 11 234 5678 / +94 77 123 4567'),
  ('store_tagline', 'Books, Stationery, Telecom Services & Xerox'),
  ('tax_percentage', '0');

-- ------------------------------------------------------------
-- Table structure for `stock_audits`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `stock_audits`;
CREATE TABLE `stock_audits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `audit_type` enum('stock_in','damage','physical_audit','sale','void_return','price_change') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_qty` int NOT NULL,
  `new_qty` int NOT NULL,
  `qty_change` int NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_audits_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table structure for `suppliers`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `suppliers` (5 rows)
INSERT INTO `suppliers` (`id`, `name`, `company`, `phone`, `email`, `address`, `created_at`) VALUES
  ('1', 'Atlas Axillia', 'Atlas Axillia PLC', '0112345678', 'sales@atlas.lk', 'Peliyagoda', '2026-10-17 11:38:39'),
  ('2', 'Atlas Axillia PLC', 'Atlas Axillia PLC Sri Lanka', '+94 11 294 6200', 'sales@atlas.lk', 'No 96, Parakrama Road, Peliyagoda', '2026-10-17 11:42:20'),
  ('3', 'Richard Pieris & Co (Arpico)', 'Richard Pieris Distributors Ltd', '+94 11 431 0500', 'stationery@arpico.com', '310 High Level Road, Nawinna, Maharagama', '2026-10-17 11:42:20'),
  ('4', 'Sarasavi Bookshop & Publishers', 'Sarasavi Bookshop (Pvt) Ltd', '+94 11 282 0830', 'distribution@sarasavi.lk', 'No. 20, Stanley Thilakarathne Mawatha, Nugegoda', '2026-10-17 11:42:20'),
  ('5', 'Dialog & Mobitel Telecom Distributor', 'Metropolitan Telecom Services', '+94 77 767 8678', 'telecom.pos@dialog.lk', '475 Union Place, Colombo 02', '2026-10-17 11:42:20');

-- ------------------------------------------------------------
-- Table structure for `users`
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','cashier') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'cashier',
  `status` enum('active','inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `isView` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `users` (3 rows)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `status`, `isView`, `created_at`) VALUES
  ('1', 'admin', '$2y$10$OHRWC4b1/EdF5ob.IL.9Ze95JPPNd35YulGNr44UZ8msbmF/r43JS', 'System Administrator', 'admin', 'active', '1', '2026-10-09 13:13:44'),
  ('2', 'cashier', '$2y$10$eNI.OZvVlbVo3MWjuWlka.OJP1GlPNhl8jvwhjE7igrSJK3UjFxTC', 'Counter Cashier', 'cashier', 'active', '1', '2026-10-09 13:13:44'),
  ('3', 'developer', '$2y$10$quHiivtsoUp098bmpXjxg.zErmUYJtL6N9BatdpdrfXkBZyi0wOrS', 'Sanithu', 'admin', 'active', '0', '2026-10-09 20:42:37');

SET FOREIGN_KEY_CHECKS = 1;
-- [End of Database Backup Dump]
