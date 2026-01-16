<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Create Document - GS Metal Concept</title>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h2>Create New Document</h2>
                <p>Choose the type of document you want to create</p>
            </div>
        </div>

        <!-- Document Type Cards -->
        <div class="document-cards-grid">
            
            <!-- Quotation Card -->
            <div class="document-card" data-type="quotation">
                <div class="card-icon quotation-icon">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12h6m-6 6h12m-12 6h12M21 12h-6"/>
                    </svg>
                </div>
                <h3>Quotation</h3>
                <p>Create a price estimate for your client. Can be converted to invoice later.</p>
                
                <div class="card-features">
                    <span class="feature-tag">✓ With GST calculation</span>
                    <span class="feature-tag">✓ Set validity period</span>
                    <span class="feature-tag">✓ Convert to invoice</span>
                </div>

                <button class="card-button" data-action="create-quotation">
                    Create Quotation →
                </button>
            </div>

            <!-- Tax Invoice (GST Bill) Card -->
            <div class="document-card" data-type="tax-invoice">
                <div class="card-icon tax-invoice-icon">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M3 9h18M9 3v18"/>
                    </svg>
                </div>
                <h3>Tax Invoice (GST Bill)</h3>
                <p>Create a full GST invoice with CGST/SGST/IGST, HSN codes, and all details.</p>
                
                <div class="card-features">
                    <span class="feature-tag">✓ Full GST compliance</span>
                    <span class="feature-tag">✓ HSN codes</span>
                    <span class="feature-tag">✓ Bank details</span>
                </div>

                <div class="card-options">
                    <label class="checkbox-label">
                        <input type="checkbox" id="fromQuotationGST">
                        <span>Create from existing quotation</span>
                    </label>
                </div>

                <button class="card-button" data-action="create-tax-invoice">
                    Create GST Invoice →
                </button>
            </div>

            <!-- Bill of Supply (No GST) Card -->
            <div class="document-card" data-type="bill-of-supply">
                <div class="card-icon bill-icon">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                </div>
                <h3>Bill of Supply (No GST)</h3>
                <p>Simple bill for clients without GST registration. Clean and straightforward.</p>
                
                <div class="card-features">
                    <span class="feature-tag">✓ No GST calculation</span>
                    <span class="feature-tag">✓ Simple format</span>
                    <span class="feature-tag">✓ Quick creation</span>
                </div>

                <div class="card-options">
                    <label class="checkbox-label">
                        <input type="checkbox" id="fromQuotationNoGST">
                        <span>Create from existing quotation</span>
                    </label>
                </div>

                <button class="card-button" data-action="create-bill-of-supply">
                    Create Simple Bill →
                </button>
            </div>

            <!-- Delivery Challan Card -->
            <div class="document-card" data-type="delivery-challan">
                <div class="card-icon challan-icon">
                    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 3h15v18H1z"/>
                        <path d="M16 8h5l3 3v6h-8V8z"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                </div>
                <h3>Delivery Challan</h3>
                <p>Create delivery challan for material/goods transportation tracking.</p>
                
                <div class="card-features">
                    <span class="feature-tag">✓ Track deliveries</span>
                    <span class="feature-tag">✓ Transport details</span>
                    <span class="feature-tag">✓ Material tracking</span>
                </div>

                <button class="card-button" data-action="create-delivery-challan">
                    Create Challan →
                </button>
            </div>

        </div>

        <!-- Quick Stats Section -->
        <div class="quick-stats">
            <h3>Recent Activity</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Quotations This Month</span>
                    <span class="stat-value" id="quotationsThisMonth">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Invoices This Month</span>
                    <span class="stat-value" id="invoicesThisMonth">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Pending Quotations</span>
                    <span class="stat-value" id="pendingQuotations">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Conversion Rate</span>
                    <span class="stat-value" id="conversionRate">0%</span>
                </div>
            </div>
        </div>

        <!-- Quotation Selection Modal (shown when checkbox is checked) -->
        <div class="modal-overlay" id="selectQuotationModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Select Quotation</h3>
                    <button class="close-button" data-action="close-modal" data-target="selectQuotationModal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <p class="modal-description">Choose an approved quotation to convert to invoice:</p>
                    
                    <div class="quotation-list" id="quotationList">
                        <div class="loading">Loading quotations...</div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Success/Error Messages -->
    <div class="success-message" id="successMessage"></div>
    <div class="error-message" id="errorMessage"></div>

    <script type="module" src="/Business%20project/assets/js/main.js"></script>
    <script type="module" src="/Business%20project/assets/js/pages/create-hub.js"></script>
</body>
</html>