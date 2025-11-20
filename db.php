<?php
$host =  "sql100.infinityfree.com";  // from infinityfree
$username = "if0_40450453";   // your mysql username
$password = "WVVUvJ3kStV"; // your mysql password
$dbname = "if0_40450453_unichat"; // your database name

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$conn->query("SET time_zone = '+00:00'");

?>
