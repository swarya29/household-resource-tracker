<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "resource_tracker";

$conn = mysqli_connect($host,$user,$password,$database);

if(!$conn){
die("Connection failed");
}
