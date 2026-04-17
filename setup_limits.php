<?php
// setup_limits.php
header('Content-Type: application/json');

try {
    $conn = new mysqli('localhost', 'root', '', 'resource_tracker');
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // 1. Add email column to users table
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL");
        // Give a default recognizable email to tanishqpote just for reference
        $conn->query("UPDATE users SET email = 'tanishq@example.com' WHERE username = 'tanishqpote'");
    }

    // 2. Create user_limits table
    $sql_limits = "CREATE TABLE IF NOT EXISTS user_limits (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        resource_type VARCHAR(50) NOT NULL,
        limit_value FLOAT NOT NULL,
        unit VARCHAR(50) NOT NULL,
        alert_sent BOOLEAN DEFAULT 0,
        last_reset_date DATE DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_resource (user_id, resource_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if (!$conn->query($sql_limits)) {
        throw new Exception("Error creating user_limits: " . $conn->error);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Limits schema created successfully! Email column added to users."
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
