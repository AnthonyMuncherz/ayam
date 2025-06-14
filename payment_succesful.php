<?php
session_start();
require_once 'db_connection.php';

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo "Invalid order ID.";
    exit;
}

$stmt = $conn->prepare("
    SELECT o.id, o.total_price, o.payment_method, o.order_date, 
           u.username, u.phone_number AS phone, u.email, u.location AS address
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Order not found.";
    exit;
}

$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #fefefe;
            margin: 0;
            padding: 0;
        }

        .success-container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #ffffff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            color: #008000;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .order-info {
            text-align: left;
            margin-top: 30px;
            font-size: 16px;
        }

        .order-info p {
            margin: 8px 0;
        }

        .order-number {
            font-size: 20px;
            color: #800000;
            font-weight: bold;
        }

        .back-home {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 25px;
            background-color: #800000;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
        }

        .back-home:hover {
            background-color: #a00000;
        }
    </style>
</head>
<body>

<div class="success-container">
    <h2>🎉 Payment Successful!</h2>
    <p class="order-number">Order No: #<?= $order['id'] ?></p>

    <div class="order-info">
        <p><strong>Customer Name:</strong> <?= htmlspecialchars($order['username']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
        <p><strong>Total Paid:</strong> RM <?= number_format($order['total_price'], 2) ?></p>
        <p><strong>Order Date:</strong> <?= date("F j, Y, g:i A", strtotime($order['order_date'])) ?></p>
    </div>

    <a href="menu.php" class="back-home">Back to Home</a>
    <br />
    <a href="order_status.php?id=<?= $order['id'] ?>" class="track-link">Track Order here</a>
</div>

</body>
</html>


