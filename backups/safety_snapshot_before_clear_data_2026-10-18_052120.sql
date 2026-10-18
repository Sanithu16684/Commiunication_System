-- ============================================================
-- Metro POS & Communication System - Database Backup
-- Database: `comm_bookshop_pos`
-- Generated: 2026-10-18 05:21:20
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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `activity_logs` (44 rows)
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `details`, `ip_address`, `created_at`) VALUES
  ('1', '1', 'Cleared All Data', 'System', 'Cleared all operational data. User accounts and store settings were preserved. Safety snapshot: safety_snapshot_before_clear_data_2026-10-17_074748.sql', '::1', '2026-10-17 11:17:48'),
  ('2', '1', 'User Login', 'Auth', 'User admin logged in successfully', '::1', '2026-10-17 11:18:14'),
  ('3', '3', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_restore_2026-10-17_065526.sql', '::1', '2026-10-17 11:19:22'),
  ('4', '3', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_clear_data_2026-10-17_074748.sql', '::1', '2026-10-17 11:19:25'),
  ('5', '1', 'User Login', 'Auth', 'User admin logged in successfully', '::1', '2026-10-17 11:20:25'),
  ('6', '1', 'Created Backup', 'Backup', 'Created local backup: backup_comm_bookshop_pos_2026-10-17_075025.sql', '::1', '2026-10-17 11:20:25'),
  ('7', '3', 'Deleted Backup', 'Backup', 'Deleted backup file: backup_comm_bookshop_pos_2026-10-17_065506.sql', '::1', '2026-10-17 11:20:59'),
  ('8', '1', 'Uploaded & Restored Database', 'Backup', 'Restored database from uploaded file: backup_comm_bookshop_pos_2026-10-17_075105.sql. Executed: 26 statements. Safety snapshot: safety_snapshot_before_restore_2026-10-17_161955.sql', '::1', '2026-10-17 19:49:55'),
  ('9', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261013-0001 for amount Rs. 550.00', '::1', '2026-10-18 08:46:46'),
  ('10', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261013-0002 for amount Rs. 350.00', '::1', '2026-10-18 08:46:46'),
  ('11', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261013-0003 for amount Rs. 2,300.00', '::1', '2026-10-18 08:46:46'),
  ('12', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261013-0004 for amount Rs. 605.00', '::1', '2026-10-18 08:46:46'),
  ('13', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261014-0001 for amount Rs. 740.00', '::1', '2026-10-18 08:46:46'),
  ('14', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261014-0002 for amount Rs. 1,250.00', '::1', '2026-10-18 08:46:46'),
  ('15', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261014-0003 for amount Rs. 500.00', '::1', '2026-10-18 08:46:46'),
  ('16', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261014-0004 for amount Rs. 1,380.00', '::1', '2026-10-18 08:46:46'),
  ('17', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261015-0001 for amount Rs. 4,440.00', '::1', '2026-10-18 08:46:46'),
  ('18', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261015-0002 for amount Rs. 480.00', '::1', '2026-10-18 08:46:46'),
  ('19', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261015-0003 for amount Rs. 1,160.00', '::1', '2026-10-18 08:46:46'),
  ('20', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261015-0004 for amount Rs. 2,100.00', '::1', '2026-10-18 08:46:46'),
  ('21', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261016-0001 for amount Rs. 900.00', '::1', '2026-10-18 08:46:46'),
  ('22', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261016-0002 for amount Rs. 1,000.00', '::1', '2026-10-18 08:46:46'),
  ('23', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261016-0003 for amount Rs. 2,300.00', '::1', '2026-10-18 08:46:46'),
  ('24', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261016-0004 for amount Rs. 3,400.00', '::1', '2026-10-18 08:46:46'),
  ('25', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261017-0001 for amount Rs. 2,000.00', '::1', '2026-10-18 08:46:46'),
  ('26', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261017-0002 for amount Rs. 810.00', '::1', '2026-10-18 08:46:46'),
  ('27', '2', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261017-0003 for amount Rs. 6,500.00', '::1', '2026-10-18 08:46:46'),
  ('28', '1', 'Created Invoice', 'POS', 'Seeded Invoice INV-20261017-0004 for amount Rs. 2,600.00', '::1', '2026-10-18 08:46:46'),
  ('29', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: backup_comm_bookshop_pos_2026-10-17_075113.sql', '::1', '2026-10-18 08:48:50'),
  ('30', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_clear_data_2026-10-17_075128.sql', '::1', '2026-10-18 08:48:52'),
  ('31', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_restore_2026-10-17_075202.sql', '::1', '2026-10-18 08:48:54'),
  ('32', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: uploaded_2026-10-17_082746_test_bom_upload.sql', '::1', '2026-10-18 08:49:03'),
  ('33', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_restore_2026-10-17_075241.sql', '::1', '2026-10-18 08:49:05'),
  ('34', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_restore_2026-10-17_075349.sql', '::1', '2026-10-18 08:49:07'),
  ('35', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_restore_2026-10-17_075719.sql', '::1', '2026-10-18 08:49:09'),
  ('36', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_restore_2026-10-17_082323.sql', '::1', '2026-10-18 08:49:11'),
  ('37', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: safety_snapshot_before_restore_2026-10-17_082746.sql', '::1', '2026-10-18 08:49:12'),
  ('38', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: starter_catalog_default.sql', '::1', '2026-10-18 08:49:14'),
  ('39', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: starter_catalog_default.sql', '::1', '2026-10-18 08:49:16'),
  ('40', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: starter_catalog_default.sql', '::1', '2026-10-18 08:49:17'),
  ('41', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: uploaded_2026-10-17_075349_backup_comm_bookshop_pos_2026-10-17_075105.sql', '::1', '2026-10-18 08:49:19'),
  ('42', '1', 'Deleted Backup', 'Backup', 'Deleted backup file: starter_catalog_default.sql', '::1', '2026-10-18 08:49:22'),
  ('43', '1', 'Created Backup', 'Backup', 'Created local backup: backup_comm_bookshop_pos_2026-10-18_052017.sql', '::1', '2026-10-18 08:50:17'),
  ('44', '1', 'Downloaded Stored Backup', 'Backup', 'Downloaded stored backup: backup_comm_bookshop_pos_2026-10-18_052017.sql', '::1', '2026-10-18 08:50:20');

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
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `categories` (9 rows)
INSERT INTO `categories` (`id`, `name`, `type`, `icon`, `created_at`) VALUES
  ('1', 'Books & Novels', 'goods', 'bi-book', '2026-10-18 08:46:46'),
  ('2', 'Exercise Books & Notebooks', 'goods', 'bi-journal-bookmark', '2026-10-18 08:46:46'),
  ('3', 'Stationery & Writing', 'goods', 'bi-pen', '2026-10-18 08:46:46'),
  ('4', 'Office & School Supplies', 'goods', 'bi-briefcase', '2026-10-18 08:46:46'),
  ('5', 'Art & Craft Materials', 'goods', 'bi-palette', '2026-10-18 08:46:46'),
  ('6', 'Printing & Photocopy Services', 'service', 'bi-printer', '2026-10-18 08:46:46'),
  ('7', 'Lamination & Binding', 'service', 'bi-shield-shaded', '2026-10-18 08:46:46'),
  ('8', 'Mobile Reload & Telecom', 'service', 'bi-phone', '2026-10-18 08:46:46'),
  ('24', 'Mobile Reload & Telecom Services', 'service', 'bi-phone', '2026-10-18 08:49:14');

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
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `invoice_items` (48 rows)
INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `product_name`, `barcode`, `item_type`, `cost_price`, `unit_price`, `quantity`, `subtotal`, `created_at`) VALUES
  ('1', '1', '1', 'Atlas CR 80 Pages Single Rule Exercise Book', '9789551234011', 'physical', '120.00', '160.00', '3', '480.00', '2026-10-13 09:30:15'),
  ('2', '1', '5', 'Atlas Choc Ballpoint Pen 0.7mm (Blue)', '4792011001015', 'physical', '22.00', '35.00', '2', '70.00', '2026-10-13 09:30:15'),
  ('3', '2', '19', 'B&W Photocopy (Single Side)', 'SRV-XER-BW', 'service', '2.50', '10.00', '15', '150.00', '2026-10-13 11:45:20'),
  ('4', '2', '22', 'A4 Thermal Document Lamination', 'SRV-LAM-A4', 'service', '35.00', '100.00', '2', '200.00', '2026-10-13 11:45:20'),
  ('5', '3', '14', 'Double A A4 80gsm Premium Copier Paper Ream (500 Sheets)', '8902001004561', 'physical', '1650.00', '1950.00', '1', '1950.00', '2026-10-13 14:15:33'),
  ('6', '3', '15', 'Kangaro No. 10 Office Stapler with 1000 Pins', '4791001005555', 'physical', '320.00', '450.00', '1', '450.00', '2026-10-13 14:15:33'),
  ('7', '4', '25', 'Dialog Mobile Reload / DTV Bill Payment', 'TEL-DIA-REL', 'reload', '95.00', '100.00', '5', '500.00', '2026-10-13 17:20:45'),
  ('8', '4', '7', 'Atlas Choc Ballpoint Pen 0.7mm (Black)', '4792011001039', 'physical', '22.00', '35.00', '3', '105.00', '2026-10-13 17:20:45'),
  ('9', '5', '2', 'Atlas CR 120 Pages Square Rule Book', '9789551234028', 'physical', '170.00', '220.00', '2', '440.00', '2026-10-14 09:12:10'),
  ('10', '5', '9', 'Nataraj 2B Velvet Writing Pencils (Pack of 10)', '4792011002012', 'physical', '140.00', '200.00', '1', '200.00', '2026-10-14 09:12:10'),
  ('11', '5', '10', 'Pelikan Dust-Free High Grade Vinyl Eraser', '4792011003019', 'physical', '30.00', '50.00', '2', '100.00', '2026-10-14 09:12:10'),
  ('12', '6', '16', 'Gunasena Sinhala-English Pocket Comprehensive Dictionary', '9789552119999', 'physical', '650.00', '850.00', '1', '850.00', '2026-10-14 12:35:40'),
  ('13', '6', '17', 'Madol Doova (Original Sinhala Classic by Martin Wickramasinghe)', '9789556520011', 'physical', '350.00', '450.00', '1', '450.00', '2026-10-14 12:35:40'),
  ('14', '7', '21', 'Color Printout High Quality A4', 'SRV-PRT-CLR', 'service', '12.00', '40.00', '8', '320.00', '2026-10-14 15:50:22'),
  ('15', '7', '23', 'Spiral Document Binding (Up to 100 pages)', 'SRV-BIND-SP', 'service', '60.00', '180.00', '1', '180.00', '2026-10-14 15:50:22'),
  ('16', '8', '26', 'Mobitel Mobile Reload / Bill Payment', 'TEL-MOB-REL', 'reload', '95.00', '100.00', '10', '1000.00', '2026-10-14 18:10:15'),
  ('17', '8', '8', 'Pilot G2 0.7mm Retractable Gel Ink Pen (Black)', '8901234567890', 'physical', '280.00', '380.00', '1', '380.00', '2026-10-14 18:10:15'),
  ('18', '9', '14', 'Double A A4 80gsm Premium Copier Paper Ream (500 Sheets)', '8902001004561', 'physical', '1650.00', '1950.00', '2', '3900.00', '2026-10-15 10:05:30'),
  ('19', '9', '13', 'Maped Student Safety Scissors 13cm', '8901030098765', 'physical', '120.00', '180.00', '2', '360.00', '2026-10-15 10:05:30'),
  ('20', '9', '12', 'Fevicol MR Synthetic Craft Adhesive Glue 50g', '8901030012345', 'physical', '75.00', '110.00', '3', '330.00', '2026-10-15 10:05:30'),
  ('21', '10', '20', 'B&W Photocopy (Double Side)', 'SRV-XER-DS', 'service', '4.00', '15.00', '24', '360.00', '2026-10-15 13:18:45'),
  ('22', '10', '24', 'Document Scanning & Email (Per Page)', 'SRV-SCAN-PDF', 'service', '0.00', '30.00', '4', '120.00', '2026-10-15 13:18:45'),
  ('23', '11', '3', 'Atlas CR 200 Pages Single Rule Hardcover Book', '9789551234035', 'physical', '260.00', '340.00', '2', '680.00', '2026-10-15 16:40:12'),
  ('24', '11', '4', 'Atlas A4 Drawing Book 40 Pages', '9789551234042', 'physical', '110.00', '150.00', '2', '300.00', '2026-10-15 16:40:12'),
  ('25', '11', '11', 'Maped 30cm Shatterproof Transparent Ruler', '4792011004016', 'physical', '55.00', '90.00', '2', '180.00', '2026-10-15 16:40:12'),
  ('26', '12', '18', 'English Grammar in Use (Intermediate 5th Edition)', '9780521189064', 'physical', '1100.00', '1450.00', '1', '1450.00', '2026-10-15 19:02:50'),
  ('27', '12', '8', 'Pilot G2 0.7mm Retractable Gel Ink Pen (Black)', '8901234567890', 'physical', '280.00', '380.00', '2', '760.00', '2026-10-15 19:02:50'),
  ('28', '13', '5', 'Atlas Choc Ballpoint Pen 0.7mm (Blue)', '4792011001015', 'physical', '22.00', '35.00', '10', '350.00', '2026-10-16 09:45:00'),
  ('29', '13', '6', 'Atlas Choc Ballpoint Pen 0.7mm (Red)', '4792011001022', 'physical', '22.00', '35.00', '5', '175.00', '2026-10-16 09:45:00'),
  ('30', '13', '9', 'Nataraj 2B Velvet Writing Pencils (Pack of 10)', '4792011002012', 'physical', '140.00', '200.00', '2', '400.00', '2026-10-16 09:45:00'),
  ('31', '14', '21', 'Color Printout High Quality A4', 'SRV-PRT-CLR', 'service', '12.00', '40.00', '15', '600.00', '2026-10-16 11:55:18'),
  ('32', '14', '22', 'A4 Thermal Document Lamination', 'SRV-LAM-A4', 'service', '35.00', '100.00', '4', '400.00', '2026-10-16 11:55:18'),
  ('33', '15', '17', 'Madol Doova (Original Sinhala Classic by Martin Wickramasinghe)', '9789556520011', 'physical', '350.00', '450.00', '2', '900.00', '2026-10-16 14:30:25'),
  ('34', '15', '16', 'Gunasena Sinhala-English Pocket Comprehensive Dictionary', '9789552119999', 'physical', '650.00', '850.00', '1', '850.00', '2026-10-16 14:30:25'),
  ('35', '15', '1', 'Atlas CR 80 Pages Single Rule Exercise Book', '9789551234011', 'physical', '120.00', '160.00', '4', '640.00', '2026-10-16 14:30:25'),
  ('36', '16', '25', 'Dialog Mobile Reload / DTV Bill Payment', 'TEL-DIA-REL', 'reload', '95.00', '100.00', '15', '1500.00', '2026-10-16 17:42:30'),
  ('37', '16', '14', 'Double A A4 80gsm Premium Copier Paper Ream (500 Sheets)', '8902001004561', 'physical', '1650.00', '1950.00', '1', '1950.00', '2026-10-16 17:42:30'),
  ('38', '17', '2', 'Atlas CR 120 Pages Square Rule Book', '9789551234028', 'physical', '170.00', '220.00', '4', '880.00', '2026-10-17 09:10:20'),
  ('39', '17', '3', 'Atlas CR 200 Pages Single Rule Hardcover Book', '9789551234035', 'physical', '260.00', '340.00', '3', '1020.00', '2026-10-17 09:10:20'),
  ('40', '17', '7', 'Atlas Choc Ballpoint Pen 0.7mm (Black)', '4792011001039', 'physical', '22.00', '35.00', '4', '140.00', '2026-10-17 09:10:20'),
  ('41', '18', '19', 'B&W Photocopy (Single Side)', 'SRV-XER-BW', 'service', '2.50', '10.00', '30', '300.00', '2026-10-17 12:20:45'),
  ('42', '18', '23', 'Spiral Document Binding (Up to 100 pages)', 'SRV-BIND-SP', 'service', '60.00', '180.00', '2', '360.00', '2026-10-17 12:20:45'),
  ('43', '18', '24', 'Document Scanning & Email (Per Page)', 'SRV-SCAN-PDF', 'service', '0.00', '30.00', '5', '150.00', '2026-10-17 12:20:45'),
  ('44', '19', '14', 'Double A A4 80gsm Premium Copier Paper Ream (500 Sheets)', '8902001004561', 'physical', '1650.00', '1950.00', '3', '5850.00', '2026-10-17 15:05:12'),
  ('45', '19', '15', 'Kangaro No. 10 Office Stapler with 1000 Pins', '4791001005555', 'physical', '320.00', '450.00', '2', '900.00', '2026-10-17 15:05:12'),
  ('46', '20', '18', 'English Grammar in Use (Intermediate 5th Edition)', '9780521189064', 'physical', '1100.00', '1450.00', '1', '1450.00', '2026-10-17 18:15:40'),
  ('47', '20', '8', 'Pilot G2 0.7mm Retractable Gel Ink Pen (Black)', '8901234567890', 'physical', '280.00', '380.00', '3', '1140.00', '2026-10-17 18:15:40'),
  ('48', '20', '10', 'Pelikan Dust-Free High Grade Vinyl Eraser', '4792011003019', 'physical', '30.00', '50.00', '3', '150.00', '2026-10-17 18:15:40');

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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `invoices` (20 rows)
INSERT INTO `invoices` (`id`, `invoice_no`, `user_id`, `customer_name`, `customer_phone`, `subtotal`, `discount_type`, `discount_amount`, `tax_amount`, `total_amount`, `payment_method`, `amount_paid`, `change_amount`, `status`, `notes`, `created_at`) VALUES
  ('1', 'INV-20261013-0001', '1', 'Kasun Perera', '+94 77 123 4567', '550.00', 'fixed', '0.00', '0.00', '550.00', 'cash', '600.00', '50.00', 'completed', 'Stationery purchases for school term', '2026-10-13 09:30:15'),
  ('2', 'INV-20261013-0002', '2', 'Walk-in Customer', '', '350.00', 'fixed', '0.00', '0.00', '350.00', 'cash', '500.00', '150.00', 'completed', 'Photocopy and document lamination counter service', '2026-10-13 11:45:20'),
  ('3', 'INV-20261013-0003', '2', 'Dinithi Fernando', '+94 71 456 7890', '2400.00', 'fixed', '100.00', '0.00', '2300.00', 'card', '2300.00', '0.00', 'completed', 'Office stationery supplies order (discount applied)', '2026-10-13 14:15:33'),
  ('4', 'INV-20261013-0004', '1', 'Sunil Shantha', '+94 78 334 4556', '605.00', 'fixed', '0.00', '0.00', '605.00', 'qr', '605.00', '0.00', 'completed', 'LankaQR mobile payment verified', '2026-10-13 17:20:45'),
  ('5', 'INV-20261014-0001', '1', 'Walk-in Customer', '', '740.00', 'fixed', '0.00', '0.00', '740.00', 'cash', '1000.00', '260.00', 'completed', 'Morning student stationery packs', '2026-10-14 09:12:10'),
  ('6', 'INV-20261014-0002', '2', 'Anusha Wickramasinghe', '+94 76 223 3445', '1300.00', 'fixed', '50.00', '0.00', '1250.00', 'card', '1250.00', '0.00', 'completed', 'Novels and literature reader purchase', '2026-10-14 12:35:40'),
  ('7', 'INV-20261014-0003', '2', 'Nuwan Pradeep', '+94 77 998 8776', '500.00', 'fixed', '0.00', '0.00', '500.00', 'qr', '500.00', '0.00', 'completed', 'Project report color printing and spiral binding', '2026-10-14 15:50:22'),
  ('8', 'INV-20261014-0004', '1', 'Walk-in Customer', '', '1380.00', 'fixed', '0.00', '0.00', '1380.00', 'cash', '1500.00', '120.00', 'completed', 'Mobile reload & gel pen', '2026-10-14 18:10:15'),
  ('9', 'INV-20261015-0001', '2', 'Dilshan Jayawardena', '+94 72 345 6789', '4590.00', 'fixed', '150.00', '0.00', '4440.00', 'transfer', '4440.00', '0.00', 'completed', 'Bulk art supplies & paper reams (Online Bank Transfer)', '2026-10-15 10:05:30'),
  ('10', 'INV-20261015-0002', '1', 'Walk-in Customer', '', '480.00', 'fixed', '0.00', '0.00', '480.00', 'cash', '500.00', '20.00', 'completed', 'Double-side photocopy copies & scanning', '2026-10-15 13:18:45'),
  ('11', 'INV-20261015-0003', '2', 'Malith Silva', '+94 75 667 7889', '1160.00', 'fixed', '0.00', '0.00', '1160.00', 'cash', '1200.00', '40.00', 'completed', 'Hardcover books and drawing books for art students', '2026-10-15 16:40:12'),
  ('12', 'INV-20261015-0004', '1', 'Ruwan Jayasinghe', '+94 77 554 4332', '2210.00', 'fixed', '110.00', '0.00', '2100.00', 'card', '2100.00', '0.00', 'completed', 'Advanced English Grammar book & Pilot pens', '2026-10-15 19:02:50'),
  ('13', 'INV-20261016-0001', '2', 'Chathura Bandara', '+94 71 223 3990', '925.00', 'fixed', '25.00', '0.00', '900.00', 'cash', '1000.00', '100.00', 'completed', 'Pen sets & pencils bulk box', '2026-10-16 09:45:00'),
  ('14', 'INV-20261016-0002', '1', 'Walk-in Customer', '', '1000.00', 'fixed', '0.00', '0.00', '1000.00', 'qr', '1000.00', '0.00', 'completed', 'Digital color certificates printout and lamination', '2026-10-16 11:55:18'),
  ('15', 'INV-20261016-0003', '2', 'Chamari Wickramasinghe', '+94 77 665 5443', '2390.00', 'fixed', '90.00', '0.00', '2300.00', 'card', '2300.00', '0.00', 'completed', 'Educational novel sets & exercise books', '2026-10-16 14:30:25'),
  ('16', 'INV-20261016-0004', '1', 'Walk-in Customer', '', '3450.00', 'fixed', '50.00', '0.00', '3400.00', 'cash', '3500.00', '100.00', 'completed', 'Prepaid reloads and paper pack', '2026-10-16 17:42:30'),
  ('17', 'INV-20261017-0001', '2', 'Kavinda Rathnayake', '+94 70 112 2334', '2040.00', 'fixed', '40.00', '0.00', '2000.00', 'cash', '2000.00', '0.00', 'completed', 'Higher grade notebooks bundle and stationery', '2026-10-17 09:10:20'),
  ('18', 'INV-20261017-0002', '1', 'Walk-in Customer', '', '810.00', 'fixed', '0.00', '0.00', '810.00', 'qr', '810.00', '0.00', 'completed', 'Exam past papers photocopying, binding and digital scanning', '2026-10-17 12:20:45'),
  ('19', 'INV-20261017-0003', '2', 'Tharindu Gunasekara', '+94 77 443 3221', '6750.00', 'fixed', '250.00', '0.00', '6500.00', 'transfer', '6500.00', '0.00', 'completed', 'Commercial office bulk printing paper & heavy-duty staplers', '2026-10-17 15:05:12'),
  ('20', 'INV-20261017-0004', '1', 'Dinithi Fernando', '+94 71 456 7890', '2740.00', 'fixed', '140.00', '0.00', '2600.00', 'card', '2600.00', '0.00', 'completed', 'Evening bookstore sale with POS card terminal', '2026-10-17 18:15:40');

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
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `products` (27 rows)
INSERT INTO `products` (`id`, `barcode`, `sku`, `name`, `category_id`, `item_type`, `cost_price`, `selling_price`, `stock_qty`, `min_stock_alert`, `unit`, `supplier_id`, `status`, `created_at`, `updated_at`) VALUES
  ('1', '9789551234011', 'BK-ATLAS-CR80', 'Atlas CR 80 Pages Single Rule Exercise Book', '2', 'physical', '120.00', '160.00', '75', '15', 'pcs', '1', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('2', '9789551234028', 'BK-ATLAS-CR120', 'Atlas CR 120 Pages Square Rule Book', '2', 'physical', '170.00', '220.00', '60', '10', 'pcs', '1', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('3', '9789551234035', 'BK-ATLAS-CR200', 'Atlas CR 200 Pages Single Rule Hardcover Book', '2', 'physical', '260.00', '340.00', '45', '8', 'pcs', '1', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('4', '9789551234042', 'BK-DRAW-A4', 'Atlas A4 Drawing Book 40 Pages', '5', 'physical', '110.00', '150.00', '40', '10', 'pcs', '1', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('5', '4792011001015', 'PEN-ATLAS-CHOC-BLU', 'Atlas Choc Ballpoint Pen 0.7mm (Blue)', '3', 'physical', '22.00', '35.00', '250', '50', 'pcs', '1', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('6', '4792011001022', 'PEN-ATLAS-CHOC-RED', 'Atlas Choc Ballpoint Pen 0.7mm (Red)', '3', 'physical', '22.00', '35.00', '180', '30', 'pcs', '1', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('7', '4792011001039', 'PEN-ATLAS-CHOC-BLK', 'Atlas Choc Ballpoint Pen 0.7mm (Black)', '3', 'physical', '22.00', '35.00', '150', '30', 'pcs', '1', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('8', '8901234567890', 'PEN-PILOT-G2', 'Pilot G2 0.7mm Retractable Gel Ink Pen (Black)', '3', 'physical', '280.00', '380.00', '35', '5', 'pcs', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('9', '4792011002012', 'PENCIL-NATARAJ-10', 'Nataraj 2B Velvet Writing Pencils (Pack of 10)', '3', 'physical', '140.00', '200.00', '50', '10', 'pack', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('10', '4792011003019', 'ERA-PELIKAN-DF', 'Pelikan Dust-Free High Grade Vinyl Eraser', '3', 'physical', '30.00', '50.00', '90', '20', 'pcs', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('11', '4792011004016', 'RUL-MAPED-30CM', 'Maped 30cm Shatterproof Transparent Ruler', '4', 'physical', '55.00', '90.00', '65', '10', 'pcs', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('12', '8901030012345', 'GLUE-FEVI-50G', 'Fevicol MR Synthetic Craft Adhesive Glue 50g', '5', 'physical', '75.00', '110.00', '40', '8', 'bottle', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('13', '8901030098765', 'SCIS-MAPED-13', 'Maped Student Safety Scissors 13cm', '4', 'physical', '120.00', '180.00', '30', '5', 'pcs', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('14', '8902001004561', 'PAP-DOUBLE-A4', 'Double A A4 80gsm Premium Copier Paper Ream (500 Sheets)', '4', 'physical', '1650.00', '1950.00', '25', '5', 'ream', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('15', '4791001005555', 'STAP-KANG-10', 'Kangaro No. 10 Office Stapler with 1000 Pins', '4', 'physical', '320.00', '450.00', '20', '4', 'set', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('16', '9789552119999', 'BK-SIN-ENG-DIC', 'Gunasena Sinhala-English Pocket Comprehensive Dictionary', '1', 'physical', '650.00', '850.00', '15', '3', 'copy', '3', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('17', '9789556520011', 'BK-MADOL-DOOVA', 'Madol Doova (Original Sinhala Classic by Martin Wickramasinghe)', '1', 'physical', '350.00', '450.00', '20', '4', 'copy', '3', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('18', '9780521189064', 'BK-ENG-GRAM-INT', 'English Grammar in Use (Intermediate 5th Edition)', '1', 'physical', '1100.00', '1450.00', '25', '4', 'copy', '3', 'active', '2026-10-18 08:46:46', '2026-10-18 08:46:46'),
  ('19', 'SRV-XER-BW', 'SRV-XER-BW', 'B&W Photocopy (Single Side)', '6', 'service', '2.50', '10.00', '99999', '0', 'pages', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('20', 'SRV-XER-DS', 'SRV-XER-DS', 'B&W Photocopy (Double Side)', '6', 'service', '4.00', '15.00', '99999', '0', 'pages', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('21', 'SRV-PRT-CLR', 'SRV-PRT-CLR', 'Color Printout High Quality A4', '6', 'service', '12.00', '40.00', '99999', '0', 'pages', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('22', 'SRV-LAM-A4', 'SRV-LAM-A4', 'A4 Thermal Document Lamination', '7', 'service', '35.00', '100.00', '99999', '0', 'doc', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('23', 'SRV-BIND-SP', 'SRV-BIND-SP', 'Spiral Document Binding (Up to 100 pages)', '7', 'service', '60.00', '180.00', '99999', '0', 'doc', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('24', 'SRV-SCAN-PDF', 'SRV-SCAN-PDF', 'Document Scanning & Email (Per Page)', '6', 'service', '0.00', '30.00', '99999', '0', 'pages', '2', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('25', 'TEL-DIA-REL', 'TEL-DIA-REL', 'Dialog Mobile Reload / DTV Bill Payment', '24', 'reload', '95.00', '100.00', '99999', '0', 'unit', '4', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('26', 'TEL-MOB-REL', 'TEL-MOB-REL', 'Mobitel Mobile Reload / Bill Payment', '24', 'reload', '95.00', '100.00', '99999', '0', 'unit', '4', 'active', '2026-10-18 08:46:46', '2026-10-18 08:49:14'),
  ('27', 'TEL-AIR-REL', 'TEL-AIR-REL', 'Airtel Prepaid Mobile Reload', '8', 'reload', '95.00', '100.00', '99999', '0', 'unit', '4', 'active', '2026-10-18 08:46:46', '2026-10-18 08:46:46');

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
  ('1', 'Atlas Axillia PLC', 'Atlas Axillia PLC Sri Lanka', '+94 11 294 6200', 'sales@atlas.lk', 'No 96, Parakrama Road, Peliyagoda', '2026-10-18 08:46:46'),
  ('2', 'Richard Pieris & Co (Arpico)', 'Richard Pieris Distributors Ltd', '+94 11 431 0500', 'stationery@arpico.com', '310 High Level Road, Nawinna, Maharagama', '2026-10-18 08:46:46'),
  ('3', 'Sarasavi Bookshop & Publishers', 'Sarasavi Bookshop (Pvt) Ltd', '+94 11 282 0830', 'distribution@sarasavi.lk', 'No. 20, Stanley Thilakarathne Mawatha, Nugegoda', '2026-10-18 08:46:46'),
  ('4', 'Dialog & Mobitel Telecom Distributor', 'Metropolitan Telecom Services', '+94 77 767 8678', 'telecom.pos@dialog.lk', '475 Union Place, Colombo 02', '2026-10-18 08:46:46'),
  ('5', 'Master Office Supplies & Paper', 'Master Paper & Stationery Ltd', '+94 11 258 9012', 'contact@masterpaper.lk', 'No. 12, Galle Road, Colombo 04', '2026-10-18 08:46:46');

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
