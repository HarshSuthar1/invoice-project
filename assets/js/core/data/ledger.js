import { apiFetch } from '../api.js';

export const fetchLedgerOverview = () =>
  apiFetch('/api/ledger/get_overview.php');

export const fetchClientLedger = ({ clientId, fromDate = '', toDate = '' }) => {
  const params = new URLSearchParams({ client_id: String(clientId) });

  if (fromDate) params.set('from_date', fromDate);
  if (toDate) params.set('to_date', toDate);

  return apiFetch(`/api/ledger/get_client_ledger.php?${params.toString()}`);
};

export const saveLedgerTransaction = (payload) =>
  apiFetch('/api/ledger/save_transaction.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
