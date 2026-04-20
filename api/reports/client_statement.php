<?php
declare(strict_types=1);

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

/**
 * Read a request value from POST first, then GET.
 */
function request_value(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    }

    if (array_key_exists($key, $_GET)) {
        return $_GET[$key];
    }

    return $default;
}

/**
 * Validate a Y-m-d date string.
 */
function parse_statement_date(mixed $value): ?string
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    $value = trim($value);
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if (!$date || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
        ApiResponse::error("Invalid date format for '{$value}'. Expected YYYY-MM-DD.", 422);
    }

    return $date->format('Y-m-d');
}

/**
 * Bind mysqli statement params dynamically.
 */
function bind_statement_params(mysqli_stmt $stmt, string $types, array $values): void
{
    $params = [$types];

    foreach ($values as $index => $value) {
        $params[] = &$values[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $params);
}

/**
 * Sanitize the ORDER BY clause using a whitelist.
 */
function resolve_statement_sort(mixed $sort): string
{
    $sort = is_string($sort) ? strtolower(trim($sort)) : 'oldest';

    return match ($sort) {
        'newest' => 'i.invoice_date DESC, i.id DESC',
        'number_asc' => 'i.invoice_number ASC',
        'number_desc' => 'i.invoice_number DESC',
        'pending_desc' => 'pending_amount DESC, i.invoice_date ASC, i.id ASC',
        'pending_asc' => 'pending_amount ASC, i.invoice_date ASC, i.id ASC',
        default => 'i.invoice_date ASC, i.id ASC',
    };
}

/**
 * Escape text for use in PDF content streams.
 */
function pdf_escape(string $value): string
{
    $value = str_replace("\\", "\\\\", $value);
    $value = str_replace("(", "\\(", $value);
    $value = str_replace(")", "\\)", $value);

    return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
}

/**
 * Render one text line in the PDF.
 */
function pdf_text(float $x, float $y, int $fontSize, string $text, string $font = 'F1'): string
{
    $safeText = pdf_escape($text);

    return sprintf(
        "BT /%s %d Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET\n",
        $font,
        $fontSize,
        $x,
        $y,
        $safeText
    );
}

/**
 * Draw a line in the PDF.
 */
function pdf_line(float $x1, float $y1, float $x2, float $y2): string
{
    return sprintf("%.2F %.2F m %.2F %.2F l S\n", $x1, $y1, $x2, $y2);
}

/**
 * Format a number for statement output.
 */
function statement_money(float $amount): string
{
    return number_format($amount, 2, '.', '');
}

/**
 * Format currency text for the PDF.
 */
function pdf_money(float $amount): string
{
    return 'Rs ' . number_format($amount, 2, '.', ',');
}

/**
 * Build a very small dependency-free PDF for the statement.
 */
function render_statement_pdf(array $statement): string
{
    $pageWidth = 595.28;
    $pageHeight = 841.89;
    $left = 42.0;
    $right = 553.0;
    $tableTop = 720.0;
    $bottomThreshold = 100.0;
    $rowHeight = 18.0;

    $pages = [];
    $documents = $statement['documents'];
    $generatedAt = (new DateTimeImmutable())->format('d M Y, h:i A');
    $clientName = $statement['client_name'] ?? 'Unknown Client';
    $dateRange = [];

    if (!empty($statement['date_from'])) {
        $dateRange[] = 'From: ' . $statement['date_from'];
    }
    if (!empty($statement['date_to'])) {
        $dateRange[] = 'To: ' . $statement['date_to'];
    }

    $dateRangeText = $dateRange ? implode('  ', $dateRange) : 'All outstanding unpaid and partially paid documents';

    $startPage = static function () use (
        $clientName,
        $generatedAt,
        $dateRangeText,
        $left,
        $right,
        $tableTop
    ): array {
        $content = '';
        $content .= "0 0 0 rg 0 0 0 RG 1 w\n";
        $content .= pdf_text($left, 800, 18, 'Client Statement', 'F2');
        $content .= pdf_text($left, 782, 11, 'Client: ' . $clientName, 'F1');
        $content .= pdf_text($left, 767, 10, 'Generated: ' . $generatedAt, 'F1');
        $content .= pdf_text($left, 753, 10, $dateRangeText, 'F1');
        $content .= pdf_line($left, 742, $right, 742);

        $content .= pdf_text($left, $tableTop, 10, 'Document', 'F2');
        $content .= pdf_text(245, $tableTop, 10, 'Date', 'F2');
        $content .= pdf_text(320, $tableTop, 10, 'Total', 'F2');
        $content .= pdf_text(402, $tableTop, 10, 'Paid', 'F2');
        $content .= pdf_text(480, $tableTop, 10, 'Pending', 'F2');
        $content .= pdf_line($left, $tableTop - 6, $right, $tableTop - 6);

        return [
            'content' => $content,
            'y' => $tableTop - 26,
        ];
    };

    $page = $startPage();

    foreach ($documents as $document) {
        if ($page['y'] < $bottomThreshold) {
            $pages[] = $page['content'];
            $page = $startPage();
        }

        $page['content'] .= pdf_text($left, $page['y'], 10, (string) $document['document_number'], 'F1');
        $page['content'] .= pdf_text(245, $page['y'], 10, (string) ($document['document_date'] ?? '-'), 'F1');
        $page['content'] .= pdf_text(320, $page['y'], 10, pdf_money((float) $document['total']), 'F1');
        $page['content'] .= pdf_text(402, $page['y'], 10, pdf_money((float) $document['paid']), 'F1');
        $page['content'] .= pdf_text(480, $page['y'], 10, pdf_money((float) $document['pending']), 'F1');
        $page['content'] .= pdf_line($left, $page['y'] - 5, $right, $page['y'] - 5);
        $page['y'] -= $rowHeight;
    }

    if ($page['y'] < $bottomThreshold + 30) {
        $pages[] = $page['content'];
        $page = $startPage();
    }

    $page['content'] .= pdf_text(360, $page['y'] - 8, 12, 'Total Pending:', 'F2');
    $page['content'] .= pdf_text(468, $page['y'] - 8, 12, pdf_money((float) $statement['total_pending']), 'F2');
    $pages[] = $page['content'];

    $objects = [];
    $offsets = [];

    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";

    $kids = [];
    $pageCount = count($pages);
    $fontObjectIds = [
        'F1' => 3 + ($pageCount * 2),
        'F2' => 4 + ($pageCount * 2),
    ];

    for ($index = 0; $index < $pageCount; $index++) {
        $pageObjectId = 3 + ($index * 2);
        $contentObjectId = 4 + ($index * 2);
        $kids[] = $pageObjectId . ' 0 R';
    }

    $objects[] = sprintf(
        "<< /Type /Pages /Kids [%s] /Count %d >>",
        implode(' ', $kids),
        $pageCount
    );

    foreach ($pages as $index => $content) {
        $pageObjectId = 3 + ($index * 2);
        $contentObjectId = 4 + ($index * 2);

        $objects[] = sprintf(
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>",
            $pageWidth,
            $pageHeight,
            $fontObjectIds['F1'],
            $fontObjectIds['F2'],
            $contentObjectId
        );

        $objects[] = sprintf(
            "<< /Length %d >>\nstream\n%sendstream",
            strlen($content),
            $content
        );
    }

    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

    $pdf = "%PDF-1.4\n";

    foreach ($objects as $index => $object) {
        $objectNumber = $index + 1;
        $offsets[$objectNumber] = strlen($pdf);
        $pdf .= $objectNumber . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= '0 ' . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($objectNumber = 1; $objectNumber <= count($objects); $objectNumber++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$objectNumber]);
    }

    $pdf .= "trailer\n";
    $pdf .= '<< /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n";
    $pdf .= $xrefOffset . "\n";
    $pdf .= "%%EOF";

    return $pdf;
}

/**
 * Load statement data for a client.
 */
function fetch_client_statement(
    mysqli $conn,
    int $clientId,
    ?string $dateFrom,
    ?string $dateTo,
    string $sortClause
): array {
    $clientStmt = $conn->prepare('SELECT company_name FROM clients WHERE id = ? LIMIT 1');
    $clientStmt->bind_param('i', $clientId);
    $clientStmt->execute();
    $clientResult = $clientStmt->get_result();
    $client = $clientResult->fetch_assoc();
    $clientStmt->close();

    if (!$client) {
        ApiResponse::error('Client not found.', 404);
    }

    $sql = "
        SELECT
            i.invoice_number AS document_number,
            i.invoice_date AS document_date,
            CAST(i.grand_total AS DECIMAL(12,2)) AS total_amount,
            CAST(COALESCE(i.amount_received, 0) AS DECIMAL(12,2)) AS paid_amount,
            CAST(GREATEST(i.grand_total - COALESCE(i.amount_received, 0), 0) AS DECIMAL(12,2)) AS pending_amount
        FROM invoices i
        WHERE i.client_id = ?
          AND LOWER(COALESCE(i.status, '')) IN ('unpaid', 'partially paid')
          AND GREATEST(i.grand_total - COALESCE(i.amount_received, 0), 0) > 0
    ";

    $types = 'i';
    $params = [$clientId];

    if ($dateFrom !== null) {
        $sql .= ' AND i.invoice_date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    if ($dateTo !== null) {
        $sql .= ' AND i.invoice_date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    $sql .= " ORDER BY {$sortClause}";

    $stmt = $conn->prepare($sql);
    bind_statement_params($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];
    $totalPending = 0.0;

    while ($row = $result->fetch_assoc()) {
        $total = (float) ($row['total_amount'] ?? 0);
        $paid = (float) ($row['paid_amount'] ?? 0);
        $pending = (float) ($row['pending_amount'] ?? 0);

        $documents[] = [
            'document_number' => $row['document_number'],
            'document_date' => $row['document_date'],
            'total' => $total,
            'paid' => $paid,
            'pending' => $pending,
        ];

        $totalPending += $pending;
    }

    $stmt->close();

    return [
        'client_id' => $clientId,
        'client_name' => $client['company_name'],
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'sort' => $sortClause,
        'total_pending' => (float) number_format($totalPending, 2, '.', ''),
        'documents' => $documents,
    ];
}

try {
    $clientId = (int) request_value('client_id', 0);
    if ($clientId <= 0) {
        ApiResponse::error('client_id is required.', 422);
    }

    $dateFrom = parse_statement_date(request_value('date_from'));
    $dateTo = parse_statement_date(request_value('date_to'));

    if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
        ApiResponse::error('date_from cannot be later than date_to.', 422);
    }

    $sortClause = resolve_statement_sort(request_value('sort', 'oldest'));
    $statement = fetch_client_statement($conn, $clientId, $dateFrom, $dateTo, $sortClause);

    $format = strtolower((string) request_value('format', 'json'));
    if ($format === 'pdf') {
        $filenameClient = preg_replace('/[^A-Za-z0-9_-]+/', '_', $statement['client_name']) ?: 'client';
        $filename = 'client_statement_' . strtolower($filenameClient) . '_' . date('Ymd_His') . '.pdf';
        $pdf = render_statement_pdf($statement);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    header('Content-Type: application/json');
    ApiResponse::success([
        'total_pending' => $statement['total_pending'],
        'documents' => array_map(
            static fn(array $document): array => [
                'document_number' => $document['document_number'],
                'total' => (float) statement_money((float) $document['total']),
                'paid' => (float) statement_money((float) $document['paid']),
                'pending' => (float) statement_money((float) $document['pending']),
            ],
            $statement['documents']
        ),
        'client_id' => $statement['client_id'],
        'client_name' => $statement['client_name'],
        'date_from' => $statement['date_from'],
        'date_to' => $statement['date_to'],
    ]);
} catch (Throwable $e) {
    error_log('Client Statement API Error: ' . $e->getMessage());

    header('Content-Type: application/json');
    ApiResponse::error('Failed to generate client statement: ' . $e->getMessage(), 500);
}
