<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css?v=20260420a">
    <title>Customer Ledger</title>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content ledger-page">
        <header class="ledger-header">
            <div>
                <p class="eyebrow">Collections Workspace</p>
                <h1>Customer Ledger & Payment Tracking</h1>
                <p class="ledger-subtitle">Track running balances, advances, partial payments, and invoice allocations from one place.</p>
            </div>

            <div class="ledger-actions">
                <button class="btn btn-primary" data-open-transaction="payment">Record Payment</button>
                <button class="btn" data-open-transaction="advance">Record Advance</button>
                <button class="btn" data-open-transaction="adjustment_credit">Add Credit Note</button>
                <button class="btn" data-open-apply-advance>Apply Advance</button>
            </div>
        </header>

        <section class="ledger-toolbar">
            <div class="toolbar-field">
                <label for="clientSelect">Client</label>
                <select id="clientSelect">
                    <option value="">Select a client</option>
                </select>
            </div>

            <div class="toolbar-field">
                <label for="fromDate">From</label>
                <input type="date" id="fromDate">
            </div>

            <div class="toolbar-field">
                <label for="toDate">To</label>
                <input type="date" id="toDate">
            </div>

            <div class="toolbar-buttons">
                <button class="btn" id="applyFiltersBtn">Refresh Ledger</button>
                <a class="btn btn-outline" id="statementLink" href="#" target="_blank" rel="noopener">Outstanding Statement</a>
            </div>
        </section>

        <section class="kpi-grid ledger-kpis">
            <article class="kpi-card">
                <div class="kpi-label">Opening Balance</div>
                <div class="kpi-value" id="openingBalance">₹0.00</div>
                <div class="kpi-subtitle">Balance before selected range</div>
            </article>

            <article class="kpi-card success">
                <div class="kpi-label">Current Outstanding</div>
                <div class="kpi-value" id="currentOutstanding">₹0.00</div>
                <div class="kpi-subtitle" id="openInvoiceCount">0 open invoices</div>
            </article>

            <article class="kpi-card warning">
                <div class="kpi-label">Unapplied Advance</div>
                <div class="kpi-value" id="unappliedAdvance">₹0.00</div>
                <div class="kpi-subtitle">Available for future invoice adjustment</div>
            </article>

            <article class="kpi-card danger">
                <div class="kpi-label">Net Balance</div>
                <div class="kpi-value" id="currentBalance">₹0.00</div>
                <div class="kpi-subtitle" id="overdueAmount">₹0.00 overdue</div>
            </article>
        </section>

        <section class="section-grid ledger-main-grid">
            <article class="section ledger-section">
                <div class="section-header">
                    <h3>Running Ledger</h3>
                </div>
                <div class="section-content">
                    <div class="ledger-meta" id="ledgerMeta">Choose a client to load the ledger.</div>
                    <div class="table-responsive">
                        <table class="ledger-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Entry</th>
                                    <th>Reference</th>
                                    <th>Mode</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody id="ledgerTableBody">
                                <tr>
                                    <td colspan="7" class="loading">Select a client to view ledger entries.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>

            <aside class="ledger-side-panels">
                <article class="section">
                    <div class="section-header">
                        <h3>Open Invoices</h3>
                    </div>
                    <div class="section-content panel-list" id="openInvoicesList">
                        <div class="loading">No client selected.</div>
                    </div>
                </article>

                <article class="section">
                    <div class="section-header">
                        <h3>Available Advances</h3>
                    </div>
                    <div class="section-content panel-list" id="availableAdvancesList">
                        <div class="loading">No client selected.</div>
                    </div>
                </article>
            </aside>
        </section>

        <section class="section ledger-overview-section">
            <div class="section-header">
                <h3>Client Balance Overview</h3>
            </div>
            <div class="section-content">
                <div class="table-responsive">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Open Invoices</th>
                                <th>Outstanding</th>
                                <th>Advance Credit</th>
                                <th>Net Balance</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="overviewTableBody">
                            <tr>
                                <td colspan="6" class="loading">Loading client balances...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="transactionModal">
        <div class="modal-content ledger-modal">
            <div class="modal-header">
                <h3 id="transactionModalTitle">Record Ledger Entry</h3>
                <button class="close-button" data-action="close-modal" data-target="transactionModal">&times;</button>
            </div>

            <form id="transactionForm" class="modal-form">
                <input type="hidden" id="transactionType">

                <div class="form-row">
                    <div class="form-group">
                        <label for="transactionDate">Date</label>
                        <input type="date" id="transactionDate" required>
                    </div>

                    <div class="form-group">
                        <label for="transactionAmount">Amount</label>
                        <input type="number" id="transactionAmount" min="0.01" step="0.01" required>
                    </div>
                </div>

                <div class="form-row transaction-payment-fields">
                    <div class="form-group">
                        <label for="paymentMode">Payment Mode</label>
                        <select id="paymentMode">
                            <option value="">Select mode</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="UPI">UPI</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="referenceNo">Reference No.</label>
                        <input type="text" id="referenceNo" placeholder="UTR / cheque / receipt number">
                    </div>
                </div>

                <div class="form-group allocation-group is-hidden" id="paymentAllocationsGroup">
                    <div class="allocation-header">
                        <label>Invoice Allocations</label>
                        <span id="allocationSummary">Allocated: ₹0.00</span>
                    </div>
                    <div id="paymentAllocationsList" class="allocation-list"></div>
                </div>

                <div class="form-group">
                    <label for="transactionNotes">Notes</label>
                    <textarea id="transactionNotes" placeholder="Optional remarks for this entry"></textarea>
                </div>
            </form>

            <div class="modal-buttons">
                <button type="button" class="modal-button cancel" data-action="close-modal" data-target="transactionModal">Cancel</button>
                <button type="button" class="modal-button save" id="saveTransactionBtn">Save Entry</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="applyAdvanceModal">
        <div class="modal-content ledger-modal">
            <div class="modal-header">
                <h3>Apply Existing Advance</h3>
                <button class="close-button" data-action="close-modal" data-target="applyAdvanceModal">&times;</button>
            </div>

            <form id="applyAdvanceForm" class="modal-form">
                <div class="form-group">
                    <label for="advanceSource">Advance Entry</label>
                    <select id="advanceSource"></select>
                </div>

                <div class="form-group allocation-group">
                    <div class="allocation-header">
                        <label>Allocate To Invoices</label>
                        <span id="applyAdvanceSummary">Allocated: ₹0.00</span>
                    </div>
                    <div id="applyAdvanceAllocationsList" class="allocation-list"></div>
                </div>
            </form>

            <div class="modal-buttons">
                <button type="button" class="modal-button cancel" data-action="close-modal" data-target="applyAdvanceModal">Cancel</button>
                <button type="button" class="modal-button save" id="applyAdvanceBtn">Apply Advance</button>
            </div>
        </div>
    </div>

    <div class="success-message" id="successMessage">Operation successful.</div>
    <div class="error-message" id="errorMessage">Something went wrong.</div>

    <script type="module" src="/Business%20project/assets/js/main.js"></script>
    <script type="module" src="/Business%20project/assets/js/pages/ledger.js"></script>
</body>

</html>
