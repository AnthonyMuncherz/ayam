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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Check if user is admin
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role'] !== 'admin') {
    // Not an admin, redirect to main site
    header('Location: ../index.php?error=access_denied');
    exit();
}

// Fetch dashboard statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['count'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Completed orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed'");
$stats['completed_orders'] = $result->fetch_assoc()['count'];

// Today's revenue
$result = $conn->query("SELECT COALESCE(SUM(total_price), 0) as revenue FROM orders WHERE DATE(order_date) = CURDATE() AND status = 'Completed'");
$stats['today_revenue'] = $result->fetch_assoc()['revenue'];

// Monthly revenue
$result = $conn->query("SELECT COALESCE(SUM(total_price), 0) as revenue FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE()) AND status = 'Completed'");
$stats['monthly_revenue'] = $result->fetch_assoc()['revenue'];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.id, o.order_date, o.total_price, o.status, u.username, o.order_type 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC 
    LIMIT 10
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ayam Gepuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin_styles.css" rel="stylesheet">
    <style>
        /* Page-specific styles for dashboard */
        .auto-refresh {
            font-size: 0.75rem;
            color: #6b7280;
            margin-left: 1rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h2>Ayam Gepuk</h2>
            <p>Admin Panel</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="manage_users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    Manage Users
                </a>
            </div>
            <div class="nav-item">
                <a href="manage_orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Manage Orders
                </a>
            </div>
            <div class="nav-item">
                <a href="manage_menu.php" class="nav-link">
                    <i class="fas fa-utensils"></i>
                    Manage Menu
                </a>
            </div>
            <div class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </div>
            <div class="nav-item" style="margin-top: 2rem;">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-globe"></i>
                    View Site
                </a>
            </div>
            <div class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <div>
                <h1>Dashboard Overview</h1>
                <p style="color: #6b7280; margin-top: 0.5rem;">Welcome back! Here's what's happening with your restaurant today.</p>
            </div>
            <div class="header-actions">
                <a href="manage_orders.php?filter=pending" class="btn btn-outline">
                    <i class="fas fa-clock"></i>
                    Pending Orders
                </a>
                <a href="manage_orders.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    New Order
                </a>
            </div>
        </header>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card revenue">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Today's Revenue</div>
                        <div class="stat-value">RM <?php echo number_format($stats['today_revenue'], 2, '.', ','); ?></div>
                    </div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card revenue">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Monthly Revenue</div>
                        <div class="stat-value">RM <?php echo number_format($stats['monthly_revenue'], 2, '.', ','); ?></div>
                    </div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card orders">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Orders</div>
                        <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                    </div>
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card orders">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Pending Orders</div>
                        <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                    </div>
                    <div class="stat-icon orders">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card users">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Users</div>
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                    </div>
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Completed Orders</div>
                        <div class="stat-value"><?php echo number_format($stats['completed_orders']); ?></div>
                    </div>
                    <div class="stat-icon orders">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>Recent Orders</h3>
                <span class="auto-refresh">Auto-refresh every 30s</span>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_orders->num_rows > 0): ?>
                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo sprintf('%06d', $order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><?php echo ucfirst($order['order_type']); ?></td>
                                <td>RM <?php echo number_format($order['total_price'], 2, '.', ','); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #9ca3af;">
                                No orders found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Auto-refresh dashboard every 30 seconds
        setInterval(function() {
            // Only refresh if user is still active (not idle)
            if (document.hasFocus()) {
                location.reload();
            }
        }, 30000);

        // Add click handlers for quick actions
        document.addEventListener('DOMContentLoaded', function() {
            // Handle sidebar mobile toggle
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
        });
    </script>
</body>
</html> 