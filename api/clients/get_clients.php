<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

// Protect API
ApiAuth::requireLogin();

try {
    $clients = [];

    $sql = "
        SELECT 
            id,
            company_name,
            contact_person,
            email,
            phone,
            address,
            gst_number,
            created_at
        FROM clients
        ORDER BY company_name ASC
    ";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
    }

    ApiResponse::success([
        'clients' => $clients
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to fetch clients: ' . $e->getMessage(),
        500
    );
}
