<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Invoice Management</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="page-header">
            <h2>Manage Invoices</h2>
        </div>

        <div id="errorContainer"></div>
        <div id="loadingIndicator" class="loading">Loading invoices...</div>

        <div class="header-content">
            <div class="filter-section">
                <input type="text" id="searchInput" placeholder="Search invoices...">
                <select id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partially paid">Partially Paid</option>
                </select>
                <select id="dateFilter">
                    <option value="">All Dates</option>
                    <option value="today">Due Today</option>
                    <option value="week">Due This Week</option>
                    <option value="month">Due This Month</option>
                </select>
            </div>
            <button class="new-invoice-btn" data-action="new-invoice">+ New Invoice</button>
        </div>

        <div class="invoice-card" id="invoiceCard" style="display: none;">

            <div class="table-container">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- View Invoice Modal -->
    <div class="modal-overlay" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Invoice Details</h3>
                <button class="close-button" data-action="close-modal" data-target="viewModal">&times;</button>
            </div>

            <div class="invoice-details">
                <div class="detail-group">
                    <label>Invoice Number</label>
                    <div class="value" id="viewInvoiceNumber"></div>
                </div>
                <div class="detail-group">
                    <label>Client</label>
                    <div class="value" id="viewClientName"></div>
                </div>
                <div class="detail-group">
                    <label>Invoice Date</label>
                    <div class="value" id="viewInvoiceDate"></div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Tax %</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody id="viewItemsTable">
                    <!-- Dynamic content -->
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
                <button type="button" class="modal-button download" data-action="download-invoice">Download PDF</button>
            </div>
        </div>
    </div>

    <!-- Edit Invoice Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Invoice</h3>
                <button class="close-button" data-action="close-modal" data-target="editModal">&times;</button>
            </div>

            <form id="editInvoiceForm">
                <div class="invoice-details">
                    <div class="form-group">
                        <label for="editInvoiceNumber">Invoice Number</label>
                        <input type="text" id="editInvoiceNumber" readonly>
                        <input type="hidden" id="editInvoiceId">
                    </div>
                    <div class="form-group">
                        <label for="editClient">Client</label>
                        <select id="editClient">
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editInvoiceDate">Invoice Date</label>
                        <input type="date" id="editInvoiceDate">
                    </div>
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select id="editStatus">
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                            <option value="partially paid">Partially Paid</option>
                        </select>
                    </div>
                    <div class="form-group" id="amountReceivedGroup" style="display: none;">
                        <label for="editAmountReceived">Amount Received</label>
                        <input type="number" id="editAmountReceived" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Tax %</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="editItemsTable">
                        <!-- Dynamic content -->
                    </tbody>
                </table>

                <button type="button" class="btn btn-view" data-action="add-edit-item" style="margin-bottom: 20px;">Add Item</button>

                <div class="summary-section">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="editSubtotal">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span id="editTax">$0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="editTotal">$0.00</span>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="modal-button cancel" data-action="close-modal" data-target="editModal">Cancel</button>
                    <button type="submit" class="modal-button save" id="saveButton">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script type="module" src="/Business%20project/assets/js/main.js"></script>
    <script type="module" src="/Business%20project/assets/js/pages/manage_invoice.js"></script>


</body>

</html>