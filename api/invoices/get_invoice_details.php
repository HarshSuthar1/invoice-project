<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    if (empty($_GET['id'])) {
        ApiResponse::error('Invoice ID is required', 422);
    }

    $invoice_id = (int) $_GET['id'];

    // Invoice details
    $stmt = $conn->prepare("
        SELECT *
        FROM invoices
        WHERE id = ?
    ");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();

    $invoice = $stmt->get_result()->fetch_assoc();

    if (!$invoice) {
        ApiResponse::error('Invoice not found', 404);
    }

    // Invoice items
    $stmt = $conn->prepare("
        SELECT *
        FROM invoice_items
        WHERE invoice_id = ?
    ");
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();

    $items = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    ApiResponse::success([
        'invoice' => $invoice,
        'items'   => $items
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to fetch invoice details: ' . $e->getMessage(),
        500
    );
}
