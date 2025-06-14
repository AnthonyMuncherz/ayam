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
        if ($_POST['action'] === 'update_role') {
            $user_id = (int)$_POST['user_id'];
            $new_role = $_POST['role'];
            
            // Prevent admin from changing their own role
            if ($user_id == $_SESSION['user_id']) {
                $message = "You cannot change your own role.";
                $message_type = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $new_role, $user_id);
                if ($stmt->execute()) {
                    $message = "Role updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error updating role.";
                    $message_type = 'error';
                }
            }
        } elseif ($_POST['action'] === 'delete_user') {
            $user_id = (int)$_POST['user_id'];
            
            // Prevent admin from deleting themselves
            if ($user_id == $_SESSION['user_id']) {
                $message = "You cannot delete your own account.";
                $message_type = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $message = "User deleted successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error deleting user.";
                    $message_type = 'error';
                }
            }
        }
    }
}

// Get user statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['customers'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'");
$stats['staff'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$stats['admins'] = $result->fetch_assoc()['count'];

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
                <a href="manage_users.php" class="nav-link active">
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
                <h1>User Management</h1>
                <p>Manage user accounts, roles, and permissions</p>
            </div>
            <div class="header-actions">
                <a href="#" class="btn btn-outline" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </a>
                <a href="#" class="btn btn-primary" onclick="showAddUserModal()">
                    <i class="fas fa-plus"></i>
                    Add User
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
            <div class="stat-card users">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Total Customers</div>
                        <div class="stat-value"><?php echo number_format($stats['customers']); ?></div>
                    </div>
                    <div class="stat-icon users">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card orders">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Staff Members</div>
                        <div class="stat-value"><?php echo number_format($stats['staff']); ?></div>
                    </div>
                    <div class="stat-icon orders">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card revenue">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Administrators</div>
                        <div class="stat-value"><?php echo number_format($stats['admins']); ?></div>
                    </div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>All Users</h3>
                <div class="header-actions">
                    <input type="text" placeholder="Search users..." class="form-input" style="width: 250px;" id="searchInput">
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user_data = $users->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo sprintf('%04d', $user_data['id']); ?></td>
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
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">
                                    <select name="role" class="form-select btn-sm" onchange="this.form.submit()" 
                                            <?php echo $user_data['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="customer" <?php echo $user_data['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="staff" <?php echo $user_data['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                        <option value="admin" <?php echo $user_data['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </form>
                                <?php if ($user_data['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user_data['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        function showAddUserModal() {
            alert('Add User functionality would be implemented here');
        }
    </script>
</body>
</html> 