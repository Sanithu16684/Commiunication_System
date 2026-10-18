<?php
// barcode_generator.php - Printable Barcode Sticker Generator
$pageTitle = 'Barcode Labels Generator';
$pageIcon = 'bi-upc';
require_once __DIR__ . '/includes/header.php';

$selectedBarcode = $_GET['barcode'] ?? '';
$products = $pdo->query("SELECT id, name, barcode, sku, selling_price FROM products WHERE item_type = 'physical' ORDER BY name ASC")->fetchAll();
$settings = get_settings($pdo);
$currency = $settings['currency_symbol'] ?? 'Rs.';
$storeName = $settings['store_name'] ?? 'Metro Bookshop';
?>

<!-- JsBarcode Library for crisp, professional barcode generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<div class="page-container">
    
    <div class="no-print" style="margin-bottom: 24px;">
        <h3 style="font-size: 18px; font-weight: 700; color: #0f172a;">Printable Barcode Stickers</h3>
        <p style="font-size: 13px; color: #64748b;">Generate and print standard barcode labels for books, stationery, and mobile accessories</p>
    </div>

    <!-- Generator Controls -->
    <div class="content-card no-print" style="margin-bottom: 24px;">
        <div class="card-body" style="padding: 20px 24px;">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 16px; align-items: flex-end;">
                <div>
                    <label class="form-label">Select Product to Print</label>
                    <select id="barcode-prod-select" class="form-select" onchange="onProductSelect(this)">
                        <option value="">-- Choose a Product --</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= htmlspecialchars($p['barcode']) ?>" 
                                data-name="<?= htmlspecialchars($p['name']) ?>" 
                                data-price="<?= $p['selling_price'] ?>"
                                <?= $selectedBarcode === $p['barcode'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['barcode']) ?>) - Rs. <?= number_format($p['selling_price'], 2) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Number of Stickers</label>
                    <input type="number" id="label-count" class="form-control" value="12" min="1" max="100">
                </div>
                <div>
                    <label class="form-label">Show Selling Price?</label>
                    <select id="show-price" class="form-select">
                        <option value="1">Yes, Print Price</option>
                        <option value="0">No, Barcode Only</option>
                    </select>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" onclick="generateBarcodeLabels()">
                        <i class="bi bi-magic"></i> Generate
                    </button>
                    <button type="button" class="btn btn-success" onclick="window.print()" style="margin-left: 6px;">
                        <i class="bi bi-printer"></i> Print Sheet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Printable Barcode Sheet Container -->
    <div id="barcode-sheet-container" class="barcode-sheet">
        <!-- Rendered stickers -->
    </div>

</div>

<style>
.barcode-sheet {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 12px;
}

.barcode-sticker {
    background: white;
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    padding: 10px 8px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.sticker-store {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.sticker-title {
    font-size: 11px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
    margin-bottom: 4px;
    height: 26px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.barcode-svg {
    max-width: 100%;
    height: 48px;
}

.sticker-price {
    font-size: 12px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: #000;
    margin-top: 4px;
}

@media print {
    body * {
        visibility: hidden;
    }
    .no-print, .sidebar, .topbar {
        display: none !important;
    }
    #barcode-sheet-container, #barcode-sheet-container * {
        visibility: visible;
    }
    #barcode-sheet-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 6mm;
        padding: 10mm;
    }
    .barcode-sticker {
        border: 1px solid #000;
        box-shadow: none;
        page-break-inside: avoid;
        padding: 4mm;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    generateBarcodeLabels();
});

function onProductSelect(select) {
    generateBarcodeLabels();
}

function generateBarcodeLabels() {
    const select = document.getElementById('barcode-prod-select');
    const selectedOption = select.options[select.selectedIndex];
    const container = document.getElementById('barcode-sheet-container');

    if (!selectedOption || !selectedOption.value) {
        container.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8;">Please select a product from the dropdown above to generate barcode stickers.</div>`;
        return;
    }

    const barcode = selectedOption.value;
    const name = selectedOption.getAttribute('data-name');
    const price = parseFloat(selectedOption.getAttribute('data-price') || 0);
    const count = parseInt(document.getElementById('label-count').value) || 12;
    const showPrice = document.getElementById('show-price').value === '1';

    let html = '';
    for (let i = 0; i < count; i++) {
        html += `
            <div class="barcode-sticker">
                <div class="sticker-store"><?= htmlspecialchars($storeName) ?></div>
                <div class="sticker-title">${escapeHtml(name)}</div>
                <svg class="barcode-svg" id="barcode-svg-${i}"></svg>
                ${showPrice ? `<div class="sticker-price"><?= $currency ?> ${price.toFixed(2)}</div>` : ''}
            </div>
        `;
    }

    container.innerHTML = html;

    // Render barcodes using JsBarcode
    for (let i = 0; i < count; i++) {
        try {
            JsBarcode(`#barcode-svg-${i}`, barcode, {
                format: "CODE128",
                width: 1.4,
                height: 38,
                displayValue: true,
                fontSize: 11,
                font: "monospace",
                margin: 0
            });
        } catch (e) {
            console.error('Barcode render error:', e);
        }
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m]);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
