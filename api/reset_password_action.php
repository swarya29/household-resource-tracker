<?php
header("Content-Type: application/json; charset=UTF-8");
include "../db.php";

$data = json_decode(file_get_contents("php://input"), true);
$token = trim($data['token'] ?? '');
$password = $data['password'] ?? '';

if (empty($token) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Invalid request details."]);
    exit();
}

if (strlen($password) < 4) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 4 characters long."]);
    exit();
}

try {
    // 1. Verify token
    $stmt = $conn->prepare("SELECT id FROM users WHERE auth_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "Invalid or expired reset token."]);
        exit();
    }

    // 2. Update password and clear token
    // NOTE: Site currently uses plain text (not ideal, better to use password_hash)
    $stmt = $conn->prepare("UPDATE users SET password = ?, auth_token = NULL, token_expiry = NULL WHERE id = ?");
    $stmt->bind_param("si", $password, $user['id']);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Password updated successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error: " . $stmt->error]);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "System error: " . $e->getMessage()]);
}
?>