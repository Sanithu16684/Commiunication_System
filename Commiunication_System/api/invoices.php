<?php
// api/invoices.php - Invoices history, view items, and void/cancel processing
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_auth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = current_user();

try {
    // 1. Get single invoice details with line items
    if ($method === 'GET' && !empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $invStmt = $pdo->prepare("SELECT i.*, u.full_name AS cashier_name 
                                  FROM invoices i 
                                  LEFT JOIN users u ON i.user_id = u.id 
                                  WHERE i.id = ?");
        $invStmt->execute([$id]);
        $invoice = $invStmt->fetch();

        if (!$invoice) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
            exit;
        }

        $itemsStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll();

        echo json_encode(['status' => 'success', 'invoice' => $invoice, 'items' => $items]);
        exit;
    }

    // 2. Filter / List invoices
    if ($method === 'GET') {
        $sql = "SELECT i.*, u.full_name AS cashier_name, COUNT(ii.id) AS total_items 
                FROM invoices i 
                LEFT JOIN users u ON i.user_id = u.id 
                LEFT JOIN invoice_items ii ON i.id = ii.invoice_id 
                WHERE 1=1";
        $params = [];

        if (!empty($_GET['q'])) {
            $sql .= " AND (i.invoice_no LIKE ? OR i.customer_name LIKE ? OR i.customer_phone LIKE ?)";
            $q = "%" . trim($_GET['q']) . "%";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        if (!empty($_GET['from'])) {
            $sql .= " AND DATE(i.created_at) >= ?";
            $params[] = $_GET['from'];
        }

        if (!empty($_GET['to'])) {
            $sql .= " AND DATE(i.created_at) <= ?";
            $params[] = $_GET['to'];
        }

        if (!empty($_GET['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $_GET['status'];
        }

        if (!empty($_GET['method'])) {
            $sql .= " AND i.payment_method = ?";
            $params[] = $_GET['method'];
        }

        $sql .= " GROUP BY i.id ORDER BY i.id DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'success', 'invoices' => $stmt->fetchAll()]);
        exit;
    }

    // 3. Void / Cancel Invoice (Restores inventory & logs audit)
    if ($method === 'POST' && $action === 'void') {
        require_admin();
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;
        $invoiceId = (int)($data['invoice_id'] ?? 0);
        $reason = trim($data['reason'] ?? 'Invoice voided by admin');

        if (!$invoiceId) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice ID required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? FOR UPDATE");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            echo json_encode(['status' => 'error', 'message' => 'Invoice not found']);
            exit;
        }

        if ($invoice['status'] === 'voided') {
            echo json_encode(['status' => 'error', 'message' => 'Invoice has already been voided']);
            exit;
        }

        $pdo->beginTransaction();

        // 1. Mark invoice as voided
        $upd = $pdo->prepare("UPDATE invoices SET status = 'voided', notes = CONCAT(IFNULL(notes, ''), ' [Voided: ', ?, ']') WHERE id = ?");
        $upd->execute([$reason, $invoiceId]);

        // 2. Fetch items and restock physical products
        $itStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $itStmt->execute([$invoiceId]);
        $items = $itStmt->fetchAll();

        $getProd = $pdo->prepare("SELECT stock_qty FROM products WHERE id = ? FOR UPDATE");
        $restockStmt = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");

        foreach ($items as $item) {
            if (!empty($item['product_id']) && $item['item_type'] === 'physical') {
                $prodId = $item['product_id'];
                $qty = (int)$item['quantity'];

                $getProd->execute([$prodId]);
                $curr = $getProd->fetch();
                if ($curr) {
                    $prevQty = (int)$curr['stock_qty'];
                    $newQty = $prevQty + $qty;
                    $restockStmt->execute([$qty, $prodId]);
                    log_stock_audit($pdo, $prodId, $user['id'], 'void_return', $prevQty, $newQty, $qty, "Restocked from Voided Invoice #{$invoice['invoice_no']}: $reason");
                }
            }
        }

        // 3. Log Activity
        log_activity($pdo, $user['id'], 'Voided Invoice', 'Invoices', "Voided Invoice #{$invoice['invoice_no']}. Reason: $reason");

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => "Invoice #{$invoice['invoice_no']} has been voided and items restocked."
        ]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
