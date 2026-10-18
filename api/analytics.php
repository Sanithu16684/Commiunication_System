<?php
// api/analytics.php - Admin Dashboard advanced analytics & reporting data
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_auth();
require_admin();

$range = $_GET['range'] ?? '7days'; // 7days, 30days, this_month

try {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $firstDayMonth = date('Y-m-01');

    // 1. KPI Cards
    // Today's Sales
    $stmt = $pdo->prepare("SELECT COUNT(*) AS order_count, IFNULL(SUM(total_amount), 0) AS total_sales 
                           FROM invoices 
                           WHERE DATE(created_at) = ? AND status = 'completed'");
    $stmt->execute([$today]);
    $todayStats = $stmt->fetch();

    // Yesterday's Sales
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(total_amount), 0) AS total_sales 
                           FROM invoices 
                           WHERE DATE(created_at) = ? AND status = 'completed'");
    $stmt->execute([$yesterday]);
    $yesterdaySales = (float)$stmt->fetchColumn();

    // Month's Sales
    $stmt = $pdo->prepare("SELECT COUNT(*) AS order_count, IFNULL(SUM(total_amount), 0) AS total_sales 
                           FROM invoices 
                           WHERE DATE(created_at) >= ? AND status = 'completed'");
    $stmt->execute([$firstDayMonth]);
    $monthStats = $stmt->fetch();

    // Today's Estimated Profit (Selling price - Cost price of items sold today)
    $stmt = $pdo->prepare("SELECT IFNULL(SUM((ii.unit_price - ii.cost_price) * ii.quantity), 0) AS est_profit 
                           FROM invoice_items ii 
                           JOIN invoices i ON ii.invoice_id = i.id 
                           WHERE DATE(i.created_at) = ? AND i.status = 'completed'");
    $stmt->execute([$today]);
    $todayProfit = (float)$stmt->fetchColumn();

    // Low stock count
    $lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock_qty <= min_stock_alert AND item_type = 'physical' AND status = 'active'")->fetchColumn();

    // Total inventory valuation
    $valStats = $pdo->query("SELECT 
        COUNT(*) AS total_products, 
        IFNULL(SUM(stock_qty * cost_price), 0) AS stock_cost_value, 
        IFNULL(SUM(stock_qty * selling_price), 0) AS stock_retail_value 
        FROM products 
        WHERE item_type = 'physical' AND status = 'active'")->fetch();

    // 2. Sales Trend Chart (Past 7 or 30 days)
    $days = ($range === '30days') ? 30 : 7;
    $trendLabels = [];
    $trendSales = [];
    $trendProfits = [];

    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $displayDate = date('M d', strtotime($d));
        $trendLabels[] = $displayDate;

        $tStmt = $pdo->prepare("SELECT IFNULL(SUM(total_amount), 0) FROM invoices WHERE DATE(created_at) = ? AND status = 'completed'");
        $tStmt->execute([$d]);
        $daySale = (float)$tStmt->fetchColumn();
        $trendSales[] = $daySale;

        $pStmt = $pdo->prepare("SELECT IFNULL(SUM((ii.unit_price - ii.cost_price) * ii.quantity), 0) 
                               FROM invoice_items ii 
                               JOIN invoices i ON ii.invoice_id = i.id 
                               WHERE DATE(i.created_at) = ? AND i.status = 'completed'");
        $pStmt->execute([$d]);
        $trendProfits[] = (float)$pStmt->fetchColumn();
    }

    // 3. Top 8 Selling Products
    $topStmt = $pdo->query("SELECT ii.product_name, SUM(ii.quantity) AS total_qty, SUM(ii.subtotal) AS total_revenue 
                            FROM invoice_items ii 
                            JOIN invoices i ON ii.invoice_id = i.id 
                            WHERE i.status = 'completed' 
                            GROUP BY ii.product_name 
                            ORDER BY total_qty DESC 
                            LIMIT 8");
    $topProducts = $topStmt->fetchAll();

    // 4. Sales by Category
    $catStmt = $pdo->query("SELECT IFNULL(c.name, 'Other/Services') AS cat_name, SUM(ii.subtotal) AS cat_revenue 
                            FROM invoice_items ii 
                            JOIN invoices i ON ii.invoice_id = i.id 
                            LEFT JOIN products p ON ii.product_id = p.id 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE i.status = 'completed' 
                            GROUP BY cat_name 
                            ORDER BY cat_revenue DESC 
                            LIMIT 6");
    $categoryData = $catStmt->fetchAll();

    // 5. Payment Methods Breakdown
    $payStmt = $pdo->query("SELECT payment_method, COUNT(*) AS count, SUM(total_amount) AS total 
                           FROM invoices 
                           WHERE status = 'completed' 
                           GROUP BY payment_method");
    $paymentData = $payStmt->fetchAll();

    // 6. Recent 10 Invoices
    $recStmt = $pdo->query("SELECT i.*, u.full_name AS cashier_name 
                            FROM invoices i 
                            LEFT JOIN users u ON i.user_id = u.id 
                            ORDER BY i.id DESC LIMIT 8");
    $recentInvoices = $recStmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'kpi' => [
            'today_sales' => (float)$todayStats['total_sales'],
            'today_orders' => (int)$todayStats['order_count'],
            'yesterday_sales' => $yesterdaySales,
            'month_sales' => (float)$monthStats['total_sales'],
            'month_orders' => (int)$monthStats['order_count'],
            'today_profit' => $todayProfit,
            'low_stock_count' => $lowStockCount,
            'inventory_valuation' => (float)$valStats['stock_cost_value'],
            'inventory_retail' => (float)$valStats['stock_retail_value'],
            'total_physical_items' => (int)$valStats['total_products']
        ],
        'charts' => [
            'trend' => [
                'labels' => $trendLabels,
                'sales' => $trendSales,
                'profits' => $trendProfits
            ],
            'top_products' => $topProducts,
            'category_sales' => $categoryData,
            'payment_methods' => $paymentData
        ],
        'recent_invoices' => $recentInvoices
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
