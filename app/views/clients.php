<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Client Management</title>
</head>

<body>
    <!-- Sidebar -->
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="page-header">
            <div class="header-content">
                <h2>Client Management</h2>
                <div class="filter-section">
                    <input type="text" id="searchInput" placeholder="Search clients...">
                    <button class="add-client-btn" data-action="add-client">+ Add New Client</button>
                </div>
            </div>
        </div>

        <div class="client-card">
            <div class="table-container">
                <table class="client-table">
                    <thead>
                        <tr>
                            <th>Company Name</th>
                            <!-- <th>Contact Person</th> -->
                            <th>Email</th>
                            <!-- <th>Phone</th> -->
                            <th>GST Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clientTableBody">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
 

    </main>

    <!-- Add/Edit Client Modal -->
    <div class="modal-overlay" id="clientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Client</h3>
                <button class="close-button" data-action="close-modal" data-target="clientModal">&times;</button>
            </div>
            <form class="modal-form" id="clientForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="companyName">Company Name <span class="required">*</span></label>
                        <input type="text" id="companyName" name="companyName" required>
                    </div>
                    <div class="form-group">
                        <label for="contactPerson">Contact Person</label>
                        <input type="text" id="contactPerson" name="contactPerson">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="gstNumber">GST Number</label>
                        <input type="text" id="gstNumber" name="gstNumber" placeholder="e.g., 29ABCDE1234F1Z5" maxlength="15">
                    </div>
                    <div class="form-group">
                        <!-- Empty space for layout -->
                    </div>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Complete address..."></textarea>
                </div>
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" placeholder="Additional notes about this client..."></textarea>
                </div>
            </form>
            <div class="modal-buttons">
                <button type="button" class="modal-button cancel" data-action="close-modal" data-target="clientModal">Cancel</button>
                <button type="button" class="modal-button save" data-action="submit-client">Save Client</button>
            </div>
        </div>
    </div>

    <!-- View Client Modal -->
    <div class="modal-overlay" id="viewClientModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Client Details</h3>
                <button class="close-button" data-action="close-modal" data-target="viewClientModal">&times;</button>
            </div>

            <div class="client-detail-card">
                <div class="detail-row">
                    <span class="detail-label">Company Name:</span>
                    <span class="detail-value" id="viewCompanyName"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact Person:</span>
                    <span class="detail-value" id="viewContactPerson"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value" id="viewEmail"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value" id="viewPhone"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">GST Number:</span>
                    <span class="detail-value" id="viewGstNumber"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value" id="viewAddress"></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Notes:</span>
                    <span class="detail-value" id="viewNotes"></span>
                </div>
            </div>

            <div class="modal-buttons">
                <button type="button" class="modal-button save" data-action="edit-client">Edit Client</button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="success-message" id="successMessage">
        ✓ Operation successful!
    </div>
    <div class="error-message" id="errorMessage">
        ✗ Something went wrong!
    </div>
    <script type="module" src="/Business%20project/assets/js/pages/clients.js"></script>

</body>

</html>
