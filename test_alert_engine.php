<?php
include 'db.php';
include 'api/usage.php';

// Directly trigger the alert engine for user 3
echo "Running alert engine check...\n";
check_limit_and_send_alert($conn, 3, 'water');
echo "Engine logic completed.\n";
?>
