<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

ApiAuth::requireLogin();

try {
    $invoice_id = (int) ($_POST['invoice_id'] ?? $_POST['id'] ?? 0);
    if ($invoice_id <= 0) {
        ApiResponse::error('Invoice ID is required', 422);
    }

    $should_delete = filter_var($_POST['delete'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($should_delete) {
        $conn->begin_transaction();

        $delete_items = $conn->prepare('DELETE FROM invoice_items WHERE invoice_id = ?');
        $delete_items->bind_param('i', $invoice_id);
        $delete_items->execute();

        $delete_invoice = $conn->prepare('DELETE FROM invoices WHERE id = ?');
        $delete_invoice->bind_param('i', $invoice_id);
        $delete_invoice->execute();

        if ($delete_invoice->affected_rows < 1) {
            $conn->rollback();
            ApiResponse::error('Document not found', 404);
        }

        $conn->commit();
        ApiResponse::success([], 'Document deleted successfully');
    }

    $status = $_POST['status'] ?? 'unpaid';
    $amount_received = (float) ($_POST['amount_received'] ?? 0);

    $stmt = $conn->prepare('
        UPDATE invoices SET
            status = ?,
            amount_received = ?
        WHERE id = ?
    ');
    $stmt->bind_param('sdi', $status, $amount_received, $invoice_id);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        $exists = $conn->prepare('SELECT id FROM invoices WHERE id = ?');
        $exists->bind_param('i', $invoice_id);
        $exists->execute();

        if (!$exists->get_result()->fetch_assoc()) {
            ApiResponse::error('Document not found', 404);
        }
    }

    ApiResponse::success([], 'Invoice updated successfully');
} catch (Throwable $e) {
    if (isset($conn) && $conn->errno === 0) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackError) {
        }
    }

    ApiResponse::error(
        'Failed to update invoice: ' . $e->getMessage(),
        500
    );
}
