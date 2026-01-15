/*
dashboard.js
Optimized dashboard logic
- loads stats
- loads recent invoices
- renders dashboard widgets
*/

import { fetchDashboard } from '../core/data/dashboard.js';
import { qs } from '../core/dom.js';
import { showError } from '../core/ui.js';
import { formatCurrency } from '../core/utils.js';

/* -----------------------------
   Load dashboard data
------------------------------ */
document.addEventListener('DOMContentLoaded', loadDashboard);

async function loadDashboard() {
  try {
    const data = await fetchDashboard();

    renderStats(data.stats);
    renderRecentInvoices(data.recent_invoices || []);
    renderPaymentChart && renderPaymentChart(data.payment_status);

  } catch (err) {
    showError(err.message || 'Failed to load dashboard data');
  }
}

/* -----------------------------
   Render stats
------------------------------ */
function renderStats(stats) {
  qs('#totalOutstanding').textContent = formatCurrency(stats.total_outstanding);
  qs('#totalReceived').textContent = formatCurrency(stats.total_received);
  qs('#unpaidCount').textContent = stats.unpaid_count ?? 0;
}

/* -----------------------------
   Render recent invoices
------------------------------ */
let paymentChart = null;

function renderRecentInvoices(invoices) {
  const tbody = qs('#recentInvoicesTable tbody');
  if (!tbody) return;

  if (!invoices.length) {
    tbody.innerHTML = '<tr><td colspan="5">No invoices found</td></tr>';
    return;
  }

  tbody.innerHTML = invoices.map(inv => {
    const statusClass = getStatusClass(inv.status);
    return `
      <tr>
        <td>${escapeHtml(inv.invoice_number)}</td>
        <td>${escapeHtml(inv.client_name)}</td>
        <td>${new Date(inv.due_date).toLocaleDateString()}</td>
        <td>${formatCurrency(inv.amount)}</td>
        <td><span class="badge ${statusClass}">${escapeHtml(inv.status)}</span></td>
      </tr>
    `;
  }).join('');
}

function getStatusClass(status) {
  const statusMap = {
    'paid': 'paid',
    'unpaid': 'unpaid',
    'partially paid': 'partial'
  };
  return statusMap[status.toLowerCase()] || 'paid';
}

function renderPaymentChart(data) {
  const canvas = qs('#paymentStatusChart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  if (paymentChart) paymentChart.destroy();

  paymentChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Paid', 'Unpaid', 'Partially Paid'],
      datasets: [{
        data: [data.paid || 0, data.unpaid || 0, data.partial || 0],
        backgroundColor: ['#059669', '#dc2626', '#d97706']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } }
    }
  });
}

// small utility to avoid XSS when inserting strings
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
