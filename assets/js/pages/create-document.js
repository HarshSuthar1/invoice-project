/*
create-document.js
Handles document creation for multiple types: quotation, bill, invoice, challan
*/

import { qs, qsa } from '../core/dom.js';
import { showError, showSuccess, openModal, closeModal } from '../core/ui.js';
import { required } from '../core/validators.js';
import { fetchClients } from '../core/data/clients.js';
import { apiFetch } from '../core/api.js';
import { formatCurrency } from '../core/utils.js';

let itemIndex = 0;
let currentDocType = '';
let showTax = true;

/* ----------------------------------
   Init
---------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
  // Get document type from URL
  const urlParams = new URLSearchParams(window.location.search);
  currentDocType = urlParams.get('type') || 'invoice';
  
  // Determine if tax should be shown
  showTax = !['bill-no-gst', 'quotation'].includes(currentDocType);
  
  // Hide/show tax columns
  updateTaxVisibility();
  
  // Set today's date
  const today = new Date().toISOString().split('T')[0];
  qs('#document-date').value = today;
  
  // Load initial data
  loadClients();
  fetchNextDocumentNumber();
  addItemRow();
});

/* ----------------------------------
   Update tax column visibility
---------------------------------- */
function updateTaxVisibility() {
  const taxColumns = qsa('.tax-column');
  const taxRows = qsa('.tax-row');
  
  taxColumns.forEach(el => {
    el.style.display = showTax ? '' : 'none';
  });
  
  taxRows.forEach(el => {
    el.style.display = showTax ? '' : 'none';
  });
}

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
      option.textContent = client.company_name || `Client #${client.id}`;
      select.appendChild(option);
    });
  } catch (err) {
    console.error(err);
    showError('Failed to load clients');
  }
}

/* ----------------------------------
   Fetch next document number
---------------------------------- */
async function fetchNextDocumentNumber() {
  try {
    const res = await apiFetch(`/api/documents/get_next_number.php?type=${currentDocType}`);
    qs('#document-number').value = res.next_number || '1';
  } catch (err) {
    console.error(err);
    // If API fails, just use a timestamp-based number
    qs('#document-number').value = Date.now().toString().slice(-6);
  }
}

/* ----------------------------------
   Add item row
---------------------------------- */
function addItemRow(data = null) {
  const tbody = qs('#documentItemsBody');
  const row = document.createElement('tr');
  row.dataset.index = itemIndex++;

  row.innerHTML = `
    <td><input type="text" class="desc" value="${data?.description || ''}" required></td>
    <td><input type="number" class="qty" value="${data?.quantity || 1}" min="0" step="0.01"></td>
    <td><input type="text" class="unit" value="${data?.unit || 'Nos'}"></td>
    <td><input type="number" class="price" value="${data?.price || 0}" min="0" step="0.01"></td>
    <td class="tax-column"><input type="number" class="tax" value="${data?.tax || 0}" min="0" max="100" step="0.01"></td>
    <td class="lineTotal">₹0.00</td>
    <td><button type="button" class="btn-delete btn" data-action="remove-item">×</button></td>
  `;

  tbody.appendChild(row);
  calculateTotals();
}

/* ----------------------------------
   Calculate totals
---------------------------------- */
function calculateTotals() {
  let subtotal = 0;
  let taxTotal = 0;

  qsa('#documentItemsBody tr').forEach(row => {
    const qty = Number(row.querySelector('.qty').value) || 0;
    const price = Number(row.querySelector('.price').value) || 0;
    const taxRate = showTax ? (Number(row.querySelector('.tax')?.value) || 0) : 0;

    const lineAmount = qty * price;
    const taxAmount = (lineAmount * taxRate) / 100;

    subtotal += lineAmount;
    taxTotal += taxAmount;

    row.querySelector('.lineTotal').textContent = formatCurrency(lineAmount + taxAmount);
  });

  qs('#subtotal').textContent = formatCurrency(subtotal);
  qs('#totalTax').textContent = formatCurrency(taxTotal);
  qs('#grandTotal').textContent = formatCurrency(subtotal + taxTotal);
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
    const row = e.target.closest('tr');
    if (qsa('#documentItemsBody tr').length > 1) {
      row?.remove();
      calculateTotals();
    } else {
      showError('At least one item is required');
    }
  }

  if (action === 'save-document') {
    saveDocument();
  }

  if (action === 'import-from') {
    const importType = e.target.dataset.importType;
    openImportModal(importType);
  }

  if (action === 'close-modal') {
    const target = e.target.dataset.target;
    closeModal(target);
  }

  if (action === 'import-document') {
    const docId = e.target.dataset.docId;
    importDocument(docId);
  }
});

/* ----------------------------------
   Recalculate on input
---------------------------------- */
document.addEventListener('input', (e) => {
  if (e.target.closest('#documentItemsBody')) {
    calculateTotals();
  }
});

/* ----------------------------------
   Open import modal
---------------------------------- */
async function openImportModal(importType) {
  try {
    const res = await apiFetch(`/api/documents/get_documents.php?type=${importType}`);
    const documents = res.documents || [];
    
    const tbody = qs('#importTableBody');
    tbody.innerHTML = '';
    
    if (!documents.length) {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">No ${importType} documents found</td></tr>`;
    } else {
      documents.forEach(doc => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${doc.document_number}</td>
          <td>${doc.client_name || 'N/A'}</td>
          <td>${doc.document_date || doc.created_at}</td>
          <td>${formatCurrency(doc.grand_total)}</td>
          <td>
            <button type="button" class="btn btn-view" data-action="import-document" data-doc-id="${doc.id}">
              Import
            </button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }
    
    openModal('importModal');
  } catch (err) {
    console.error(err);
    showError('Failed to load documents for import');
  }
}

/* ----------------------------------
   Import document data
---------------------------------- */
async function importDocument(docId) {
  try {
    const res = await apiFetch(`/api/documents/get_document_details.php?id=${docId}`);
    const doc = res.document;
    const items = res.items || [];
    
    // Fill client
    qs('#clientSelect').value = doc.client_id;
    
    // Clear existing items and add imported ones
    qs('#documentItemsBody').innerHTML = '';
    itemIndex = 0;
    
    items.forEach(item => {
      addItemRow({
        description: item.description,
        quantity: item.quantity,
        unit: item.unit || 'Nos',
        price: item.unit_price || item.price,
        tax: showTax ? (item.tax_rate || 0) : 0
      });
    });
    
    if (items.length === 0) {
      addItemRow();
    }
    
    closeModal('importModal');
    showSuccess('Document data imported successfully');
    calculateTotals();
  } catch (err) {
    console.error(err);
    showError('Failed to import document');
  }
}

/* ----------------------------------
   Save document
---------------------------------- */
async function saveDocument() {
  // Validate
  if (!required(qs('#clientSelect').value)) {
    showError('Please select a client');
    return;
  }

  if (!required(qs('#document-date').value)) {
    showError('Please enter a date');
    return;
  }

  const items = [];
  let hasEmptyDescription = false;

  qsa('#documentItemsBody tr').forEach(row => {
    const desc = row.querySelector('.desc').value.trim();
    if (!desc) {
      hasEmptyDescription = true;
      return;
    }
    
    items.push({
      description: desc,
      quantity: row.querySelector('.qty').value,
      unit: row.querySelector('.unit').value,
      price: row.querySelector('.price').value,
      tax: showTax ? (row.querySelector('.tax')?.value || 0) : 0
    });
  });

  if (hasEmptyDescription) {
    showError('Please fill in all item descriptions');
    return;
  }

  if (!items.length) {
    showError('Add at least one item');
    return;
  }

  const formData = new FormData(qs('#documentForm'));
  formData.append('items', JSON.stringify(items));
  formData.append('grand_total', qs('#grandTotal').textContent.replace(/[^0-9.-]+/g, ''));
  formData.append('subtotal', qs('#subtotal').textContent.replace(/[^0-9.-]+/g, ''));
  formData.append('total_tax', qs('#totalTax').textContent.replace(/[^0-9.-]+/g, ''));

  const saveBtn = qs('[data-action="save-document"]');
  const form = qs('#documentForm');

  try {
    form?.classList.add('loading');
    if (saveBtn) saveBtn.disabled = true;

    await apiFetch('/api/documents/save_document.php', {
      method: 'POST',
      body: formData
    });

    showSuccess('Document created successfully');
    
    // Redirect after short delay
    setTimeout(() => {
      window.location.href = '/Business%20project/public/index.php?page=manage-documents';
    }, 1500);
    
  } catch (err) {
    showError(err.message || 'Failed to save document');
  } finally {
    form?.classList.remove('loading');
    if (saveBtn) saveBtn.disabled = false;
  }
}