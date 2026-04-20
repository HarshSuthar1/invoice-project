<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();
require_once __DIR__ . '/_helpers.php';

try {
    $payload = ledger_request_payload();
    $entryType = strtolower(trim((string) ledger_value($payload, 'entry_type', '')));
    $clientId = (int) ledger_value($payload, 'client_id', 0);

    if ($clientId <= 0) {
        ApiResponse::error('client_id is required.', 422);
    }

    ledger_fetch_client($conn, $clientId);

    $nowIso = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

    switch ($entryType) {
        case 'payment':
            $transactionDate = ledger_parse_date(ledger_value($payload, 'transaction_date'), 'transaction_date', true);
            $amount = ledger_positive_amount(ledger_value($payload, 'amount'), 'amount');
            $paymentMode = trim((string) ledger_value($payload, 'payment_mode', ''));
            $referenceNo = trim((string) ledger_value($payload, 'reference_no', ''));
            $notes = trim((string) ledger_value($payload, 'notes', ''));
            $allocations = ledger_normalize_allocations($conn, $clientId, (array) ledger_value($payload, 'allocations', []));

            if (abs($allocations['total'] - $amount) > 0.01) {
                ApiResponse::error('Payment amount must match the allocated invoice total.', 422);
            }

            $transaction = [
                'id' => ledger_generate_id(),
                'client_id' => $clientId,
                'type' => 'payment',
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'payment_mode' => $paymentMode,
                'reference_no' => $referenceNo,
                'notes' => $notes,
                'allocations' => $allocations['allocations'],
                'created_at' => $nowIso,
                'updated_at' => $nowIso,
            ];

            $conn->begin_transaction();
            ledger_apply_allocations($conn, $clientId, $allocations['allocations']);
            ledger_append_transaction($transaction);
            $conn->commit();

            ApiResponse::success([
                'transaction' => $transaction,
                'ledger' => ledger_build_client_snapshot($conn, $clientId),
            ], 'Payment recorded successfully.');
            break;

        case 'advance':
            $transactionDate = ledger_parse_date(ledger_value($payload, 'transaction_date'), 'transaction_date', true);
            $amount = ledger_positive_amount(ledger_value($payload, 'amount'), 'amount');

            $transaction = [
                'id' => ledger_generate_id(),
                'client_id' => $clientId,
                'type' => 'advance',
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'payment_mode' => trim((string) ledger_value($payload, 'payment_mode', '')),
                'reference_no' => trim((string) ledger_value($payload, 'reference_no', '')),
                'notes' => trim((string) ledger_value($payload, 'notes', '')),
                'allocations' => [],
                'created_at' => $nowIso,
                'updated_at' => $nowIso,
            ];

            ledger_append_transaction($transaction);

            ApiResponse::success([
                'transaction' => $transaction,
                'ledger' => ledger_build_client_snapshot($conn, $clientId),
            ], 'Advance recorded successfully.');
            break;

        case 'adjustment_credit':
        case 'adjustment_debit':
            $transactionDate = ledger_parse_date(ledger_value($payload, 'transaction_date'), 'transaction_date', true);
            $amount = ledger_positive_amount(ledger_value($payload, 'amount'), 'amount');

            $transaction = [
                'id' => ledger_generate_id(),
                'client_id' => $clientId,
                'type' => $entryType,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'payment_mode' => '',
                'reference_no' => trim((string) ledger_value($payload, 'reference_no', '')),
                'notes' => trim((string) ledger_value($payload, 'notes', '')),
                'allocations' => [],
                'created_at' => $nowIso,
                'updated_at' => $nowIso,
            ];

            ledger_append_transaction($transaction);

            ApiResponse::success([
                'transaction' => $transaction,
                'ledger' => ledger_build_client_snapshot($conn, $clientId),
            ], 'Adjustment recorded successfully.');
            break;

        case 'apply_credit':
            $sourceTransactionId = trim((string) ledger_value($payload, 'source_transaction_id', ''));
            if ($sourceTransactionId === '') {
                ApiResponse::error('source_transaction_id is required.', 422);
            }

            $allocations = ledger_normalize_allocations($conn, $clientId, (array) ledger_value($payload, 'allocations', []));

            $conn->begin_transaction();
            ledger_apply_allocations($conn, $clientId, $allocations['allocations']);
            $updatedAdvance = ledger_apply_advance_to_store($clientId, $sourceTransactionId, $allocations['allocations']);
            $conn->commit();

            ApiResponse::success([
                'advance' => $updatedAdvance,
                'ledger' => ledger_build_client_snapshot($conn, $clientId),
            ], 'Advance applied successfully.');
            break;

        default:
            ApiResponse::error('Unsupported entry_type supplied.', 422);
    }
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli && $conn->errno === 0) {
        try {
            $conn->rollback();
        } catch (Throwable) {
        }
    }

    error_log('Ledger Save Transaction API Error: ' . $e->getMessage());
    ApiResponse::error('Failed to save ledger transaction: ' . $e->getMessage(), 500);
}
