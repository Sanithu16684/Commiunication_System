// assets/js/pos.js - Ultra-Fast POS Terminal Engine

let cart = [];
let allProducts = [];
let activeCategoryId = '';
let selectedPaymentMethod = 'cash';
let lastCompletedInvoiceId = null;
let lastCompletedInvoiceNo = null;

// Audio Synth Feedback (Web Audio API)
let audioCtx = null;
function playBeep(success = true) {
    try {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);

        if (success) {
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // High pitch pleasant beep
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.08);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.08);
        } else {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, audioCtx.currentTime); // Low warning buzzer
            gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.2);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.2);
        }
    } catch (e) {
        // Fallback or audio not allowed without user gesture
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    updateHeldBillsBadge();
    setupScannerListeners();
    setupKeyboardShortcuts();

    // Auto-focus scanner input
    const scanInput = document.getElementById('barcode-scanner-input');
    if (scanInput) scanInput.focus();
});

// Setup Barcode Scanner & Search
function setupScannerListeners() {
    const scanInput = document.getElementById('barcode-scanner-input');
    if (!scanInput) return;

    let debounceTimer;

    scanInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = scanInput.value.trim();
            if (val.length > 0) {
                processBarcodeScan(val);
            }
        }
    });

    scanInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const val = scanInput.value.trim();
            // If length is short, just filter locally
            filterCatalogLive(val);
        }, 120);
    });
}

// Process direct barcode scan
async function processBarcodeScan(barcodeOrSku) {
    const scanInput = document.getElementById('barcode-scanner-input');
    const scanBox = document.getElementById('scan-container');

    try {
        // 1. First check in loaded in-memory products for instant speed
        const localMatch = allProducts.find(p => 
            p.barcode.toLowerCase() === barcodeOrSku.toLowerCase() || 
            p.sku.toLowerCase() === barcodeOrSku.toLowerCase()
        );

        if (localMatch) {
            addToCart(localMatch);
            playBeep(true);
            flashScannerBox(scanBox, 'success');
            scanInput.value = '';
            scanInput.focus();
            return;
        }

        // 2. Query exact match API
        const res = await fetch(`api/pos_search.php?exact=1&q=${encodeURIComponent(barcodeOrSku)}`);
        const data = await res.json();

        if (data.status === 'success' && data.item) {
            addToCart(data.item);
            playBeep(true);
            flashScannerBox(scanBox, 'success');
        } else {
            playBeep(false);
            flashScannerBox(scanBox, 'error');
            showToast(`Item not found for barcode: "${barcodeOrSku}"`, 'warning');
        }
    } catch (err) {
        playBeep(false);
        showToast('Scan lookup error', 'error');
    } finally {
        scanInput.value = '';
        scanInput.focus();
    }
}

function flashScannerBox(elem, type) {
    if (!elem) return;
    const origBorder = elem.style.borderColor;
    elem.style.borderColor = (type === 'success') ? '#10b981' : '#ef4444';
    setTimeout(() => {
        elem.style.borderColor = origBorder;
    }, 400);
}

// Fetch all products for the catalog grid
async function loadProducts(catId = '') {
    const grid = document.getElementById('pos-products-grid');
    try {
        let url = 'api/pos_search.php';
        if (catId) url += `?category_id=${encodeURIComponent(catId)}`;

        const res = await fetch(url);
        const data = await res.json();

        if (data.status === 'success') {
            allProducts = data.items;
            renderProductGrid(allProducts);
        } else {
            grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #ef4444; padding: 20px;">Failed to load items.</div>`;
        }
    } catch (e) {
        grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #ef4444; padding: 20px;">Connection error loading items.</div>`;
    }
}

// Render product cards in grid
function renderProductGrid(items) {
    const grid = document.getElementById('pos-products-grid');
    if (!grid) return;

    if (!items || items.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8;">
                <i class="bi bi-search" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                <p>No products found matching criteria.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = items.map(p => {
        const isOutOfStock = p.item_type === 'physical' && parseInt(p.stock_qty) <= 0;
        const isLow = p.item_type === 'physical' && parseInt(p.stock_qty) <= parseInt(p.min_stock_alert);

        let stockText = '';
        if (p.item_type === 'physical') {
            if (isOutOfStock) {
                stockText = `<span class="prod-stock out"><i class="bi bi-x-circle"></i> Out of Stock</span>`;
            } else if (isLow) {
                stockText = `<span class="prod-stock low" title="Low Stock: ${p.stock_qty} ${escapeHtml(p.unit || '')} remaining"><i class="bi bi-exclamation-triangle-fill"></i> ${p.stock_qty} ${escapeHtml(p.unit || '')}</span>`;
            } else {
                stockText = `<span class="prod-stock" title="In Stock"><i class="bi bi-check2"></i> ${p.stock_qty} ${escapeHtml(p.unit || '')}</span>`;
            }
        } else {
            stockText = `<span class="prod-stock service-stock"><i class="bi bi-infinity"></i> Unlimited</span>`;
        }

        const badgeClass = p.item_type === 'physical' ? 'goods' : (p.item_type === 'reload' ? 'reload' : 'service');
        const badgeLabel = p.item_type === 'physical' ? 'Physical' : (p.item_type === 'reload' ? 'Reload' : 'Service');
        const displayName = p.name ? p.name : (p.sku || p.barcode || 'Item #' + p.id);
        const displayCode = p.sku || p.barcode || '';

        return `
            <div class="pos-prod-card ${isOutOfStock ? 'out-stock' : ''}" onclick="handleProductClick(${p.id})" title="${escapeHtml(displayName)}">
                <div class="prod-top">
                    <span class="prod-code" title="${escapeHtml(displayCode)}">${escapeHtml(displayCode)}</span>
                    <span class="prod-badge ${badgeClass}">${badgeLabel}</span>
                </div>
                <div class="prod-title">${escapeHtml(displayName)}</div>
                <div class="prod-bottom">
                    <div class="prod-price">Rs. ${parseFloat(p.selling_price).toFixed(2)}</div>
                    <div class="prod-stock-wrap">${stockText}</div>
                </div>
            </div>
        `;
    }).join('');
}

// Live local filter
function filterCatalogLive(term) {
    if (!term) {
        renderProductGrid(allProducts);
        return;
    }
    const lower = term.toLowerCase();
    const filtered = allProducts.filter(p => 
        p.name.toLowerCase().includes(lower) || 
        p.barcode.toLowerCase().includes(lower) || 
        p.sku.toLowerCase().includes(lower)
    );
    renderProductGrid(filtered);
}

// Filter by category pills
function filterCategory(catId, btnElem) {
    activeCategoryId = catId;
    document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
    if (btnElem) btnElem.classList.add('active');

    loadProducts(catId);
}

// Handle product click
function handleProductClick(id) {
    const item = allProducts.find(p => p.id == id);
    if (!item) return;

    if (item.item_type === 'physical' && parseInt(item.stock_qty) <= 0) {
        showToast(`"${item.name}" is Out of Stock!`, 'warning');
        playBeep(false);
        return;
    }

    addToCart(item);
    playBeep(true);
}

// Quick Add Service items by SKU
function quickAddService(sku) {
    const item = allProducts.find(p => p.sku === sku);
    if (item) {
        addToCart(item);
        playBeep(true);
        showToast(`Added ${item.name} to bill`, 'success');
    } else {
        // Fallback query
        fetch(`api/pos_search.php?exact=1&q=${encodeURIComponent(sku)}`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success' && data.item) {
                    addToCart(data.item);
                    playBeep(true);
                } else {
                    showToast('Service item not found', 'error');
                }
            });
    }
}

// Add Item to Active Cart
function addToCart(product) {
    const existingIndex = cart.findIndex(c => c.id == product.id && c.item_type === product.item_type);

    if (existingIndex > -1) {
        // Increment quantity
        const existing = cart[existingIndex];
        if (existing.item_type === 'physical' && existing.qty + 1 > parseInt(product.stock_qty)) {
            showToast(`Cannot add more. Available stock is ${product.stock_qty}`, 'warning');
            playBeep(false);
            return;
        }
        existing.qty += 1;
        existing.total = existing.qty * existing.price;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            barcode: product.barcode || '',
            sku: product.sku || '',
            item_type: product.item_type || 'physical',
            price: parseFloat(product.selling_price),
            cost_price: parseFloat(product.cost_price || 0),
            qty: 1,
            unit: product.unit || 'pcs',
            stock_qty: parseInt(product.stock_qty || 9999),
            total: parseFloat(product.selling_price)
        });
    }

    renderCart();
}

// Render Active Cart
function renderCart() {
    const tbody = document.getElementById('cart-items-tbody');
    const emptyMsg = document.getElementById('cart-empty-message');
    const badgeCount = document.getElementById('cart-item-count');

    if (!tbody) return;

    if (cart.length === 0) {
        tbody.innerHTML = '';
        if (emptyMsg) emptyMsg.style.display = 'block';
        if (badgeCount) badgeCount.textContent = '0 items';
        calculateCartTotals();
        return;
    }

    if (emptyMsg) emptyMsg.style.display = 'none';
    const totalCount = cart.reduce((acc, i) => acc + i.qty, 0);
    if (badgeCount) badgeCount.textContent = `${totalCount} item(s)`;

    tbody.innerHTML = cart.map((item, idx) => `
        <tr>
            <td>
                <div class="cart-item-name">${escapeHtml(item.name)}</div>
                <div class="cart-item-code">${item.barcode || item.sku || 'N/A'}</div>
            </td>
            <td>
                <div class="cart-qty-ctrl">
                    <button type="button" class="cart-qty-btn" onclick="updateItemQty(${idx}, -1)">-</button>
                    <input type="number" class="cart-qty-input" value="${item.qty}" min="1" onchange="setItemQtyDirect(${idx}, this.value)">
                    <button type="button" class="cart-qty-btn" onclick="updateItemQty(${idx}, 1)">+</button>
                </div>
            </td>
            <td style="text-align: right;" class="cart-item-price">
                Rs. ${item.price.toFixed(2)}
            </td>
            <td class="cart-item-total">
                Rs. ${(item.price * item.qty).toFixed(2)}
            </td>
            <td style="text-align: center;">
                <button type="button" class="cart-item-remove" onclick="removeCartItem(${idx})" title="Remove item">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
            </td>
        </tr>
    `).join('');

    calculateCartTotals();
}

// Update quantity with +/-
function updateItemQty(idx, delta) {
    const item = cart[idx];
    if (!item) return;

    const newQty = item.qty + delta;
    if (newQty <= 0) {
        removeCartItem(idx);
        return;
    }

    if (item.item_type === 'physical' && newQty > item.stock_qty) {
        showToast(`Stock limit reached! Max available: ${item.stock_qty}`, 'warning');
        playBeep(false);
        return;
    }

    item.qty = newQty;
    item.total = item.qty * item.price;
    renderCart();
}

// Set quantity directly from input
function setItemQtyDirect(idx, val) {
    const item = cart[idx];
    if (!item) return;

    let qty = parseInt(val) || 1;
    if (qty <= 0) qty = 1;

    if (item.item_type === 'physical' && qty > item.stock_qty) {
        showToast(`Stock limit reached! Max available: ${item.stock_qty}`, 'warning');
        qty = item.stock_qty;
    }

    item.qty = qty;
    item.total = item.qty * item.price;
    renderCart();
}

// Remove item from cart
function removeCartItem(idx) {
    cart.splice(idx, 1);
    renderCart();
}

// Clear cart
function clearCart(confirmPrompt = true) {
    if (cart.length === 0) return;
    if (confirmPrompt && !confirm('Are you sure you want to clear the active cart?')) return;

    cart = [];
    document.getElementById('customer-name').value = '';
    document.getElementById('customer-phone').value = '';
    document.getElementById('discount-input').value = '0';
    renderCart();
}

// Calculate Financial Totals
function calculateCartTotals() {
    const subtotal = cart.reduce((acc, i) => acc + (i.price * i.qty), 0);
    const discType = document.getElementById('discount-type')?.value || 'fixed';
    let discInput = parseFloat(document.getElementById('discount-input')?.value || 0);
    if (isNaN(discInput) || discInput < 0) discInput = 0;

    let discountAmount = 0;
    if (discType === 'percentage') {
        discountAmount = (subtotal * discInput) / 100;
    } else {
        discountAmount = discInput;
    }
    if (discountAmount > subtotal) discountAmount = subtotal;

    const netTotal = Math.max(0, subtotal - discountAmount);

    document.getElementById('cart-subtotal').textContent = `Rs. ${subtotal.toFixed(2)}`;
    document.getElementById('cart-grand-total').textContent = `Rs. ${netTotal.toFixed(2)}`;

    return {
        subtotal: subtotal,
        discountType: discType,
        discountAmount: discountAmount,
        netTotal: netTotal
    };
}

// Quick Mobile Reload Modal Handling
function openReloadModal() {
    openModal('reload-modal');
    setTimeout(() => {
        document.getElementById('reload-phone').focus();
    }, 150);
}

function addReloadToCart() {
    const op = document.getElementById('reload-operator').value;
    const phone = document.getElementById('reload-phone').value.trim();
    const amt = parseFloat(document.getElementById('reload-amount').value || 0);

    if (amt <= 0) {
        showToast('Please enter a valid reload amount', 'warning');
        return;
    }

    const reloadName = `${op} Reload (${phone || 'Walk-in'})`;
    cart.push({
        id: null,
        name: reloadName,
        barcode: 'RELOAD-' + op.toUpperCase(),
        sku: 'RLD-' + op.toUpperCase(),
        item_type: 'reload',
        price: amt,
        cost_price: amt * 0.96, // standard 4% commission estimate
        qty: 1,
        unit: 'Rs',
        stock_qty: 9999,
        total: amt
    });

    closeModal('reload-modal');
    document.getElementById('reload-phone').value = '';
    document.getElementById('reload-amount').value = '';

    renderCart();
    playBeep(true);
    showToast(`Added ${reloadName} - Rs. ${amt.toFixed(2)} to cart`, 'success');
}

// Hold and Recall Bills (Multi-Queue)
function holdCurrentCart() {
    if (cart.length === 0) {
        showToast('Cart is empty. Nothing to hold.', 'warning');
        return;
    }

    const customer = document.getElementById('customer-name').value.trim() || 'Walk-in Customer';
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');

    const heldItem = {
        id: 'HOLD-' + Date.now(),
        date: new Date().toLocaleTimeString(),
        customer: customer,
        phone: document.getElementById('customer-phone').value.trim(),
        cart: [...cart],
        totals: calculateCartTotals()
    };

    heldBills.push(heldItem);
    localStorage.setItem('pos_held_bills', JSON.stringify(heldBills));

    showToast(`Bill held for "${customer}"!`, 'info');
    clearCart(false);
    updateHeldBillsBadge();
}

function updateHeldBillsBadge() {
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    const badge = document.getElementById('held-count');
    if (badge) badge.textContent = heldBills.length;
    const hotkeyBadge = document.getElementById('hotkey-held-count');
    if (hotkeyBadge) hotkeyBadge.textContent = heldBills.length;
}

function showHeldBillsModal() {
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    const container = document.getElementById('held-bills-container');
    if (!container) return;

    if (heldBills.length === 0) {
        container.innerHTML = `<div style="text-align: center; padding: 30px; color: #94a3b8;">No bills currently on hold.</div>`;
    } else {
        container.innerHTML = heldBills.map((b, idx) => `
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h5 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">${escapeHtml(b.customer)} (${b.date})</h5>
                    <p style="font-size: 12px; color: #64748b;">${b.cart.length} items &bull; <strong>Rs. ${b.totals.netTotal.toFixed(2)}</strong></p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="recallHeldBill(${idx})">
                        <i class="bi bi-arrow-clockwise"></i> Recall
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="discardHeldBill(${idx})" style="color: #ef4444;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    openModal('held-bills-modal');
}

function recallHeldBill(idx) {
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    if (!heldBills[idx]) return;

    if (cart.length > 0) {
        if (!confirm('Recall will overwrite current active cart. Continue?')) return;
    }

    const bill = heldBills[idx];
    cart = [...bill.cart];
    document.getElementById('customer-name').value = bill.customer === 'Walk-in Customer' ? '' : bill.customer;
    document.getElementById('customer-phone').value = bill.phone || '';

    heldBills.splice(idx, 1);
    localStorage.setItem('pos_held_bills', JSON.stringify(heldBills));

    closeModal('held-bills-modal');
    updateHeldBillsBadge();
    renderCart();
    showToast('Bill recalled successfully!', 'success');
}

function discardHeldBill(idx) {
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    heldBills.splice(idx, 1);
    localStorage.setItem('pos_held_bills', JSON.stringify(heldBills));
    updateHeldBillsBadge();
    showHeldBillsModal();
}

// Payment & Checkout Flow
function openPaymentModal() {
    if (cart.length === 0) {
        showToast('Cart is empty. Please add items before checkout.', 'warning');
        playBeep(false);
        return;
    }

    const totals = calculateCartTotals();
    document.getElementById('modal-pay-total').textContent = `Rs. ${totals.netTotal.toFixed(2)}`;
    
    // Default tender to exact amount
    const tenderInput = document.getElementById('tendered-amount');
    tenderInput.value = totals.netTotal.toFixed(2);
    
    updateChangeCalculation();
    openModal('payment-modal');

    setTimeout(() => {
        tenderInput.select();
    }, 150);
}

function selectPayMethod(method, elem) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.pay-method-card').forEach(c => c.classList.remove('selected'));
    if (elem) elem.classList.add('selected');

    const cashSec = document.getElementById('cash-tender-section');
    if (method === 'cash') {
        cashSec.style.display = 'block';
    } else {
        cashSec.style.display = 'none';
    }
}

function setTenderExact() {
    const totals = calculateCartTotals();
    document.getElementById('tendered-amount').value = totals.netTotal.toFixed(2);
    updateChangeCalculation();
}

function addTender(amt) {
    const inp = document.getElementById('tendered-amount');
    const curr = parseFloat(inp.value) || 0;
    inp.value = (curr + amt).toFixed(2);
    updateChangeCalculation();
}

function setTenderValue(val) {
    document.getElementById('tendered-amount').value = val.toFixed(2);
    updateChangeCalculation();
}

function updateChangeCalculation() {
    const totals = calculateCartTotals();
    const tendered = parseFloat(document.getElementById('tendered-amount').value) || 0;
    const change = tendered - totals.netTotal;

    const changeBox = document.getElementById('change-box');
    const changeDueEl = document.getElementById('modal-change-due');

    if (change < 0) {
        changeBox.classList.add('negative');
        changeDueEl.textContent = `Short: Rs. ${Math.abs(change).toFixed(2)}`;
    } else {
        changeBox.classList.remove('negative');
        changeDueEl.textContent = `Rs. ${change.toFixed(2)}`;
    }
}

// Execute Final Checkout
async function executeCheckout() {
    const totals = calculateCartTotals();
    const tendered = selectedPaymentMethod === 'cash' ? (parseFloat(document.getElementById('tendered-amount').value) || 0) : totals.netTotal;

    if (selectedPaymentMethod === 'cash' && tendered < totals.netTotal) {
        showToast('Amount tendered is less than the net total!', 'warning');
        playBeep(false);
        return;
    }

    const change = Math.max(0, tendered - totals.netTotal);
    const btn = document.getElementById('btn-confirm-checkout');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Processing...`;

    const payload = {
        customer_name: document.getElementById('customer-name').value.trim(),
        customer_phone: document.getElementById('customer-phone').value.trim(),
        items: cart,
        subtotal: totals.subtotal,
        discount_type: totals.discountType,
        discount_amount: totals.discountAmount,
        tax_amount: 0.00,
        total_amount: totals.netTotal,
        payment_method: selectedPaymentMethod,
        amount_paid: tendered,
        change_amount: change,
        notes: document.getElementById('bill-notes').value.trim()
    };

    try {
        const res = await fetch('api/checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.status === 'success') {
            lastCompletedInvoiceId = data.invoice_id;
            lastCompletedInvoiceNo = data.invoice_no;

            closeModal('payment-modal');
            playBeep(true);

            // 1. Populate Bill Preview Modal
            document.getElementById('success-inv-no').textContent = '#' + data.invoice_no;
            document.getElementById('success-paid').textContent = `Rs. ${tendered.toFixed(2)}`;
            document.getElementById('success-change').textContent = `Rs. ${change.toFixed(2)}`;

            // Reset preview tab state to thermal
            currentPreviewFormat = 'thermal';
            updatePreviewTabButtons();

            // Load live bill preview inside popup iframe
            const previewFrame = document.getElementById('receipt-preview-frame');
            if (previewFrame) {
                previewFrame.src = `print_receipt.php?id=${data.invoice_id}&format=thermal&embed=1`;
            }

            // Open the Bill Preview Popup immediately
            openModal('checkout-success-modal');

            // 2. Dispatch silent print without redirecting
            printThermalReceipt();

            // 3. Clear current cart & reset POS terminal for next customer
            cart = [];
            document.getElementById('customer-name').value = '';
            document.getElementById('customer-phone').value = '';
            document.getElementById('discount-input').value = '0';
            document.getElementById('bill-notes').value = '';
            renderCart();

            // 4. Refresh product stock numbers in background
            loadProducts(activeCategoryId);
        } else {
            showToast(data.message || 'Checkout failed', 'error');
            playBeep(false);
        }
    } catch (e) {
        showToast('Server communication error during checkout', 'error');
        playBeep(false);
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="bi bi-printer-fill"></i> Pay & Print (Enter)`;
    }
}

// Bill Preview & Printing Actions (Without Redirecting)
let currentPreviewFormat = 'thermal';

function switchPreviewFormat(format) {
    if (!lastCompletedInvoiceId) return;
    currentPreviewFormat = format;
    updatePreviewTabButtons();
    const previewFrame = document.getElementById('receipt-preview-frame');
    if (previewFrame) {
        previewFrame.src = `print_receipt.php?id=${lastCompletedInvoiceId}&format=${format}&embed=1`;
    }
}

function updatePreviewTabButtons() {
    const btnThermal = document.getElementById('btn-preview-thermal');
    const btnA4 = document.getElementById('btn-preview-a4');
    if (btnThermal && btnA4) {
        if (currentPreviewFormat === 'thermal') {
            btnThermal.classList.add('active');
            btnA4.classList.remove('active');
        } else {
            btnA4.classList.add('active');
            btnThermal.classList.remove('active');
        }
    }
}

function printThermalReceipt() {
    if (!lastCompletedInvoiceId) return;
    const printUrl = `print_receipt.php?id=${lastCompletedInvoiceId}&format=thermal&autoprint=1`;
    sendToPrintIframe(printUrl);
}

function printA4Invoice() {
    if (!lastCompletedInvoiceId) return;
    const printUrl = `print_receipt.php?id=${lastCompletedInvoiceId}&format=a4&autoprint=1`;
    sendToPrintIframe(printUrl);
}

function viewA4Invoice() {
    printA4Invoice();
}

function sendToPrintIframe(url) {
    let iframe = document.getElementById('receipt-print-iframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'receipt-print-iframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.style.opacity = '0';
        iframe.style.pointerEvents = 'none';
        document.body.appendChild(iframe);
    }
    iframe.src = url;
}

function startNewSale() {
    closeModal('checkout-success-modal');
    const previewFrame = document.getElementById('receipt-preview-frame');
    if (previewFrame) {
        previewFrame.src = 'about:blank';
    }
    focusScannerInput();
}

// Cashier Focus & Modal Helpers
function focusScannerInput() {
    const scan = document.getElementById('barcode-scanner-input');
    if (scan) {
        scan.focus();
        scan.select();
    }
}

function focusCustomerInput() {
    const nameInput = document.getElementById('customer-name');
    if (nameInput) {
        nameInput.focus();
        nameInput.select();
    }
}

function focusDiscountInput() {
    const discInput = document.getElementById('discount-input');
    if (discInput) {
        discInput.focus();
        discInput.select();
    }
}

function openServicesHotkeyModal() {
    openModal('services-hotkey-modal');
}

function quickAddServiceKey(sku) {
    quickAddService(sku);
    closeModal('services-hotkey-modal');
    focusScannerInput();
}

function openReloadFromServices() {
    closeModal('services-hotkey-modal');
    openReloadModal();
}

function openShortcutsHelpModal() {
    openModal('shortcuts-help-modal');
}

// Comprehensive Keyboard Shortcuts Listener for Maximum Cashier Speed
function setupKeyboardShortcuts() {
    window.addEventListener('keydown', (e) => {
        const isServicesModalOpen = document.getElementById('services-hotkey-modal')?.classList.contains('show');
        const isPaymentModalOpen = document.getElementById('payment-modal')?.classList.contains('show');
        const isSuccessModalOpen = document.getElementById('checkout-success-modal')?.classList.contains('show');
        const isHeldModalOpen = document.getElementById('held-bills-modal')?.classList.contains('show');
        const isReloadModalOpen = document.getElementById('reload-modal')?.classList.contains('show');
        const isShortcutsModalOpen = document.getElementById('shortcuts-help-modal')?.classList.contains('show');

        const activeTag = document.activeElement ? document.activeElement.tagName : '';
        const isInputActive = ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeTag);

        // 1. Navigation Shortcuts with Alt + [1-6]
        if (e.altKey && !e.ctrlKey) {
            if (e.key === '1') { e.preventDefault(); window.location.href = 'pos.php'; return; }
            if (e.key === '2') { e.preventDefault(); window.location.href = 'invoices.php'; return; }
            if (e.key === '3') { e.preventDefault(); window.location.href = 'inventory.php'; return; }
            if (e.key === '4') { e.preventDefault(); window.location.href = 'audits.php'; return; }
            if (e.key === '5') { e.preventDefault(); window.location.href = 'categories.php'; return; }
            if (e.key === '6') { e.preventDefault(); window.location.href = 'suppliers.php'; return; }
        }

        // 2. Escape: Close open modals and return to scanner
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.show').forEach(m => m.classList.remove('show'));
            focusScannerInput();
            return;
        }

        // 3. Keys when Quick Services Modal (F3) is Open
        if (isServicesModalOpen) {
            if (e.key === '1') { e.preventDefault(); quickAddServiceKey('SRV-XER-BW'); return; }
            if (e.key === '2') { e.preventDefault(); quickAddServiceKey('SRV-XER-DS'); return; }
            if (e.key === '3') { e.preventDefault(); quickAddServiceKey('SRV-PRT-CLR'); return; }
            if (e.key === '4') { e.preventDefault(); quickAddServiceKey('SRV-LAM-A4'); return; }
            if (e.key === '5') { e.preventDefault(); openReloadFromServices(); return; }
        }

        // 4. Keys when Success Modal is Open
        if (isSuccessModalOpen) {
            if (e.key === 'Enter') {
                e.preventDefault();
                startNewSale();
                return;
            }
            if (e.key === 'p' || e.key === 'P') {
                e.preventDefault();
                printThermalReceipt();
                return;
            }
            if (e.key === 'a' || e.key === 'A') {
                e.preventDefault();
                viewA4Invoice();
                return;
            }
        }

        // 5. Keys when Payment Modal (F9) is Open
        if (isPaymentModalOpen) {
            // Switch payment methods using 1, 2, 3, 4 when not typing notes or when alt is held
            if (e.altKey || document.activeElement.id !== 'tendered-amount' && document.activeElement.id !== 'bill-notes') {
                if (e.key === '1') { e.preventDefault(); selectPayMethod('cash', document.getElementById('pay-card-cash')); return; }
                if (e.key === '2') { e.preventDefault(); selectPayMethod('card', document.getElementById('pay-card-card')); return; }
                if (e.key === '3') { e.preventDefault(); selectPayMethod('qr', document.getElementById('pay-card-qr')); return; }
                if (e.key === '4') { e.preventDefault(); selectPayMethod('transfer', document.getElementById('pay-card-transfer')); return; }
            }

            // Quick exact tendered amount with 'x'
            if (e.key === 'x' && document.activeElement.id !== 'bill-notes') {
                e.preventDefault();
                setTenderExact();
                return;
            }

            // Enter key inside payment modal executes transaction
            if (e.key === 'Enter') {
                e.preventDefault();
                executeCheckout();
                return;
            }
        }

        // 6. Global Function Action Keys (F1 - F12)
        // F1: Focus Barcode Scanner / Search
        if (e.key === 'F1') {
            e.preventDefault();
            focusScannerInput();
        }
        // F2: Quick Mobile Reload
        else if (e.key === 'F2') {
            e.preventDefault();
            openReloadModal();
        }
        // F3: Quick Services Menu (Photocopy, Xerox, Print)
        else if (e.key === 'F3') {
            e.preventDefault();
            openServicesHotkeyModal();
        }
        // F4: Hold Active Bill in Queue
        else if (e.key === 'F4') {
            e.preventDefault();
            holdCurrentCart();
        }
        // F6: Recall Held Bills Modal
        else if (e.key === 'F6') {
            e.preventDefault();
            showHeldBillsModal();
        }
        // F7: Clear Active Cart
        else if (e.key === 'F7') {
            e.preventDefault();
            clearCart(true);
        }
        // F8: Focus Customer Name & Phone
        else if (e.key === 'F8') {
            e.preventDefault();
            focusCustomerInput();
        }
        // F9 or 'End': Open Payment / Tender Modal
        else if (e.key === 'F9' || e.key === 'End') {
            e.preventDefault();
            openPaymentModal();
        }
        // F10: Focus Discount Field
        else if (e.key === 'F10') {
            e.preventDefault();
            focusDiscountInput();
        }
        // F12: Open Shortcuts Cheat Sheet Help Modal
        else if (e.key === 'F12') {
            e.preventDefault();
            openShortcutsHelpModal();
        }

        // 7. Cart Fast Line Items Controls (Active when NOT typing in an input field)
        if (!isInputActive && cart.length > 0 && !isPaymentModalOpen && !isServicesModalOpen) {
            const lastIdx = cart.length - 1;

            // '+' or Up Arrow: Increase last item quantity
            if (e.key === '+' || e.key === 'ArrowUp') {
                e.preventDefault();
                updateItemQty(lastIdx, 1);
                playBeep(true);
            }
            // '-' or Down Arrow: Decrease last item quantity
            else if (e.key === '-' || e.key === 'ArrowDown') {
                e.preventDefault();
                updateItemQty(lastIdx, -1);
                playBeep(true);
            }
            // 'Delete': Remove last item from cart
            else if (e.key === 'Delete') {
                e.preventDefault();
                removeCartItem(lastIdx);
                showToast('Removed item from bill', 'info');
            }
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}
