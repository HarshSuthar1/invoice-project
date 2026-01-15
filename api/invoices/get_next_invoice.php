<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    $sql = "SELECT MAX(invoice_number) AS last_invoice FROM invoices";
    $result = $conn->query($sql);

    $next_invoice = 1;

    if ($result) {
        $row = $result->fetch_assoc();
        $next_invoice = ((int) $row['last_invoice']) + 1;
    }

    ApiResponse::success([
        'next_invoice_number' => $next_invoice
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to get next invoice number: ' . $e->getMessage(),
        500
    );
}
