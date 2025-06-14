<?php
session_start();
include 'db_connect.php';

/*if (!isset($_SESSION['admin_username'])) {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_username'];
*/
// Fetch Order Summary Data
$total_orders = 0;
$completed_orders = 0;
$pending_orders = 0;

$query = "SELECT 
            COUNT(*) AS total_orders, 
            SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS completed_orders,
            SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending_orders 
          FROM orders";

$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $total_orders = $row['total_orders'];
    $completed_orders = $row['completed_orders'];
    $pending_orders = $row['pending_orders'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Ayam Gepuk Pak Gembus</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            background: #f9f9f9;
            color: #333;
        }
        .header {
            background-color: #bf360c;
            color: white;
            padding: 30px 0;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header h2 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 1px;
        }
        .logout {
            position: absolute;
            top: 20px;
            right: 30px;
            background-color: #e64a19;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .logout:hover {
            background-color: #d84315;
        }
        .welcome {
            text-align: center;
            padding: 30px 0 10px;
            font-size: 18px;
        }
        .card-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding: 20px 40px;
            flex-wrap: wrap;
        }
        .summary-card {
            background: white;
            width: 250px;
            height: 150px;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(0,0,0,0.12);
            text-align: center;
            padding: 20px;
            transition: transform 0.3s;
            font-size: 20px;
        }
        .summary-card:hover {
            transform: translateY(-8px);
        }
        .summary-card h3 {
            margin: 10px 0;
            color: #bf360c;
            font-size: 20px;
        }
        .summary-card p {
            font-size: 32px;
            font-weight: 600;
            margin: 0;
            color: #333;
        }
        .nav-card {
            background: white;
            width: 200px;
            height: 120px;
            border-radius: 10px;
            box-shadow: 0 6px 14px rgba(0,0,0,0.1);
            text-align: center;
            padding: 20px;
            transition: transform 0.3s;
            font-size: 16px;
        }
        .nav-card:hover {
            transform: translateY(-6px);
        }
        .nav-card a {
            display: block;
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #bf360c;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
            font-weight: 500;
        }
        .nav-card a:hover {
            background-color: #d84315;
        }
    </style>
</head>
<body>

<a class="logout" href="logout.php">Logout</a>

<div class="header">
    <h2>Ayam Gepuk Pak Gembus</h2>
    <h3>Admin Dashboard</h3>
</div>

<div class="welcome">
    Welcome back, <strong><?php echo htmlspecialchars($admin_name); ?></strong>! Here’s your system summary:
</div>

<!-- Summary Cards (Top) -->
<div class="card-container">
    <div class="summary-card">
        <h3>Total Orders</h3>
        <p><?php echo $total_orders; ?></p>
    </div>
    <div class="summary-card">
        <h3>Completed Orders</h3>
        <p><?php echo $completed_orders; ?></p>
    </div>
    <div class="summary-card">
        <h3>Pending Orders</h3>
        <p><?php echo $pending_orders; ?></p>
    </div>
</div>

<!-- Navigation Cards (Bottom) -->
<div class="card-container">
    <div class="nav-card">
        <h3>Manage Menu</h3>
        <a href="Menuadmin.php">Go</a>
    </div>
    <div class="nav-card">
        <h3>View Orders</h3>
        <a href="Ordertracking.php">Go</a>
    </div>
    <div class="nav-card">
        <h3>Manage Users</h3>
        <a href="Viewadmin.php">Go</a>
    </div>
</div>

</body>
</html>
