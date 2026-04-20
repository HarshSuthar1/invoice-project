<?php
declare(strict_types=1);

function ledger_request_payload(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function ledger_value(array $payload, string $key, mixed $default = null): mixed
{
    return array_key_exists($key, $payload) ? $payload[$key] : $default;
}

function ledger_parse_date(mixed $value, string $fieldName, bool $required = false): ?string
{
    if (!is_string($value) || trim($value) === '') {
        if ($required) {
            ApiResponse::error("{$fieldName} is required.", 422);
        }

        return null;
    }

    $value = trim($value);
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if (
        !$date ||
        ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))
    ) {
        ApiResponse::error("Invalid {$fieldName}. Expected format: YYYY-MM-DD.", 422);
    }

    return $date->format('Y-m-d');
}

function ledger_positive_amount(mixed $value, string $fieldName): float
{
    if (!is_numeric($value)) {
        ApiResponse::error("{$fieldName} must be a valid amount.", 422);
    }

    $amount = round((float) $value, 2);
    if ($amount <= 0) {
        ApiResponse::error("{$fieldName} must be greater than zero.", 422);
    }

    return $amount;
}

function ledger_round(float $amount): float
{
    return round($amount, 2);
}

function ledger_storage_path(): string
{
    return BASE_PATH . '/app/storage/ledger_transactions.json';
}

function ledger_read_store(): array
{
    $path = ledger_storage_path();
    $directory = dirname($path);

    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to initialize ledger storage directory.');
    }

    if (!is_file($path)) {
        $initial = ['transactions' => []];
        file_put_contents($path, json_encode($initial, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $initial;
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return ['transactions' => []];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['transactions' => []];
    }

    if (!isset($decoded['transactions']) || !is_array($decoded['transactions'])) {
        $decoded['transactions'] = [];
    }

    return $decoded;
}

function ledger_write_store(array $store): void
{
    $encoded = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new RuntimeException('Failed to encode ledger storage data.');
    }

    $written = file_put_contents(ledger_storage_path(), $encoded, LOCK_EX);
    if ($written === false) {
        throw new RuntimeException('Failed to write ledger storage data.');
    }
}

function ledger_transaction_label(string $type): string
{
    return match ($type) {
        'invoice' => 'Invoice Raised',
        'legacy_payment' => 'Existing Payment Snapshot',
        'payment' => 'Payment Received',
        'advance' => 'Advance Received',
        'adjustment_credit' => 'Credit Adjustment',
        'adjustment_debit' => 'Debit Adjustment',
        default => 'Ledger Entry',
    };
}

function ledger_transaction_direction(string $type): string
{
    return match ($type) {
        'invoice', 'adjustment_debit' => 'debit',
        default => 'credit',
    };
}

function ledger_sort_weight(string $type): int
{
    return match ($type) {
        'invoice' => 10,
        'adjustment_debit' => 20,
        'legacy_payment' => 30,
        'payment' => 40,
        'advance' => 50,
        'adjustment_credit' => 60,
        default => 99,
    };
}

function ledger_generate_id(): string
{
    return 'led_' . bin2hex(random_bytes(8));
}

function ledger_fetch_client(mysqli $conn, int $clientId): array
{
    $stmt = $conn->prepare('
        SELECT id, company_name, contact_person, email, phone
        FROM clients
        WHERE id = ?
        LIMIT 1
    ');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();
    $stmt->close();

    if (!$client) {
        ApiResponse::error('Client not found.', 404);
    }

    return $client;
}

function ledger_fetch_client_invoices(mysqli $conn, int $clientId): array
{
    $stmt = $conn->prepare('
        SELECT
            id,
            invoice_number,
            invoice_date,
            due_date,
            grand_total,
            amount_received,
            status,
            updated_at
        FROM invoices
        WHERE client_id = ?
        ORDER BY invoice_date ASC, id ASC
    ');
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $today = new DateTimeImmutable('today');
    $invoices = [];

    while ($row = $result->fetch_assoc()) {
        $grandTotal = ledger_round((float) ($row['grand_total'] ?? 0));
        $received = ledger_round((float) ($row['amount_received'] ?? 0));
        $pending = ledger_round(max(0, $grandTotal - $received));

        $daysOverdue = 0;
        if (!empty($row['due_date']) && $pending > 0) {
            $dueDate = new DateTimeImmutable((string) $row['due_date']);
            if ($dueDate < $today) {
                $daysOverdue = (int) $dueDate->diff($today)->format('%a');
            }
        }

        $invoices[] = [
            'id' => (int) $row['id'],
            'invoice_number' => (string) $row['invoice_number'],
            'invoice_date' => (string) $row['invoice_date'],
            'due_date' => (string) $row['due_date'],
            'grand_total' => $grandTotal,
            'amount_received' => $received,
            'pending_amount' => $pending,
            'status' => (string) ($row['status'] ?? 'Unpaid'),
            'updated_at' => (string) ($row['updated_at'] ?? $row['invoice_date']),
            'days_overdue' => $daysOverdue,
        ];
    }

    $stmt->close();

    return $invoices;
}

function ledger_client_transactions(int $clientId): array
{
    $store = ledger_read_store();
    $transactions = array_values(array_filter(
        $store['transactions'],
        static fn(mixed $transaction): bool => is_array($transaction) && (int) ($transaction['client_id'] ?? 0) === $clientId
    ));

    usort($transactions, static function (array $left, array $right): int {
        $dateCompare = strcmp((string) ($left['transaction_date'] ?? ''), (string) ($right['transaction_date'] ?? ''));
        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        $weightCompare = ledger_sort_weight((string) ($left['type'] ?? '')) <=> ledger_sort_weight((string) ($right['type'] ?? ''));
        if ($weightCompare !== 0) {
            return $weightCompare;
        }

        return strcmp((string) ($left['created_at'] ?? ''), (string) ($right['created_at'] ?? ''));
    });

    return $transactions;
}

function ledger_allocated_amount(array $transaction): float
{
    $total = 0.0;
    foreach (($transaction['allocations'] ?? []) as $allocation) {
        if (!is_array($allocation)) {
            continue;
        }

        $total += (float) ($allocation['amount'] ?? 0);
    }

    return ledger_round($total);
}

function ledger_custom_allocations_by_invoice(array $transactions): array
{
    $totals = [];

    foreach ($transactions as $transaction) {
        foreach (($transaction['allocations'] ?? []) as $allocation) {
            if (!is_array($allocation)) {
                continue;
            }

            $invoiceId = (int) ($allocation['invoice_id'] ?? 0);
            $amount = ledger_round((float) ($allocation['amount'] ?? 0));

            if ($invoiceId <= 0 || $amount <= 0) {
                continue;
            }

            if (!isset($totals[$invoiceId])) {
                $totals[$invoiceId] = 0.0;
            }

            $totals[$invoiceId] = ledger_round($totals[$invoiceId] + $amount);
        }
    }

    return $totals;
}

function ledger_available_advances(array $transactions): array
{
    $advances = [];

    foreach ($transactions as $transaction) {
        if (($transaction['type'] ?? '') !== 'advance') {
            continue;
        }

        $amount = ledger_round((float) ($transaction['amount'] ?? 0));
        $allocated = ledger_allocated_amount($transaction);
        $available = ledger_round(max(0, $amount - $allocated));

        if ($available <= 0) {
            continue;
        }

        $advances[] = [
            'id' => (string) ($transaction['id'] ?? ''),
            'transaction_date' => (string) ($transaction['transaction_date'] ?? ''),
            'amount' => $amount,
            'allocated_amount' => $allocated,
            'available_amount' => $available,
            'payment_mode' => (string) ($transaction['payment_mode'] ?? ''),
            'reference_no' => (string) ($transaction['reference_no'] ?? ''),
            'notes' => (string) ($transaction['notes'] ?? ''),
        ];
    }

    return $advances;
}

function ledger_entries(array $invoices, array $transactions): array
{
    $entries = [];
    $customAllocations = ledger_custom_allocations_by_invoice($transactions);

    foreach ($invoices as $invoice) {
        $entries[] = [
            'id' => 'invoice_' . $invoice['id'],
            'date' => $invoice['invoice_date'],
            'type' => 'invoice',
            'label' => ledger_transaction_label('invoice'),
            'reference' => $invoice['invoice_number'],
            'notes' => 'Invoice created',
            'payment_mode' => '',
            'debit' => $invoice['grand_total'],
            'credit' => 0.0,
            'sort_weight' => ledger_sort_weight('invoice'),
        ];

        $customAmount = $customAllocations[$invoice['id']] ?? 0.0;
        $legacyAmount = ledger_round(max(0, $invoice['amount_received'] - $customAmount));

        if ($legacyAmount > 0) {
            $entries[] = [
                'id' => 'legacy_payment_' . $invoice['id'],
                'date' => substr($invoice['updated_at'], 0, 10) ?: $invoice['invoice_date'],
                'type' => 'legacy_payment',
                'label' => ledger_transaction_label('legacy_payment'),
                'reference' => $invoice['invoice_number'],
                'notes' => 'Existing received amount already present in invoice data',
                'payment_mode' => '',
                'debit' => 0.0,
                'credit' => $legacyAmount,
                'sort_weight' => ledger_sort_weight('legacy_payment'),
            ];
        }
    }

    foreach ($transactions as $transaction) {
        $type = (string) ($transaction['type'] ?? '');
        $amount = ledger_round((float) ($transaction['amount'] ?? 0));
        if ($amount <= 0) {
            continue;
        }

        $direction = ledger_transaction_direction($type);
        $allocationLabels = [];
        foreach (($transaction['allocations'] ?? []) as $allocation) {
            if (!is_array($allocation) || empty($allocation['invoice_number'])) {
                continue;
            }

            $allocationLabels[] = (string) $allocation['invoice_number'] . ' (' . number_format((float) $allocation['amount'], 2) . ')';
        }

        $notes = trim((string) ($transaction['notes'] ?? ''));
        if ($allocationLabels !== []) {
            $notes = trim($notes . ' Allocated to: ' . implode(', ', $allocationLabels));
        }

        $entries[] = [
            'id' => (string) ($transaction['id'] ?? ledger_generate_id()),
            'date' => (string) ($transaction['transaction_date'] ?? ''),
            'type' => $type,
            'label' => ledger_transaction_label($type),
            'reference' => (string) ($transaction['reference_no'] ?? ''),
            'notes' => $notes,
            'payment_mode' => (string) ($transaction['payment_mode'] ?? ''),
            'debit' => $direction === 'debit' ? $amount : 0.0,
            'credit' => $direction === 'credit' ? $amount : 0.0,
            'sort_weight' => ledger_sort_weight($type),
        ];
    }

    usort($entries, static function (array $left, array $right): int {
        $dateCompare = strcmp((string) $left['date'], (string) $right['date']);
        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        $weightCompare = ((int) $left['sort_weight']) <=> ((int) $right['sort_weight']);
        if ($weightCompare !== 0) {
            return $weightCompare;
        }

        return strcmp((string) $left['id'], (string) $right['id']);
    });

    return $entries;
}

function ledger_build_client_snapshot(mysqli $conn, int $clientId, ?string $fromDate = null, ?string $toDate = null): array
{
    $client = ledger_fetch_client($conn, $clientId);
    $invoices = ledger_fetch_client_invoices($conn, $clientId);
    $transactions = ledger_client_transactions($clientId);
    $entries = ledger_entries($invoices, $transactions);

    $openingBalance = 0.0;
    $runningBalance = 0.0;
    $rows = [];
    $invoicedAmount = 0.0;
    $receivedAmount = 0.0;
    $debitAdjustments = 0.0;
    $creditAdjustments = 0.0;

    foreach ($entries as $entry) {
        $date = (string) $entry['date'];
        $debit = ledger_round((float) $entry['debit']);
        $credit = ledger_round((float) $entry['credit']);
        $effect = ledger_round($debit - $credit);

        if ($fromDate !== null && $date < $fromDate) {
            $openingBalance = ledger_round($openingBalance + $effect);
            $runningBalance = $openingBalance;
            continue;
        }

        if ($toDate !== null && $date > $toDate) {
            continue;
        }

        $runningBalance = ledger_round($runningBalance + $effect);
        $rows[] = [
            'id' => $entry['id'],
            'date' => $date,
            'type' => $entry['type'],
            'label' => $entry['label'],
            'reference' => $entry['reference'],
            'payment_mode' => $entry['payment_mode'],
            'notes' => $entry['notes'],
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => $runningBalance,
        ];

        if ($entry['type'] === 'invoice') {
            $invoicedAmount = ledger_round($invoicedAmount + $debit);
        } elseif (in_array($entry['type'], ['payment', 'legacy_payment', 'advance'], true)) {
            $receivedAmount = ledger_round($receivedAmount + $credit);
        } elseif ($entry['type'] === 'adjustment_debit') {
            $debitAdjustments = ledger_round($debitAdjustments + $debit);
        } elseif ($entry['type'] === 'adjustment_credit') {
            $creditAdjustments = ledger_round($creditAdjustments + $credit);
        }
    }

    $currentBalance = 0.0;
    foreach ($entries as $entry) {
        $currentBalance = ledger_round($currentBalance + ((float) $entry['debit'] - (float) $entry['credit']));
    }

    $openInvoices = [];
    $currentOutstanding = 0.0;
    $overdueAmount = 0.0;

    foreach ($invoices as $invoice) {
        if ($invoice['pending_amount'] <= 0) {
            continue;
        }

        $currentOutstanding = ledger_round($currentOutstanding + $invoice['pending_amount']);
        if ($invoice['days_overdue'] > 0) {
            $overdueAmount = ledger_round($overdueAmount + $invoice['pending_amount']);
        }

        $openInvoices[] = [
            'invoice_id' => $invoice['id'],
            'invoice_number' => $invoice['invoice_number'],
            'invoice_date' => $invoice['invoice_date'],
            'due_date' => $invoice['due_date'],
            'grand_total' => $invoice['grand_total'],
            'amount_received' => $invoice['amount_received'],
            'pending_amount' => $invoice['pending_amount'],
            'status' => $invoice['status'],
            'days_overdue' => $invoice['days_overdue'],
        ];
    }

    $availableAdvances = ledger_available_advances($transactions);
    $unappliedAdvance = 0.0;
    foreach ($availableAdvances as $advance) {
        $unappliedAdvance = ledger_round($unappliedAdvance + $advance['available_amount']);
    }

    return [
        'client' => [
            'id' => (int) $client['id'],
            'company_name' => (string) $client['company_name'],
            'contact_person' => (string) ($client['contact_person'] ?? ''),
            'email' => (string) ($client['email'] ?? ''),
            'phone' => (string) ($client['phone'] ?? ''),
        ],
        'filters' => [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ],
        'summary' => [
            'opening_balance' => $openingBalance,
            'invoiced_amount' => $invoicedAmount,
            'received_amount' => $receivedAmount,
            'debit_adjustments' => $debitAdjustments,
            'credit_adjustments' => $creditAdjustments,
            'closing_balance' => $rows === [] ? $openingBalance : (float) end($rows)['running_balance'],
            'current_balance' => $currentBalance,
            'current_outstanding' => $currentOutstanding,
            'overdue_amount' => $overdueAmount,
            'unapplied_advance' => $unappliedAdvance,
            'open_invoice_count' => count($openInvoices),
        ],
        'entries' => $rows,
        'open_invoices' => $openInvoices,
        'available_advances' => $availableAdvances,
    ];
}

function ledger_overview(mysqli $conn): array
{
    $result = $conn->query('
        SELECT
            c.id,
            c.company_name,
            COUNT(i.id) AS invoice_count,
            COALESCE(SUM(GREATEST(i.grand_total - COALESCE(i.amount_received, 0), 0)), 0) AS outstanding_amount,
            COALESCE(SUM(CASE
                WHEN i.due_date < CURDATE() THEN GREATEST(i.grand_total - COALESCE(i.amount_received, 0), 0)
                ELSE 0
            END), 0) AS overdue_amount,
            MAX(COALESCE(i.updated_at, i.created_at)) AS last_activity_at
        FROM clients c
        LEFT JOIN invoices i ON i.client_id = c.id
        GROUP BY c.id, c.company_name
        ORDER BY c.company_name ASC
    ');

    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[(int) $row['id']] = [
            'client_id' => (int) $row['id'],
            'client_name' => (string) $row['company_name'],
            'invoice_count' => (int) ($row['invoice_count'] ?? 0),
            'outstanding_amount' => ledger_round((float) ($row['outstanding_amount'] ?? 0)),
            'overdue_amount' => ledger_round((float) ($row['overdue_amount'] ?? 0)),
            'unapplied_advance' => 0.0,
            'last_activity_at' => (string) ($row['last_activity_at'] ?? ''),
        ];
    }

    $store = ledger_read_store();
    foreach ($store['transactions'] as $transaction) {
        if (!is_array($transaction) || ($transaction['type'] ?? '') !== 'advance') {
            continue;
        }

        $clientId = (int) ($transaction['client_id'] ?? 0);
        if ($clientId <= 0 || !isset($clients[$clientId])) {
            continue;
        }

        $available = ledger_round(max(
            0,
            (float) ($transaction['amount'] ?? 0) - ledger_allocated_amount($transaction)
        ));

        $clients[$clientId]['unapplied_advance'] = ledger_round($clients[$clientId]['unapplied_advance'] + $available);
    }

    foreach ($clients as &$client) {
        $client['net_balance'] = ledger_round($client['outstanding_amount'] - $client['unapplied_advance']);
    }
    unset($client);

    return array_values($clients);
}

function ledger_placeholder_string(int $count): string
{
    return implode(',', array_fill(0, $count, '?'));
}

function ledger_normalize_allocations(mysqli $conn, int $clientId, array $allocations): array
{
    if ($allocations === []) {
        ApiResponse::error('At least one invoice allocation is required.', 422);
    }

    $invoiceIds = [];
    foreach ($allocations as $allocation) {
        if (!is_array($allocation)) {
            continue;
        }

        $invoiceId = (int) ($allocation['invoice_id'] ?? 0);
        if ($invoiceId > 0) {
            $invoiceIds[$invoiceId] = $invoiceId;
        }
    }

    if ($invoiceIds === []) {
        ApiResponse::error('Valid invoice selections are required.', 422);
    }

    $ids = array_values($invoiceIds);
    $types = 'i' . str_repeat('i', count($ids));
    $params = [$clientId, ...$ids];
    $refs = [];
    foreach ($params as $index => $value) {
        $refs[$index] = &$params[$index];
    }

    $sql = '
        SELECT id, invoice_number, grand_total, amount_received
        FROM invoices
        WHERE client_id = ?
          AND id IN (' . ledger_placeholder_string(count($ids)) . ')
    ';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$refs);
    $stmt->execute();
    $result = $stmt->get_result();

    $invoiceMap = [];
    while ($row = $result->fetch_assoc()) {
        $invoiceMap[(int) $row['id']] = [
            'invoice_id' => (int) $row['id'],
            'invoice_number' => (string) $row['invoice_number'],
            'grand_total' => ledger_round((float) ($row['grand_total'] ?? 0)),
            'amount_received' => ledger_round((float) ($row['amount_received'] ?? 0)),
        ];
    }
    $stmt->close();

    $normalized = [];
    $total = 0.0;

    foreach ($allocations as $allocation) {
        if (!is_array($allocation)) {
            continue;
        }

        $invoiceId = (int) ($allocation['invoice_id'] ?? 0);
        $amount = ledger_round((float) ($allocation['amount'] ?? 0));

        if ($invoiceId <= 0 || $amount <= 0) {
            continue;
        }

        if (!isset($invoiceMap[$invoiceId])) {
            ApiResponse::error('One or more selected invoices do not belong to this client.', 422);
        }

        $invoice = $invoiceMap[$invoiceId];
        $pending = ledger_round(max(0, $invoice['grand_total'] - $invoice['amount_received']));
        if ($amount > $pending + 0.01) {
            ApiResponse::error("Allocation exceeds pending amount for {$invoice['invoice_number']}.", 422);
        }

        $normalized[] = [
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoice['invoice_number'],
            'amount' => $amount,
        ];
        $total = ledger_round($total + $amount);
    }

    if ($normalized === []) {
        ApiResponse::error('Allocation amounts must be greater than zero.', 422);
    }

    return [
        'allocations' => $normalized,
        'total' => $total,
    ];
}

function ledger_apply_allocations(mysqli $conn, int $clientId, array $allocations): void
{
    $invoiceIds = array_map(static fn(array $allocation): int => (int) $allocation['invoice_id'], $allocations);
    $ids = array_values(array_unique($invoiceIds));

    $types = 'i' . str_repeat('i', count($ids));
    $params = [$clientId, ...$ids];
    $refs = [];
    foreach ($params as $index => $value) {
        $refs[$index] = &$params[$index];
    }

    $sql = '
        SELECT id, grand_total, amount_received
        FROM invoices
        WHERE client_id = ?
          AND id IN (' . ledger_placeholder_string(count($ids)) . ')
        FOR UPDATE
    ';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$refs);
    $stmt->execute();
    $result = $stmt->get_result();

    $invoiceMap = [];
    while ($row = $result->fetch_assoc()) {
        $invoiceMap[(int) $row['id']] = [
            'grand_total' => ledger_round((float) ($row['grand_total'] ?? 0)),
            'amount_received' => ledger_round((float) ($row['amount_received'] ?? 0)),
        ];
    }
    $stmt->close();

    $updateStmt = $conn->prepare('
        UPDATE invoices
        SET amount_received = ?, status = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND client_id = ?
    ');

    foreach ($allocations as $allocation) {
        $invoiceId = (int) $allocation['invoice_id'];
        $amount = ledger_round((float) $allocation['amount']);

        if (!isset($invoiceMap[$invoiceId])) {
            throw new RuntimeException('Failed to lock invoice for allocation.');
        }

        $invoice = $invoiceMap[$invoiceId];
        $newReceived = ledger_round($invoice['amount_received'] + $amount);
        $newReceived = min($newReceived, $invoice['grand_total']);

        $status = 'Unpaid';
        if ($newReceived >= $invoice['grand_total'] - 0.01) {
            $status = 'Paid';
        } elseif ($newReceived > 0) {
            $status = 'Partially Paid';
        }

        $updateStmt->bind_param('dsii', $newReceived, $status, $invoiceId, $clientId);
        $updateStmt->execute();
    }

    $updateStmt->close();
}

function ledger_append_transaction(array $transaction): array
{
    $store = ledger_read_store();
    $store['transactions'][] = $transaction;
    ledger_write_store($store);

    return $transaction;
}

function ledger_apply_advance_to_store(int $clientId, string $sourceId, array $allocations): array
{
    $store = ledger_read_store();

    foreach ($store['transactions'] as $index => $transaction) {
        if (!is_array($transaction)) {
            continue;
        }

        if (
            (string) ($transaction['id'] ?? '') !== $sourceId ||
            (int) ($transaction['client_id'] ?? 0) !== $clientId ||
            (string) ($transaction['type'] ?? '') !== 'advance'
        ) {
            continue;
        }

        $existingAllocations = is_array($transaction['allocations'] ?? null) ? $transaction['allocations'] : [];
        $allocatedAmount = ledger_allocated_amount($transaction);
        $availableAmount = ledger_round((float) ($transaction['amount'] ?? 0) - $allocatedAmount);

        $requestedAmount = 0.0;
        foreach ($allocations as $allocation) {
            $requestedAmount = ledger_round($requestedAmount + (float) $allocation['amount']);
        }

        if ($requestedAmount > $availableAmount + 0.01) {
            ApiResponse::error('Selected advance does not have enough available balance.', 422);
        }

        $transaction['allocations'] = array_merge($existingAllocations, $allocations);
        $transaction['updated_at'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
        $store['transactions'][$index] = $transaction;
        ledger_write_store($store);

        return $transaction;
    }

    ApiResponse::error('Advance entry not found.', 404);
}
