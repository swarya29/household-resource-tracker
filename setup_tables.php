<?php
// setup_tables.php — Run this ONCE to create devices & device_usage tables
include "db.php";

$sqls = [
    "CREATE TABLE IF NOT EXISTS `devices` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `name` varchar(255) NOT NULL,
        `resource_type` varchar(50) NOT NULL DEFAULT 'electricity',
        `consumption_rate` float NOT NULL DEFAULT 0,
        `unit` varchar(50) NOT NULL DEFAULT 'watts',
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `device_usage` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `device_id` int(11) NOT NULL,
        `start_time` datetime NOT NULL,
        `end_time` datetime NOT NULL,
        `duration` float NOT NULL DEFAULT 0,
        `consumption` float NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `device_id` (`device_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Add FK only if not exists (ignored if already there)
    "ALTER TABLE `devices` ADD CONSTRAINT `devices_user_fk` FOREIGN KEY IF NOT EXISTS (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE",
    "ALTER TABLE `device_usage` ADD CONSTRAINT `du_user_fk` FOREIGN KEY IF NOT EXISTS (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE",
    "ALTER TABLE `device_usage` ADD CONSTRAINT `du_device_fk` FOREIGN KEY IF NOT EXISTS (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE",
];

$errors = [];
foreach ($sqls as $sql) {
    if (!mysqli_query($conn, $sql)) {
        $errors[] = mysqli_error($conn) . " | SQL: " . substr($sql, 0, 80);
    }
}

header("Content-Type: application/json");
if (empty($errors)) {
    echo json_encode(["status" => "success", "message" => "Tables created/verified successfully."]);
} else {
    echo json_encode(["status" => "partial", "errors" => $errors]);
}
?>
