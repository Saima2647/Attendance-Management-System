<?php
$servername = "localhost";
$username = "root"; // Change if using a different username
$password = ""; // Change if using a password
$database = "attendance_system"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
