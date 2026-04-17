<?php
include "db.php";

// Fetch the actual token from DB
$stmt = $conn->prepare("SELECT auth_token FROM users WHERE email = 'tanishq25comp@student.mes.ac.in'");
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$token = $row['auth_token'];

if (!$token) die("No token found in DB\n");

$data = json_encode(['token' => $token, 'password' => 'newpassword123']);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/household-resource-tracker/api/reset_password_action.php', false, $context);
echo "Response: " . $result . "\n";

// Check DB for updated password and cleared token
$stmt = $conn->prepare("SELECT password, auth_token FROM users WHERE email = 'tanishq25comp@student.mes.ac.in'");
$stmt->execute();
$after = $stmt->get_result()->fetch_assoc();
echo "Updated Password: " . $after['password'] . "\n";
echo "Token cleared: " . ($after['auth_token'] === null ? "Yes" : "No") . "\n";
?>
