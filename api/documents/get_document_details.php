<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    if (empty($_GET['id'])) {
        ApiResponse::error('Document ID is required', 422);
    }

    $document_id = (int) $_GET['id'];
    $type = $_GET['type'] ?? 'invoice';

    $table_name = match($type) {
        'quotation' => 'quotations',
        'bill-no-gst' => 'bills',
        'invoice' => 'invoices',
        'challan' => 'challans',
        default => 'invoices'
    };

    $items_table = match($type) {
        'quotation' => 'quotation_items',
        'bill-no-gst' => 'bill_items',
        'invoice' => 'invoice_items',
        'challan' => 'challan_items',
        default => 'invoice_items'
    };

    // Get document details
    $stmt = $conn->prepare("
        SELECT d.*, c.company_name as client_name
        FROM {$table_name} d
        LEFT JOIN clients c ON d.client_id = c.id
        WHERE d.id = ?
    ");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();

    $document = $stmt->get_result()->fetch_assoc();

    if (!$document) {
        ApiResponse::error('Document not found', 404);
    }

    // Get document items
    $stmt = $conn->prepare("
        SELECT *
        FROM {$items_table}
        WHERE document_id = ?
    ");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();

    $items = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    ApiResponse::success([
        'document' => $document,
        'items'    => $items
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to fetch document details: ' . $e->getMessage(),
        500
    );
}