<?php
$c = mysqli_connect('localhost', 'root', '', 'resource_tracker');
if (!$c) die("DB err\n");

$r = mysqli_query($c, "SELECT * FROM user_limits");
echo "Limits:\n";
while($row = mysqli_fetch_assoc($r)) print_r($row);

$r = mysqli_query($c, "SELECT id, email, username FROM users");
echo "\nUsers:\n";
while($row = mysqli_fetch_assoc($r)) print_r($row);
?>
