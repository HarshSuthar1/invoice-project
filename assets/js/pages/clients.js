import { qs, qsa } from '../core/dom.js';
import { openModal, closeModal, showSuccess, showError } from '../core/ui.js';
import { required, isEmail, isGST } from '../core/validators.js';
import { fetchClients, saveClient, deleteClient } from '../core/data/clients.js';

let clientsData = {};
let isEditMode = false;
let currentEditId = null;

/* ------------------------
   Load clients
------------------------ */
document.addEventListener('DOMContentLoaded', loadClients);

async function loadClients() {
  const tbody = qs('#clientTableBody');
  if (tbody) tbody.innerHTML = `<tr><td colspan="4" class="loading">Loading clients...</td></tr>`;
  try {
    const clients = await fetchClients();
    if (!tbody) return;

    tbody.innerHTML = '';
    clientsData = {};

    if (!clients.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="empty">No clients found</td></tr>`;
      return;
    }

    clients.forEach(c => {
      clientsData[c.id] = c;
      addRow(c);
    });

  } catch (err) {
    console.error(err);
    showError(err.message || 'Failed to load clients');
  }
}

/* ------------------------
   Render row
------------------------ */
function addRow(client) {
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><h4>${client.company_name}</h4><p>${client.contact_person || ''}</p></td>
    <td>${client.email || ''}<br><small>${client.phone || ''}</small></td>
    <td>${client.gst_number || ''}</td>
    <td>
      <button class="btn btn-view" data-action="view" data-id="${client.id}">View</button>
      <button class="btn btn-edit" data-action="edit" data-id="${client.id}">Edit</button>
      <button class="btn btn-delete" data-action="delete" data-id="${client.id}">Delete</button>
    </td>
  `;
  qs('#clientTableBody').appendChild(tr);
}

/* ------------------------
   Actions
------------------------ */
document.addEventListener('click', (e) => {
  const { action, id, target } = e.target.dataset;
  if (!action) return;

  if (action === 'add-client') {
    isEditMode = false;
    currentEditId = null;
    qs('#clientForm').reset();
    qs('#modalTitle').textContent = 'Add New Client';
    openModal('clientModal');
  }

  if (action === 'edit') {
    fillForm(id);
    isEditMode = true;
    currentEditId = id;
    qs('#modalTitle').textContent = 'Edit Client';
    openModal('clientModal');
  }

  if (action === 'view') {
    populateView(id);
    openModal('viewClientModal');
  }

  if (action === 'delete') handleDelete(id);

  if (action === 'edit-client') {
    const viewId = qs('#viewClientModal')?.dataset.viewId;
    if (viewId) {
      fillForm(viewId);
      isEditMode = true;
      currentEditId = viewId;
      closeModal('viewClientModal');
      openModal('clientModal');
    }
  }

  if (action === 'close-modal') {
    if (target) {
      closeModal(target);
    }
  }

  if (action === 'submit-client') {
    qs('#clientForm')?.requestSubmit ? qs('#clientForm').requestSubmit() : qs('#clientForm')?.dispatchEvent(new Event('submit', {cancelable: true}));
  }
});

// Search / Filter clients
qs('#searchInput')?.addEventListener('input', () => filterClients());

const filterClients = () => {
  const term = (qs('#searchInput')?.value || '').toLowerCase();
  qsa('#clientTableBody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
  });
};

/* ------------------------
   Save client - FIXED
------------------------ */
qs('#clientForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const email = qs('#email').value.trim();
  const gst = qs('#gstNumber').value.trim();

  if (!required(qs('#companyName').value)) return showError('Company name required');
  if (email && !isEmail(email)) return showError('Invalid email');
  if (gst && !isGST(gst)) return showError('Invalid GST format');

  // Create FormData with correct field names (snake_case for API)
  const formData = new FormData();
  formData.append('company_name', qs('#companyName').value.trim());
  formData.append('contact_person', qs('#contactPerson').value.trim());
  formData.append('email', email);
  formData.append('phone', qs('#phone').value.trim());
  formData.append('gst_number', gst);
  formData.append('address', qs('#address').value.trim());
  formData.append('notes', qs('#notes').value.trim());

  if (isEditMode && currentEditId) {
    formData.append('id', currentEditId);
  }

  const form = qs('#clientForm');
  const saveButtons = document.querySelectorAll('[data-action="submit-client"]');
  
  try {
    // UI: show loading
    form?.classList.add('loading');
    saveButtons.forEach(b => b.disabled = true);

    await saveClient(formData);
    closeModal('clientModal');
    showSuccess(isEditMode ? 'Client updated successfully' : 'Client added successfully');
    await loadClients();
    
    // Reset form and mode
    form.reset();
    isEditMode = false;
    currentEditId = null;
    
  } catch (err) {
    console.error(err);
    showError(err.message || 'Failed to save client');
  } finally {
    form?.classList.remove('loading');
    saveButtons.forEach(b => b.disabled = false);
  }
});

function populateView(id) {
  const c = clientsData[id];
  if (!c) return;
  qs('#viewCompanyName').textContent = c.company_name || '';
  qs('#viewContactPerson').textContent = c.contact_person || '';
  qs('#viewEmail').textContent = c.email || '';
  qs('#viewPhone').textContent = c.phone || '';
  qs('#viewGstNumber').textContent = c.gst_number || '';
  qs('#viewAddress').textContent = c.address || '';
  qs('#viewNotes').textContent = c.notes || '';
  qs('#viewClientModal').dataset.viewId = id;
}

function fillForm(id) {
  const c = clientsData[id];
  if (!c) return;
  qs('#companyName').value = c.company_name || '';
  qs('#contactPerson').value = c.contact_person || '';
  qs('#email').value = c.email || '';
  qs('#phone').value = c.phone || '';
  qs('#gstNumber').value = c.gst_number || '';
  qs('#address').value = c.address || '';
  qs('#notes').value = c.notes || '';
}

async function handleDelete(id) {
  if (!confirm('Delete this client? This action cannot be undone.')) return;
  try {
    await deleteClient(id);
    showSuccess('Client deleted successfully');
    loadClients();
  } catch (err) {
    console.error(err);
    showError(err.message || 'Failed to delete client');
  }
}