<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    $invoices = [];

    $sql = "
        SELECT 
            i.id,
            i.invoice_number,
            c.company_name AS client_name,
            i.grand_total,
            i.amount_received,
            i.status,
            i.created_at
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        ORDER BY i.created_at DESC
    ";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $invoices[] = $row;
        }
    }

    ApiResponse::success([
        'invoices' => $invoices
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to fetch invoices: ' . $e->getMessage(),
        500
    );
}
