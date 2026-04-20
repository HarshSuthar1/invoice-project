<?php
$docType = $_GET['type'] ?? 'invoice';
$docTitles = [
    'quotation' => 'Quotation',
    'bill-no-gst' => 'Bill (No GST)',
    'invoice' => 'Invoice (With GST)',
    'challan' => 'Transport Challan'
];
$docTitle = $docTitles[$docType] ?? 'Invoice';
$showTax = !in_array($docType, ['bill-no-gst', 'quotation', 'challan']);
$isChallan = $docType === 'challan';
$isInvoice = $docType === 'invoice';
$showImageColumn = in_array($docType, ['quotation', 'bill-no-gst']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css?v=20260417c">
    <title>Create <?php echo $docTitle; ?></title>
    <style>
        .section-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-header-bar h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-heading);
            margin: 0;
        }

        .btn-back {
            background: var(--surface-color);
            color: var(--text-primary);
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: var(--surface-subtle);
        }

        .import-section {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .import-section h4 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }

        .import-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .import-buttons .btn {
            flex: 1;
            min-width: 200px;
        }

        .issuer-toggle-group {
            margin-bottom: 24px;
        }

        .issuer-toggle-group > label {
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .issuer-options {
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
            padding: 6px;
            background: #e5e7eb;
            border-radius: 999px;
        }

        .issuer-option {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #4b5563;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 200ms ease, color 200ms ease, transform 200ms ease, box-shadow 200ms ease;
        }

        .issuer-option:hover {
            transform: translateY(-1px);
        }

        .issuer-option.active {
            background: #3b82f6;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.22);
        }

        .issuer-option input {
            display: none;
        }

        .issuer-info-banner {
            display: none;
            margin-bottom: 20px;
            padding: 12px 16px;
            border-radius: 8px;
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            font-size: 14px;
            font-weight: 500;
        }

        .issuer-info-banner.visible {
            display: block;
        }

        .tax-column {
            display: <?php echo $showTax ? 'table-cell' : 'none'; ?>;
        }

        .image-column {
            display: <?php echo $showImageColumn ? 'table-cell' : 'none'; ?>;
        }

        /* Item Image Paste Area */
        .item-image-paste {
            position: relative;
            min-width: 120px;
            width: 120px;
        }

        .image-paste-box {
            width: 100px;
            height: 100px;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f9fafb;
            position: relative;
            overflow: hidden;
        }

        .image-paste-box:hover {
            border-color: #3b82f6;
            background: #f0f9ff;
        }

        .image-paste-box.has-image {
            border-style: solid;
            border-color: #10b981;
            padding: 0;
        }

        .paste-icon {
            color: #9ca3af;
            font-size: 12px;
            text-align: center;
            padding: 8px;
        }

        .paste-icon svg {
            width: 24px;
            height: 24px;
            margin-bottom: 4px;
        }

        .item-image-preview {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
        }

        .item-image-preview.show {
            display: block;
        }

        .remove-item-image {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 4px;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .image-paste-box.has-image .remove-item-image {
            display: flex;
        }

        .remove-item-image:hover {
            background: #dc2626;
        }

        /* Hidden file input */
        .item-image-file-input {
            display: none;
        }

        /* Help text */
        .image-help {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .image-help p {
            margin: 0;
            font-size: 14px;
            color: #1e40af;
        }

        .image-help strong {
            font-weight: 600;
        }

        /* ============================================================
           DISCOUNT STYLES
           ============================================================ */

        /* Discount row in summary — hidden by default, shown when > 0 */
        .discount-row {
            display: none;
        }
        .discount-row.visible {
            display: flex;
        }

        /* Discount input section — sits between items table and summary */
        .discount-section {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            margin: 16px 0 8px 0;
            padding: 14px 20px;
            background: #fffbeb;
            border: 1px dashed #f59e0b;
            border-radius: 8px;
        }

        .discount-section label {
            font-size: 14px;
            font-weight: 600;
            color: #92400e;
            white-space: nowrap;
        }

        .discount-type-toggle {
            display: flex;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            overflow: hidden;
            background: white;
        }

        .discount-type-btn {
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            background: white;
            color: #6b7280;
            cursor: pointer;
            transition: all 150ms ease;
        }

        .discount-type-btn.active {
            background: #f59e0b;
            color: white;
        }

        .discount-type-btn:first-child {
            border-right: 1px solid #d1d5db;
        }

        .discount-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .discount-input-wrap .discount-symbol {
            position: absolute;
            left: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            pointer-events: none;
        }

        /* symbol shifts right when % is active */
        .discount-input-wrap .discount-symbol.percent {
            left: auto;
            right: 10px;
        }

        #discountValue {
            width: 110px;
            padding: 7px 12px 7px 24px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            outline: none;
            transition: border-color 200ms ease;
            color: #1f2937;
        }

        #discountValue.percent-mode {
            padding: 7px 28px 7px 12px;
        }

        #discountValue:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        .discount-clear-btn {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            padding: 2px 4px;
            transition: color 150ms;
        }

        .discount-clear-btn:hover {
            color: #ef4444;
        }

        /* Summary discount row styling */
        .summary-row.discount-savings {
            color: #059669;
        }

        .summary-row.discount-savings span:first-child {
            color: #059669;
        }

        .summary-row.discount-savings span:last-child {
            color: #059669;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="section-header-bar">
            <h2>Create <?php echo $docTitle; ?></h2>
            <a href="/Business%20project/public/index.php?page=create-hub" class="btn-back">
                ← Back to Hub
            </a>
        </div>

        <div class="invoice-card">
            <form id="documentForm">
                <input type="hidden" id="documentType" name="document_type" value="<?php echo htmlspecialchars($docType); ?>">

                <div class="issuer-toggle-group">
                    <label>Issued By</label>
                    <div class="issuer-options">
                        <label class="issuer-option active" data-value="company">
                            <input type="radio" name="issuer_type" value="company" checked>
                            🏭 GS Metal Concept
                        </label>
                        <label class="issuer-option" data-value="personal">
                            <input type="radio" name="issuer_type" value="personal">
                            👤 Harsh (Personal)
                        </label>
                    </div>
                </div>

                <div class="issuer-info-banner" id="issuerInfoBanner">
                    Personal mode: No GST, no company details on document
                </div>

                <!-- Import Section (Only for Invoice) -->
                <?php if ($isInvoice): ?>
                <div class="import-section">
                    <h4>💡 Import Data From Existing Document</h4>
                    <div class="import-buttons">
                        <button type="button" class="btn btn-view" data-action="import-from" data-import-type="quotation">
                            Import from Quotation
                        </button>
                        <button type="button" class="btn btn-view" data-action="import-from" data-import-type="bill-no-gst">
                            Import from Bill (No GST)
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Document Details -->
                <div class="invoice-details-grid">
                    <div class="form-group">
                        <label for="clientSelect">Client <span class="required">*</span></label>
                        <select id="clientSelect" name="client_id" required>
                            <option value="">Loading clients...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="document-date">Date <span class="required">*</span></label>
                        <input type="date" id="document-date" name="document_date" required>
                    </div>
                    <div class="form-group">
                        <label for="document-number">Document # <span class="required">*</span></label>
                        <input type="text" id="document-number" name="document_number" required>
                    </div>
                </div>

                <?php if ($showImageColumn): ?>
                <div class="image-help">
                    <p>📸 <strong>Tip:</strong> Click on the image box in each row and press <strong>Ctrl+V</strong> to paste product images directly from clipboard!</p>
                </div>
                <?php endif; ?>

                <!-- Items Table -->
                <div class="table-container">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <?php if ($isChallan): ?>
                                    <th style="width: 12%;">Date</th>
                                    <th style="width: 45%;">Description (Starting → Ending Destination)</th>
                                    <th style="width: 12%;">Rounds</th>
                                    <th style="width: 18%;">Amount</th>
                                    <th style="width: 13%;">Action</th>
                                <?php else: ?>
                                    <th>Description</th>
                                    <?php if ($showTax): ?>
                                    <th style="width: 10%;">HSN Code</th>
                                    <?php endif; ?>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Unit Price</th>
                                    <th class="tax-column">Tax (%)</th>
                                    <th>Amount</th>
                                    <?php if ($showImageColumn): ?>
                                    <th class="image-column" style="width: 130px;">Image</th>
                                    <?php endif; ?>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="documentItemsBody">
                            <!-- Rows added dynamically -->
                        </tbody>
                    </table>
                </div>

                <button type="button" class="add-item-button" data-action="add-item">+ Add Row</button>

                <!-- ============================================================
                     DISCOUNT SECTION — sits between items and summary
                     ============================================================ -->
                <?php if (!$isChallan): ?>
                <div class="discount-section" id="discountSection">
                    <label for="discountValue">🏷️ Discount</label>

                    <!-- ₹ / % toggle -->
                    <div class="discount-type-toggle">
                        <button type="button" class="discount-type-btn active" id="discountFlat" data-type="flat">₹ Flat</button>
                        <button type="button" class="discount-type-btn" id="discountPercent" data-type="percent">% Off</button>
                    </div>

                    <!-- Input -->
                    <div class="discount-input-wrap">
                        <span class="discount-symbol" id="discountSymbol">₹</span>
                        <input
                            type="number"
                            id="discountValue"
                            name="discount"
                            value="0"
                            min="0"
                            step="0.01"
                            placeholder="0"
                        >
                    </div>

                    <!-- Clear button -->
                    <button type="button" class="discount-clear-btn" id="discountClear" title="Clear discount">×</button>
                </div>
                <?php endif; ?>

                <!-- Summary Section -->
                <div class="summary-section">
                    <div class="summary-card">
                        <?php if (!$isChallan): ?>
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span id="subtotal">₹0.00</span>
                        </div>
                        <?php if ($showTax): ?>
                        <div class="summary-item tax-column">
                            <span>CGST</span>
                            <span id="cgst">₹0.00</span>
                        </div>
                        <div class="summary-item tax-column">
                            <span>SGST</span>
                            <span id="sgst">₹0.00</span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-item tax-column">
                            <span>Total Tax</span>
                            <span id="totalTax">₹0.00</span>
                        </div>
                        <!-- Discount row — only visible when discount > 0 -->
                        <div class="summary-item discount-row discount-savings" id="discountRow">
                            <span id="discountLabel">Discount</span>
                            <span id="discountDisplay">-₹0.00</span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-total">
                            <span>Total</span>
                            <span id="grandTotal">₹0.00</span>
                        </div>
                    </div>
                </div>

                <div class="create-invoice-button-container">
                    <button type="button" class="create-invoice-button" data-action="save-document">
                        Create <?php echo $docTitle; ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Import Modal -->
    <div class="modal-overlay" id="importModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Document to Import</h3>
                <button class="close-button" data-action="close-modal" data-target="importModal">&times;</button>
            </div>
            <div class="table-container">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Document #</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="importTableBody">
                        <tr><td colspan="5" class="loading">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="success-message" id="successMessage">✓ Success!</div>
    <div class="error-message" id="errorMessage">✗ Error occurred!</div>

    <script type="module">
import { qs, qsa } from '/Business%20project/assets/js/core/dom.js';
import { showError, showSuccess, openModal, closeModal } from '/Business%20project/assets/js/core/ui.js';
import { required } from '/Business%20project/assets/js/core/validators.js';
import { fetchClients } from '/Business%20project/assets/js/core/data/clients.js';
import { apiFetch } from '/Business%20project/assets/js/core/api.js';
import { formatCurrency } from '/Business%20project/assets/js/core/utils.js';

let itemIndex = 0;
const currentDocType = '<?php echo $docType; ?>';
const isChallan = currentDocType === 'challan';
const canUseTax = !['bill-no-gst', 'quotation', 'challan'].includes(currentDocType);
let showTax = canUseTax;
const showImageColumn = ['quotation', 'bill-no-gst'].includes(currentDocType);
let issuerType = 'company';

/* ============================================================
   DISCOUNT STATE
   ============================================================ */
let discountType = 'flat';   // 'flat' | 'percent'
let discountValue = 0;

/* ============================================================
   DISCOUNT UI HELPERS
   ============================================================ */
function syncDiscountMode(nextType) {
    const flatBtn    = qs('#discountFlat');
    const percentBtn = qs('#discountPercent');
    const input      = qs('#discountValue');
    const symbol     = qs('#discountSymbol');

    discountType = nextType;

    if (flatBtn) flatBtn.classList.toggle('active', nextType === 'flat');
    if (percentBtn) percentBtn.classList.toggle('active', nextType === 'percent');

    if (!input || !symbol) return;

    if (nextType === 'percent') {
        symbol.textContent = '%';
        symbol.classList.add('percent');
        input.classList.add('percent-mode');
        input.style.paddingLeft = '12px';
        input.style.paddingRight = '28px';
        input.step = '0.01';
        input.max = '100';
    } else {
        symbol.textContent = '₹';
        symbol.classList.remove('percent');
        input.classList.remove('percent-mode');
        input.style.paddingLeft = '24px';
        input.style.paddingRight = '12px';
        input.step = '0.01';
        input.removeAttribute('max');
    }
}

function resetDiscount({ keepType = false } = {}) {
    const input = qs('#discountValue');

    discountValue = 0;
    if (!keepType) syncDiscountMode('flat');

    if (input) {
        input.value = '0';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    } else {
        calculateTotals();
    }
}

function updateTaxVisibility() {
    const displayValue = showTax ? '' : 'none';
    qsa('.tax-column').forEach(el => {
        el.style.display = displayValue;
    });

    const banner = qs('#issuerInfoBanner');
    if (banner) {
        banner.classList.toggle('visible', issuerType === 'personal');
    }

    if (!showTax) {
        const cgstEl = qs('#cgst');
        const sgstEl = qs('#sgst');
        const totalTaxEl = qs('#totalTax');
        if (cgstEl) cgstEl.textContent = formatCurrency(0);
        if (sgstEl) sgstEl.textContent = formatCurrency(0);
        if (totalTaxEl) totalTaxEl.textContent = formatCurrency(0);
    }
}

function syncIssuerType(nextType) {
    issuerType = nextType === 'personal' ? 'personal' : 'company';
    showTax = canUseTax && issuerType === 'company';

    qsa('.issuer-option').forEach(option => {
        option.classList.toggle('active', option.dataset.value === issuerType);
        const radio = option.querySelector('input[type="radio"]');
        if (radio) radio.checked = option.dataset.value === issuerType;
    });

    updateTaxVisibility();
    calculateTotals();
}

function setupIssuerToggle() {
    qsa('.issuer-option input[name="issuer_type"]').forEach(input => {
        input.addEventListener('change', () => {
            if (input.checked) syncIssuerType(input.value);
        });
    });

    syncIssuerType(qs('.issuer-option input[name="issuer_type"]:checked')?.value || 'company');
}

/* ============================================================
   DISCOUNT CONTROLS SETUP
   ============================================================ */
function setupDiscountControls() {
    if (isChallan) return;

    const flatBtn     = qs('#discountFlat');
    const percentBtn  = qs('#discountPercent');
    const input       = qs('#discountValue');
    const symbol      = qs('#discountSymbol');
    const clearBtn    = qs('#discountClear');

    // Toggle between flat and percent modes
    flatBtn?.addEventListener('click', () => {
        syncDiscountMode('flat');
        symbol.textContent = '₹';
        symbol.classList.remove('percent');
        input.classList.remove('percent-mode');
        input.style.paddingLeft = '24px';
        input.style.paddingRight = '12px';
        // Clamp: if previous percent caused a huge flat value, just reset
        discountValue = 0;
        input.value = 0;
        calculateTotals();
    });

    percentBtn?.addEventListener('click', () => {
        discountType = 'percent';
        percentBtn.classList.add('active');
        flatBtn.classList.remove('active');
        symbol.textContent = '%';
        symbol.classList.add('percent');
        input.classList.add('percent-mode');
        input.style.paddingLeft = '12px';
        input.style.paddingRight = '28px';
        discountValue = 0;
        input.value = 0;
        calculateTotals();
    });

    // Live update on input
    input?.addEventListener('input', () => {
        let v = parseFloat(input.value) || 0;
        if (v < 0) v = 0;
        if (discountType === 'percent' && v > 100) v = 100;
        discountValue = v;
        calculateTotals();
    });

    // Clear discount
    clearBtn?.addEventListener('click', () => {
        discountValue = 0;
        if (input) input.value = 0;
        calculateTotals();
    });
}

function installDiscountControlOverrides() {
    if (isChallan) return;

    const flatBtn    = qs('#discountFlat');
    const percentBtn = qs('#discountPercent');
    const input      = qs('#discountValue');
    const clearBtn   = qs('#discountClear');

    flatBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();
        syncDiscountMode('flat');
        resetDiscount({ keepType: true });
    }, true);

    percentBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();
        syncDiscountMode('percent');
        resetDiscount({ keepType: true });
    }, true);

    clearBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();
        resetDiscount();
    }, true);

    input?.addEventListener('input', () => {
        let v = parseFloat(input.value) || 0;
        if (v < 0) v = 0;
        if (discountType === 'percent' && v > 100) v = 100;
        if (String(v) !== input.value) input.value = String(v);
        discountValue = v;
    });

    syncDiscountMode(discountType);
}

/* ============================================================
   CALCULATE TOTALS  (discount-aware)
   ============================================================ */
function calculateTotals() {
    let subtotalAmt = 0;
    let taxTotal    = 0;

    qsa('#documentItemsBody tr').forEach(row => {
        const qty   = Number(row.querySelector('.qty')?.value)   || 0;
        const price = Number(row.querySelector('.price')?.value) || 0;

        if (isChallan) {
            subtotalAmt += qty * price;
        } else {
            const taxRate    = showTax ? (Number(row.querySelector('.tax')?.value) || 0) : 0;
            const lineAmount = qty * price;
            const taxAmount  = (lineAmount * taxRate) / 100;

            subtotalAmt += lineAmount;
            taxTotal    += taxAmount;

            const lineTotal = row.querySelector('.lineTotal');
            if (lineTotal) lineTotal.textContent = formatCurrency(lineAmount + taxAmount);
        }
    });

    // --- Compute discount amount ---
    let discountAmt = 0;
    if (!isChallan && discountValue > 0) {
        if (discountType === 'flat') {
            discountAmt = Math.min(discountValue, subtotalAmt + taxTotal); // can't exceed total
        } else {
            // percent off the pre-tax subtotal
            discountAmt = (subtotalAmt * discountValue) / 100;
        }
    }

    const grandTotalAmt = subtotalAmt + taxTotal - discountAmt;

    // --- Update DOM ---
    if (!isChallan) {
        const subtotalEl = qs('#subtotal');
        if (subtotalEl) subtotalEl.textContent = formatCurrency(subtotalAmt);

        if (showTax) {
            const cgst = taxTotal / 2;
            const sgst = taxTotal / 2;
            const cgstEl = qs('#cgst');
            const sgstEl = qs('#sgst');
            if (cgstEl) cgstEl.textContent = formatCurrency(cgst);
            if (sgstEl) sgstEl.textContent = formatCurrency(sgst);
        } else {
            const cgstEl = qs('#cgst');
            const sgstEl = qs('#sgst');
            if (cgstEl) cgstEl.textContent = formatCurrency(0);
            if (sgstEl) sgstEl.textContent = formatCurrency(0);
        }

        const totalTaxEl = qs('#totalTax');
        if (totalTaxEl) totalTaxEl.textContent = formatCurrency(taxTotal);

        // Discount row — show/hide
        const discountRow = qs('#discountRow');
        const discountDisplay = qs('#discountDisplay');
        const discountLabel   = qs('#discountLabel');

        if (discountRow && discountDisplay) {
            if (discountAmt > 0) {
                discountRow.classList.add('visible');
                const suffix = discountType === 'percent'
                    ? ` (${discountValue}%)`
                    : '';
                if (discountLabel) discountLabel.textContent = `Discount${suffix}`;
                discountDisplay.textContent = `-${formatCurrency(discountAmt)}`;
            } else {
                discountRow.classList.remove('visible');
                if (discountLabel) discountLabel.textContent = 'Discount';
                discountDisplay.textContent = `-${formatCurrency(0)}`;
            }
        }
    }

    const grandTotalEl = qs('#grandTotal');
    if (grandTotalEl) grandTotalEl.textContent = formatCurrency(Math.max(0, grandTotalAmt));
}

/* ============================================================
   IMAGE RESIZE
   ============================================================ */
function resizeImage(base64Str, maxWidth = 300, maxHeight = 300) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let width = img.width;
            let height = img.height;

            if (width > height) {
                if (width > maxWidth) { height = (height * maxWidth) / width; width = maxWidth; }
            } else {
                if (height > maxHeight) { width = (width * maxHeight) / height; height = maxHeight; }
            }

            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);
            resolve(canvas.toDataURL('image/jpeg', 0.8));
        };
        img.src = base64Str;
    });
}

/* ============================================================
   ITEM IMAGE PASTE
   ============================================================ */
function setupItemImagePaste(row) {
    if (!showImageColumn) return;

    const pasteBox  = row.querySelector('.image-paste-box');
    const preview   = row.querySelector('.item-image-preview');
    const pasteIcon = row.querySelector('.paste-icon');
    const removeBtn = row.querySelector('.remove-item-image');
    const fileInput = row.querySelector('.item-image-file-input');

    pasteBox.addEventListener('paste', async (e) => {
        e.preventDefault();
        for (let i = 0; i < e.clipboardData.items.length; i++) {
            if (e.clipboardData.items[i].type.indexOf('image') !== -1) {
                await handleItemImage(e.clipboardData.items[i].getAsFile(), pasteBox, preview, pasteIcon);
                break;
            }
        }
    });

    pasteBox.addEventListener('click', () => {
        if (!pasteBox.classList.contains('has-image')) fileInput.click();
    });

    fileInput.addEventListener('change', async (e) => {
        if (e.target.files?.[0]) await handleItemImage(e.target.files[0], pasteBox, preview, pasteIcon);
    });

    removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        preview.src = '';
        preview.classList.remove('show');
        pasteIcon.style.display = 'block';
        pasteBox.classList.remove('has-image');
        pasteBox.dataset.imageData = '';
    });

    async function handleItemImage(file, box, img, icon) {
        if (!file.type.startsWith('image/')) { showError('Please paste/select an image file'); return; }
        const reader = new FileReader();
        reader.onload = async (e) => {
            const resized = await resizeImage(e.target.result, 300, 300);
            img.src = resized;
            img.classList.add('show');
            icon.style.display = 'none';
            box.classList.add('has-image');
            box.dataset.imageData = resized;
        };
        reader.readAsDataURL(file);
    }
}

/* ============================================================
   ADD ITEM ROW
   ============================================================ */
function addItemRow(data = null) {
    const tbody = qs('#documentItemsBody');
    const row   = document.createElement('tr');
    row.dataset.index = itemIndex++;

    if (isChallan) {
        row.innerHTML = `
            <td><input type="date" class="row-date" value="${data?.date || ''}" required></td>
            <td><textarea class="desc" rows="2" placeholder="e.g., Ahmedabad → Mumbai" required>${data?.description || ''}</textarea></td>
            <td><input type="number" class="qty" value="${data?.quantity || 1}" min="0" step="1"></td>
            <td><input type="number" class="price" value="${data?.price || 0}" min="0" step="0.01"></td>
            <td><button type="button" class="btn-delete btn" data-action="remove-item">×</button></td>
        `;
    } else {
        const hsnCell = canUseTax
            ? `<td><input type="text" class="hsn" value="${data?.hsn || ''}" placeholder="e.g., 7308"></td>`
            : '';
        const imageCell = showImageColumn ? `
            <td class="image-column">
                <div class="item-image-paste">
                    <div class="image-paste-box" tabindex="0" data-image-data="">
                        <div class="paste-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            <div style="font-size: 10px;">Paste</div>
                        </div>
                        <img class="item-image-preview" src="" alt="Item">
                        <button type="button" class="remove-item-image">×</button>
                    </div>
                    <input type="file" class="item-image-file-input" accept="image/*">
                </div>
            </td>
        ` : '';

        row.innerHTML = `
            <td><input type="text" class="desc" value="${data?.description || ''}" required></td>
            ${hsnCell}
            <td><input type="number" class="qty" value="${data?.quantity || 1}" min="0" step="0.01"></td>
            <td><input type="text" class="unit" value="${data?.unit || 'Nos'}"></td>
            <td><input type="number" class="price" value="${data?.price || 0}" min="0" step="0.01"></td>
            <td class="tax-column"><input type="number" class="tax" value="${data?.tax || 0}" min="0" max="100" step="0.01"></td>
            <td class="lineTotal">₹0.00</td>
            ${imageCell}
            <td><button type="button" class="btn-delete btn" data-action="remove-item">×</button></td>
        `;
    }

    tbody.appendChild(row);
    if (showImageColumn) setupItemImagePaste(row);
    updateTaxVisibility();
    calculateTotals();
}

/* ============================================================
   IMPORT MODAL
   ============================================================ */
async function openImportModal(importType) {
    try {
        const res = await apiFetch(`/api/documents/get_documents.php?type=${importType}`);
        const documents = res.documents || [];
        const tbody = qs('#importTableBody');
        tbody.innerHTML = '';

        if (!documents.length) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">No ${importType} documents found</td></tr>`;
        } else {
            documents.forEach(doc => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${doc.document_number}</td>
                    <td>${doc.client_name || 'N/A'}</td>
                    <td>${doc.document_date || doc.created_at}</td>
                    <td>${formatCurrency(doc.grand_total)}</td>
                    <td><button type="button" class="btn btn-view" data-action="import-document" data-doc-id="${doc.id}">Import</button></td>
                `;
                tbody.appendChild(tr);
            });
        }
        openModal('importModal');
    } catch (err) {
        console.error(err);
        showError('Failed to load documents for import');
    }
}

async function importDocument(docId) {
    try {
        const res = await apiFetch(`/api/documents/get_document_details.php?id=${docId}`);
        const doc  = res.document;
        const items = res.items || [];

        qs('#clientSelect').value = doc.client_id;
        qs('#documentItemsBody').innerHTML = '';
        itemIndex = 0;

        items.forEach(item => {
            addItemRow({
                description: item.description,
                hsn:         item.hsn_code,
                quantity:    item.quantity,
                unit:        item.unit || 'Nos',
                price:       item.unit_price || item.price,
                tax:         canUseTax ? (item.tax_rate || 0) : 0
            });
        });

        if (!items.length) addItemRow();

        if (!isChallan) {
            syncDiscountMode('flat');
            discountValue = Number(doc.discount) || 0;
            const discountInput = qs('#discountValue');
            if (discountInput) discountInput.value = String(discountValue);
        }

        closeModal('importModal');
        showSuccess('Document data imported successfully');
        calculateTotals();
    } catch (err) {
        console.error(err);
        showError('Failed to import document');
    }
}

/* ============================================================
   SAVE DOCUMENT
   ============================================================ */
async function saveDocument() {
    if (!required(qs('#clientSelect').value)) { showError('Please select a client'); return; }
    if (!required(qs('#document-date').value)) { showError('Please enter a date'); return; }

    const items = [];
    let hasEmptyDescription = false;

    qsa('#documentItemsBody tr').forEach(row => {
        const desc = row.querySelector('.desc').value.trim();
        if (!desc) { hasEmptyDescription = true; return; }

        if (isChallan) {
            items.push({
                description: desc,
                date:        row.querySelector('.row-date').value,
                quantity:    row.querySelector('.qty').value,
                unit:        'Rounds',
                price:       row.querySelector('.price').value,
                tax:         0
            });
        } else {
            const itemData = {
                description: desc,
                hsn_code:    showTax ? (row.querySelector('.hsn')?.value || '') : '',
                quantity:    row.querySelector('.qty').value,
                unit:        row.querySelector('.unit').value,
                price:       row.querySelector('.price').value,
                tax:         showTax ? (row.querySelector('.tax')?.value || 0) : 0
            };

            if (showImageColumn) {
                const imageBox = row.querySelector('.image-paste-box');
                if (imageBox?.dataset.imageData) itemData.image = imageBox.dataset.imageData;
            }

            items.push(itemData);
        }
    });

    if (hasEmptyDescription) { showError('Please fill in all descriptions'); return; }
    if (!items.length) { showError('Add at least one row'); return; }

    // --- Build the discount amount to send to backend ---
    let subtotalAmt = 0;
    let taxTotal    = 0;
    items.forEach(it => {
        const lineAmt = parseFloat(it.quantity) * parseFloat(it.price);
        const taxAmt  = (lineAmt * parseFloat(it.tax || 0)) / 100;
        subtotalAmt  += lineAmt;
        taxTotal     += taxAmt;
    });

    let discountAmt = 0;
    if (!isChallan && discountValue > 0) {
        discountAmt = discountType === 'flat'
            ? Math.min(discountValue, subtotalAmt + taxTotal)
            : (subtotalAmt * discountValue) / 100;
    }

    const grandTotalAmt = Math.max(0, subtotalAmt + taxTotal - discountAmt);

    const formData = new FormData(qs('#documentForm'));
    formData.append('items', JSON.stringify(items));
    formData.append('grand_total',   grandTotalAmt.toFixed(2));
    formData.append('discount',      discountAmt.toFixed(2));
    formData.append('discount_type', discountType);

    if (!isChallan) {
        formData.append('subtotal',  subtotalAmt.toFixed(2));
        formData.append('total_tax', taxTotal.toFixed(2));
    } else {
        formData.append('subtotal',  grandTotalAmt.toFixed(2));
        formData.append('total_tax', '0');
    }

    const saveBtn = qs('[data-action="save-document"]');
    const form    = qs('#documentForm');

    try {
        form?.classList.add('loading');
        if (saveBtn) saveBtn.disabled = true;

        await apiFetch('/api/documents/save_document.php', { method: 'POST', body: formData });

        showSuccess('Document created successfully');
        setTimeout(() => {
            window.location.href = '/Business%20project/public/index.php?page=manage-documents';
        }, 1500);
    } catch (err) {
        showError(err.message || 'Failed to save document');
    } finally {
        form?.classList.remove('loading');
        if (saveBtn) saveBtn.disabled = false;
    }
}

/* ============================================================
   EVENT LISTENERS
   ============================================================ */
document.addEventListener('click', (e) => {
    const actionEl = e.target.closest('[data-action]');
    const action = actionEl?.dataset.action;
    if (!action) return;

    if (action === 'add-item')         addItemRow();
    if (action === 'save-document')    saveDocument();

    if (action === 'remove-item') {
        const row = actionEl.closest('tr');
        if (qsa('#documentItemsBody tr').length > 1) {
            row?.remove();
            calculateTotals();
        } else {
            showError('At least one row is required');
        }
    }

    if (action === 'import-from') openImportModal(actionEl.dataset.importType);
    if (action === 'import-document') importDocument(actionEl.dataset.docId);
    if (action === 'close-modal') closeModal(actionEl.dataset.target);
});

document.addEventListener('input', (e) => {
    if (e.target.closest('#documentItemsBody')) calculateTotals();
});

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    qs('#document-date').value = new Date().toISOString().split('T')[0];

    // Load dropdown data
    (async () => {
        try {
            const clients = await fetchClients();
            const select  = qs('#clientSelect');
            select.innerHTML = `<option value="">Select Client</option>`;
            clients.forEach(c => {
                const opt = document.createElement('option');
                opt.value       = c.id;
                opt.textContent = c.company_name || `Client #${c.id}`;
                select.appendChild(opt);
            });
        } catch (err) {
            console.error(err);
            showError('Failed to load clients');
        }
    })();

    (async () => {
        try {
            const res = await apiFetch(`/api/documents/get_next_number.php?type=${currentDocType}`);
            qs('#document-number').value = res.next_number || '1';
        } catch (err) {
            console.error(err);
            qs('#document-number').value = Date.now().toString().slice(-6);
        }
    })();

    // Wire discount controls
    setupDiscountControls();
    installDiscountControlOverrides();
    setupIssuerToggle();

    // Add first row
    addItemRow();
});
    </script>
    <script type="module" src="/Business%20project/assets/js/main.js"></script>
</body>
</html>
