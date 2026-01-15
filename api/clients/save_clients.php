<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

// Protect API
ApiAuth::requireLogin();

try {
    // Basic validation
    if (empty($_POST['company_name'])) {
        ApiResponse::error('Company name is required', 422);
    }

    $company_name     = trim($_POST['company_name']);
    $contact_person   = trim($_POST['contact_person'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $gst_number       = trim($_POST['gst_number'] ?? '');
    $client_id        = $_POST['id'] ?? null;

    if ($client_id) {
        // Update client
        $stmt = $conn->prepare("
            UPDATE clients SET
                company_name = ?,
                contact_person = ?,
                email = ?,
                phone = ?,
                address = ?,
                gst_number = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssssi",
            $company_name,
            $contact_person,
            $email,
            $phone,
            $address,
            $gst_number,
            $client_id
        );

        $stmt->execute();

        ApiResponse::success([], 'Client updated successfully');

    } else {
        // Create new client
        $stmt = $conn->prepare("
            INSERT INTO clients (
                company_name,
                contact_person,
                email,
                phone,
                address,
                gst_number,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "ssssss",
            $company_name,
            $contact_person,
            $email,
            $phone,
            $address,
            $gst_number
        );

        $stmt->execute();

        ApiResponse::success([], 'Client added successfully');
    }

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to save client: ' . $e->getMessage(),
        500
    );
}
