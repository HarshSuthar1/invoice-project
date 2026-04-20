<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();
require_once __DIR__ . '/_helpers.php';

try {
    $clientId = (int) ($_GET['client_id'] ?? 0);
    if ($clientId <= 0) {
        ApiResponse::error('client_id is required.', 422);
    }

    $fromDate = ledger_parse_date($_GET['from_date'] ?? null, 'from_date');
    $toDate = ledger_parse_date($_GET['to_date'] ?? null, 'to_date');

    if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
        ApiResponse::error('from_date cannot be later than to_date.', 422);
    }

    ApiResponse::success(ledger_build_client_snapshot($conn, $clientId, $fromDate, $toDate));
} catch (Throwable $e) {
    error_log('Client Ledger API Error: ' . $e->getMessage());
    ApiResponse::error('Failed to load client ledger: ' . $e->getMessage(), 500);
}
