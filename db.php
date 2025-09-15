<?php
// Database configuration
$host = "localhost";     // DB host
$user = "root";          // DB username
$pass = "";              // DB password
$dbname = "computer_course";  // DB name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
