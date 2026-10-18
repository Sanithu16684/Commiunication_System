<?php
// api/checkout.php - POS checkout transaction processing
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['items'])) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty or invalid data received']);
    exit;
}

$user = current_user();
$userId = $user['id'] ?? 1;

try {
    $pdo->beginTransaction();

    $invoiceNo = generate_invoice_no($pdo);
    $customerName = trim($data['customer_name'] ?? 'Walk-in Customer');
    if ($customerName === '') $customerName = 'Walk-in Customer';
    $customerPhone = trim($data['customer_phone'] ?? '');

    $subtotal = (float)($data['subtotal'] ?? 0);
    $discountType = in_array($data['discount_type'] ?? '', ['percentage', 'fixed']) ? $data['discount_type'] : 'fixed';
    $discountAmount = (float)($data['discount_amount'] ?? 0);
    $taxAmount = (float)($data['tax_amount'] ?? 0);
    $totalAmount = (float)($data['total_amount'] ?? 0);

    $paymentMethod = in_array($data['payment_method'] ?? '', ['cash', 'card', 'transfer', 'qr']) ? $data['payment_method'] : 'cash';
    $amountPaid = (float)($data['amount_paid'] ?? $totalAmount);
    $changeAmount = (float)($data['change_amount'] ?? 0);
    $notes = trim($data['notes'] ?? '');

    // 1. Insert Invoice
    $invStmt = $pdo->prepare("INSERT INTO invoices 
        (invoice_no, user_id, customer_name, customer_phone, subtotal, discount_type, discount_amount, tax_amount, total_amount, payment_method, amount_paid, change_amount, status, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)");
    $invStmt->execute([
        $invoiceNo, $userId, $customerName, $customerPhone, 
        $subtotal, $discountType, $discountAmount, $taxAmount, $totalAmount, 
        $paymentMethod, $amountPaid, $changeAmount, $notes
    ]);
    $invoiceId = $pdo->lastInsertId();

    // 2. Insert Invoice Items and Adjust Inventory
    $itemStmt = $pdo->prepare("INSERT INTO invoice_items 
        (invoice_id, product_id, product_name, barcode, item_type, cost_price, unit_price, quantity, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $updateStockStmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
    $getProdStmt = $pdo->prepare("SELECT stock_qty, cost_price FROM products WHERE id = ? FOR UPDATE");

    foreach ($data['items'] as $item) {
        $prodId = !empty($item['id']) ? (int)$item['id'] : null;
        $name = trim($item['name'] ?? 'Custom Item');
        $barcode = $item['barcode'] ?? '';
        $itemType = in_array($item['item_type'] ?? '', ['physical', 'service', 'reload']) ? $item['item_type'] : 'physical';
        $unitPrice = (float)($item['price'] ?? 0);
        $qty = max(1, (int)($item['qty'] ?? 1));
        $itemSubtotal = $unitPrice * $qty;

        $costPrice = 0.00;
        $prevQty = 0;
        $newQty = 0;

        if ($prodId) {
            $getProdStmt->execute([$prodId]);
            $prodRow = $getProdStmt->fetch();
            if ($prodRow) {
                $costPrice = (float)$prodRow['cost_price'];
                $prevQty = (int)$prodRow['stock_qty'];
            }
        }

        // Insert item record
        $itemStmt->execute([
            $invoiceId, $prodId, $name, $barcode, $itemType, $costPrice, $unitPrice, $qty, $itemSubtotal
        ]);

        // If physical product, decrement stock & create audit log
        if ($prodId && $itemType === 'physical') {
            $updateStockStmt->execute([$qty, $prodId]);
            $newQty = $prevQty - $qty;
            log_stock_audit($pdo, $prodId, $userId, 'sale', $prevQty, $newQty, -$qty, "POS Sale Invoice #$invoiceNo");
        }
    }

    // 3. Log Activity
    log_activity($pdo, $userId, 'Created Invoice', 'POS', "Invoice $invoiceNo completed for amount Rs. " . number_format($totalAmount, 2));

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Sale processed successfully!',
        'invoice_id' => $invoiceId,
        'invoice_no' => $invoiceNo,
        'total' => $totalAmount,
        'change' => $changeAmount
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Failed to process sale: ' . $e->getMessage()]);
}
