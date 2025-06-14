<?php
session_start();
require_once '../db_connection.php';

// Check admin access
if (!isset($_SESSION['user_id'])) {
    header('Location: admin_login.php');
    exit();
}

$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_status') {
            $order_id = (int)$_POST['order_id'];
            $new_status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            if ($stmt->execute()) {
                $message = "Order status updated successfully!";
                $message_type = 'success';
            } else {
                $message = "Error updating order status.";
                $message_type = 'error';
            }
        }
    }
}

// Get order statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed'");
$stats['completed_orders'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COALESCE(SUM(total_price), 0) as revenue FROM orders WHERE status = 'Completed'");
$stats['total_revenue'] = $result->fetch_assoc()['revenue'];

// Get orders with user information
$orders = $conn->query("
    SELECT o.*, u.username, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin_styles.css" rel="stylesheet">
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
                <a href="index.php" class="nav-link">
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
                <a href="manage_orders.php" class="nav-link active">
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
                <h1>Order Management</h1>
                <p>Track and manage customer orders and their status</p>
            </div>
            <div class="header-actions">
                <a href="#" class="btn btn-outline" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </a>
                <a href="#" class="btn btn-primary" onclick="exportOrders()">
                    <i class="fas fa-download"></i>
                    Export
                </a>
            </div>
        </header>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
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

            <div class="stat-card orders">
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

            <div class="stat-card revenue">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-value">RM <?php echo number_format($stats['total_revenue'], 2, '.', ','); ?></div>
                    </div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="search-filters">
            <div class="search-form">
                <div class="form-group">
                    <input type="text" placeholder="Search orders..." class="form-input" id="searchInput">
                </div>
                <div class="form-group">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="ready">Ready</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="delivery">Delivery</option>
                        <option value="pickup">Pickup</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>All Orders</h3>
                <div class="header-actions">
                    <span class="text-sm text-gray-500">Total: <?php echo $stats['total_orders']; ?> orders</span>
                </div>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo sprintf('%06d', $order['id']); ?></strong>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($order['username']); ?></strong>
                                    <br>
                                    <small style="color: #6b7280;"><?php echo htmlspecialchars($order['email']); ?></small>
                                </div>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($order['order_date'])); ?></td>
                            <td>
                                <span class="badge">
                                    <i class="fas fa-<?php echo $order['order_type'] === 'delivery' ? 'truck' : 'store'; ?>"></i>
                                    <?php echo ucfirst($order['order_type']); ?>
                                </span>
                            </td>
                            <td><strong>RM <?php echo number_format($order['total_price'], 2, '.', ','); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select btn-sm" onchange="this.form.submit()">
                                            <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Ready" <?php echo $order['status'] === 'Ready' ? 'selected' : ''; ?>>Ready</option>
                                            <option value="Completed" <?php echo $order['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                    <button class="btn btn-outline btn-sm" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #9ca3af;">
                                <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <br>
                                No orders found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            filterTable();
        });

        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTable();
        });

        document.getElementById('typeFilter').addEventListener('change', function() {
            filterTable();
        });

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
            const tableRows = document.querySelectorAll('.table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !statusFilter || text.includes(statusFilter);
                const matchesType = !typeFilter || text.includes(typeFilter);
                
                row.style.display = (matchesSearch && matchesStatus && matchesType) ? '' : 'none';
            });
        }

        function viewOrderDetails(orderId) {
            alert('Order details for #' + orderId + ' would be shown here');
        }

        function exportOrders() {
            alert('Export functionality would be implemented here');
        }

        // Auto-refresh every 60 seconds
        setInterval(function() {
            if (document.hasFocus()) {
                location.reload();
            }
        }, 60000);
    </script>
</body>
</html> 