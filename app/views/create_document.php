<?php
$docType = $_GET['type'] ?? 'invoice';
$docTitles = [
    'quotation' => 'Quotation',
    'bill-no-gst' => 'Bill (No GST)',
    'invoice' => 'Invoice (With GST)',
    'challan' => 'Transport Challan'
];
$docTitle = $docTitles[$docType] ?? 'Invoice';
$showTax = !in_array($docType, ['bill-no-gst', 'quotation', 'challan']);
$isChallan = $docType === 'challan';
$isInvoice = $docType === 'invoice';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Create <?php echo $docTitle; ?></title>
    <style>
        .section-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
        }

        .section-header-bar h2 {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .btn-back {
            background: #f3f4f6;
            color: #374151;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: #e5e7eb;
        }

        .import-section {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .import-section h4 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 12px;
        }

        .import-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .import-buttons .btn {
            flex: 1;
            min-width: 200px;
        }

        .tax-column {
            display: <?php echo $showTax ? 'table-cell' : 'none'; ?>;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="section-header-bar">
            <h2>Create <?php echo $docTitle; ?></h2>
            <a href="/Business%20project/public/index.php?page=create-hub" class="btn-back">
                ← Back to Hub
            </a>
        </div>

        <div class="invoice-card">
            <form id="documentForm">
                <input type="hidden" id="documentType" name="document_type" value="<?php echo htmlspecialchars($docType); ?>">

                <!-- Import Section (Only for Invoice) -->
                <?php if ($isInvoice): ?>
                <div class="import-section">
                    <h4>💡 Import Data From Existing Document</h4>
                    <div class="import-buttons">
                        <button type="button" class="btn btn-view" data-action="import-from" data-import-type="quotation">
                            Import from Quotation
                        </button>
                        <button type="button" class="btn btn-view" data-action="import-from" data-import-type="bill-no-gst">
                            Import from Bill (No GST)
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Document Details -->
                <div class="invoice-details-grid">
                    <div class="form-group">
                        <label for="clientSelect">Client <span class="required">*</span></label>
                        <select id="clientSelect" name="client_id" required>
                            <option value="">Loading clients...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="document-date">Date <span class="required">*</span></label>
                        <input type="date" id="document-date" name="document_date" required>
                    </div>
                    <div class="form-group">
                        <label for="document-number">Document # <span class="required">*</span></label>
                        <input type="text" id="document-number" name="document_number" required>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-container">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <?php if ($isChallan): ?>
                                    <th style="width: 12%;">Date</th>
                                    <th style="width: 45%;">Description (Starting → Ending Destination)</th>
                                    <th style="width: 12%;">Rounds</th>
                                    <th style="width: 18%;">Amount</th>
                                    <th style="width: 13%;">Action</th>
                                <?php else: ?>
                                    <th>Description</th>
                                    <?php if ($showTax): ?>
                                    <th style="width: 10%;">HSN Code</th>
                                    <?php endif; ?>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Unit Price</th>
                                    <th class="tax-column">Tax (%)</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="documentItemsBody">
                            <!-- Rows added dynamically -->
                        </tbody>
                    </table>
                </div>

                <button type="button" class="add-item-button" data-action="add-item">+ Add Row</button>

                <!-- Summary Section -->
                <div class="summary-section">
                    <div class="summary-card">
                        <?php if (!$isChallan): ?>
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span id="subtotal">₹0.00</span>
                        </div>
                        <?php if ($showTax): ?>
                        <div class="summary-item tax-column">
                            <span>CGST</span>
                            <span id="cgst">₹0.00</span>
                        </div>
                        <div class="summary-item tax-column">
                            <span>SGST</span>
                            <span id="sgst">₹0.00</span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-item tax-column">
                            <span>Total Tax</span>
                            <span id="totalTax">₹0.00</span>
                        </div>
                        <?php endif; ?>
                        <div class="summary-total">
                            <span>Total</span>
                            <span id="grandTotal">₹0.00</span>
                        </div>
                    </div>
                </div>

                <div class="create-invoice-button-container">
                    <button type="button" class="create-invoice-button" data-action="save-document">
                        Create <?php echo $docTitle; ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Import Modal -->
    <div class="modal-overlay" id="importModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Select Document to Import</h3>
                <button class="close-button" data-action="close-modal" data-target="importModal">&times;</button>
            </div>
            <div class="table-container">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Document #</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="importTableBody">
                        <tr><td colspan="5" class="loading">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div class="success-message" id="successMessage">✓ Success!</div>
    <div class="error-message" id="errorMessage">✗ Error occurred!</div>

    <script type="module">
import { qs, qsa } from '/Business%20project/assets/js/core/dom.js';
import { showError, showSuccess, openModal, closeModal } from '/Business%20project/assets/js/core/ui.js';
import { required } from '/Business%20project/assets/js/core/validators.js';
import { fetchClients } from '/Business%20project/assets/js/core/data/clients.js';
import { apiFetch } from '/Business%20project/assets/js/core/api.js';
import { formatCurrency } from '/Business%20project/assets/js/core/utils.js';

let itemIndex = 0;
const currentDocType = '<?php echo $docType; ?>';
const isChallan = currentDocType === 'challan';
const showTax = !['bill-no-gst', 'quotation', 'challan'].includes(currentDocType);

document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().split('T')[0];
  qs('#document-date').value = today;
  
  loadClients();
  fetchNextDocumentNumber();
  addItemRow();
});

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

async function fetchNextDocumentNumber() {
  try {
    const res = await apiFetch(`/api/documents/get_next_number.php?type=${currentDocType}`);
    qs('#document-number').value = res.next_number || '1';
  } catch (err) {
    console.error(err);
    qs('#document-number').value = Date.now().toString().slice(-6);
  }
}

function addItemRow(data = null) {
  const tbody = qs('#documentItemsBody');
  const row = document.createElement('tr');
  row.dataset.index = itemIndex++;

  if (isChallan) {
    // Simplified challan row: Date, Description, Rounds, Amount
    row.innerHTML = `
      <td><input type="date" class="row-date" value="${data?.date || ''}" required></td>
      <td><textarea class="desc" rows="2" placeholder="e.g., Ahmedabad → Mumbai" required>${data?.description || ''}</textarea></td>
      <td><input type="number" class="qty" value="${data?.quantity || 1}" min="0" step="1"></td>
      <td><input type="number" class="price" value="${data?.price || 0}" min="0" step="0.01"></td>
      <td><button type="button" class="btn-delete btn" data-action="remove-item">×</button></td>
    `;
  } else {
    // Standard row for other documents
    const hsnCell = showTax ? `<td><input type="text" class="hsn" value="${data?.hsn || ''}" placeholder="e.g., 7308"></td>` : '';
    row.innerHTML = `
      <td><input type="text" class="desc" value="${data?.description || ''}" required></td>
      ${hsnCell}
      <td><input type="number" class="qty" value="${data?.quantity || 1}" min="0" step="0.01"></td>
      <td><input type="text" class="unit" value="${data?.unit || 'Nos'}"></td>
      <td><input type="number" class="price" value="${data?.price || 0}" min="0" step="0.01"></td>
      <td class="tax-column"><input type="number" class="tax" value="${data?.tax || 0}" min="0" max="100" step="0.01"></td>
      <td class="lineTotal">₹0.00</td>
      <td><button type="button" class="btn-delete btn" data-action="remove-item">×</button></td>
    `;
  }

  tbody.appendChild(row);
  calculateTotals();
}

function calculateTotals() {
  let total = 0;
  let subtotal = 0;
  let taxTotal = 0;

  qsa('#documentItemsBody tr').forEach(row => {
    const qty = Number(row.querySelector('.qty').value) || 0;
    const price = Number(row.querySelector('.price').value) || 0;
    
    if (isChallan) {
      // For challan: simple multiplication (rounds × amount)
      const lineAmount = qty * price;
      total += lineAmount;
    } else {
      // For other documents: calculate with tax
      const taxRate = showTax ? (Number(row.querySelector('.tax')?.value) || 0) : 0;
      const lineAmount = qty * price;
      const taxAmount = (lineAmount * taxRate) / 100;
      
      subtotal += lineAmount;
      taxTotal += taxAmount;
      total = subtotal + taxTotal;
      
      row.querySelector('.lineTotal').textContent = formatCurrency(lineAmount + taxAmount);
    }
  });

  if (!isChallan) {
    qs('#subtotal').textContent = formatCurrency(subtotal);
    
    if (showTax) {
      // Split tax into CGST and SGST (half each for intra-state)
      const cgst = taxTotal / 2;
      const sgst = taxTotal / 2;
      qs('#cgst').textContent = formatCurrency(cgst);
      qs('#sgst').textContent = formatCurrency(sgst);
    }
    
    qs('#totalTax').textContent = formatCurrency(taxTotal);
  }
  qs('#grandTotal').textContent = formatCurrency(total);
}

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
      showError('At least one row is required');
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

document.addEventListener('input', (e) => {
  if (e.target.closest('#documentItemsBody')) {
    calculateTotals();
  }
});

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

async function importDocument(docId) {
  try {
    const res = await apiFetch(`/api/documents/get_document_details.php?id=${docId}`);
    const doc = res.document;
    const items = res.items || [];
    
    qs('#clientSelect').value = doc.client_id;
    
    qs('#documentItemsBody').innerHTML = '';
    itemIndex = 0;
    
    items.forEach(item => {
      addItemRow({
        description: item.description,
        hsn: item.hsn_code,
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

async function saveDocument() {
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
    
    if (isChallan) {
      items.push({
        description: desc,
        date: row.querySelector('.row-date').value,
        quantity: row.querySelector('.qty').value,
        unit: 'Rounds',
        price: row.querySelector('.price').value,
        tax: 0
      });
    } else {
      items.push({
        description: desc,
        hsn_code: showTax ? (row.querySelector('.hsn')?.value || '') : '',
        quantity: row.querySelector('.qty').value,
        unit: row.querySelector('.unit').value,
        price: row.querySelector('.price').value,
        tax: showTax ? (row.querySelector('.tax')?.value || 0) : 0
      });
    }
  });

  if (hasEmptyDescription) {
    showError('Please fill in all descriptions');
    return;
  }

  if (!items.length) {
    showError('Add at least one row');
    return;
  }

  const formData = new FormData(qs('#documentForm'));
  formData.append('items', JSON.stringify(items));
  formData.append('grand_total', qs('#grandTotal').textContent.replace(/[^0-9.-]+/g, ''));
  
  if (!isChallan) {
    formData.append('subtotal', qs('#subtotal').textContent.replace(/[^0-9.-]+/g, ''));
    formData.append('total_tax', qs('#totalTax').textContent.replace(/[^0-9.-]+/g, ''));
  } else {
    formData.append('subtotal', qs('#grandTotal').textContent.replace(/[^0-9.-]+/g, ''));
    formData.append('total_tax', '0');
  }

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
    </script>
</body>
</html>