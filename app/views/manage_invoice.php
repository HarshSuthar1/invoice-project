<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css?v=20260328c">
    <title>Manage Documents</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        /* Page Header */
        .page-header {
            margin-bottom: 32px;
        }

        .page-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .page-title-section h2 {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .page-title-section p {
            font-size: 16px;
            color: #6b7280;
        }

        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 32px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: all 200ms ease;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-bar input[type="search"] {
            flex: 1;
            min-width: 250px;
        }

        .filter-bar select {
            min-width: 150px;
        }

        /* Document Cards Grid */
        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        /* Individual Document Card */
        .document-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .document-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 300ms ease;
        }

        .document-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .document-card:hover::before {
            transform: scaleX(1);
        }

        /* Card Header */
        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .document-number {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .document-type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .type-quotation {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-invoice {
            background: #d1fae5;
            color: #065f46;
        }

        .type-bill {
            background: #fef3c7;
            color: #92400e;
        }

        .type-challan {
            background: #e0e7ff;
            color: #4338ca;
        }

        /* Card Body */
        .card-body {
            margin-bottom: 20px;
        }

        .card-info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #6b7280;
        }

        .card-info-row svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .card-info-row strong {
            color: #111827;
        }

        .client-name {
            font-weight: 600;
            color: #111827;
        }

        /* Status & Amount Row */
        .status-amount-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .status-unpaid {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-partially-paid {
            background: #fef3c7;
            color: #78350f;
        }

        .document-amount {
            font-size: 20px;
            font-weight: 700;
            color: #059669;
        }

        /* Card Actions */
        .card-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .card-actions .btn {
            flex: 1;
            min-width: 70px;
            justify-content: center;
            font-size: 12px;
            padding: 8px 12px;
        }

        /* Loading & Empty States */
        .loading-state,
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #6b7280;
        }

        .loading-state {
            font-size: 16px;
        }

        .empty-state {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .empty-state h3 {
            font-size: 20px;
            color: #111827;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 24px;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: #111827;
        }

        .close-button {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .close-button:hover {
            background-color: #f3f4f6;
            color: #374151;
        }

        .invoice-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .detail-group {
            background-color: #f9fafb;
            padding: 16px;
            border-radius: 6px;
        }

        .detail-group label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
            display: block;
        }

        .detail-group .value {
            font-size: 16px;
            color: #111827;
            font-weight: 500;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .items-table th {
            background-color: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .summary-section {
            background-color: #f9fafb;
            padding: 16px;
            border-radius: 6px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 18px;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            margin-top: 8px;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        /* Toast Messages */
        .success-message,
        .error-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            z-index: 1001;
            transform: translateX(100%);
            transition: transform 300ms ease;
        }

        .success-message {
            background: #10b981;
            color: white;
        }

        .error-message {
            background: #dc2626;
            color: white;
        }

        .success-message.show,
        .error-message.show {
            transform: translateX(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .documents-grid {
                grid-template-columns: 1fr;
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-bar input,
            .filter-bar select {
                width: 100%;
            }

            .card-actions {
                flex-direction: column;
            }

            .card-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title-section">
                    <h2>Manage Documents</h2>
                    <p>View, edit, and manage all your business documents</p>
                </div>
                <button class="btn btn-view" onclick="window.location.href='/Business%20project/public/index.php?page=create-hub'">
                    ➕ Create New Document
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <input type="search" id="searchInput" placeholder="🔍 Search by number, client name...">
            <select id="typeFilter">
                <option value="">All Types</option>
                <option value="quotation">Quotations</option>
                <option value="invoice">Invoices (GST)</option>
                <option value="bill-no-gst">Bills (No GST)</option>
                <option value="challan">Challans</option>
            </select>
            <select id="statusFilter">
                <option value="">All Statuses</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
                <option value="partially paid">Partially Paid</option>
            </select>
            <select id="dateFilter">
                <option value="">All Dates</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
            </select>
        </div>

        <!-- Documents Grid -->
        <div id="documentsContainer">
            <div class="loading-state">
                📄 Loading documents...
            </div>
        </div>
    </main>

    <!-- View Document Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Document Details</h3>
                <button class="close-button" data-action="close-modal" data-target="viewModal">&times;</button>
            </div>

            <div class="invoice-details">
                <div class="detail-group">
                    <label>Document Number</label>
                    <div class="value" id="viewDocumentNumber"></div>
                </div>
                <div class="detail-group">
                    <label>Client</label>
                    <div class="value" id="viewClientName"></div>
                </div>
                <div class="detail-group">
                    <label>Date</label>
                    <div class="value" id="viewDocumentDate"></div>
                </div>
                <div class="detail-group">
                    <label>Status</label>
                    <div class="value" id="viewStatus"></div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="viewItemsTable">
                    <!-- Populated dynamically -->
                </tbody>
            </table>

            <div class="summary-section">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="viewSubtotal"></span>
                </div>
                <div class="summary-row">
                    <span>Tax:</span>
                    <span id="viewTax"></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="viewTotal"></span>
                </div>
            </div>

            <div class="modal-buttons">
                <button type="button" class="btn btn-view" data-action="download-invoice">📥 Download PDF</button>
                <button type="button" class="btn btn-edit" data-action="edit-from-view">✏️ Edit</button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="success-message" id="successMessage">✓ Success!</div>
    <div class="error-message" id="errorMessage">✗ Error occurred!</div>

    <script type="module" src="/Business%20project/assets/js/main.js"></script>
    <script type="module" src="/Business%20project/assets/js/pages/manage_documents_cards.js"></script>
</body>

</html>
