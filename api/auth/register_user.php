<?php
header('Content-Type: application/json');
include '../../includes/config.php';

$data = json_decode(file_get_contents('php://input'), true);
// Accept form-encoded fallback for non-JS form submissions
if (!$data || !is_array($data)) {
    $data = $_POST ?? [];
}

// Validate input
if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Check if username exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $data['username']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit;
}

// Hash password
$password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $data['username'], $password_hash, $data['email']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful', 'redirect' => '/Business%20project/public/index.php?page=login&registered=true']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
} 