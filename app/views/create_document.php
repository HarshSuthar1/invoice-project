<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title id="pageTitle">Create Document - GS Metal Concept</title>
</head>

<body>
    <?php 
    include __DIR__ . '/../../includes/sidebar.php';
    
    // Get document type from URL
    $docType = $_GET['type'] ?? 'quotation';
    $fromQuotationId = $_GET['from_quotation'] ?? null;
    $editId = $_GET['id'] ?? null;
    ?>

    <main class="main-content">
        <!-- Document Type Header (Dynamic) -->
        <div class="page-header">
            <div>
                <h2 id="documentTypeTitle">Create Quotation</h2>
                <p id="documentTypeDescription">Fill in the details below</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="window.history.back()">
                    ← Back
                </button>
                <button class="btn btn-primary" data-action="save-document" id="saveDocumentBtn">
                    Save & Generate
                </button>
            </div>
        </div>

        <!-- Document Form Card -->
        <div class="document-form-card">
            <form id="documentForm">
                <input type="hidden" id="documentType" name="document_type" value="<?php echo htmlspecialchars($docType); ?>">
                <input type="hidden" id="fromQuotationId" value="<?php echo htmlspecialchars($fromQuotationId ?? ''); ?>">
                <input type="hidden" id="editId" value="<?php echo htmlspecialchars($editId ?? ''); ?>">

                <!-- Basic Information Section -->
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    
                    <div class="form-grid">
                        <!-- Client Selection -->
                        <div class="form-group">
                            <label for="clientSelect">Client <span class="required">*</span></label>
                            <select id="clientSelect" name="client_id" required>
                                <option value="">Select client...</option>
                            </select>
                        </div>

                        <!-- Document Number (Auto-generated) -->
                        <div class="form-group">
                            <label for="documentNumber">Document Number</label>
                            <input type="text" id="documentNumber" name="document_number" readonly>
                        </div>

                        <!-- Document Date -->
                        <div class="form-group">
                            <label for="documentDate">Date <span class="required">*</span></label>
                            <input type="date" id="documentDate" name="document_date" required>
                        </div>

                        <!-- Valid Until (Quotation only) -->
                        <div class="form-group" id="validUntilGroup" style="display: none;">
                            <label for="validUntil">Valid Until <span class="required">*</span></label>
                            <input type="date" id="validUntil" name="valid_until">
                        </div>

                        <!-- Due Date (Invoice only) -->
                        <div class="form-group" id="dueDateGroup" style="display: none;">
                            <label for="dueDate">Due Date</label>
                            <input type="date" id="dueDate" name="due_date">
                        </div>

                        <!-- Place of Supply (Tax Invoice only) -->
                        <div class="form-group" id="placeOfSupplyGroup" style="display: none;">
                            <label for="placeOfSupply">Place of Supply</label>
                            <input type="text" id="placeOfSupply" name="place_of_supply" placeholder="Gujarat">
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="form-section">
                    <div class="section-header-with-action">
                        <h3 class="section-title">Items / Services</h3>
                        <button type="button" class="btn btn-secondary" data-action="add-item">
                            + Add Item
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="items-table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Description</th>
                                    <th id="hsnHeader" style="width: 100px;">HSN Code</th>
                                    <th style="width: 80px;">Qty</th>
                                    <th style="width: 80px;">Unit</th>
                                    <th style="width: 100px;">Rate</th>
                                    <th id="taxHeader" style="width: 80px;">GST %</th>
                                    <th style="width: 120px;">Amount</th>
                                    <th style="width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Items will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="form-section">
                    <h3 class="section-title">Summary</h3>
                    
                    <div class="summary-container">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="subtotalDisplay">₹0.00</span>
                        </div>
                        <div class="summary-row" id="taxRow">
                            <span id="taxLabel">Tax:</span>
                            <span id="taxDisplay">₹0.00</span>
                        </div>
                        <div class="summary-row" id="cgstRow" style="display: none;">
                            <span>CGST:</span>
                            <span id="cgstDisplay">₹0.00</span>
                        </div>
                        <div class="summary-row" id="sgstRow" style="display: none;">
                            <span>SGST:</span>
                            <span id="sgstDisplay">₹0.00</span>
                        </div>
                        <div class="summary-row" id="igstRow" style="display: none;">
                            <span>IGST:</span>
                            <span id="igstDisplay">₹0.00</span>
                        </div>
                        <div class="summary-row total-row">
                            <span>Grand Total:</span>
                            <span id="grandTotalDisplay">₹0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="form-section">
                    <h3 class="section-title">Additional Information</h3>
                    
                    <div class="form-group">
                        <label for="notes">Notes / Special Instructions</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any special instructions or notes..."></textarea>
                    </div>

                    <div class="form-group" id="termsGroup">
                        <label for="terms">Terms & Conditions</label>
                        <textarea id="terms" name="terms" rows="3" placeholder="Payment terms and conditions..."></textarea>
                    </div>
                </div>

            </form>
        </div>

        <!-- Action Buttons Footer -->
        <div class="form-footer">
            <button class="btn btn-secondary" onclick="window.history.back()">
                Cancel
            </button>
            <button class="btn btn-secondary" data-action="save-draft">
                Save as Draft
            </button>
            <button class="btn btn-primary" data-action="save-and-generate">
                Save & Generate PDF
            </button>
        </div>

    </main>

    <!-- HSN Code Selection Modal -->
    <div class="modal-overlay" id="hsnModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select HSN Code</h3>
                <button class="close-button" data-action="close-modal" data-target="hsnModal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" id="hsnSearch" placeholder="Search HSN codes..." class="search-input">
                <div class="hsn-list" id="hsnList">
                    <!-- HSN codes will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="success-message" id="successMessage"></div>
    <div class="error-message" id="errorMessage"></div>

    <script type="module" src="/Business%20project/assets/js/main.js"></script>
    <script type="module" src="/Business%20project/assets/js/pages/create-document.js"></script>
</body>
</html>