<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery / Pickup - Ayam Gepuk</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(to bottom, #ffcc33, #cc0000);
            color: maroon;
        }

        header {
            background-color: #8B0000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
        }

        .logo {
            height: 60px;
        }

        nav a {
            color: white;
            margin: 0 10px;
            text-decoration: none;
            font-weight: bold;
        }

        .main-content {
            text-align: center;
            padding: 50px 20px;
        }

.main-content img {
    max-width: 800px;
    margin-top: 20px;
    border-radius: 10px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

        .button-group {
            margin: 30px 0;
        }

        .button-group form {
            display: inline-block;
        }

        .button-group button {
            padding: 15px 30px;
            font-size: 18px;
            font-weight: bold;
            margin: 0 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color: #6B0606;
            color: rgb(255, 255, 255);
        }

        .button-group button:hover {
            background-color: #8B0000;
        }

        .promo-section {
            background-color: #8B0000;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .promo-text {
            max-width: 500px;
        }

        .promo-text h2 {
            color: #fff;
        }

        .promo-text p {
            color: #fff;
            font-size: 16px;
        }

        footer {
            background-color: #000;
            color: white;
            padding: 30px 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        footer .left,
        footer .right {
            flex: 1 1 300px;
        }

        footer input[type="email"] {
            padding: 10px;
            border-radius: 5px;
            border: none;
            width: 70%;
        }

        footer button {
            padding: 10px 20px;
            background-color: #f5c542;
            border: none;
            color: black;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 10px;
        }

        .footer-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 10px;
        }

        .footer-nav a {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .social-icons {
            margin-top: 10px;
        }

        .social-icons img {
            height: 24px;
            margin-right: 8px;
        }
    </style>
</head>
<body>

<header>
    <img src="images/logo.png" alt="Logo" class="logo">
    <nav>
        <a href="#">HOME</a>
        <a href="#">EXPLORE PG</a>
        <a href="#">FOOD</a>
        <a href="#">PG DEALS</a>
        <a href="#">OUTLETS</a>
        <a href="#">DELIVERY/PICKUP</a>
        <a href="#">REVIEW</a>
        <a href="#">CONTACT US</a>
    </nav>
</header>

<div style="position: absolute; top: 60px; right: 20px;">
    <form method="post" action="logout.php">
        <button type="submit" style="padding: 8px 16px; background-color: black; color: white; border: none; border-radius: 5px;">
            Logout
        </button>
    </form>
</div>

<div class="main-content">
    <h1>Choose Your Order Option</h1>
    <div class="button-group">
        <form action="delivery.php" method="get">
  <button type="submit">DELIVERY</button>
</form>
        <form action="pickup.php" method="get">
  <button type="submit">PICKUP</button>
</form>

    </div>
    <img src="images/banyak.webp" alt="Ayam Gepuk Dish"> <!-- Replace with your actual image -->
</div>

<section class="promo-section">
    <div class="promo-text">
        <h2>Pandan Coconut – New!</h2>
        <p>
            Quench your thirst with our refreshing new Pandan Coconut drink!<br><br>
            This rejuvenating blend features the aromatic allure of pandan, the creamy richness of coconut,
            and delightful chewy bits that add a playful twist. Rehydrate in a fresher way and experience a burst of tropical bliss with every sip.
        </p>
    </div>
    <div>
        <img src="images/pandan.png" alt="Pandan Coconut Drink" style="max-width: 250px; border-radius: 10px;">
    </div>
</section>

<footer>
    <div class="left">
        <img src="images/logo.png" alt="Logo" style="height: 80px;">
        <p>AYAM GEPUK PAK GEMBUS MALAYSIA</p>
        <p>Subscribe to get latest updates and deals straight to your inbox</p>
        <input type="email" placeholder="Your email address">
        <button>SUBSCRIBE</button>
    </div>
    <div class="right">
        <div class="footer-nav">
            <a href="#">HOME</a>
            <a href="#">EXPLORE</a>
            <a href="#">MENU</a>
            <a href="#">PROMOTIONS</a>
            <a href="#">OUTLETS</a>
            <a href="#">NEWS</a>
            <a href="#">BUSINESS OPPORTUNITY</a>
            <a href="#">DELIVERY/PICKUP</a>
            <a href="#">CONTACT US</a>
        </div>
        <div class="social-icons">
            <img src="facebook.png" alt="Facebook">
            <img src="instagram.png" alt="Instagram">
            <img src="tiktok.png" alt="TikTok">
        </div>
    </div>
</footer>

</body>
</html>
