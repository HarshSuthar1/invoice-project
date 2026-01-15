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

    // Get company profile (typically id=1 for single-company setup)
    // You can also link this to user_id if you want multi-company support
    $stmt = $conn->prepare("
        SELECT 
            id,
            company_name,
            gst_number,
            email,
            phone,
            website,
            pan_number,
            address,
            bank_name,
            account_number,
            ifsc_code,
            account_holder_name,
            branch_name,
            swift_code,
            invoice_prefix,
            default_due_days,
            invoice_terms,
            payment_instructions,
            updated_at
        FROM company_profile
        WHERE id = 1
        LIMIT 1
    ");

    $stmt->execute();

    $profile = $stmt->get_result()->fetch_assoc();

    if (!$profile) {
        // If no profile exists, create a default one
        $stmt = $conn->prepare("
            INSERT INTO company_profile (company_name, email) 
            VALUES ('Your Company Name', 'info@company.com')
        ");
        $stmt->execute();
        
        // Fetch the newly created profile
        $stmt = $conn->prepare("SELECT * FROM company_profile WHERE id = LAST_INSERT_ID()");
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
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