<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    $type = $_GET['type'] ?? 'invoice';
    
    // All document types use the invoices table for now
    // Filter by prefix in invoice_number
    $prefix = match($type) {
        'quotation' => 'QT',
        'bill-no-gst' => 'BL',
        'invoice' => 'INV',
        'challan' => 'CH',
        default => 'INV'
    };

    $documents = [];

    $sql = "
        SELECT 
            i.id,
            i.invoice_number as document_number,
            c.company_name AS client_name,
            i.invoice_date as document_date,
            i.grand_total,
            i.amount_received,
            i.status,
            i.created_at
        FROM invoices i
        LEFT JOIN clients c ON i.client_id = c.id
        WHERE i.invoice_number LIKE '{$prefix}%'
        ORDER BY i.created_at DESC
    ";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
    }

    ApiResponse::success([
        'documents' => $documents
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to fetch documents: ' . $e->getMessage(),
        500
    );
}