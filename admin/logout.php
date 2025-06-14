<?php
// Start session with secure configuration
session_start([
    'cookie_lifetime' => 0,
    'cookie_path' => '/',
    'cookie_domain' => $_SERVER['HTTP_HOST'] ?? '',
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

// Include database connection for logging
require_once '../db_connection.php';

// Log logout action if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO admin_logs (user_id, action, ip_address, user_agent) VALUES (?, 'logout', ?, ?)");
        $action = 'logout';
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $stmt->bind_param("iss", $_SESSION['user_id'], $ip, $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        // Silently fail if logging fails
    }
}

// Destroy all session data
session_unset();
session_destroy();

// Remove session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear any application-specific cookies
setcookie('admin_remember', '', time() - 3600, '/');

// Redirect to admin login page
header('Location: admin_login.php?message=logged_out');
exit();
?> 