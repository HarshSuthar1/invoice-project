<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    if (empty($_POST['client_id']) || empty($_POST['items'])) {
        ApiResponse::error('Invalid invoice data', 422);
    }

    $client_id        = (int) $_POST['client_id'];
    $invoice_number   = $_POST['invoice_number'];
    $status           = $_POST['status'] ?? 'unpaid';
    $grand_total      = (float) $_POST['grand_total'];
    $amount_received  = (float) ($_POST['amount_received'] ?? 0);
    $items            = json_decode($_POST['items'], true);

    $conn->begin_transaction();

    // Insert invoice
    $stmt = $conn->prepare("
        INSERT INTO invoices (
            invoice_number,
            client_id,
            grand_total,
            amount_received,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        "sidds",
        $invoice_number,
        $client_id,
        $grand_total,
        $amount_received,
        $status
    );
    $stmt->execute();

    $invoice_id = $conn->insert_id;

    // Insert items
    $stmt = $conn->prepare("
        INSERT INTO invoice_items (
            invoice_id,
            description,
            quantity,
            price,
            total
        ) VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $stmt->bind_param(
            "isidd",
            $invoice_id,
            $item['description'],
            $item['quantity'],
            $item['price'],
            $item['total']
        );
        $stmt->execute();
    }

    $conn->commit();

    ApiResponse::success([], 'Invoice created successfully');

} catch (Throwable $e) {
    $conn->rollback();

    ApiResponse::error(
        'Failed to save invoice: ' . $e->getMessage(),
        500
    );
}
