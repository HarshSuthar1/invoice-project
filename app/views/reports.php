<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
  <title>Reports - Invoice Management</title>
</head>

<body>
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main-content">
    <header class="header">
      <h1>Business Reports</h1>
      <p>Key metrics and insights into your invoice performance</p>
    </header>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card success">
        <div class="kpi-label">Total Revenue Invoiced</div>
        <div class="kpi-value" id="totalInvoiced">₹0</div>
        <div class="kpi-subtitle">All invoices</div>
      </div>

      <div class="kpi-card success">
        <div class="kpi-label">Total Amount Received</div>
        <div class="kpi-value" id="totalReceived">₹0</div>
        <div class="kpi-subtitle" id="collectionRate">0% collected</div>
      </div>

      <div class="kpi-card danger">
        <div class="kpi-label">Total Outstanding</div>
        <div class="kpi-value" id="totalOutstanding">₹0</div>
        <div class="kpi-subtitle">Amount still owed</div>
      </div>

      <div class="kpi-card warning">
        <div class="kpi-label">Days Sales Outstanding</div>
        <div class="kpi-value" id="daysOutstanding">0</div>
        <div class="kpi-subtitle">Days to collect payment</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">Active Clients</div>
        <div class="kpi-value" id="activeClients">0</div>
        <div class="kpi-subtitle">With invoices</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-label">Total Invoices</div>
        <div class="kpi-value" id="totalInvoices">0</div>
        <div class="kpi-subtitle">All time</div>
      </div>
    </div>

    <!-- Revenue Trend & Top Clients -->
    <div class="section-grid">
      <div class="section">
        <div class="section-header">
          <h3>Monthly Revenue Trend</h3>
        </div>
        <div class="section-content">
          <div class="chart-container" id="revenueChart">
            <div class="loading">Loading chart data...</div>
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-header">
          <h3>Top Paying Clients</h3>
        </div>
        <div class="section-content" id="topClients">
          <div class="loading">Loading client data...</div>
        </div>
      </div>
    </div>

    <!-- Payment Performance & Outstanding by Client -->
    <div class="section-grid">
      <div class="section">
        <div class="section-header">
          <h3>Client Payment Performance</h3>
        </div>
        <div class="section-content">
          <div class="table-responsive">
            <table id="paymentPerformanceTable">
              <thead>
                <tr>
                  <th>Client</th>
                  <th>Avg Days to Pay</th>
                  <th>On-Time Rate</th>
                  <th>Total Invoices</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="4" class="loading">Loading...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="section">
        <div class="section-header">
          <h3>Outstanding by Client</h3>
        </div>
        <div class="section-content" id="outstandingByClient">
          <div class="loading">Loading...</div>
        </div>
      </div>
    </div>

    <!-- Aging Report -->
    <div class="section-grid">
      <div class="section aging-report">
        <div class="section-header">
          <h3>Aging Report - Outstanding Invoices</h3>
        </div>
        <div class="section-content">
          <div class="aging-grid" id="agingReport">
            <div class="aging-card current">
              <h4>Current (0-30 Days)</h4>
              <div class="amount">₹0</div>
              <div class="count">0 invoices</div>
            </div>
            <div class="aging-card days-30">
              <h4>31-60 Days</h4>
              <div class="amount">₹0</div>
              <div class="count">0 invoices</div>
            </div>
            <div class="aging-card days-60">
              <h4>61-90 Days</h4>
              <div class="amount">₹0</div>
              <div class="count">0 invoices</div>
            </div>
            <div class="aging-card days-90">
              <h4>90+ Days</h4>
              <div class="amount">₹0</div>
              <div class="count">0 invoices</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script type="module" src="/Business%20project/assets/js/pages/reports.js"></script>
</body>

</html>