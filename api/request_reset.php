<?php
header("Content-Type: application/json; charset=UTF-8");
include "../db.php";
require_once "mailer.php";

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');

if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Email address is required."]);
    exit();
}

try {
    // 1. Check if email exists
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // For security, don't reveal if email exists or not, but for this app we will just be helpful
        echo json_encode(["status" => "error", "message" => "We couldn't find an account with that email address."]);
        exit();
    }

    // 2. Generate token
    $token = bin2hex(random_bytes(32));

    // 3. Update DB - Use MySQL NOW() + interval to avoid timezone dsync
    $stmt = $conn->prepare("UPDATE users SET auth_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
    $stmt->bind_param("si", $token, $user['id']);
    $stmt->execute();
    $stmt->close();

    // 4. Send Email
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $uri = str_replace("api/request_reset.php", "reset_password.php", $_SERVER['REQUEST_URI']);
    $reset_link = "$protocol://$host$uri?token=$token";

    // In local dev, maybe the host is just localhost
    $sent = \PHPMailer\PHPMailer\send_password_reset_email($email, $user['username'], $reset_link);

    if ($sent) {
        echo json_encode(["status" => "success", "message" => "Reset link has been sent to your email!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to send email. Please check api_errors.log"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "System error: " . $e->getMessage()]);
}
?>
