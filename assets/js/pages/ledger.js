import { qs, qsa } from '../core/dom.js';
import { closeModal, openModal, showError, showSuccess } from '../core/ui.js';
import { formatCurrency } from '../core/utils.js';
import { fetchClientLedger, fetchLedgerOverview, saveLedgerTransaction } from '../core/data/ledger.js';

const state = {
  overview: [],
  ledger: null,
  selectedClientId: null,
};

const today = new Date().toISOString().slice(0, 10);

document.addEventListener('DOMContentLoaded', async () => {
  bindEvents();
  qs('#transactionDate').value = today;
  await loadOverview();
  await initializeSelection();
});

function bindEvents() {
  qs('#applyFiltersBtn')?.addEventListener('click', loadLedgerForSelectedClient);
  qs('#clientSelect')?.addEventListener('change', async (event) => {
    state.selectedClientId = event.target.value || null;
    syncQueryString();
    updateStatementLink();
    await loadLedgerForSelectedClient();
  });

  document.addEventListener('click', (event) => {
    const action = event.target.dataset.action;
    const modalTarget = event.target.dataset.target;
    const openTransactionType = event.target.dataset.openTransaction;
    const quickOpenClientId = event.target.dataset.clientId;

    if (modalTarget && action === 'close-modal') {
      closeModal(modalTarget);
      return;
    }

    if (openTransactionType) {
      openTransactionModal(openTransactionType);
      return;
    }

    if (event.target.hasAttribute('data-open-apply-advance')) {
      openApplyAdvanceModal();
      return;
    }

    if (quickOpenClientId) {
      state.selectedClientId = quickOpenClientId;
      qs('#clientSelect').value = quickOpenClientId;
      syncQueryString();
      updateStatementLink();
      loadLedgerForSelectedClient();
    }
  });

  qs('#saveTransactionBtn')?.addEventListener('click', saveTransaction);
  qs('#applyAdvanceBtn')?.addEventListener('click', applyAdvance);

  document.addEventListener('input', (event) => {
    if (event.target.matches('.allocation-input')) {
      syncPaymentAllocationSummary();
    }

    if (event.target.matches('.advance-allocation-input')) {
      syncAdvanceAllocationSummary();
    }
  });

  qs('#advanceSource')?.addEventListener('change', syncAdvanceAllocationSummary);
}

async function initializeSelection() {
  const params = new URLSearchParams(window.location.search);
  const clientIdFromUrl = params.get('client_id');
  const fallbackClient = clientIdFromUrl || state.overview[0]?.client_id || '';

  if (!fallbackClient) {
    updateStatementLink();
    return;
  }

  state.selectedClientId = String(fallbackClient);
  qs('#clientSelect').value = state.selectedClientId;
  updateStatementLink();
  await loadLedgerForSelectedClient();
}

async function loadOverview() {
  const tbody = qs('#overviewTableBody');
  const select = qs('#clientSelect');

  tbody.innerHTML = '<tr><td colspan="6" class="loading">Loading client balances...</td></tr>';

  try {
    const response = await fetchLedgerOverview();
    state.overview = response.clients || [];

    select.innerHTML = '<option value="">Select a client</option>';
    state.overview.forEach((client) => {
      const option = document.createElement('option');
      option.value = client.client_id;
      option.textContent = client.client_name;
      select.appendChild(option);
    });

    renderOverviewTable();
  } catch (error) {
    console.error(error);
    tbody.innerHTML = '<tr><td colspan="6" class="error">Failed to load client balances.</td></tr>';
    showError(error.message || 'Failed to load ledger overview');
  }
}

function renderOverviewTable() {
  const tbody = qs('#overviewTableBody');

  if (!state.overview.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="loading">No clients found.</td></tr>';
    return;
  }

  tbody.innerHTML = state.overview.map((client) => `
    <tr>
      <td>${escapeHtml(client.client_name)}</td>
      <td>${client.invoice_count}</td>
      <td class="text-right">${formatCurrency(client.outstanding_amount)}</td>
      <td class="text-right">${formatCurrency(client.unapplied_advance)}</td>
      <td class="text-right">${formatCurrency(client.net_balance)}</td>
      <td><button class="quick-open-btn" data-client-id="${client.client_id}">Open Ledger</button></td>
    </tr>
  `).join('');
}

async function loadLedgerForSelectedClient() {
  if (!state.selectedClientId) {
    resetLedgerPanels('Select a client to view ledger entries.');
    return;
  }

  const tbody = qs('#ledgerTableBody');
  tbody.innerHTML = '<tr><td colspan="7" class="loading">Loading ledger...</td></tr>';
  qs('#ledgerMeta').textContent = 'Loading client ledger...';
  qs('#openInvoicesList').innerHTML = '<div class="loading">Loading open invoices...</div>';
  qs('#availableAdvancesList').innerHTML = '<div class="loading">Loading advances...</div>';

  try {
    state.ledger = await fetchClientLedger({
      clientId: state.selectedClientId,
      fromDate: qs('#fromDate').value,
      toDate: qs('#toDate').value,
    });

    renderLedgerSummary();
    renderLedgerEntries();
    renderOpenInvoices();
    renderAvailableAdvances();
    updateStatementLink();
  } catch (error) {
    console.error(error);
    resetLedgerPanels('Failed to load ledger entries.');
    showError(error.message || 'Failed to load client ledger');
  }
}

function resetLedgerPanels(message) {
  state.ledger = null;
  qs('#ledgerMeta').textContent = message;
  qs('#ledgerTableBody').innerHTML = `<tr><td colspan="7" class="loading">${escapeHtml(message)}</td></tr>`;
  qs('#openInvoicesList').innerHTML = `<div class="loading">${escapeHtml(message)}</div>`;
  qs('#availableAdvancesList').innerHTML = `<div class="loading">${escapeHtml(message)}</div>`;
  qs('#openingBalance').textContent = formatCurrency(0);
  qs('#currentOutstanding').textContent = formatCurrency(0);
  qs('#unappliedAdvance').textContent = formatCurrency(0);
  qs('#currentBalance').textContent = formatCurrency(0);
  qs('#openInvoiceCount').textContent = '0 open invoices';
  qs('#overdueAmount').textContent = `${formatCurrency(0)} overdue`;
}

function renderLedgerSummary() {
  const { client, summary, filters } = state.ledger;
  qs('#openingBalance').textContent = formatCurrency(summary.opening_balance);
  qs('#currentOutstanding').textContent = formatCurrency(summary.current_outstanding);
  qs('#unappliedAdvance').textContent = formatCurrency(summary.unapplied_advance);
  qs('#currentBalance').textContent = formatCurrency(summary.current_balance);
  qs('#openInvoiceCount').textContent = `${summary.open_invoice_count} open invoice${summary.open_invoice_count === 1 ? '' : 's'}`;
  qs('#overdueAmount').textContent = `${formatCurrency(summary.overdue_amount)} overdue`;

  const rangeText = filters.from_date || filters.to_date
    ? `Showing entries from ${filters.from_date || 'start'} to ${filters.to_date || 'today'}`
    : 'Showing full ledger history';

  qs('#ledgerMeta').textContent = `${client.company_name} • ${rangeText}`;
}

function renderLedgerEntries() {
  const tbody = qs('#ledgerTableBody');
  const entries = state.ledger?.entries || [];

  if (!entries.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="loading">No ledger entries found for the selected range.</td></tr>';
    return;
  }

  tbody.innerHTML = entries.map((entry) => {
    const chipClass = entry.credit > 0 ? 'credit' : 'debit';
    return `
      <tr>
        <td>${escapeHtml(entry.date)}</td>
        <td>
          <div class="entry-chip ${chipClass}">${escapeHtml(entry.label)}</div>
          <div class="entry-notes">${escapeHtml(entry.notes || 'No notes')}</div>
        </td>
        <td>${escapeHtml(entry.reference || '—')}</td>
        <td>${escapeHtml(entry.payment_mode || '—')}</td>
        <td class="text-right">${entry.debit ? formatCurrency(entry.debit) : '—'}</td>
        <td class="text-right">${entry.credit ? formatCurrency(entry.credit) : '—'}</td>
        <td class="text-right">${formatCurrency(entry.running_balance)}</td>
      </tr>
    `;
  }).join('');
}

function renderOpenInvoices() {
  const container = qs('#openInvoicesList');
  const invoices = state.ledger?.open_invoices || [];

  if (!invoices.length) {
    container.innerHTML = '<div class="loading">No open invoices for this client.</div>';
    return;
  }

  container.innerHTML = invoices.map((invoice) => `
    <div class="panel-card">
      <div class="panel-card-header">
        <div>
          <h4>${escapeHtml(invoice.invoice_number)}</h4>
          <p>Due ${escapeHtml(invoice.due_date || invoice.invoice_date)} • ${invoice.days_overdue > 0 ? `${invoice.days_overdue} days overdue` : 'Not overdue'}</p>
        </div>
        <div class="panel-amount ${invoice.days_overdue > 0 ? 'overdue' : ''}">${formatCurrency(invoice.pending_amount)}</div>
      </div>
      <p>Received ${formatCurrency(invoice.amount_received)} of ${formatCurrency(invoice.grand_total)}</p>
    </div>
  `).join('');
}

function renderAvailableAdvances() {
  const container = qs('#availableAdvancesList');
  const advances = state.ledger?.available_advances || [];

  if (!advances.length) {
    container.innerHTML = '<div class="loading">No unapplied advances available.</div>';
    return;
  }

  container.innerHTML = advances.map((advance) => `
    <div class="panel-card">
      <div class="panel-card-header">
        <div>
          <h4>${escapeHtml(advance.payment_mode || 'Advance Entry')}</h4>
          <p>${escapeHtml(advance.transaction_date)} ${advance.reference_no ? `• ${escapeHtml(advance.reference_no)}` : ''}</p>
        </div>
        <div class="panel-amount">${formatCurrency(advance.available_amount)}</div>
      </div>
      <p>${escapeHtml(advance.notes || 'Available to apply against future invoices')}</p>
    </div>
  `).join('');
}

function openTransactionModal(type) {
  if (!state.selectedClientId) {
    showError('Select a client first.');
    return;
  }

  const titles = {
    payment: 'Record Payment',
    advance: 'Record Advance',
    adjustment_credit: 'Add Credit Adjustment',
    adjustment_debit: 'Add Debit Adjustment',
  };

  qs('#transactionModalTitle').textContent = titles[type] || 'Record Ledger Entry';
  qs('#transactionType').value = type;
  qs('#transactionForm').reset();
  qs('#transactionDate').value = today;
  qs('#transactionAmount').value = '';
  qs('#referenceNo').value = '';
  qs('#transactionNotes').value = '';

  const paymentFieldsVisible = type === 'payment' || type === 'advance';
  qsa('.transaction-payment-fields').forEach((field) => {
    field.style.display = paymentFieldsVisible ? '' : 'none';
  });

  const allocationGroup = qs('#paymentAllocationsGroup');
  if (type === 'payment') {
    allocationGroup.classList.remove('is-hidden');
    renderPaymentAllocationInputs();
    syncPaymentAllocationSummary();
  } else {
    allocationGroup.classList.add('is-hidden');
    qs('#paymentAllocationsList').innerHTML = '';
    qs('#allocationSummary').textContent = `Allocated: ${formatCurrency(0)}`;
  }

  openModal('transactionModal');
}

function renderPaymentAllocationInputs() {
  const invoices = state.ledger?.open_invoices || [];
  const container = qs('#paymentAllocationsList');

  if (!invoices.length) {
    container.innerHTML = '<div class="loading">No open invoices available for allocation.</div>';
    return;
  }

  container.innerHTML = invoices.map((invoice) => `
    <div class="allocation-row">
      <div>
        <h4>${escapeHtml(invoice.invoice_number)}</h4>
        <p>Pending ${formatCurrency(invoice.pending_amount)} • Due ${escapeHtml(invoice.due_date || invoice.invoice_date)}</p>
      </div>
      <input
        class="allocation-input"
        type="number"
        min="0"
        max="${invoice.pending_amount}"
        step="0.01"
        data-invoice-id="${invoice.invoice_id}"
        data-invoice-number="${escapeHtml(invoice.invoice_number)}"
        placeholder="0.00"
      >
    </div>
  `).join('');
}

function syncPaymentAllocationSummary() {
  const allocations = collectAllocations('.allocation-input');
  const total = allocations.reduce((sum, item) => sum + item.amount, 0);
  qs('#allocationSummary').textContent = `Allocated: ${formatCurrency(total)}`;

  if (qs('#transactionType').value === 'payment') {
    qs('#transactionAmount').value = total ? total.toFixed(2) : '';
  }
}

async function saveTransaction() {
  if (!state.selectedClientId) {
    showError('Select a client first.');
    return;
  }

  const entryType = qs('#transactionType').value;
  const payload = {
    entry_type: entryType,
    client_id: Number(state.selectedClientId),
    transaction_date: qs('#transactionDate').value,
    amount: Number(qs('#transactionAmount').value || 0),
    payment_mode: qs('#paymentMode').value,
    reference_no: qs('#referenceNo').value.trim(),
    notes: qs('#transactionNotes').value.trim(),
  };

  if (!payload.transaction_date) {
    showError('Transaction date is required.');
    return;
  }

  if (payload.amount <= 0) {
    showError('Enter a valid amount.');
    return;
  }

  if (entryType === 'payment') {
    payload.allocations = collectAllocations('.allocation-input');
    if (!payload.allocations.length) {
      showError('Add at least one invoice allocation.');
      return;
    }
  }

  try {
    const response = await saveLedgerTransaction(payload);
    state.ledger = response.ledger;
    closeModal('transactionModal');
    showSuccess(response.message || 'Ledger entry saved successfully.');
    await loadOverview();
    renderLedgerSummary();
    renderLedgerEntries();
    renderOpenInvoices();
    renderAvailableAdvances();
  } catch (error) {
    console.error(error);
    showError(error.message || 'Failed to save ledger entry');
  }
}

function openApplyAdvanceModal() {
  if (!state.selectedClientId) {
    showError('Select a client first.');
    return;
  }

  const advances = state.ledger?.available_advances || [];
  const invoices = state.ledger?.open_invoices || [];

  if (!advances.length) {
    showError('No available advances to apply.');
    return;
  }

  if (!invoices.length) {
    showError('This client has no open invoices to apply the advance against.');
    return;
  }

  qs('#advanceSource').innerHTML = advances.map((advance) => `
    <option value="${advance.id}" data-available="${advance.available_amount}">
      ${advance.transaction_date} • ${formatCurrency(advance.available_amount)} • ${escapeHtml(advance.reference_no || advance.payment_mode || 'Advance')}
    </option>
  `).join('');

  qs('#applyAdvanceAllocationsList').innerHTML = invoices.map((invoice) => `
    <div class="allocation-row">
      <div>
        <h4>${escapeHtml(invoice.invoice_number)}</h4>
        <p>Pending ${formatCurrency(invoice.pending_amount)} • Due ${escapeHtml(invoice.due_date || invoice.invoice_date)}</p>
      </div>
      <input
        class="advance-allocation-input"
        type="number"
        min="0"
        max="${invoice.pending_amount}"
        step="0.01"
        data-invoice-id="${invoice.invoice_id}"
        data-invoice-number="${escapeHtml(invoice.invoice_number)}"
        placeholder="0.00"
      >
    </div>
  `).join('');

  syncAdvanceAllocationSummary();
  openModal('applyAdvanceModal');
}

function syncAdvanceAllocationSummary() {
  const allocations = collectAllocations('.advance-allocation-input');
  const total = allocations.reduce((sum, item) => sum + item.amount, 0);
  const selectedOption = qs('#advanceSource')?.selectedOptions?.[0];
  const available = Number(selectedOption?.dataset.available || 0);
  qs('#applyAdvanceSummary').textContent = `Allocated: ${formatCurrency(total)} of ${formatCurrency(available)}`;
}

async function applyAdvance() {
  if (!state.selectedClientId) {
    showError('Select a client first.');
    return;
  }

  const sourceTransactionId = qs('#advanceSource').value;
  const allocations = collectAllocations('.advance-allocation-input');
  const available = Number(qs('#advanceSource').selectedOptions?.[0]?.dataset.available || 0);
  const requested = allocations.reduce((sum, item) => sum + item.amount, 0);

  if (!sourceTransactionId) {
    showError('Choose an advance entry to apply.');
    return;
  }

  if (!allocations.length) {
    showError('Enter at least one allocation amount.');
    return;
  }

  if (requested > available + 0.01) {
    showError('Allocation exceeds the selected advance balance.');
    return;
  }

  try {
    const response = await saveLedgerTransaction({
      entry_type: 'apply_credit',
      client_id: Number(state.selectedClientId),
      source_transaction_id: sourceTransactionId,
      allocations,
    });

    state.ledger = response.ledger;
    closeModal('applyAdvanceModal');
    showSuccess(response.message || 'Advance applied successfully.');
    await loadOverview();
    renderLedgerSummary();
    renderLedgerEntries();
    renderOpenInvoices();
    renderAvailableAdvances();
  } catch (error) {
    console.error(error);
    showError(error.message || 'Failed to apply advance');
  }
}

function collectAllocations(selector) {
  return qsa(selector)
    .map((input) => ({
      invoice_id: Number(input.dataset.invoiceId),
      invoice_number: input.dataset.invoiceNumber,
      amount: Number(input.value || 0),
    }))
    .filter((allocation) => allocation.invoice_id > 0 && allocation.amount > 0);
}

function updateStatementLink() {
  const link = qs('#statementLink');
  if (!link || !state.selectedClientId) {
    if (link) {
      link.href = '#';
      link.setAttribute('aria-disabled', 'true');
    }
    return;
  }

  const params = new URLSearchParams({
    client_id: String(state.selectedClientId),
    format: 'pdf',
  });

  if (qs('#fromDate').value) params.set('date_from', qs('#fromDate').value);
  if (qs('#toDate').value) params.set('date_to', qs('#toDate').value);

  link.href = `/Business%20project/api/reports/client_statement.php?${params.toString()}`;
  link.removeAttribute('aria-disabled');
}

function syncQueryString() {
  const url = new URL(window.location.href);
  if (state.selectedClientId) {
    url.searchParams.set('page', 'ledger');
    url.searchParams.set('client_id', state.selectedClientId);
  } else {
    url.searchParams.delete('client_id');
  }

  window.history.replaceState({}, '', url.toString());
}

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
