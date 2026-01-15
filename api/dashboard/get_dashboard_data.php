<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

// Protect API
ApiAuth::requireLogin();

try {
    $stats = [
        'total_outstanding' => 0,
        'total_received'    => 0,
        'unpaid_count'      => 0
    ];

    // 1. Get total outstanding, received, unpaid count
    $sql = "
        SELECT 
            SUM(
                CASE 
                    WHEN status != 'paid' 
                    THEN grand_total - COALESCE(amount_received, 0) 
                    ELSE 0 
                END
            ) AS total_outstanding,
            SUM(COALESCE(amount_received, 0)) AS total_received,
            COUNT(CASE WHEN status != 'paid' THEN 1 END) AS unpaid_count
        FROM invoices
    ";

    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_outstanding'] = $row['total_outstanding'] ?? 0;
        $stats['total_received']   = $row['total_received'] ?? 0;
        $stats['unpaid_count']     = $row['unpaid_count'] ?? 0;
    }

    // 2. Get recent invoices
    $sql = "
        SELECT 
            i.invoice_number,
            c.company_name AS client_name,
            i.grand_total AS amount,
            i.status
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        ORDER BY i.created_at DESC
        LIMIT 5
    ";

    $result = $conn->query($sql);
    $recent_invoices = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_invoices[] = $row;
        }
    }

    // 3. Get payment status distribution
    $sql = "
        SELECT 
            COUNT(CASE WHEN status = 'paid' THEN 1 END) AS paid,
            COUNT(CASE WHEN status = 'unpaid' THEN 1 END) AS unpaid,
            COUNT(CASE WHEN status = 'partially paid' THEN 1 END) AS partial
        FROM invoices
    ";

    $result = $conn->query($sql);
    $payment_status = $result ? $result->fetch_assoc() : [
        'paid'    => 0,
        'unpaid'  => 0,
        'partial' => 0
    ];

    // Success response
    ApiResponse::success([
        'stats'            => $stats,
        'recent_invoices'  => $recent_invoices,
        'payment_status'   => $payment_status
    ]);

} catch (Throwable $e) {

    ApiResponse::error(
        'Database error: ' . $e->getMessage(),
        500
    );
}
