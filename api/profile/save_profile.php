<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

// Protect API
ApiAuth::requireLogin();

try {
    $user = Auth::user();

    if (!$user || empty($user['id'])) {
        ApiResponse::error('Unauthorized', 401);
    }

    // Basic validation
    if (empty($_POST['name']) || empty($_POST['email'])) {
        ApiResponse::error('Name and email are required', 422);
    }

    $name            = trim($_POST['name']);
    $email           = trim($_POST['email']);
    $phone           = trim($_POST['phone'] ?? '');
    $company_name    = trim($_POST['company_name'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $gst_number      = trim($_POST['gst_number'] ?? '');

    $stmt = $conn->prepare("
        UPDATE users SET
            name = ?,
            email = ?,
            phone = ?,
            company_name = ?,
            company_address = ?,
            gst_number = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssssi",
        $name,
        $email,
        $phone,
        $company_name,
        $company_address,
        $gst_number,
        $user['id']
    );

    $stmt->execute();

    ApiResponse::success([], 'Profile updated successfully');

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to update profile: ' . $e->getMessage(),
        500
    );
}
