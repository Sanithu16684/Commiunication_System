<?php
// settings.php - Store and System Configuration (Admin only)
$pageTitle = 'Store Settings';
$pageIcon = 'bi-gear-fill';
require_once __DIR__ . '/includes/header.php';
require_admin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsToUpdate = [
        'store_name' => trim($_POST['store_name'] ?? ''),
        'store_tagline' => trim($_POST['store_tagline'] ?? ''),
        'store_phone' => trim($_POST['store_phone'] ?? ''),
        'store_email' => trim($_POST['store_email'] ?? ''),
        'store_address' => trim($_POST['store_address'] ?? ''),
        'currency_symbol' => trim($_POST['currency_symbol'] ?? 'Rs.'),
        'receipt_footer' => trim($_POST['receipt_footer'] ?? '')
    ];

    $updStmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    foreach ($settingsToUpdate as $k => $v) {
        $updStmt->execute([$k, $v, $v]);
    }

    log_activity($pdo, $user['id'], 'Updated Settings', 'Settings', "Updated store general settings");
    $msg = 'Settings updated successfully.';
    $settings = get_settings($pdo);
}
?>

<div class="page-container" style="max-width: 860px;">
    
    <div style="margin-bottom: 24px;">
        <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">Store & Receipt Information</h3>
        <p style="font-size: 13px; color: #64748b;">Configure details that appear on printed receipts, invoices, and the POS system</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="content-card">
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Store / Business Name *</label>
                    <input type="text" name="store_name" class="form-control" required value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>" placeholder="e.g. Metro Communication & Bookshop">
                </div>

                <div class="form-group">
                    <label class="form-label">Tagline / Subheading</label>
                    <input type="text" name="store_tagline" class="form-control" value="<?= htmlspecialchars($settings['store_tagline'] ?? '') ?>" placeholder="e.g. Books, Stationery, Telecom Services & Xerox">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Contact Phone Number(s)</label>
                        <input type="text" name="store_phone" class="form-control" value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>" placeholder="0112345678 / 0771234567">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Store Email Address</label>
                        <input type="email" name="store_email" class="form-control" value="<?= htmlspecialchars($settings['store_email'] ?? '') ?>" placeholder="contact@shop.com">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Physical Address</label>
                    <textarea name="store_address" class="form-control" rows="2"><?= htmlspecialchars($settings['store_address'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Currency Symbol</label>
                    <input type="text" name="currency_symbol" class="form-control" style="max-width: 150px; font-family: var(--font-mono);" value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'Rs.') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Receipt Footer Message / Return Policy</label>
                    <textarea name="receipt_footer" class="form-control" rows="3"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
                    <span style="font-size: 11px; color: #64748b;">Printed at the bottom of 80mm thermal receipts and A4 invoices.</span>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 10px;">
                    <i class="bi bi-check2-circle"></i> Save Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Database Backup & Maintenance Section -->
    <div class="content-card">
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
    <div class="content-card" style="border: 1px solid #fecaca;">
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
                            <i class="bi bi-check-circle-fill"></i> Preserved: Store Settings
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
                        <strong>Guaranteed Safety:</strong> <strong>User Accounts</strong> and <strong>Store Settings</strong> will remain completely intact. An automated safety backup snapshot will be saved to your server before clearing.
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

<script>
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
