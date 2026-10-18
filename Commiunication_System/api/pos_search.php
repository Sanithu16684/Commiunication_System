<?php
// api/pos_search.php - Ultra-fast product lookup for barcode scanner & live search
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$query = trim($_GET['q'] ?? '');
$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
$exact = isset($_GET['exact']) && $_GET['exact'] == '1';

try {
    if ($exact && $query !== '') {
        // Instant barcode or SKU match
        $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.type AS category_type 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE (p.barcode = ? OR p.sku = ?) AND p.status = 'active' 
                               LIMIT 1");
        $stmt->execute([$query, $query]);
        $item = $stmt->fetch();
        if ($item) {
            echo json_encode(['status' => 'success', 'match' => 'exact', 'item' => $item]);
        } else {
            echo json_encode(['status' => 'not_found', 'message' => 'No item matching barcode/SKU']);
        }
        exit;
    }

    // Filtered / Live search
    $sql = "SELECT p.*, c.name AS category_name, c.type AS category_type 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active'";
    $params = [];

    if ($query !== '') {
        $sql .= " AND (p.barcode LIKE ? OR p.sku LIKE ? OR p.name LIKE ?)";
        $term = "%$query%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }

    $sql .= " ORDER BY p.name ASC LIMIT 60";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'items' => $items, 'count' => count($items)]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
