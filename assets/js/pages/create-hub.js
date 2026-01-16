/*
create-hub.js - Unified Document Creation Hub
Handles document type selection and routing
*/

import { qs } from '../core/dom.js';
import { showError, showSuccess, openModal, closeModal } from '../core/ui.js';
import { apiFetch } from '../core/api.js';
import { formatCurrency } from '../core/utils.js';

let selectedQuotationId = null;
let pendingDocumentType = null;

/* -----------------------------
   Load quick stats
------------------------------ */
const loadQuickStats = async () => {
    try {
        const data = await apiFetch('/api/dashboard/get_dashboard_data.php');
        
        // You'll need to add a new API endpoint for create hub stats
        // For now, using dashboard data as placeholder
        
        const quotationsEl = qs('#quotationsThisMonth');
        const invoicesEl = qs('#invoicesThisMonth');
        const pendingEl = qs('#pendingQuotations');
        const rateEl = qs('#conversionRate');

        // Placeholder values - will be replaced with actual stats
        if (quotationsEl) quotationsEl.textContent = '0';
        if (invoicesEl) invoicesEl.textContent = '0';
        if (pendingEl) pendingEl.textContent = '0';
        if (rateEl) rateEl.textContent = '0%';

    } catch (err) {
        console.error('[create-hub.js] Stats error:', err);
        // Don't show error for stats, it's not critical
    }
};

/* -----------------------------
   Document creation handlers
------------------------------ */
document.addEventListener('click', async (e) => {
    const action = e.target.dataset.action;
    if (!action) return;

    switch (action) {
        case 'create-quotation':
            createQuotation();
            break;

        case 'create-tax-invoice':
            await handleTaxInvoiceCreation();
            break;

        case 'create-bill-of-supply':
            await handleBillOfSupplyCreation();
            break;

        case 'create-delivery-challan':
            createDeliveryChallan();
            break;

        case 'close-modal':
            const targetModal = e.target.dataset.target;
            if (targetModal) {
                closeModal(targetModal);
                selectedQuotationId = null;
                pendingDocumentType = null;
            }
            break;

        case 'select-quotation':
            const quotationId = e.target.dataset.id;
            selectQuotation(quotationId);
            break;

        case 'confirm-quotation-selection':
            confirmQuotationSelection();
            break;
    }
});

/* -----------------------------
   Create Quotation
------------------------------ */
const createQuotation = () => {
    console.log('[create-hub.js] Creating new quotation');
    window.location.href = '/Business%20project/public/index.php?page=create&type=quotation';
};

/* -----------------------------
   Create Tax Invoice (with optional quotation)
------------------------------ */
const handleTaxInvoiceCreation = async () => {
    const fromQuotation = qs('#fromQuotationGST')?.checked;

    if (fromQuotation) {
        // Show quotation selection modal
        pendingDocumentType = 'tax-invoice';
        await loadApprovedQuotations();
        openModal('selectQuotationModal');
    } else {
        // Create new tax invoice directly
        console.log('[create-hub.js] Creating new tax invoice');
        window.location.href = '/Business%20project/public/index.php?page=create&type=tax-invoice';
    }
};

/* -----------------------------
   Create Bill of Supply (with optional quotation)
------------------------------ */
const handleBillOfSupplyCreation = async () => {
    const fromQuotation = qs('#fromQuotationNoGST')?.checked;

    if (fromQuotation) {
        // Show quotation selection modal
        pendingDocumentType = 'bill-of-supply';
        await loadApprovedQuotations();
        openModal('selectQuotationModal');
    } else {
        // Create new bill of supply directly
        console.log('[create-hub.js] Creating new bill of supply');
        window.location.href = '/Business%20project/public/index.php?page=create&type=bill-of-supply';
    }
};

/* -----------------------------
   Create Delivery Challan
------------------------------ */
const createDeliveryChallan = () => {
    console.log('[create-hub.js] Creating delivery challan');
    window.location.href = '/Business%20project/public/index.php?page=create&type=delivery-challan';
};

/* -----------------------------
   Load approved quotations
------------------------------ */
const loadApprovedQuotations = async () => {
    const listContainer = qs('#quotationList');
    if (!listContainer) return;

    try {
        listContainer.innerHTML = '<div class="loading">Loading quotations...</div>';

        const data = await apiFetch('/api/quotations/get_quotations.php');
        const quotations = Array.isArray(data) ? data : (data.quotations || []);

        // Filter for Approved or Sent quotations only
        const availableQuotations = quotations.filter(q => 
            q.status === 'Approved' || q.status === 'Sent'
        );

        if (!availableQuotations.length) {
            listContainer.innerHTML = `
                <div class="no-data">
                    <p>No approved quotations available to convert.</p>
                    <button class="btn btn-primary" onclick="window.location.href='/Business%20project/public/index.php?page=create&type=quotation'">
                        Create New Quotation First
                    </button>
                </div>
            `;
            return;
        }

        // Render quotation list
        let html = '';
        availableQuotations.forEach(q => {
            html += `
                <div class="quotation-item" data-action="select-quotation" data-id="${q.id}">
                    <div class="quotation-item-header">
                        <span class="quotation-number">${q.quotation_number}</span>
                        <span class="quotation-amount">${formatCurrency(q.grand_total)}</span>
                    </div>
                    <div class="quotation-client">${q.client_name}</div>
                    <div class="quotation-date">
                        Date: ${formatDate(q.quotation_date)} | 
                        Valid Until: ${formatDate(q.valid_until)}
                    </div>
                </div>
            `;
        });

        html += `
            <div style="margin-top: 20px; text-align: right;">
                <button class="btn btn-secondary" data-action="close-modal" data-target="selectQuotationModal" style="margin-right: 10px;">
                    Cancel
                </button>
                <button class="btn btn-primary" data-action="confirm-quotation-selection" id="confirmQuotationBtn" disabled>
                    Confirm Selection
                </button>
            </div>
        `;

        listContainer.innerHTML = html;

    } catch (err) {
        console.error('[create-hub.js] Error loading quotations:', err);
        listContainer.innerHTML = `
            <div class="error">
                Failed to load quotations. Please try again.
            </div>
        `;
    }
};

/* -----------------------------
   Select quotation from list
------------------------------ */
const selectQuotation = (id) => {
    selectedQuotationId = id;

    // Update UI - highlight selected
    const items = document.querySelectorAll('.quotation-item');
    items.forEach(item => {
        if (item.dataset.id === id) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });

    // Enable confirm button
    const confirmBtn = qs('#confirmQuotationBtn');
    if (confirmBtn) {
        confirmBtn.disabled = false;
    }
};

/* -----------------------------
   Confirm quotation selection and proceed
------------------------------ */
const confirmQuotationSelection = () => {
    if (!selectedQuotationId || !pendingDocumentType) {
        showError('Please select a quotation');
        return;
    }

    console.log(`[create-hub.js] Converting quotation ${selectedQuotationId} to ${pendingDocumentType}`);

    // Route to create page with quotation ID
    const url = `/Business%20project/public/index.php?page=create&type=${pendingDocumentType}&from_quotation=${selectedQuotationId}`;
    window.location.href = url;
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
   Initialize
------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
    console.log('[create-hub.js] Document hub loaded');
    loadQuickStats();
});