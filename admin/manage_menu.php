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

// Function to handle image upload
function handleImageUpload($file, $old_image_path = null) {
    $upload_dir = '../images/uploads/';
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error occurred.'];
    }
    
    // Validate file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.'];
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'menu_' . uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Delete old image if updating
        if ($old_image_path && file_exists('../' . $old_image_path) && strpos($old_image_path, 'uploads/') !== false) {
            unlink('../' . $old_image_path);
        }
        
        return ['success' => true, 'path' => 'images/uploads/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_item') {
            $name = trim($_POST['name']);
            $price = (float)$_POST['price'];
            $type = $_POST['type'];
            $is_set_meal = isset($_POST['is_set_meal']) ? 1 : 0;
            
            // Handle image upload or manual path
            $image_path = '';
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handleImageUpload($_FILES['image_file']);
                if ($upload_result['success']) {
                    $image_path = $upload_result['path'];
                } else {
                    $message = $upload_result['message'];
                    $message_type = 'error';
                }
            } elseif (!empty($_POST['image_path'])) {
                $image_path = trim($_POST['image_path']);
            } else {
                $message = "Please upload an image or provide an image path.";
                $message_type = 'error';
            }
            
            if (empty($message) && !empty($image_path)) {
                $stmt = $conn->prepare("INSERT INTO menu_items (name, image_path, price, type, is_set_meal) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdsi", $name, $image_path, $price, $type, $is_set_meal);
                
                if ($stmt->execute()) {
                    $message = "Menu item added successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error adding menu item.";
                    $message_type = 'error';
                }
            }
        } elseif ($_POST['action'] === 'update_item') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $price = (float)$_POST['price'];
            $type = $_POST['type'];
            $is_set_meal = isset($_POST['is_set_meal']) ? 1 : 0;
            
            // Get current image path
            $stmt = $conn->prepare("SELECT image_path FROM menu_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_item = $result->fetch_assoc();
            $current_image_path = $current_item['image_path'];
            
            // Handle image upload or keep existing
            $image_path = $current_image_path;
            if (isset($_FILES['edit_image_file']) && $_FILES['edit_image_file']['error'] === UPLOAD_ERR_OK) {
                $upload_result = handleImageUpload($_FILES['edit_image_file'], $current_image_path);
                if ($upload_result['success']) {
                    $image_path = $upload_result['path'];
                } else {
                    $message = $upload_result['message'];
                    $message_type = 'error';
                }
            } elseif (!empty($_POST['image_path']) && $_POST['image_path'] !== $current_image_path) {
                $image_path = trim($_POST['image_path']);
            }
            
            if (empty($message)) {
                $stmt = $conn->prepare("UPDATE menu_items SET name = ?, image_path = ?, price = ?, type = ?, is_set_meal = ? WHERE id = ?");
                $stmt->bind_param("ssdsii", $name, $image_path, $price, $type, $is_set_meal, $id);
                
                if ($stmt->execute()) {
                    $message = "Menu item updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error updating menu item.";
                    $message_type = 'error';
                }
            }
        } elseif ($_POST['action'] === 'delete_item') {
            $id = (int)$_POST['id'];
            
            // Get image path before deleting
            $stmt = $conn->prepare("SELECT image_path FROM menu_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();
            
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                // Delete uploaded image file if it exists
                if ($item && strpos($item['image_path'], 'uploads/') !== false) {
                    $file_path = '../' . $item['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
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
        
        /* Image Upload Styles */
        .image-upload-container {
            border: 2px dashed #d1d5db;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .image-upload-container:hover {
            border-color: #f5c542;
            background: #fffbeb;
        }
        
        .image-upload-container.dragover {
            border-color: #f5c542;
            background: #fffbeb;
            transform: scale(1.02);
        }
        
        .upload-icon {
            font-size: 2rem;
            color: #9ca3af;
            margin-bottom: 0.5rem;
        }
        
        .upload-text {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .upload-hint {
            font-size: 0.75rem;
            color: #9ca3af;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 0.5rem;
            margin: 1rem auto;
            display: block;
            border: 2px solid #e5e7eb;
        }
        
        .image-upload-input {
            display: none;
        }
        
        .current-image {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .current-image img {
            max-width: 150px;
            max-height: 100px;
            border-radius: 0.5rem;
            border: 2px solid #e5e7eb;
        }
        
        .image-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 0.5rem;
        }
        
        .btn-upload {
            background: #f5c542;
            color: #1f2937;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-upload:hover {
            background: #f59e0b;
            transform: translateY(-1px);
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
                                     onerror="this.src='../images/placeholder.svg'">
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
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_item">
                
                <div class="form-group">
                    <label class="form-label" for="name">Name:</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Image:</label>
                    <div class="image-upload-container" onclick="document.getElementById('image_file').click()" 
                         ondrop="handleDrop(event, 'image_file')" 
                         ondragover="handleDragOver(event)" 
                         ondragleave="handleDragLeave(event)">
                        <input type="file" id="image_file" name="image_file" class="image-upload-input" 
                               accept="image/*" onchange="previewImage(this, 'add_preview')">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">Click to upload or drag and drop</div>
                        <div class="upload-hint">PNG, JPG, GIF, WebP up to 5MB</div>
                    </div>
                    <img id="add_preview" class="image-preview" style="display: none;">
                    
                    <div style="margin-top: 1rem; text-align: center;">
                        <strong>OR</strong>
                    </div>
                    
                    <div style="margin-top: 0.5rem;">
                        <label class="form-label" for="image_path">Manual Image Path:</label>
                        <input type="text" id="image_path" name="image_path" class="form-input" 
                               placeholder="images/item.jpg (optional if uploading file)">
                    </div>
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
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_item">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label class="form-label" for="edit_name">Name:</label>
                    <input type="text" id="edit_name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Current Image:</label>
                    <div id="current_image_container" class="current-image">
                        <img id="current_image" src="" alt="Current image">
                        <div class="image-actions">
                            <button type="button" class="btn-upload" onclick="document.getElementById('edit_image_file').click()">
                                <i class="fas fa-upload"></i> Replace Image
                            </button>
                        </div>
                    </div>
                    
                    <div class="image-upload-container" onclick="document.getElementById('edit_image_file').click()" 
                         ondrop="handleDrop(event, 'edit_image_file')" 
                         ondragover="handleDragOver(event)" 
                         ondragleave="handleDragLeave(event)" 
                         style="display: none;" id="edit_upload_container">
                        <input type="file" id="edit_image_file" name="edit_image_file" class="image-upload-input" 
                               accept="image/*" onchange="previewImage(this, 'edit_preview')">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">Click to upload or drag and drop</div>
                        <div class="upload-hint">PNG, JPG, GIF, WebP up to 5MB</div>
                    </div>
                    <img id="edit_preview" class="image-preview" style="display: none;">
                    
                    <div style="margin-top: 1rem; text-align: center;">
                        <strong>OR</strong>
                    </div>
                    
                    <div style="margin-top: 0.5rem;">
                        <label class="form-label" for="edit_image_path">Manual Image Path:</label>
                        <input type="text" id="edit_image_path" name="image_path" class="form-input" 
                               placeholder="images/item.jpg">
                    </div>
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
            
            // Show current image
            const currentImage = document.getElementById('current_image');
            currentImage.src = '../' + item.image_path;
            currentImage.onerror = function() {
                this.src = '../images/placeholder.svg';
            };
            
            // Reset upload preview
            document.getElementById('edit_preview').style.display = 'none';
            document.getElementById('edit_image_file').value = '';
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Image upload functions
        function previewImage(input, previewId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);
            
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please select a JPEG, PNG, GIF, or WebP image.');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size too large. Please select an image smaller than 5MB.');
                    input.value = '';
                    preview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }
        
        function handleDragOver(event) {
            event.preventDefault();
            event.currentTarget.classList.add('dragover');
        }
        
        function handleDragLeave(event) {
            event.preventDefault();
            event.currentTarget.classList.remove('dragover');
        }
        
        function handleDrop(event, inputId) {
            event.preventDefault();
            event.currentTarget.classList.remove('dragover');
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById(inputId);
                input.files = files;
                
                // Trigger preview
                const previewId = inputId === 'image_file' ? 'add_preview' : 'edit_preview';
                previewImage(input, previewId);
            }
        }
        
        // Reset forms when modals are closed
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            
            if (modalId === 'addModal') {
                // Reset add form
                document.getElementById('image_file').value = '';
                document.getElementById('add_preview').style.display = 'none';
                document.querySelector('#addModal form').reset();
            } else if (modalId === 'editModal') {
                // Reset edit form previews
                document.getElementById('edit_image_file').value = '';
                document.getElementById('edit_preview').style.display = 'none';
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            if (event.target === addModal) {
                closeModal('addModal');
            }
            if (event.target === editModal) {
                closeModal('editModal');
            }
        }
    </script>
</body>
</html> 