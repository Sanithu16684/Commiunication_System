<?php
// api/categories.php - Category CRUD operations
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
        $stmt = $pdo->query("SELECT c.*, COUNT(p.id) AS product_count 
                             FROM categories c 
                             LEFT JOIN products p ON c.id = p.category_id 
                             GROUP BY c.id 
                             ORDER BY c.name ASC");
        echo json_encode(['status' => 'success', 'categories' => $stmt->fetchAll()]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        if ($action === 'create') {
            $name = trim($data['name'] ?? '');
            $type = in_array($data['type'] ?? '', ['goods', 'service']) ? $data['type'] : 'goods';
            $icon = trim($data['icon'] ?? 'bi-tag');

            if ($name === '') {
                echo json_encode(['status' => 'error', 'message' => 'Category name is required']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO categories (name, type, icon) VALUES (?, ?, ?)");
            $stmt->execute([$name, $type, $icon]);
            log_activity($pdo, $user['id'], 'Created Category', 'Categories', "Added category $name ($type)");
            echo json_encode(['status' => 'success', 'message' => 'Category created successfully']);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($data['id'] ?? 0);
            $name = trim($data['name'] ?? '');
            $type = in_array($data['type'] ?? '', ['goods', 'service']) ? $data['type'] : 'goods';
            $icon = trim($data['icon'] ?? 'bi-tag');

            if (!$id || $name === '') {
                echo json_encode(['status' => 'error', 'message' => 'Valid Category ID and name required']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE categories SET name = ?, type = ?, icon = ? WHERE id = ?");
            $stmt->execute([$name, $type, $icon, $id]);
            log_activity($pdo, $user['id'], 'Updated Category', 'Categories', "Updated category $name (ID #$id)");
            echo json_encode(['status' => 'success', 'message' => 'Category updated successfully']);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($data['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Category ID required']);
                exit;
            }
            // Remove foreign key references or update them to NULL
            $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            log_activity($pdo, $user['id'], 'Deleted Category', 'Categories', "Deleted category ID #$id");
            echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully']);
            exit;
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
