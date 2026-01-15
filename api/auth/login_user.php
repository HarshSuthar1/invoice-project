<?php
header('Content-Type: application/json');
include '../../includes/config.php';

$data = json_decode(file_get_contents('php://input'), true);
// Accept form-encoded fallback for non-JS form submissions
if (!$data || !is_array($data)) {
    $data = $_POST ?? [];
}
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_logged_in'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => '/Business%20project/public/index.php?page=dashboard'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password'
    ]);
}