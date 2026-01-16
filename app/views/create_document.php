<?php
$docType = $_GET['type'] ?? 'invoice';
$docTitles = [
    'quotation' => 'Quotation',
    'bill-no-gst' => 'Bill (No GST)',
    'invoice' => 'Invoice (With GST)',
    'challan' => 'Transport Challan'
];
$docTitle = $docTitles[$docType] ?? 'Invoice';
$showTax = !in_array($docType, ['bill-no-gst', 'quotation']);
$isChallan = $docType === 'challan';
$isInvoice = $docType === 'invoice';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Create <?php echo $docTitle; ?></title>
    <style>
        .section-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-header-bar h2 {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .btn-back {
            background: #f3f4f6;
            color: #374151;
            padding: 8px 16px;
            border: none;
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
            background: #e5e7eb;
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

        .challan-fields {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .challan-fields h4 {
            font-size: 16px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 16px;
        }

        .tax-column {
            display: <?php echo $showTax ? 'table-cell' : 'none'; ?>;
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

                <!-- Transport Challan Specific Fields -->
                <?php if ($isChallan): ?>
                <div class="challan-fields">
                    <h4>🚚 Transport Details</h4>
                    <div class="invoice-details-grid">
                        <div class="form-group">
                            <label for="vehicle-number">Vehicle Number</label>
                            <input type="text" id="vehicle-number" name="vehicle_number" placeholder="GJ-01-AB-1234">
                        </div>
                        <div class="form-group">
                            <label for="driver-name">Driver Name</label>
                            <input type="text" id="driver-name" name="driver_name">
                        </div>
                        <div class="form-group">
                            <label for="destination">Destination</label>
                            <input type="text" id="destination" name="destination">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Items Table -->
                <div class="table-container">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Unit Price</th>
                                <th class="tax-column">Tax (%)</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="documentItemsBody">
                            <!-- Rows added dynamically -->
                        </tbody>
                    </table>
                </div>

                <button type="button" class="add-item-button" data-action="add-item">+ Add Item</button>

                <!-- Summary Section -->
                <div class="summary-section">
                    <div class="summary-card">
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span id="subtotal">₹0.00</span>
                        </div>
                        <div class="summary-item tax-column">
                            <span>Total Tax</span>
                            <span id="totalTax">₹0.00</span>
                        </div>
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

    <script type="module" src="/Business%20project/assets/js/pages/create-document.js"></script>
</body>
</html>