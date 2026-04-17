<?php
ob_start();
header("Content-Type: application/json; charset=UTF-8");
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    include_once "../../db.php";
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized");
    }

    $user_id = (int)$_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $conn->prepare("SELECT id, resource_type, limit_value, unit, alert_sent FROM user_limits WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $limits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $email = $stmt->get_result()->fetch_assoc()['email'] ?? '';
        $stmt->close();

        echo json_encode(["status" => "success", "data" => ["limits" => $limits, "email" => $email]]);

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $resource_type = strtolower(trim($input['resource_type'] ?? ''));
        $limit_value = floatval($input['limit_value'] ?? 0);
        $email = trim($input['email'] ?? '');
        
        if (!$resource_type || $limit_value <= 0) {
            throw new Exception("Invalid limit parameters.");
        }

        $unit = 'hL';
        if ($resource_type === 'electricity') $unit = 'kWh';
        if ($resource_type === 'gas') $unit = 'kg';

        // Update email if provided
        if (!empty($email)) {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // Upsert limit
        $stmt = $conn->prepare("
            INSERT INTO user_limits (user_id, resource_type, limit_value, unit) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE limit_value = VALUES(limit_value), unit = VALUES(unit), alert_sent = 0
        ");
        $stmt->bind_param("isss", $user_id, $resource_type, $limit_value, $unit);
        $stmt->execute();
        $stmt->close();

        // 🚨 Immediately evaluate usage to see if the new limit is already exceeded
        require_once __DIR__ . '/../alert_engine.php';
        check_limit_and_send_alert($conn, $user_id, $resource_type);

        echo json_encode(["status" => "success", "message" => ucfirst($resource_type) . " limit set to $limit_value $unit!"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
ob_end_flush();
?>
