<?php
$data = json_encode(['device_id'=>3, 'start_time'=>date('Y-m-d H:i:s', time()-3600*2), 'end_time'=>date('Y-m-d H:i:s')]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nCookie: PHPSESSID=" . (isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : '') . "\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/household-resource-tracker/debug_api.php', false, $context);
echo "Result:\n" . $result . "\n";
?>
