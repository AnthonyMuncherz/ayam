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
        if ($_POST['action'] === 'add_item') {
            $name = trim($_POST['name']);
            $image_path = trim($_POST['image_path']);
            $price = (float)$_POST['price'];
            $type = $_POST['type'];
            $is_set_meal = isset($_POST['is_set_meal']) ? 1 : 0;
            
            $stmt = $conn->prepare("INSERT INTO menu_items (name, image_path, price, type, is_set_meal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsi", $name, $image_path, $price, $type, $is_set_meal);
            
            if ($stmt->execute()) {
                $message = "Menu item added successfully!";
                $message_type = 'success';
            } else {
                $message = "Error adding menu item.";
                $message_type = 'error';
            }
        } elseif ($_POST['action'] === 'update_item') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $image_path = trim($_POST['image_path']);
            $price = (float)$_POST['price'];
            $type = $_POST['type'];
            $is_set_meal = isset($_POST['is_set_meal']) ? 1 : 0;
            
            $stmt = $conn->prepare("UPDATE menu_items SET name = ?, image_path = ?, price = ?, type = ?, is_set_meal = ? WHERE id = ?");
            $stmt->bind_param("ssdsii", $name, $image_path, $price, $type, $is_set_meal, $id);
            
            if ($stmt->execute()) {
                $message = "Menu item updated successfully!";
                $message_type = 'success';
            } else {
                $message = "Error updating menu item.";
                $message_type = 'error';
            }
        } elseif ($_POST['action'] === 'delete_item') {
            $id = (int)$_POST['id'];
            
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Menu item deleted successfully!";
                $message_type = 'success';
            } else {
                $message = "Error deleting menu item.";
                $message_type = 'error';
            }
        }
    }
}

// Get menu statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM menu_items WHERE type = 'food'");
$stats['food_items'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM menu_items WHERE type = 'drink'");
$stats['drink_items'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM menu_items WHERE is_set_meal = 1");
$stats['set_meals'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT AVG(price) as avg_price FROM menu_items");
$stats['avg_price'] = $result->fetch_assoc()['avg_price'] ?? 0;

// Get all menu items
$menu_items = $conn->query("SELECT * FROM menu_items ORDER BY type, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin_styles.css" rel="stylesheet">
    <style>
        /* Page-specific styles for menu management */
        .menu-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 2px solid #e5e7eb;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-set {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .badge-single {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .type-food {
            background: #fef3c7;
            color: #d97706;
        }
        
        .type-drink {
            background: #dbeafe;
            color: #2563eb;
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
                <a href="manage_orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Manage Orders
                </a>
            </div>
            <div class="nav-item">
                <a href="manage_menu.php" class="nav-link active">
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
                <h1>Menu Management</h1>
                <p>Manage restaurant menu items, prices, and categories</p>
            </div>
            <div class="header-actions">
                <a href="#" class="btn btn-outline" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </a>
                <a href="#" class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i>
                    Add Item
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
                        <div class="stat-title">Food Items</div>
                        <div class="stat-value"><?php echo number_format($stats['food_items']); ?></div>
                    </div>
                    <div class="stat-icon orders">
                        <i class="fas fa-hamburger"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card users">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Drink Items</div>
                        <div class="stat-value"><?php echo number_format($stats['drink_items']); ?></div>
                    </div>
                    <div class="stat-icon users">
                        <i class="fas fa-glass-water"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card revenue">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Set Meals</div>
                        <div class="stat-value"><?php echo number_format($stats['set_meals']); ?></div>
                    </div>
                    <div class="stat-icon revenue">
                        <i class="fas fa-utensils"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card revenue">
                <div class="stat-header">
                    <div>
                        <div class="stat-title">Average Price</div>
                        <div class="stat-value">RM <?php echo number_format($stats['avg_price'], 2); ?></div>
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
                    <input type="text" placeholder="Search menu items..." class="form-input" id="searchInput">
                </div>
                <div class="form-group">
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="food">Food</option>
                        <option value="drink">Drink</option>
                    </select>
                </div>
                <div class="form-group">
                    <select class="form-select" id="setMealFilter">
                        <option value="">All Items</option>
                        <option value="1">Set Meals Only</option>
                        <option value="0">Single Items Only</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Menu Items Table -->
        <div class="table-container">
            <div class="table-header">
                <h3>Menu Items</h3>
                <div class="header-actions">
                    <span class="text-sm text-gray-500">Total: <?php echo $stats['food_items'] + $stats['drink_items']; ?> items</span>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Type</th>
                        <th>Set Meal</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($menu_items->num_rows > 0): ?>
                        <?php while ($item = $menu_items->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="menu-item-image"
                                     onerror="this.src='../images/placeholder.jpg'">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                <br>
                                <small style="color: #6b7280;">ID: #<?php echo $item['id']; ?></small>
                            </td>
                            <td><strong>RM <?php echo number_format($item['price'], 2, '.', ','); ?></strong></td>
                            <td>
                                <span class="badge type-<?php echo $item['type']; ?>">
                                    <i class="fas fa-<?php echo $item['type'] === 'food' ? 'hamburger' : 'glass-water'; ?>"></i>
                                    <?php echo ucfirst($item['type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $item['is_set_meal'] ? 'badge-set' : 'badge-single'; ?>">
                                    <?php echo $item['is_set_meal'] ? 'Set Meal' : 'Single Item'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-outline btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #9ca3af;">
                                <i class="fas fa-utensils" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <br>
                                No menu items found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Item Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Menu Item</h3>
                <button class="close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_item">
                
                <div class="form-group">
                    <label class="form-label" for="name">Name:</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="image_path">Image Path:</label>
                    <input type="text" id="image_path" name="image_path" class="form-input" placeholder="images/item.jpg" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="price">Price (RM):</label>
                    <input type="number" id="price" name="price" class="form-input" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="type">Type:</label>
                    <select id="type" name="type" class="form-select" required>
                        <option value="food">Food</option>
                        <option value="drink">Drink</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="is_set_meal" name="is_set_meal">
                        Is Set Meal
                    </label>
                </div>
                
                <div class="actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Menu Item</h3>
                <button class="close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_item">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label class="form-label" for="edit_name">Name:</label>
                    <input type="text" id="edit_name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_image_path">Image Path:</label>
                    <input type="text" id="edit_image_path" name="image_path" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_price">Price (RM):</label>
                    <input type="number" id="edit_price" name="price" class="form-input" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_type">Type:</label>
                    <select id="edit_type" name="type" class="form-select" required>
                        <option value="food">Food</option>
                        <option value="drink">Drink</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="edit_is_set_meal" name="is_set_meal">
                        Is Set Meal
                    </label>
                </div>
                
                <div class="actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            filterTable();
        });

        document.getElementById('typeFilter').addEventListener('change', function() {
            filterTable();
        });

        document.getElementById('setMealFilter').addEventListener('change', function() {
            filterTable();
        });

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
            const setMealFilter = document.getElementById('setMealFilter').value;
            const tableRows = document.querySelectorAll('.table tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const matchesSearch = text.includes(searchTerm);
                const matchesType = !typeFilter || text.includes(typeFilter);
                const matchesSetMeal = !setMealFilter || 
                    (setMealFilter === '1' && text.includes('set meal')) ||
                    (setMealFilter === '0' && text.includes('single item'));
                
                row.style.display = (matchesSearch && matchesType && matchesSetMeal) ? '' : 'none';
            });
        }

        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_image_path').value = item.image_path;
            document.getElementById('edit_price').value = item.price;
            document.getElementById('edit_type').value = item.type;
            document.getElementById('edit_is_set_meal').checked = item.is_set_meal == 1;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 