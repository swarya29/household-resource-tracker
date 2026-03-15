<?php
include "db.php";

if(isset($_POST['login'])){

$username=$_POST['username'];
$password=$_POST['password'];

$query="SELECT * FROM users WHERE username='$username' AND password='$password'";

$result=mysqli_query($conn,$query);

if(mysqli_num_rows($result)>0){

header("Location:index.php");

}else{

echo "Invalid Login";

}

}
?>

<!DOCTYPE html>
<html>

<head>

<title>Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card p-4 shadow">

<h3 class="mb-3">Login</h3>

<form method="POST">

<input class="form-control mb-3" name="username" placeholder="Username">

<input class="form-control mb-3" type="password" name="password" placeholder="Password">

<button class="btn btn-primary" name="login">Login</button>

<a href="register.php" class="btn btn-link">Register</a>

</form>

</div>

</div>

</body>

</html>
