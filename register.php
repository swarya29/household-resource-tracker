<?php
include "db.php";

if(isset($_POST['register'])){

$username=$_POST['username'];
$password=$_POST['password'];

$query="INSERT INTO users(username,password)
VALUES('$username','$password')";

mysqli_query($conn,$query);

header("Location:login.php");

}
?>

<!DOCTYPE html>
<html>

<head>

<title>Register</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card p-4 shadow">

<h3 class="mb-3">Register</h3>

<form method="POST">

<input class="form-control mb-3" name="username" placeholder="Username">

<input class="form-control mb-3" type="password" name="password" placeholder="Password">

<button class="btn btn-success" name="register">Register</button>

</form>

</div>

</div>

</body>

</html>
