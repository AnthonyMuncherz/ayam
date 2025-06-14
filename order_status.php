<?php
require_once "db_connection.php";
$order_id = $_GET['id'] ?? 0;

// Get order info
$stmt = $conn->prepare("
    SELECT o.id, o.status, o.payment_method, o.order_date, o.total_price,
           o.order_type, o.outlet_id, o.user_id,
           u.username, u.location AS user_address,
           ot.name AS outlet_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN outlets ot ON o.outlet_id = ot.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<p>Order not found.</p>";
    exit;
}

// Get order items (removed is_addon)
$item_stmt = $conn->prepare("
    SELECT item_name, quantity, spiciness
    FROM order_items
    WHERE order_id = ?
");
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
$order_items = $item_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Status Pesanan</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #fffdf9;
        margin: 0;
        padding: 0;
    }

    .confirmation-box {
        max-width: 700px;
        margin: 40px auto;
        padding: 30px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    h2 {
        color: #800000;
        font-size: 28px;
        margin-bottom: 15px;
    }

    .info-line {
        margin: 10px 0;
        font-size: 16px;
    }

    .items {
        margin-top: 20px;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 8px;
    }

    .items p {
        margin: 6px 0;
    }

    .map {
        margin-top: 30px;
        text-align: center;
    }

    .map img {
        border-radius: 10px;
        max-width: 100%;
    }

    .back-link {
        display: inline-block;
        margin-top: 30px;
        padding: 10px 20px;
        background-color: #800000;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-size: 16px;
    }

    .back-link:hover {
        background-color: #a00000;
    }
  </style>
</head>
<body>

<div class="confirmation-box">
  <h2>Order Status</h2>

  <div class="info-line"><strong>Order ID:</strong> #<?= htmlspecialchars($order['id']) ?></div>
  <div class="info-line"><strong>Customer:</strong> <?= htmlspecialchars($order['username'] ?? 'Unknown') ?></div>

  <?php if ($order['order_type'] === 'delivery'): ?>
    <div class="info-line"><strong>Address:</strong> <?= htmlspecialchars($order['user_address'] ?? '-') ?></div>
  <?php else: ?>
    <div class="info-line"><strong>Pickup at:</strong> <?= htmlspecialchars($order['outlet_name'] ?? '-') ?></div>
  <?php endif; ?>

  <div class="info-line"><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></div>
  <div class="info-line"><strong>Date and Time:</strong> <?= date("F j, Y, g:i A", strtotime($order['order_date'])) ?></div>
  <div class="info-line"><strong>Total Price:</strong> RM <?= number_format($order['total_price'], 2) ?></div>

  <div class="items">
      <h4>🧾 Ordered Items:</h4>
      <?php foreach ($order_items as $item): ?>
          <p>
              <?= htmlspecialchars($item['item_name']) ?> × <?= $item['quantity'] ?>
              <?= $item['spiciness'] ? ' - ' . htmlspecialchars($item['spiciness']) : '' ?>
          </p>
      <?php endforeach; ?>
  </div>

  <div class="map">
    <h3>APPROACHING THE LOCATION IN...</h3>
    <p id="timer" style="margin-top: 15px; font-size: 18px; color: #333;"></p>
  </div>

  <script>
    let secondsLeft = 10;
    const timerElement = document.getElementById('timer');

    function updateTimer() {
      if (secondsLeft > 0) {
        timerElement.textContent = `... ${secondsLeft} Seconds...`;
        secondsLeft--;
      } else {
        clearInterval(countdown);
        timerElement.textContent = "ORDER IS ARRIVED!";
      }
    }

    const countdown = setInterval(updateTimer, 1000);
    updateTimer(); 
  </script>

  <a class="back-link" href="deliverypickup.php">Go to homepage</a>
</div>

</body>
</html>



