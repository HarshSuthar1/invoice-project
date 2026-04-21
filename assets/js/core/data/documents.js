import { apiFetch } from '../api.js';

const normalizeDocument = (doc) => ({
  ...doc,
  invoice_number: doc.invoice_number || doc.document_number || '',
  document_number: doc.document_number || doc.invoice_number || ''
});

let _documentsCache = null;
const _documentCache = new Map();

export const clearDocumentsCache = () => {
  _documentsCache = null;
};

export const clearDocumentCache = (id) => {
  _documentCache.delete(String(id));
};

export const fetchDocuments = async (type = 'all') => {
  if (type === 'all' && _documentsCache) return _documentsCache;

  const res = await apiFetch(`/api/documents/get_documents.php?type=${encodeURIComponent(type)}`);

  let documents = [];
  if (Array.isArray(res)) documents = res;
  else if (res && Array.isArray(res.documents)) documents = res.documents;
  else if (res && Array.isArray(res.data)) documents = res.data;
  else throw new Error('Invalid documents response');

  const normalized = documents.map(normalizeDocument);

  if (type === 'all') {
    _documentsCache = normalized;
  }

  return normalized;
};

export const fetchDocument = async (id) => {
  if (!id) throw new Error('Missing document id');

  const cacheKey = String(id);
  if (_documentCache.has(cacheKey)) return _documentCache.get(cacheKey);

  const res = await apiFetch(`/api/documents/get_document_details.php?id=${encodeURIComponent(id)}`);

  const doc = res.document || res.data?.document || null;
  const items = Array.isArray(res.items) ? res.items : Array.isArray(res.data?.items) ? res.data.items : [];

  if (!doc) throw new Error('Invalid document response');

  const result = {
    document: normalizeDocument(doc),
    items
  };

  _documentCache.set(cacheKey, result);
  return result;
};
