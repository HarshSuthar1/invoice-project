<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

function document_bind_dynamic_params(mysqli_stmt $stmt, array $values): void
{
    $types = '';
    $params = [];

    foreach ($values as $index => $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }

        $params[$index] = $value;
    }

    $bindArgs = [$types];
    foreach ($params as $index => $value) {
        $bindArgs[] = &$params[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}

function document_table_columns(mysqli $conn, string $table): array
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `{$table}`");
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }

    $cache[$table] = $columns;

    return $columns;
}

function document_normalize_sub_items(mixed $subItems): array
{
    if (!is_array($subItems)) {
        return [];
    }

    $normalized = [];

    foreach ($subItems as $subItem) {
        if (!is_array($subItem)) {
            continue;
        }

        $description = trim((string) ($subItem['description'] ?? ''));
        if ($description === '') {
            continue;
        }

        $normalized[] = [
            'description' => $description,
            'quantity' => round((float) ($subItem['quantity'] ?? 1), 2),
            'unit' => trim((string) ($subItem['unit'] ?? 'Nos')) ?: 'Nos',
            'price' => round((float) ($subItem['price'] ?? 0), 2),
        ];
    }

    return $normalized;
}

function document_item_metadata(array $item): ?string
{
    $metadata = [];
    $extraDescription = trim((string) ($item['description_extra'] ?? ''));
    $subItems = document_normalize_sub_items($item['sub_items'] ?? []);

    if ($extraDescription !== '') {
        $metadata['description_extra'] = $extraDescription;
    }

    if ($subItems !== []) {
        $metadata['sub_items'] = $subItems;
    }

    if ($metadata === []) {
        return null;
    }

    $encoded = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $encoded === false ? null : $encoded;
}

function document_item_line_total(array $item): float
{
    $qty = (float) ($item['quantity'] ?? 0);
    $price = (float) ($item['price'] ?? 0);
    $tax = (float) ($item['tax'] ?? 0);
    $lineAmount = $qty * $price;

    foreach (document_normalize_sub_items($item['sub_items'] ?? []) as $subItem) {
        $lineAmount += (float) $subItem['quantity'] * (float) $subItem['price'];
    }

    return round($lineAmount * (1 + ($tax / 100)), 2);
}

function document_prepare_item_insert(mysqli $conn): array
{
    $columns = document_table_columns($conn, 'invoice_items');
    $available = array_flip($columns);

    $insertColumns = ['invoice_id', 'description'];

    foreach (['specifications', 'hsn_code'] as $column) {
        if (isset($available[$column])) {
            $insertColumns[] = $column;
        }
    }

    if (isset($available['quantity'])) {
        $insertColumns[] = 'quantity';
    }

    if (isset($available['unit'])) {
        $insertColumns[] = 'unit';
    }

    if (isset($available['unit_price'])) {
        $insertColumns[] = 'unit_price';
    } elseif (isset($available['price'])) {
        $insertColumns[] = 'price';
    }

    if (isset($available['tax_rate'])) {
        $insertColumns[] = 'tax_rate';
    } elseif (isset($available['tax'])) {
        $insertColumns[] = 'tax';
    }

    if (isset($available['line_total'])) {
        $insertColumns[] = 'line_total';
    } elseif (isset($available['total'])) {
        $insertColumns[] = 'total';
    }

    if (isset($available['image_url'])) {
        $insertColumns[] = 'image_url';
    } elseif (isset($available['item_image'])) {
        $insertColumns[] = 'item_image';
    }

    if (isset($available['item_order'])) {
        $insertColumns[] = 'item_order';
    }

    $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
    $sql = 'INSERT INTO invoice_items (' . implode(', ', $insertColumns) . ') VALUES (' . $placeholders . ')';

    return [
        'columns' => $insertColumns,
        'statement' => $conn->prepare($sql),
    ];
}

try {
    if (empty($_POST['client_id']) || empty($_POST['items']) || empty($_POST['document_type'])) {
        ApiResponse::error('Invalid document data', 422);
    }

    $client_id        = (int) $_POST['client_id'];
    $document_type    = $_POST['document_type']; // quotation, bill-no-gst, invoice, challan
    $issuer_type      = $_POST['issuer_type'] ?? 'company';
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
                issuer_type,
                document_image,
                created_at
            ) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 DAY), ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sissddddsss",
            $document_number,
            $client_id,
            $document_date,
            $document_date,
            $subtotal,
            $total_tax,
            $grand_total,
            $amount_received,
            $status,
            $issuer_type,
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
                issuer_type,
                document_image,
                created_at
            ) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 DAY), ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sissddddsss",
            $document_number,
            $client_id,
            $document_date,
            $document_date,
            $subtotal,
            $total_tax,
            $grand_total,
            $amount_received,
            $status,
            $issuer_type,
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
                issuer_type,
                document_image,
                created_at
            ) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 30 DAY), ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sissddddsss",
            $document_number,
            $client_id,
            $document_date,
            $document_date,
            $subtotal,
            $total_tax,
            $grand_total,
            $amount_received,
            $status,
            $issuer_type,
            $document_image
        );
    }

    $stmt->execute();
    $document_id = $conn->insert_id;

    // Insert items - all use invoice_items table for now
    $itemInsert = document_prepare_item_insert($conn);
    $stmt = $itemInsert['statement'];
    $itemColumns = $itemInsert['columns'];

    foreach ($items as $index => $item) {
        $qty = (float) ($item['quantity'] ?? 0);
        $price = (float) ($item['price'] ?? 0);
        $tax = (float) ($item['tax'] ?? 0);
        $line_total = document_item_line_total($item);
        $metadata = document_item_metadata($item);

        $values = [];
        foreach ($itemColumns as $column) {
            $values[] = match ($column) {
                'invoice_id' => $document_id,
                'description' => trim((string) ($item['description'] ?? '')),
                'specifications' => $metadata,
                'hsn_code' => trim((string) ($item['hsn_code'] ?? '')),
                'quantity' => $qty,
                'unit' => trim((string) ($item['unit'] ?? 'Nos')) ?: 'Nos',
                'unit_price', 'price' => $price,
                'tax_rate', 'tax' => $tax,
                'line_total', 'total' => $line_total,
                'image_url', 'item_image' => $item['image'] ?? null,
                'item_order' => $index,
                default => null,
            };
        }

        document_bind_dynamic_params($stmt, $values);
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
