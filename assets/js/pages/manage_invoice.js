/*
manage_invoice.js
Preserves:
- invoice listing
- view modal
- edit modal
- status labels
- PDF download hook
*/

import { qs, qsa } from '../core/dom.js';
import { formatCurrency } from '../core/utils.js';
import { fetchInvoices, fetchInvoice, updateInvoice, clearInvoicesCache } from '../core/data/invoice.js';
import { showError, showSuccess } from '../core/ui.js';

let invoicesData = {};

let currentViewingInvoiceId = null;
/* -----------------------------
   Load invoices
------------------------------ */
const loadInvoices = async () => {
  const tbody = qs('#invoiceTableBody');
  const loadingIndicator = qs('#loadingIndicator');
  const invoiceCard = qs('#invoiceCard');

  // show loading, hide card
  if (loadingIndicator) loadingIndicator.style.display = '';
  if (invoiceCard) invoiceCard.style.display = 'none';

  if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="loading">Loading invoices...</td></tr>`;

  try {
    const invoices = await fetchInvoices();

    invoicesData = {};
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!invoices.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="loading">No invoices found</td>
        </tr>`;
      // hide loading and show card even when empty
      if (loadingIndicator) loadingIndicator.style.display = 'none';
      if (invoiceCard) invoiceCard.style.display = '';
      return;
    }

    invoices.forEach(inv => {
      invoicesData[inv.id] = inv;
      addInvoiceRow(inv);
    });

    // hide loading and show invoice list
    if (loadingIndicator) loadingIndicator.style.display = 'none';
    if (invoiceCard) invoiceCard.style.display = '';

  } catch (err) {
    console.error(err);
    // hide loading and show card so message is visible
    if (loadingIndicator) loadingIndicator.style.display = 'none';
    if (invoiceCard) invoiceCard.style.display = '';
    showError(err.message || 'Failed to load invoices');
  }
};

const addInvoiceRow = (inv) => {
  const tr = document.createElement('tr');
  const status = inv.status || '';

  tr.innerHTML = `
    <td>${inv.invoice_number}</td>
    <td>${inv.client_name || ''}</td>
    <td>${inv.created_at || ''}</td>
    <td>
      <span class="status-label status-${status.replace(/\s+/g, '-')}">
        ${status}
      </span>
    </td>
    <td>${formatCurrency(inv.grand_total)}</td>
    <td>
      <div class="action-buttons">
        <button class="btn btn-view" data-action="view-invoice" data-id="${inv.id}">View</button>
        <button class="btn btn-edit" data-action="edit-invoice" data-id="${inv.id}">Edit</button>
        <button class="btn btn-delete" data-action="delete-invoice" data-id="${inv.id}">Delete</button>
      </div>
    </td>
  `;

  qs('#invoiceTableBody').appendChild(tr);
};

/* -----------------------------
   Modal helpers
------------------------------ */
const openModal = (id) => {
  qs(`#${id}`).style.display = 'flex';
  document.body.style.overflow = 'hidden';
};

const closeModal = (id) => {
  qs(`#${id}`).style.display = 'none';
  document.body.style.overflow = '';
};

/* -----------------------------
   View invoice
------------------------------ */
const viewInvoice = async (id) => {
  try {
    const { invoice: inv, items } = await fetchInvoice(id);

    currentViewingInvoiceId = id;

    qs('#viewInvoiceNumber').textContent = inv.invoice_number;
    qs('#viewClientName').textContent = inv.client_name || '';
    qs('#viewInvoiceDate').textContent = inv.invoice_date || inv.created_at || '';
    qs('#viewStatus').textContent = inv.status;

    const tbody = qs('#viewItemsTable');
    tbody.innerHTML = '';

    let subtotal = 0;
    let tax = 0;

    (items || []).forEach(item => {
      subtotal += (item.quantity || 0) * (item.price || 0);
      tax += item.tax || 0;

      tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${item.description}</td>
          <td>${item.quantity}</td>
          <td>${formatCurrency(item.price)}</td>
          <td>${formatCurrency(item.total)}</td>
        </tr>
      `);
    });

    qs('#viewSubtotal').textContent = formatCurrency(subtotal);
    qs('#viewTax').textContent = formatCurrency(tax);
    qs('#viewTotal').textContent = formatCurrency(inv.grand_total);

    openModal('viewModal');

  } catch (err) {
    console.error(err);
    showError(err.message || 'Failed to load invoice');
  }
};

const downloadInvoicePDF = async () => {
  if (!currentViewingInvoiceId) return showError('No invoice selected');

  try {
    const profileRes = await apiFetch('/api/profile/get_profile.php');
    const profile = profileRes.profile || profileRes;

    const { invoice: inv, items = [] } = await fetchInvoice(currentViewingInvoiceId);

    const html = `
      <div style="font-family: Arial, Helvetica, sans-serif; padding: 20px; color: #111827;">
        <h2>${profile?.company_name || ''}</h2>
        <p>${profile?.address || ''}</p>
        <h3>Invoice #${inv.invoice_number}</h3>
        <p>Client: ${inv.client_name || ''}</p>
        <table width="100%" style="border-collapse: collapse; margin-top: 20px;">
          <thead>
            <tr>
              <th style="text-align:left; border-bottom:1px solid #ccc;">Description</th>
              <th style="text-align:right; border-bottom:1px solid #ccc;">Qty</th>
              <th style="text-align:right; border-bottom:1px solid #ccc;">Unit</th>
              <th style="text-align:right; border-bottom:1px solid #ccc;">Total</th>
            </tr>
          </thead>
          <tbody>
            ${items.map(it => `
              <tr>
                <td>${it.description}</td>
                <td style="text-align:right">${it.quantity}</td>
                <td style="text-align:right">${formatCurrency(it.unit_price)}</td>
                <td style="text-align:right">${formatCurrency(it.line_total)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        <h3 style="text-align:right">Total: ${formatCurrency(inv.grand_total)}</h3>
      </div>
    `;

    const container = document.createElement('div');
    container.innerHTML = html;

    html2pdf().set({ margin: 10, filename: `invoice-${inv.invoice_number}.pdf`, html2canvas: { scale: 2 } }).from(container).save();

  } catch (err) {
    console.error(err);
    showError('Failed to generate PDF');
  }
};

/* -----------------------------
   Edit invoice
------------------------------ */
const editInvoice = (id) => {
  const inv = invoicesData[id];
  if (!inv) return;

  qs('#editInvoiceNumber').value = inv.invoice_number;
  qs('#editStatus').value = inv.status || 'unpaid';
  qs('#editAmountReceived').value = inv.amount_received || 0;
  qs('#editInvoiceId').value = id;

  // populate client select
  if (qs('#editClient')) {
    // assume invoicesData stores client id as client_id
    qs('#editClient').value = inv.client_id || '';
  }

  // populate items table
  const itemsTable = qs('#editItemsTable');
  itemsTable.innerHTML = '';
  (inv.items || []).forEach(item => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td><input type="text" name="desc[]" value="${item.description}" /></td>
      <td><input type="number" name="qty[]" value="${item.quantity}" step="0.01" /></td>
      <td><input type="number" name="price[]" value="${item.unit_price}" step="0.01" /></td>
      <td><input type="number" name="tax[]" value="${item.tax_rate || 0}" step="0.01" /></td>
      <td class="calculated-amount">${formatCurrency(item.line_total)}</td>
      <td><button type="button" class="btn btn-edit" data-action="remove-edit-item">Remove</button></td>
    `;
    itemsTable.appendChild(row);
  });

  calculateEditTotals();
  openModal('editModal');
};

// handle item add/remove and input changes via delegation
qs('#editItemsTable')?.addEventListener('click', (e) => {
  if (e.target.dataset.action === 'remove-edit-item') {
    const row = e.target.closest('tr');
    if (row && qs('#editItemsTable').rows.length > 1) {
      row.remove();
      calculateEditTotals();
    }
  }
});

qs('#editItemsTable')?.addEventListener('input', (e) => {
  if (e.target.matches('input')) calculateEditTotals();
});

const addEditItem = () => {
  const table = qs('#editItemsTable');
  const row = document.createElement('tr');
  row.innerHTML = `
    <td><input type="text" name="desc[]" /></td>
    <td><input type="number" name="qty[]" value="1" step="0.01" /></td>
    <td><input type="number" name="price[]" value="0" step="0.01" /></td>
    <td><input type="number" name="tax[]" value="0" step="0.01" /></td>
    <td class="calculated-amount">${formatCurrency(0)}</td>
    <td><button type="button" class="btn btn-edit" data-action="remove-edit-item">Remove</button></td>
  `;
  table.appendChild(row);
  calculateEditTotals();
};

const calculateEditTotals = () => {
  const rows = qs('#editItemsTable').querySelectorAll('tr');
  let subtotal = 0;
  let totalTax = 0;

  rows.forEach(row => {
    const inputs = row.querySelectorAll('input');
    if (inputs.length >= 4) {
      const qty = parseFloat(inputs[1].value) || 0;
      const price = parseFloat(inputs[2].value) || 0;
      const taxRate = parseFloat(inputs[3].value) || 0;

      const lineSubtotal = qty * price;
      const lineTax = lineSubtotal * (taxRate / 100);
      const lineTotal = lineSubtotal + lineTax;

      subtotal += lineSubtotal;
      totalTax += lineTax;

      const amountCell = row.querySelector('.calculated-amount');
      if (amountCell) amountCell.textContent = formatCurrency(lineTotal);
    }
  });

  qs('#editSubtotal').textContent = formatCurrency(subtotal);
  qs('#editTax').textContent = formatCurrency(totalTax);
  qs('#editTotal').textContent = formatCurrency(subtotal + totalTax);

// Ensure UI responds when status changes (shows amount received on paid/partially-paid)
// This will also be called when editInvoice sets initial values
qs('#editStatus')?.addEventListener('change', () => calculateEditTotals());

  const status = qs('#editStatus').value;
  const amountGroup = qs('#amountReceivedGroup');
  if (amountGroup) {
    amountGroup.style.display = (status === 'paid' || status === 'partially paid') ? '' : 'none';
  }

  if (status === 'paid') {
    qs('#editAmountReceived').value = (subtotal + totalTax).toFixed(2);
  }
};



qs('#editInvoiceForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const formData = new FormData(e.target);

  // collect items
  const rows = qs('#editItemsTable').querySelectorAll('tr');
  const items = [];
  rows.forEach(row => {
    const inputs = row.querySelectorAll('input');
    if (inputs.length >= 4) {
      items.push({
        description: inputs[0].value,
        quantity: inputs[1].value,
        price: inputs[2].value,
        tax: inputs[3].value
      });
    }
  });

  formData.append('items', JSON.stringify(items));
  formData.append('grand_total', qs('#editTotal').textContent.replace(/[^0-9.-]+/g, ''));

  try {
    await apiFetch('/api/invoices/update_invoice.php', {
      method: 'POST',
      body: formData
    });

    closeModal('editModal');
    loadInvoices();

  } catch (err) {
    showError(err.message);
  }
});

/* -----------------------------
   Global click handler
------------------------------ */
document.addEventListener('click', (e) => {
  const action = e.target.dataset.action;
  const id = e.target.dataset.id;
  const target = e.target.dataset.target;

  if (!action) return;

  if (action === 'new-invoice') {
    window.location.href = '/Business%20project/public/index.php?page=invoice';
  }

  if (action === 'view-invoice') viewInvoice(id);
  if (action === 'edit-invoice') editInvoice(id);
  if (action === 'delete-invoice') deleteInvoice(id);
  if (action === 'download-invoice') downloadInvoicePDF(id);
  if (action === 'add-edit-item') addEditItem();
  if (action === 'close-modal') closeModal(target);
});

/* -----------------------------
   Delete invoice
------------------------------ */
const deleteInvoice = async (id) => {
  if (!id) return;
  if (!confirm('Are you sure you want to delete this invoice?')) return;

  try {
    await updateInvoice({ delete: true, id });

    await loadInvoices();
    showSuccess('Invoice deleted successfully');
  } catch (err) {
    console.error(err);
    showError(err.message || 'Failed to delete invoice');
  }
};

/* -----------------------------
   Search & Filter
------------------------------ */
qs('#searchInput')?.addEventListener('input', () => filterInvoices());
qs('#statusFilter')?.addEventListener('change', () => filterInvoices());

const filterInvoices = () => {
  const searchTerm = (qs('#searchInput')?.value || '').toLowerCase();
  const statusFilter = (qs('#statusFilter')?.value || '').toLowerCase();

  const rows = Object.values(invoicesData).filter(inv => {
    const status = (inv.status || '').toLowerCase();
    const matchesSearch = inv.invoice_number.toLowerCase().includes(searchTerm) || (inv.client_name || '').toLowerCase().includes(searchTerm);
    const matchesStatus = statusFilter === '' || status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const tbody = qs('#invoiceTableBody');
  tbody.innerHTML = '';
  rows.forEach(addInvoiceRow);
};

/* -----------------------------
   Helpers
------------------------------ */
// local showError removed — use shared ui


document.addEventListener('DOMContentLoaded', loadInvoices);
