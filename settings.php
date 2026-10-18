<?php
// settings.php - Store and System Configuration with Live Receipt Preview (Admin only)
$pageTitle = 'Store Settings';
$pageIcon = 'bi-gear-fill';
require_once __DIR__ . '/includes/header.php';
require_admin();

$msg = '';
$errorMsg = '';

// Ensure upload directory exists
$uploadDir = __DIR__ . '/uploads/logo';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Text settings update
    $settingsToUpdate = [
        'store_name' => trim($_POST['store_name'] ?? ''),
        'store_tagline' => trim($_POST['store_tagline'] ?? ''),
        'store_phone' => trim($_POST['store_phone'] ?? ''),
        'store_email' => trim($_POST['store_email'] ?? ''),
        'store_address' => trim($_POST['store_address'] ?? ''),
        'currency_symbol' => trim($_POST['currency_symbol'] ?? 'Rs.'),
        'receipt_footer' => trim($_POST['receipt_footer'] ?? '')
    ];

    // 2. Handle Logo Removal
    if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
        $oldLogo = $settings['store_logo'] ?? '';
        if ($oldLogo && strpos($oldLogo, 'uploads/logo/') === 0 && file_exists(__DIR__ . '/' . $oldLogo)) {
            @unlink(__DIR__ . '/' . $oldLogo);
        }
        $settingsToUpdate['store_logo'] = '';
    }

    // 3. Handle Sample Logo Selection
    if (isset($_POST['use_sample_logo']) && $_POST['use_sample_logo'] === '1') {
        $settingsToUpdate['store_logo'] = 'assets/img/sample_logo.svg';
    }

    // 4. Handle Logo File Upload
    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['store_logo'];
        $fileSize = $file['size'];
        $fileName = $file['name'];
        $tmpPath = $file['tmp_name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
        $mime = mime_content_type($tmpPath);

        if (!in_array($ext, $allowedExts) || (!in_array($mime, $allowedMimes) && $ext !== 'svg')) {
            $errorMsg = 'Invalid logo image format. Allowed formats: PNG, JPG, JPEG, WEBP, SVG.';
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $errorMsg = 'Logo file size exceeds the 2MB limit.';
        } else {
            $newFileName = 'store_logo_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $destination = $uploadDir . '/' . $newFileName;
            $relativeDbPath = 'uploads/logo/' . $newFileName;

            if (move_uploaded_file($tmpPath, $destination)) {
                // Delete previous custom uploaded logo to save space
                $oldLogo = $settings['store_logo'] ?? '';
                if ($oldLogo && strpos($oldLogo, 'uploads/logo/') === 0 && file_exists(__DIR__ . '/' . $oldLogo)) {
                    @unlink(__DIR__ . '/' . $oldLogo);
                }
                $settingsToUpdate['store_logo'] = $relativeDbPath;
            } else {
                $errorMsg = 'Failed to save uploaded logo to server. Check folder permissions.';
            }
        }
    }

    if (!$errorMsg) {
        $updStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        foreach ($settingsToUpdate as $k => $v) {
            $updStmt->execute([$k, $v, $v]);
        }

        log_activity($pdo, $user['id'], 'Updated Settings', 'Settings', "Updated store general settings & receipt configuration");
        $msg = 'Store settings and receipt configuration saved successfully!';
        $settings = get_settings($pdo);
    }
}

$currentLogo = $settings['store_logo'] ?? '';
$hasLogo = !empty($currentLogo) && file_exists(__DIR__ . '/' . $currentLogo);
$currentCurrency = $settings['currency_symbol'] ?? 'Rs.';
?>

<div class="page-container" style="max-width: 1440px;">
    
    <!-- Top Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">Store Settings & Receipt Designer</h3>
            <p style="font-size: 13.5px; color: #64748b; margin: 0;">Configure your store details, upload your receipt logo, and preview your 80mm thermal receipt in real-time</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="pos.php" class="btn btn-primary">
                <i class="bi bi-cart3"></i> Open POS Terminal
            </a>
            <a href="backup.php" class="btn btn-outline">
                <i class="bi bi-shield-lock"></i> Backup Center
            </a>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php endif; ?>

    <!-- Main 2-Column Responsive Layout -->
    <div class="settings-layout">
        
        <!-- Left Column: Settings Form -->
        <div class="settings-form-column">
            <form method="POST" enctype="multipart/form-data" id="store-settings-form">
                
                <!-- Card 1: Store Logo & Branding -->
                <div class="content-card" style="margin-bottom: 20px;">
                    <div class="card-header-flex" style="padding: 16px 22px; border-bottom: 1px solid #e2e8f0;">
                        <span class="card-title" style="font-size: 15px; font-weight: 700;">
                            <i class="bi bi-image" style="color: var(--primary);"></i> Receipt Logo & Branding
                        </span>
                        <span style="font-size: 12px; color: #64748b;">Printed at the top of receipts</span>
                    </div>
                    <div class="card-body" style="padding: 22px;">
                        
                        <div class="logo-config-wrapper" style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                            <!-- Logo Preview Thumbnail Box with floating cancel button -->
                            <div class="logo-preview-box" id="logo-preview-box" style="width: 140px; height: 90px; border: 2px dashed #cbd5e1; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                <?php if ($hasLogo): ?>
                                    <img src="<?= htmlspecialchars($currentLogo) ?>?v=<?= time() ?>" id="current-logo-img" alt="Store Logo" style="max-width: 100%; max-height: 100%; object-fit: contain; padding: 6px;">
                                    <div id="no-logo-placeholder" style="display: none; text-align: center; color: #94a3b8; font-size: 11px;">
                                        <i class="bi bi-image" style="font-size: 28px; display: block; margin-bottom: 2px;"></i>
                                        <span>No Logo Set</span>
                                    </div>
                                <?php else: ?>
                                    <div id="no-logo-placeholder" style="text-align: center; color: #94a3b8; font-size: 11px;">
                                        <i class="bi bi-image" style="font-size: 28px; display: block; margin-bottom: 2px;"></i>
                                        <span>No Logo Set</span>
                                    </div>
                                    <img id="current-logo-img" src="" alt="Store Logo" style="display: none; max-width: 100%; max-height: 100%; object-fit: contain; padding: 6px;">
                                <?php endif; ?>
                                
                                <!-- Floating Quick Cancel Badge -->
                                <button type="button" id="thumb-cancel-btn" onclick="cancelLogoSelection()" title="Cancel logo selection" style="display: <?= $hasLogo ? 'flex' : 'none' ?>; position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border-radius: 50%; background: #ef4444; color: white; border: none; cursor: pointer; align-items: center; justify-content: center; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>

                            <!-- Upload Controls -->
                            <div style="flex: 1; min-width: 240px;">
                                <label class="form-label" style="margin-bottom: 6px; font-weight: 600;">Upload New Logo</label>
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <label class="btn btn-outline btn-sm" style="cursor: pointer; margin: 0; background: white; border: 1px solid #cbd5e1;">
                                        <i class="bi bi-cloud-arrow-up-fill" style="color: var(--primary);"></i> Choose Image
                                        <input type="file" name="store_logo" id="logo-file-input" accept="image/png, image/jpeg, image/jpg, image/webp, image/svg+xml" style="display: none;" onchange="handleLogoSelect(this)">
                                    </label>

                                    <button type="button" class="btn btn-outline btn-sm" onclick="useSampleLogoPreset()" title="Use pre-designed Metro POS vector logo">
                                        <i class="bi bi-stars" style="color: #f59e0b;"></i> Use Sample Logo
                                    </button>

                                    <!-- Cancel Option Button -->
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-logo" onclick="cancelLogoSelection()" style="display: <?= $hasLogo ? 'inline-flex' : 'none' ?>;" title="Cancel logo selection and revert">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>

                                    <!-- Remove Logo Button -->
                                    <button type="button" class="btn btn-danger btn-sm" id="btn-remove-logo" onclick="removeCurrentLogo()" style="display: <?= $hasLogo ? 'inline-flex' : 'none' ?>;" title="Remove logo from printed receipts">
                                        <i class="bi bi-trash"></i> Remove Logo
                                    </button>
                                </div>
                                <input type="hidden" name="remove_logo" id="remove-logo-hidden" value="0">
                                <input type="hidden" name="use_sample_logo" id="use-sample-logo-hidden" value="0">
                                <div style="font-size: 11.5px; color: #64748b; margin-top: 8px; line-height: 1.4;">
                                    PNG, JPG, SVG or WEBP (Max 2MB). Ideal for 80mm thermal receipt printing (monochrome/transparent recommended).
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Card 2: Store Information -->
                <div class="content-card" style="margin-bottom: 20px;">
                    <div class="card-header-flex" style="padding: 16px 22px; border-bottom: 1px solid #e2e8f0;">
                        <span class="card-title" style="font-size: 15px; font-weight: 700;">
                            <i class="bi bi-shop" style="color: var(--primary);"></i> Store & Receipt Information
                        </span>
                        <span style="font-size: 12px; color: #64748b;">Header and footer details</span>
                    </div>
                    <div class="card-body" style="padding: 22px;">
                        
                        <div class="form-group">
                            <label class="form-label">Store / Business Name *</label>
                            <input type="text" name="store_name" id="input-store-name" class="form-control" required value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>" placeholder="e.g. Communication & Bookshop">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tagline / Subheading</label>
                            <input type="text" name="store_tagline" id="input-store-tagline" class="form-control" value="<?= htmlspecialchars($settings['store_tagline'] ?? '') ?>" placeholder="e.g. Books, Stationery, Telecom Services & Xerox">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label class="form-label">Contact Phone Number(s)</label>
                                <input type="text" name="store_phone" id="input-store-phone" class="form-control" value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>" placeholder="0112345678 / 0771234567">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Store Email Address</label>
                                <input type="email" name="store_email" id="input-store-email" class="form-control" value="<?= htmlspecialchars($settings['store_email'] ?? '') ?>" placeholder="metrobooks.comm@gmail.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Physical Address</label>
                            <textarea name="store_address" id="input-store-address" class="form-control" rows="2" placeholder="Street, City, Postal Code"><?= htmlspecialchars($settings['store_address'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" name="currency_symbol" id="input-currency-symbol" class="form-control" style="max-width: 140px; font-family: var(--font-mono); font-weight: 700;" value="<?= htmlspecialchars($currentCurrency) ?>">
                            <span style="font-size: 11px; color: #64748b;">e.g. Rs. or $, LKR</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Receipt Footer Message / Return Policy</label>
                            <textarea name="receipt_footer" id="input-receipt-footer" class="form-control" rows="3" placeholder="Thank you message, return conditions, warranty information"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
                            <span style="font-size: 11px; color: #64748b;">Printed at the bottom of 80mm thermal receipts and A4 invoices.</span>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 10px; flex-wrap: wrap;">
                            <button type="submit" class="btn btn-primary btn-lg" style="box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);">
                                <i class="bi bi-check2-circle"></i> Save Settings
                            </button>
                            <a href="settings.php" class="btn btn-outline btn-lg" style="background: white; border: 1px solid #cbd5e1; color: #475569;" title="Discard unsaved changes">
                                <i class="bi bi-x-lg"></i> Cancel
                            </a>
                            <span id="save-status-indicator" style="font-size: 12.5px; color: #64748b;"></span>
                        </div>

                    </div>
                </div>

            </form>
        </div>

        <!-- Right Column: Live Interactive Receipt Preview -->
        <div class="receipt-preview-column">
            <div class="preview-sticky-card">
                
                <!-- Preview Toolbar -->
                <div class="preview-header-bar">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-receipt" style="color: var(--primary); font-size: 17px;"></i>
                        <span style="font-weight: 700; font-size: 14px; color: #0f172a;">Live Receipt Preview</span>
                        <span class="live-pulse-badge">
                            <span class="pulse-dot"></span> Live
                        </span>
                    </div>
                    <div class="format-switcher-pills">
                        <button type="button" class="format-btn active" id="btn-fmt-thermal" onclick="setPreviewFormat('thermal')">
                            <i class="bi bi-receipt-cutoff"></i> 80mm Thermal
                        </button>
                        <button type="button" class="format-btn" id="btn-fmt-a4" onclick="setPreviewFormat('a4')">
                            <i class="bi bi-file-earmark-text"></i> A4
                        </button>
                    </div>
                </div>

                <!-- Preview Canvas Stage -->
                <div class="preview-stage" id="preview-stage">
                    
                    <!-- Authentic Thermal Receipt Paper Slip -->
                    <div class="paper-receipt thermal-mode" id="paper-receipt-container">
                        
                        <!-- Top Perforation Effect -->
                        <div class="paper-tear paper-tear-top"></div>

                        <!-- Receipt Header -->
                        <div class="mock-receipt-header">
                            <!-- Logo Container -->
                            <div id="mock-logo-wrap" class="mock-logo-wrap" style="<?= $hasLogo ? 'display: block;' : 'display: none;' ?>">
                                <img id="mock-receipt-logo" src="<?= $hasLogo ? htmlspecialchars($currentLogo) : '' ?>" alt="Store Logo" class="mock-logo-img">
                            </div>
                            
                            <h2 id="mock-store-name" class="mock-store-name"><?= htmlspecialchars($settings['store_name'] ?? 'Communication & Bookshop') ?></h2>
                            <p id="mock-store-tagline" class="mock-subtext"><?= htmlspecialchars($settings['store_tagline'] ?? 'Books, Stationery, Telecom Services & Xerox') ?></p>
                            <p id="mock-store-address" class="mock-subtext"><?= htmlspecialchars($settings['store_address'] ?? 'No. 45, Main Street, Colombo 03, Sri Lanka') ?></p>
                            <p id="mock-store-phone" class="mock-subtext">Tel: <?= htmlspecialchars($settings['store_phone'] ?? '+94 11 234 5678') ?></p>
                        </div>

                        <!-- Meta Info Line -->
                        <div class="mock-divider"></div>
                        <div class="mock-meta-row">
                            <span>INVOICE: #INV-20261018-0042</span>
                            <span><?= date('Y-m-d H:i') ?></span>
                        </div>
                        <div class="mock-meta-row">
                            <span>CASHIER: <?= htmlspecialchars($user['name'] ?? 'System Administrator') ?></span>
                            <span>STATUS: PAID</span>
                        </div>
                        <div class="mock-meta-row">
                            <span>CUSTOMER: Walk-in Customer</span>
                            <span>+94 77 123 4567</span>
                        </div>
                        <div class="mock-divider"></div>

                        <!-- Items Table -->
                        <table class="mock-table">
                            <thead>
                                <tr>
                                    <th style="width: 52%; text-align: left;">ITEM</th>
                                    <th style="width: 14%; text-align: center;">QTY</th>
                                    <th style="width: 17%; text-align: right;">PRICE</th>
                                    <th style="width: 17%; text-align: right;">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="item-name">B&W Photocopy (Single Side)</div>
                                        <div class="item-code">SRV-XER-BW</div>
                                    </td>
                                    <td class="text-center">15</td>
                                    <td class="text-right">10.00</td>
                                    <td class="text-right">150.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="item-name">Atlas CR 80 Pgs Rule Book</div>
                                        <div class="item-code">9789551234011</div>
                                    </td>
                                    <td class="text-center">2</td>
                                    <td class="text-right">160.00</td>
                                    <td class="text-right">320.00</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="item-name">Pilot G2 0.7mm Gel Pen (Black)</div>
                                        <div class="item-code">8901234567890</div>
                                    </td>
                                    <td class="text-center">1</td>
                                    <td class="text-right">380.00</td>
                                    <td class="text-right">380.00</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="mock-divider"></div>

                        <!-- Totals Section -->
                        <div class="mock-total-row">
                            <span>SUBTOTAL:</span>
                            <span class="mock-curr-val" data-amount="850.00"><?= htmlspecialchars($currentCurrency) ?> 850.00</span>
                        </div>
                        <div class="mock-total-row">
                            <span>DISCOUNT (FIXED):</span>
                            <span class="mock-curr-val" data-amount="-50.00">- <?= htmlspecialchars($currentCurrency) ?> 50.00</span>
                        </div>
                        <div class="mock-total-row grand-total">
                            <span>TOTAL AMOUNT:</span>
                            <span class="mock-curr-val" data-amount="800.00"><?= htmlspecialchars($currentCurrency) ?> 800.00</span>
                        </div>
                        <div class="mock-total-row">
                            <span>PAYMENT MODE:</span>
                            <span>CASH</span>
                        </div>
                        <div class="mock-total-row">
                            <span>AMOUNT PAID:</span>
                            <span class="mock-curr-val" data-amount="1000.00"><?= htmlspecialchars($currentCurrency) ?> 1,000.00</span>
                        </div>
                        <div class="mock-total-row">
                            <span>CHANGE DUE:</span>
                            <span class="mock-curr-val" data-amount="200.00"><?= htmlspecialchars($currentCurrency) ?> 200.00</span>
                        </div>

                        <!-- Barcode & Footer -->
                        <div class="mock-footer">
                            <div class="mock-barcode">*INV-20261018-0042*</div>
                            <p id="mock-receipt-footer" class="mock-footer-text"><?= htmlspecialchars($settings['receipt_footer'] ?? 'Thank you for your patronage! Please retain this receipt for returns/warranty within 7 days.') ?></p>
                            <p class="mock-credits">Software by Metro POS Systems</p>
                        </div>

                        <!-- Bottom Perforation Effect -->
                        <div class="paper-tear paper-tear-bottom"></div>

                    </div>
                </div>

                <!-- Preview Actions Footer -->
                <div class="preview-actions-bar">
                    <button type="button" class="btn btn-outline btn-sm" onclick="printPreviewReceipt()" title="Print sample receipt using browser print dialog">
                        <i class="bi bi-printer"></i> Print Test Receipt
                    </button>
                    <span style="font-size: 11.5px; color: #64748b;">
                        <i class="bi bi-info-circle"></i> Displays exactly as printed on physical POS hardware
                    </span>
                </div>

            </div>
        </div>

    </div>

    <!-- Database Backup & Maintenance Section -->
    <div class="content-card" style="margin-top: 10px; margin-bottom: 20px;">
        <div class="card-header-flex">
            <div>
                <span class="card-title">
                    <i class="bi bi-database-fill-gear" style="color: var(--primary);"></i> Database Backup & Data Protection
                </span>
                <span style="font-size: 12px; color: #64748b;">Safeguard sales history, inventory, suppliers, and customer transactions</span>
            </div>
            <div>
                <a href="backup.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-right-circle"></i> Open Backup & Restore Center
                </a>
            </div>
        </div>
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h5 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Regular Backups Recommended</h5>
                    <p style="font-size: 13px; color: #64748b; margin: 0; max-width: 540px;">
                        Create 1-click live SQL backups to your local drive or save snapshots securely on the server. You can also restore past backups with automatic safety snapshots.
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="backup.php?action=download_current" class="btn btn-outline">
                        <i class="bi bi-cloud-arrow-down-fill" style="color: var(--primary);"></i> 1-Click Download (.sql)
                    </a>
                    <a href="backup.php" class="btn btn-success">
                        <i class="bi bi-arrow-counterclockwise"></i> Restore Database
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Danger Zone: Reset & Clear System Data -->
    <div class="content-card" style="border: 1px solid #fecaca; margin-bottom: 24px;">
        <div class="card-header-flex" style="background: #fff5f5; border-bottom: 1px solid #fee2e2;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="card-title" style="color: #991b1b; font-size: 15px;">
                    <i class="bi bi-radioactive" style="color: #ef4444;"></i> Danger Zone: Reset & Clear System Data
                </span>
                <span class="badge badge-danger">High Impact</span>
            </div>
            <div>
                <button type="button" class="btn btn-danger btn-sm" onclick="openClearDataModal()">
                    <i class="bi bi-trash3-fill"></i> Clear All Data
                </button>
            </div>
        </div>
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h5 style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">Clear Operational Records & Reset Database</h5>
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 10px; max-width: 540px; line-height: 1.5;">
                        Clears all sales invoices, products, stock audits, categories, and suppliers. 
                        <strong>User accounts and store settings will remain untouched and safe.</strong>
                    </p>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 12px;">
                        <span style="color: #15803d; font-weight: 600;">
                            <i class="bi bi-check-circle-fill"></i> Preserved: User Accounts & Roles
                        </span>
                        <span style="color: #15803d; font-weight: 600;">
                            <i class="bi bi-check-circle-fill"></i> Preserved: Store Settings & Logo
                        </span>
                        <span style="color: #b91c1c; font-weight: 600;">
                            <i class="bi bi-x-circle-fill"></i> Cleared: Products & Invoices
                        </span>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-danger" onclick="openClearDataModal()">
                        <i class="bi bi-trash3-fill"></i> Clear All Data
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Clear Data Confirmation -->
<div class="modal-backdrop" id="clear-data-modal">
    <div class="modal-dialog" style="max-width: 520px;">
        <div class="modal-header" style="background: #fef2f2; border-bottom: 1px solid #fee2e2;">
            <div class="modal-title" style="color: #991b1b;">
                <i class="bi bi-exclamation-triangle-fill" style="color: #dc2626;"></i> Confirm Clear System Data
            </div>
            <button type="button" class="modal-close" onclick="closeClearDataModal()">&times;</button>
        </div>
        <form method="POST" action="backup.php">
            <input type="hidden" name="action" value="clear_all_data">
            <div class="modal-body">
                <div style="background: #fef2f2; border-left: 4px solid #ef4444; border-radius: var(--radius-sm); padding: 14px; margin-bottom: 18px;">
                    <p style="font-size: 13.5px; color: #7f1d1d; font-weight: 700; margin: 0 0 6px 0;">
                        Are you sure you want to clear all data?
                    </p>
                    <p style="font-size: 12.5px; color: #991b1b; margin: 0; line-height: 1.5;">
                        This will permanently delete all <strong>Invoices</strong>, <strong>Invoice Items</strong>, <strong>Products</strong>, <strong>Stock Audits</strong>, <strong>Categories</strong>, <strong>Suppliers</strong>, and <strong>Activity Logs</strong>.
                    </p>
                </div>

                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 12px 14px; margin-bottom: 18px;">
                    <div style="font-size: 12.5px; color: #166534; line-height: 1.5;">
                        <i class="bi bi-shield-check" style="font-size: 16px; margin-right: 4px;"></i>
                        <strong>Guaranteed Safety:</strong> <strong>User Accounts</strong> and <strong>Store Settings & Logo</strong> will remain completely intact. An automated safety backup snapshot will be saved to your server before clearing.
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 13px; color: #334155;">
                        <input type="checkbox" id="confirm-clear-checkbox" required style="margin-top: 3px; width: 16px; height: 16px; cursor: pointer;">
                        <span>I understand this will remove all operational data and keep only user accounts and store settings.</span>
                    </label>
                </div>
            </div>

            <div class="modal-footer" style="background: #fafbfc;">
                <button type="button" class="btn btn-outline" onclick="closeClearDataModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash3-fill"></i> Yes, Clear All Data
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Embedded CSS for 2-Column Layout & Authentic Receipt Preview -->
<style>
/* 2-Column Responsive Grid */
.settings-layout {
    display: grid;
    grid-template-columns: 1.18fr 0.82fr;
    gap: 26px;
    align-items: start;
}

@media (max-width: 1100px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
}

/* Sticky Preview Container */
.preview-sticky-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 20px;
    overflow: hidden;
}

.preview-header-bar {
    padding: 14px 20px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.live-pulse-badge {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pulse-dot {
    width: 7px;
    height: 7px;
    background: #10b981;
    border-radius: 50%;
    animation: livePulse 1.6s infinite;
}

@keyframes livePulse {
    0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1.2); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.format-switcher-pills {
    display: inline-flex;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 8px;
    gap: 2px;
}

.format-btn {
    border: none;
    background: transparent;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.format-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.preview-stage {
    padding: 24px 20px;
    background: #f8fafc;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 520px;
    max-height: 780px;
    overflow-y: auto;
}

/* Paper Receipt Thermal Styling */
.paper-receipt {
    background: #ffffff;
    color: #000000;
    font-family: 'JetBrains Mono', Courier, monospace;
    font-size: 11.5px;
    line-height: 1.35;
    padding: 16px 14px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.05);
    position: relative;
    transition: all 0.25s ease;
}

.paper-receipt.thermal-mode {
    width: 310px; /* ~80mm representation on screen */
    border-radius: 4px;
}

.paper-receipt.a4-mode {
    width: 100%;
    max-width: 480px;
    padding: 24px 20px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 12px;
    border-radius: 8px;
}

.mock-receipt-header {
    text-align: center;
    margin-bottom: 10px;
}

.mock-logo-wrap {
    text-align: center;
    margin-bottom: 8px;
}

.mock-logo-img {
    max-height: 48px;
    max-width: 150px;
    object-fit: contain;
    filter: grayscale(100%) contrast(125%);
    transition: all 0.2s ease;
}

.a4-mode .mock-logo-img {
    filter: none !important;
    max-height: 60px;
    max-width: 190px;
}

.mock-store-name {
    font-size: 15px;
    font-weight: 800;
    text-transform: uppercase;
    margin: 0 0 3px 0;
    letter-spacing: -0.02em;
    color: #000000;
}

.mock-subtext {
    font-size: 10.5px;
    margin: 1px 0;
    color: #222222;
}

.mock-divider {
    border-top: 1px dashed #000000;
    margin: 8px 0;
}

.mock-meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    margin-bottom: 2px;
    color: #111111;
}

.mock-table {
    width: 100%;
    border-collapse: collapse;
    margin: 6px 0;
    font-size: 11px;
}

.mock-table th {
    border-bottom: 1px solid #000000;
    padding-bottom: 4px;
    font-weight: 700;
    font-size: 10px;
    color: #000000;
}

.mock-table td {
    padding: 4px 0;
    vertical-align: top;
    color: #000000;
}

.item-name {
    font-weight: 600;
    line-height: 1.25;
}

.item-code {
    font-size: 9.5px;
    color: #444444;
}

.mock-total-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    margin-bottom: 2px;
}

.mock-total-row.grand-total {
    font-size: 14px;
    font-weight: 800;
    border-top: 1px dashed #000000;
    border-bottom: 1px dashed #000000;
    padding: 6px 0;
    margin: 6px 0;
}

.mock-footer {
    text-align: center;
    margin-top: 12px;
    padding-top: 8px;
    border-top: 1px dashed #000000;
}

.mock-barcode {
    display: inline-block;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    padding: 3px 10px;
    font-weight: 800;
    letter-spacing: 3px;
    font-size: 12px;
    margin-bottom: 6px;
    color: #000;
}

.mock-footer-text {
    font-size: 10px;
    margin: 4px 0 2px 0;
    color: #222222;
    line-height: 1.3;
}

.mock-credits {
    font-size: 9px;
    color: #666666;
    margin-top: 4px;
}

.preview-actions-bar {
    padding: 12px 18px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

/* Subtle Perforation Edge */
.paper-tear {
    height: 6px;
    background: radial-gradient(circle, transparent, transparent 50%, #ffffff 50%, #ffffff 100%);
    background-size: 12px 12px;
    display: none;
}
</style>

<!-- Live Sync JavaScript -->
<script>
// Live Synchronized Preview Logic
const inputStoreName = document.getElementById('input-store-name');
const inputStoreTagline = document.getElementById('input-store-tagline');
const inputStorePhone = document.getElementById('input-store-phone');
const inputStoreAddress = document.getElementById('input-store-address');
const inputCurrencySymbol = document.getElementById('input-currency-symbol');
const inputReceiptFooter = document.getElementById('input-receipt-footer');

const mockStoreName = document.getElementById('mock-store-name');
const mockStoreTagline = document.getElementById('mock-store-tagline');
const mockStorePhone = document.getElementById('mock-store-phone');
const mockStoreAddress = document.getElementById('mock-store-address');
const mockReceiptFooter = document.getElementById('mock-receipt-footer');
const mockLogoWrap = document.getElementById('mock-logo-wrap');
const mockReceiptLogo = document.getElementById('mock-receipt-logo');
const currentLogoImg = document.getElementById('current-logo-img');
const noLogoPlaceholder = document.getElementById('no-logo-placeholder');

// Real-time Event Listeners
if (inputStoreName) {
    inputStoreName.addEventListener('input', function() {
        mockStoreName.textContent = this.value.trim() || 'Store / Business Name';
    });
}

if (inputStoreTagline) {
    inputStoreTagline.addEventListener('input', function() {
        mockStoreTagline.textContent = this.value.trim();
        mockStoreTagline.style.display = this.value.trim() ? 'block' : 'none';
    });
}

if (inputStorePhone) {
    inputStorePhone.addEventListener('input', function() {
        mockStorePhone.textContent = this.value.trim() ? ('Tel: ' + this.value.trim()) : '';
        mockStorePhone.style.display = this.value.trim() ? 'block' : 'none';
    });
}

if (inputStoreAddress) {
    inputStoreAddress.addEventListener('input', function() {
        mockStoreAddress.textContent = this.value.trim();
        mockStoreAddress.style.display = this.value.trim() ? 'block' : 'none';
    });
}

if (inputCurrencySymbol) {
    inputCurrencySymbol.addEventListener('input', function() {
        const curr = this.value.trim() || 'Rs.';
        document.querySelectorAll('.mock-curr-val').forEach(function(el) {
            const rawAmt = el.getAttribute('data-amount') || '';
            if (rawAmt.startsWith('-')) {
                const positiveVal = rawAmt.replace('-', '').trim();
                el.textContent = '- ' + curr + ' ' + positiveVal;
            } else {
                el.textContent = curr + ' ' + rawAmt;
            }
        });
    });
}

if (inputReceiptFooter) {
    inputReceiptFooter.addEventListener('input', function() {
        mockReceiptFooter.textContent = this.value.trim() || 'Thank you for your visit!';
    });
}

// Initial Logo State Tracking
const initialLogoSrc = "<?= $hasLogo ? htmlspecialchars($currentLogo) : '' ?>";
const hasInitialLogo = <?= $hasLogo ? 'true' : 'false' ?>;
const btnCancelLogo = document.getElementById('btn-cancel-logo');
const btnRemoveLogo = document.getElementById('btn-remove-logo');
const thumbCancelBtn = document.getElementById('thumb-cancel-btn');

// Logo Selection Real-time Preview via FileReader
function handleLogoSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Reset flags
        document.getElementById('remove-logo-hidden').value = '0';
        document.getElementById('use-sample-logo-hidden').value = '0';

        const reader = new FileReader();
        reader.onload = function(e) {
            const dataUrl = e.target.result;
            
            // Update left thumbnail box
            currentLogoImg.src = dataUrl;
            currentLogoImg.style.display = 'block';
            if (noLogoPlaceholder) noLogoPlaceholder.style.display = 'none';

            // Update right receipt preview logo
            mockReceiptLogo.src = dataUrl;
            mockLogoWrap.style.display = 'block';

            // Show Cancel and Remove options
            if (btnCancelLogo) btnCancelLogo.style.display = 'inline-flex';
            if (btnRemoveLogo) btnRemoveLogo.style.display = 'inline-flex';
            if (thumbCancelBtn) thumbCancelBtn.style.display = 'flex';

            // Flash effect on mock logo
            mockReceiptLogo.style.opacity = '0.3';
            setTimeout(() => { mockReceiptLogo.style.opacity = '1'; }, 100);
        };
        reader.readAsDataURL(file);
    }
}

// Cancel / Revert Logo Selection Action
function cancelLogoSelection() {
    const fileInput = document.getElementById('logo-file-input');
    if (fileInput) fileInput.value = '';
    document.getElementById('remove-logo-hidden').value = '0';
    document.getElementById('use-sample-logo-hidden').value = '0';

    if (hasInitialLogo && initialLogoSrc) {
        // Revert to saved logo
        currentLogoImg.src = initialLogoSrc;
        currentLogoImg.style.display = 'block';
        if (noLogoPlaceholder) noLogoPlaceholder.style.display = 'none';

        mockReceiptLogo.src = initialLogoSrc;
        mockLogoWrap.style.display = 'block';

        if (btnRemoveLogo) btnRemoveLogo.style.display = 'inline-flex';
        if (thumbCancelBtn) thumbCancelBtn.style.display = 'flex';
        if (btnCancelLogo) btnCancelLogo.style.display = 'none';
    } else {
        // Revert to no logo
        currentLogoImg.style.display = 'none';
        if (noLogoPlaceholder) noLogoPlaceholder.style.display = 'block';
        mockLogoWrap.style.display = 'none';

        if (btnRemoveLogo) btnRemoveLogo.style.display = 'none';
        if (thumbCancelBtn) thumbCancelBtn.style.display = 'none';
        if (btnCancelLogo) btnCancelLogo.style.display = 'none';
    }
}

// Remove Logo Action
function removeCurrentLogo() {
    document.getElementById('remove-logo-hidden').value = '1';
    document.getElementById('use-sample-logo-hidden').value = '0';
    const fileInput = document.getElementById('logo-file-input');
    if (fileInput) fileInput.value = '';

    // Hide thumbnails and preview
    if (currentLogoImg) currentLogoImg.style.display = 'none';
    if (noLogoPlaceholder) noLogoPlaceholder.style.display = 'block';
    if (mockLogoWrap) mockLogoWrap.style.display = 'none';

    // Show Cancel button so the user can easily undo/revert before saving!
    if (btnCancelLogo) btnCancelLogo.style.display = 'inline-flex';
    if (btnRemoveLogo) btnRemoveLogo.style.display = 'none';
    if (thumbCancelBtn) thumbCancelBtn.style.display = 'none';
}

// Use Sample Logo Preset Action
function useSampleLogoPreset() {
    const sampleLogoPath = 'assets/img/sample_logo.svg';
    document.getElementById('use-sample-logo-hidden').value = '1';
    document.getElementById('remove-logo-hidden').value = '0';
    const fileInput = document.getElementById('logo-file-input');
    if (fileInput) fileInput.value = '';

    // Update thumbnail
    if (currentLogoImg) {
        currentLogoImg.src = sampleLogoPath;
        currentLogoImg.style.display = 'block';
    }
    if (noLogoPlaceholder) noLogoPlaceholder.style.display = 'none';

    // Update mock receipt logo
    if (mockReceiptLogo) mockReceiptLogo.src = sampleLogoPath;
    if (mockLogoWrap) mockLogoWrap.style.display = 'block';

    // Show Cancel and Remove options
    if (btnCancelLogo) btnCancelLogo.style.display = 'inline-flex';
    if (btnRemoveLogo) btnRemoveLogo.style.display = 'inline-flex';
    if (thumbCancelBtn) thumbCancelBtn.style.display = 'flex';
}

// Switch between Thermal and A4 Preview
function setPreviewFormat(fmt) {
    const container = document.getElementById('paper-receipt-container');
    const btnThermal = document.getElementById('btn-fmt-thermal');
    const btnA4 = document.getElementById('btn-fmt-a4');

    if (fmt === 'a4') {
        container.classList.remove('thermal-mode');
        container.classList.add('a4-mode');
        btnThermal.classList.remove('active');
        btnA4.classList.add('active');
    } else {
        container.classList.remove('a4-mode');
        container.classList.add('thermal-mode');
        btnA4.classList.remove('active');
        btnThermal.classList.add('active');
    }
}

// Print Sample Receipt directly from preview
function printPreviewReceipt() {
    const receiptContent = document.getElementById('paper-receipt-container').outerHTML;
    const printWin = window.open('', '_blank', 'width=450,height=650');
    printWin.document.open();
    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Receipt Print</title>
            <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { font-family: 'JetBrains Mono', monospace; padding: 8px; display: flex; justify-content: center; background: #fff; }
                .paper-receipt { width: 80mm; font-size: 11px; line-height: 1.35; }
                .mock-receipt-header { text-align: center; margin-bottom: 8px; }
                .mock-logo-img { max-height: 48px; max-width: 150px; object-fit: contain; filter: grayscale(100%) contrast(130%); }
                .mock-store-name { font-size: 15px; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
                .mock-subtext { font-size: 10px; margin: 1px 0; }
                .mock-divider { border-top: 1px dashed #000; margin: 6px 0; }
                .mock-meta-row { display: flex; justify-content: space-between; font-size: 10px; margin-bottom: 2px; }
                .mock-table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 10.5px; }
                .mock-table th { border-bottom: 1px solid #000; padding-bottom: 3px; font-size: 10px; text-align: left; }
                .mock-table td { padding: 3px 0; vertical-align: top; }
                .mock-total-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px; }
                .mock-total-row.grand-total { font-size: 14px; font-weight: 800; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; margin: 5px 0; }
                .mock-footer { text-align: center; margin-top: 10px; padding-top: 6px; border-top: 1px dashed #000; }
                .mock-barcode { display: inline-block; padding: 2px 6px; font-weight: 800; letter-spacing: 2px; font-size: 12px; }
                .mock-footer-text { font-size: 9.5px; margin: 4px 0 2px 0; }
                .mock-credits { font-size: 8.5px; color: #555; }
                @media print {
                    @page { size: 80mm auto; margin: 0; }
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            ${receiptContent}
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWin.document.close();
}

// Danger Zone Modal Handlers
function openClearDataModal() {
    const cb = document.getElementById('confirm-clear-checkbox');
    if (cb) cb.checked = false;
    document.getElementById('clear-data-modal').classList.add('show');
}

function closeClearDataModal() {
    document.getElementById('clear-data-modal').classList.remove('show');
}

window.addEventListener('click', function(e) {
    const clearModal = document.getElementById('clear-data-modal');
    if (e.target === clearModal) closeClearDataModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
