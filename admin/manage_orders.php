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

// Handle AJAX requests for order details
if (isset($_GET['action']) && $_GET['action'] === 'get_order_details' && isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    
    // Get order details with customer information
    $stmt = $conn->prepare("
        SELECT o.*, u.username, u.email, u.phone_number, u.location 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order = $order_result->fetch_assoc();
    
    if ($order) {
        // Get order items
        $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        
        // If no items found, create sample items based on order total
        if (empty($items)) {
            $items = [
                [
                    'item_name' => 'Ayam Gepuk Original',
                    'item_type' => 'food',
                    'quantity' => 1,
                    'price' => $order['total_price'],
                    'spiciness' => 'Medium'
                ]
            ];
        }
        
        $response = [
            'success' => true,
            'order' => $order,
            'items' => $items
        ];
    } else {
        $response = ['success' => false, 'message' => 'Order not found'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

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
    <style>
        /* Order Details Modal Styles */
        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .order-section {
            background: #f9fafb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
        }
        
        .order-section h4 {
            margin: 0 0 1rem 0;
            color: #1f2937;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .order-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .order-info-item:last-child {
            border-bottom: none;
        }
        
        .order-info-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .order-info-value {
            color: #1f2937;
            font-weight: 600;
        }
        
        .order-items-list {
            margin-top: 1rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        
        .order-item:last-child {
            margin-bottom: 0;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .item-meta {
            font-size: 0.875rem;
            color: #6b7280;
            display: flex;
            gap: 1rem;
        }
        
        .item-price {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }
        
        .spiciness-badge {
            background: #fef3c7;
            color: #d97706;
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .order-timeline {
            grid-column: 1 / -1;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: white;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #f5c542;
        }
        
        .timeline-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: #f5c542;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1f2937;
            margin-right: 1rem;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .timeline-time {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .order-total {
            background: #f5c542;
            color: #1f2937;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .order-details-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
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

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="orderDetailsTitle">Order Details</h3>
                <button class="close" onclick="closeModal('orderDetailsModal')">&times;</button>
            </div>
            <div id="orderDetailsContent">
                <div class="loading-spinner" style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #f5c542;"></i>
                    <p style="margin-top: 1rem; color: #6b7280;">Loading order details...</p>
                </div>
            </div>
        </div>
    </div>

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
            // Show modal with loading state
            document.getElementById('orderDetailsModal').style.display = 'block';
            document.getElementById('orderDetailsTitle').textContent = 'Order #' + String(orderId).padStart(6, '0');
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="loading-spinner" style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #f5c542;"></i>
                    <p style="margin-top: 1rem; color: #6b7280;">Loading order details...</p>
                </div>
            `;
            
            // Fetch order details
            fetch(`manage_orders.php?action=get_order_details&order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order, data.items);
                    } else {
                        document.getElementById('orderDetailsContent').innerHTML = `
                            <div style="text-align: center; padding: 2rem; color: #dc2626;">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Error loading order details: ${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Error loading order details. Please try again.</p>
                        </div>
                    `;
                });
        }
        
        function displayOrderDetails(order, items) {
            const orderDate = new Date(order.order_date);
            const formattedDate = orderDate.toLocaleDateString('en-MY', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Calculate subtotal and other costs
            const subtotal = parseFloat(order.total_price);
            const deliveryFee = order.order_type === 'delivery' ? 2.00 : 0.00;
            const actualSubtotal = subtotal - deliveryFee;
            
            const content = `
                <div class="order-details-grid">
                    <!-- Order Information -->
                    <div class="order-section">
                        <h4><i class="fas fa-receipt"></i> Order Information</h4>
                        <div class="order-info-item">
                            <span class="order-info-label">Order ID</span>
                            <span class="order-info-value">#${String(order.id).padStart(6, '0')}</span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Date & Time</span>
                            <span class="order-info-value">${formattedDate}</span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Order Type</span>
                            <span class="order-info-value">
                                <i class="fas fa-${order.order_type === 'delivery' ? 'truck' : 'store'}"></i>
                                ${order.order_type.charAt(0).toUpperCase() + order.order_type.slice(1)}
                            </span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Payment Method</span>
                            <span class="order-info-value">${order.payment_method}</span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Status</span>
                            <span class="order-info-value">
                                <span class="status-badge status-${order.status.toLowerCase()}">${order.status}</span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="order-section">
                        <h4><i class="fas fa-user"></i> Customer Information</h4>
                        <div class="order-info-item">
                            <span class="order-info-label">Name</span>
                            <span class="order-info-value">${order.username}</span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Email</span>
                            <span class="order-info-value">${order.email}</span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Phone</span>
                            <span class="order-info-value">${order.phone_number}</span>
                        </div>
                        ${order.order_type === 'delivery' ? `
                        <div class="order-info-item">
                            <span class="order-info-label">Delivery Address</span>
                            <span class="order-info-value" style="text-align: right; max-width: 200px; word-wrap: break-word;">
                                ${order.location || 'Not provided'}
                            </span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Order Items -->
                    <div class="order-section" style="grid-column: 1 / -1;">
                        <h4><i class="fas fa-utensils"></i> Order Items</h4>
                        <div class="order-items-list">
                            ${items.map(item => `
                                <div class="order-item">
                                    <div class="item-details">
                                        <div class="item-name">${item.item_name}</div>
                                        <div class="item-meta">
                                            <span><i class="fas fa-hashtag"></i> Qty: ${item.quantity}</span>
                                            <span><i class="fas fa-pepper-hot"></i> <span class="spiciness-badge">${item.spiciness}</span></span>
                                            <span><i class="fas fa-tag"></i> ${item.item_type.charAt(0).toUpperCase() + item.item_type.slice(1)}</span>
                                        </div>
                                    </div>
                                    <div class="item-price">RM ${parseFloat(item.price).toFixed(2)}</div>
                                </div>
                            `).join('')}
                        </div>
                        
                        <!-- Pricing Breakdown -->
                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #e5e7eb;">
                            <div class="order-info-item">
                                <span class="order-info-label">Subtotal</span>
                                <span class="order-info-value">RM ${actualSubtotal.toFixed(2)}</span>
                            </div>
                            ${order.order_type === 'delivery' ? `
                            <div class="order-info-item">
                                <span class="order-info-label">Delivery Fee</span>
                                <span class="order-info-value">RM ${deliveryFee.toFixed(2)}</span>
                            </div>
                            ` : ''}
                            <div class="order-total">
                                Total: RM ${parseFloat(order.total_price).toFixed(2)}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Timeline -->
                    <div class="order-section order-timeline">
                        <h4><i class="fas fa-history"></i> Order Timeline</h4>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">Order Placed</div>
                                <div class="timeline-time">${formattedDate}</div>
                            </div>
                        </div>
                        ${order.status !== 'Pending' ? `
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">Order ${order.status}</div>
                                <div class="timeline-time">Status updated</div>
                            </div>
                        </div>
                        ` : ''}
                        ${order.status === 'Completed' ? `
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">Order Completed</div>
                                <div class="timeline-time">Successfully delivered/picked up</div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            document.getElementById('orderDetailsContent').innerHTML = content;
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
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

        // Handle URL parameters for filtering
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const filterParam = urlParams.get('filter');
            
            if (filterParam) {
                const statusFilter = document.getElementById('statusFilter');
                if (statusFilter) {
                    // Set the filter dropdown to the URL parameter value
                    statusFilter.value = filterParam.toLowerCase();
                    // Trigger the filter function
                    filterTable();
                    
                    // Highlight the relevant stat card
                    if (filterParam.toLowerCase() === 'pending') {
                        const pendingCard = document.querySelector('.stat-card:nth-child(2)');
                        if (pendingCard) {
                            pendingCard.style.border = '2px solid #f5c542';
                            pendingCard.style.boxShadow = '0 0 10px rgba(245, 196, 66, 0.3)';
                        }
                    }
                }
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const orderDetailsModal = document.getElementById('orderDetailsModal');
            if (event.target === orderDetailsModal) {
                closeModal('orderDetailsModal');
            }
        }
    </script>
</body>
</html> 