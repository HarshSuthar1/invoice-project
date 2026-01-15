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
    if (empty($_POST['company_name']) || empty($_POST['email'])) {
        ApiResponse::error('Company name and email are required', 422);
    }

    // Collect all fields
    $company_name           = trim($_POST['company_name']);
    $email                  = trim($_POST['email']);
    $phone                  = trim($_POST['phone'] ?? '');
    $address                = trim($_POST['address'] ?? '');
    $gst_number             = trim($_POST['gst_number'] ?? '');
    $website                = trim($_POST['website'] ?? '');
    $pan_number             = trim($_POST['pan_number'] ?? '');
    
    // Banking information
    $bank_name              = trim($_POST['bank_name'] ?? '');
    $account_number         = trim($_POST['account_number'] ?? '');
    $ifsc_code              = trim($_POST['ifsc_code'] ?? '');
    $account_holder_name    = trim($_POST['account_holder_name'] ?? '');
    $branch_name            = trim($_POST['branch_name'] ?? '');
    $swift_code             = trim($_POST['swift_code'] ?? '');
    
    // Invoice preferences
    $invoice_prefix         = trim($_POST['invoice_prefix'] ?? '');
    $default_due_days       = (int) ($_POST['default_due_days'] ?? 30);
    $invoice_terms          = trim($_POST['invoice_terms'] ?? '');
    $payment_instructions   = trim($_POST['payment_instructions'] ?? '');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ApiResponse::error('Invalid email format', 422);
    }

    // Update company_profile (typically id=1 for single-company setup)
    $stmt = $conn->prepare("
        UPDATE company_profile SET
            company_name = ?,
            gst_number = ?,
            email = ?,
            phone = ?,
            website = ?,
            pan_number = ?,
            address = ?,
            bank_name = ?,
            account_number = ?,
            ifsc_code = ?,
            account_holder_name = ?,
            branch_name = ?,
            swift_code = ?,
            invoice_prefix = ?,
            default_due_days = ?,
            invoice_terms = ?,
            payment_instructions = ?
        WHERE id = 1
    ");

    $stmt->bind_param(
        "sssssssssssssssss",
        $company_name,
        $gst_number,
        $email,
        $phone,
        $website,
        $pan_number,
        $address,
        $bank_name,
        $account_number,
        $ifsc_code,
        $account_holder_name,
        $branch_name,
        $swift_code,
        $invoice_prefix,
        $default_due_days,
        $invoice_terms,
        $payment_instructions
    );

    $stmt->execute();

    if ($stmt->errno) {
        ApiResponse::error('Failed to update profile: ' . $stmt->error, 500);
    }

    ApiResponse::success([], 'Profile updated successfully');

} catch (Throwable $e) {
    ApiResponse::error(
        'Failed to update profile: ' . $e->getMessage(),
        500
    );
}