<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

// Protect API
ApiAuth::requireLogin();

try {
    if (!Auth::check()) {
        ApiResponse::error('Unauthorized', 401);
    }

    $user = Auth::user();

    if (!$user || empty($user['id'])) {
        ApiResponse::error('User not found', 404);
    }

    $stmt = $conn->prepare("
        SELECT 
            id,
            name,
            email,
            phone,
            company_name,
            company_address,
            gst_number
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $user['id']);
    $stmt->execute();

    $profile = $stmt->get_result()->fetch_assoc();

    if (!$profile) {
        ApiResponse::error('Profile not found', 404);
    }

    ApiResponse::success([
        'profile' => $profile
    ]);

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to fetch profile: ' . $e->getMessage(),
        500
    );
}
