<?php
// audits.php - Separate Audits, Stock Reconciliation & Activity Trail Section
$pageTitle = 'Audits & Stock Logs';
$pageIcon = 'bi-clipboard-check-fill';
require_once __DIR__ . '/includes/header.php';

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="page-container">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">Store Audits & Reconciliation</h3>
            <p style="font-size: 13px; color: #64748b;">Conduct physical inventory counts, review stock adjustment history, and examine system audit logs</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-primary" onclick="openReconciliationModal()">
                <i class="bi bi-calculator"></i> Run Physical Stock Audit
            </button>
        </div>
    </div>

    <?php if (!is_admin()): ?>
    <div class="alert alert-info" style="margin-bottom: 20px;">
        <i class="bi bi-person-badge-fill" style="font-size: 18px;"></i>
        <div>
            <strong>Personal Logs View:</strong> You are viewing stock adjustments and activity logs performed by your account (<strong><?= htmlspecialchars($user['name']) ?></strong>).
        </div>
    </div>
    <?php endif; ?>

    <!-- Tab navigation -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
        <button type="button" class="btn btn-outline active" id="tab-btn-stock" onclick="switchAuditTab('stock')">
            <i class="bi bi-box-arrow-in-down"></i> Stock Adjustments & Movements
        </button>
        <button type="button" class="btn btn-outline" id="tab-btn-activity" onclick="switchAuditTab('activity')">
            <i class="bi bi-shield-check"></i> System Activity Trail
        </button>
    </div>

    <!-- Stock Audits Tab Content -->
    <div id="tab-content-stock">
        <!-- Filter Toolbar -->
        <div class="content-card" style="margin-bottom: 20px;">
            <div class="card-body" style="padding: 18px 24px;">
                <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 14px;">
                    <div>
                        <label class="form-label">Audit Event Type</label>
                        <select id="audit-filter-type" class="form-select" onchange="fetchStockAudits()">
                            <option value="">All Adjustment Types</option>
                            <option value="physical_audit">Physical Stock Reconciliation</option>
                            <option value="stock_in">Supplier Delivery / Stock In</option>
                            <option value="damage">Damage & Loss Write-off</option>
                            <option value="sale">POS Sale Deduction</option>
                            <option value="void_return">Voided Invoice Restock</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">From Date</label>
                        <input type="date" id="audit-from" class="form-control" onchange="fetchStockAudits()">
                    </div>
                    <div>
                        <label class="form-label">To Date</label>
                        <input type="date" id="audit-to" class="form-control" onchange="fetchStockAudits()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Audits Table -->
        <div class="content-card">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Product Name</th>
                            <th>Barcode / SKU</th>
                            <th>Audit Type</th>
                            <th style="text-align: center;">Previous Qty</th>
                            <th style="text-align: center;">New Qty</th>
                            <th style="text-align: center;">Variance / Change</th>
                            <th>Audited By</th>
                            <th>Reason / Description</th>
                        </tr>
                    </thead>
                    <tbody id="stock-audits-tbody">
                        <tr><td colspan="9" style="text-align: center; padding: 30px;">Loading audit logs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- System Activity Trail Tab Content -->
    <div id="tab-content-activity" style="display: none;">
        <div class="content-card">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User Account</th>
                            <th>Module</th>
                            <th>Action Performed</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody id="activity-logs-tbody">
                        <tr><td colspan="6" style="text-align: center; padding: 30px;">Loading activity history...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal: Physical Stock Reconciliation Tool -->
<div class="modal-backdrop" id="reconcile-modal">
    <div class="modal-dialog modal-xl">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-clipboard-data" style="color: var(--primary);"></i>
                <span>Physical Inventory Audit & Reconciliation</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('reconcile-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill"></i>
                <div>
                    Enter the counted physical stock quantity on store shelves. The system will calculate discrepancies and update active inventory records with full audit trail logging.
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <select id="recon-cat-filter" class="form-select" style="max-width: 250px;" onchange="loadReconcileItems(this.value)">
                    <option value="">All Physical Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <?php if ($c['type'] === 'goods'): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="recon-search" class="form-control" placeholder="Quick search in audit list..." oninput="filterReconcileRows(this.value)">
            </div>

            <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Barcode</th>
                            <th style="text-align: center; width: 120px;">System Stock</th>
                            <th style="text-align: center; width: 140px;">Physical Count</th>
                            <th style="text-align: center; width: 120px;">Discrepancy</th>
                            <th>Audit Note / Reason</th>
                        </tr>
                    </thead>
                    <tbody id="reconcile-items-tbody">
                        <tr><td colspan="6" style="text-align: center; padding: 20px;">Loading physical items...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('reconcile-modal')">Cancel</button>
            <button type="button" class="btn btn-success" onclick="submitReconciliation()">
                <i class="bi bi-check2-all"></i> Save & Apply Audit Reconciliation
            </button>
        </div>
    </div>
</div>

<script>
let reconcileProducts = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchStockAudits();
});

function switchAuditTab(tab) {
    const isStock = tab === 'stock';
    document.getElementById('tab-btn-stock').classList.toggle('active', isStock);
    document.getElementById('tab-btn-activity').classList.toggle('active', !isStock);

    document.getElementById('tab-content-stock').style.display = isStock ? 'block' : 'none';
    document.getElementById('tab-content-activity').style.display = !isStock ? 'block' : 'none';

    if (isStock) {
        fetchStockAudits();
    } else {
        fetchActivityLogs();
    }
}

async function fetchStockAudits() {
    const type = document.getElementById('audit-filter-type').value;
    const from = document.getElementById('audit-from').value;
    const to = document.getElementById('audit-to').value;

    let url = 'api/audit.php?action=stock_audits';
    if (type) url += `&type=${encodeURIComponent(type)}`;
    if (from) url += `&from=${encodeURIComponent(from)}`;
    if (to) url += `&to=${encodeURIComponent(to)}`;

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.status === 'success') {
            renderStockAudits(data.audits);
        }
    } catch (e) {
        showToast('Error loading stock audits', 'error');
    }
}

function renderStockAudits(items) {
    const tbody = document.getElementById('stock-audits-tbody');
    if (!tbody) return;

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 30px; color: #94a3b8;">No audit records found.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(a => {
        let typeBadge = 'badge-neutral';
        let typeLabel = a.audit_type.replace('_', ' ').toUpperCase();
        if (a.audit_type === 'stock_in') typeBadge = 'badge-success';
        if (a.audit_type === 'damage') typeBadge = 'badge-danger';
        if (a.audit_type === 'physical_audit') typeBadge = 'badge-purple';
        if (a.audit_type === 'void_return') typeBadge = 'badge-warning';

        const changeVal = parseInt(a.qty_change);
        const changeClass = changeVal > 0 ? 'color: #10b981; font-weight: 700;' : (changeVal < 0 ? 'color: #ef4444; font-weight: 700;' : 'color: #64748b;');
        const changePrefix = changeVal > 0 ? '+' : '';

        return `
            <tr>
                <td style="font-size: 13px;">${a.created_at}</td>
                <td><strong>${escapeHtml(a.product_name)}</strong></td>
                <td><span class="barcode-tag">${escapeHtml(a.barcode || a.sku || '-')}</span></td>
                <td><span class="badge ${typeBadge}">${typeLabel}</span></td>
                <td style="text-align: center; font-family: var(--font-mono);">${a.previous_qty}</td>
                <td style="text-align: center; font-family: var(--font-mono); font-weight: 700;">${a.new_qty}</td>
                <td style="text-align: center; font-family: var(--font-mono); ${changeClass}">
                    ${changePrefix}${changeVal}
                </td>
                <td>${escapeHtml(a.user_name || 'System')}</td>
                <td style="font-size: 12.5px; color: #475569;">${escapeHtml(a.reason || '-')}</td>
            </tr>
        `;
    }).join('');
}

async function fetchActivityLogs() {
    try {
        const res = await fetch('api/audit.php?action=activity_logs');
        const data = await res.json();
        if (data.status === 'success') {
            renderActivityLogs(data.logs);
        }
    } catch (e) {
        showToast('Error loading activity logs', 'error');
    }
}

function renderActivityLogs(logs) {
    const tbody = document.getElementById('activity-logs-tbody');
    if (!tbody) return;

    if (!logs || logs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8;">No activity logged yet.</td></tr>`;
        return;
    }

    tbody.innerHTML = logs.map(l => `
        <tr>
            <td style="font-size: 13px;">${l.created_at}</td>
            <td><strong>${escapeHtml(l.user_name || 'System')}</strong></td>
            <td><span class="badge badge-info">${escapeHtml(l.module)}</span></td>
            <td><strong>${escapeHtml(l.action)}</strong></td>
            <td style="font-size: 13px;">${escapeHtml(l.details || '-')}</td>
            <td style="font-family: var(--font-mono); font-size: 12px; color: #64748b;">${escapeHtml(l.ip_address || '-')}</td>
        </tr>
    `).join('');
}

// Physical Inventory Reconciliation Tool
async function openReconciliationModal() {
    openModal('reconcile-modal');
    loadReconcileItems();
}

async function loadReconcileItems(catId = '') {
    const tbody = document.getElementById('reconcile-items-tbody');
    try {
        let url = 'api/pos_search.php';
        if (catId) url += `?category_id=${encodeURIComponent(catId)}`;

        const res = await fetch(url);
        const data = await res.json();
        if (data.status === 'success') {
            reconcileProducts = data.items.filter(p => p.item_type === 'physical');
            renderReconcileRows(reconcileProducts);
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: red;">Error loading products</td></tr>`;
    }
}

function renderReconcileRows(items) {
    const tbody = document.getElementById('reconcile-items-tbody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">No physical products found in category.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(p => `
        <tr class="recon-row" data-name="${escapeHtml(p.name.toLowerCase())}" data-code="${escapeHtml((p.barcode + ' ' + p.sku).toLowerCase())}">
            <td><strong>${escapeHtml(p.name)}</strong></td>
            <td><span class="barcode-tag">${escapeHtml(p.barcode)}</span></td>
            <td style="text-align: center; font-family: var(--font-mono); font-weight: 700;" id="sys-qty-${p.id}">${p.stock_qty}</td>
            <td style="text-align: center;">
                <input type="number" class="form-control" style="width: 100px; text-align: center; margin: 0 auto; font-family: var(--font-mono); font-weight: 700;" 
                       id="count-qty-${p.id}" value="${p.stock_qty}" oninput="calcReconDiff(${p.id}, ${p.stock_qty})">
            </td>
            <td style="text-align: center; font-family: var(--font-mono); font-weight: 700;" id="diff-qty-${p.id}">
                0
            </td>
            <td>
                <input type="text" class="form-control" id="reason-${p.id}" placeholder="Note (if adjusted)" style="font-size: 12px;">
            </td>
        </tr>
    `).join('');
}

function filterReconcileRows(term) {
    const lower = term.toLowerCase().trim();
    document.querySelectorAll('.recon-row').forEach(row => {
        const name = row.getAttribute('data-name');
        const code = row.getAttribute('data-code');
        if (!lower || name.includes(lower) || code.includes(lower)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function calcReconDiff(id, sysQty) {
    const countVal = parseInt(document.getElementById(`count-qty-${id}`).value) || 0;
    const diff = countVal - sysQty;
    const diffEl = document.getElementById(`diff-qty-${id}`);

    if (diff > 0) {
        diffEl.textContent = `+${diff}`;
        diffEl.style.color = '#10b981';
    } else if (diff < 0) {
        diffEl.textContent = `${diff}`;
        diffEl.style.color = '#ef4444';
    } else {
        diffEl.textContent = '0';
        diffEl.style.color = '#64748b';
    }
}

async function submitReconciliation() {
    const itemsToUpdate = [];

    reconcileProducts.forEach(p => {
        const countInput = document.getElementById(`count-qty-${p.id}`);
        if (!countInput) return;

        const countVal = parseInt(countInput.value) || 0;
        const diff = countVal - parseInt(p.stock_qty);
        const reason = document.getElementById(`reason-${p.id}`)?.value.trim() || 'Physical Stock Audit Reconciliation';

        if (diff !== 0) {
            itemsToUpdate.push({
                product_id: p.id,
                counted_qty: countVal,
                reason: reason
            });
        }
    });

    if (itemsToUpdate.length === 0) {
        showToast('No differences detected. Inventory counts match perfectly!', 'info');
        closeModal('reconcile-modal');
        return;
    }

    if (!confirm(`Apply audit corrections to ${itemsToUpdate.length} item(s)? This will adjust inventory and log stock audits.`)) {
        return;
    }

    try {
        const res = await fetch('api/audit.php?action=reconcile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: itemsToUpdate })
        });
        const data = await res.json();

        if (data.status === 'success') {
            closeModal('reconcile-modal');
            showToast(data.message, 'success');
            fetchStockAudits();
        } else {
            showToast(data.message || 'Audit update failed', 'error');
        }
    } catch (e) {
        showToast('Server communication error', 'error');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
