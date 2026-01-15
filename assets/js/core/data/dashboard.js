import { apiFetch } from '../api.js';

let _dashboardCache = null;
export const clearDashboardCache = () => { _dashboardCache = null; };

export const fetchDashboard = async () => {
  if (_dashboardCache) return _dashboardCache;

  const res = await apiFetch('/api/dashboard/get_dashboard_data.php');

  // Normalize
  const stats = res.stats || {
    total_outstanding: 0,
    total_received: 0,
    unpaid_count: 0
  };

  const recent_invoices = Array.isArray(res.recent_invoices) ? res.recent_invoices : [];

  const payment_status = res.payment_status || { paid: 0, unpaid: 0, partial: 0 };

  // Basic validation
  if (typeof stats.total_outstanding === 'undefined' || typeof stats.total_received === 'undefined') {
    throw new Error('Invalid dashboard stats');
  }

  _dashboardCache = { stats, recent_invoices, payment_status };
  return _dashboardCache;
};