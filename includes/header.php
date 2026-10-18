<?php
// includes/header.php - Main header and navigation layout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_auth();

$user = current_user();
$settings = get_settings($pdo);
$currPage = basename($_SERVER['PHP_SELF']);
$storeName = $settings['store_name'] ?? 'Metro Communication & Books';
$currency = $settings['currency_symbol'] ?? 'Rs.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'POS System') ?> - <?= htmlspecialchars($storeName) ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Core Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if ($currPage === 'pos.php'): ?>
    <link rel="stylesheet" href="assets/css/pos.css?v=<?= filemtime(__DIR__ . '/../assets/css/pos.css') ?>">
    <?php endif; ?>
    <!-- Security: Developer Tools Blocker -->
    <script src="assets/js/anti-devtools.js?v=2.1"></script>
</head>
<body>
<div class="app-wrapper">
    <!-- Left Navigation Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-book-half"></i>
            </div>
            <div class="brand-text">
                <h4>POS</h4>
                <span>Comm & Books</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <?php if (is_admin()): ?>
            <a href="dashboard.php" class="nav-item <?= $currPage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Admin Dashboard</span>
            </a>
            <?php endif; ?>

            <a href="pos.php" class="nav-item pos-btn <?= $currPage === 'pos.php' ? 'active' : '' ?>">
                <i class="bi bi-upc-scan"></i>
                <span>POS Terminal (F1)</span>
            </a>

            <div class="nav-section-title">Sales & Billing</div>
            <a href="invoices.php" class="nav-item <?= $currPage === 'invoices.php' ? 'active' : '' ?>">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Invoices & Sales</span>
            </a>

            <div class="nav-section-title">Stock & Store</div>
            <a href="inventory.php" class="nav-item <?= $currPage === 'inventory.php' ? 'active' : '' ?>">
                <i class="bi bi-box-seam-fill"></i>
                <span>Inventory Manage</span>
            </a>
            <a href="audits.php" class="nav-item <?= $currPage === 'audits.php' ? 'active' : '' ?>">
                <i class="bi bi-clipboard-check-fill"></i>
                <span>Audits & Logs</span>
            </a>
            <a href="categories.php" class="nav-item <?= $currPage === 'categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i>
                <span>Categories</span>
            </a>
            <a href="suppliers.php" class="nav-item <?= $currPage === 'suppliers.php' ? 'active' : '' ?>">
                <i class="bi bi-truck"></i>
                <span>Suppliers</span>
            </a>
            <a href="barcode_generator.php" class="nav-item <?= $currPage === 'barcode_generator.php' ? 'active' : '' ?>">
                <i class="bi bi-upc"></i>
                <span>Barcode Labels</span>
            </a>

            <?php if (is_admin()): ?>
            <div class="nav-section-title">System & Admin</div>
            <a href="users.php" class="nav-item <?= $currPage === 'users.php' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>User Accounts</span>
            </a>
            <a href="settings.php" class="nav-item <?= $currPage === 'settings.php' ? 'active' : '' ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Store Settings</span>
            </a>
            <a href="backup.php" class="nav-item <?= $currPage === 'backup.php' ? 'active' : '' ?>">
                <i class="bi bi-database-fill-gear"></i>
                <span>Backup & Restore</span>
            </a>
            <?php endif; ?>

            <div class="nav-section-title">Account</div>
            <a href="userprofile.php" class="nav-item <?= in_array($currPage, ['profile.php', 'userprofile.php']) ? 'active' : '' ?>">
                <i class="bi bi-person-circle"></i>
                <span>User Profile</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="userprofile.php" class="user-snippet" title="Open User Profile" style="text-decoration: none; flex: 1; min-width: 0;">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['name'] ?: 'U', 0, 1)) ?>
                </div>
                <div class="user-info" style="min-width: 0; overflow: hidden;">
                    <div class="user-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
                </div>
            </a>
            <a href="logout.php" title="Sign Out" style="color: #94a3b8; font-size: 18px; text-decoration: none; padding-left: 8px;">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h1 class="page-title">
                    <i class="<?= $pageIcon ?? 'bi-speedometer2' ?>" style="color: var(--primary);"></i>
                    <?= htmlspecialchars($pageTitle ?? 'POS System') ?>
                </h1>
            </div>

            <div class="topbar-right">
                <div class="live-clock" id="live-clock">
                    <i class="bi bi-clock"></i>
                    <span>--:--:--</span>
                </div>

                <?php if ($currPage === 'pos.php'): ?>
                <button type="button" class="btn btn-outline btn-sm" onclick="openShortcutsHelpModal()" title="View Keyboard Shortcuts (F12)">
                    <i class="bi bi-keyboard"></i> Keys (F12)
                </button>
                <?php endif; ?>

                <?php if ($currPage !== 'pos.php'): ?>
                <a href="pos.php" class="btn btn-success btn-sm">
                    <i class="bi bi-cart-plus"></i> Open POS
                </a>
                <?php endif; ?>

                <a href="userprofile.php" class="btn btn-outline btn-sm" title="My Profile & Settings">
                    <i class="bi bi-person-circle"></i> Profile
                </a>

                <a href="logout.php" class="btn btn-outline btn-sm" title="Log Out">
                    <i class="bi bi-power"></i> Exit
                </a>
            </div>
        </header>

        <!-- Flash alerts -->
        <div style="padding: 16px 28px 0;">
            <?php $flash = get_flash(); if ($flash && (!empty($flash['message']) || !empty($flash['msg']))): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?>">
                <?= $flash['message'] ?? $flash['msg'] ?? '' ?>
            </div>
            <?php endif; ?>
        </div>
