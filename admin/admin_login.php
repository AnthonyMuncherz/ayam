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

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Include database connection
require_once '../db_connection.php';

$message = '';
$message_type = '';

// Check if user is already logged in as admin
if (isset($_SESSION['user_id'])) {
    // Check if user is admin
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'admin') {
        header('Location: index.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Rate limiting (simple implementation)
    $ip = $_SERVER['REMOTE_ADDR'];
    $current_time = time();
    
    // Check for login attempts in the last 15 minutes
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
    $time_limit = $current_time - (15 * 60); // 15 minutes ago
    $stmt->bind_param("si", $ip, $time_limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempts = $result->fetch_assoc()['attempts'];
    
    if ($attempts >= 5) {
        $message = "Too many login attempts. Please try again in 15 minutes.";
        $message_type = 'error';
    } else {
        // Validate input
        if (empty($username) || empty($password)) {
            $message = "Please fill in all fields.";
            $message_type = 'error';
        } else {
            // Check credentials
            $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if user is admin
                if ($user['role'] === 'admin') {
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    // Clear any failed login attempts for this IP
                    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                    $stmt->bind_param("s", $ip);
                    $stmt->execute();
                    
                    // Log successful login
                    $stmt = $conn->prepare("INSERT INTO admin_logs (user_id, action, ip_address, user_agent) VALUES (?, 'login', ?, ?)");
                    $action = 'login';
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    $stmt->bind_param("iss", $user['id'], $ip, $user_agent);
                    $stmt->execute();
                    
                    header('Location: index.php');
                    exit();
                } else {
                    $message = "Access denied. Admin privileges required.";
                    $message_type = 'error';
                }
            } else {
                $message = "Invalid username or password.";
                $message_type = 'error';
                
                // Log failed attempt
                $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, ?)");
                $stmt->bind_param("ssi", $ip, $username, $current_time);
                $stmt->execute();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Ayam Gepuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .login-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #f5c542;
        }

        .login-header p {
            color: #d1d5db;
            font-size: 0.875rem;
        }

        .login-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #f9fafb;
        }

        .form-input:focus {
            outline: none;
            border-color: #f5c542;
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 197, 66, 0.1);
        }

        .form-input-icon {
            position: relative;
        }

        .form-input-icon input {
            padding-left: 2.5rem;
        }

        .form-input-icon i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
        }

        .btn {
            width: 100%;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #f5c542 0%, #f39c12 100%);
            color: #1f2937;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 197, 66, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
            color: #374151;
        }

        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(31, 41, 55, 0.3);
            border-radius: 50%;
            border-top-color: #1f2937;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .security-notice {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.75rem;
            color: #1e40af;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }

            .login-header,
            .login-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-shield-alt"></i> Admin Access</h1>
            <p>Ayam Gepuk Management System</p>
        </div>

        <form method="POST" class="login-form" id="loginForm">
            <div class="security-notice">
                <i class="fas fa-lock"></i>
                This is a secure admin area. All activities are logged.
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username" class="form-label">Username or Email</label>
                <div class="form-input-icon">
                    <i class="fas fa-user"></i>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Enter your username or email"
                        required
                        autocomplete="username"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="form-input-icon">
                    <i class="fas fa-lock"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <button type="submit" class="btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In to Admin Panel
            </button>

            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Main Site
                </a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const originalContent = btn.innerHTML;
            
            btn.innerHTML = '<span class="loading"></span> Signing In...';
            btn.disabled = true;
            
            // Re-enable button after 5 seconds in case of issues
            setTimeout(() => {
                if (btn.disabled) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            }, 5000);
        });

        // Add some keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + L to focus username field
            if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                e.preventDefault();
                document.getElementById('username').focus();
            }
        });

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>

<?php
// Create necessary tables if they don't exist
try {
    // Create login_attempts table
    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(255),
        attempt_time INT NOT NULL,
        INDEX idx_ip_time (ip_address, attempt_time)
    )");

    // Create admin_logs table
    $conn->query("CREATE TABLE IF NOT EXISTS admin_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )");
} catch (Exception $e) {
    // Silently fail - tables might already exist
}

$conn->close();
?> 