<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "tutoring"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
