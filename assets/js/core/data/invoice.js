import { apiFetch } from '../api.js';

// Caches - page lifecycle scoped
let _invoicesCache = null;
const _invoiceCache = new Map();

export const clearInvoicesCache = () => {
  _invoicesCache = null;
};

export const clearInvoiceCache = (id) => {
  _invoiceCache.delete(String(id));
};

// Fetch list of invoices (normalized)
export const fetchInvoices = async () => {
  if (_invoicesCache) return _invoicesCache;

  const res = await apiFetch('/api/invoices/get_invoices.php');

  let invoices = [];
  if (Array.isArray(res)) invoices = res;
  else if (res && Array.isArray(res.invoices)) invoices = res.invoices;
  else if (res && res.invoices === undefined && Array.isArray(res.data)) invoices = res.data; // defensive fallback
  else throw new Error('Invalid invoices response');

  _invoicesCache = invoices;
  return invoices;
};

// Save a new invoice
export const saveInvoice = async (formData) => {
  if (!(formData instanceof FormData)) {
    const fd = new FormData();
    Object.entries(formData).forEach(([k, v]) => fd.append(k, v));
    formData = fd;
  }

  const res = await apiFetch('/api/invoices/save_invoice.php', {
    method: 'POST',
    body: formData
  });

  // Invalidate list cache
  clearInvoicesCache();
  return res;
};

// Get invoice details normalized
export const fetchInvoice = async (id) => {
  if (!id) throw new Error('Missing invoice id');
  const cacheKey = String(id);
  if (_invoiceCache.has(cacheKey)) return _invoiceCache.get(cacheKey);

  const res = await apiFetch(`/api/invoices/get_invoice_details.php?id=${encodeURIComponent(id)}`);

  // Normalize expected shape: { invoice: {...}, items: [...] }
  const inv = res.invoice || res.data?.invoice || null;
  const items = Array.isArray(res.items) ? res.items : Array.isArray(res.data?.items) ? res.data.items : [];

  if (!inv) throw new Error('Invalid invoice response');

  const result = { invoice: inv, items };
  _invoiceCache.set(cacheKey, result);
  return result;
};

// Update invoice (e.g., edit fields, delete)
export const updateInvoice = async (payload) => {
  // Accept plain object or FormData
  let body = payload;
  if (!(payload instanceof FormData)) {
    const fd = new FormData();
    Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
    body = fd;
  }

  const res = await apiFetch('/api/invoices/update_invoice.php', {
    method: 'POST',
    body
  });

  // Invalidate caches
  clearInvoicesCache();
  if (payload.id) clearInvoiceCache(payload.id);
  return res;
};