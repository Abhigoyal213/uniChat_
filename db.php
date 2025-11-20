<?php
$db_url = getenv("DATABASE_URL");
if (!$db_url) die("DATABASE_URL not set");

$parts = parse_url($db_url);

$host = $parts['host'];
$port = $parts['port'];
$user = $parts['user'];
$pass = $parts['pass'];
$dbname = ltrim($parts['path'], '/');

$conn = mysqli_connect($host, $user, $pass, $dbname, $port);
if (!$conn) die("Connection failed: " . mysqli_connect_error());
?>
