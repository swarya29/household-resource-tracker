<?php
$c = mysqli_connect('localhost','root','','resource_tracker');
echo "=== TABLES ===" . PHP_EOL;
$r = mysqli_query($c,'SHOW TABLES');
while($row = mysqli_fetch_row($r)) echo $row[0] . PHP_EOL;

echo "=== DEVICES ===" . PHP_EOL;
$r = mysqli_query($c,'SELECT COUNT(*) as n FROM devices');
$row = mysqli_fetch_assoc($r);
echo "Count: " . $row['n'] . PHP_EOL;

echo "=== DEVICE_USAGE ===" . PHP_EOL;
$r = mysqli_query($c,'SELECT COUNT(*) as n FROM device_usage');
$row = mysqli_fetch_assoc($r);
echo "Count: " . $row['n'] . PHP_EOL;

echo "=== USERS ===" . PHP_EOL;
$r = mysqli_query($c,'SELECT id, username FROM users');
while($row = mysqli_fetch_assoc($r)) echo $row['id'] . ': ' . $row['username'] . PHP_EOL;
?>
