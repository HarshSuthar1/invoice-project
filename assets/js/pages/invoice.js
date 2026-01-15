/*
invoice.js
Optimized invoice page logic
- load clients
- add / remove items
- auto calculate totals
- save invoice
*/

import { qs, qsa } from '../core/dom.js';
import { showError, showSuccess } from '../core/ui.js';
import { required } from '../core/validators.js';
import { fetchClients } from '../core/data/clients.js';
import { saveInvoice as saveInvoiceApi } from '../core/data/invoice.js';

let itemIndex = 0;

/* ----------------------------------
   Init
---------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
  loadClients();
  addItemRow();
});

/* ----------------------------------
   Load clients into dropdown
---------------------------------- */
async function loadClients() {
  try {
    const clients = await fetchClients();

    const select = qs('#clientSelect');
    select.innerHTML = `<option value="">Select Client</option>`;

    clients.forEach(client => {
      const option = document.createElement('option');
      option.value = client.id;
      option.textContent =
        client.company_name ||
        client.companyName ||
        `Client #${client.id}`;

      select.appendChild(option);
    });

  } catch (err) {
    console.error(err);
    showError('Failed to load clients');
  }
}

/* ----------------------------------
   Add item row
---------------------------------- */
function addItemRow() {
  const tbody = qs('#invoiceItemsBody');
  const row = document.createElement('tr');
  row.dataset.index = itemIndex++;

  row.innerHTML = `
    <td><input type="text" class="desc" required></td>
    <td><input type="number" class="qty" value="1" min="1"></td>
    <td><input type="text" class="unit" value="Nos"></td>
    <td><input type="number" class="price" value="0" min="0"></td>
    <td><input type="number" class="tax" value="0" min="0"></td>
    <td class="lineTotal">0.00</td>
    <td><button class="btn-delete btn" data-action="remove-item">X</button></td>
  `;

  tbody.appendChild(row);
}

/* ----------------------------------
   Calculate totals
---------------------------------- */
function calculateTotals() {
  let subtotal = 0;
  let taxTotal = 0;

  qsa('#invoiceItemsBody tr').forEach(row => {
    const qty = Number(row.querySelector('.qty').value) || 0;
    const price = Number(row.querySelector('.price').value) || 0;
    const taxRate = Number(row.querySelector('.tax').value) || 0;

    const lineAmount = qty * price;
    const taxAmount = (lineAmount * taxRate) / 100;

    subtotal += lineAmount;
    taxTotal += taxAmount;

    row.querySelector('.lineTotal').textContent =
      (lineAmount + taxAmount).toFixed(2);
  });

  qs('#subtotal').textContent = subtotal.toFixed(2);
  qs('#totalTax').textContent = taxTotal.toFixed(2);
  qs('#grandTotal').textContent = (subtotal + taxTotal).toFixed(2);
}

/* ----------------------------------
   Event delegation
---------------------------------- */
document.addEventListener('click', (e) => {
  const action = e.target.dataset.action;
  if (!action) return;

  if (action === 'add-item') {
    addItemRow();
  }

  if (action === 'remove-item') {
    e.target.closest('tr')?.remove();
    calculateTotals();
  }

  if (action === 'save-invoice') {
    saveInvoice();
  }
});

/* ----------------------------------
   Recalculate on input
---------------------------------- */
document.addEventListener('input', (e) => {
  if (e.target.closest('#invoiceItemsBody')) {
    calculateTotals();
  }
});

/* ----------------------------------
   Save invoice
---------------------------------- */
async function saveInvoice() {
  if (!required(qs('#clientSelect').value)) {
    showError('Please select a client');
    return;
  }

  const items = [];

  qsa('#invoiceItemsBody tr').forEach(row => {
    items.push({
      description: row.querySelector('.desc').value,
      quantity: row.querySelector('.qty').value,
      price: row.querySelector('.price').value,
      tax: row.querySelector('.tax').value
    });
  });

  if (!items.length) {
    showError('Add at least one item');
    return;
  }

  const formData = new FormData(qs('#invoiceForm'));
  formData.append('items', JSON.stringify(items));
  formData.append('grand_total', qs('#grandTotal').textContent);

  const saveButtons = document.querySelectorAll('[data-action="save-invoice"]');
  const form = qs('#invoiceForm');

  try {
    form?.classList.add('loading');
    saveButtons.forEach(b => b.disabled = true);

    await saveInvoiceApi(formData);
    showSuccess('Invoice saved successfully');
    // navigate after success
    window.location.href = '/Business%20project/public/index.php?page=manage_invoice';
  } catch (err) {
    showError(err.message || 'Network error while saving invoice');
  } finally {
    form?.classList.remove('loading');
    saveButtons.forEach(b => b.disabled = false);
  }
}
