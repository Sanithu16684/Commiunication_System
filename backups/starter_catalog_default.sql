-- ============================================================
-- Metro POS & Communication System - Starter Store Catalog
-- Pre-configured baseline inventory for Communication & Bookshop
-- Generated: 2026-10-18 05:19:22
-- Contains: 24 Products, 8 Categories, 4 Suppliers
-- Safe Restore: Preserves existing User Accounts and Store Settings
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 1;
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Table structure & seed data for `categories`
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
-- Table structure & seed data for `suppliers`
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

INSERT INTO `suppliers` (`id`, `name`, `company`, `phone`, `email`, `address`, `created_at`) VALUES
  ('1', 'Atlas Axillia PLC', 'Atlas Axillia PLC Sri Lanka', '+94 11 294 6200', 'sales@atlas.lk', 'No 96, Parakrama Road, Peliyagoda', '2026-10-18 08:46:46'),
  ('2', 'Richard Pieris & Co (Arpico)', 'Richard Pieris Distributors Ltd', '+94 11 431 0500', 'stationery@arpico.com', '310 High Level Road, Nawinna, Maharagama', '2026-10-18 08:46:46'),
  ('3', 'Sarasavi Bookshop & Publishers', 'Sarasavi Bookshop (Pvt) Ltd', '+94 11 282 0830', 'distribution@sarasavi.lk', 'No. 20, Stanley Thilakarathne Mawatha, Nugegoda', '2026-10-18 08:46:46'),
  ('4', 'Dialog & Mobitel Telecom Distributor', 'Metropolitan Telecom Services', '+94 77 767 8678', 'telecom.pos@dialog.lk', '475 Union Place, Colombo 02', '2026-10-18 08:46:46'),
  ('5', 'Master Office Supplies & Paper', 'Master Paper & Stationery Ltd', '+94 11 258 9012', 'contact@masterpaper.lk', 'No. 12, Galle Road, Colombo 04', '2026-10-18 08:46:46');

-- ------------------------------------------------------------
-- Table structure & seed data for `products`
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

SET FOREIGN_KEY_CHECKS = 1;
-- [End of Starter Catalog Baseline Dump]
