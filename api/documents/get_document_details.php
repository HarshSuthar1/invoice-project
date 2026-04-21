<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

function document_parse_item_metadata(array $item): array
{
    $item['description_extra'] = '';
    $item['sub_items'] = [];

    $raw = trim((string) ($item['specifications'] ?? ''));
    if ($raw !== '') {
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $item['description_extra'] = trim((string) ($decoded['description_extra'] ?? ''));
            $item['sub_items'] = is_array($decoded['sub_items'] ?? null) ? $decoded['sub_items'] : [];
        } else {
            $item['description_extra'] = $raw;
        }
    }

    if ($item['description_extra'] === '' && str_contains((string) ($item['description'] ?? ''), "\n")) {
        $parts = preg_split("/\r\n|\n|\r/", (string) $item['description']);
        $item['description'] = array_shift($parts) ?? '';
        $item['description_extra'] = trim(implode(PHP_EOL, $parts));
    }

    return $item;
}

try {
    if (empty($_GET['id'])) {
        ApiResponse::error('Document ID is required', 422);
    }

    $document_id = (int) $_GET['id'];

    // Get document details from invoices table
    $stmt = $conn->prepare("
        SELECT i.*, c.company_name as client_name
        FROM invoices i
        LEFT JOIN clients c ON i.client_id = c.id
        WHERE i.id = ?
    ");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();

    $document = $stmt->get_result()->fetch_assoc();

    if (!$document) {
        ApiResponse::error('Document not found', 404);
    }

    // Get document items from invoice_items table
    $stmt = $conn->prepare("
        SELECT *
        FROM invoice_items
        WHERE invoice_id = ?
    ");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();

    $items = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = document_parse_item_metadata($row);
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
