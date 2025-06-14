<?php
// pickup.php
session_start();
require 'db.php'; // connect to your ayam_gepuk database

// Get outlets from DB
$query = "SELECT name, address, latitude, longitude FROM outlets";
$result = $conn->query($query);
$outlets = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pickup Order</title>
    <link rel="stylesheet" href="style.css">
    <style>
    html, body { height: 100%; margin: 0; padding: 0; display: flex; flex-direction: column; font-family: 'Segoe UI', sans-serif; background-color: #800000; background-size: cover; background-position: center; color: #000; }
    .content-wrapper { flex: 1; }       
    .header { background-color: #800000; display: flex; align-items: center; justify-content: space-between; padding: 15px 40px; }
    .header img { height: 50px; }
    .nav { display: flex; gap: 25px; }
    .nav a { color: white; text-decoration: none; font-weight: bold; }
    .pickup-header { text-align: center; padding: 40px 0 20px; font-size: 48px; font-weight: bold; background-color: #800000; color: yellow; }
    .outlet-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; padding: 40px; background-color: white; }
    .outlet-box { background-color: #800000; width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; text-align: center; color: white; font-weight: bold; font-size: 16px; border-radius: 8px; cursor: pointer; transition: transform 0.2s ease; border: none; }
    .outlet-box:hover { transform: scale(1.05); background-color: #a00000; }
    .footer { background: black; color: white; padding: 20px; font-size: 14px; display: flex; flex-wrap: wrap; justify-content: space-between; margin-top: auto; }
    .footer .logo img { height: 60px; }
    .footer-column { flex: 1; min-width: 150px; margin: 10px 0; }
    .footer input[type="email"] { padding: 8px; width: 80%; margin-top: 10px; }
    .footer button { padding: 8px 15px; background: gold; border: none; cursor: pointer; font-weight: bold; margin-top: auto; }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/logo.png" alt="Logo">
        <div class="nav">
            <a href="#">HOME</a>
            <a href="#">EXPLORE PG</a>
            <a href="#">FOOD</a>
            <a href="#">PG DEALS</a>
            <a href="#">OUTLETS</a>
            <a href="#" style="color: yellow;">DELIVERY/PICKUP</a>
            <a href="#">REVIEW</a>
            <a href="#">CONTACT US</a>
        </div>
        <img src="user.png" alt="User" style="height: 40px; border-radius: 50%;">
    </div>

    <div class="pickup-header">PICKUP ORDER</div>
    <div class="outlet-grid">
        <?php foreach ($outlets as $outlet): ?>
        <form method="POST" action="set_pickup.php" style="display:inline;">
            <input type="hidden" name="outlet_name" value="<?= htmlspecialchars($outlet['name']) ?>">
            <button type="submit" class="outlet-box"><?= htmlspecialchars($outlet['name']) ?></button>
        </form>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        <div class="logo">
            <img src="images/logo.png" alt="Ayam Gepuk Pak Gembus">
            <p>AYAM GEPUK PAK GEMBUS MALAYSIA</p>
        </div>
        <div class="section">
            <h4>HOME</h4>
            <p>EXPLORE</p>
            <p>MENU</p>
            <p>PROMOTIONS</p>
            <p>OUTLETS</p>
        </div>
        <div class="section">
            <h4>NEWS</h4>
            <p>BUSINESS OPPORTUNITY</p>
            <p>DELIVERY/PICKUP</p>
            <p>CONTACT US</p>
        </div>
        <div class="section subscribe">
            <h4>Subscribe to get latest updates and deals straight to your inbox.</h4>
            <input type="email" placeholder="Your email address">
            <button>SUBSCRIBE</button>
        </div>
    </div>
</body>
</html>
