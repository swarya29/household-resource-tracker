<?php
// Direct DB diagnostic — test exact API queries
$c = mysqli_connect('localhost','root','','resource_tracker');
if (!$c) die("DB connection failed");

// Check each user's data
$users = mysqli_query($c, "SELECT id, username FROM users");
while ($u = mysqli_fetch_assoc($users)) {
    $uid = $u['id'];
    echo "\n=== USER: {$u['username']} (id=$uid) ===\n";
    
    // Resource summary
    $r = mysqli_query($c, "SELECT d.resource_type, SUM(u.consumption) AS total
        FROM device_usage u JOIN devices d ON u.device_id = d.id
        WHERE u.user_id = $uid GROUP BY d.resource_type");
    echo "-- resource_summary:\n";
    while ($row = mysqli_fetch_assoc($r)) print_r($row);
    
    // Device wise
    $r = mysqli_query($c, "SELECT d.name AS device_name, d.resource_type, d.unit, SUM(u.consumption) AS total
        FROM device_usage u JOIN devices d ON u.device_id = d.id
        WHERE u.user_id = $uid GROUP BY d.id ORDER BY total DESC");
    echo "-- device_wise:\n";
    while ($row = mysqli_fetch_assoc($r)) print_r($row);
    
    // Hourly
    $r = mysqli_query($c, "SELECT DATE_FORMAT(u.start_time, '%Y-%m-%d %H:00:00') AS hour,
        d.resource_type, SUM(u.consumption) AS total
        FROM device_usage u JOIN devices d ON u.device_id = d.id
        WHERE u.user_id = $uid GROUP BY hour, d.resource_type ORDER BY hour ASC LIMIT 96");
    echo "-- hourly_breakdown:\n";
    while ($row = mysqli_fetch_assoc($r)) print_r($row);
    
    // Recent logs
    $r = mysqli_query($c, "SELECT u.id, u.device_id, d.name AS device_name,
        d.resource_type, u.start_time, u.end_time, u.duration, u.consumption
        FROM device_usage u JOIN devices d ON u.device_id = d.id
        WHERE u.user_id = $uid ORDER BY u.start_time DESC LIMIT 15");
    echo "-- recent_logs:\n";
    while ($row = mysqli_fetch_assoc($r)) print_r($row);
}

echo "\n=== RAW DEVICES TABLE ===\n";
$r = mysqli_query($c, "SELECT * FROM devices");
while ($row = mysqli_fetch_assoc($r)) print_r($row);

echo "\n=== RAW DEVICE_USAGE TABLE ===\n";
$r = mysqli_query($c, "SELECT * FROM device_usage");
while ($row = mysqli_fetch_assoc($r)) print_r($row);
?>
