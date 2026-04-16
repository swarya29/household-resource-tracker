<?php
// debug_api.php
session_start();
$_SESSION['user'] = 'tanmay';
$_SESSION['user_id'] = 1;

// Mock the request method
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock the JSON input
$input = json_encode([
    'date' => date('Y-m-d'),
    'water' => 10.5,
    'energy' => 5.2
]);

// Use a temporary file to mock php://input if needed, 
// but in PHP 5.6+ we can't easily mock php://input without stream wrappers.
// However, we can use a custom function or just manually set the input in the target file.

// Let's just include the file and see what happens.
// Note: api/usage.php will try to read from php://input.
// So we might need to modify api/usage.php temporarily or use a different approach.

echo "--- START API OUTPUT ---\n";
include 'api/usage.php';
echo "\n--- END API OUTPUT ---\n";
?>
