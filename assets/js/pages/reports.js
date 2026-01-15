/*
reports.js
Preserves:
- date filters
- status filter
- summary totals
- invoice report table
*/

import { qs } from '../core/dom.js';
import { formatCurrency } from '../core/utils.js';
import { showError, showSuccess } from '../core/ui.js';
import { fetchReports, clearReportsCache } from '../core/data/reports.js';

/* -----------------------------
   Fetch & render reports
------------------------------ */
const loadReports = async () => {
  // show loading states
  qs('#revenueChart').innerHTML = '<div class="loading">Loading chart data...</div>';
  const tbody = qs('#reportTableBody');
  if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="loading">Loading...</td></tr>`;

  try {
    const params = new URLSearchParams();

    const fromDate = qs('#fromDate')?.value;
    const toDate = qs('#toDate')?.value;
    const status = qs('#statusFilter')?.value;

    if (fromDate) params.append('from_date', fromDate);
    if (toDate) params.append('to_date', toDate);
    if (status) params.append('status', status);

    const data = await fetchReports(Object.fromEntries(params.entries()));

    renderSummary(data.summary);
    renderTable(data.invoices || []);

  } catch (err) {
    showError(err.message || 'Failed to load reports');
  }
};

/* -----------------------------
   Render summary
------------------------------ */
const renderSummary = (summary) => {
  qs('#totalInvoices').textContent = summary.total_invoices || 0;
  qs('#totalAmount').textContent = formatCurrency(summary.total_amount);
  qs('#totalReceived').textContent = formatCurrency(summary.total_received);
  qs('#totalDue').textContent = formatCurrency(summary.total_due);
};

/* -----------------------------
   Render table (if exists)
------------------------------ */
const renderTable = (rows) => {
  const tbody = qs('#reportTableBody');
  if (!tbody) return;

  tbody.innerHTML = '';

  if (!rows.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" style="text-align:center;color:#6b7280;padding:30px">
          No records found
        </td>
      </tr>`;
    return;
  }

  rows.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.invoice_number}</td>
      <td>${row.client_name}</td>
      <td>${row.created_at}</td>
      <td>${row.status}</td>
      <td>${formatCurrency(row.grand_total)}</td>
      <td>${formatCurrency(row.amount_received)}</td>
    `;
    tbody.appendChild(tr);
  });
};

/* -----------------------------
   Events
------------------------------ */
qs('#applyFilters')?.addEventListener('click', loadReports);

/* -----------------------------
   Helpers
------------------------------ */
// use shared ui showError (imported)

document.addEventListener('DOMContentLoaded', loadReports);
