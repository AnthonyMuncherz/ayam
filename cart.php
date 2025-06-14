<?php
session_start();
require_once 'db_connection.php';

// Get user info
$user_id = $_SESSION['user_id'] ?? null;
$userInfo = [
    'username' => '',
    'phone_number' => '',
    'email' => '',
    'location' => ''
];

if ($user_id) {
    $stmt = $conn->prepare("SELECT username, phone_number, email, location FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $userInfo = $result->fetch_assoc();
    }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $username = $_POST['username'];
    $phone = $_POST['phone_number'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $payment_method = $_POST['payment_method'];
    $user_id = $_SESSION['user_id'];

    $total_price = 0;
    foreach ($_SESSION['food'] as $food) {
        list($foodName, $foodPrice, $isSetMeal) = explode("|", $food);
        $foodQty = intval($_SESSION['food_qty'][$foodName] ?? 1);
        $total_price += floatval($foodPrice) * $foodQty;
    }

    foreach ($_SESSION['drink'] as $drink) {
        list($drinkName, $drinkPrice) = explode("|", $drink);
        $drinkQty = intval($_SESSION['drink_qty'][$drinkName] ?? 1);
        $total_price += floatval($drinkPrice) * $drinkQty;
    }

    // ✅ FIXED SQL syntax
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, payment_method) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $user_id, $total_price, $payment_method);
    $stmt->execute();

    $order_id = $stmt->insert_id;

    // Clear session cart
    unset($_SESSION['food'], $_SESSION['food_qty'], $_SESSION['spiciness'], $_SESSION['drink'], $_SESSION['drink_qty'], $_SESSION['set_drink']);

    header("Location: payment_succesful.php?order_id=$order_id");
    exit;
}

// Save cart if submitted
$_SESSION['food'] = $_POST['food'] ?? $_SESSION['food'] ?? [];
$_SESSION['food_qty'] = $_POST['food_qty'] ?? $_SESSION['food_qty'] ?? [];
$_SESSION['spiciness'] = $_POST['spiciness'] ?? $_SESSION['spiciness'] ?? [];
$_SESSION['drink'] = $_POST['drink'] ?? $_SESSION['drink'] ?? [];
$_SESSION['drink_qty'] = $_POST['drink_qty'] ?? $_SESSION['drink_qty'] ?? [];
$_SESSION['set_drink'] = $_POST['set_drink'] ?? $_SESSION['set_drink'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cart - Ayam Gepuk Order</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff8f5;
        }

        .cart-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        h2, h3 {
            color: #800000;
            margin-bottom: 20px;
        }

        .cart-items {
            margin-bottom: 30px;
        }

        .cart-items p {
            font-size: 17px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            margin-top: 30px;
            color: #333;
        }

        .confirm-btn {
            width: 100%;
            padding: 15px;
            background-color: #800000;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
        }

        .confirm-btn:hover {
            background-color: #a30000;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #800000;
            text-decoration: none;
            font-weight: bold;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        textarea {
            resize: vertical;
            min-height: 60px;
        }
    </style>
</head>
<body>

<div class="cart-container">
    <a href="menu.php" class="back-btn">&larr; Back to Menu</a>
    <h2>Cart Summary</h2>

    <?php
    $totalPrice = 0;
    echo "<div class='cart-items'>";

    foreach ($_SESSION['food'] as $food) {
        list($foodName, $foodPrice, $isSetMeal) = explode("|", $food);
        $foodQty = intval($_SESSION['food_qty'][$foodName] ?? 1);
        $spiciness = $_SESSION['spiciness'][$foodName] ?? 'None';
        $itemTotal = floatval($foodPrice) * $foodQty;

        echo "<p><strong>🍗 $foodName</strong> ($spiciness) × $foodQty = <strong>RM " . number_format($itemTotal, 2) . "</strong></p>";
        $totalPrice += $itemTotal;

        if ($isSetMeal === "1" || $isSetMeal === "true") {
            $drinkValue = $_SESSION['set_drink'][$foodName] ?? '';
            if ($drinkValue !== '') {
                list($drinkName, $drinkPrice) = explode("|", $drinkValue);
                echo "<p style='margin-left: 20px;'>🧃 <em>Drink (Set):</em> $drinkName <strong>(Included)</strong></p>";
            }
        }
    }

    foreach ($_SESSION['drink'] as $drink) {
        list($drinkName, $drinkPrice) = explode("|", $drink);
        $drinkQty = intval($_SESSION['drink_qty'][$drinkName] ?? 1);
        $drinkTotal = floatval($drinkPrice) * $drinkQty;

        echo "<p><strong>🥤 $drinkName</strong> × $drinkQty = <strong>RM " . number_format($drinkTotal, 2) . "</strong></p>";
        $totalPrice += $drinkTotal;
    }

    echo "<div class='total'>Total: RM " . number_format($totalPrice, 2) . "</div>";
    echo "</div>";
    ?>

    <form action="cart.php" method="POST">

        <h3>Customer Information</h3>

        <label for="username">Name:</label>
        <input type="text" name="username" id="username" value="<?= htmlspecialchars($userInfo['username']) ?>" required>

        <label for="phone_number">Mobile Number:</label>
        <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($userInfo['phone_number']) ?>" required>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($userInfo['email']) ?>" required>

        <label for="address">Address:</label>
        <textarea name="address" id="address" required><?= htmlspecialchars($userInfo['location']) ?></textarea>

        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" id="payment_method" required>
            <option value="">-- Select Payment Method --</option>
            <option value="Debit Card">Debit Card</option>
            <option value="Online Banking">Online Banking</option>
            <option value="Cash on Delivery">Cash on Delivery</option>
        </select>

        <button type="submit" class="confirm-btn">Proceed to pay</button>
    </form>
</div>

</body>
</html>


