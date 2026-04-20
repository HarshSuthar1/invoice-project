/*
manage_documents_cards.js
Card-based document management with filtering, viewing, editing, and deleting
*/

import { qs, qsa } from '../core/dom.js';
import { formatCurrency } from '../core/utils.js';
import { fetchInvoices, fetchInvoice, updateInvoice, clearInvoicesCache } from '../core/data/invoice.js';
import { showError, showSuccess } from '../core/ui.js';

let documentsData = [];
let currentViewingDocId = null;

/* -----------------------------
   Load all documents
------------------------------ */
const loadDocuments = async () => {
  const container = qs('#documentsContainer');
  
  try {
    container.innerHTML = '<div class="loading-state">📄 Loading documents...</div>';
    
    const invoices = await fetchInvoices();
    documentsData = invoices;

    if (!invoices.length) {
      showEmptyState(container);
      return;
    }

    renderDocumentCards(invoices);

  } catch (err) {
    console.error('[manage_documents_cards.js] Error:', err);
    container.innerHTML = `
      <div class="empty-state">
        <h3>⚠️ Failed to Load Documents</h3>
        <p>${err.message || 'Please try refreshing the page'}</p>
      </div>
    `;
    showError(err.message || 'Failed to load documents');
  }
};

/* -----------------------------
   Render document cards
------------------------------ */
const renderDocumentCards = (documents) => {
  const container = qs('#documentsContainer');
  
  if (!documents.length) {
    showEmptyState(container);
    return;
  }

  const grid = document.createElement('div');
  grid.className = 'documents-grid';

  documents.forEach(doc => {
    const card = createDocumentCard(doc);
    grid.appendChild(card);
  });

  container.innerHTML = '';
  container.appendChild(grid);
};

/* -----------------------------
   Create individual document card
------------------------------ */
const createDocumentCard = (doc) => {
  const card = document.createElement('div');
  card.className = 'document-card';
  card.dataset.id = doc.id;

  // Determine document type from invoice number prefix
  const docType = getDocumentType(doc.invoice_number);
  const typeBadge = getTypeBadge(docType);
  const statusBadge = getStatusBadge(doc.status);

  card.innerHTML = `
    <div class="card-header-row">
      <div>
        <div class="document-number">${escapeHtml(doc.invoice_number)}</div>
        <span class="document-type-badge ${typeBadge.class}">${typeBadge.text}</span>
      </div>
    </div>

    <div class="card-body">
      <div class="card-info-row">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span class="client-name">${escapeHtml(doc.client_name || 'N/A')}</span>
      </div>

      <div class="card-info-row">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>${formatDate(doc.created_at)}</span>
      </div>

      <div class="status-amount-row">
        <span class="status-badge ${statusBadge.class}">${statusBadge.text}</span>
        <span class="document-amount">${formatCurrency(doc.grand_total)}</span>
      </div>
    </div>

    <div class="card-actions">
      <button class="btn btn-view" data-action="view-document" data-id="${doc.id}">
        👁️ View
      </button>
      <button class="btn btn-edit" data-action="edit-document" data-id="${doc.id}">
        ✏️ Edit
      </button>
      <button class="btn btn-delete" data-action="delete-document" data-id="${doc.id}">
        🗑️ Delete
      </button>
    </div>
  `;

  return card;
};

/* -----------------------------
   Helper: Get document type from number
------------------------------ */
const getDocumentType = (invoiceNumber) => {
  if (!invoiceNumber) return 'unknown';
  
  if (invoiceNumber.startsWith('QT-')) return 'quotation';
  if (invoiceNumber.startsWith('BL-')) return 'bill';
  if (invoiceNumber.startsWith('CH-')) return 'challan';
  if (invoiceNumber.startsWith('INV-')) return 'invoice';
  
  return 'invoice'; // default
};

/* -----------------------------
   Helper: Get type badge config
------------------------------ */
const getTypeBadge = (type) => {
  const badges = {
    'quotation': { text: 'Quotation', class: 'type-quotation' },
    'invoice': { text: 'Invoice (GST)', class: 'type-invoice' },
    'bill': { text: 'Bill (No GST)', class: 'type-bill' },
    'challan': { text: 'Challan', class: 'type-challan' }
  };
  
  return badges[type] || { text: 'Document', class: 'type-invoice' };
};

/* -----------------------------
   Helper: Get status badge config
------------------------------ */
const getStatusBadge = (status) => {
  const badges = {
    'paid': { text: 'Paid', class: 'status-paid' },
    'unpaid': { text: 'Unpaid', class: 'status-unpaid' },
    'partially paid': { text: 'Partial', class: 'status-partially-paid' }
  };
  
  const normalized = (status || 'unpaid').toLowerCase();
  return badges[normalized] || { text: status, class: 'status-unpaid' };
};

/* -----------------------------
   Helper: Format date
------------------------------ */
const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-IN', { 
    day: '2-digit', 
    month: 'short', 
    year: 'numeric' 
  });
};

/* -----------------------------
   Helper: Escape HTML
------------------------------ */
const escapeHtml = (text) => {
  const div = document.createElement('div');
  div.textContent = text || '';
  return div.innerHTML;
};

/* -----------------------------
   Show empty state
------------------------------ */
const showEmptyState = (container) => {
  container.innerHTML = `
    <div class="empty-state">
      <h3>📭 No Documents Found</h3>
      <p>You haven't created any documents yet. Start by creating your first document!</p>
      <button class="btn btn-view" onclick="window.location.href='/Business%20project/public/index.php?page=create-hub'" 
              style="margin-top: 16px; padding: 12px 24px;">
        ➕ Create First Document
      </button>
    </div>
  `;
};

/* -----------------------------
   View document details
------------------------------ */
const viewDocument = async (id) => {
  try {
    const { invoice: doc, items } = await fetchInvoice(id);
    currentViewingDocId = id;
    const listDoc = documentsData.find(entry => String(entry.id) === String(id));

    // Populate modal
    qs('#viewDocumentNumber').textContent = doc.invoice_number;
    qs('#viewClientName').textContent = doc.client_name || listDoc?.client_name || 'N/A';
    qs('#viewDocumentDate').textContent = formatDate(doc.invoice_date || doc.created_at);
    qs('#viewStatus').textContent = doc.status;

    // Populate items table
    const tbody = qs('#viewItemsTable');
    tbody.innerHTML = '';

    let subtotal = 0;
    let tax = 0;

    (items || []).forEach(item => {
      const unitPrice = Number(item.unit_price ?? item.price ?? 0);
      const quantity = Number(item.quantity || 0);
      const lineTotal = Number(item.line_total ?? item.total ?? quantity * unitPrice);
      const taxAmount = Number(item.tax_amount ?? item.tax ?? lineTotal - (quantity * unitPrice));

      subtotal += quantity * unitPrice;
      tax += taxAmount;

      tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${escapeHtml(item.description)}</td>
          <td>${quantity}</td>
          <td>${formatCurrency(unitPrice)}</td>
          <td>${formatCurrency(lineTotal)}</td>
        </tr>
      `);
    });

    const discount = Number(doc.discount || 0);
    const discountRow = qs('#viewDiscountRow');
    const discountValue = qs('#viewDiscount');

    qs('#viewSubtotal').textContent = formatCurrency(subtotal);
    qs('#viewTax').textContent = formatCurrency(tax);
    if (discountRow && discountValue) {
      if (discount > 0) {
        discountRow.style.display = 'flex';
        discountValue.textContent = `-${formatCurrency(discount)}`;
      } else {
        discountRow.style.display = 'none';
        discountValue.textContent = '';
      }
    }
    qs('#viewTotal').textContent = formatCurrency(doc.grand_total);

    // Open modal
    openModal('viewModal');

  } catch (err) {
    console.error('[manage_documents_cards.js] View error:', err);
    showError(err.message || 'Failed to load document details');
  }
};

/* -----------------------------
   Edit document
------------------------------ */
const editDocument = (id) => {
  const doc = documentsData.find(d => d.id == id);
  if (!doc) return;

  const docType = getDocumentType(doc.invoice_number);
  
  // Route to appropriate edit page
  window.location.href = `/Business%20project/public/index.php?page=create-document&type=${docType}&edit=${id}`;
};

/* -----------------------------
   Delete document
------------------------------ */
const deleteDocument = async (id) => {
  const doc = documentsData.find(d => d.id == id);
  if (!doc) return;

  if (!confirm(`Are you sure you want to delete ${doc.invoice_number}?\n\nThis action cannot be undone.`)) {
    return;
  }

  try {
    await updateInvoice({ delete: true, id });
    showSuccess('Document deleted successfully');
    
    // Reload documents
    await loadDocuments();
    
  } catch (err) {
    console.error('[manage_documents_cards.js] Delete error:', err);
    showError(err.message || 'Failed to delete document');
  }
};

/* -----------------------------
   Download PDF
------------------------------ */
const downloadDocumentPDF = async () => {
  if (!currentViewingDocId) return;

  try {
    const { invoice: doc, items = [] } = await fetchInvoice(currentViewingDocId);

    const html = `
      <div style="font-family: Arial, sans-serif; padding: 40px; color: #111827;">
        <h1 style="margin-bottom: 8px;">${doc.invoice_number}</h1>
        <p style="color: #6b7280; margin-bottom: 32px;">Client: ${escapeHtml(doc.client_name || 'N/A')}</p>
        
        <table width="100%" style="border-collapse: collapse; margin: 20px 0;">
          <thead>
            <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
              <th style="text-align:left; padding: 12px;">Description</th>
              <th style="text-align:right; padding: 12px;">Qty</th>
              <th style="text-align:right; padding: 12px;">Price</th>
              <th style="text-align:right; padding: 12px;">Total</th>
            </tr>
          </thead>
          <tbody>
            ${items.map(item => `
              <tr style="border-bottom: 1px solid #f3f4f6;">
                <td style="padding: 12px;">${escapeHtml(item.description)}</td>
                <td style="text-align:right; padding: 12px;">${item.quantity}</td>
                <td style="text-align:right; padding: 12px;">${formatCurrency(item.price)}</td>
                <td style="text-align:right; padding: 12px;">${formatCurrency(item.total)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        
        <div style="text-align: right; margin-top: 32px;">
          <h2 style="font-size: 24px; color: #059669;">Total: ${formatCurrency(doc.grand_total)}</h2>
        </div>
      </div>
    `;

    const container = document.createElement('div');
    container.innerHTML = html;

    html2pdf()
      .set({ 
        margin: 10, 
        filename: `${doc.invoice_number}.pdf`, 
        html2canvas: { scale: 2 },
        jsPDF: { format: 'a4' }
      })
      .from(container)
      .save();

  } catch (err) {
    console.error('[manage_documents_cards.js] PDF error:', err);
    showError('Failed to generate PDF');
  }
};

/* -----------------------------
   Filter documents
------------------------------ */
const filterDocuments = () => {
  const searchTerm = (qs('#searchInput')?.value || '').toLowerCase();
  const typeFilter = (qs('#typeFilter')?.value || '').toLowerCase();
  const statusFilter = (qs('#statusFilter')?.value || '').toLowerCase();

  const filtered = documentsData.filter(doc => {
    const matchesSearch = 
      doc.invoice_number.toLowerCase().includes(searchTerm) ||
      (doc.client_name || '').toLowerCase().includes(searchTerm);
    
    const docType = getDocumentType(doc.invoice_number);
    const matchesType = !typeFilter || docType === typeFilter;
    
    const matchesStatus = !statusFilter || 
      (doc.status || '').toLowerCase() === statusFilter;

    return matchesSearch && matchesType && matchesStatus;
  });

  renderDocumentCards(filtered);
};

/* -----------------------------
   Modal helpers
------------------------------ */
const openModal = (id) => {
  const modal = qs(`#${id}`);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
};

const closeModal = (id) => {
  const modal = qs(`#${id}`);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
};

/* -----------------------------
   Event handlers
------------------------------ */
document.addEventListener('click', (e) => {
  const actionEl = e.target.closest('[data-action]');
  const action = actionEl?.dataset.action;
  const id = actionEl?.dataset.id;
  const target = actionEl?.dataset.target;

  if (!action) return;

  switch (action) {
    case 'view-document':
      viewDocument(id);
      break;
    
    case 'edit-document':
      editDocument(id);
      break;
    
    case 'delete-document':
      deleteDocument(id);
      break;
    
    case 'download-invoice':
      downloadDocumentPDF();
      break;
    
    case 'edit-from-view':
      closeModal('viewModal');
      if (currentViewingDocId) {
        editDocument(currentViewingDocId);
      }
      break;
    
    case 'close-modal':
      closeModal(target);
      break;
  }
});

// Filter listeners
qs('#searchInput')?.addEventListener('input', filterDocuments);
qs('#typeFilter')?.addEventListener('change', filterDocuments);
qs('#statusFilter')?.addEventListener('change', filterDocuments);
qs('#dateFilter')?.addEventListener('change', filterDocuments);

// Close modal on ESC key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const activeModals = qsa('.modal-overlay.active');
    activeModals.forEach(modal => {
      closeModal(modal.id);
    });
  }
});

// Close modal when clicking outside
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay') && e.target.classList.contains('active')) {
    closeModal(e.target.id);
  }
});

/* -----------------------------
   Initialize
------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
  console.log('[manage_documents_cards.js] Initializing...');
  loadDocuments();
});
