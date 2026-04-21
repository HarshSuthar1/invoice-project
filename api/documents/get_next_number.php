<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    $type = $_GET['type'] ?? 'invoice';

    // Get prefix based on type
    $prefix = match($type) {
        'quotation' => 'QT',
        'bill-no-gst' => 'BL',
        'invoice' => 'INV',
        'challan' => 'CH',
        default => 'DOC'
    };

    // For invoices (bills with GST), use financial year based numbering
    if ($type === 'invoice') {
        // Get current date
        $current_date = new DateTime();
        $current_month = (int) $current_date->format('m');
        $current_year = (int) $current_date->format('Y');
        
        // Financial year starts on April 1st
        // If current month is Jan-Mar, we're in previous financial year
        if ($current_month < 4) {
            $financial_year = $current_year - 1;
        } else {
            $financial_year = $current_year;
        }
        
        // Get last 2 digits of financial year (e.g., 2025 -> 25)
        $year_suffix = substr((string)$financial_year, -2);
        
        // Build the pattern for this financial year: INV-25-%
        $year_prefix = $prefix . '-' . $year_suffix;
        
        $stmt = $conn->prepare("
            SELECT MAX(CAST(SUBSTRING(invoice_number, LENGTH(?) + 2) AS UNSIGNED)) AS last_number
            FROM invoices
            WHERE document_type = ?
              AND invoice_number LIKE CONCAT(?, '-%')
        ");
        $stmt->bind_param('sss', $year_prefix, $type, $year_prefix);
        $stmt->execute();
        $result = $stmt->get_result();
        $next_number = 1;
        
        if ($result) {
            $row = $result->fetch_assoc();
            $last_number = (int) ($row['last_number'] ?? 0);
            $next_number = $last_number + 1;
        }
        $stmt->close();
        
        // Format: INV-25-0001
        $formatted_number = $year_prefix . '-' . str_pad((string)$next_number, 4, '0', STR_PAD_LEFT);
        
    } else {
        $stmt = $conn->prepare("
            SELECT MAX(CAST(SUBSTRING(invoice_number, LENGTH(?) + 2) AS UNSIGNED)) AS last_number
            FROM invoices
            WHERE document_type = ?
              AND invoice_number LIKE CONCAT(?, '-%')
        ");
        $stmt->bind_param('sss', $prefix, $type, $prefix);
        $stmt->execute();
        $result = $stmt->get_result();
        $next_number = 1;
        
        if ($result) {
            $row = $result->fetch_assoc();
            $last_number = (int) ($row['last_number'] ?? 0);
            $next_number = $last_number + 1;
        }
        $stmt->close();
        
        // Format: QT-0001, BL-0001, CH-0001
        $formatted_number = $prefix . '-' . str_pad((string)$next_number, 4, '0', STR_PAD_LEFT);
    }

    ApiResponse::success([
        'next_number' => $formatted_number
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to get next document number: ' . $e->getMessage(),
        500
    );
}
