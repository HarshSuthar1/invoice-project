<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();
require_once __DIR__ . '/_helpers.php';

try {
    ApiResponse::success([
        'clients' => ledger_overview($conn),
        'generated_on' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
    ]);
} catch (Throwable $e) {
    error_log('Ledger Overview API Error: ' . $e->getMessage());
    ApiResponse::error('Failed to load ledger overview: ' . $e->getMessage(), 500);
}
