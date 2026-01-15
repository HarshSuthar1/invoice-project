<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

// Protect API
ApiAuth::requireLogin();

try {
    // Default filters
    $from_date = $_GET['from_date'] ?? null;
    $to_date   = $_GET['to_date'] ?? null;
    $status    = $_GET['status'] ?? null;

    $conditions = [];
    $params     = [];
    $types      = '';

    if ($from_date) {
        $conditions[] = 'created_at >= ?';
        $params[] = $from_date;
        $types .= 's';
    }

    if ($to_date) {
        $conditions[] = 'created_at <= ?';
        $params[] = $to_date;
        $types .= 's';
    }

    if ($status) {
        $conditions[] = 'status = ?';
        $params[] = $status;
        $types .= 's';
    }

    $where_sql = '';
    if (!empty($conditions)) {
        $where_sql = 'WHERE ' . implode(' AND ', $conditions);
    }

    // 1. Fetch invoices for report table
    $sql = "
        SELECT 
            i.invoice_number,
            c.company_name AS client_name,
            i.grand_total,
            i.amount_received,
            i.status,
            i.created_at
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        $where_sql
        ORDER BY i.created_at DESC
    ";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }

    // 2. Summary data
    $sql = "
        SELECT
            COUNT(*) AS total_invoices,
            SUM(grand_total) AS total_amount,
            SUM(amount_received) AS total_received,
            SUM(grand_total - COALESCE(amount_received, 0)) AS total_due
        FROM invoices
        $where_sql
    ";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();

    ApiResponse::success([
        'summary'  => [
            'total_invoices' => (int) ($summary['total_invoices'] ?? 0),
            'total_amount'   => (float) ($summary['total_amount'] ?? 0),
            'total_received' => (float) ($summary['total_received'] ?? 0),
            'total_due'      => (float) ($summary['total_due'] ?? 0),
        ],
        'invoices' => $invoices
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to generate report: ' . $e->getMessage(),
        500
    );
}
