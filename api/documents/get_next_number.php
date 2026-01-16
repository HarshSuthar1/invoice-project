<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    $type = $_GET['type'] ?? 'invoice';
    
    $table_name = match($type) {
        'quotation' => 'quotations',
        'bill-no-gst' => 'bills',
        'invoice' => 'invoices',
        'challan' => 'challans',
        default => 'invoices'
    };

    // Get prefix based on type
    $prefix = match($type) {
        'quotation' => 'QT',
        'bill-no-gst' => 'BL',
        'invoice' => 'INV',
        'challan' => 'CH',
        default => 'DOC'
    };

    $sql = "SELECT MAX(CAST(SUBSTRING(document_number, 4) AS UNSIGNED)) AS last_number FROM {$table_name} WHERE document_number LIKE '{$prefix}%'";
    $result = $conn->query($sql);

    $next_number = 1;

    if ($result) {
        $row = $result->fetch_assoc();
        $last_number = (int) ($row['last_number'] ?? 0);
        $next_number = $last_number + 1;
    }

    // Format with prefix and padding
    $formatted_number = $prefix . '-' . str_pad((string)$next_number, 4, '0', STR_PAD_LEFT);

    ApiResponse::success([
        'next_number' => $formatted_number
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to get next document number: ' . $e->getMessage(),
        500
    );
}