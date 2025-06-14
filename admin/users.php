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

// Check if user is logged in and is admin
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
    header('Location: ../index.php?error=access_denied');
    exit();
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_role':
                $user_id = (int)$_POST['user_id'];
                $new_role = $_POST['role'];
                
                if (in_array($new_role, ['customer', 'admin', 'staff'])) {
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_role, $user_id);
                    
                    if ($stmt->execute()) {
                        $message = "User role updated successfully.";
                        $message_type = 'success';
                        
                        // Log the action
                        $stmt = $conn->prepare("INSERT INTO admin_logs (user_id, action, details, ip_address) VALUES (?, 'role_change', ?, ?)");
                        $details = "Changed user ID $user_id role to $new_role";
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
                        $stmt->execute();
                    } else {
                        $message = "Error updating user role.";
                        $message_type = 'error';
                    }
                }
                break;
                
            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                
                // Don't allow deleting self
                if ($user_id !== $_SESSION['user_id']) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    
                    if ($stmt->execute()) {
                        $message = "User deleted successfully.";
                        $message_type = 'success';
                        
                        // Log the action
                        $stmt = $conn->prepare("INSERT INTO admin_logs (user_id, action, details, ip_address) VALUES (?, 'user_delete', ?, ?)");
                        $details = "Deleted user ID $user_id";
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip);
                        $stmt->execute();
                    } else {
                        $message = "Error deleting user.";
                        $message_type = 'error';
                    }
                } else {
                    $message = "You cannot delete your own account.";
                    $message_type = 'error';
                }
                break;
        }
    }
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR phone_number LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'sss';
}

if ($role_filter && in_array($role_filter, ['customer', 'admin', 'staff'])) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $param_types .= 's';
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total users
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
if ($params) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_users = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_users / $per_page);

// Get users
$users_query = "SELECT id, username, email, phone_number, role, created_at, location 
                FROM users $where_clause 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

$users_stmt = $conn->prepare($users_query);
if ($params) {
    $users_stmt->bind_param($param_types, ...$params);
}
$users_stmt->execute();
$users_result = $users_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
            background: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            padding: 2rem 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            text-align: center;
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand h2 {
            color: #f5c542;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar-brand p {
            color: #9ca3af;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #d1d5db;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(245, 197, 66, 0.1);
            color: #f5c542;
        }

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 2rem;
        }

        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #1f2937;
            font-size: 1.875rem;
            font-weight: 700;
        }

        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: #f5c542;
            color: #1f2937;
        }

        .btn-outline {
            background: transparent;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .users-table {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f3f4f6;
        }

        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }

        .table td {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .table tbody tr:hover {
            background: #f9fafb;
        }

        .role-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .role-admin {
            background: #fef3c7;
            color: #d97706;
        }

        .role-staff {
            background: #dbeafe;
            color: #2563eb;
        }

        .role-customer {
            background: #d1fae5;
            color: #065f46;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            text-decoration: none;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .pagination a.active {
            background: #f5c542;
            color: #1f2937;
            border-color: #f5c542;
        }

        .pagination a:hover:not(.active) {
            background: #f3f4f6;
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

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .modal-header h3 {
            color: #1f2937;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .search-form {
                flex-direction: column;
            }

            .form-group {
                min-width: auto;
            }

            .table-container {
                overflow-x: auto;
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
                <a href="users.php" class="nav-link active">
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
                <a href="menu.php" class="nav-link">
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
                <h1>User Management</h1>
                <p style="color: #6b7280; margin-top: 0.5rem;">Manage user accounts and permissions</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'error' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <div class="search-filters">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search" class="form-label">Search Users</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        class="form-input" 
                        placeholder="Search by username, email, or phone..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>
                <div class="form-group">
                    <label for="role" class="form-label">Filter by Role</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="users-table">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while ($user_data = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $user_data['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user_data['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user_data['phone_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user_data['role']; ?>">
                                            <?php echo ucfirst($user_data['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <button 
                                                class="btn btn-outline btn-sm" 
                                                onclick="openRoleModal(<?php echo $user_data['id']; ?>, '<?php echo $user_data['username']; ?>', '<?php echo $user_data['role']; ?>')"
                                            >
                                                <i class="fas fa-user-cog"></i>
                                                Role
                                            </button>
                                            <?php if ($user_data['id'] !== $_SESSION['user_id']): ?>
                                                <button 
                                                    class="btn btn-danger btn-sm" 
                                                    onclick="confirmDelete(<?php echo $user_data['id']; ?>, '<?php echo htmlspecialchars($user_data['username']); ?>')"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #9ca3af;">
                                    No users found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a 
                        href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                        class="<?php echo $i === $page ? 'active' : ''; ?>"
                    >
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Role Change Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change User Role</h3>
                <button class="close" onclick="closeModal('roleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="roleForm">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" id="roleUserId">
                
                <div class="form-group">
                    <label class="form-label">User: <span id="roleUsername"></span></label>
                </div>
                
                <div class="form-group">
                    <label for="newRole" class="form-label">Select Role</label>
                    <select id="newRole" name="role" class="form-select" required>
                        <option value="customer">Customer</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('roleModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button class="close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete user <strong id="deleteUsername"></strong>? This action cannot be undone.</p>
                
                <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRoleModal(userId, username, currentRole) {
            document.getElementById('roleUserId').value = userId;
            document.getElementById('roleUsername').textContent = username;
            document.getElementById('newRole').value = currentRole;
            document.getElementById('roleModal').style.display = 'block';
        }

        function confirmDelete(userId, username) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Auto-submit search form on filter change
        document.getElementById('role').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>

<?php
$conn->close();
?> 