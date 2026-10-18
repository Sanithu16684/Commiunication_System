-- ============================================================
-- Test Upload Backup with UTF-8 BOM
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- Insert an extra sample category and product
INSERT INTO `categories` (`name`, `type`, `icon`) VALUES ('Gift Items & Wrapping', 'goods', 'bi-gift')
ON DUPLICATE KEY UPDATE `type` = 'goods';

INSERT INTO `products` (`barcode`, `sku`, `name`, `category_id`, `item_type`, `cost_price`, `selling_price`, `stock_qty`, `min_stock_alert`, `unit`, `supplier_id`, `status`)
VALUES ('9789559990001', 'GIFT-WRAP-PREM', 'Premium Glossy Gift Wrap Paper Sheet (50x70cm)', (SELECT id FROM categories WHERE name = 'Gift Items & Wrapping' LIMIT 1), 'physical', 45.00, 75.00, 100, 20, 'sheets', 1, 'active')
ON DUPLICATE KEY UPDATE `selling_price` = 75.00;

SET FOREIGN_KEY_CHECKS = 1;
