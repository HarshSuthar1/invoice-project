<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Invoice Application</title>
</head>

<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="main-content">
<div class="invoice-card"><form id="invoiceForm">
            <h2>New Invoice</h2>

            <!-- Invoice Details Form -->
            <div class="invoice-details-grid">
                <div class="form-group">
                    <label for="clientSelect">Client</label>
                    <select id="clientSelect" name="client_id">
                        <option value="">Loading clients...</option>
                    </select>
                </div> 
                <div class="form-group">
                    <label for="invoice-date">Invoice Date</label>
                    <input type="date" id="invoice-date" name="invoice_date">
                </div>
                <div class="form-group">
                    <label for="invoice-number">Invoice #</label>
                    <input type="text" id="invoice-number" name="invoice_number" readonly>
                </div>
            </div>

            <!-- Invoice Items Table -->
            <div class="table-container">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Tax (%)</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceItemsBody">
                        <!-- Rows will be added dynamically -->
                    </tbody>
                </table>
            </div>

            <button class="add-item-button" data-action="add-item">Add Item</button>

            <!-- Summary Section -->
            <div class="summary-section">
                <div class="summary-card">
                    <div class="summary-item">
                        <span>Subtotal</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="summary-item">
                        <span>Total Tax</span>
                        <span id="totalTax">$0.00</span>
                    </div>
                    <div class="summary-item">
                        <span>Discount</span>
                        <span id="discount">$0.00</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span id="grandTotal">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="create-invoice-button-container">
                <button class="create-invoice-button" data-action="save-invoice" id="createInvoiceBtn">Create Invoice</button>
            </div>
        </form></div>
    </main>

    <!-- Add Client Modal -->
    <div class="modal-overlay" id="clientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Client</h3>
                <button class="close-button" data-action="close-modal" data-target="clientModal">&times;</button>
            </div>
            <form class="modal-form" id="clientForm">
                <div class="form-group">
                    <label for="company_name">Company Name <span class="required">*</span></label>
                    <input type="text" id="company_name" required>
                </div>
                <div class="form-group">
                    <label for="contact_person">Contact Person</label>
                    <input type="text" id="contact_person">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email">
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone">
                </div>
                <div class="form-group">
                    <label for="gst_number">GST Number</label>
                    <input type="text" id="gst_number" placeholder="e.g., 29ABCDE1234F1Z5" maxlength="15">
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" placeholder="Full address..."></textarea>
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" placeholder="Additional notes about this client..."></textarea>
                </div>
            </form>
            <div class="modal-buttons">
                <button type="button" class="modal-button cancel" data-action="close-modal" data-target="clientModal">Cancel</button>
                <button type="button" class="modal-button save" data-action="submit-client" id="saveClientBtn">Save Client</button>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div class="success-message" id="successMessage">
        ✓ Success!
    </div>

    <!-- Error Message -->
    <div class="error-message" id="errorMessage">
        ✗ Error occurred!
    </div>

    <script type="module" src="/Business%20project/assets/js/main.js"></script>
    <script type="module" src="/Business%20project/assets/js/pages/invoice.js"></script>

</body>

</html>