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
    
    // Handle pasted image (base64 encoded) - only for quotations and bills
    $document_image   = null;
    if (in_array($document_type, ['quotation', 'bill-no-gst']) && !empty($_POST['document_image'])) {
        $document_image = $_POST['document_image']; // Already base64 encoded from frontend
    }

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

    // For invoices, use invoice_number and invoice_date columns
    // For other documents, use document_number and document_date columns
    if ($document_type === 'invoice') {
        $stmt = $conn->prepare("
            INSERT INTO {$table_name} (
                invoice_number,
                client_id,
                invoice_date,
                due_date,
                subtotal,
                total_tax,
                grand_total,
                amount_received,
                status,
                document_image,
                created_at
            ) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 DAY), ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sissdddss",
            $document_number,
            $client_id,
            $document_date,
            $document_date,
            $subtotal,
            $total_tax,
            $grand_total,
            $amount_received,
            $status,
            $document_image
        );
    } else if ($document_type === 'challan') {
        // Challans may not have the same table structure - use the existing invoices table for now
        // You'll need to create a separate challans table if needed
        $stmt = $conn->prepare("
            INSERT INTO invoices (
                invoice_number,
                client_id,
                invoice_date,
                due_date,
                subtotal,
                total_tax,
                grand_total,
                amount_received,
                status,
                document_image,
                created_at
            ) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 DAY), ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sissdddss",
            $document_number,
            $client_id,
            $document_date,
            $document_date,
            $subtotal,
            $total_tax,
            $grand_total,
            $amount_received,
            $status,
            $document_image
        );
    } else {
        // For quotations and bills, use the invoices table with prefix
        $stmt = $conn->prepare("
            INSERT INTO invoices (
                invoice_number,
                client_id,
                invoice_date,
                due_date,
                subtotal,
                total_tax,
                grand_total,
                amount_received,
                status,
                document_image,
                created_at
            ) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 DAY), ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sissdddss",
            $document_number,
            $client_id,
            $document_date,
            $document_date,
            $subtotal,
            $total_tax,
            $grand_total,
            $amount_received,
            $status,
            $document_image
        );
    }

    $stmt->execute();
    $document_id = $conn->insert_id;

    // Insert items - all use invoice_items table for now
    $stmt = $conn->prepare("
        INSERT INTO invoice_items (
            invoice_id,
            description,
            quantity,
            unit,
            unit_price,
            tax_rate,
            line_total,
            item_image
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $qty = (float) $item['quantity'];
        $price = (float) $item['price'];
        $tax = (float) ($item['tax'] ?? 0);
        $line_total = $qty * $price * (1 + $tax / 100);
        $item_image = $item['image'] ?? null;

        $stmt->bind_param(
            "isdsddss",
            $document_id,
            $item['description'],
            $qty,
            $item['unit'] ?? 'Nos',
            $price,
            $tax,
            $line_total,
            $item_image
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