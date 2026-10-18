<?php
// backup.php - Database Backup and Restore Management (Admin only)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_admin();

$user = current_user();
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Helper: Generate SQL Backup String
function generate_sql_dump($pdo, $db_name) {
    // Retrieve all base tables
    $tables = [];
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $out = "-- ============================================================\n";
    $out .= "-- Metro POS & Communication System - Database Backup\n";
    $out .= "-- Database: `{$db_name}`\n";
    $out .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $out .= "-- Tables Count: " . count($tables) . "\n";
    $out .= "-- ============================================================\n\n";
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $out .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
    $out .= "SET AUTOCOMMIT = 1;\n";
    $out .= "SET NAMES utf8mb4;\n\n";

    foreach ($tables as $table) {
        $out .= "-- ------------------------------------------------------------\n";
        $out .= "-- Table structure for `{$table}`\n";
        $out .= "-- ------------------------------------------------------------\n";
        $out .= "DROP TABLE IF EXISTS `{$table}`;\n";

        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        if ($createStmt && isset($createStmt[1])) {
            $out .= $createStmt[1] . ";\n\n";
        }

        // Fetch rows
        $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
        $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $out .= "-- Dumping data for `{$table}` (" . count($rows) . " rows)\n";
            $columns = array_keys($rows[0]);
            $colList = '`' . implode('`, `', $columns) . '`';

            // Chunk inserts by 50 to optimize payload size
            $chunks = array_chunk($rows, 50);
            foreach ($chunks as $chunk) {
                $out .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";
                $valLines = [];
                foreach ($chunk as $row) {
                    $vals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = $pdo->quote($val);
                        }
                    }
                    $valLines[] = "  (" . implode(', ', $vals) . ")";
                }
                $out .= implode(",\n", $valLines) . ";\n";
            }
            $out .= "\n";
        }
    }

    $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $out .= "-- [End of Database Backup Dump]\n";

    return $out;
}

// Helper: Robust SQL Restore Parser & Executor
function execute_sql_restore($pdo, $sqlContent) {
    @set_time_limit(300);

    // 1. Strip UTF-8 BOM if present (prevents Windows Notepad 1064 syntax error)
    $sqlContent = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContent);

    // 2. Normalize newlines
    $sqlContent = str_replace(["\r\n", "\r"], "\n", $sqlContent);

    // Disable foreign keys and set standard modes
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("SET SQL_MODE = '';");
    $pdo->exec("SET AUTOCOMMIT = 1;");
    $pdo->exec("SET NAMES utf8mb4;");

    $len = strlen($sqlContent);
    $query = '';
    $in_string = false;
    $string_char = '';
    $in_backtick = false;
    $in_comment = false;
    $comment_type = '';
    $executedCount = 0;
    $warnings = [];
    $errors = [];

    try {
        for ($i = 0; $i < $len; $i++) {
            $char = $sqlContent[$i];
            $next = ($i + 1 < $len) ? $sqlContent[$i + 1] : '';

            // Handle inside comment
            if ($in_comment) {
                if ($comment_type === '/*' && $char === '*' && $next === '/') {
                    $in_comment = false;
                    $i++;
                } elseif (($comment_type === '--' || $comment_type === '#') && $char === "\n") {
                    $in_comment = false;
                }
                continue;
            }

            // Handle inside string literal
            if ($in_string) {
                $query .= $char;
                if ($char === '\\') {
                    if ($i + 1 < $len) {
                        $i++;
                        $query .= $sqlContent[$i];
                    }
                } elseif ($char === $string_char) {
                    if ($string_char === "'" && $next === "'") {
                        $i++;
                        $query .= $sqlContent[$i];
                    } else {
                        $in_string = false;
                    }
                }
                continue;
            }

            // Handle inside backtick identifier
            if ($in_backtick) {
                $query .= $char;
                if ($char === '`') {
                    if ($next === '`') {
                        $i++;
                        $query .= $sqlContent[$i];
                    } else {
                        $in_backtick = false;
                    }
                }
                continue;
            }

            // Check for comment starts
            if ($char === '-' && $next === '-') {
                $in_comment = true;
                $comment_type = '--';
                $i++;
                continue;
            }
            if ($char === '#') {
                $in_comment = true;
                $comment_type = '#';
                continue;
            }
            if ($char === '/' && $next === '*') {
                $in_comment = true;
                $comment_type = '/*';
                $i++;
                continue;
            }

            // Check for identifier starts
            if ($char === '`') {
                $in_backtick = true;
                $query .= $char;
                continue;
            }

            // Check for string starts
            if ($char === "'" || $char === '"') {
                $in_string = true;
                $string_char = $char;
                $query .= $char;
                continue;
            }

            // Semicolon statement terminator
            if ($char === ';') {
                $trimmed = trim($query);
                if (!empty($trimmed)) {
                    $upper = strtoupper(substr($trimmed, 0, 25));
                    // Filter out CLI delimiter statements and conflicting transaction headers
                    if (strpos($upper, 'DELIMITER') === false && 
                        strpos($upper, 'START TRANSACTION') === false && 
                        strpos($upper, 'SET AUTOCOMMIT') === false) {
                        try {
                            $pdo->exec($trimmed);
                            $executedCount++;
                        } catch (PDOException $e) {
                            $msg = $e->getMessage();
                            if (stripos($msg, 'syntax error') !== false) {
                                throw new Exception("SQL Syntax Error on statement: " . substr($trimmed, 0, 90) . "... Error: " . $msg);
                            } elseif (stripos($msg, 'already exists') !== false || stripos($msg, 'Duplicate') !== false) {
                                $warnings[] = substr($trimmed, 0, 60) . " (" . $msg . ")";
                            } else {
                                $errors[] = substr($trimmed, 0, 60) . " (" . $msg . ")";
                            }
                        }
                    }
                }
                $query = '';
                continue;
            }

            $query .= $char;
        }

        $trimmed = trim($query);
        if (!empty($trimmed)) {
            $upper = strtoupper(substr($trimmed, 0, 25));
            if (strpos($upper, 'DELIMITER') === false && 
                strpos($upper, 'START TRANSACTION') === false && 
                strpos($upper, 'SET AUTOCOMMIT') === false) {
                try {
                    $pdo->exec($trimmed);
                    $executedCount++;
                } catch (PDOException $e) {
                    $msg = $e->getMessage();
                    if (stripos($msg, 'syntax error') !== false) {
                        throw new Exception("SQL Syntax Error on final statement: " . substr($trimmed, 0, 90) . "... Error: " . $msg);
                    }
                }
            }
        }
    } finally {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    return [
        'statements' => $executedCount,
        'warnings' => $warnings,
        'errors' => $errors
    ];
}

// Helper: Seed Default Starter Catalog (24 Products, 8 Categories, 4 Suppliers)
function seed_starter_catalog_data($pdo) {
    $categories = [
        ['name' => 'Books & Novels', 'type' => 'goods', 'icon' => 'bi-book'],
        ['name' => 'Exercise Books & Notebooks', 'type' => 'goods', 'icon' => 'bi-journal-bookmark'],
        ['name' => 'Stationery & Writing', 'type' => 'goods', 'icon' => 'bi-pen'],
        ['name' => 'Office & School Supplies', 'type' => 'goods', 'icon' => 'bi-briefcase'],
        ['name' => 'Art & Craft Materials', 'type' => 'goods', 'icon' => 'bi-palette'],
        ['name' => 'Printing & Photocopy Services', 'type' => 'service', 'icon' => 'bi-printer'],
        ['name' => 'Lamination & Binding', 'type' => 'service', 'icon' => 'bi-shield-shaded'],
        ['name' => 'Mobile Reload & Telecom Services', 'type' => 'service', 'icon' => 'bi-phone']
    ];

    $suppliers = [
        [
            'name' => 'Atlas Axillia PLC',
            'company' => 'Atlas Axillia PLC Sri Lanka',
            'phone' => '+94 11 294 6200',
            'email' => 'sales@atlas.lk',
            'address' => 'No 96, Parakrama Road, Peliyagoda'
        ],
        [
            'name' => 'Richard Pieris & Co (Arpico)',
            'company' => 'Richard Pieris Distributors Ltd',
            'phone' => '+94 11 431 0500',
            'email' => 'stationery@arpico.com',
            'address' => '310 High Level Road, Nawinna, Maharagama'
        ],
        [
            'name' => 'Sarasavi Bookshop & Publishers',
            'company' => 'Sarasavi Bookshop (Pvt) Ltd',
            'phone' => '+94 11 282 0830',
            'email' => 'distribution@sarasavi.lk',
            'address' => 'No. 20, Stanley Thilakarathne Mawatha, Nugegoda'
        ],
        [
            'name' => 'Dialog & Mobitel Telecom Distributor',
            'company' => 'Metropolitan Telecom Services',
            'phone' => '+94 77 767 8678',
            'email' => 'telecom.pos@dialog.lk',
            'address' => '475 Union Place, Colombo 02'
        ]
    ];

    $products = [
        ['barcode' => 'SRV-XER-BW', 'sku' => 'SRV-XER-BW', 'name' => 'B&W Photocopy (Single Side)', 'category_name' => 'Printing & Photocopy Services', 'item_type' => 'service', 'cost_price' => 2.50, 'selling_price' => 10.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'pages', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => 'SRV-XER-DS', 'sku' => 'SRV-XER-DS', 'name' => 'B&W Photocopy (Double Side)', 'category_name' => 'Printing & Photocopy Services', 'item_type' => 'service', 'cost_price' => 4.00, 'selling_price' => 15.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'pages', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => 'SRV-PRT-CLR', 'sku' => 'SRV-PRT-CLR', 'name' => 'Color Printout High Quality A4', 'category_name' => 'Printing & Photocopy Services', 'item_type' => 'service', 'cost_price' => 12.00, 'selling_price' => 40.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'pages', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => 'SRV-LAM-A4', 'sku' => 'SRV-LAM-A4', 'name' => 'A4 Thermal Document Lamination', 'category_name' => 'Lamination & Binding', 'item_type' => 'service', 'cost_price' => 35.00, 'selling_price' => 100.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'doc', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => 'SRV-BIND-SP', 'sku' => 'SRV-BIND-SP', 'name' => 'Spiral Document Binding (Up to 100 pages)', 'category_name' => 'Lamination & Binding', 'item_type' => 'service', 'cost_price' => 60.00, 'selling_price' => 180.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'doc', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => 'SRV-SCAN-PDF', 'sku' => 'SRV-SCAN-PDF', 'name' => 'Document Scanning & Email (Per Page)', 'category_name' => 'Printing & Photocopy Services', 'item_type' => 'service', 'cost_price' => 0.00, 'selling_price' => 30.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'pages', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => 'TEL-DIA-REL', 'sku' => 'TEL-DIA-REL', 'name' => 'Dialog Mobile Reload / DTV Bill Payment', 'category_name' => 'Mobile Reload & Telecom Services', 'item_type' => 'reload', 'cost_price' => 95.00, 'selling_price' => 100.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'unit', 'supplier_name' => 'Dialog & Mobitel Telecom Distributor'],
        ['barcode' => 'TEL-MOB-REL', 'sku' => 'TEL-MOB-REL', 'name' => 'Mobitel Mobile Reload / Bill Payment', 'category_name' => 'Mobile Reload & Telecom Services', 'item_type' => 'reload', 'cost_price' => 95.00, 'selling_price' => 100.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'unit', 'supplier_name' => 'Dialog & Mobitel Telecom Distributor'],
        ['barcode' => '9789551234011', 'sku' => 'BK-ATLAS-CR80', 'name' => 'Atlas CR 80 Pages Single Rule Exercise Book', 'category_name' => 'Exercise Books & Notebooks', 'item_type' => 'physical', 'cost_price' => 120.00, 'selling_price' => 160.00, 'stock_qty' => 75, 'min_stock_alert' => 15, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
        ['barcode' => '9789551234028', 'sku' => 'BK-ATLAS-CR120', 'name' => 'Atlas CR 120 Pages Square Rule Book', 'category_name' => 'Exercise Books & Notebooks', 'item_type' => 'physical', 'cost_price' => 170.00, 'selling_price' => 220.00, 'stock_qty' => 60, 'min_stock_alert' => 10, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
        ['barcode' => '9789551234035', 'sku' => 'BK-ATLAS-CR200', 'name' => 'Atlas CR 200 Pages Single Rule Hardcover Book', 'category_name' => 'Exercise Books & Notebooks', 'item_type' => 'physical', 'cost_price' => 260.00, 'selling_price' => 340.00, 'stock_qty' => 45, 'min_stock_alert' => 8, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
        ['barcode' => '9789551234042', 'sku' => 'BK-DRAW-A4', 'name' => 'Atlas A4 Drawing Book 40 Pages', 'category_name' => 'Art & Craft Materials', 'item_type' => 'physical', 'cost_price' => 110.00, 'selling_price' => 150.00, 'stock_qty' => 40, 'min_stock_alert' => 10, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
        ['barcode' => '4792011001015', 'sku' => 'PEN-ATLAS-CHOC-BLU', 'name' => 'Atlas Choc Ballpoint Pen 0.7mm (Blue)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 22.00, 'selling_price' => 35.00, 'stock_qty' => 250, 'min_stock_alert' => 50, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
        ['barcode' => '4792011001022', 'sku' => 'PEN-ATLAS-CHOC-RED', 'name' => 'Atlas Choc Ballpoint Pen 0.7mm (Red)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 22.00, 'selling_price' => 35.00, 'stock_qty' => 180, 'min_stock_alert' => 30, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
        ['barcode' => '4792011001039', 'sku' => 'PEN-ATLAS-CHOC-BLK', 'name' => 'Atlas Choc Ballpoint Pen 0.7mm (Black)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 22.00, 'selling_price' => 35.00, 'stock_qty' => 150, 'min_stock_alert' => 30, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
        ['barcode' => '8901234567890', 'sku' => 'PEN-PILOT-G2', 'name' => 'Pilot G2 0.7mm Retractable Gel Ink Pen (Black)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 280.00, 'selling_price' => 380.00, 'stock_qty' => 35, 'min_stock_alert' => 5, 'unit' => 'pcs', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => '4792011002012', 'sku' => 'PENCIL-NATARAJ-10', 'name' => 'Nataraj 2B Velvet Writing Pencils (Pack of 10)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 140.00, 'selling_price' => 200.00, 'stock_qty' => 50, 'min_stock_alert' => 10, 'unit' => 'pack', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => '4792011003019', 'sku' => 'ERA-PELIKAN-DF', 'name' => 'Pelikan Dust-Free High Grade Vinyl Eraser', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 30.00, 'selling_price' => 50.00, 'stock_qty' => 90, 'min_stock_alert' => 20, 'unit' => 'pcs', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => '4792011004016', 'sku' => 'RUL-MAPED-30CM', 'name' => 'Maped 30cm Shatterproof Transparent Ruler', 'category_name' => 'Office & School Supplies', 'item_type' => 'physical', 'cost_price' => 55.00, 'selling_price' => 90.00, 'stock_qty' => 65, 'min_stock_alert' => 10, 'unit' => 'pcs', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => '8901030012345', 'sku' => 'GLUE-FEVI-50G', 'name' => 'Fevicol MR Synthetic Craft Adhesive Glue 50g', 'category_name' => 'Art & Craft Materials', 'item_type' => 'physical', 'cost_price' => 75.00, 'selling_price' => 110.00, 'stock_qty' => 40, 'min_stock_alert' => 8, 'unit' => 'bottle', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => '8901030098765', 'sku' => 'SCIS-MAPED-13', 'name' => 'Maped Student Safety Scissors 13cm', 'category_name' => 'Office & School Supplies', 'item_type' => 'physical', 'cost_price' => 120.00, 'selling_price' => 180.00, 'stock_qty' => 30, 'min_stock_alert' => 5, 'unit' => 'pcs', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => '8902001004561', 'sku' => 'PAP-DOUBLE-A4', 'name' => 'Double A A4 80gsm Premium Copier Paper Ream (500 Sheets)', 'category_name' => 'Office & School Supplies', 'item_type' => 'physical', 'cost_price' => 1650.00, 'selling_price' => 1950.00, 'stock_qty' => 25, 'min_stock_alert' => 5, 'unit' => 'ream', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => '4791001005555', 'sku' => 'STAP-KANG-10', 'name' => 'Kangaro No. 10 Office Stapler with 1000 Pins', 'category_name' => 'Office & School Supplies', 'item_type' => 'physical', 'cost_price' => 320.00, 'selling_price' => 450.00, 'stock_qty' => 20, 'min_stock_alert' => 4, 'unit' => 'set', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
        ['barcode' => '9789552119999', 'sku' => 'BK-SIN-ENG-DIC', 'name' => 'Gunasena Sinhala-English Pocket Comprehensive Dictionary', 'category_name' => 'Books & Novels', 'item_type' => 'physical', 'cost_price' => 650.00, 'selling_price' => 850.00, 'stock_qty' => 15, 'min_stock_alert' => 3, 'unit' => 'copy', 'supplier_name' => 'Sarasavi Bookshop & Publishers'],
        ['barcode' => '9789556520011', 'sku' => 'BK-MADOL-DOOVA', 'name' => 'Madol Doova (Original Sinhala Classic by Martin Wickramasinghe)', 'category_name' => 'Books & Novels', 'item_type' => 'physical', 'cost_price' => 350.00, 'selling_price' => 450.00, 'stock_qty' => 20, 'min_stock_alert' => 4, 'unit' => 'copy', 'supplier_name' => 'Sarasavi Bookshop & Publishers']
    ];

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 1. Seed Categories
    $catMap = [];
    $catStmt = $pdo->prepare("INSERT INTO `categories` (`name`, `type`, `icon`) 
                             VALUES (?, ?, ?) 
                             ON DUPLICATE KEY UPDATE `type` = VALUES(`type`), `icon` = VALUES(`icon`)");
    foreach ($categories as $cat) {
        $catStmt->execute([$cat['name'], $cat['type'], $cat['icon']]);
    }

    $cRows = $pdo->query("SELECT id, name FROM categories")->fetchAll();
    foreach ($cRows as $r) {
        $catMap[$r['name']] = (int)$r['id'];
    }

    // 2. Seed Suppliers
    $suppMap = [];
    $suppCheck = $pdo->prepare("SELECT id FROM suppliers WHERE name = ?");
    $suppInsert = $pdo->prepare("INSERT INTO `suppliers` (`name`, `company`, `phone`, `email`, `address`) VALUES (?, ?, ?, ?, ?)");
    foreach ($suppliers as $supp) {
        $suppCheck->execute([$supp['name']]);
        $existingId = $suppCheck->fetchColumn();
        if ($existingId) {
            $suppMap[$supp['name']] = (int)$existingId;
        } else {
            $suppInsert->execute([$supp['name'], $supp['company'], $supp['phone'], $supp['email'], $supp['address']]);
            $suppMap[$supp['name']] = (int)$pdo->lastInsertId();
        }
    }

    // 3. Seed Products
    $prodStmt = $pdo->prepare("INSERT INTO `products` 
        (`barcode`, `sku`, `name`, `category_id`, `item_type`, `cost_price`, `selling_price`, `stock_qty`, `min_stock_alert`, `unit`, `supplier_id`, `status`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE
            `sku` = VALUES(`sku`),
            `name` = VALUES(`name`),
            `category_id` = VALUES(`category_id`),
            `item_type` = VALUES(`item_type`),
            `cost_price` = VALUES(`cost_price`),
            `selling_price` = VALUES(`selling_price`),
            `stock_qty` = VALUES(`stock_qty`),
            `min_stock_alert` = VALUES(`min_stock_alert`),
            `unit` = VALUES(`unit`),
            `supplier_id` = VALUES(`supplier_id`),
            `status` = 'active'");

    $seededCount = 0;
    foreach ($products as $p) {
        $catId = $catMap[$p['category_name']] ?? null;
        $suppId = $suppMap[$p['supplier_name']] ?? null;
        $prodStmt->execute([
            $p['barcode'],
            $p['sku'],
            $p['name'],
            $catId,
            $p['item_type'],
            $p['cost_price'],
            $p['selling_price'],
            $p['stock_qty'],
            $p['min_stock_alert'],
            $p['unit'],
            $suppId
        ]);
        $seededCount++;
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    return [
        'categories' => count($categories),
        'suppliers' => count($suppliers),
        'products' => $seededCount
    ];
}

// Helper: Ensure baseline starter catalog file exists in /backups
function ensure_starter_catalog_sql_file($pdo, $backupDir) {
    $filepath = $backupDir . '/starter_catalog_default.sql';
    if (!file_exists($filepath) || filesize($filepath) < 500) {
        seed_starter_catalog_data($pdo);

        $out = "-- ============================================================\n";
        $out .= "-- Metro POS & Communication System - Starter Store Catalog\n";
        $out .= "-- Pre-configured baseline inventory for Communication & Bookshop\n";
        $out .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $out .= "-- Contains: 24 Products, 8 Categories, 4 Suppliers\n";
        $out .= "-- Safe Restore: Preserves existing User Accounts and Store Settings\n";
        $out .= "-- ============================================================\n\n";
        $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $out .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $out .= "SET AUTOCOMMIT = 1;\n";
        $out .= "SET NAMES utf8mb4;\n\n";

        $catalogTables = ['categories', 'suppliers', 'products'];
        foreach ($catalogTables as $table) {
            $out .= "-- ------------------------------------------------------------\n";
            $out .= "-- Table structure & seed data for `{$table}`\n";
            $out .= "-- ------------------------------------------------------------\n";
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            if ($createStmt && isset($createStmt[1])) {
                $out .= $createStmt[1] . ";\n\n";
            }

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $colList = '`' . implode('`, `', $columns) . '`';

                $chunks = array_chunk($rows, 50);
                foreach ($chunks as $chunk) {
                    $out .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";
                    $valLines = [];
                    foreach ($chunk as $row) {
                        $vals = [];
                        foreach ($row as $val) {
                            if ($val === null) {
                                $vals[] = 'NULL';
                            } else {
                                $vals[] = $pdo->quote($val);
                            }
                        }
                        $valLines[] = "  (" . implode(', ', $vals) . ")";
                    }
                    $out .= implode(",\n", $valLines) . ";\n";
                }
                $out .= "\n";
            }
        }

        $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $out .= "-- [End of Starter Catalog Baseline Dump]\n";

        file_put_contents($filepath, $out);
    }
}

// Action: Restore Default Starter Catalog (1-Click)
if (isset($_GET['action']) && $_GET['action'] === 'seed_starter_catalog') {
    try {
        // Safety snapshot of current database
        $snapshotName = 'safety_snapshot_before_catalog_seed_' . date('Y-m-d_His') . '.sql';
        $safetySql = generate_sql_dump($pdo, $db_name);
        @file_put_contents($backupDir . '/' . $snapshotName, $safetySql);

        $res = seed_starter_catalog_data($pdo);
        ensure_starter_catalog_sql_file($pdo, $backupDir);

        log_activity($pdo, $user['id'], 'Restored Starter Catalog', 'Inventory', "Restored default store catalog: {$res['products']} products, {$res['categories']} categories, {$res['suppliers']} suppliers. Safety snapshot: {$snapshotName}");

        set_flash('success', "Starter Store Catalog restored successfully! Loaded {$res['products']} Products, {$res['categories']} Categories, and {$res['suppliers']} Suppliers into your store. Your user accounts and store settings remain untouched. (Safety snapshot saved: {$snapshotName})");
    } catch (Exception $e) {
        set_flash('danger', 'Error restoring starter catalog: ' . $e->getMessage());
    }
    header("Location: backup.php");
    exit;
}

// Action: Direct Browser Download of Current Database
if (isset($_GET['action']) && $_GET['action'] === 'download_current') {
    try {
        $sql = generate_sql_dump($pdo, $db_name);
        $filename = 'backup_' . $db_name . '_' . date('Y-m-d_His') . '.sql';

        log_activity($pdo, $user['id'], 'Downloaded Backup', 'Backup', "Downloaded live database backup: {$filename}");

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $sql;
        exit;
    } catch (Exception $e) {
        set_flash('danger', 'Failed to generate backup: ' . $e->getMessage());
        header("Location: backup.php");
        exit;
    }
}

// Action: Create Local Server Backup
if (isset($_POST['action']) && $_POST['action'] === 'create_local') {
    try {
        $sql = generate_sql_dump($pdo, $db_name);
        $filename = 'backup_' . $db_name . '_' . date('Y-m-d_His') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        if (file_put_contents($filepath, $sql) !== false) {
            log_activity($pdo, $user['id'], 'Created Backup', 'Backup', "Created local backup: {$filename}");
            set_flash('success', "Database backup created successfully: {$filename}");
        } else {
            set_flash('danger', 'Failed to write backup file to server storage.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error creating backup: ' . $e->getMessage());
    }
    header("Location: backup.php");
    exit;
}

// Action: Download Existing Server Backup
if (isset($_GET['action']) && $_GET['action'] === 'download_file') {
    $filename = basename($_GET['file'] ?? '');
    $filepath = $backupDir . '/' . $filename;

    if ($filename && preg_match('/\.sql$/i', $filename) && file_exists($filepath)) {
        log_activity($pdo, $user['id'], 'Downloaded Stored Backup', 'Backup', "Downloaded stored backup: {$filename}");
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($filepath);
        exit;
    } else {
        set_flash('danger', 'Requested backup file not found or invalid.');
        header("Location: backup.php");
        exit;
    }
}

// Action: Delete Stored Backup
if (isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    $filename = basename($_POST['file'] ?? '');
    $filepath = $backupDir . '/' . $filename;

    if ($filename && preg_match('/\.sql$/i', $filename) && file_exists($filepath)) {
        if (unlink($filepath)) {
            log_activity($pdo, $user['id'], 'Deleted Backup', 'Backup', "Deleted backup file: {$filename}");
            set_flash('success', "Backup file '{$filename}' deleted successfully.");
        } else {
            set_flash('danger', "Could not delete backup file '{$filename}'. Check file permissions.");
        }
    } else {
        set_flash('danger', 'Invalid backup file specified for deletion.');
    }
    header("Location: backup.php");
    exit;
}

// Action: Restore from Server Stored Backup
if (isset($_POST['action']) && $_POST['action'] === 'restore_server_file') {
    $filename = basename($_POST['file'] ?? '');
    $filepath = $backupDir . '/' . $filename;

    if ($filename && preg_match('/\.sql$/i', $filename) && file_exists($filepath)) {
        try {
            // Check file size
            $fsize = filesize($filepath);
            if ($fsize === 0) {
                throw new Exception("The selected backup file is completely empty (0 bytes).");
            }

            // Safety: Auto-create snapshot of current state before applying restore
            $snapshotName = 'safety_snapshot_before_restore_' . date('Y-m-d_His') . '.sql';
            $safetySql = generate_sql_dump($pdo, $db_name);
            file_put_contents($backupDir . '/' . $snapshotName, $safetySql);

            // Perform restore
            $sqlContent = file_get_contents($filepath);
            $res = execute_sql_restore($pdo, $sqlContent);

            $prodCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $catCount = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
            $invCount = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();

            log_activity($pdo, $user['id'], 'Restored Database', 'Backup', "Restored database from stored backup: {$filename}. Executed: {$res['statements']} statements. Safety snapshot: {$snapshotName}");

            $msg = "Database restored successfully from '{$filename}'! Executed {$res['statements']} SQL statements. (Active Store State: {$prodCount} Products, {$catCount} Categories, {$invCount} Invoices). A safety snapshot ('{$snapshotName}') was saved.";
            if ($prodCount === 0) {
                $msg .= " <em>Note: This backup contained no products. You can restore the starter store catalog anytime using the button above.</em>";
            }
            set_flash('success', $msg);
        } catch (Exception $e) {
            set_flash('danger', 'Restore failed: ' . $e->getMessage());
        }
    } else {
        set_flash('danger', 'Invalid or missing backup file for restore.');
    }
    header("Location: backup.php");
    exit;
}

// Action: Upload & Restore from User's Computer
if (isset($_POST['action']) && $_POST['action'] === 'upload_restore') {
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrCode = $_FILES['backup_file']['error'] ?? -1;
        $errMsg = 'Please choose a valid .sql backup file to upload.';
        if ($uploadErrCode === UPLOAD_ERR_INI_SIZE || $uploadErrCode === UPLOAD_ERR_FORM_SIZE) {
            $errMsg = 'Uploaded file exceeds the server maximum upload file size (' . ini_get('upload_max_filesize') . ').';
        } elseif ($uploadErrCode === UPLOAD_ERR_PARTIAL) {
            $errMsg = 'File upload was interrupted. Please retry.';
        } elseif ($uploadErrCode === UPLOAD_ERR_NO_FILE) {
            $errMsg = 'No backup file was selected for upload.';
        }
        set_flash('danger', $errMsg);
    } else {
        $uploadedName = basename($_FILES['backup_file']['name']);
        $ext = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));

        if ($ext !== 'sql') {
            set_flash('danger', 'Invalid file type. Only standard MySQL .sql files are accepted.');
        } else {
            try {
                $fsize = filesize($_FILES['backup_file']['tmp_name']);
                if ($fsize === 0) {
                    throw new Exception("The uploaded .sql file is empty (0 bytes).");
                }

                // Safety: Auto-create snapshot of current state before applying restore
                $snapshotName = 'safety_snapshot_before_restore_' . date('Y-m-d_His') . '.sql';
                $safetySql = generate_sql_dump($pdo, $db_name);
                file_put_contents($backupDir . '/' . $snapshotName, $safetySql);

                // Save uploaded file into backups folder as record
                $savedUploadName = 'uploaded_' . date('Y-m-d_His') . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $uploadedName);
                move_uploaded_file($_FILES['backup_file']['tmp_name'], $backupDir . '/' . $savedUploadName);

                // Execute restore
                $sqlContent = file_get_contents($backupDir . '/' . $savedUploadName);
                $res = execute_sql_restore($pdo, $sqlContent);

                $prodCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                $catCount = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
                $invCount = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();

                log_activity($pdo, $user['id'], 'Uploaded & Restored Database', 'Backup', "Restored database from uploaded file: {$uploadedName}. Executed: {$res['statements']} statements. Safety snapshot: {$snapshotName}");

                $msg = "Database restored successfully from uploaded file '{$uploadedName}'! Executed {$res['statements']} SQL statements. (Active Store State: {$prodCount} Products, {$catCount} Categories, {$invCount} Invoices). A safety snapshot ('{$snapshotName}') was saved.";
                if ($prodCount === 0) {
                    $msg .= " <em>Note: This uploaded file contained no catalog products. You can restore the starter store catalog anytime using the button above.</em>";
                }
                set_flash('success', $msg);
            } catch (Exception $e) {
                set_flash('danger', 'Restore error: ' . $e->getMessage());
            }
        }
    }
    header("Location: backup.php");
    exit;
}

// Action: Clear All Data (Without User Accounts and Store Settings)
if (isset($_POST['action']) && $_POST['action'] === 'clear_all_data') {
    try {
        // 1. Automatically create safety snapshot before clearing
        $snapshotName = 'safety_snapshot_before_clear_data_' . date('Y-m-d_His') . '.sql';
        $safetySql = generate_sql_dump($pdo, $db_name);
        @file_put_contents($backupDir . '/' . $snapshotName, $safetySql);

        // 2. Clear all operational tables without touching users and settings
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $tablesToClear = [
            'invoice_items',
            'invoices',
            'stock_audits',
            'products',
            'categories',
            'suppliers',
            'activity_logs'
        ];

        foreach ($tablesToClear as $tbl) {
            $pdo->exec("TRUNCATE TABLE `$tbl`");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Ensure starter catalog SQL file is still present for easy 1-click restore
        ensure_starter_catalog_sql_file($pdo, $backupDir);

        // 3. Log the reset action in fresh activity log
        log_activity($pdo, $user['id'], 'Cleared All Data', 'System', "Cleared all operational data. User accounts and store settings were preserved. Safety snapshot: {$snapshotName}");

        set_flash('success', "All operational data cleared successfully! User accounts and store settings remain intact. A safety snapshot ('{$snapshotName}') was automatically saved. You can restore the starter store catalog anytime using the 'Restore Starter Catalog' button.");
    } catch (Exception $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        set_flash('danger', 'Error clearing system data: ' . $e->getMessage());
    }
    header("Location: backup.php");
    exit;
}

// Fetch Database Health & Overview Statistics
$dbStats = [
    'tables_count' => 0,
    'total_size_mb' => 0,
    'products_count' => 0,
    'invoices_count' => 0,
    'users_count' => 0,
    'customers_count' => 0,
    'tables_detail' => []
];

try {
    $tblStmt = $pdo->prepare("SELECT table_name AS `name`, table_rows AS `rows`, 
                              round(((data_length + index_length) / 1024 / 1024), 2) AS `size_mb` 
                              FROM information_schema.TABLES 
                              WHERE table_schema = ? 
                              ORDER BY table_name ASC");
    $tblStmt->execute([$db_name]);
    $dbStats['tables_detail'] = $tblStmt->fetchAll(PDO::FETCH_ASSOC);
    $dbStats['tables_count'] = count($dbStats['tables_detail']);
    
    $totalMb = 0;
    foreach ($dbStats['tables_detail'] as $tbl) {
        $totalMb += (float)$tbl['size_mb'];
    }
    $dbStats['total_size_mb'] = round($totalMb, 2);

    $dbStats['products_count'] = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $dbStats['invoices_count'] = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
    $dbStats['users_count'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (Exception $e) {
    // fallback if information_schema query fails
}

// Ensure baseline starter catalog backup file is present in /backups
ensure_starter_catalog_sql_file($pdo, $backupDir);

// Scan Existing Stored Backups
$storedBackups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && preg_match('/\.sql$/i', $f)) {
            $fpath = $backupDir . '/' . $f;
            $storedBackups[] = [
                'name' => $f,
                'size' => filesize($fpath),
                'mtime' => filemtime($fpath),
                'is_snapshot' => (strpos($f, 'safety_snapshot') !== false),
                'is_starter' => ($f === 'starter_catalog_default.sql')
            ];
        }
    }
    // Sort newest first
    usort($storedBackups, function ($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });
}

// Helper: Format bytes
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

$pageTitle = 'Backup & Restore Database';
$pageIcon = 'bi-database-fill-gear';
require_once __DIR__ . '/includes/header.php';

$flash = get_flash();
?>

<div class="page-container" style="max-width: 1200px;">

    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">
                <i class="bi bi-database-fill-gear" style="color: var(--primary);"></i> Database Backup & Restore
            </h2>
            <p style="font-size: 13.5px; color: #64748b;">
                Protect store sales, inventory, and invoices with 1-click SQL backups and automated safety snapshots.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="backup.php?action=seed_starter_catalog" class="btn btn-warning" 
               onclick="return confirm('Restore Starter Store Catalog (24 products, 8 categories, 4 suppliers)?\n\nYour existing user accounts and store settings will be preserved safely.\n\nAn automated safety backup will be created first.');">
                <i class="bi bi-stars"></i> Restore Starter Catalog
            </a>
            <a href="backup.php?action=download_current" class="btn btn-primary">
                <i class="bi bi-cloud-arrow-down-fill"></i> Download Live Backup (.sql)
            </a>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="create_local">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-hdd-fill"></i> Save Backup on Server
                </button>
            </form>
        </div>
    </div>

    <?php if ($dbStats['products_count'] === 0): ?>
    <div style="background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <i class="bi bi-exclamation-circle-fill" style="color: #d97706; font-size: 22px;"></i>
            <div>
                <strong style="color: #92400e; font-size: 14px;">Store Catalog is Currently Empty (0 Products)</strong>
                <p style="font-size: 12.5px; color: #b45309; margin: 2px 0 0 0;">
                    Your database has 0 products and categories. You can restore the complete pre-configured store catalog (24 products, 8 categories, 4 suppliers) in 1 click or restore from a previous backup.
                </p>
            </div>
        </div>
        <a href="backup.php?action=seed_starter_catalog" class="btn btn-warning" onclick="return confirm('Load starter catalog with 24 products, 8 categories, and 4 suppliers?');">
            <i class="bi bi-stars"></i> 1-Click Restore Catalog
        </a>
    </div>
    <?php endif; ?>


    <!-- Database Quick Overview Stats -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="bi bi-database"></i></div>
            <div class="stat-info">
                <div class="stat-label">Active Database</div>
                <div class="stat-val" style="font-size: 17px;"><?= htmlspecialchars($db_name) ?></div>
                <div class="stat-desc"><?= $dbStats['tables_count'] ?> Tables (<?= $dbStats['total_size_mb'] ?> MB)</div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
            <div class="stat-info">
                <div class="stat-label">Catalog Products</div>
                <div class="stat-val"><?= number_format($dbStats['products_count']) ?></div>
                <div class="stat-desc">Inventory items & services</div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <div class="stat-info">
                <div class="stat-label">Sales Invoices</div>
                <div class="stat-val"><?= number_format($dbStats['invoices_count']) ?></div>
                <div class="stat-desc">Transaction records stored</div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-info">
                <div class="stat-label">Saved Backups</div>
                <div class="stat-val"><?= count($storedBackups) ?></div>
                <div class="stat-desc"><?= !empty($storedBackups) ? 'Latest: ' . date('M d, H:i', $storedBackups[0]['mtime']) : 'No backups saved yet' ?></div>
            </div>
        </div>
    </div>

    <!-- Main Grid: Upload Restore & Stored Backups -->
    <div style="display: grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: start; margin-bottom: 24px;">
        
        <!-- Left Column: Upload Restore Card & Safety Info -->
        <div>
            <!-- Upload & Restore Card -->
            <div class="content-card">
                <div class="card-header-flex">
                    <span class="card-title">
                        <i class="bi bi-upload" style="color: var(--secondary);"></i> Upload & Restore Backup
                    </span>
                </div>
                <div class="card-body">
                    <p style="font-size: 12.5px; color: #64748b; line-height: 1.5; margin-bottom: 16px;">
                        Select a MySQL <code>.sql</code> backup file from your computer to restore all tables, invoices, products, and user accounts.
                    </p>

                    <form method="POST" enctype="multipart/form-data" id="upload-restore-form" onsubmit="return confirmUploadRestore();">
                        <input type="hidden" name="action" value="upload_restore">
                        
                        <div class="form-group">
                            <label class="form-label">Select .sql Backup File *</label>
                            <input type="file" name="backup_file" id="backup-file-input" class="form-control" accept=".sql" required style="padding: 8px;">
                            <span style="font-size: 11px; color: #94a3b8; display: block; margin-top: 4px;">Max file size: 50MB (Standard SQL format)</span>
                        </div>

                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 12px; margin-bottom: 16px;">
                            <div style="display: flex; gap: 8px; font-size: 12px; color: #991b1b; line-height: 1.4;">
                                <i class="bi bi-shield-exclamation" style="font-size: 16px; flex-shrink: 0;"></i>
                                <div>
                                    <strong>Automated Safety Protection:</strong> A safety snapshot of current data will be saved before restoring, ensuring you can undo at any time.
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            <i class="bi bi-arrow-counterclockwise"></i> Upload & Restore Database
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Tips Card -->
            <div class="content-card">
                <div class="card-header-flex">
                    <span class="card-title" style="font-size: 14px;">
                        <i class="bi bi-lightbulb-fill" style="color: var(--warning);"></i> Best Practice Tips
                    </span>
                </div>
                <div class="card-body" style="font-size: 12.5px; color: #475569; line-height: 1.6;">
                    <ul style="padding-left: 18px; margin: 0;">
                        <li style="margin-bottom: 8px;">Create a backup at the end of every business shift or before running major inventory adjustments.</li>
                        <li style="margin-bottom: 8px;">Keep offline copies of your <code>.sql</code> backups on an external drive or USB thumb drive.</li>
                        <li style="margin-bottom: 0;">Local server backups are securely shielded from unauthorized web downloads via <code>.htaccess</code>.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column: Stored Backups Table -->
        <div>
            <div class="content-card">
                <div class="card-header-flex">
                    <div>
                        <span class="card-title">
                            <i class="bi bi-hdd-network-fill" style="color: var(--primary);"></i> Saved Backups on Server
                        </span>
                        <span style="font-size: 12px; color: #64748b;">Located in <code>/backups</code> directory</span>
                    </div>
                    <div>
                        <span class="badge badge-neutral"><?= count($storedBackups) ?> Files</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Backup Filename</th>
                                <th>Created Date</th>
                                <th>File Size</th>
                                <th>Type</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($storedBackups)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 36px; color: #94a3b8;">
                                    <i class="bi bi-folder-x" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                                    No saved backups found on the server. Click <strong>"Save Backup on Server"</strong> or <strong>"Download Live Backup"</strong> above to create one.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($storedBackups as $b): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="bi bi-file-earmark-code" style="color: var(--primary); font-size: 18px;"></i>
                                        <span style="font-family: var(--font-mono); font-size: 12.5px; font-weight: 600; color: #0f172a;">
                                            <?= htmlspecialchars($b['name']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="white-space: nowrap; font-size: 12.5px; color: #475569;">
                                    <?= date('Y-m-d H:i:s', $b['mtime']) ?>
                                </td>
                                <td style="font-family: var(--font-mono); font-size: 12px; white-space: nowrap;">
                                    <?= format_bytes($b['size']) ?>
                                </td>
                                <td>
                                    <?php if (!empty($b['is_starter'])): ?>
                                    <span class="badge badge-purple" title="Official starter store inventory catalog (24 items, 8 categories, 4 suppliers)">
                                        <i class="bi bi-stars"></i> Starter Store Catalog
                                    </span>
                                    <?php elseif ($b['is_snapshot']): ?>
                                    <span class="badge badge-warning" title="Automatically generated snapshot prior to restore">
                                        <i class="bi bi-shield-check"></i> Safety Snapshot
                                    </span>
                                    <?php else: ?>
                                    <span class="badge badge-success">
                                        <i class="bi bi-check-circle"></i> Standard Backup
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <!-- Download Button -->
                                    <a href="backup.php?action=download_file&file=<?= urlencode($b['name']) ?>" 
                                       class="btn btn-outline btn-sm" title="Download to PC" style="padding: 5px 9px;">
                                        <i class="bi bi-download"></i>
                                    </a>

                                    <!-- Restore Button -->
                                    <button type="button" class="btn btn-outline btn-sm" 
                                            onclick="openRestoreModal('<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')"
                                            title="Restore this backup" style="padding: 5px 9px; color: #b45309; border-color: #fde68a; background: #fffbeb;">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>

                                    <!-- Delete Button -->
                                    <button type="button" class="btn btn-outline btn-sm" 
                                            onclick="openDeleteModal('<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>')"
                                            title="Delete file" style="padding: 5px 9px; color: #ef4444; border-color: #fecaca; background: #fef2f2;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Database Tables Breakdown Collapse/Detail -->
            <div class="content-card">
                <div class="card-header-flex">
                    <span class="card-title" style="font-size: 14px;">
                        <i class="bi bi-table" style="color: var(--primary);"></i> Current Database Schema & Tables
                    </span>
                    <span class="badge badge-neutral"><?= count($dbStats['tables_detail']) ?> Tables</span>
                </div>
                <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                    <table class="custom-table" style="font-size: 12.5px;">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Estimated Records</th>
                                <th>Disk Usage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dbStats['tables_detail'] as $td): ?>
                            <tr>
                                <td>
                                    <code><?= htmlspecialchars($td['name']) ?></code>
                                </td>
                                <td><?= number_format((int)$td['rows']) ?></td>
                                <td style="font-family: var(--font-mono); font-size: 12px;"><?= $td['size_mb'] ?> MB</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Danger Zone: Clear System Data (Preserves Users & Store Settings) -->
    <div class="content-card" style="border: 1px solid #fecaca;">
        <div class="card-header-flex" style="background: #fff5f5; border-bottom: 1px solid #fee2e2;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="card-title" style="color: #991b1b; font-size: 15px;">
                    <i class="bi bi-radioactive" style="color: #ef4444;"></i> Danger Zone: Reset & Clear System Data
                </span>
                <span class="badge badge-danger">High Impact</span>
            </div>
            <div>
                <button type="button" class="btn btn-danger btn-sm" onclick="openClearDataModal()">
                    <i class="bi bi-trash3-fill"></i> Clear All Data
                </button>
            </div>
        </div>
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div style="max-width: 720px;">
                    <h5 style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">
                        Remove all operational data (Invoices, Products, Inventory, Categories, Suppliers & Audits)
                    </h5>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 12px; line-height: 1.5;">
                        This operation wipes all transactional sales data, stock logs, and product catalog records for a clean system start. 
                        <strong>Your User Accounts and Store Configuration are 100% preserved.</strong>
                    </p>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 12px;">
                        <span style="color: #15803d; font-weight: 600;">
                            <i class="bi bi-check-circle-fill"></i> Preserved: User Accounts & Staff Logins
                        </span>
                        <span style="color: #15803d; font-weight: 600;">
                            <i class="bi bi-check-circle-fill"></i> Preserved: Store Name & Receipt Settings
                        </span>
                        <span style="color: #b91c1c; font-weight: 600;">
                            <i class="bi bi-x-circle-fill"></i> Cleared: Products, Invoices & Stock History
                        </span>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-danger" onclick="openClearDataModal()">
                        <i class="bi bi-trash3-fill"></i> Clear All Data
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Clear Data Confirmation -->
<div class="modal-backdrop" id="clear-data-modal">
    <div class="modal-dialog" style="max-width: 520px;">
        <div class="modal-header" style="background: #fef2f2; border-bottom: 1px solid #fee2e2;">
            <div class="modal-title" style="color: #991b1b;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #dc2626;"></i> Confirm Clear System Data
            </div>
            <button type="button" class="modal-close" onclick="closeClearDataModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="clear_all_data">
            <div class="modal-body">
                <div style="background: #fef2f2; border-left: 4px solid #ef4444; border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
                    <p style="font-size: 13.5px; color: #7f1d1d; font-weight: 700; margin: 0 0 6px 0;">
                        Are you sure you want to clear all data?
                    </p>
                    <p style="font-size: 12.5px; color: #991b1b; margin: 0; line-height: 1.5;">
                        This will permanently delete all <strong>Invoices</strong>, <strong>Invoice Items</strong>, <strong>Products</strong>, <strong>Stock Audits</strong>, <strong>Categories</strong>, <strong>Suppliers</strong>, and <strong>Activity Logs</strong>.
                    </p>
                </div>

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 12px 14px; margin-bottom: 18px;">
                    <div style="font-size: 12.5px; color: #166534; line-height: 1.5;">
                        <i class="bi bi-shield-check" style="font-size: 16px; margin-right: 4px;"></i>
                        <strong>Guaranteed Safety:</strong> <strong>User Accounts</strong> and <strong>Store Settings</strong> will remain intact. An automated safety snapshot of the entire database will be saved before clearing.
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13px; color: #334155;">
                        <input type="checkbox" id="confirm-clear-checkbox" required style="margin-top: 3px; width: 16px; height: 16px; cursor: pointer;">
                        <span>I understand this will remove all operational data and keep only user accounts and store settings.</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer" style="background: #fafbfc;">
                <button type="button" class="btn btn-outline" onclick="closeClearDataModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash3-fill"></i> Yes, Clear All Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal-backdrop" id="restore-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-exclamation-triangle-fill" style="color: var(--danger);"></i> Confirm Database Restore
            </div>
            <button type="button" class="modal-close" onclick="closeRestoreModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="restore_server_file">
            <input type="hidden" name="file" id="modal-restore-filename" value="">

            <div class="modal-body">
                <p style="font-size: 14px; color: #334155; margin-bottom: 16px;">
                    Are you sure you want to restore the database from this backup?
                </p>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">Selected Backup:</span>
                    <strong style="font-family: var(--font-mono); color: #0f172a; font-size: 13.5px;" id="modal-display-filename"></strong>
                </div>

                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 14px;">
                    <div style="display:flex; gap:10px; color:#991b1b; font-size:12.5px; line-height:1.5;">
                        <i class="bi bi-shield-lock-fill" style="font-size:18px; flex-shrink:0;"></i>
                        <div>
                            <strong>Warning:</strong> Restoring will overwrite existing records with the state from this backup file.
                            An automated safety snapshot of the live database will be created first.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeRestoreModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-arrow-counterclockwise"></i> Yes, Proceed with Restore
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-backdrop" id="delete-modal">
    <div class="modal-dialog" style="max-width: 480px;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-trash-fill" style="color: var(--danger);"></i> Delete Backup File
            </div>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="delete_file">
            <input type="hidden" name="file" id="modal-delete-filename" value="">

            <div class="modal-body">
                <p style="font-size: 13.5px; color: #334155; margin-bottom: 12px;">
                    Are you sure you want to permanently delete this backup file from the server?
                </p>
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; font-family: var(--font-mono); font-size: 13px; color: #0f172a;" id="modal-delete-display"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Delete Backup
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRestoreModal(filename) {
    document.getElementById('modal-restore-filename').value = filename;
    document.getElementById('modal-display-filename').textContent = filename;
    document.getElementById('restore-modal').classList.add('show');
}

function closeRestoreModal() {
    document.getElementById('restore-modal').classList.remove('show');
}

function openDeleteModal(filename) {
    document.getElementById('modal-delete-filename').value = filename;
    document.getElementById('modal-delete-display').textContent = filename;
    document.getElementById('delete-modal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.remove('show');
}

function confirmUploadRestore() {
    const fileInput = document.getElementById('backup-file-input');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Please select a .sql file to upload.');
        return false;
    }
    const fileName = fileInput.files[0].name;
    return confirm('Are you sure you want to restore the database from "' + fileName + '"?\n\nExisting database tables and records will be updated with data from this file.\n\nAn automated safety backup will be created before proceeding.');
}

function openClearDataModal() {
    const cb = document.getElementById('confirm-clear-checkbox');
    if (cb) cb.checked = false;
    document.getElementById('clear-data-modal').classList.add('show');
}

function closeClearDataModal() {
    document.getElementById('clear-data-modal').classList.remove('show');
}

// Close modals when clicking outside modal-dialog
window.addEventListener('click', function(e) {
    const restoreModal = document.getElementById('restore-modal');
    const deleteModal = document.getElementById('delete-modal');
    const clearModal = document.getElementById('clear-data-modal');
    if (e.target === restoreModal) closeRestoreModal();
    if (e.target === deleteModal) closeDeleteModal();
    if (e.target === clearModal) closeClearDataModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
