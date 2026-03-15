<?php
include "db.php";

$result = mysqli_query($conn,"SELECT * FROM usage_data");

$dates = [];
$water = [];
$energy = [];

while($row = mysqli_fetch_assoc($result)){

$dates[] = $row['date'];
$water[] = $row['water_usage'];
$energy[] = $row['energy_usage'];

}
?>

<!DOCTYPE html>
<html>

<head>

<title>Dashboard</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<h2 class="text-center mb-4">Usage Dashboard</h2>

<canvas id="usageChart"></canvas>

<br>

<a href="index.php" class="btn btn-secondary">Back</a>

</div>

<script>

const ctx = document.getElementById('usageChart');

new Chart(ctx, {
type: 'line',
data: {
labels: <?php echo json_encode($dates); ?>,
datasets: [
{
label: 'Water Usage (L)',
data: <?php echo json_encode($water); ?>,
borderWidth: 3
},
{
label: 'Energy Usage (kWh)',
data: <?php echo json_encode($energy); ?>,
borderWidth: 3
}
]
}
});

</script>

</body>
</html>
