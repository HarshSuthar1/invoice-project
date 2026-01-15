import { apiFetch } from '../api.js';

// simple page-lifecycle cache keyed by serialized params
let _reportsCache = new Map();

export const clearReportsCache = () => {
  _reportsCache.clear();
};

export const fetchReports = async (params = {}) => {
  const qs = new URLSearchParams(params).toString();
  if (_reportsCache.has(qs)) return _reportsCache.get(qs);

  const res = await apiFetch(`/api/reports/get_reports_data.php?${qs}`);

  // Normalize to { summary: {}, invoices: [] }
  const summary = res.summary || res.data?.summary || {};
  const invoices = Array.isArray(res.invoices) ? res.invoices : Array.isArray(res.data?.invoices) ? res.data.invoices : [];

  const out = { summary, invoices };
  _reportsCache.set(qs, out);
  return out;
};