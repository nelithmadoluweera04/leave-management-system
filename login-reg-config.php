<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "employee_db";

$conn = new mysqli($host, $user, $password, $database, 3307);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
