/*
reports.js - Comprehensive Business Reports
Displays KPIs, revenue trends, client performance, and aging reports
*/

import { qs } from '../core/dom.js';
import { formatCurrency } from '../core/utils.js';
import { showError } from '../core/ui.js';
import { apiFetch } from '../core/api.js';

/* -----------------------------
   Load all report data
------------------------------ */
const loadReports = async () => {
  try {
    console.log('[reports.js] Fetching report data...');
    
    const data = await apiFetch('/api/reports/get_reports_data.php');
    
    console.log('[reports.js] Data received:', data);

    // Render all sections
    renderKPIs(data.kpis || {});
    renderRevenueTrend(data.revenue_trend || []);
    renderTopClients(data.top_clients || []);
    renderPaymentPerformance(data.payment_performance || []);
    renderOutstandingByClient(data.outstanding_by_client || []);
    renderAgingReport(data.aging_report || {});

  } catch (err) {
    console.error('[reports.js] Error:', err);
    showError(err.message || 'Failed to load reports');
    
    // Show error in all sections
    showLoadingError();
  }
};

/* -----------------------------
   Show error state
------------------------------ */
const showLoadingError = () => {
  const errorMsg = '<div class="error">Failed to load report data. Please refresh the page.</div>';
  
  qs('#revenueChart').innerHTML = errorMsg;
  qs('#topClients').innerHTML = errorMsg;
  qs('#paymentPerformanceTable tbody').innerHTML = `<tr><td colspan="4">${errorMsg}</td></tr>`;
  qs('#outstandingByClient').innerHTML = errorMsg;
};

/* -----------------------------
   Render KPIs (top cards)
------------------------------ */
const renderKPIs = (kpis) => {
  console.log('[reports.js] Rendering KPIs:', kpis);
  
  qs('#totalInvoiced').textContent = formatCurrency(kpis.total_invoiced || 0);
  qs('#totalReceived').textContent = formatCurrency(kpis.total_received || 0);
  qs('#totalOutstanding').textContent = formatCurrency(kpis.total_outstanding || 0);
  qs('#collectionRate').textContent = `${kpis.collection_rate || 0}% collected`;
  qs('#daysOutstanding').textContent = kpis.days_outstanding || 0;
  qs('#activeClients').textContent = kpis.active_clients || 0;
  qs('#totalInvoices').textContent = kpis.total_invoices || 0;
};

/* -----------------------------
   Render Revenue Trend Chart
------------------------------ */
const renderRevenueTrend = (data) => {
  console.log('[reports.js] Rendering revenue trend:', data);
  
  const container = qs('#revenueChart');
  
  if (!data || data.length === 0) {
    container.innerHTML = '<div class="loading">No revenue data available</div>';
    return;
  }

  // Calculate max value for scaling
  const maxRevenue = Math.max(...data.map(d => d.revenue));
  const chartHeight = 250;

  // Build simple bar chart
  let html = '<div class="chart-container">';
  
  data.forEach(item => {
    const barHeight = maxRevenue > 0 ? (item.revenue / maxRevenue) * chartHeight : 0;
    html += `
      <div class="chart-bar" style="height: ${barHeight}px;" title="${item.month}: ${formatCurrency(item.revenue)}">
        <span class="value">${formatCurrency(item.revenue)}</span>
        <span class="label">${item.month}</span>
      </div>
    `;
  });
  
  html += '</div>';
  container.innerHTML = html;
};

/* -----------------------------
   Render Top Clients List
------------------------------ */
const renderTopClients = (clients) => {
  console.log('[reports.js] Rendering top clients:', clients);
  
  const container = qs('#topClients');
  
  if (!clients || clients.length === 0) {
    container.innerHTML = '<div class="loading">No client data available</div>';
    return;
  }

  let html = '';
  clients.forEach((client, index) => {
    html += `
      <div class="client-item">
        <div class="client-info">
          <h4>${index + 1}. ${client.client_name}</h4>
          <p>${client.invoice_count} invoice${client.invoice_count !== 1 ? 's' : ''}</p>
        </div>
        <div class="amount">${formatCurrency(client.total_paid)}</div>
      </div>
    `;
  });
  
  container.innerHTML = html;
};

/* -----------------------------
   Render Payment Performance Table
------------------------------ */
const renderPaymentPerformance = (data) => {
  console.log('[reports.js] Rendering payment performance:', data);
  
  const tbody = qs('#paymentPerformanceTable tbody');
  
  if (!tbody) return;
  
  if (!data || data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="loading">No performance data available</td></tr>';
    return;
  }

  tbody.innerHTML = '';
  
  data.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.client_name}</td>
      <td>${row.avg_days_to_pay} days</td>
      <td>${row.on_time_rate}%</td>
      <td>${row.total_invoices}</td>
    `;
    tbody.appendChild(tr);
  });
};

/* -----------------------------
   Render Outstanding by Client
------------------------------ */
const renderOutstandingByClient = (clients) => {
  console.log('[reports.js] Rendering outstanding by client:', clients);
  
  const container = qs('#outstandingByClient');
  
  if (!clients || clients.length === 0) {
    container.innerHTML = '<div class="loading">No outstanding amounts</div>';
    return;
  }

  let html = '';
  clients.forEach((client, index) => {
    html += `
      <div class="client-item">
        <div class="client-info">
          <h4>${index + 1}. ${client.client_name}</h4>
        </div>
        <div class="amount overdue">${formatCurrency(client.outstanding)}</div>
      </div>
    `;
  });
  
  container.innerHTML = html;
};

/* -----------------------------
   Render Aging Report Cards
------------------------------ */
const renderAgingReport = (aging) => {
  console.log('[reports.js] Rendering aging report:', aging);
  
  // Current (0-30 days)
  const currentCard = document.querySelector('.aging-card.current');
  if (currentCard && aging.current) {
    currentCard.querySelector('.amount').textContent = formatCurrency(aging.current.amount || 0);
    currentCard.querySelector('.count').textContent = `${aging.current.count || 0} invoices`;
  }
  
  // 31-60 days
  const days30Card = document.querySelector('.aging-card.days-30');
  if (days30Card && aging.days_30) {
    days30Card.querySelector('.amount').textContent = formatCurrency(aging.days_30.amount || 0);
    days30Card.querySelector('.count').textContent = `${aging.days_30.count || 0} invoices`;
  }
  
  // 61-90 days
  const days60Card = document.querySelector('.aging-card.days-60');
  if (days60Card && aging.days_60) {
    days60Card.querySelector('.amount').textContent = formatCurrency(aging.days_60.amount || 0);
    days60Card.querySelector('.count').textContent = `${aging.days_60.count || 0} invoices`;
  }
  
  // 90+ days
  const days90Card = document.querySelector('.aging-card.days-90');
  if (days90Card && aging.days_90) {
    days90Card.querySelector('.amount').textContent = formatCurrency(aging.days_90.amount || 0);
    days90Card.querySelector('.count').textContent = `${aging.days_90.count || 0} invoices`;
  }
};

/* -----------------------------
   Initialize on page load
------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
  console.log('[reports.js] Page loaded, fetching reports...');
  loadReports();
});

// Optional: Refresh button
qs('#refreshReports')?.addEventListener('click', () => {
  console.log('[reports.js] Manual refresh triggered');
  loadReports();
});