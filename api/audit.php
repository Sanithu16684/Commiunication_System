<?php
// api/audit.php - Audits API for stock reconciliations and activity logs
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_auth();
$user = current_user();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    // 1. Fetch Stock Audits history
    if ($method === 'GET' && $action === 'stock_audits') {
        $sql = "SELECT sa.*, p.name AS product_name, p.barcode, p.sku, u.full_name AS user_name 
                FROM stock_audits sa 
                JOIN products p ON sa.product_id = p.id 
                LEFT JOIN users u ON sa.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if (!empty($_GET['product_id'])) {
            $sql .= " AND sa.product_id = ?";
            $params[] = (int)$_GET['product_id'];
        }

        if (!empty($_GET['type'])) {
            $sql .= " AND sa.audit_type = ?";
            $params[] = $_GET['type'];
        }

        if (!empty($_GET['from'])) {
            $sql .= " AND DATE(sa.created_at) >= ?";
            $params[] = $_GET['from'];
        }

        if (!empty($_GET['to'])) {
            $sql .= " AND DATE(sa.created_at) <= ?";
            $params[] = $_GET['to'];
        }

        // If user is not admin, only load their own logs
        if (!is_admin()) {
            $sql .= " AND sa.user_id = ?";
            $params[] = $user['id'];
        }

        $sql .= " ORDER BY sa.id DESC LIMIT 150";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'success', 'audits' => $stmt->fetchAll()]);
        exit;
    }

    // 2. Fetch System Activity Logs
    if ($method === 'GET' && $action === 'activity_logs') {
        $sql = "SELECT al.*, u.full_name AS user_name 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE 1=1";
        $params = [];

        // If user is not admin, only load their own logs
        if (!is_admin()) {
            $sql .= " AND al.user_id = ?";
            $params[] = $user['id'];
        }

        $sql .= " ORDER BY al.id DESC LIMIT 150";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'success', 'logs' => $stmt->fetchAll()]);
        exit;
    }

    // 3. Perform Stock Reconciliation (Audit Count)
    if ($method === 'POST' && $action === 'reconcile') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $items = $data['items'] ?? [];
        if (empty($items)) {
            echo json_encode(['status' => 'error', 'message' => 'No items provided for reconciliation']);
            exit;
        }

        $pdo->beginTransaction();
        $updatedCount = 0;

        $getStmt = $pdo->prepare("SELECT id, name, stock_qty FROM products WHERE id = ? FOR UPDATE");
        $updStmt = $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?");

        foreach ($items as $item) {
            $prodId = (int)($item['product_id'] ?? 0);
            $countedQty = (int)($item['counted_qty'] ?? 0);
            $reason = trim($item['reason'] ?? 'Physical Inventory Audit');

            if (!$prodId) continue;

            $getStmt->execute([$prodId]);
            $prod = $getStmt->fetch();
            if ($prod) {
                $prevQty = (int)$prod['stock_qty'];
                $diff = $countedQty - $prevQty;

                if ($diff !== 0) {
                    $updStmt->execute([$countedQty, $prodId]);
                    log_stock_audit($pdo, $prodId, $user['id'], 'physical_audit', $prevQty, $countedQty, $diff, $reason);
                    $updatedCount++;
                }
            }
        }

        log_activity($pdo, $user['id'], 'Stock Reconciliation', 'Audits', "Completed physical stock audit: adjusted $updatedCount items.");

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => "Reconciliation complete! $updatedCount product(s) stock adjusted."
        ]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
