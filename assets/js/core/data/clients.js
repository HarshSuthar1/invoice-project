import { apiFetch } from '../api.js';

// In-memory cache scoped to page lifecycle
let _clientsCache = null;

export const clearClientsCache = () => {
  _clientsCache = null;
};

// Fetch clients and normalize response
export const fetchClients = async () => {
  if (_clientsCache) return _clientsCache;

  const res = await apiFetch('/api/clients/get_clients.php');

  // Normalize possible shapes
  let clients = [];
  if (Array.isArray(res)) clients = res;
  else if (res && Array.isArray(res.clients)) clients = res.clients;
  else throw new Error('Invalid clients response');

  _clientsCache = clients;
  return clients;
};

// Save a client. Accepts FormData or POJO (converted to FormData)
export const saveClient = async (formData) => {
  // Allow passing plain object
  let body = formData;
  if (!(formData instanceof FormData)) {
    body = new FormData();
    Object.entries(formData).forEach(([k, v]) => body.append(k, v));
  }

  const res = await apiFetch('/api/clients/save_clients.php', {
    method: 'POST',
    body
  });

  // Invalidate cache after mutation
  clearClientsCache();
  return res; // keep full returned payload for callers
};

// Delete client
export const deleteClient = async (id) => {
  if (!id) throw new Error('Missing id');
  const fd = new FormData();
  fd.append('id', id);
  fd.append('delete', '1');

  const res = await apiFetch('/api/clients/save_clients.php', {
    method: 'POST',
    body: fd
  });

  clearClientsCache();
  return res;
};