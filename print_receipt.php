<?php
// print_receipt.php - High Quality Printable Thermal (80mm) & A4 Receipt
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

check_auth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die("Invalid Invoice ID");
}

$settings = get_settings($pdo);
$format = $_GET['format'] ?? 'thermal'; // 'thermal' or 'a4'
$autoprint = isset($_GET['autoprint']) && $_GET['autoprint'] == '1';
$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';

// Fetch invoice data
$stmt = $pdo->prepare("SELECT i.*, u.full_name AS cashier_name 
                       FROM invoices i 
                       LEFT JOIN users u ON i.user_id = u.id 
                       WHERE i.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Invoice not found.");
}

// Fetch invoice items
$itStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$itStmt->execute([$id]);
$items = $itStmt->fetchAll();

$storeName = $settings['store_name'] ?? 'Metro Communication & Bookshop';
$storeTagline = $settings['store_tagline'] ?? 'Books, Stationery, Telecom Services & Xerox';
$storePhone = $settings['store_phone'] ?? '';
$storeAddress = $settings['store_address'] ?? '';
$receiptFooter = $settings['receipt_footer'] ?? 'Thank you for your visit! Goods once sold cannot be returned without receipt.';
$currency = $settings['currency_symbol'] ?? 'Rs.';
$storeLogo = $settings['store_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= htmlspecialchars($invoice['invoice_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Security: Developer Tools Blocker -->
    <script src="assets/js/anti-devtools.js?v=3.0"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'JetBrains Mono', Courier, monospace;
            background: #f1f5f9;
            color: #000;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        <?php if ($isEmbed): ?>
        body {
            background: #f8fafc !important;
            padding: 12px 6px !important;
            min-height: auto !important;
        }
        .no-print-bar {
            display: none !important;
        }
        .receipt-container {
            margin: 0 auto !important;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06) !important;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        <?php endif; ?>

        .no-print-bar {
            position: fixed;
            top: 10px;
            right: 10px;
            background: white;
            padding: 8px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            gap: 10px;
            z-index: 1000;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-action {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-action.btn-secondary {
            background: #64748b;
        }

        /* Thermal 80mm format */
        .receipt-container {
            width: 80mm;
            background: #fff;
            padding: 6mm 4mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            font-size: 12px;
            line-height: 1.35;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
        }

        .receipt-header h1 {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.02em;
        }

        .receipt-header p {
            font-size: 10.5px;
            margin-top: 2px;
        }

        .meta-line {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .receipt-divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin: 6px 0;
        }

        .receipt-table th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            font-weight: 700;
            font-size: 11px;
        }

        .receipt-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .total-row.grand {
            font-size: 15px;
            font-weight: 800;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 6px 0;
            margin: 6px 0;
        }

        .receipt-footer {
            text-align: center;
            font-size: 10.5px;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dashed #000;
        }

        .barcode-visual {
            margin: 10px auto;
            letter-spacing: 4px;
            font-weight: 800;
            font-size: 14px;
            background: #eee;
            padding: 4px;
            display: inline-block;
        }

        /* A4 Format overrides */
        <?php if ($format === 'a4'): ?>
        .receipt-container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
        }
        .receipt-header h1 { font-size: 24px; }
        .receipt-table { font-size: 13.5px; margin: 20px 0; }
        .receipt-table th { padding: 10px; background: #f8fafc; }
        .receipt-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        .total-row.grand { font-size: 20px; }
        <?php endif; ?>

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none;
                width: 100%;
                padding: 0;
            }
            @page {
                size: <?= $format === 'a4' ? 'A4' : '80mm auto' ?>;
                margin: <?= $format === 'a4' ? '15mm' : '0' ?>;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <button onclick="window.print()" class="btn-action">
        <i class="bi bi-printer"></i> Print Now
    </button>
    <a href="print_receipt.php?id=<?= $id ?>&format=<?= $format === 'thermal' ? 'a4' : 'thermal' ?>" class="btn-action btn-secondary">
        Switch to <?= $format === 'thermal' ? 'A4 Invoice' : '80mm Thermal' ?>
    </a>
    <a href="pos.php" class="btn-action btn-secondary">
        Back to POS
    </a>
</div>

<div class="receipt-container">
    <div class="receipt-header">
        <?php if (!empty($storeLogo) && file_exists(__DIR__ . '/' . $storeLogo)): ?>
        <div class="receipt-logo-wrap" style="text-align: center; margin-bottom: 8px;">
            <img src="<?= htmlspecialchars($storeLogo) ?>" alt="Store Logo" class="receipt-logo-img" style="max-height: <?= $format === 'a4' ? '70px' : '48px' ?>; max-width: <?= $format === 'a4' ? '220px' : '150px' ?>; object-fit: contain; <?= $format === 'thermal' ? 'filter: grayscale(100%) contrast(130%);' : '' ?>">
        </div>
        <?php endif; ?>
        <h1><?= htmlspecialchars($storeName) ?></h1>
        <p><?= htmlspecialchars($storeTagline) ?></p>
        <?php if ($storeAddress): ?><p><?= htmlspecialchars($storeAddress) ?></p><?php endif; ?>
        <?php if ($storePhone): ?><p>Tel: <?= htmlspecialchars($storePhone) ?></p><?php endif; ?>
    </div>

    <div class="meta-line">
        <span>INVOICE: #<?= htmlspecialchars($invoice['invoice_no']) ?></span>
        <span><?= date('Y-m-d H:i', strtotime($invoice['created_at'])) ?></span>
    </div>
    <div class="meta-line">
        <span>CASHIER: <?= htmlspecialchars($invoice['cashier_name'] ?? 'Staff') ?></span>
        <span>STATUS: <?= strtoupper($invoice['status']) ?></span>
    </div>
    <div class="meta-line">
        <span>CUSTOMER: <?= htmlspecialchars($invoice['customer_name']) ?></span>
        <?php if ($invoice['customer_phone']): ?>
        <span><?= htmlspecialchars($invoice['customer_phone']) ?></span>
        <?php endif; ?>
    </div>

    <div class="receipt-divider"></div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th style="width: 50%;">ITEM</th>
                <th class="text-center" style="width: 15%;">QTY</th>
                <th class="text-right" style="width: 15%;">PRICE</th>
                <th class="text-right" style="width: 20%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                    <?php if ($item['barcode']): ?>
                    <div style="font-size: 10px; color: #555;"><?= htmlspecialchars($item['barcode']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-right"><?= number_format($item['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="receipt-divider"></div>

    <div class="total-row">
        <span>SUBTOTAL:</span>
        <span><?= $currency ?> <?= number_format($invoice['subtotal'], 2) ?></span>
    </div>

    <?php if ($invoice['discount_amount'] > 0): ?>
    <div class="total-row">
        <span>DISCOUNT:</span>
        <span>- <?= $currency ?> <?= number_format($invoice['discount_amount'], 2) ?></span>
    </div>
    <?php endif; ?>

    <div class="total-row grand">
        <span>TOTAL AMOUNT:</span>
        <span><?= $currency ?> <?= number_format($invoice['total_amount'], 2) ?></span>
    </div>

    <div class="total-row">
        <span>PAYMENT MODE:</span>
        <span><?= strtoupper($invoice['payment_method']) ?></span>
    </div>
    <div class="total-row">
        <span>AMOUNT PAID:</span>
        <span><?= $currency ?> <?= number_format($invoice['amount_paid'], 2) ?></span>
    </div>
    <div class="total-row">
        <span>CHANGE DUE:</span>
        <span><?= $currency ?> <?= number_format($invoice['change_amount'], 2) ?></span>
    </div>

    <?php if ($invoice['notes']): ?>
    <div style="font-size: 11px; margin-top: 6px; font-style: italic;">
        Note: <?= htmlspecialchars($invoice['notes']) ?>
    </div>
    <?php endif; ?>

    <div class="receipt-footer">
        <div class="barcode-visual">*<?= htmlspecialchars($invoice['invoice_no']) ?>*</div>
        <p><?= htmlspecialchars($receiptFooter) ?></p>
        <p style="margin-top: 4px; font-size: 9.5px; color: #666;">Software by Metro POS Systems</p>
    </div>
</div>

<script>
// If loaded inside preview iframe, hide the navigation top bar
if (window.self !== window.top) {
    document.querySelectorAll('.no-print-bar').forEach(function(el) {
        el.style.display = 'none';
    });
}
<?php if ($autoprint): ?>
window.addEventListener('load', function() {
    setTimeout(function() {
        window.print();
    }, 150);
});
<?php endif; ?>
</script>

</body>
</html>
