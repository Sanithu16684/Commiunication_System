<?php
// seed.php - Database Test Data Seeder for Metro POS & Communication System
// Seeds Categories, Suppliers, Products, and Invoices for dates: 10/13, 10/14, 10/15, 10/16, 10/17

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
$action = $_POST['action'] ?? $_GET['action'] ?? ($isCli ? 'run' : '');
$clearOldInvoices = isset($_POST['clear_invoices']) && $_POST['clear_invoices'] === '1';

// Seed Function Definition
function run_database_seeder($pdo, $clearExistingInvoices = false) {
    $stats = [
        'categories' => 0,
        'suppliers' => 0,
        'products' => 0,
        'invoices' => 0,
        'invoice_items' => 0,
        'total_revenue' => 0.0,
        'dates_breakdown' => [],
        'invoices_list' => []
    ];

    $pdo->beginTransaction();

    try {
        // Ensure baseline tables exist
        bootstrap_database($pdo);

        // Fetch or assign system user for seed records
        $userId = (int)($pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn() ?: 1);
        $cashierId = (int)($pdo->query("SELECT id FROM users WHERE role = 'cashier' LIMIT 1")->fetchColumn() ?: $userId);

        // ========================================================
        // 1. SEED CATEGORIES (Goods & Services)
        // ========================================================
        $categoriesData = [
            ['name' => 'Books & Novels', 'type' => 'goods', 'icon' => 'bi-book'],
            ['name' => 'Exercise Books & Notebooks', 'type' => 'goods', 'icon' => 'bi-journal-bookmark'],
            ['name' => 'Stationery & Writing', 'type' => 'goods', 'icon' => 'bi-pen'],
            ['name' => 'Office & School Supplies', 'type' => 'goods', 'icon' => 'bi-briefcase'],
            ['name' => 'Art & Craft Materials', 'type' => 'goods', 'icon' => 'bi-palette'],
            ['name' => 'Printing & Photocopy Services', 'type' => 'service', 'icon' => 'bi-printer'],
            ['name' => 'Lamination & Binding', 'type' => 'service', 'icon' => 'bi-shield-shaded'],
            ['name' => 'Mobile Reload & Telecom', 'type' => 'service', 'icon' => 'bi-phone']
        ];

        $catStmt = $pdo->prepare("INSERT INTO categories (name, type, icon) 
                                 VALUES (?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE type = VALUES(type), icon = VALUES(icon)");
        foreach ($categoriesData as $cat) {
            $catStmt->execute([$cat['name'], $cat['type'], $cat['icon']]);
        }
        $stats['categories'] = count($categoriesData);

        // Map Category IDs
        $catMap = [];
        $cRows = $pdo->query("SELECT id, name FROM categories")->fetchAll();
        foreach ($cRows as $r) {
            $catMap[$r['name']] = (int)$r['id'];
        }

        // ========================================================
        // 2. SEED SUPPLIERS
        // ========================================================
        $suppliersData = [
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
            ],
            [
                'name' => 'Master Office Supplies & Paper',
                'company' => 'Master Paper & Stationery Ltd',
                'phone' => '+94 11 258 9012',
                'email' => 'contact@masterpaper.lk',
                'address' => 'No. 12, Galle Road, Colombo 04'
            ]
        ];

        $suppMap = [];
        $suppCheck = $pdo->prepare("SELECT id FROM suppliers WHERE name = ?");
        $suppInsert = $pdo->prepare("INSERT INTO suppliers (name, company, phone, email, address) VALUES (?, ?, ?, ?, ?)");
        $suppUpdate = $pdo->prepare("UPDATE suppliers SET company = ?, phone = ?, email = ?, address = ? WHERE id = ?");

        foreach ($suppliersData as $supp) {
            $suppCheck->execute([$supp['name']]);
            $existingId = $suppCheck->fetchColumn();
            if ($existingId) {
                $suppUpdate->execute([$supp['company'], $supp['phone'], $supp['email'], $supp['address'], $existingId]);
                $suppMap[$supp['name']] = (int)$existingId;
            } else {
                $suppInsert->execute([$supp['name'], $supp['company'], $supp['phone'], $supp['email'], $supp['address']]);
                $suppMap[$supp['name']] = (int)$pdo->lastInsertId();
            }
        }
        $stats['suppliers'] = count($suppliersData);

        // ========================================================
        // 3. SEED PRODUCTS
        // ========================================================
        $productsData = [
            // Physical Books & Stationery
            ['barcode' => '9789551234011', 'sku' => 'BK-ATLAS-CR80', 'name' => 'Atlas CR 80 Pages Single Rule Exercise Book', 'category_name' => 'Exercise Books & Notebooks', 'item_type' => 'physical', 'cost_price' => 120.00, 'selling_price' => 160.00, 'stock_qty' => 180, 'min_stock_alert' => 20, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
            ['barcode' => '9789551234028', 'sku' => 'BK-ATLAS-CR120', 'name' => 'Atlas CR 120 Pages Square Rule Book', 'category_name' => 'Exercise Books & Notebooks', 'item_type' => 'physical', 'cost_price' => 170.00, 'selling_price' => 220.00, 'stock_qty' => 140, 'min_stock_alert' => 15, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
            ['barcode' => '9789551234035', 'sku' => 'BK-ATLAS-CR200', 'name' => 'Atlas CR 200 Pages Single Rule Hardcover Book', 'category_name' => 'Exercise Books & Notebooks', 'item_type' => 'physical', 'cost_price' => 260.00, 'selling_price' => 340.00, 'stock_qty' => 95, 'min_stock_alert' => 10, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
            ['barcode' => '9789551234042', 'sku' => 'BK-DRAW-A4', 'name' => 'Atlas A4 Drawing Book 40 Pages', 'category_name' => 'Art & Craft Materials', 'item_type' => 'physical', 'cost_price' => 110.00, 'selling_price' => 150.00, 'stock_qty' => 85, 'min_stock_alert' => 12, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
            ['barcode' => '4792011001015', 'sku' => 'PEN-ATLAS-CHOC-BLU', 'name' => 'Atlas Choc Ballpoint Pen 0.7mm (Blue)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 22.00, 'selling_price' => 35.00, 'stock_qty' => 450, 'min_stock_alert' => 50, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
            ['barcode' => '4792011001022', 'sku' => 'PEN-ATLAS-CHOC-RED', 'name' => 'Atlas Choc Ballpoint Pen 0.7mm (Red)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 22.00, 'selling_price' => 35.00, 'stock_qty' => 320, 'min_stock_alert' => 40, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
            ['barcode' => '4792011001039', 'sku' => 'PEN-ATLAS-CHOC-BLK', 'name' => 'Atlas Choc Ballpoint Pen 0.7mm (Black)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 22.00, 'selling_price' => 35.00, 'stock_qty' => 380, 'min_stock_alert' => 40, 'unit' => 'pcs', 'supplier_name' => 'Atlas Axillia PLC'],
            ['barcode' => '8901234567890', 'sku' => 'PEN-PILOT-G2', 'name' => 'Pilot G2 0.7mm Retractable Gel Ink Pen (Black)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 280.00, 'selling_price' => 380.00, 'stock_qty' => 75, 'min_stock_alert' => 10, 'unit' => 'pcs', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
            ['barcode' => '4792011002012', 'sku' => 'PENCIL-NATARAJ-10', 'name' => 'Nataraj 2B Velvet Writing Pencils (Pack of 10)', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 140.00, 'selling_price' => 200.00, 'stock_qty' => 110, 'min_stock_alert' => 15, 'unit' => 'pack', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
            ['barcode' => '4792011003019', 'sku' => 'ERA-PELIKAN-DF', 'name' => 'Pelikan Dust-Free High Grade Vinyl Eraser', 'category_name' => 'Stationery & Writing', 'item_type' => 'physical', 'cost_price' => 30.00, 'selling_price' => 50.00, 'stock_qty' => 160, 'min_stock_alert' => 25, 'unit' => 'pcs', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
            ['barcode' => '4792011004016', 'sku' => 'RUL-MAPED-30CM', 'name' => 'Maped 30cm Shatterproof Transparent Ruler', 'category_name' => 'Office & School Supplies', 'item_type' => 'physical', 'cost_price' => 55.00, 'selling_price' => 90.00, 'stock_qty' => 125, 'min_stock_alert' => 15, 'unit' => 'pcs', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
            ['barcode' => '8901030012345', 'sku' => 'GLUE-FEVI-50G', 'name' => 'Fevicol MR Synthetic Craft Adhesive Glue 50g', 'category_name' => 'Art & Craft Materials', 'item_type' => 'physical', 'cost_price' => 75.00, 'selling_price' => 110.00, 'stock_qty' => 80, 'min_stock_alert' => 10, 'unit' => 'bottle', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
            ['barcode' => '8901030098765', 'sku' => 'SCIS-MAPED-13', 'name' => 'Maped Student Safety Scissors 13cm', 'category_name' => 'Office & School Supplies', 'item_type' => 'physical', 'cost_price' => 120.00, 'selling_price' => 180.00, 'stock_qty' => 60, 'min_stock_alert' => 8, 'unit' => 'pcs', 'supplier_name' => 'Richard Pieris & Co (Arpico)'],
            ['barcode' => '8902001004561', 'sku' => 'PAP-DOUBLE-A4', 'name' => 'Double A A4 80gsm Premium Copier Paper Ream (500 Sheets)', 'category_name' => 'Office & School Supplies', 'item_type' => 'physical', 'cost_price' => 1650.00, 'selling_price' => 1950.00, 'stock_qty' => 55, 'min_stock_alert' => 10, 'unit' => 'ream', 'supplier_name' => 'Master Office Supplies & Paper'],
            ['barcode' => '4791001005555', 'sku' => 'STAP-KANG-10', 'name' => 'Kangaro No. 10 Office Stapler with 1000 Pins', 'category_name' => 'Office & School Supplies', 'item_type' => 'physical', 'cost_price' => 320.00, 'selling_price' => 450.00, 'stock_qty' => 45, 'min_stock_alert' => 6, 'unit' => 'set', 'supplier_name' => 'Master Office Supplies & Paper'],
            ['barcode' => '9789552119999', 'sku' => 'BK-SIN-ENG-DIC', 'name' => 'Gunasena Sinhala-English Pocket Comprehensive Dictionary', 'category_name' => 'Books & Novels', 'item_type' => 'physical', 'cost_price' => 650.00, 'selling_price' => 850.00, 'stock_qty' => 35, 'min_stock_alert' => 5, 'unit' => 'copy', 'supplier_name' => 'Sarasavi Bookshop & Publishers'],
            ['barcode' => '9789556520011', 'sku' => 'BK-MADOL-DOOVA', 'name' => 'Madol Doova (Original Sinhala Classic by Martin Wickramasinghe)', 'category_name' => 'Books & Novels', 'item_type' => 'physical', 'cost_price' => 350.00, 'selling_price' => 450.00, 'stock_qty' => 40, 'min_stock_alert' => 5, 'unit' => 'copy', 'supplier_name' => 'Sarasavi Bookshop & Publishers'],
            ['barcode' => '9780521189064', 'sku' => 'BK-ENG-GRAM-INT', 'name' => 'English Grammar in Use (Intermediate 5th Edition)', 'category_name' => 'Books & Novels', 'item_type' => 'physical', 'cost_price' => 1100.00, 'selling_price' => 1450.00, 'stock_qty' => 25, 'min_stock_alert' => 4, 'unit' => 'copy', 'supplier_name' => 'Sarasavi Bookshop & Publishers'],

            // Services
            ['barcode' => 'SRV-XER-BW', 'sku' => 'SRV-XER-BW', 'name' => 'B&W Photocopy (Single Side)', 'category_name' => 'Printing & Photocopy Services', 'item_type' => 'service', 'cost_price' => 2.50, 'selling_price' => 10.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'pages', 'supplier_name' => 'Master Office Supplies & Paper'],
            ['barcode' => 'SRV-XER-DS', 'sku' => 'SRV-XER-DS', 'name' => 'B&W Photocopy (Double Side)', 'category_name' => 'Printing & Photocopy Services', 'item_type' => 'service', 'cost_price' => 4.00, 'selling_price' => 15.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'pages', 'supplier_name' => 'Master Office Supplies & Paper'],
            ['barcode' => 'SRV-PRT-CLR', 'sku' => 'SRV-PRT-CLR', 'name' => 'Color Printout High Quality A4', 'category_name' => 'Printing & Photocopy Services', 'item_type' => 'service', 'cost_price' => 12.00, 'selling_price' => 40.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'pages', 'supplier_name' => 'Master Office Supplies & Paper'],
            ['barcode' => 'SRV-LAM-A4', 'sku' => 'SRV-LAM-A4', 'name' => 'A4 Thermal Document Lamination', 'category_name' => 'Lamination & Binding', 'item_type' => 'service', 'cost_price' => 35.00, 'selling_price' => 100.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'doc', 'supplier_name' => 'Master Office Supplies & Paper'],
            ['barcode' => 'SRV-BIND-SP', 'sku' => 'SRV-BIND-SP', 'name' => 'Spiral Document Binding (Up to 100 pages)', 'category_name' => 'Lamination & Binding', 'item_type' => 'service', 'cost_price' => 60.00, 'selling_price' => 180.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'doc', 'supplier_name' => 'Master Office Supplies & Paper'],
            ['barcode' => 'SRV-SCAN-PDF', 'sku' => 'SRV-SCAN-PDF', 'name' => 'Document Scanning & Email (Per Page)', 'category_name' => 'Printing & Photocopy Services', 'item_type' => 'service', 'cost_price' => 0.00, 'selling_price' => 30.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'pages', 'supplier_name' => 'Master Office Supplies & Paper'],

            // Telecom Reloads
            ['barcode' => 'TEL-DIA-REL', 'sku' => 'TEL-DIA-REL', 'name' => 'Dialog Mobile Reload / DTV Bill Payment', 'category_name' => 'Mobile Reload & Telecom', 'item_type' => 'reload', 'cost_price' => 95.00, 'selling_price' => 100.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'unit', 'supplier_name' => 'Dialog & Mobitel Telecom Distributor'],
            ['barcode' => 'TEL-MOB-REL', 'sku' => 'TEL-MOB-REL', 'name' => 'Mobitel Mobile Reload / Bill Payment', 'category_name' => 'Mobile Reload & Telecom', 'item_type' => 'reload', 'cost_price' => 95.00, 'selling_price' => 100.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'unit', 'supplier_name' => 'Dialog & Mobitel Telecom Distributor'],
            ['barcode' => 'TEL-AIR-REL', 'sku' => 'TEL-AIR-REL', 'name' => 'Airtel Prepaid Mobile Reload', 'category_name' => 'Mobile Reload & Telecom', 'item_type' => 'reload', 'cost_price' => 95.00, 'selling_price' => 100.00, 'stock_qty' => 99999, 'min_stock_alert' => 0, 'unit' => 'unit', 'supplier_name' => 'Dialog & Mobitel Telecom Distributor']
        ];

        $prodStmt = $pdo->prepare("INSERT INTO products 
            (barcode, sku, name, category_id, item_type, cost_price, selling_price, stock_qty, min_stock_alert, unit, supplier_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE
                sku = VALUES(sku),
                name = VALUES(name),
                category_id = VALUES(category_id),
                item_type = VALUES(item_type),
                cost_price = VALUES(cost_price),
                selling_price = VALUES(selling_price),
                stock_qty = VALUES(stock_qty),
                min_stock_alert = VALUES(min_stock_alert),
                unit = VALUES(unit),
                supplier_id = VALUES(supplier_id),
                status = 'active'");

        $seededProds = 0;
        foreach ($productsData as $p) {
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
            $seededProds++;
        }
        $stats['products'] = $seededProds;

        // Fetch all products into memory lookup by barcode/sku
        $productLookup = [];
        $pRows = $pdo->query("SELECT id, barcode, sku, name, item_type, cost_price, selling_price, stock_qty FROM products")->fetchAll();
        foreach ($pRows as $pr) {
            $productLookup[$pr['barcode']] = $pr;
            $productLookup[$pr['sku']] = $pr;
        }

        // ========================================================
        // 4. SEED INVOICES FOR 10/13, 10/14, 10/15, 10/16, 10/17
        // ========================================================
        if ($clearExistingInvoices) {
            // Remove test invoices from 2026-10-13 to 2026-10-17 if requested
            $pdo->exec("DELETE FROM invoices WHERE DATE(created_at) BETWEEN '2026-10-13' AND '2026-10-17'");
        }

        // Target Date Invoices Blueprint
        $invoicesData = [
            // -----------------------------------------------------------------
            // DATE: 2026-10-13
            // -----------------------------------------------------------------
            [
                'invoice_no' => 'INV-20261013-0001',
                'date' => '2026-10-13 09:30:15',
                'customer_name' => 'Kasun Perera',
                'customer_phone' => '+94 77 123 4567',
                'user_id' => $userId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 600.00,
                'notes' => 'Stationery purchases for school term',
                'items' => [
                    ['barcode' => '9789551234011', 'qty' => 3], // Atlas CR 80pgs (160 * 3 = 480)
                    ['barcode' => '4792011001015', 'qty' => 2], // Atlas Choc Blue (35 * 2 = 70)
                ] // Subtotal: 550, Total: 550, Change: 50
            ],
            [
                'invoice_no' => 'INV-20261013-0002',
                'date' => '2026-10-13 11:45:20',
                'customer_name' => 'Walk-in Customer',
                'customer_phone' => '',
                'user_id' => $cashierId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 500.00,
                'notes' => 'Photocopy and document lamination counter service',
                'items' => [
                    ['barcode' => 'SRV-XER-BW', 'qty' => 15], // B&W Photocopy (10 * 15 = 150)
                    ['barcode' => 'SRV-LAM-A4', 'qty' => 2],   // A4 Lamination (100 * 2 = 200)
                ] // Subtotal: 350, Total: 350, Change: 150
            ],
            [
                'invoice_no' => 'INV-20261013-0003',
                'date' => '2026-10-13 14:15:33',
                'customer_name' => 'Dinithi Fernando',
                'customer_phone' => '+94 71 456 7890',
                'user_id' => $cashierId,
                'payment_method' => 'card',
                'discount_type' => 'fixed',
                'discount_amount' => 100.00,
                'tax_amount' => 0.00,
                'amount_paid' => 2300.00,
                'notes' => 'Office stationery supplies order (discount applied)',
                'items' => [
                    ['barcode' => '8902001004561', 'qty' => 1], // Double A Copier Paper (1950)
                    ['barcode' => '4791001005555', 'qty' => 1], // Kangaro Stapler (450)
                ] // Subtotal: 2400, Total: 2300, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261013-0004',
                'date' => '2026-10-13 17:20:45',
                'customer_name' => 'Sunil Shantha',
                'customer_phone' => '+94 78 334 4556',
                'user_id' => $userId,
                'payment_method' => 'qr',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 605.00,
                'notes' => 'LankaQR mobile payment verified',
                'items' => [
                    ['barcode' => 'TEL-DIA-REL', 'qty' => 5],   // Dialog Reload (100 * 5 = 500)
                    ['barcode' => '4792011001039', 'qty' => 3], // Atlas Choc Black (35 * 3 = 105)
                ] // Subtotal: 605, Total: 605, Change: 0
            ],

            // -----------------------------------------------------------------
            // DATE: 2026-10-14
            // -----------------------------------------------------------------
            [
                'invoice_no' => 'INV-20261014-0001',
                'date' => '2026-10-14 09:12:10',
                'customer_name' => 'Walk-in Customer',
                'customer_phone' => '',
                'user_id' => $userId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 1000.00,
                'notes' => 'Morning student stationery packs',
                'items' => [
                    ['barcode' => '9789551234028', 'qty' => 2], // Atlas CR 120pgs (220 * 2 = 440)
                    ['barcode' => '4792011002012', 'qty' => 1], // Nataraj Pencils 10s (200)
                    ['barcode' => '4792011003019', 'qty' => 2], // Pelikan Eraser (50 * 2 = 100)
                ] // Subtotal: 740, Total: 740, Change: 260
            ],
            [
                'invoice_no' => 'INV-20261014-0002',
                'date' => '2026-10-14 12:35:40',
                'customer_name' => 'Anusha Wickramasinghe',
                'customer_phone' => '+94 76 223 3445',
                'user_id' => $cashierId,
                'payment_method' => 'card',
                'discount_type' => 'fixed',
                'discount_amount' => 50.00,
                'tax_amount' => 0.00,
                'amount_paid' => 1250.00,
                'notes' => 'Novels and literature reader purchase',
                'items' => [
                    ['barcode' => '9789552119999', 'qty' => 1], // Gunasena Dictionary (850)
                    ['barcode' => '9789556520011', 'qty' => 1], // Madol Doova (450)
                ] // Subtotal: 1300, Total: 1250, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261014-0003',
                'date' => '2026-10-14 15:50:22',
                'customer_name' => 'Nuwan Pradeep',
                'customer_phone' => '+94 77 998 8776',
                'user_id' => $cashierId,
                'payment_method' => 'qr',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 500.00,
                'notes' => 'Project report color printing and spiral binding',
                'items' => [
                    ['barcode' => 'SRV-PRT-CLR', 'qty' => 8], // Color Print (40 * 8 = 320)
                    ['barcode' => 'SRV-BIND-SP', 'qty' => 1], // Spiral Binding (180)
                ] // Subtotal: 500, Total: 500, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261014-0004',
                'date' => '2026-10-14 18:10:15',
                'customer_name' => 'Walk-in Customer',
                'customer_phone' => '',
                'user_id' => $userId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 1500.00,
                'notes' => 'Mobile reload & gel pen',
                'items' => [
                    ['barcode' => 'TEL-MOB-REL', 'qty' => 10], // Mobitel Reload (100 * 10 = 1000)
                    ['barcode' => '8901234567890', 'qty' => 1],  // Pilot G2 Gel Pen (380)
                ] // Subtotal: 1380, Total: 1380, Change: 120
            ],

            // -----------------------------------------------------------------
            // DATE: 2026-10-15
            // -----------------------------------------------------------------
            [
                'invoice_no' => 'INV-20261015-0001',
                'date' => '2026-10-15 10:05:30',
                'customer_name' => 'Dilshan Jayawardena',
                'customer_phone' => '+94 72 345 6789',
                'user_id' => $cashierId,
                'payment_method' => 'transfer',
                'discount_type' => 'fixed',
                'discount_amount' => 150.00,
                'tax_amount' => 0.00,
                'amount_paid' => 4440.00,
                'notes' => 'Bulk art supplies & paper reams (Online Bank Transfer)',
                'items' => [
                    ['barcode' => '8902001004561', 'qty' => 2], // Double A Copier Paper (1950 * 2 = 3900)
                    ['barcode' => '8901030098765', 'qty' => 2], // Maped Scissors (180 * 2 = 360)
                    ['barcode' => '8901030012345', 'qty' => 3], // Fevicol Glue (110 * 3 = 330)
                ] // Subtotal: 4590, Total: 4440, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261015-0002',
                'date' => '2026-10-15 13:18:45',
                'customer_name' => 'Walk-in Customer',
                'customer_phone' => '',
                'user_id' => $userId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 500.00,
                'notes' => 'Double-side photocopy copies & scanning',
                'items' => [
                    ['barcode' => 'SRV-XER-DS', 'qty' => 24],  // Double-side Photocopy (15 * 24 = 360)
                    ['barcode' => 'SRV-SCAN-PDF', 'qty' => 4], // Document Scanning (30 * 4 = 120)
                ] // Subtotal: 480, Total: 480, Change: 20
            ],
            [
                'invoice_no' => 'INV-20261015-0003',
                'date' => '2026-10-15 16:40:12',
                'customer_name' => 'Malith Silva',
                'customer_phone' => '+94 75 667 7889',
                'user_id' => $cashierId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 1200.00,
                'notes' => 'Hardcover books and drawing books for art students',
                'items' => [
                    ['barcode' => '9789551234035', 'qty' => 2], // Atlas CR 200pgs Hardcover (340 * 2 = 680)
                    ['barcode' => '9789551234042', 'qty' => 2], // Atlas A4 Drawing Book (150 * 2 = 300)
                    ['barcode' => '4792011004016', 'qty' => 2], // Maped 30cm Ruler (90 * 2 = 180)
                ] // Subtotal: 1160, Total: 1160, Change: 40
            ],
            [
                'invoice_no' => 'INV-20261015-0004',
                'date' => '2026-10-15 19:02:50',
                'customer_name' => 'Ruwan Jayasinghe',
                'customer_phone' => '+94 77 554 4332',
                'user_id' => $userId,
                'payment_method' => 'card',
                'discount_type' => 'fixed',
                'discount_amount' => 110.00,
                'tax_amount' => 0.00,
                'amount_paid' => 2100.00,
                'notes' => 'Advanced English Grammar book & Pilot pens',
                'items' => [
                    ['barcode' => '9780521189064', 'qty' => 1], // English Grammar in Use (1450)
                    ['barcode' => '8901234567890', 'qty' => 2], // Pilot G2 (380 * 2 = 760)
                ] // Subtotal: 2210, Total: 2100, Change: 0
            ],

            // -----------------------------------------------------------------
            // DATE: 2026-10-16
            // -----------------------------------------------------------------
            [
                'invoice_no' => 'INV-20261016-0001',
                'date' => '2026-10-16 09:45:00',
                'customer_name' => 'Chathura Bandara',
                'customer_phone' => '+94 71 223 3990',
                'user_id' => $cashierId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 25.00,
                'tax_amount' => 0.00,
                'amount_paid' => 1000.00,
                'notes' => 'Pen sets & pencils bulk box',
                'items' => [
                    ['barcode' => '4792011001015', 'qty' => 10], // Atlas Choc Blue (35 * 10 = 350)
                    ['barcode' => '4792011001022', 'qty' => 5],  // Atlas Choc Red (35 * 5 = 175)
                    ['barcode' => '4792011002012', 'qty' => 2],  // Nataraj Pencils (200 * 2 = 400)
                ] // Subtotal: 925, Total: 900, Change: 100
            ],
            [
                'invoice_no' => 'INV-20261016-0002',
                'date' => '2026-10-16 11:55:18',
                'customer_name' => 'Walk-in Customer',
                'customer_phone' => '',
                'user_id' => $userId,
                'payment_method' => 'qr',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 1000.00,
                'notes' => 'Digital color certificates printout and lamination',
                'items' => [
                    ['barcode' => 'SRV-PRT-CLR', 'qty' => 15], // Color Print (40 * 15 = 600)
                    ['barcode' => 'SRV-LAM-A4', 'qty' => 4],   // A4 Lamination (100 * 4 = 400)
                ] // Subtotal: 1000, Total: 1000, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261016-0003',
                'date' => '2026-10-16 14:30:25',
                'customer_name' => 'Chamari Wickramasinghe',
                'customer_phone' => '+94 77 665 5443',
                'user_id' => $cashierId,
                'payment_method' => 'card',
                'discount_type' => 'fixed',
                'discount_amount' => 90.00,
                'tax_amount' => 0.00,
                'amount_paid' => 2300.00,
                'notes' => 'Educational novel sets & exercise books',
                'items' => [
                    ['barcode' => '9789556520011', 'qty' => 2], // Madol Doova (450 * 2 = 900)
                    ['barcode' => '9789552119999', 'qty' => 1], // Gunasena Dictionary (850)
                    ['barcode' => '9789551234011', 'qty' => 4], // Atlas CR 80pgs (160 * 4 = 640)
                ] // Subtotal: 2390, Total: 2300, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261016-0004',
                'date' => '2026-10-16 17:42:30',
                'customer_name' => 'Walk-in Customer',
                'customer_phone' => '',
                'user_id' => $userId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 50.00,
                'tax_amount' => 0.00,
                'amount_paid' => 3500.00,
                'notes' => 'Prepaid reloads and paper pack',
                'items' => [
                    ['barcode' => 'TEL-DIA-REL', 'qty' => 15],  // Dialog Reload (100 * 15 = 1500)
                    ['barcode' => '8902001004561', 'qty' => 1], // Double A Copier Paper (1950)
                ] // Subtotal: 3450, Total: 3400, Change: 100
            ],

            // -----------------------------------------------------------------
            // DATE: 2026-10-17
            // -----------------------------------------------------------------
            [
                'invoice_no' => 'INV-20261017-0001',
                'date' => '2026-10-17 09:10:20',
                'customer_name' => 'Kavinda Rathnayake',
                'customer_phone' => '+94 70 112 2334',
                'user_id' => $cashierId,
                'payment_method' => 'cash',
                'discount_type' => 'fixed',
                'discount_amount' => 40.00,
                'tax_amount' => 0.00,
                'amount_paid' => 2000.00,
                'notes' => 'Higher grade notebooks bundle and stationery',
                'items' => [
                    ['barcode' => '9789551234028', 'qty' => 4], // Atlas CR 120pgs (220 * 4 = 880)
                    ['barcode' => '9789551234035', 'qty' => 3], // Atlas CR 200pgs Hardcover (340 * 3 = 1020)
                    ['barcode' => '4792011001039', 'qty' => 4], // Atlas Choc Black (35 * 4 = 140)
                ] // Subtotal: 2040, Total: 2000, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261017-0002',
                'date' => '2026-10-17 12:20:45',
                'customer_name' => 'Walk-in Customer',
                'customer_phone' => '',
                'user_id' => $userId,
                'payment_method' => 'qr',
                'discount_type' => 'fixed',
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'amount_paid' => 810.00,
                'notes' => 'Exam past papers photocopying, binding and digital scanning',
                'items' => [
                    ['barcode' => 'SRV-XER-BW', 'qty' => 30],  // B&W Photocopy (10 * 30 = 300)
                    ['barcode' => 'SRV-BIND-SP', 'qty' => 2], // Spiral Binding (180 * 2 = 360)
                    ['barcode' => 'SRV-SCAN-PDF', 'qty' => 5], // Scanning (30 * 5 = 150)
                ] // Subtotal: 810, Total: 810, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261017-0003',
                'date' => '2026-10-17 15:05:12',
                'customer_name' => 'Tharindu Gunasekara',
                'customer_phone' => '+94 77 443 3221',
                'user_id' => $cashierId,
                'payment_method' => 'transfer',
                'discount_type' => 'fixed',
                'discount_amount' => 250.00,
                'tax_amount' => 0.00,
                'amount_paid' => 6500.00,
                'notes' => 'Commercial office bulk printing paper & heavy-duty staplers',
                'items' => [
                    ['barcode' => '8902001004561', 'qty' => 3], // Double A Copier Paper (1950 * 3 = 5850)
                    ['barcode' => '4791001005555', 'qty' => 2], // Kangaro Stapler (450 * 2 = 900)
                ] // Subtotal: 6750, Total: 6500, Change: 0
            ],
            [
                'invoice_no' => 'INV-20261017-0004',
                'date' => '2026-10-17 18:15:40',
                'customer_name' => 'Dinithi Fernando',
                'customer_phone' => '+94 71 456 7890',
                'user_id' => $userId,
                'payment_method' => 'card',
                'discount_type' => 'fixed',
                'discount_amount' => 140.00,
                'tax_amount' => 0.00,
                'amount_paid' => 2600.00,
                'notes' => 'Evening bookstore sale with POS card terminal',
                'items' => [
                    ['barcode' => '9780521189064', 'qty' => 1], // English Grammar in Use (1450)
                    ['barcode' => '8901234567890', 'qty' => 3], // Pilot G2 (380 * 3 = 1140)
                    ['barcode' => '4792011003019', 'qty' => 3], // Pelikan Eraser (50 * 3 = 150)
                ] // Subtotal: 2740, Total: 2600, Change: 0
            ]
        ];

        // Prepare statements for inserting invoices and invoice_items
        $invCheck = $pdo->prepare("SELECT id FROM invoices WHERE invoice_no = ?");
        $invInsert = $pdo->prepare("INSERT INTO invoices 
            (invoice_no, user_id, customer_name, customer_phone, subtotal, discount_type, discount_amount, tax_amount, total_amount, payment_method, amount_paid, change_amount, status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?)");

        $itemInsert = $pdo->prepare("INSERT INTO invoice_items 
            (invoice_id, product_id, product_name, barcode, item_type, cost_price, unit_price, quantity, subtotal, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($invoicesData as $inv) {
            $invCheck->execute([$inv['invoice_no']]);
            $existingInvId = $invCheck->fetchColumn();

            // Calculate totals from line items
            $computedSubtotal = 0.00;
            $itemsToInsert = [];

            foreach ($inv['items'] as $it) {
                $p = $productLookup[$it['barcode']] ?? null;
                if (!$p) continue;

                $unitPrice = (float)$p['selling_price'];
                $costPrice = (float)$p['cost_price'];
                $qty = (int)$it['qty'];
                $lineSubtotal = round($unitPrice * $qty, 2);
                $computedSubtotal += $lineSubtotal;

                $itemsToInsert[] = [
                    'product_id' => (int)$p['id'],
                    'product_name' => $p['name'],
                    'barcode' => $p['barcode'],
                    'item_type' => $p['item_type'],
                    'cost_price' => $costPrice,
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'subtotal' => $lineSubtotal,
                    'created_at' => $inv['date']
                ];
            }

            $discountAmount = (float)$inv['discount_amount'];
            $taxAmount = (float)$inv['tax_amount'];
            $totalAmount = max(0, $computedSubtotal - $discountAmount + $taxAmount);
            $amountPaid = (float)$inv['amount_paid'];
            if ($amountPaid < $totalAmount) {
                $amountPaid = $totalAmount;
            }
            $changeAmount = round(max(0, $amountPaid - $totalAmount), 2);

            $targetInvId = null;

            if ($existingInvId) {
                // Already seeded; skip re-insert to avoid duplicate rows
                $targetInvId = (int)$existingInvId;
            } else {
                $invInsert->execute([
                    $inv['invoice_no'],
                    $inv['user_id'],
                    $inv['customer_name'],
                    $inv['customer_phone'],
                    $computedSubtotal,
                    $inv['discount_type'],
                    $discountAmount,
                    $taxAmount,
                    $totalAmount,
                    $inv['payment_method'],
                    $amountPaid,
                    $changeAmount,
                    $inv['notes'],
                    $inv['date']
                ]);
                $targetInvId = (int)$pdo->lastInsertId();

                // Insert Items
                foreach ($itemsToInsert as $ins) {
                    $itemInsert->execute([
                        $targetInvId,
                        $ins['product_id'],
                        $ins['product_name'],
                        $ins['barcode'],
                        $ins['item_type'],
                        $ins['cost_price'],
                        $ins['unit_price'],
                        $ins['quantity'],
                        $ins['subtotal'],
                        $ins['created_at']
                    ]);
                    $stats['invoice_items']++;
                }

                // Log Activity
                log_activity($pdo, $inv['user_id'], 'Created Invoice', 'POS', "Seeded Invoice {$inv['invoice_no']} for amount Rs. " . number_format($totalAmount, 2));

                $stats['invoices']++;
            }

            $stats['total_revenue'] += $totalAmount;

            $dayKey = substr($inv['date'], 0, 10);
            if (!isset($stats['dates_breakdown'][$dayKey])) {
                $stats['dates_breakdown'][$dayKey] = [
                    'count' => 0,
                    'total' => 0.0,
                    'invoices' => []
                ];
            }
            $stats['dates_breakdown'][$dayKey]['count']++;
            $stats['dates_breakdown'][$dayKey]['total'] += $totalAmount;
            $stats['dates_breakdown'][$dayKey]['invoices'][] = [
                'invoice_no' => $inv['invoice_no'],
                'date' => $inv['date'],
                'customer' => $inv['customer_name'],
                'payment' => $inv['payment_method'],
                'items_count' => count($itemsToInsert),
                'total' => $totalAmount
            ];

            $stats['invoices_list'][] = [
                'invoice_no' => $inv['invoice_no'],
                'date' => $inv['date'],
                'customer' => $inv['customer_name'],
                'payment' => $inv['payment_method'],
                'items_count' => count($itemsToInsert),
                'total' => $totalAmount
            ];
        }

        $pdo->commit();
        $stats['success'] = true;
        $stats['message'] = 'Testing data seeded successfully!';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $stats['success'] = false;
        $stats['error'] = $e->getMessage();
    }

    return $stats;
}

// Handle Auto/Trigger Run
$seedResult = null;
if ($action === 'run' || isset($_GET['run']) || $isCli) {
    $seedResult = run_database_seeder($pdo, $clearOldInvoices);
}

// If CLI execution, output plain text and exit
if ($isCli) {
    if ($seedResult['success']) {
        echo "\n=======================================================\n";
        echo " METRO POS - DATABASE SEEDER COMPLETED SUCCESSFULLY\n";
        echo "=======================================================\n";
        echo "Categories Seeded : {$seedResult['categories']}\n";
        echo "Suppliers Seeded  : {$seedResult['suppliers']}\n";
        echo "Products Seeded   : {$seedResult['products']}\n";
        echo "Invoices Seeded   : {$seedResult['invoices']}\n";
        echo "Invoice Items     : {$seedResult['invoice_items']}\n";
        echo "Total Sales Value : Rs. " . number_format($seedResult['total_revenue'], 2) . "\n\n";
        echo "Breakdown by Target Dates (10/13 - 10/17):\n";
        foreach ($seedResult['dates_breakdown'] as $date => $info) {
            echo " - {$date}: {$info['count']} Invoices | Total: Rs. " . number_format($info['total'], 2) . "\n";
        }
        echo "=======================================================\n\n";
    } else {
        echo "\n[ERROR] Seeding failed: " . ($seedResult['error'] ?? 'Unknown error') . "\n\n";
    }
    exit;
}

// Fetch current counts from database for UI display
$dbStats = [
    'categories' => (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'suppliers' => (int)$pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn(),
    'products' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'invoices' => (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn(),
    'target_dates_invoices' => (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE DATE(created_at) BETWEEN '2026-10-13' AND '2026-10-17'")->fetchColumn(),
    'target_dates_revenue' => (float)$pdo->query("SELECT IFNULL(SUM(total_amount), 0) FROM invoices WHERE DATE(created_at) BETWEEN '2026-10-13' AND '2026-10-17' AND status = 'completed'")->fetchColumn()
];

// Group existing invoices for 10/13, 10/14, 10/15, 10/16, 10/17
$recentInvoices = $pdo->query("SELECT invoice_no, customer_name, total_amount, payment_method, status, created_at,
                               (SELECT COUNT(*) FROM invoice_items WHERE invoice_id = invoices.id) AS items_count
                               FROM invoices 
                               WHERE DATE(created_at) BETWEEN '2026-10-13' AND '2026-10-17'
                               ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder (Testing Data) - Metro POS</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Core Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --seeder-primary: #3b82f6;
            --seeder-primary-dark: #1d4ed8;
            --seeder-emerald: #10b981;
            --seeder-amber: #f59e0b;
            --seeder-slate: #0f172a;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .seeder-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .seeder-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            padding: 32px 36px;
            color: #ffffff;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
            position: relative;
            overflow: hidden;
        }
        .seeder-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }
        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
        }
        .action-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
        }
        .table-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .table-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
        }
        .date-pill {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .method-cash { background: #dcfce7; color: #15803d; }
        .method-card { background: #e0e7ff; color: #4338ca; }
        .method-qr { background: #fef3c7; color: #b45309; }
        .method-transfer { background: #ede9fe; color: #6d28d9; }
        .btn-seed-run {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-seed-run:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.45);
            transform: translateY(-1px);
        }
        .quick-nav-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 14px;
        }
        .nav-btn {
            background: #f1f5f9;
            color: #334155;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #cbd5e1;
            transition: all 0.15s ease;
        }
        .nav-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>
<body>

<div class="seeder-container">

    <!-- Top Header Banner -->
    <div class="seeder-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; position: relative; z-index: 2;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(59, 130, 246, 0.2); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #93c5fd; margin-bottom: 10px; border: 1px solid rgba(59, 130, 246, 0.3);">
                    <i class="bi bi-database-fill-gear"></i> Testing Data Generator
                </div>
                <h1 style="font-size: 26px; font-weight: 800; margin: 0 0 6px 0; color: #ffffff;">Metro POS Database Seeder</h1>
                <p style="font-size: 14px; color: #94a3b8; margin: 0; max-width: 650px;">
                    Populates realistic testing data including <strong>Categories</strong>, <strong>Suppliers</strong>, <strong>Products</strong>, and <strong>Sales Invoices for 10/13, 10/14, 10/15, 10/16, and 10/17</strong>.
                </p>
            </div>
            <div>
                <form method="POST" style="margin: 0; display: inline-flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="run">
                    <label style="font-size: 13px; color: #cbd5e1; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <input type="checkbox" name="clear_invoices" value="1" style="accent-color: var(--seeder-primary);"> Reset target date invoices before seeding
                    </label>
                    <button type="submit" class="btn-seed-run">
                        <i class="bi bi-play-fill" style="font-size: 18px;"></i>
                        <span>Execute Database Seed</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="quick-nav-bar" style="position: relative; z-index: 2; margin-top: 24px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 18px;">
            <span style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; align-self: center; margin-right: 4px;">Quick Shortcuts:</span>
            <a href="dashboard.php" class="nav-btn" style="background: rgba(255,255,255,0.1); color: #ffffff; border-color: rgba(255,255,255,0.2);">
                <i class="bi bi-grid-1x2-fill"></i> Admin Dashboard
            </a>
            <a href="invoices.php" class="nav-btn" style="background: rgba(255,255,255,0.1); color: #ffffff; border-color: rgba(255,255,255,0.2);">
                <i class="bi bi-receipt-cutoff"></i> Invoices History
            </a>
            <a href="inventory.php" class="nav-btn" style="background: rgba(255,255,255,0.1); color: #ffffff; border-color: rgba(255,255,255,0.2);">
                <i class="bi bi-box-seam"></i> Products & Inventory
            </a>
            <a href="pos.php" class="nav-btn" style="background: rgba(255,255,255,0.1); color: #ffffff; border-color: rgba(255,255,255,0.2);">
                <i class="bi bi-cart3"></i> POS Terminal
            </a>
            <a href="backup.php" class="nav-btn" style="background: rgba(255,255,255,0.1); color: #ffffff; border-color: rgba(255,255,255,0.2);">
                <i class="bi bi-cloud-arrow-up"></i> Backup & Restore
            </a>
        </div>
    </div>

    <!-- Real-time Seed Result Notification -->
    <?php if ($seedResult): ?>
        <?php if ($seedResult['success']): ?>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 18px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px;">
                <div style="background: #10b981; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #065f46;">Seeding Complete! Testing Data Ready</h4>
                    <p style="margin: 0; font-size: 14px; color: #047857;">
                        Successfully verified/inserted <strong><?= $seedResult['categories'] ?> Categories</strong>, 
                        <strong><?= $seedResult['suppliers'] ?> Suppliers</strong>, 
                        <strong><?= $seedResult['products'] ?> Products</strong>, and 
                        <strong><?= $seedResult['invoices'] ?> Invoices</strong> with 
                        <strong><?= $seedResult['invoice_items'] ?> line items</strong> (Totaling <strong>Rs. <?= number_format($seedResult['total_revenue'], 2) ?></strong>) across the requested dates 10/13 through 10/17.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 18px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px;">
                <div style="background: #ef4444; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 700; color: #991b1b;">Seeding Error</h4>
                    <p style="margin: 0; font-size: 14px; color: #b91c1c;"><?= htmlspecialchars($seedResult['error']) ?></p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Current Database Summary Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">
                <i class="bi bi-tags-fill" style="color: #3b82f6;"></i> Categories
            </div>
            <div class="stat-value"><?= number_format($dbStats['categories']) ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Goods & Services</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">
                <i class="bi bi-truck" style="color: #8b5cf6;"></i> Suppliers
            </div>
            <div class="stat-value"><?= number_format($dbStats['suppliers']) ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Verified Vendors</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">
                <i class="bi bi-box-seam-fill" style="color: #10b981;"></i> Products
            </div>
            <div class="stat-value"><?= number_format($dbStats['products']) ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Active Catalog Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">
                <i class="bi bi-calendar2-range" style="color: #f59e0b;"></i> Target Invoices (10/13-17)
            </div>
            <div class="stat-value"><?= number_format($dbStats['target_dates_invoices']) ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Dates: 10/13 - 10/17</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">
                <i class="bi bi-cash-stack" style="color: #059669;"></i> Target Dates Sales
            </div>
            <div class="stat-value" style="font-size: 22px;">Rs. <?= number_format($dbStats['target_dates_revenue'], 2) ?></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">5-Day Test Sales Total</div>
        </div>
    </div>

    <!-- Target Dates Breakdown Cards -->
    <div style="margin-bottom: 24px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-calendar-check" style="color: var(--seeder-primary);"></i>
            Target Dates Overview (10/13, 10/14, 10/15, 10/16, 10/17)
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            <?php 
            $targetDates = ['2026-10-13', '2026-10-14', '2026-10-15', '2026-10-16', '2026-10-17'];
            foreach ($targetDates as $tDate): 
                $dateSales = $pdo->prepare("SELECT COUNT(*) AS cnt, IFNULL(SUM(total_amount), 0) AS total FROM invoices WHERE DATE(created_at) = ? AND status = 'completed'");
                $dateSales->execute([$tDate]);
                $dRow = $dateSales->fetch();
            ?>
            <div style="background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
                <div class="date-pill" style="margin-bottom: 8px;"><?= date('M d, Y', strtotime($tDate)) ?></div>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                    <?= (int)$dRow['cnt'] ?> Invoices
                </div>
                <div style="font-size: 13px; font-weight: 700; color: #059669;">
                    Rs. <?= number_format((float)$dRow['total'], 2) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Table of Seeded / Existing Invoices for Target Dates -->
    <div class="table-card">
        <div class="table-header">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Invoices for Dates: 10/13, 10/14, 10/15, 10/16, 10/17</h3>
                <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">Showing <?= count($recentInvoices) ?> transactions</p>
            </div>
            <div>
                <a href="invoices.php" class="nav-btn">
                    <i class="bi bi-box-arrow-up-right"></i> Open Invoices Page
                </a>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="custom-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; text-align: left; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 700; color: #475569;">Invoice #</th>
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 700; color: #475569;">Date & Time</th>
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 700; color: #475569;">Customer</th>
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Items</th>
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 700; color: #475569;">Payment Method</th>
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 700; color: #475569; text-align: right;">Total Amount</th>
                        <th style="padding: 12px 18px; font-size: 12px; font-weight: 700; color: #475569; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentInvoices)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 8px; color: #94a3b8;"></i>
                            No invoices recorded for 10/13 - 10/17 yet. Click <strong>"Execute Database Seed"</strong> above to generate test invoices!
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recentInvoices as $inv): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 18px; font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 13px; color: #2563eb;">
                                <?= htmlspecialchars($inv['invoice_no']) ?>
                            </td>
                            <td style="padding: 12px 18px; font-size: 13px; color: #334155;">
                                <div style="font-weight: 600;"><?= date('M d, Y', strtotime($inv['created_at'])) ?></div>
                                <div style="font-size: 11px; color: #94a3b8;"><?= date('h:i A', strtotime($inv['created_at'])) ?></div>
                            </td>
                            <td style="padding: 12px 18px; font-size: 13px; font-weight: 500; color: #1e293b;">
                                <?= htmlspecialchars($inv['customer_name']) ?>
                            </td>
                            <td style="padding: 12px 18px; font-size: 13px; text-align: center; color: #475569;">
                                <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 12px; font-weight: 700;">
                                    <?= (int)$inv['items_count'] ?>
                                </span>
                            </td>
                            <td style="padding: 12px 18px; font-size: 13px;">
                                <?php 
                                $methodClass = 'method-' . $inv['payment_method'];
                                $methodIcons = [
                                    'cash' => 'bi-cash',
                                    'card' => 'bi-credit-card-2-front',
                                    'qr' => 'bi-qr-code-scan',
                                    'transfer' => 'bi-bank'
                                ];
                                ?>
                                <span class="method-badge <?= $methodClass ?>">
                                    <i class="bi <?= $methodIcons[$inv['payment_method']] ?? 'bi-wallet2' ?>"></i>
                                    <?= ucfirst($inv['payment_method']) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 18px; font-size: 14px; font-weight: 700; color: #0f172a; text-align: right;">
                                Rs. <?= number_format((float)$inv['total_amount'], 2) ?>
                            </td>
                            <td style="padding: 12px 18px; text-align: center;">
                                <span style="background: #dcfce7; color: #166534; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; text-transform: uppercase;">
                                    <?= htmlspecialchars($inv['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
