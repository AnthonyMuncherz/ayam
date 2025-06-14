<?php
$servername = "localhost";
$username = "root"; // Default in XAMPP
$password = "";     // Default in XAMPP
$dbname = "ayam_gepuk"; // Your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
