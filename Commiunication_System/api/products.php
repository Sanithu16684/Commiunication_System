<?php
// api/products.php - Complete Product CRUD & Quick Stock Adjustment
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_auth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = current_user();

try {
    // 1. Fetch single product or list
    if ($method === 'GET') {
        if (!empty($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, s.name AS supplier_name 
                                   FROM products p 
                                   LEFT JOIN categories c ON p.category_id = c.id 
                                   LEFT JOIN suppliers s ON p.supplier_id = s.id 
                                   WHERE p.id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            if ($product) {
                echo json_encode(['status' => 'success', 'product' => $product]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            }
            exit;
        }

        // List products
        $stmt = $pdo->query("SELECT p.*, c.name AS category_name, s.name AS supplier_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             LEFT JOIN suppliers s ON p.supplier_id = s.id 
                             ORDER BY p.id DESC");
        $products = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'products' => $products]);
        exit;
    }

    // 2. Quick Stock Adjustment / Stock In
    if ($method === 'POST' && $action === 'adjust_stock') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $productId = (int)($data['product_id'] ?? 0);
        $type = $data['adjustment_type'] ?? 'stock_in'; // stock_in, damage, physical_audit
        $qtyChange = (int)($data['qty_change'] ?? 0);
        $reason = trim($data['reason'] ?? '');

        if (!$productId || $qtyChange == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Product and non-zero quantity change required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, name, stock_qty FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $prod = $stmt->fetch();

        if (!$prod) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            exit;
        }

        $prevQty = (int)$prod['stock_qty'];
        $newQty = $prevQty + $qtyChange;
        if ($newQty < 0) $newQty = 0;
        $actualChange = $newQty - $prevQty;

        // Update product stock
        $upd = $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?");
        $upd->execute([$newQty, $productId]);

        // Audit log
        log_stock_audit($pdo, $productId, $user['id'], $type, $prevQty, $newQty, $actualChange, $reason);
        log_activity($pdo, $user['id'], 'Stock Adjustment', 'Inventory', "Adjusted stock for {$prod['name']} from $prevQty to $newQty ($type)");

        echo json_encode([
            'status' => 'success',
            'message' => 'Stock updated successfully!',
            'new_stock' => $newQty
        ]);
        exit;
    }

    // 3. Create New Product
    if ($method === 'POST' && $action === 'create') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $barcode = trim($data['barcode'] ?? '');
        $sku = trim($data['sku'] ?? '');
        $name = trim($data['name'] ?? '');
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $supplierId = !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null;
        $itemType = in_array($data['item_type'] ?? '', ['physical', 'service', 'reload']) ? $data['item_type'] : 'physical';
        $costPrice = (float)($data['cost_price'] ?? 0);
        $sellingPrice = (float)($data['selling_price'] ?? 0);
        $stockQty = (int)($data['stock_qty'] ?? 0);
        $minStock = (int)($data['min_stock_alert'] ?? 5);
        $unit = trim($data['unit'] ?? 'pcs');

        if ($name === '') {
            echo json_encode(['status' => 'error', 'message' => 'Product name is required']);
            exit;
        }

        // Auto-generate barcode if blank
        if ($barcode === '') {
            $barcode = '890' . rand(1000000, 9999999);
        }
        // Auto-generate sku if blank
        if ($sku === '') {
            $sku = strtoupper(substr($name, 0, 3)) . '-' . rand(100, 999);
        }

        // Check duplicates
        $chk = $pdo->prepare("SELECT id FROM products WHERE barcode = ? OR sku = ?");
        $chk->execute([$barcode, $sku]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Barcode or SKU already in use by another product']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO products 
            (barcode, sku, name, category_id, item_type, cost_price, selling_price, stock_qty, min_stock_alert, unit, supplier_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([
            $barcode, $sku, $name, $categoryId, $itemType, $costPrice, $sellingPrice, $stockQty, $minStock, $unit, $supplierId
        ]);
        $newId = $pdo->lastInsertId();

        if ($stockQty > 0) {
            log_stock_audit($pdo, $newId, $user['id'], 'stock_in', 0, $stockQty, $stockQty, 'Initial opening stock');
        }

        log_activity($pdo, $user['id'], 'Created Product', 'Inventory', "Added new product: $name (SKU: $sku, Barcode: $barcode)");

        echo json_encode([
            'status' => 'success',
            'message' => 'Product created successfully!',
            'product_id' => $newId
        ]);
        exit;
    }

    // 4. Update Product
    if ($method === 'POST' && $action === 'update') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Product ID is missing']);
            exit;
        }

        $barcode = trim($data['barcode'] ?? '');
        $sku = trim($data['sku'] ?? '');
        $name = trim($data['name'] ?? '');
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $supplierId = !empty($data['supplier_id']) ? (int)$data['supplier_id'] : null;
        $itemType = in_array($data['item_type'] ?? '', ['physical', 'service', 'reload']) ? $data['item_type'] : 'physical';
        $costPrice = (float)($data['cost_price'] ?? 0);
        $sellingPrice = (float)($data['selling_price'] ?? 0);
        $minStock = (int)($data['min_stock_alert'] ?? 5);
        $unit = trim($data['unit'] ?? 'pcs');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : 'active';

        // Check duplicate barcode or sku on other items
        $chk = $pdo->prepare("SELECT id FROM products WHERE (barcode = ? OR sku = ?) AND id != ?");
        $chk->execute([$barcode, $sku, $id]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Barcode or SKU is already assigned to another item']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE products SET 
            barcode = ?, sku = ?, name = ?, category_id = ?, item_type = ?, 
            cost_price = ?, selling_price = ?, min_stock_alert = ?, unit = ?, 
            supplier_id = ?, status = ? 
            WHERE id = ?");
        $stmt->execute([
            $barcode, $sku, $name, $categoryId, $itemType, 
            $costPrice, $sellingPrice, $minStock, $unit, 
            $supplierId, $status, $id
        ]);

        log_activity($pdo, $user['id'], 'Updated Product', 'Inventory', "Updated details for: $name (ID #$id)");

        echo json_encode(['status' => 'success', 'message' => 'Product updated successfully!']);
        exit;
    }

    // 5. Delete Product
    if ($method === 'POST' && $action === 'delete') {
        require_admin();
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;
        $id = (int)($data['id'] ?? 0);

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Product ID is missing']);
            exit;
        }

        // Check if item has sales history
        $chk = $pdo->prepare("SELECT COUNT(*) FROM invoice_items WHERE product_id = ?");
        $chk->execute([$id]);
        $hasSales = $chk->fetchColumn();

        if ($hasSales > 0) {
            // Soft delete to protect financial invoice history
            $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Product marked as inactive (has past invoice records).';
        } else {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Product deleted successfully.';
        }

        log_activity($pdo, $user['id'], 'Deleted Product', 'Inventory', "Deleted/Deactivated Product ID #$id");
        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
