<?php
// suppliers.php - Supplier & Distributor Management
$pageTitle = 'Suppliers & Vendors';
$pageIcon = 'bi-truck';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">Suppliers & Book Distributors</h3>
            <p style="font-size: 13px; color: #64748b;">Manage vendor records, contact information, and stock providers</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAddSupplierModal()">
            <i class="bi bi-plus-lg"></i> Add Supplier
        </button>
    </div>

    <div class="content-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Supplier / Contact</th>
                        <th>Company Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address / Location</th>
                        <th style="text-align: center;">Items Supplied</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="suppliers-tbody">
                    <tr><td colspan="7" style="text-align: center; padding: 30px;">Loading suppliers...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Add / Edit Supplier -->
<div class="modal-backdrop" id="supplier-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-truck" style="color: var(--primary);"></i>
                <span id="sup-modal-title">Add Supplier</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('supplier-modal')">&times;</button>
        </div>
        <form onsubmit="saveSupplier(event)">
            <input type="hidden" id="sup-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Contact Person / Name *</label>
                    <input type="text" id="sup-name" class="form-control" required placeholder="e.g. Atlas Stationery Ltd">
                </div>
                <div class="form-group">
                    <label class="form-label">Company / Business Name</label>
                    <input type="text" id="sup-company" class="form-control" placeholder="e.g. Atlas Axillia PLC">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" id="sup-phone" class="form-control" placeholder="011XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" id="sup-email" class="form-control" placeholder="sales@supplier.com">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Office / Warehouse Address</label>
                    <textarea id="sup-address" class="form-control" rows="2" placeholder="Street, City"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('supplier-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
let suppliersList = [];

document.addEventListener('DOMContentLoaded', fetchSuppliers);

async function fetchSuppliers() {
    try {
        const res = await fetch('api/suppliers.php');
        const data = await res.json();
        if (data.status === 'success') {
            suppliersList = data.suppliers;
            renderSuppliers(suppliersList);
        }
    } catch (e) {
        showToast('Error loading suppliers', 'error');
    }
}

function renderSuppliers(items) {
    const tbody = document.getElementById('suppliers-tbody');
    if (!tbody) return;

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 30px;">No suppliers found.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(s => `
        <tr>
            <td><strong>${escapeHtml(s.name)}</strong></td>
            <td>${escapeHtml(s.company || '-')}</td>
            <td style="font-family: var(--font-mono); font-size: 13px;">${escapeHtml(s.phone || '-')}</td>
            <td>${escapeHtml(s.email || '-')}</td>
            <td style="font-size: 13px; color: #64748b;">${escapeHtml(s.address || '-')}</td>
            <td style="text-align: center;">
                <span class="badge badge-neutral">${s.products_count} products</span>
            </td>
            <td style="text-align: right;">
                <button type="button" class="btn btn-outline btn-sm" onclick="openEditSupplierModal(${s.id})">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="deleteSupplier(${s.id})" style="color: var(--danger);">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function openAddSupplierModal() {
    document.getElementById('sup-modal-title').textContent = 'Add Supplier';
    document.getElementById('sup-id').value = '';
    document.getElementById('sup-name').value = '';
    document.getElementById('sup-company').value = '';
    document.getElementById('sup-phone').value = '';
    document.getElementById('sup-email').value = '';
    document.getElementById('sup-address').value = '';
    openModal('supplier-modal');
}

function openEditSupplierModal(id) {
    const s = suppliersList.find(item => item.id == id);
    if (!s) return;

    document.getElementById('sup-modal-title').textContent = 'Edit Supplier';
    document.getElementById('sup-id').value = s.id;
    document.getElementById('sup-name').value = s.name;
    document.getElementById('sup-company').value = s.company || '';
    document.getElementById('sup-phone').value = s.phone || '';
    document.getElementById('sup-email').value = s.email || '';
    document.getElementById('sup-address').value = s.address || '';
    openModal('supplier-modal');
}

async function saveSupplier(e) {
    e.preventDefault();
    const id = document.getElementById('sup-id').value;
    const action = id ? 'update' : 'create';

    const payload = {
        id: id,
        name: document.getElementById('sup-name').value,
        company: document.getElementById('sup-company').value,
        phone: document.getElementById('sup-phone').value,
        email: document.getElementById('sup-email').value,
        address: document.getElementById('sup-address').value
    };

    try {
        const res = await fetch(`api/suppliers.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.status === 'success') {
            closeModal('supplier-modal');
            showToast(data.message, 'success');
            fetchSuppliers();
        } else {
            showToast(data.message || 'Action failed', 'error');
        }
    } catch (e) {
        showToast('Server communication error', 'error');
    }
}

async function deleteSupplier(id) {
    if (!confirm('Are you sure you want to delete this supplier?')) return;

    try {
        const res = await fetch('api/suppliers.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            fetchSuppliers();
        } else {
            showToast(data.message || 'Delete failed', 'error');
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
