<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    if (empty($_POST['invoice_id'])) {
        ApiResponse::error('Invoice ID is required', 422);
    }

    $invoice_id       = (int) $_POST['invoice_id'];
    $status           = $_POST['status'] ?? 'unpaid';
    $amount_received  = (float) ($_POST['amount_received'] ?? 0);

    $stmt = $conn->prepare("
        UPDATE invoices SET
            status = ?,
            amount_received = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sdi",
        $status,
        $amount_received,
        $invoice_id
    );

    $stmt->execute();

    ApiResponse::success([], 'Invoice updated successfully');

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to update invoice: ' . $e->getMessage(),
        500
    );
}
