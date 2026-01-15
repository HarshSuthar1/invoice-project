<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Company Profile Settings</title>
</head>

<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="page-header">
            <h2>Company Profile Settings</h2>
            <p>Manage your company information that appears on invoices and documents</p>
        </div>

        <div class="settings-container">
            <!-- Company Information Card -->
            <div class="settings-card">
                <div class="card-header">
                    <h3>Company Information</h3>
                    <p>Basic details about your company</p>
                </div>
                <div class="card-content">
                    <form id="profileForm" class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company_name">Company Name <span class="required">*</span></label>
                                <input type="text" id="company_name" name="company_name" required>
                                <div class="help-text">This appears on all invoices and documents</div>
                            </div>
                            <div class="form-group">
                                <label for="gst_number">GST Number</label>
                                <input type="text" id="gst_number" name="gst_number" placeholder="e.g., 29ABCDE1234F1Z5" maxlength="15">
                                <div class="help-text">Your Goods and Services Tax registration number</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email">
                                <div class="help-text">Primary business email address</div>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone">
                                <div class="help-text">Main contact number</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="website">Website URL</label>
                                <input type="url" id="website" name="website" placeholder="https://www.example.com">
                                <div class="help-text">Your company website (optional)</div>
                            </div>
                            <div class="form-group">
                                <label for="pan_number">PAN Number</label>
                                <input type="text" id="pan_number" name="pan_number" placeholder="e.g., ABCTY1234D" maxlength="10">
                                <div class="help-text">Permanent Account Number (optional)</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Business Address</label>
                            <textarea id="address" name="address" placeholder="Enter your complete business address..."></textarea>
                            <div class="help-text">Full address including city, state, and postal code</div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bank Details Card -->
            <div class="settings-card">
                <div class="card-header">
                    <h3>Banking Information</h3>
                    <p>Bank details for payment instructions on invoices</p>
                </div>
                <div class="card-content">
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bank_name">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name">
                                <div class="help-text">Name of your bank</div>
                            </div>
                            <div class="form-group">
                                <label for="account_number">Account Number</label>
                                <input type="text" id="account_number" name="account_number">
                                <div class="help-text">Your bank account number</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="ifsc_code">IFSC Code</label>
                                <input type="text" id="ifsc_code" name="ifsc_code" placeholder="e.g., SBIN0001234" maxlength="11">
                                <div class="help-text">Indian Financial System Code</div>
                            </div>
                            <div class="form-group">
                                <label for="account_holder_name">Account Holder Name</label>
                                <input type="text" id="account_holder_name" name="account_holder_name">
                                <div class="help-text">Name as per bank account</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="branch_name">Branch Name</label>
                                <input type="text" id="branch_name" name="branch_name">
                                <div class="help-text">Bank branch location (optional)</div>
                            </div>
                            <div class="form-group">
                                <label for="swift_code">SWIFT Code</label>
                                <input type="text" id="swift_code" name="swift_code" placeholder="e.g., SBININBB123">
                                <div class="help-text">For international payments (optional)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Preferences Card -->
            <div class="settings-card">
                <div class="card-header">
                    <h3>Invoice Preferences</h3>
                    <p>Default settings for your invoices</p>
                </div>
                <div class="card-content">
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="invoice_prefix">Invoice Number Prefix</label>
                                <input type="text" id="invoice_prefix" name="invoice_prefix" placeholder="e.g., INV-" maxlength="10">
                                <div class="help-text">Prefix for invoice numbers (optional)</div>
                            </div>
                            <div class="form-group">
                                <label for="default_due_days">Default Due Days</label>
                                <input type="number" id="default_due_days" name="default_due_days" value="30" min="1" max="365">
                                <div class="help-text">Default number of days for payment</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="invoice_terms">Default Terms & Conditions</label>
                            <textarea id="invoice_terms" name="invoice_terms" placeholder="Enter default terms and conditions for invoices..."></textarea>
                            <div class="help-text">These will appear on all invoices by default</div>
                        </div>

                        <div class="form-group">
                            <label for="payment_instructions">Payment Instructions</label>
                            <textarea id="payment_instructions" name="payment_instructions" placeholder="Enter payment instructions for clients..."></textarea>
                            <div class="help-text">Instructions on how clients should make payments</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="settings-card">
                <div class="card-header">
                    <h3>Preview</h3>
                    <p>How your company information will appear on invoices</p>
                </div>
                <div class="card-content">
                    <div class="preview-content" id="companyPreview">
                        <div class="company-preview">Your Company Name</div>
                        <div class="company-details">
                            <div>Email: info@company.com</div>
                            <div>Phone: (555) 123-4567</div>
                            <div>GST: 29ABCDE1234F1Z5</div>
                            <div>123 Business Street, City, State 12345</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Section -->
            <div class="save-section">
                <div class="last-updated">
                    Last updated: <span id="lastUpdated">Never</span>
                </div>
                <button type="button" class="save-button" data-action="save-profile">
                    💾 Save Changes
                </button>
            </div>
        </div>
    </main>

    <!-- Success/Error Messages -->
    <div class="success-message" id="successMessage">
        ✓ Settings saved successfully!
    </div>
    <div class="error-message" id="errorMessage">
        ✗ Failed to save settings!
    </div>


    <script type="module" src="/Business%20project/assets/js/pages/profile.js"></script>
</body>

</html>