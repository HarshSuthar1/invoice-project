<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

// Protect API
ApiAuth::requireLogin();

try {
    // Handle DELETE operation
    if (!empty($_POST['delete']) && !empty($_POST['id'])) {
        $client_id = (int) $_POST['id'];
        
        $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        
        ApiResponse::success([], 'Client deleted successfully');
    }
    
    // Basic validation for save/update
    if (empty($_POST['company_name'])) {
        ApiResponse::error('Company name is required', 422);
    }

    $company_name     = trim($_POST['company_name']);
    $contact_person   = trim($_POST['contact_person'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $gst_number       = trim($_POST['gst_number'] ?? '');
    $notes            = trim($_POST['notes'] ?? '');
    $client_id        = !empty($_POST['id']) ? (int)$_POST['id'] : null;

    // Validate email format if provided
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('Invalid email format', 422);
    }

    if ($client_id) {
        // Update existing client (updated_at will auto-update via ON UPDATE CURRENT_TIMESTAMP)
        $stmt = $conn->prepare("
            UPDATE clients SET
                company_name = ?,
                contact_person = ?,
                email = ?,
                phone = ?,
                gst_number = ?,
                address = ?,
                notes = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssssssi",
            $company_name,
            $contact_person,
            $email,
            $phone,
            $gst_number,
            $address,
            $notes,
            $client_id
        );

        $stmt->execute();

        // Don't error on 0 affected rows - data might be unchanged
        if ($stmt->errno) {
            ApiResponse::error('Failed to update client', 500);
        }

        ApiResponse::success([], 'Client updated successfully');

    } else {
        // Create new client (created_at and updated_at will auto-populate)
        $stmt = $conn->prepare("
            INSERT INTO clients (
                company_name,
                contact_person,
                email,
                phone,
                gst_number,
                address,
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssss",
            $company_name,
            $contact_person,
            $email,
            $phone,
            $gst_number,
            $address,
            $notes
        );

        $stmt->execute();
        
        $new_id = $conn->insert_id;

        ApiResponse::success(['id' => $new_id], 'Client added successfully');
    }

} catch (mysqli_sql_exception $e) {
    error_log("Database error in save_clients.php: " . $e->getMessage());
    ApiResponse::error('Database error: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    error_log("Error in save_clients.php: " . $e->getMessage());
    ApiResponse::error('Failed to save client: ' . $e->getMessage(), 500);
}