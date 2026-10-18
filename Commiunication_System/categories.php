<?php
// categories.php - Category Management
$pageTitle = 'Product & Service Categories';
$pageIcon = 'bi-tags-fill';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">Categories</h3>
            <p style="font-size: 13px; color: #64748b;">Organize books, stationery, communication goods, and service offerings</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAddCategoryModal()">
            <i class="bi bi-plus-lg"></i> Add Category
        </button>
    </div>

    <div class="content-card">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Icon</th>
                        <th>Category Name</th>
                        <th>Classification</th>
                        <th style="text-align: center;">Assigned Products</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="categories-tbody">
                    <tr><td colspan="5" style="text-align: center; padding: 30px;">Loading categories...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Add / Edit Category -->
<div class="modal-backdrop" id="category-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-tag-fill" style="color: var(--primary);"></i>
                <span id="cat-modal-title">Add Category</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('category-modal')">&times;</button>
        </div>
        <form onsubmit="saveCategory(event)">
            <input type="hidden" id="cat-id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" id="cat-name" class="form-control" required placeholder="e.g. Novels & Classic Literature">
                </div>
                <div class="form-group">
                    <label class="form-label">Classification Type</label>
                    <select id="cat-type" class="form-select">
                        <option value="goods">Physical Goods / Merchandise</option>
                        <option value="service">Service (Xerox, Print, Reload)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Bootstrap Icon Class</label>
                    <input type="text" id="cat-icon" class="form-control" value="bi-tag" placeholder="e.g. bi-book, bi-phone, bi-pen">
                    <span style="font-size: 11px; color: #64748b;">Uses standard Bootstrap icons (bi-book, bi-printer, bi-phone, bi-pen...)</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('category-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
let categoriesList = [];

document.addEventListener('DOMContentLoaded', fetchCategories);

async function fetchCategories() {
    try {
        const res = await fetch('api/categories.php');
        const data = await res.json();
        if (data.status === 'success') {
            categoriesList = data.categories;
            renderCategories(categoriesList);
        }
    } catch (e) {
        showToast('Error loading categories', 'error');
    }
}

function renderCategories(items) {
    const tbody = document.getElementById('categories-tbody');
    if (!tbody) return;

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 30px;">No categories found.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(c => `
        <tr>
            <td>
                <div style="width: 38px; height: 38px; background: #e0e7ff; color: #4338ca; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="bi ${escapeHtml(c.icon || 'bi-tag')}"></i>
                </div>
            </td>
            <td><strong>${escapeHtml(c.name)}</strong></td>
            <td>
                <span class="badge ${c.type === 'goods' ? 'badge-info' : 'badge-purple'}">
                    ${c.type === 'goods' ? 'Physical Goods' : 'Service'}
                </span>
            </td>
            <td style="text-align: center;">
                <span class="badge badge-neutral">${c.product_count} items</span>
            </td>
            <td style="text-align: right;">
                <button type="button" class="btn btn-outline btn-sm" onclick="openEditCategoryModal(${c.id})">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="deleteCategory(${c.id})" style="color: var(--danger);">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function openAddCategoryModal() {
    document.getElementById('cat-modal-title').textContent = 'Add Category';
    document.getElementById('cat-id').value = '';
    document.getElementById('cat-name').value = '';
    document.getElementById('cat-type').value = 'goods';
    document.getElementById('cat-icon').value = 'bi-tag';
    openModal('category-modal');
}

function openEditCategoryModal(id) {
    const cat = categoriesList.find(c => c.id == id);
    if (!cat) return;

    document.getElementById('cat-modal-title').textContent = 'Edit Category';
    document.getElementById('cat-id').value = cat.id;
    document.getElementById('cat-name').value = cat.name;
    document.getElementById('cat-type').value = cat.type;
    document.getElementById('cat-icon').value = cat.icon || 'bi-tag';
    openModal('category-modal');
}

async function saveCategory(e) {
    e.preventDefault();
    const id = document.getElementById('cat-id').value;
    const action = id ? 'update' : 'create';

    const payload = {
        id: id,
        name: document.getElementById('cat-name').value,
        type: document.getElementById('cat-type').value,
        icon: document.getElementById('cat-icon').value
    };

    try {
        const res = await fetch(`api/categories.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.status === 'success') {
            closeModal('category-modal');
            showToast(data.message, 'success');
            fetchCategories();
        } else {
            showToast(data.message || 'Action failed', 'error');
        }
    } catch (e) {
        showToast('Server communication error', 'error');
    }
}

async function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category? Associated items will become uncategorized.')) return;

    try {
        const res = await fetch('api/categories.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            showToast(data.message, 'success');
            fetchCategories();
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
