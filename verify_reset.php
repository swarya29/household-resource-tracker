<?php
$data = json_encode(['email' => 'tanishq25comp@student.mes.ac.in']);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/household-resource-tracker/api/request_reset.php', false, $context);
echo "Response: " . $result . "\n";

// Check DB for token
include "db.php";
$stmt = $conn->prepare("SELECT auth_token, token_expiry FROM users WHERE email = 'tanishq25comp@student.mes.ac.in'");
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
echo "Token in DB: " . ($row['auth_token'] ? "Yes" : "No") . "\n";
echo "Expiry in DB: " . $row['token_expiry'] . "\n";
?>
