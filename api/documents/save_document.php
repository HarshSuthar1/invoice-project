<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    if (empty($_POST['client_id']) || empty($_POST['items']) || empty($_POST['document_type'])) {
        ApiResponse::error('Invalid document data', 422);
    }

    $client_id        = (int) $_POST['client_id'];
    $document_type    = $_POST['document_type']; // quotation, bill-no-gst, invoice, challan
    $document_number  = $_POST['document_number'];
    $document_date    = $_POST['document_date'];
    $status           = $_POST['status'] ?? 'unpaid';
    $grand_total      = (float) $_POST['grand_total'];
    $subtotal         = (float) ($_POST['subtotal'] ?? 0);
    $total_tax        = (float) ($_POST['total_tax'] ?? 0);
    $amount_received  = (float) ($_POST['amount_received'] ?? 0);
    $items            = json_decode($_POST['items'], true);

    // Challan-specific fields
    $vehicle_number   = $_POST['vehicle_number'] ?? null;
    $driver_name      = $_POST['driver_name'] ?? null;
    $destination      = $_POST['destination'] ?? null;

    $conn->begin_transaction();

    // Determine table name based on document type
    $table_name = match($document_type) {
        'quotation' => 'quotations',
        'bill-no-gst' => 'bills',
        'invoice' => 'invoices',
        'challan' => 'challans',
        default => 'invoices'
    };

    // Insert document
    if ($document_type === 'challan') {
        $stmt = $conn->prepare("
            INSERT INTO {$table_name} (
                document_number,
                client_id,
                document_date,
                subtotal,
                total_tax,
                grand_total,
                amount_received,
                status,
                vehicle_number,
                driver_name,
                destination,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sisddddsss",
            $document_number,
            $client_id,
            $document_date,
            $subtotal,
            $total_tax,
            $grand_total,
            $amount_received,
            $status,
            $vehicle_number,
            $driver_name,
            $destination
        );
    } else {
        $stmt = $conn->prepare("
            INSERT INTO {$table_name} (
                document_number,
                client_id,
                document_date,
                subtotal,
                total_tax,
                grand_total,
                amount_received,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sisddds",
            $document_number,
            $client_id,
            $document_date,
            $subtotal,
            $total_tax,
            $grand_total,
            $amount_received,
            $status
        );
    }

    $stmt->execute();
    $document_id = $conn->insert_id;

    // Insert items
    $items_table = match($document_type) {
        'quotation' => 'quotation_items',
        'bill-no-gst' => 'bill_items',
        'invoice' => 'invoice_items',
        'challan' => 'challan_items',
        default => 'invoice_items'
    };

    $stmt = $conn->prepare("
        INSERT INTO {$items_table} (
            document_id,
            description,
            quantity,
            unit,
            unit_price,
            tax_rate,
            line_total
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $qty = (float) $item['quantity'];
        $price = (float) $item['price'];
        $tax = (float) ($item['tax'] ?? 0);
        $line_total = $qty * $price * (1 + $tax / 100);

        $stmt->bind_param(
            "isdsdd",
            $document_id,
            $item['description'],
            $qty,
            $item['unit'] ?? 'Nos',
            $price,
            $tax,
            $line_total
        );
        $stmt->execute();
    }

    $conn->commit();

    ApiResponse::success([], 'Document created successfully');

} catch (Throwable $e) {
    $conn->rollback();

    ApiResponse::error(
        'Failed to save document: ' . $e->getMessage(),
        500
    );
}