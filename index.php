<?php
session_start();
include "db.php";

if(isset($_POST['login'])){

$username=$_POST['username'];
$password=$_POST['password'];

$query="SELECT * FROM users WHERE username='$username' AND password='$password'";
$result=mysqli_query($conn,$query);

if(mysqli_num_rows($result)>0){

$_SESSION['user']=$username;

}else{
$error="Invalid login";
}

}

if(isset($_POST['register'])){

$username=$_POST['username'];
$password=$_POST['password'];

$query="INSERT INTO users(username,password)
VALUES('$username','$password')";

mysqli_query($conn,$query);

$success="Account created! Please login.";

}

if(isset($_GET['logout'])){
session_destroy();
header("Location:index.php");
}

?>
<!DOCTYPE html>
<html>
<head>

<title>Smart Resource Tracker</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<?php if(!isset($_SESSION['user'])){ ?>

<div class="row">

<!-- LOGIN -->

<div class="col-md-6">

<div class="card p-4 shadow">

<h3>Login</h3>

<form method="POST">

<input class="form-control mb-3" name="username" placeholder="Username">

<input class="form-control mb-3" type="password" name="password" placeholder="Password">

<button class="btn btn-primary" name="login">Login</button>

</form>

<?php if(isset($error)){ echo "<p class='text-danger'>$error</p>"; } ?>

</div>

</div>

<!-- REGISTER -->

<div class="col-md-6">

<div class="card p-4 shadow">

<h3>Register</h3>

<form method="POST">

<input class="form-control mb-3" name="username" placeholder="Username">

<input class="form-control mb-3" type="password" name="password" placeholder="Password">

<button class="btn btn-success" name="register">Register</button>

</form>

<?php if(isset($success)){ echo "<p class='text-success'>$success</p>"; } ?>

</div>

</div>

</div>

<?php } else { ?>

<!-- MAIN TRACKER PAGE -->

<h2>Welcome <?php echo $_SESSION['user']; ?></h2>

<a href="?logout=true" class="btn btn-danger mb-3">Logout</a>

<div class="card p-4 shadow">

<form action="save.php" method="POST">

<label>Date</label>
<input class="form-control mb-3" type="date" name="date">

<label>Water Usage (Liters)</label>
<input class="form-control mb-3" type="number" name="water">

<label>Energy Usage (kWh)</label>
<input class="form-control mb-3" type="number" step="0.1" name="energy">

<button class="btn btn-success">Save Data</button>

<a href="dashboard.php" class="btn btn-primary">Dashboard</a>

</form>

</div>

<?php } ?>

</div>

</body>
</html>
