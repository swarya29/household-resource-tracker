<?php
session_start();
$_SESSION['user_id'] = 3;
$sid = session_id();
session_write_close(); // Prevent session blocking!

$data = json_encode(['resource_type'=>'electricity', 'limit_value'=>5, 'email'=>'tanishq25comp@student.mes.ac.in']);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nCookie: PHPSESSID=" . $sid . "\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/household-resource-tracker/api/endpoints/limits.php', false, $context);
echo "Result:\n" . $result . "\n";
?>
