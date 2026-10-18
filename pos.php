<?php
// pos.php - High-Speed POS Terminal for Communication & Bookshop
$pageTitle = 'POS Billing Terminal';
$pageIcon = 'bi-upc-scan';
require_once __DIR__ . '/includes/header.php';

// Fetch categories for quick pills
$cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="pos-outer-wrapper">
<div class="pos-container">
    <!-- Left Pane: Product Catalog & Fast Barcode Scanner -->
    <div class="pos-catalog-pane">
        
        <!-- Barcode & Search Box -->
        <div class="pos-scan-box" id="scan-container">
            <div class="scan-icon-pulse">
                <i class="bi bi-upc-scan"></i>
            </div>
            <div class="scan-input-wrapper">
                <label for="barcode-scanner-input">
                    <span>Barcode Scanner & Search</span>
                    <span style="font-weight: 500; text-transform: none; color: #64748b;">(Auto-detects Barcodes & Item names)</span>
                </label>
                <input type="text" id="barcode-scanner-input" class="scan-input" placeholder="Scan Barcode with Reader or Type Item Name..." autofocus autocomplete="off">
            </div>
            <div class="scan-hint">
                <kbd style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 11px;">F1</kbd> Scanner Focus
            </div>
        </div>

        <!-- Quick Communication Services Bar -->
        <div class="pos-services-bar">
            <button type="button" class="quick-srv-btn" onclick="quickAddService('SRV-XER-BW')">
                <i class="bi bi-printer-fill"></i> B&W Photocopy (Rs. 10)
            </button>
            <button type="button" class="quick-srv-btn" onclick="quickAddService('SRV-XER-DS')">
                <i class="bi bi-printer"></i> Double Xerox (Rs. 15)
            </button>
            <button type="button" class="quick-srv-btn" onclick="quickAddService('SRV-PRT-CLR')">
                <i class="bi bi-file-earmark-richtext-fill"></i> Color Print (Rs. 40)
            </button>
            <button type="button" class="quick-srv-btn" onclick="quickAddService('SRV-LAM-A4')">
                <i class="bi bi-shield-shaded"></i> Lamination (Rs. 100)
            </button>
            <button type="button" class="quick-srv-btn" onclick="openReloadModal()">
                <i class="bi bi-phone-fill"></i> Mobile Reload / Top-up
            </button>
        </div>

        <!-- Category Filter Pills -->
        <div class="pos-categories-pills">
            <button type="button" class="cat-pill active" onclick="filterCategory('', this)">All Categories</button>
            <?php foreach ($cats as $cat): ?>
            <button type="button" class="cat-pill" onclick="filterCategory(<?= $cat['id'] ?>, this)">
                <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i> <?= htmlspecialchars($cat['name']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Product Grid Catalog -->
        <div class="pos-products-grid" id="pos-products-grid">
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8;">
                <div class="spinner-border" role="status"></div>
                <p style="margin-top: 8px;">Loading product catalog...</p>
            </div>
        </div>
    </div>

    <!-- Right Pane: Active Bill Cart & Checkout -->
    <div class="pos-cart-pane">
        <!-- Cart Top Header -->
        <div class="cart-header">
            <div class="cart-header-title">
                <i class="bi bi-cart3" style="color: var(--primary);"></i>
                <span>Active Bill Cart</span>
                <span class="badge badge-purple" id="cart-item-count">0 items</span>
            </div>
            <div class="cart-header-actions">
                <button type="button" class="btn btn-outline btn-sm" onclick="holdCurrentCart()" title="Hold this bill (F4)">
                    <i class="bi bi-pause-circle"></i> Hold
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="showHeldBillsModal()" id="held-bills-badge" title="Recall held bills">
                    <i class="bi bi-clock-history"></i> <span id="held-count">0</span>
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="clearCart(true)" title="Clear cart" style="color: var(--danger);">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        <!-- Customer Info Bar -->
        <div class="cart-customer-box">
            <input type="text" id="customer-name" class="form-control" placeholder="Customer Name (Walk-in Customer)" style="flex: 1.2;">
            <input type="text" id="customer-phone" class="form-control" placeholder="Phone No (Optional)" style="flex: 1;">
        </div>

        <!-- Cart Items List Table -->
        <div class="cart-items-wrapper">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Item Details</th>
                        <th style="width: 90px; text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Total</th>
                        <th style="width: 36px;"></th>
                    </tr>
                </thead>
                <tbody id="cart-items-tbody">
                    <!-- Populated dynamically via pos.js -->
                </tbody>
            </table>
            <div id="cart-empty-message" class="cart-empty-state">
                <i class="bi bi-cart-x"></i>
                <h4 style="font-size: 15px; font-weight: 700; color: #475569; margin-bottom: 4px;">Cart is Empty</h4>
                <p style="font-size: 12.5px;">Scan a barcode, select an item from the left, or use quick service buttons.</p>
            </div>
        </div>

        <!-- Cart Financial Totals & Checkout -->
        <div class="cart-footer">
            <div class="cart-summary-line">
                <span>Subtotal:</span>
                <span id="cart-subtotal" style="font-family: var(--font-mono); font-weight: 700; color: #334155;">Rs. 0.00</span>
            </div>

            <div class="cart-summary-line" style="gap: 8px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span>Discount:</span>
                    <select id="discount-type" onchange="calculateCartTotals()" style="padding: 2px 6px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px;">
                        <option value="fixed">Fixed (Rs.)</option>
                        <option value="percentage">% Percent</option>
                    </select>
                </div>
                <input type="number" id="discount-input" min="0" value="0" oninput="calculateCartTotals()" style="width: 80px; text-align: right; padding: 2px 6px; border: 1px solid #cbd5e1; border-radius: 4px; font-family: var(--font-mono); font-weight: 600;">
            </div>

            <div class="cart-summary-line grand-total">
                <span>NET TOTAL:</span>
                <span class="val" id="cart-grand-total">Rs. 0.00</span>
            </div>

            <div class="cart-action-buttons">
                <button type="button" class="btn btn-hold btn-lg" onclick="holdCurrentCart()">
                    <i class="bi bi-pause-fill"></i> Hold Bill
                </button>
                <button type="button" class="btn btn-checkout btn-lg" id="btn-pay" onclick="openPaymentModal()">
                    <i class="bi bi-cash-coin"></i> Charge / Pay (F9)
                </button>
            </div> <!-- End .cart-action-buttons -->
        </div> <!-- End .cart-footer -->
    </div> <!-- End .pos-cart-pane -->
</div> <!-- End .pos-container -->

<!-- Persistent Cashier Hotkeys Bar -->
<div class="pos-hotkeys-bar">
    <div class="hotkey-chips-wrapper">
        <span style="font-weight: 700; color: #94a3b8; margin-right: 4px; display: inline-flex; align-items: center; gap: 4px;">
            <i class="bi bi-lightning-charge-fill" style="color: #f59e0b;"></i> QUICK KEYS:
        </span>
        <div class="hotkey-chip" onclick="focusScannerInput()" title="Press F1: Focus Barcode Scanner / Search">
            <kbd>F1</kbd> Scan
        </div>
        <div class="hotkey-chip" onclick="openReloadModal()" title="Press F2: Mobile Reload / Top-up">
            <kbd>F2</kbd> Reload
        </div>
        <div class="hotkey-chip" onclick="openServicesHotkeyModal()" title="Press F3: Xerox & Print Services Menu">
            <kbd>F3</kbd> Services
        </div>
        <div class="hotkey-chip" onclick="holdCurrentCart()" title="Press F4: Hold current bill in queue">
            <kbd>F4</kbd> Hold
        </div>
        <div class="hotkey-chip" onclick="showHeldBillsModal()" title="Press F6: Recall held bills">
            <kbd>F6</kbd> Recall (<span id="hotkey-held-count">0</span>)
        </div>
        <div class="hotkey-chip" onclick="clearCart(true)" title="Press F7: Clear active cart">
            <kbd>F7</kbd> Clear
        </div>
        <div class="hotkey-chip" onclick="focusCustomerInput()" title="Press F8: Enter customer details">
            <kbd>F8</kbd> Customer
        </div>
        <div class="hotkey-chip" onclick="focusDiscountInput()" title="Press F10: Apply discount">
            <kbd>F10</kbd> Discount
        </div>
        <div class="hotkey-chip highlight" onclick="openPaymentModal()" title="Press F9 or End: Charge / Pay & Print">
            <kbd>F9</kbd> Pay & Tender
        </div>
    </div>
    <div>
        <div class="hotkey-chip" onclick="openShortcutsHelpModal()" title="Press F12: Open all keyboard shortcuts help">
            <kbd>F12</kbd> Hotkeys Cheat Sheet
        </div>
    </div>
</div>
</div> <!-- End .pos-outer-wrapper -->

<!-- Modal: Fast Checkout / Payment -->
<div class="modal-backdrop" id="payment-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-credit-card-2-front" style="color: var(--primary);"></i>
                <span>Complete Payment</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('payment-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 14px; text-align: center; margin-bottom: 18px;">
                <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Amount to Collect</span>
                <div id="modal-pay-total" style="font-size: 32px; font-weight: 800; font-family: var(--font-mono); color: var(--primary); line-height: 1.2;">
                    Rs. 0.00
                </div>
            </div>

            <!-- Payment Methods -->
            <label class="form-label">Payment Method <span style="font-size: 11px; color: #64748b; font-weight: normal;">(Press 1, 2, 3, or 4 to switch)</span></label>
            <div class="payment-methods-grid">
                <div class="pay-method-card selected" id="pay-card-cash" onclick="selectPayMethod('cash', this)">
                    <i class="bi bi-cash-stack"></i>
                    <span>Cash <kbd style="font-size: 10px; background: rgba(0,0,0,0.1); padding: 1px 4px; border-radius: 3px;">1</kbd></span>
                </div>
                <div class="pay-method-card" id="pay-card-card" onclick="selectPayMethod('card', this)">
                    <i class="bi bi-credit-card"></i>
                    <span>Card <kbd style="font-size: 10px; background: rgba(0,0,0,0.1); padding: 1px 4px; border-radius: 3px;">2</kbd></span>
                </div>
                <div class="pay-method-card" id="pay-card-qr" onclick="selectPayMethod('qr', this)">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>LankaQR <kbd style="font-size: 10px; background: rgba(0,0,0,0.1); padding: 1px 4px; border-radius: 3px;">3</kbd></span>
                </div>
                <div class="pay-method-card" id="pay-card-transfer" onclick="selectPayMethod('transfer', this)">
                    <i class="bi bi-bank"></i>
                    <span>Transfer <kbd style="font-size: 10px; background: rgba(0,0,0,0.1); padding: 1px 4px; border-radius: 3px;">4</kbd></span>
                </div>
            </div>

            <!-- Tendered Amount (For Cash) -->
            <div id="cash-tender-section">
                <div class="form-group">
                    <label class="form-label">Amount Tendered by Customer (Rs.)</label>
                    <input type="number" id="tendered-amount" class="form-control" style="font-size: 20px; font-family: var(--font-mono); font-weight: 700; height: 48px;" oninput="updateChangeCalculation()">
                </div>

                <div class="quick-cash-chips">
                    <button type="button" class="cash-chip" onclick="setTenderExact()">Exact Total</button>
                    <button type="button" class="cash-chip" onclick="addTender(100)">+100</button>
                    <button type="button" class="cash-chip" onclick="addTender(500)">+500</button>
                    <button type="button" class="cash-chip" onclick="addTender(1000)">+1000</button>
                    <button type="button" class="cash-chip" onclick="addTender(5000)">+5000</button>
                    <button type="button" class="cash-chip" onclick="setTenderValue(500)">Rs. 500</button>
                    <button type="button" class="cash-chip" onclick="setTenderValue(1000)">Rs. 1,000</button>
                    <button type="button" class="cash-chip" onclick="setTenderValue(5000)">Rs. 5,000</button>
                </div>

                <!-- Change Due -->
                <div class="change-due-box" id="change-box">
                    <span class="change-label">Balance / Change Due:</span>
                    <span class="change-amount" id="modal-change-due">Rs. 0.00</span>
                </div>
            </div>

            <div class="form-group" style="margin-top: 14px;">
                <label class="form-label">Bill Notes / Reference (Optional)</label>
                <input type="text" id="bill-notes" class="form-control" placeholder="e.g. Reload Ref #, School name, Discount reason">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('payment-modal')">Cancel</button>
            <button type="button" class="btn btn-success btn-lg" id="btn-confirm-checkout" onclick="executeCheckout()" style="min-width: 180px;">
                <i class="bi bi-printer-fill"></i> Pay & Print (Enter)
            </button>
        </div>
    </div>
</div>

<!-- Modal: Quick Mobile Reload & Custom Service -->
<div class="modal-backdrop" id="reload-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-phone" style="color: var(--primary);"></i>
                <span>Mobile Reload / Bill Payment</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('reload-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Mobile Network Operator</label>
                <select id="reload-operator" class="form-select">
                    <option value="Dialog">Dialog Axiata</option>
                    <option value="Mobitel">SLT-Mobitel</option>
                    <option value="Airtel">Airtel Lanka</option>
                    <option value="Hutch">Hutchison</option>
                    <option value="CEB">CEB / Water Board Bill</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Phone / Account Number</label>
                <input type="text" id="reload-phone" class="form-control" placeholder="07XXXXXXXX or Account No" autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Reload / Payment Amount (Rs.)</label>
                <input type="number" id="reload-amount" class="form-control" placeholder="100, 250, 500, 1000..." min="10" step="10">
            </div>
            <div class="quick-cash-chips" style="margin-top: 8px;">
                <button type="button" class="cash-chip" onclick="document.getElementById('reload-amount').value = 100">Rs. 100</button>
                <button type="button" class="cash-chip" onclick="document.getElementById('reload-amount').value = 200">Rs. 200</button>
                <button type="button" class="cash-chip" onclick="document.getElementById('reload-amount').value = 350">Rs. 350</button>
                <button type="button" class="cash-chip" onclick="document.getElementById('reload-amount').value = 500">Rs. 500</button>
                <button type="button" class="cash-chip" onclick="document.getElementById('reload-amount').value = 1000">Rs. 1,000</button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('reload-modal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="addReloadToCart()">
                <i class="bi bi-plus-circle"></i> Add to Bill Cart
            </button>
        </div>
    </div>
</div>

<!-- Modal: Held Bills List -->
<div class="modal-backdrop" id="held-bills-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-pause-circle" style="color: var(--primary);"></i>
                <span>Held Bill Queue</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('held-bills-modal')">&times;</button>
        </div>
        <div class="modal-body" id="held-bills-container">
            <!-- Populated via pos.js -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('held-bills-modal')">Close</button>
        </div>
    </div>
</div>

<!-- Modal: Checkout Success & Bill Preview / Print Popup -->
<div class="modal-backdrop" id="checkout-success-modal">
    <div class="modal-dialog" style="max-width: 640px; width: 95%;">
        <div class="modal-header" style="padding: 14px 20px; border-bottom: 1px solid var(--border);">
            <div class="modal-title" style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-receipt-cutoff" style="color: var(--primary); font-size: 22px;"></i>
                <span style="font-weight: 700; font-size: 16px;">Bill Receipt Preview</span>
                <span class="badge" id="success-inv-no" style="background: #e0e7ff; color: #4338ca; font-weight: 700; font-size: 12.5px; font-family: var(--font-mono); padding: 4px 10px; border-radius: 6px;">#INV-XXXX</span>
            </div>
            <button type="button" class="modal-close" onclick="startNewSale()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 16px 20px;">
            <!-- Top Summary Strip -->
            <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 16px; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 6px; color: #16a34a; font-weight: 700; font-size: 13px;">
                    <i class="bi bi-check-circle-fill" style="font-size: 16px;"></i>
                    <span>Payment Recorded & Print Sent</span>
                </div>
                <div style="display: flex; align-items: center; gap: 16px; font-size: 13px;">
                    <div><span style="color: #64748b;">Paid:</span> <strong id="success-paid" style="font-family: var(--font-mono);">Rs. 0.00</strong></div>
                    <div><span style="color: #64748b;">Change:</span> <strong id="success-change" style="color: #059669; font-size: 15px; font-family: var(--font-mono);">Rs. 0.00</strong></div>
                </div>
            </div>

            <!-- Preview Format Selector Tabs -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em;">
                    <i class="bi bi-eye"></i> Bill Live Preview
                </div>
                <div style="display: inline-flex; background: #e2e8f0; padding: 3px; border-radius: 8px; gap: 4px;">
                    <button type="button" id="btn-preview-thermal" class="preview-tab-btn active" onclick="switchPreviewFormat('thermal')">
                        <i class="bi bi-receipt"></i> 80mm Thermal
                    </button>
                    <button type="button" id="btn-preview-a4" class="preview-tab-btn" onclick="switchPreviewFormat('a4')">
                        <i class="bi bi-file-earmark-text"></i> A4 Invoice
                    </button>
                </div>
            </div>

            <!-- Embedded Live Bill Preview Frame -->
            <div style="position: relative; width: 100%; height: 440px; background: #e2e8f0; border: 1px solid #cbd5e1; border-radius: 10px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);">
                <iframe id="receipt-preview-frame" style="width: 100%; height: 100%; border: none; display: block; background: #f8fafc;" src="about:blank"></iframe>
            </div>
        </div>
        <div class="modal-footer" style="padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-top: 1px solid var(--border); border-radius: 0 0 var(--radius-lg) var(--radius-lg); flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-primary" id="btn-print-thermal-receipt" onclick="printThermalReceipt()">
                    <i class="bi bi-printer-fill"></i> Re-Print Thermal (P)
                </button>
                <button type="button" class="btn btn-outline" onclick="printA4Invoice()">
                    <i class="bi bi-file-earmark-text"></i> Print A4 (A)
                </button>
            </div>
            <button type="button" class="btn btn-success btn-lg" onclick="startNewSale()" style="min-width: 170px;">
                <i class="bi bi-plus-circle"></i> New Sale (Enter)
            </button>
        </div>
    </div>
</div>

<!-- Modal: Quick Communication Services (F3) -->
<div class="modal-backdrop" id="services-hotkey-modal">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-printer-fill" style="color: var(--primary);"></i>
                <span>Quick Communication Services</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('services-hotkey-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 13px; color: #64748b; margin-bottom: 14px;">
                Press number key <kbd>1</kbd> to <kbd>5</kbd> on your keyboard to instantly add to bill:
            </p>
            <div class="service-hotkey-list">
                <div class="service-hotkey-item" onclick="quickAddServiceKey('SRV-XER-BW')">
                    <div>
                        <strong style="color: #0f172a; font-size: 14px;">B&W Photocopy (Single Side)</strong>
                        <div style="font-size: 13px; color: var(--primary); font-family: var(--font-mono); font-weight: 700; margin-top: 2px;">Rs. 10.00 / page</div>
                    </div>
                    <kbd>1</kbd>
                </div>
                <div class="service-hotkey-item" onclick="quickAddServiceKey('SRV-XER-DS')">
                    <div>
                        <strong style="color: #0f172a; font-size: 14px;">B&W Photocopy (Double Side)</strong>
                        <div style="font-size: 13px; color: var(--primary); font-family: var(--font-mono); font-weight: 700; margin-top: 2px;">Rs. 15.00 / page</div>
                    </div>
                    <kbd>2</kbd>
                </div>
                <div class="service-hotkey-item" onclick="quickAddServiceKey('SRV-PRT-CLR')">
                    <div>
                        <strong style="color: #0f172a; font-size: 14px;">Color Printout High Quality A4</strong>
                        <div style="font-size: 13px; color: var(--primary); font-family: var(--font-mono); font-weight: 700; margin-top: 2px;">Rs. 40.00 / page</div>
                    </div>
                    <kbd>3</kbd>
                </div>
                <div class="service-hotkey-item" onclick="quickAddServiceKey('SRV-LAM-A4')">
                    <div>
                        <strong style="color: #0f172a; font-size: 14px;">A4 Thermal Document Lamination</strong>
                        <div style="font-size: 13px; color: var(--primary); font-family: var(--font-mono); font-weight: 700; margin-top: 2px;">Rs. 100.00 / doc</div>
                    </div>
                    <kbd>4</kbd>
                </div>
                <div class="service-hotkey-item" onclick="openReloadFromServices()">
                    <div>
                        <strong style="color: #0f172a; font-size: 14px;">Mobile Reload / Top-up</strong>
                        <div style="font-size: 13px; color: #10b981; font-weight: 600; margin-top: 2px;">Dialog, Mobitel, Airtel, Hutch, Bills</div>
                    </div>
                    <kbd>5</kbd>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('services-hotkey-modal')">Close (Esc)</button>
        </div>
    </div>
</div>

<!-- Modal: Cashier Keyboard Shortcuts Cheat Sheet (F12) -->
<div class="modal-backdrop" id="shortcuts-help-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-keyboard-fill" style="color: var(--primary);"></i>
                <span>Cashier Keyboard Shortcuts Cheat Sheet</span>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('shortcuts-help-modal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <h5 style="font-size: 13px; font-weight: 800; color: #4338ca; text-transform: uppercase; margin-bottom: 12px; border-bottom: 2px solid var(--primary-light); padding-bottom: 6px;">
                        ⚡ Terminal Action Keys
                    </h5>
                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F1</kbd> <span>Focus Barcode Scanner / Search</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F2</kbd> <span>Quick Mobile Reload Modal</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F3</kbd> <span>Xerox, Print & Lamination Menu</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F4</kbd> <span>Hold Current Bill in Queue</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F6</kbd> <span>Recall Held Bills Modal</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F7</kbd> <span>Clear / Cancel Active Cart</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F8</kbd> <span>Jump to Customer Name / Phone</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F10</kbd> <span>Jump to Discount Field</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>F9</kbd> or <kbd>End</kbd> <strong style="color: #059669; font-weight: 800;">Pay / Checkout Modal</strong></div>
                    </div>
                </div>

                <div>
                    <h5 style="font-size: 13px; font-weight: 800; color: #047857; text-transform: uppercase; margin-bottom: 12px; border-bottom: 2px solid var(--accent-light); padding-bottom: 6px;">
                        🛒 Cart Adjustments
                    </h5>
                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>+</kbd> or <kbd>&uarr;</kbd> <span>Increase Last Item Quantity</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>-</kbd> or <kbd>&darr;</kbd> <span>Decrease Last Item Quantity</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>Del</kbd> <span>Remove Last Item from Cart</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>Esc</kbd> <span>Close Modals / Return to Scan</span></div>
                    </div>

                    <h5 style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-top: 18px; margin-bottom: 12px; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px;">
                        💳 In Checkout Modal
                    </h5>
                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>1</kbd> / <kbd>2</kbd> / <kbd>3</kbd> / <kbd>4</kbd> <span>Cash / Card / QR / Transfer</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>Space</kbd> or <kbd>x</kbd> <span>Exact Cash Tendered</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;"><kbd>Enter</kbd> <strong style="color: #4f46e5;">Complete Sale & Print</strong></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeModal('shortcuts-help-modal')">Got it! (Esc)</button>
        </div>
    </div>
</div>

<!-- Hidden Receipt Iframe for Seamless Background Printing -->
<iframe id="receipt-print-iframe" style="position: fixed; right: 0; bottom: 0; width: 0; height: 0; border: 0; opacity: 0; pointer-events: none;"></iframe>

<script src="assets/js/pos.js?v=<?= filemtime(__DIR__ . '/assets/js/pos.js') ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
