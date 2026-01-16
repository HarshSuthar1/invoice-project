/*
create-document.js - Unified Document Creation
Handles: Quotations, Tax Invoices, Bills of Supply, Delivery Challans
*/

import { qs, qsa } from '../core/dom.js';
import { formatCurrency } from '../core/utils.js';
import { showError, showSuccess, openModal, closeModal } from '../core/ui.js';
import { apiFetch } from '../core/api.js';

// Document state
let documentType = 'quotation';
let fromQuotationId = null;
let itemCounter = 0;
let hsnCodes = [];
let clients = [];
let companyProfile = null;
let currentHsnRowId = null;

/* -----------------------------
   Initialize
------------------------------ */
document.addEventListener('DOMContentLoaded', async () => {
    console.log('[create-document.js] Starting...');

    documentType = qs('#documentType')?.value || 'quotation';
    fromQuotationId = qs('#fromQuotationId')?.value || null;

    console.log(`[create-document.js] Type: ${documentType}, From Quote: ${fromQuotationId}`);

    await loadCompanyProfile();
    await loadClients();
    await loadHsnCodes();
    
    adaptFormToDocumentType();
    
    const today = new Date().toISOString().split('T')[0];
    if (qs('#documentDate')) qs('#documentDate').value = today;

    await generateDocumentNumber();

    if (fromQuotationId) {
        await loadFromQuotation();
    } else {
        addItemRow();
    }

    setupEventListeners();
});

/* -----------------------------
   Load Data
------------------------------ */
const loadCompanyProfile = async () => {
    try {
        const data = await apiFetch('/api/profile/get_profile.php');
        companyProfile = data.profile || data;
    } catch (err) {
        console.error('Profile error:', err);
    }
};

const loadClients = async () => {
    try {
        const data = await apiFetch('/api/clients/get_clients.php');
        clients = Array.isArray(data) ? data : (data.clients || []);
        
        const select = qs('#clientSelect');
        if (!select) return;

        select.innerHTML = '<option value="">Select client...</option>';
        
        clients.forEach(client => {
            const option = document.createElement('option');
            option.value = client.id;
            option.textContent = client.company_name;
            option.dataset.gst = client.gst_number || '';
            option.dataset.state = client.state_code || '';
            select.appendChild(option);
        });
    } catch (err) {
        showError('Failed to load clients');
    }
};

const loadHsnCodes = async () => {
    try {
        const data = await apiFetch('/api/hsn_codes/get_hsn_codes.php');
        hsnCodes = Array.isArray(data) ? data : (data.hsn_codes || []);
    } catch (err) {
        console.log('HSN codes not loaded');
    }
};

/* -----------------------------
   Adapt Form
------------------------------ */
const adaptFormToDocumentType = () => {
    const isQuotation = documentType === 'quotation';
    const isTaxInvoice = documentType === 'tax-invoice';

    const titles = {
        'quotation': 'Create Quotation',
        'tax-invoice': 'Create Tax Invoice (GST)',
        'bill-of-supply': 'Create Bill of Supply',
        'delivery-challan': 'Create Delivery Challan'
    };

    if (qs('#documentTypeTitle')) qs('#documentTypeTitle').textContent = titles[documentType];

    if (qs('#validUntilGroup')) {
        qs('#validUntilGroup').style.display = isQuotation ? 'block' : 'none';
        if (isQuotation && qs('#validUntil')) {
            const validDate = new Date();
            validDate.setDate(validDate.getDate() + 30);
            qs('#validUntil').value = validDate.toISOString().split('T')[0];
        }
    }

    if (qs('#dueDateGroup')) {
        qs('#dueDateGroup').style.display = (isTaxInvoice || documentType === 'bill-of-supply') ? 'block' : 'none';
    }

    if (qs('#placeOfSupplyGroup')) {
        qs('#placeOfSupplyGroup').style.display = isTaxInvoice ? 'block' : 'none';
    }

    if (qs('#hsnHeader')) {
        qs('#hsnHeader').style.display = (isTaxInvoice || isQuotation) ? '' : 'none';
    }

    if (qs('#taxHeader')) {
        qs('#taxHeader').style.display = (isTaxInvoice || isQuotation) ? '' : 'none';
    }
};

/* -----------------------------
   Generate Number
------------------------------ */
const generateDocumentNumber = async () => {
    const input = qs('#documentNumber');
    if (!input) return;

    const prefixes = {
        'quotation': 'QUO',
        'tax-invoice': 'INV',
        'bill-of-supply': 'BOS',
        'delivery-challan': 'DC'
    };

    const prefix = prefixes[documentType] || 'DOC';
    input.value = `${prefix}-0001`;
};

/* -----------------------------
   Load From Quotation
------------------------------ */
const loadFromQuotation = async () => {
    try {
        const data = await apiFetch(`/api/quotations/get_quotation.php?id=${fromQuotationId}`);
        const quotation = data.quotation || data;
        const items = data.items || [];

        if (qs('#clientSelect')) qs('#clientSelect').value = quotation.client_id;
        if (qs('#notes')) qs('#notes').value = quotation.notes || '';

        items.forEach(item => {
            addItemRow({
                description: item.description,
                hsn_code: item.hsn_code,
                quantity: item.quantity,
                unit: item.unit || 'Nos',
                unit_price: item.unit_price,
                tax_rate: documentType === 'bill-of-supply' ? 0 : item.tax_rate
            });
        });

        showSuccess('Quotation loaded');
        calculateTotals();
    } catch (err) {
        showError('Failed to load quotation');
        addItemRow();
    }
};

/* -----------------------------
   Add Item Row
------------------------------ */
const addItemRow = (data = {}) => {
    const tbody = qs('#itemsTableBody');
    if (!tbody) return;

    itemCounter++;
    const rowId = `row-${itemCounter}`;

    const isTaxDoc = documentType === 'tax-invoice' || documentType === 'quotation';
    const showHsn = isTaxDoc;
    const showTax = isTaxDoc;

    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.dataset.rowId = itemCounter;

    tr.innerHTML = `
        <td>
            <textarea class="item-description" rows="2" placeholder="Description..." required>${data.description || ''}</textarea>
        </td>
        <td style="display: ${showHsn ? '' : 'none'};">
            <input type="text" class="item-hsn" value="${data.hsn_code || ''}" placeholder="HSN" readonly>
            <button type="button" class="btn-hsn" data-row="${itemCounter}">🔍</button>
        </td>
        <td>
            <input type="number" class="item-qty" value="${data.quantity || 1}" min="0.01" step="0.01" required>
        </td>
        <td>
            <select class="item-unit">
                <option value="Nos" ${data.unit === 'Nos' ? 'selected' : ''}>Nos</option>
                <option value="Kg" ${data.unit === 'Kg' ? 'selected' : ''}>Kg</option>
                <option value="Meter" ${data.unit === 'Meter' ? 'selected' : ''}>Meter</option>
                <option value="Feet" ${data.unit === 'Feet' ? 'selected' : ''}>Feet</option>
            </select>
        </td>
        <td>
            <input type="number" class="item-rate" value="${data.unit_price || 0}" min="0" step="0.01" required>
        </td>
        <td style="display: ${showTax ? '' : 'none'};">
            <input type="number" class="item-tax" value="${data.tax_rate || 18}" min="0" max="100" step="0.01">
        </td>
        <td>
            <strong class="item-total">${formatCurrency(0)}</strong>
        </td>
        <td>
            <button type="button" class="btn-remove" data-row="${itemCounter}">❌</button>
        </td>
    `;

    tbody.appendChild(tr);
    calculateRowTotal(rowId);
};

/* -----------------------------
   Calculate Row Total
------------------------------ */
const calculateRowTotal = (rowId) => {
    const row = document.getElementById(rowId);
    if (!row) return;

    const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
    const rate = parseFloat(row.querySelector('.item-rate')?.value || 0);
    const taxRate = parseFloat(row.querySelector('.item-tax')?.value || 0);

    const subtotal = qty * rate;
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + taxAmount;

    const totalCell = row.querySelector('.item-total');
    if (totalCell) totalCell.textContent = formatCurrency(total);
};

/* -----------------------------
   Calculate Totals
------------------------------ */
const calculateTotals = () => {
    let subtotal = 0;
    let totalTax = 0;

    const rows = qsa('#itemsTableBody tr');
    
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
        const rate = parseFloat(row.querySelector('.item-rate')?.value || 0);
        const taxRate = parseFloat(row.querySelector('.item-tax')?.value || 0);

        const rowSubtotal = qty * rate;
        const rowTax = rowSubtotal * (taxRate / 100);

        subtotal += rowSubtotal;
        totalTax += rowTax;

        const totalCell = row.querySelector('.item-total');
        if (totalCell) totalCell.textContent = formatCurrency(rowSubtotal + rowTax);
    });

    const grandTotal = subtotal + totalTax;

    if (qs('#subtotalDisplay')) qs('#subtotalDisplay').textContent = formatCurrency(subtotal);
    if (qs('#grandTotalDisplay')) qs('#grandTotalDisplay').textContent = formatCurrency(grandTotal);

    if (documentType === 'tax-invoice') {
        const clientSelect = qs('#clientSelect');
        const selectedOption = clientSelect?.options[clientSelect.selectedIndex];
        const clientStateCode = selectedOption?.dataset.state || '';
        const companyStateCode = companyProfile?.state_code || '24';

        const isSameState = clientStateCode === companyStateCode;

        if (isSameState) {
            const cgst = totalTax / 2;
            const sgst = totalTax / 2;

            if (qs('#cgstDisplay')) qs('#cgstDisplay').textContent = formatCurrency(cgst);
            if (qs('#sgstDisplay')) qs('#sgstDisplay').textContent = formatCurrency(sgst);
            if (qs('#igstDisplay')) qs('#igstDisplay').textContent = formatCurrency(0);

            if (qs('#cgstRow')) qs('#cgstRow').style.display = '';
            if (qs('#sgstRow')) qs('#sgstRow').style.display = '';
            if (qs('#igstRow')) qs('#igstRow').style.display = 'none';
            if (qs('#taxRow')) qs('#taxRow').style.display = 'none';
        } else {
            if (qs('#igstDisplay')) qs('#igstDisplay').textContent = formatCurrency(totalTax);
            if (qs('#cgstRow')) qs('#cgstRow').style.display = 'none';
            if (qs('#sgstRow')) qs('#sgstRow').style.display = 'none';
            if (qs('#igstRow')) qs('#igstRow').style.display = '';
            if (qs('#taxRow')) qs('#taxRow').style.display = 'none';
        }
    } else {
        if (qs('#taxDisplay')) qs('#taxDisplay').textContent = formatCurrency(totalTax);
        if (qs('#taxRow')) qs('#taxRow').style.display = documentType === 'quotation' ? '' : 'none';
    }
};

/* -----------------------------
   Event Listeners
------------------------------ */
const setupEventListeners = () => {
    document.addEventListener('click', (e) => {
        if (e.target.dataset.action === 'add-item') {
            addItemRow();
        }

        if (e.target.classList.contains('btn-remove')) {
            const tbody = qs('#itemsTableBody');
            if (tbody && tbody.children.length > 1) {
                const row = e.target.closest('tr');
                if (row) row.remove();
                calculateTotals();
            } else {
                showError('Need at least one item');
            }
        }

        if (e.target.classList.contains('btn-hsn')) {
            currentHsnRowId = e.target.dataset.row;
            showHsnModal();
        }

        if (e.target.dataset.action === 'save-document' || e.target.dataset.action === 'save-and-generate') {
            saveDocument();
        }

        if (e.target.dataset.action === 'save-draft') {
            saveDocument(true);
        }
    });

    document.addEventListener('input', (e) => {
        if (e.target.classList.contains('item-qty') ||
            e.target.classList.contains('item-rate') ||
            e.target.classList.contains('item-tax')) {
            const row = e.target.closest('tr');
            if (row) {
                calculateRowTotal(row.id);
                calculateTotals();
            }
        }

        if (e.target.id === 'hsnSearch') {
            const term = e.target.value.toLowerCase();
            qsa('.hsn-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(term) ? '' : 'none';
            });
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target.id === 'clientSelect') {
            calculateTotals();
        }
    });
};

/* -----------------------------
   HSN Modal
------------------------------ */
const showHsnModal = () => {
    const list = qs('#hsnList');
    if (!list) return;

    list.innerHTML = '';
    
    if (!hsnCodes.length) {
        list.innerHTML = '<div class="no-data">No HSN codes</div>';
    } else {
        hsnCodes.forEach(hsn => {
            const div = document.createElement('div');
            div.className = 'hsn-item';
            div.innerHTML = `
                <div class="hsn-code">${hsn.hsn_code}</div>
                <div class="hsn-desc">${hsn.description}</div>
                <div class="hsn-rate">GST: ${hsn.gst_rate}%</div>
            `;
            div.addEventListener('click', () => selectHsn(hsn.hsn_code, hsn.gst_rate));
            list.appendChild(div);
        });
    }

    openModal('hsnModal');
};

const selectHsn = (code, rate) => {
    if (!currentHsnRowId) return;

    const row = document.getElementById(`row-${currentHsnRowId}`);
    if (!row) return;

    const hsnInput = row.querySelector('.item-hsn');
    const taxInput = row.querySelector('.item-tax');

    if (hsnInput) hsnInput.value = code;
    if (taxInput) taxInput.value = rate;

    calculateRowTotal(row.id);
    calculateTotals();

    closeModal('hsnModal');
};

/* -----------------------------
   Save Document
------------------------------ */
const saveDocument = async (isDraft = false) => {
    try {
        const clientId = qs('#clientSelect')?.value;
        if (!clientId) {
            showError('Select a client');
            return;
        }

        const rows = qsa('#itemsTableBody tr');
        const items = [];
        
        rows.forEach(row => {
            const desc = row.querySelector('.item-description')?.value || '';
            const hsn = row.querySelector('.item-hsn')?.value || '';
            const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
            const unit = row.querySelector('.item-unit')?.value || 'Nos';
            const rate = parseFloat(row.querySelector('.item-rate')?.value || 0);
            const tax = parseFloat(row.querySelector('.item-tax')?.value || 0);

            if (desc && qty > 0 && rate > 0) {
                items.push({ description: desc, hsn_code: hsn, quantity: qty, unit, unit_price: rate, tax_rate: tax });
            }
        });

        if (!items.length) {
            showError('Add at least one item');
            return;
        }

        const formData = new FormData();
        formData.append('document_type', documentType);
        formData.append('client_id', clientId);
        formData.append('document_number', qs('#documentNumber')?.value || '');
        formData.append('document_date', qs('#documentDate')?.value || '');
        formData.append('notes', qs('#notes')?.value || '');
        formData.append('items', JSON.stringify(items));
        formData.append('status', isDraft ? 'Draft' : 'Sent');

        if (documentType === 'quotation') {
            formData.append('valid_until', qs('#validUntil')?.value || '');
            formData.append('terms', qs('#terms')?.value || '');
        }

        const endpoint = documentType === 'quotation'
            ? '/api/quotations/save_quotation.php'
            : '/api/invoices/save_invoice.php';

        await apiFetch(endpoint, { method: 'POST', body: formData });

        showSuccess(isDraft ? 'Saved as draft' : 'Document saved!');

        setTimeout(() => {
            window.location.href = documentType === 'quotation'
                ? '/Business%20project/public/index.php?page=quotations'
                : '/Business%20project/public/index.php?page=manage-invoice';
        }, 1500);

    } catch (err) {
        showError(err.message || 'Failed to save');
    }
};

console.log('[create-document.js] Loaded');