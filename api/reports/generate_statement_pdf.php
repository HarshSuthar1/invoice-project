<?php
declare(strict_types=1);

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

/**
 * Return a request value from POST first, then GET.
 */
function statement_request_value(string $key, mixed $default = null): mixed
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
 * Validate YYYY-MM-DD input.
 */
function statement_parse_date(mixed $value, string $fieldName): string
{
    if (!is_string($value) || trim($value) === '') {
        ApiResponse::error("{$fieldName} is required.", 422);
    }

    $value = trim($value);
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();

    if (
        !$date ||
        ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))
    ) {
        ApiResponse::error("Invalid {$fieldName}. Expected format: YYYY-MM-DD.", 422);
    }

    return $date->format('Y-m-d');
}

/**
 * Format dates for display inside the PDF.
 */
function statement_display_date(string $date): string
{
    return (new DateTimeImmutable($date))->format('d M Y');
}

/**
 * Format an amount with INR symbol.
 */
function statement_currency(float $amount): string
{
    return '₹' . number_format($amount, 2);
}

/**
 * Resolve a filesystem path for the company logo when possible.
 */
function statement_resolve_logo_path(?string $logoUrl): ?string
{
    if (!$logoUrl) {
        return null;
    }

    $logoUrl = trim($logoUrl);
    if ($logoUrl === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $logoUrl)) {
        return $logoUrl;
    }

    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logoUrl);
    $basePath = BASE_PATH;

    $candidates = [
        $normalized,
        $basePath . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR),
        dirname($basePath) . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR),
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

try {
    $autoloadCandidates = [
        BASE_PATH . '/vendor/autoload.php',
        dirname(BASE_PATH) . '/vendor/autoload.php',
    ];

    $autoloadPath = null;
    foreach ($autoloadCandidates as $candidate) {
        if (is_file($candidate)) {
            $autoloadPath = $candidate;
            break;
        }
    }

    if ($autoloadPath === null) {
        throw new RuntimeException('mPDF autoload file not found. Please install dependencies with Composer.');
    }

    require_once $autoloadPath;

    /** @phpstan-ignore-next-line */
    if (!class_exists(\Mpdf\Mpdf::class)) {
        throw new RuntimeException('mPDF library is not available after loading Composer autoload.');
    }

    $clientId = (int) statement_request_value('client_id', 0);
    if ($clientId <= 0) {
        ApiResponse::error('client_id is required.', 422);
    }

    $fromDate = statement_parse_date(statement_request_value('from_date'), 'from_date');
    $toDate = statement_parse_date(statement_request_value('to_date'), 'to_date');

    if ($fromDate > $toDate) {
        ApiResponse::error('from_date cannot be later than to_date.', 422);
    }

    if (!isset($host, $dbname, $username, $password)) {
        throw new RuntimeException('Database configuration variables are not available.');
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $dbname);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $companyStmt = $pdo->query("
        SELECT company_name, logo_url
        FROM company_profile
        WHERE id = 1
        LIMIT 1
    ");
    $companyProfile = $companyStmt->fetch() ?: [
        'company_name' => 'GS Metal Concept',
        'logo_url' => null,
    ];

    $clientStmt = $pdo->prepare("
        SELECT id, company_name AS name
        FROM clients
        WHERE id = :client_id
        LIMIT 1
    ");
    $clientStmt->execute(['client_id' => $clientId]);
    $client = $clientStmt->fetch();

    if (!$client) {
        ApiResponse::error('Client not found.', 404);
    }

    $documentsStmt = $pdo->prepare("
        SELECT
            d.id,
            d.document_number,
            d.created_at,
            CAST(d.grand_total AS DECIMAL(12,2)) AS grand_total,
            CAST(COALESCE(SUM(p.amount), 0) AS DECIMAL(12,2)) AS paid_amount,
            CAST(d.grand_total - COALESCE(SUM(p.amount), 0) AS DECIMAL(12,2)) AS pending_amount
        FROM documents d
        LEFT JOIN payments p ON p.document_id = d.id
        WHERE d.client_id = :client_id
          AND DATE(d.created_at) BETWEEN :from_date AND :to_date
        GROUP BY d.id, d.document_number, d.created_at, d.grand_total
        HAVING pending_amount > 0
        ORDER BY DATE(d.created_at) ASC, d.id ASC
    ");

    $documentsStmt->execute([
        'client_id' => $clientId,
        'from_date' => $fromDate,
        'to_date' => $toDate,
    ]);

    $documents = $documentsStmt->fetchAll();

    $totalPending = 0.0;
    foreach ($documents as &$document) {
        $document['grand_total'] = (float) $document['grand_total'];
        $document['paid_amount'] = (float) $document['paid_amount'];
        $document['pending_amount'] = max(0, (float) $document['pending_amount']);
        $totalPending += $document['pending_amount'];
    }
    unset($document);

    $logoPath = statement_resolve_logo_path($companyProfile['logo_url'] ?? null);
    $logoHtml = '';
    if ($logoPath !== null) {
        $logoHtml = '<div class="logo-wrap"><img src="' . htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') . '" alt="Company Logo"></div>';
    }

    $rowsHtml = '';
    if ($documents === []) {
        $rowsHtml = '
            <tr>
                <td colspan="5" class="empty-state">No outstanding documents found for the selected date range.</td>
            </tr>
        ';
    } else {
        foreach ($documents as $document) {
            $rowsHtml .= '
                <tr>
                    <td>' . htmlspecialchars((string) $document['document_number'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars(statement_display_date(substr((string) $document['created_at'], 0, 10)), ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="amount">' . htmlspecialchars(statement_currency($document['grand_total']), ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="amount">' . htmlspecialchars(statement_currency($document['paid_amount']), ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="amount pending">' . htmlspecialchars(statement_currency($document['pending_amount']), ENT_QUOTES, 'UTF-8') . '</td>
                </tr>
            ';
        }
    }

    $html = '
        <html>
        <head>
            <style>
                body {
                    font-family: sans-serif;
                    font-size: 11px;
                    color: #111827;
                }

                .header {
                    border-bottom: 2px solid #1d4ed8;
                    padding-bottom: 14px;
                    margin-bottom: 18px;
                }

                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .header-table td {
                    vertical-align: top;
                    border: none;
                    padding: 0;
                }

                .logo-wrap {
                    width: 90px;
                }

                .logo-wrap img {
                    max-width: 80px;
                    max-height: 60px;
                }

                .company-name {
                    font-size: 18px;
                    font-weight: bold;
                    color: #0f172a;
                    margin-bottom: 4px;
                }

                .title {
                    font-size: 16px;
                    font-weight: bold;
                    color: #1d4ed8;
                    margin-bottom: 8px;
                }

                .meta {
                    font-size: 11px;
                    line-height: 1.7;
                }

                table.statement-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 18px;
                }

                .statement-table th,
                .statement-table td {
                    border: 1px solid #cbd5e1;
                    padding: 9px 10px;
                }

                .statement-table th {
                    background: #eff6ff;
                    font-weight: bold;
                    text-align: left;
                    color: #0f172a;
                }

                .statement-table td.amount,
                .statement-table th.amount {
                    text-align: right;
                }

                .statement-table td.pending,
                .statement-table th.pending {
                    background: #fef2f2;
                    color: #b91c1c;
                    font-weight: bold;
                }

                .empty-state {
                    text-align: center;
                    color: #64748b;
                    padding: 20px 10px;
                }

                .footer-box {
                    margin-top: 18px;
                    border-top: 2px solid #e2e8f0;
                    padding-top: 12px;
                }

                .total-pending {
                    text-align: right;
                    font-size: 13px;
                    font-weight: bold;
                    margin-bottom: 10px;
                    color: #0f172a;
                }

                .thank-you {
                    text-align: center;
                    font-size: 11px;
                    color: #475569;
                    margin-top: 14px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <table class="header-table">
                    <tr>
                        <td style="width: 90px;">' . $logoHtml . '</td>
                        <td>
                            <div class="company-name">' . htmlspecialchars((string) ($companyProfile['company_name'] ?? 'GS Metal Concept'), ENT_QUOTES, 'UTF-8') . '</div>
                            <div class="title">Client Statement</div>
                            <div class="meta">
                                <strong>Client Name:</strong> ' . htmlspecialchars((string) $client['name'], ENT_QUOTES, 'UTF-8') . '<br>
                                <strong>Date Range:</strong> ' . htmlspecialchars(statement_display_date($fromDate), ENT_QUOTES, 'UTF-8') . ' to ' . htmlspecialchars(statement_display_date($toDate), ENT_QUOTES, 'UTF-8') . '
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="statement-table">
                <thead>
                    <tr>
                        <th>Document Number</th>
                        <th>Date</th>
                        <th class="amount">Total Amount</th>
                        <th class="amount">Paid Amount</th>
                        <th class="amount pending">Pending Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $rowsHtml . '
                </tbody>
            </table>

            <div class="footer-box">
                <div class="total-pending">Total Pending Amount: ' . htmlspecialchars(statement_currency($totalPending), ENT_QUOTES, 'UTF-8') . '</div>
                <div class="thank-you">Thank you for your business</div>
            </div>
        </body>
        </html>
    ';

    $safeClientName = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $client['name']) ?: 'client';
    $downloadFileName = 'Client_Statement_' . $safeClientName . '.pdf';

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 14,
        'margin_right' => 12,
        'margin_bottom' => 18,
        'margin_left' => 12,
    ]);

    $mpdf->SetTitle('Client Statement - ' . (string) $client['name']);
    $mpdf->SetAuthor((string) ($companyProfile['company_name'] ?? 'GS Metal Concept'));
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->SetHTMLFooter('
        <div style="border-top: 1px solid #cbd5e1; font-size: 10px; color: #64748b; padding-top: 6px; text-align: center;">
            Page {PAGENO} of {nbpg}
        </div>
    ');
    $mpdf->WriteHTML($html);
    $mpdf->Output($downloadFileName, \Mpdf\Output\Destination::DOWNLOAD);
    exit;
} catch (Throwable $e) {
    error_log('Generate Statement PDF Error: ' . $e->getMessage());
    header('Content-Type: application/json');
    ApiResponse::error('Failed to generate statement PDF: ' . $e->getMessage(), 500);
}
