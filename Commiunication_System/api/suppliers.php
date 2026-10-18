<?php
// api/suppliers.php - Supplier CRUD operations
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_auth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$user = current_user();

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT s.*, COUNT(p.id) AS products_count 
                             FROM suppliers s 
                             LEFT JOIN products p ON s.id = p.supplier_id 
                             GROUP BY s.id 
                             ORDER BY s.name ASC");
        echo json_encode(['status' => 'success', 'suppliers' => $stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        if ($action === 'create') {
            $name = trim($data['name'] ?? '');
            $company = trim($data['company'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $email = trim($data['email'] ?? '');
            $address = trim($data['address'] ?? '');

            if ($name === '') {
                echo json_encode(['status' => 'error', 'message' => 'Supplier name is required']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO suppliers (name, company, phone, email, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $company, $phone, $email, $address]);
            log_activity($pdo, $user['id'], 'Created Supplier', 'Suppliers', "Added supplier: $name");
            echo json_encode(['status' => 'success', 'message' => 'Supplier created successfully']);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($data['id'] ?? 0);
            $name = trim($data['name'] ?? '');
            $company = trim($data['company'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $email = trim($data['email'] ?? '');
            $address = trim($data['address'] ?? '');

            if (!$id || $name === '') {
                echo json_encode(['status' => 'error', 'message' => 'Valid Supplier ID and name required']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, company = ?, phone = ?, email = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $company, $phone, $email, $address, $id]);
            log_activity($pdo, $user['id'], 'Updated Supplier', 'Suppliers', "Updated supplier ID #$id ($name)");
            echo json_encode(['status' => 'success', 'message' => 'Supplier updated successfully']);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($data['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Supplier ID required']);
                exit;
            }
            $pdo->prepare("UPDATE products SET supplier_id = NULL WHERE supplier_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
            log_activity($pdo, $user['id'], 'Deleted Supplier', 'Suppliers', "Deleted supplier ID #$id");
            echo json_encode(['status' => 'success', 'message' => 'Supplier deleted successfully']);
            exit;
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
