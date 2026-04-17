<?php
// Function moved into central alert_engine to avoid redeclaration and to execute globally
function check_limit_and_send_alert($conn, $user_id, $resource_type) {
    $stmt = $conn->prepare("
        SELECT u.email, u.username, l.id as limit_id, l.limit_value, l.unit, l.alert_sent, l.last_reset_date 
        FROM users u 
        LEFT JOIN user_limits l ON u.id = l.user_id AND l.resource_type = ? 
        WHERE u.id = ?");
    $stmt->bind_param("si", $resource_type, $user_id);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$userData || empty($userData['limit_id']) || empty($userData['limit_value'])) {
         return;
    }
    
    $today = date('Y-m-d');
    if ($userData['last_reset_date'] !== $today) {
        $userData['alert_sent'] = 0;
        $conn->query("UPDATE user_limits SET alert_sent = 0, last_reset_date = '$today' WHERE id = " . $userData['limit_id']);
    }

    if ($userData['alert_sent']) return;

    $stmt = $conn->prepare("
        SELECT SUM(u.consumption) as total_today 
        FROM device_usage u 
        JOIN devices d ON u.device_id = d.id 
        WHERE u.user_id = ? AND d.resource_type = ? AND DATE(u.start_time) = ?");
    $stmt->bind_param("iss", $user_id, $resource_type, $today);
    $stmt->execute();
    $usageData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $current_usage = (float)($usageData['total_today'] ?? 0);
    $limit_value = (float)$userData['limit_value'];

    if ($current_usage >= $limit_value) {
        include_once __DIR__ . '/mailer.php';
        $sent = \PHPMailer\PHPMailer\send_alert_email(
            $userData['email'], $userData['username'], $resource_type, 
            $limit_value, round($current_usage, 3), $userData['unit'], date('Y-m-d H:i:s')
        );
        if ($sent) {
            $conn->query("UPDATE user_limits SET alert_sent = 1 WHERE id = " . $userData['limit_id']);
        }
    }
}
?>
