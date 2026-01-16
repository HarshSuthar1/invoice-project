<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    $type = $_GET['type'] ?? 'invoice'; // quotation, bill-no-gst, invoice, challan
    
    $table_name = match($type) {
        'quotation' => 'quotations',
        'bill-no-gst' => 'bills',
        'invoice' => 'invoices',
        'challan' => 'challans',
        default => 'invoices'
    };

    $documents = [];

    $sql = "
        SELECT 
            d.id,
            d.document_number,
            c.company_name AS client_name,
            d.document_date,
            d.grand_total,
            d.amount_received,
            d.status,
            d.created_at
        FROM {$table_name} d
        LEFT JOIN clients c ON d.client_id = c.id
        ORDER BY d.created_at DESC
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