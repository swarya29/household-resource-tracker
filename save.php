<?php

include "db.php";

$date = $_POST['date'];
$water = $_POST['water'];
$energy = $_POST['energy'];

$sql = "INSERT INTO usage_data(date,water_usage,energy_usage)
VALUES('$date','$water','$energy')";

if(mysqli_query($conn,$sql)){
echo "Data Saved Successfully";
}
else{
echo "Error";
}

?>

<br><br>
<a href="index.php">Go Back</a>
