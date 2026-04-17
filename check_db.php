<?php
include "db.php";
$queries = [
    "DESCRIBE usage_data",
    "SELECT COUNT(*) FROM usage_data"
];

foreach ($queries as $query) {
    echo "Running query: $query\n";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        print_r($rows);
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
    echo "-------------------\n";
}
?>