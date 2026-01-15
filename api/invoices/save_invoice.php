<?php
declare(strict_types=1);

header('Content-Type: application/json');

// Using the established bootstrap for session and DB connection [cite: 55]
require_once '../../app/bootstrap.php';

// Ensure user is authorized to perform this action [cite: 55]
ApiAuth::requireLogin();

try {
    // 1. Basic Validation
    if (empty($_POST['client_id']) || empty($_POST['items'])) {
        ApiResponse::error('Invalid invoice data: Client and items are required.', 422);
    }

    $client_id        = (int) $_POST['client_id'];
    $invoice_number   = trim($_POST['invoice_number']);
    $status           = $_POST['status'] ?? 'unpaid';
    $amount_received  = (float) ($_POST['amount_received'] ?? 0);
    
    // Decode items from JSON 
    $items = json_decode($_POST['items'], true);
    if (!is_array($items)) {
        ApiResponse::error('Invalid items format.', 422);
    }

    // 2. SERVER-SIDE CALCULATION (The "Better Approach" fix)
    // We calculate the total here to ensure integrity 
    $calculated_grand_total = 0;
    $processed_items = [];

    foreach ($items as $item) {
        $qty   = (float) ($item['quantity'] ?? 0);
        $price = (float) ($item['price'] ?? 0);
        $row_total = $qty * $price;

        $calculated_grand_total += $row_total;

        // Store processed data for the second insert loop
        $processed_items[] = [
            'description' => trim($item['description'] ?? 'No description'),
            'quantity'    => $qty,
            'price'       => $price,
            'total'       => $row_total
        ];
    }

    // 3. Database Transaction 
    $conn->begin_transaction();

    // Insert main invoice using calculated total
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
        $calculated_grand_total, // Now using the safe, server-calculated total
        $amount_received,
        $status
    );
    
    $stmt->execute();
    $invoice_id = $conn->insert_id;

    // Insert individual items [cite: 60]
    $stmtItem = $conn->prepare("
        INSERT INTO invoice_items (
            invoice_id,
            description,
            quantity,
            price,
            total
        ) VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($processed_items as $item) {
        $stmtItem->bind_param(
            "isidd",
            $invoice_id,
            $item['description'],
            $item['quantity'],
            $item['price'],
            $item['total']
        );
        $stmtItem->execute();
    }

    // If everything worked, save permanently 
    $conn->commit();

    ApiResponse::success([
        'invoice_id' => $invoice_id,
        'final_total' => $calculated_grand_total
    ], 'Invoice created successfully with verified totals.');

} catch (Throwable $e) {
    // If any error occurs, undo everything in this transaction 
    if (isset($conn)) {
        $conn->rollback();
    }
    
    ApiResponse::error(
        'Failed to save invoice: ' . $e->getMessage(),
        500
    );
}