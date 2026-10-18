<?php
// invoices.php - Separate Invoices & Sales Management Section
$pageTitle = 'Invoices & Sales History';
$pageIcon = 'bi-receipt-cutoff';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-container">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">Sales Invoices</h3>
            <p style="font-size: 13px; color: #64748b;">Review sales transactions, view line items, reprint receipts, and process returns/voids</p>
        </div>
        <div>
            <a href="pos.php" class="btn btn-primary">
                <i class="bi bi-cart-plus"></i> New Sale (POS)
            </a>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="content-card" style="margin-bottom: 20px;">
        <div class="card-body" style="padding: 18px 24px;">
            <div style="display: grid; grid-template-columns: 2fr 1.2fr 1.2fr 1fr 1fr; gap: 14px;">
                <div>
                    <label class="form-label">Search Invoices</label>
                    <input type="text" id="inv-search" class="form-control" placeholder="Search by Invoice #, Customer name, Phone..." oninput="fetchInvoices()">
                </div>
                <div>
                    <label class="form-label">From Date</label>
                    <input type="date" id="inv-from" class="form-control" onchange="fetchInvoices()">
                </div>
                <div>
                    <label class="form-label">To Date</label>
                    <input type="date" id="inv-to" class="form-control" onchange="fetchInvoices()">
                </div>
                <div>
                    <label class="form-label">Payment Method</label>
                    <select id="inv-method" class="form-select" onchange="fetchInvoices()">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="qr">LankaQR</option>
                        <option value="transfer">Bank Transfer</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select id="inv-status" class="form-select" onchange="fetchInvoices()">
                        <option value="">All Statuses</option>
                        <option value="completed">Completed</option>
                        <option value="voided">Voided / Cancelled</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="content-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Items</th>
                        <th>Payment</th>
                        <th style="text-align: right;">Net Total</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="invoices-tbody">
                    <tr><td colspan="9" style="text-align: center; padding: 30px;">Loading invoices...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: View Invoice Details -->
<div class="modal-backdrop" id="invoice-details-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-receipt" style="color: var(--primary);"></i>
                <span id="detail-inv-title">Invoice Details</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('invoice-details-modal')">&times;</button>
        </div>
        <div class="modal-body" id="invoice-details-content">
            <!-- Populated via invoices.js -->
        </div>
        <div class="modal-footer" id="invoice-details-footer">
            <!-- Buttons dynamically populated -->
        </div>
    </div>
</div>

<!-- Modal: Void Invoice Confirmation -->
<div class="modal-backdrop" id="void-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title" style="color: var(--danger);">
                <i class="bi bi-x-octagon-fill"></i>
                <span>Void / Cancel Invoice</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('void-modal')">&times;</button>
        </div>
        <form onsubmit="confirmVoidInvoice(event)">
            <input type="hidden" id="void-invoice-id">
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Warning:</strong> Voiding this invoice will cancel the transaction and automatically restock all items back into active inventory!
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Reason for Voiding *</label>
                    <textarea id="void-reason" class="form-control" rows="3" required placeholder="e.g. Customer returned goods, entered in error by cashier..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('void-modal')">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x-circle"></i> Confirm Void & Restock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let invoicesList = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchInvoices();
});

async function fetchInvoices() {
    const q = document.getElementById('inv-search').value.trim();
    const from = document.getElementById('inv-from').value;
    const to = document.getElementById('inv-to').value;
    const method = document.getElementById('inv-method').value;
    const status = document.getElementById('inv-status').value;

    let url = 'api/invoices.php?';
    if (q) url += `&q=${encodeURIComponent(q)}`;
    if (from) url += `&from=${encodeURIComponent(from)}`;
    if (to) url += `&to=${encodeURIComponent(to)}`;
    if (method) url += `&method=${encodeURIComponent(method)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;

    try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.status === 'success') {
            invoicesList = data.invoices;
            renderInvoicesTable(invoicesList);
        }
    } catch (e) {
        showToast('Error loading invoices', 'error');
    }
}

function renderInvoicesTable(items) {
    const tbody = document.getElementById('invoices-tbody');
    if (!tbody) return;

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 40px; color: #94a3b8;">No invoices found matching criteria.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(i => {
        const statusBadge = i.status === 'completed' ? 'badge-success' : (i.status === 'voided' ? 'badge-danger' : 'badge-warning');

        return `
            <tr>
                <td>
                    <strong style="color: var(--primary); font-family: var(--font-mono);">${escapeHtml(i.invoice_no)}</strong>
                </td>
                <td style="font-size: 13px;">${i.created_at}</td>
                <td>
                    <strong>${escapeHtml(i.customer_name)}</strong>
                    ${i.customer_phone ? `<div style="font-size: 11px; color: #64748b;">${escapeHtml(i.customer_phone)}</div>` : ''}
                </td>
                <td>${escapeHtml(i.cashier_name || 'Staff')}</td>
                <td><span class="badge badge-neutral">${i.total_items || 0} items</span></td>
                <td><span class="badge badge-info">${i.payment_method.toUpperCase()}</span></td>
                <td style="text-align: right; font-family: var(--font-mono); font-weight: 700; font-size: 14px;">
                    Rs. ${parseFloat(i.total_amount).toFixed(2)}
                </td>
                <td style="text-align: center;">
                    <span class="badge ${statusBadge}">${i.status.toUpperCase()}</span>
                </td>
                <td style="text-align: right;">
                    <div style="display: inline-flex; gap: 6px;">
                        <button type="button" class="btn btn-outline btn-sm" onclick="viewInvoiceDetails(${i.id})" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                        <a href="print_receipt.php?id=${i.id}&format=thermal" target="_blank" class="btn btn-outline btn-sm" title="Reprint Thermal Receipt">
                            <i class="bi bi-printer"></i>
                        </a>
                        <a href="print_receipt.php?id=${i.id}&format=a4" target="_blank" class="btn btn-outline btn-sm" title="A4 Invoice">
                            <i class="bi bi-file-earmark-text"></i>
                        </a>
                        <?php if (is_admin()): ?>
                        ${i.status === 'completed' ? `
                        <button type="button" class="btn btn-outline btn-sm" onclick="openVoidModal(${i.id})" title="Void / Cancel Invoice" style="color: var(--danger);">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        ` : ''}
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

async function viewInvoiceDetails(id) {
    try {
        const res = await fetch(`api/invoices.php?id=${id}`);
        const data = await res.json();

        if (data.status === 'success') {
            const inv = data.invoice;
            const items = data.items;

            document.getElementById('detail-inv-title').textContent = `Invoice #${inv.invoice_no}`;

            let html = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b;">Customer Information</div>
                        <strong style="font-size: 14px; color: #0f172a;">${escapeHtml(inv.customer_name)}</strong>
                        <div style="font-size: 12px; color: #475569;">${escapeHtml(inv.customer_phone || 'No phone provided')}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; color: #64748b;">Date & Cashier</div>
                        <div style="font-size: 13px; font-weight: 600;">${inv.created_at}</div>
                        <div style="font-size: 12px; color: #475569;">Served by: ${escapeHtml(inv.cashier_name || 'Staff')}</div>
                    </div>
                </div>

                <h5 style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">Purchased Items</h5>
                <table class="custom-table" style="margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Code</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Unit Price</th>
                            <th style="text-align: right;">Line Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(it => `
                            <tr>
                                <td><strong>${escapeHtml(it.product_name)}</strong></td>
                                <td style="font-family: var(--font-mono); font-size: 11px;">${escapeHtml(it.barcode || '-')}</td>
                                <td style="text-align: center;">${it.quantity}</td>
                                <td style="text-align: right; font-family: var(--font-mono);">Rs. ${parseFloat(it.unit_price).toFixed(2)}</td>
                                <td style="text-align: right; font-family: var(--font-mono); font-weight: 700;">Rs. ${parseFloat(it.subtotal).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div style="max-width: 320px; margin-left: auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 13px;">
                        <span>Subtotal:</span>
                        <span>Rs. ${parseFloat(inv.subtotal).toFixed(2)}</span>
                    </div>
                    ${parseFloat(inv.discount_amount) > 0 ? `
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 13px; color: #ef4444;">
                        <span>Discount:</span>
                        <span>- Rs. ${parseFloat(inv.discount_amount).toFixed(2)}</span>
                    </div>` : ''}
                    <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; border-top: 1px dashed #cbd5e1; padding-top: 6px; margin-top: 6px;">
                        <span>Net Total:</span>
                        <span style="color: var(--primary); font-family: var(--font-mono);">Rs. ${parseFloat(inv.total_amount).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 12px; color: #64748b;">
                        <span>Payment (${inv.payment_method.toUpperCase()}):</span>
                        <span>Paid: Rs. ${parseFloat(inv.amount_paid).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #059669; font-weight: 600;">
                        <span>Change Given:</span>
                        <span>Rs. ${parseFloat(inv.change_amount).toFixed(2)}</span>
                    </div>
                </div>
            `;

            if (inv.notes) {
                html += `<div style="margin-top: 14px; font-size: 12.5px; color: #64748b;"><em>Note: ${escapeHtml(inv.notes)}</em></div>`;
            }

            document.getElementById('invoice-details-content').innerHTML = html;

            document.getElementById('invoice-details-footer').innerHTML = `
                <a href="print_receipt.php?id=${inv.id}&format=thermal" target="_blank" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print Thermal Receipt
                </a>
                <a href="print_receipt.php?id=${inv.id}&format=a4" target="_blank" class="btn btn-outline">
                    <i class="bi bi-file-earmark-text"></i> A4 Invoice
                </a>
                <button type="button" class="btn btn-outline" onclick="closeModal('invoice-details-modal')">Close</button>
            `;

            openModal('invoice-details-modal');
        }
    } catch (e) {
        showToast('Error loading invoice details', 'error');
    }
}

function openVoidModal(id) {
    document.getElementById('void-invoice-id').value = id;
    document.getElementById('void-reason').value = '';
    openModal('void-modal');
}

async function confirmVoidInvoice(e) {
    e.preventDefault();
    const id = document.getElementById('void-invoice-id').value;
    const reason = document.getElementById('void-reason').value;

    try {
        const res = await fetch('api/invoices.php?action=void', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ invoice_id: id, reason: reason })
        });
        const data = await res.json();

        if (data.status === 'success') {
            closeModal('void-modal');
            showToast(data.message, 'success');
            fetchInvoices();
        } else {
            showToast(data.message || 'Void operation failed', 'error');
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
