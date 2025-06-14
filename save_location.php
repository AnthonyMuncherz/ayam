<?php 
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit();
}

// Get the location from POST
$location = $_POST['location'] ?? '';

if (empty($location)) {
    http_response_code(400);
    echo json_encode(["error" => "No location provided"]);
    exit();
}

// Connect to MySQL database
$conn = new mysqli("localhost", "root", "", "ayam_gepuk");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Prepare and execute update query
$email = $_SESSION['email'];
$stmt = $conn->prepare("UPDATE users SET location = ? WHERE email = ?");
$stmt->bind_param("ss", $location, $email);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

// Clean up
$stmt->close();
$conn->close();
?>
