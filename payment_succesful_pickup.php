<?php
session_start();
require_once 'db_connection.php';

if (!isset($_GET['order_id'])) {
    echo "Order ID is missing.";
    exit;
}

$order_id = intval($_GET['order_id']);

// Get the outlet info from session
$outlet_id = $_SESSION['pickup_outlet_id'] ?? null;
$outlet_name = $_SESSION['pickup_outlet_name'] ?? 'Unknown Outlet';

// If outlet_id is available, update the orders table with it
if ($outlet_id) {
    $update_stmt = $conn->prepare("UPDATE orders SET outlet_id = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $outlet_id, $order_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Fetch updated order info
$stmt = $conn->prepare("SELECT total_price, payment_method, order_date FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo "Order not found.";
    exit;
}

$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful - Pickup</title>
    <style>
        body {
            background-color: #fefefe;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        .success-container {
            max-width: 600px;
            margin: 60px auto;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        h1 {
            color: #008000;
            font-size: 32px;
            margin-bottom: 20px;
        }

        .details {
            font-size: 18px;
            color: #333;
            margin-top: 25px;
            text-align: left;
        }

        .details p {
            margin: 10px 0;
        }

        .outlet-name {
            font-weight: bold;
            color: #800000;
        }

        .back-btn, .track-btn {
            margin-top: 20px;
            display: inline-block;
            padding: 12px 25px;
            background-color: #800000;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            margin-right: 10px;
        }

        .back-btn:hover, .track-btn:hover {
            background-color: #a30000;
        }
    </style>
</head>
<body>

<div class="success-container">
    <h1>🎉 Payment Successful!</h1>
    <p>Thank you for placing your pickup order with us.</p>

    <div class="details">
        <p><strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?></p>
        <p><strong>Total Price:</strong> RM <?= number_format($order['total_price'], 2) ?></p>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
        <p><strong>Pickup Outlet:</strong> <span class="outlet-name"><?= htmlspecialchars($outlet_name) ?></span></p>
        <p><strong>Order Time:</strong> <?= htmlspecialchars($order['order_date']) ?></p>
    </div>

    <a href="menu_pickup.php" class="back-btn">Back to Menu</a>
    <br/>
    <a href="orderpickup_status.php?id=<?= $order_id ?>" class="track-btn">Track Order here</a>
</div>

</body>
</html>




