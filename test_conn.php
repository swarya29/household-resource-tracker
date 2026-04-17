<?php
// test_conn.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

if ($conn) {
    echo "Connection successful!\n";
    $res = $conn->query("SHOW TABLES");
    while ($row = $res->fetch_row()) {
        echo "Table: " . $row[0] . "\n";
    }
} else {
    echo "Connection failed: " . mysqli_connect_error() . "\n";
}
?>