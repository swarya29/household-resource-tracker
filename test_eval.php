<?php
include 'db.php';

$user_id = 3;
$resources = ['electricity', 'water', 'gas'];
$today = date('Y-m-d');

foreach($resources as $resource_type) {
    echo "--- Checking $resource_type ---\n";
    
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
         echo "No limits matched.\n";
         continue;
    }
    
    echo "Limit value: {$userData['limit_value']} {$userData['unit']}\n";
    
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
    echo "Current usage today ($today): $current_usage\n";
    
    if ($current_usage >= (float)$userData['limit_value']) {
        echo "--> LIMIT REACHED!\n";
    } else {
        echo "--> Under limit.\n";
    }
}
?>
