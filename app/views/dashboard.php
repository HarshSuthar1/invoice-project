<!-- <!DOCTYPE html> -->
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Outstanding</span>
                </div>
                <div class="stat-value" id="totalOutstanding">₹0.00</div>
                <div class="stat-trend">
                    <span>Till Now</span>
                </div>
            </div> 

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Received</span>
                </div>
                <div class="stat-value" id="totalReceived">₹0.00</div>
                <div class="stat-trend">
                    <span>Till Now</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Unpaid Invoices</span>
                </div>
                <div class="stat-value" id="unpaidCount">0</div>
                <div class="stat-trend">
                    <span>Till Now</span>
                </div>
            </div>
        </div>

        <div class="invoice-sections">
            <div class="section-card">
                <div class="section-header">
                    <h3>Recent Invoices</h3>
                </div>
                <div class="section-content">
                    <table class="invoice-table" id="recentInvoicesTable">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>Payment Status</h3>
                </div>
                <div class="section-content">
                    <canvas id="paymentStatusChart"></canvas>
                </div>
            </div>
        </div>
    </main>
    <script type="module" src="/Business%20project/assets/js/main.js"></script>
    <script type="module" src="/Business%20project/assets/js/pages/dashboard.js"></script>

</body>

</html>