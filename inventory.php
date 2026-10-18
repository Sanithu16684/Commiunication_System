<?php
// inventory.php - Complete Inventory Management Section
$pageTitle = 'Inventory Management';
$pageIcon = 'bi-box-seam-fill';
require_once __DIR__ . '/includes/header.php';

// Fetch categories & suppliers for dropdowns
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();
?>

<div class="page-container">
    
    <!-- Header Bar with Action Buttons -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">Store Products & Services</h3>
            <p style="font-size: 13px; color: #64748b;">Manage physical merchandise, stationery, books, and communication services</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="barcode_generator.php" class="btn btn-outline">
                <i class="bi bi-upc"></i> Print Barcode Labels
            </a>
            <button type="button" class="btn btn-primary" onclick="openAddProductModal()">
                <i class="bi bi-plus-lg"></i> Add New Item
            </button>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="content-card" style="margin-bottom: 20px;">
        <div class="card-body" style="padding: 18px 24px;">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 14px;">
                <div>
                    <label class="form-label">Search Products</label>
                    <div style="position: relative;">
                        <input type="text" id="filter-search" class="form-control" placeholder="Search by name, barcode, or SKU..." oninput="filterProducts()">
                    </div>
                </div>
                <div>
                    <label class="form-label">Category</label>
                    <select id="filter-category" class="form-select" onchange="filterProducts()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Item Type</label>
                    <select id="filter-type" class="form-select" onchange="filterProducts()">
                        <option value="">All Types</option>
                        <option value="physical">Physical Goods</option>
                        <option value="service">Services (Xerox/Print)</option>
                        <option value="reload">Mobile Reloads</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Stock Status</label>
                    <select id="filter-stock" class="form-select" onchange="filterProducts()">
                        <option value="">All Statuses</option>
                        <option value="low">Low Stock Alert</option>
                        <option value="out">Out of Stock</option>
                        <option value="in">Normal Stock</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="content-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Barcode & SKU</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th style="text-align: right;">Cost Price</th>
                        <th style="text-align: right;">Selling Price</th>
                        <th style="text-align: center;">Stock Level</th>
                        <th>Supplier</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="inventory-tbody">
                    <tr><td colspan="9" style="text-align: center; padding: 30px;">Loading inventory items...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Add / Edit Product -->
<div class="modal-backdrop" id="product-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-box-seam" style="color: var(--primary);"></i>
                <span id="product-modal-title">Add New Product</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('product-modal')">&times;</button>
        </div>
        <form id="product-form" onsubmit="saveProduct(event)">
            <input type="hidden" id="prod-id" name="id">
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Item Name *</label>
                        <input type="text" id="prod-name" name="name" class="form-control" required placeholder="e.g. Atlas CR 120 Pages Book">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select id="prod-category" name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Barcode (Scan or Auto)</label>
                        <div style="display: flex; gap: 6px;">
                            <input type="text" id="prod-barcode" name="barcode" class="form-control" placeholder="Scan or leave blank to auto-generate">
                            <button type="button" class="btn btn-outline btn-sm" onclick="generateRandomBarcode()" title="Generate Barcode">
                                <i class="bi bi-magic"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SKU / Item Code</label>
                        <input type="text" id="prod-sku" name="sku" class="form-control" placeholder="e.g. BK-CR-01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Item Type</label>
                        <select id="prod-type" name="item_type" class="form-select" onchange="toggleStockFields(this.value)">
                            <option value="physical">Physical Goods</option>
                            <option value="service">Service (Xerox/Print)</option>
                            <option value="reload">Mobile Reload</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Cost Price (Rs.)</label>
                        <input type="number" step="0.01" id="prod-cost" name="cost_price" class="form-control" required value="0.00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Selling Price (Rs.) *</label>
                        <input type="number" step="0.01" id="prod-selling" name="selling_price" class="form-control" required value="0.00">
                    </div>
                </div>

                <div id="stock-fields-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Stock Quantity</label>
                        <input type="number" id="prod-stock" name="stock_qty" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Low Stock Alert Level</label>
                        <input type="number" id="prod-min-stock" name="min_stock_alert" class="form-control" value="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit of Measure</label>
                        <input type="text" id="prod-unit" name="unit" class="form-control" placeholder="pcs, book, box, page" value="pcs">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Supplier / Distributor</label>
                        <select id="prod-supplier" name="supplier_id" class="form-select">
                            <option value="">None / Direct</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['company']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="status-group" style="display: none;">
                        <label class="form-label">Status</label>
                        <select id="prod-status" name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('product-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btn-save-prod">
                    <i class="bi bi-check-lg"></i> Save Product
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Quick Stock Adjustment / Stock In -->
<div class="modal-backdrop" id="adjust-stock-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-arrow-left-right" style="color: var(--primary);"></i>
                <span>Adjust Stock Quantity</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('adjust-stock-modal')">&times;</button>
        </div>
        <form id="adjust-stock-form" onsubmit="saveStockAdjustment(event)">
            <input type="hidden" id="adj-prod-id" name="product_id">
            <div class="modal-body">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 14px; margin-bottom: 16px;">
                    <strong id="adj-prod-name" style="color: #0f172a; font-size: 14px; display: block; margin-bottom: 4px;">Product Name</strong>
                    <span style="font-size: 12.5px; color: #64748b;">Current In-Stock: <strong id="adj-curr-stock" style="color: var(--primary); font-family: var(--font-mono);">0</strong></span>
                </div>

                <div class="form-group">
                    <label class="form-label">Adjustment Type</label>
                    <select id="adj-type" name="adjustment_type" class="form-select" onchange="handleAdjustmentTypeChange(this.value)">
                        <option value="stock_in">Supplier Delivery / Stock In (+)</option>
                        <option value="damage">Damaged / Expired / Lost (-)</option>
                        <option value="physical_audit">Physical Count Correction (+/-)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" id="adj-qty-label">Quantity to Add</label>
                    <input type="number" id="adj-qty" name="qty_change" class="form-control" required min="1" placeholder="Enter quantity">
                </div>

                <div class="form-group">
                    <label class="form-label">Reason / Notes (Recorded in Audit Trail)</label>
                    <input type="text" id="adj-reason" name="reason" class="form-control" required placeholder="e.g. Invoice #PO-902 received or Damaged during transit">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('adjust-stock-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Apply Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let inventoryList = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchInventory();
});

async function fetchInventory() {
    try {
        const res = await fetch('api/products.php');
        const data = await res.json();
        if (data.status === 'success') {
            inventoryList = data.products;
            renderInventoryTable(inventoryList);
        }
    } catch (e) {
        showToast('Error loading inventory list', 'error');
    }
}

function renderInventoryTable(items) {
    const tbody = document.getElementById('inventory-tbody');
    if (!tbody) return;

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 40px; color: #94a3b8;">No products found matching filters.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(p => {
        const isLow = p.item_type === 'physical' && parseInt(p.stock_qty) <= parseInt(p.min_stock_alert) && parseInt(p.stock_qty) > 0;
        const isOut = p.item_type === 'physical' && parseInt(p.stock_qty) <= 0;

        let stockBadge = '';
        if (p.item_type === 'physical') {
            if (isOut) {
                stockBadge = `<span class="badge badge-danger">Out of Stock (0)</span>`;
            } else if (isLow) {
                stockBadge = `<span class="badge badge-warning">Low: ${p.stock_qty} ${p.unit}</span>`;
            } else {
                stockBadge = `<span class="badge badge-success">${p.stock_qty} ${p.unit}</span>`;
            }
        } else {
            stockBadge = `<span class="badge badge-purple">Service</span>`;
        }

        const typeBadge = p.item_type === 'physical' ? 'badge-info' : (p.item_type === 'reload' ? 'badge-success' : 'badge-warning');

        return `
            <tr>
                <td>
                    <div class="barcode-tag">${escapeHtml(p.barcode)}</div>
                    <div style="font-size: 11px; color: #64748b; font-family: var(--font-mono); margin-top: 2px;">SKU: ${escapeHtml(p.sku)}</div>
                </td>
                <td>
                    <strong style="color: #0f172a;">${escapeHtml(p.name)}</strong>
                </td>
                <td>${escapeHtml(p.category_name || 'Uncategorized')}</td>
                <td><span class="badge ${typeBadge}">${p.item_type.toUpperCase()}</span></td>
                <td style="text-align: right; font-family: var(--font-mono);">Rs. ${parseFloat(p.cost_price).toFixed(2)}</td>
                <td style="text-align: right; font-family: var(--font-mono); font-weight: 700; color: var(--primary);">Rs. ${parseFloat(p.selling_price).toFixed(2)}</td>
                <td style="text-align: center;">${stockBadge}</td>
                <td>${escapeHtml(p.supplier_name || '-')}</td>
                <td style="text-align: right;">
                    <div style="display: inline-flex; gap: 6px;">
                        ${p.item_type === 'physical' ? `
                        <button type="button" class="btn btn-outline btn-sm" onclick="openAdjustStockModal(${p.id})" title="Adjust Stock / Stock In">
                            <i class="bi bi-plus-slash-minus"></i>
                        </button>
                        ` : ''}
                        <a href="barcode_generator.php?barcode=${encodeURIComponent(p.barcode)}" class="btn btn-outline btn-sm" title="Print Barcode Sticker">
                            <i class="bi bi-upc"></i>
                        </a>
                        <button type="button" class="btn btn-outline btn-sm" onclick="openEditProductModal(${p.id})" title="Edit Details">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <?php if (is_admin()): ?>
                        <button type="button" class="btn btn-outline btn-sm" onclick="deleteProduct(${p.id})" title="Delete Item" style="color: var(--danger);">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function filterProducts() {
    const q = document.getElementById('filter-search').value.toLowerCase().trim();
    const cat = document.getElementById('filter-category').value;
    const type = document.getElementById('filter-type').value;
    const stock = document.getElementById('filter-stock').value;

    const filtered = inventoryList.filter(p => {
        const matchesQ = !q || p.name.toLowerCase().includes(q) || p.barcode.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q);
        const matchesCat = !cat || p.category_id == cat;
        const matchesType = !type || p.item_type == type;

        let matchesStock = true;
        if (stock === 'low') {
            matchesStock = p.item_type === 'physical' && parseInt(p.stock_qty) <= parseInt(p.min_stock_alert) && parseInt(p.stock_qty) > 0;
        } else if (stock === 'out') {
            matchesStock = p.item_type === 'physical' && parseInt(p.stock_qty) <= 0;
        } else if (stock === 'in') {
            matchesStock = p.item_type === 'physical' && parseInt(p.stock_qty) > parseInt(p.min_stock_alert);
        }

        return matchesQ && matchesCat && matchesType && matchesStock;
    });

    renderInventoryTable(filtered);
}

function openAddProductModal() {
    document.getElementById('product-modal-title').textContent = 'Add New Product';
    document.getElementById('prod-id').value = '';
    document.getElementById('product-form').reset();
    document.getElementById('status-group').style.display = 'none';
    document.getElementById('stock-fields-row').style.display = 'grid';
    generateRandomBarcode();
    openModal('product-modal');
}

function generateRandomBarcode() {
    const code = '890' + Math.floor(1000000 + Math.random() * 9000000);
    document.getElementById('prod-barcode').value = code;
}

function toggleStockFields(type) {
    const row = document.getElementById('stock-fields-row');
    if (type === 'physical') {
        row.style.display = 'grid';
    } else {
        row.style.display = 'none';
    }
}

function openEditProductModal(id) {
    const p = inventoryList.find(item => item.id == id);
    if (!p) return;

    document.getElementById('product-modal-title').textContent = 'Edit Product Details';
    document.getElementById('prod-id').value = p.id;
    document.getElementById('prod-name').value = p.name;
    document.getElementById('prod-category').value = p.category_id || '';
    document.getElementById('prod-barcode').value = p.barcode;
    document.getElementById('prod-sku').value = p.sku;
    document.getElementById('prod-type').value = p.item_type;
    document.getElementById('prod-cost').value = p.cost_price;
    document.getElementById('prod-selling').value = p.selling_price;
    document.getElementById('prod-stock').value = p.stock_qty;
    document.getElementById('prod-min-stock').value = p.min_stock_alert;
    document.getElementById('prod-unit').value = p.unit;
    document.getElementById('prod-supplier').value = p.supplier_id || '';
    document.getElementById('prod-status').value = p.status;
    document.getElementById('status-group').style.display = 'block';

    toggleStockFields(p.item_type);
    openModal('product-modal');
}

async function saveProduct(e) {
    e.preventDefault();
    const id = document.getElementById('prod-id').value;
    const action = id ? 'update' : 'create';

    const payload = {
        id: id,
        name: document.getElementById('prod-name').value,
        category_id: document.getElementById('prod-category').value,
        barcode: document.getElementById('prod-barcode').value,
        sku: document.getElementById('prod-sku').value,
        item_type: document.getElementById('prod-type').value,
        cost_price: document.getElementById('prod-cost').value,
        selling_price: document.getElementById('prod-selling').value,
        stock_qty: document.getElementById('prod-stock').value,
        min_stock_alert: document.getElementById('prod-min-stock').value,
        unit: document.getElementById('prod-unit').value,
        supplier_id: document.getElementById('prod-supplier').value,
        status: document.getElementById('prod-status').value || 'active'
    };

    try {
        const res = await fetch(`api/products.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.status === 'success') {
            closeModal('product-modal');
            showToast(data.message, 'success');
            fetchInventory();
        } else {
            showToast(data.message || 'Operation failed', 'error');
        }
    } catch (e) {
        showToast('Server communication error', 'error');
    }
}

async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) return;

    try {
        const res = await fetch('api/products.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            fetchInventory();
        } else {
            showToast(data.message || 'Delete failed', 'error');
        }
    } catch (e) {
        showToast('Server communication error', 'error');
    }
}

// Adjust Stock Modal Handlers
function openAdjustStockModal(id) {
    const p = inventoryList.find(item => item.id == id);
    if (!p) return;

    document.getElementById('adj-prod-id').value = p.id;
    document.getElementById('adj-prod-name').textContent = p.name;
    document.getElementById('adj-curr-stock').textContent = `${p.stock_qty} ${p.unit}`;
    document.getElementById('adj-type').value = 'stock_in';
    document.getElementById('adj-qty').value = '';
    document.getElementById('adj-reason').value = '';
    handleAdjustmentTypeChange('stock_in');

    openModal('adjust-stock-modal');
}

function handleAdjustmentTypeChange(type) {
    const lbl = document.getElementById('adj-qty-label');
    if (type === 'stock_in') {
        lbl.textContent = 'Quantity to Add (+)';
    } else if (type === 'damage') {
        lbl.textContent = 'Quantity to Deduct (-)';
    } else {
        lbl.textContent = 'Quantity Difference (+ or -)';
    }
}

async function saveStockAdjustment(e) {
    e.preventDefault();
    const type = document.getElementById('adj-type').value;
    let qtyChange = parseInt(document.getElementById('adj-qty').value) || 0;

    if (type === 'damage' && qtyChange > 0) {
        qtyChange = -qtyChange;
    }

    const payload = {
        product_id: document.getElementById('adj-prod-id').value,
        adjustment_type: type,
        qty_change: qtyChange,
        reason: document.getElementById('adj-reason').value
    };

    try {
        const res = await fetch('api/products.php?action=adjust_stock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.status === 'success') {
            closeModal('adjust-stock-modal');
            showToast(data.message, 'success');
            fetchInventory();
        } else {
            showToast(data.message || 'Adjustment failed', 'error');
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
