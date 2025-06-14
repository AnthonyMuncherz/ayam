<?php 
session_start(); 
require_once 'db_connection.php';

// Fetch menu items from database
$foodItems = [];
$drinkItems = [];

try {
    if ($conn) {
        // Fetch food items
        $foodQuery = "SELECT * FROM menu_items WHERE type = 'food' ORDER BY name";
        $foodResult = $conn->query($foodQuery);
        if ($foodResult) {
            while ($row = $foodResult->fetch_assoc()) {
                $foodItems[] = $row;
            }
        }
        
        // Fetch drink items
        $drinkQuery = "SELECT * FROM menu_items WHERE type = 'drink' ORDER BY name";
        $drinkResult = $conn->query($drinkQuery);
        if ($drinkResult) {
            while ($row = $drinkResult->fetch_assoc()) {
                $drinkItems[] = $row;
            }
        }
    }
} catch (Exception $e) {
    error_log("Database error in menu.php: " . $e->getMessage());
    // Fallback to hardcoded items if database fails
    $foodItems = [
        ["id" => 1, "name" => "Ayam Gepuk Ori", "image_path" => "images/ayamgepukori.webp", "price" => 15.00, "is_set_meal" => 0],
        ["id" => 2, "name" => "Ayam Gepuk Crispy", "image_path" => "images/ayamgepukcrispy.jpg", "price" => 17.00, "is_set_meal" => 0],
        ["id" => 3, "name" => "Ayam Gepuk Ori Set", "image_path" => "images/ayamgepukoriset.jpg", "price" => 20.00, "is_set_meal" => 1],
        ["id" => 4, "name" => "Ayam Gepuk Crispy Set", "image_path" => "images/ayamgepukcrispyset.jpg", "price" => 22.00, "is_set_meal" => 1]
    ];
    $drinkItems = [
        ["id" => 5, "name" => "Honeydew Latte", "image_path" => "images/HoneydewLatte.webp", "price" => 5.00],
        ["id" => 6, "name" => "Iced Lemon Tea", "image_path" => "images/icelemontea.webp", "price" => 4.00],
        ["id" => 7, "name" => "Orange Fizz", "image_path" => "images/orangefizz.png", "price" => 6.00],
        ["id" => 8, "name" => "Bali Blue", "image_path" => "images/baliblue.png", "price" => 7.00],
        ["id" => 9, "name" => "Green Java", "image_path" => "images/greenjava.jpg", "price" => 6.50]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ayam Gepuk Menu & Drinks</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Your previous CSS from menu page */
        body {
            font-family: 'Segoe UI', sans-serif;
                 background: url('images/background.jpg');
      background-size: cover;
      background-position: center;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            text-align: center;
            padding: 30px;
        }
        h1 {
            color: #800000;
        }
        .menu-grid, .drink-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
            color: white;
        }
        .menu-item, .drink-item {
            border: 2px solid #800000;
            padding: 15px;
            border-radius: 10px;
            background-color:rgb(88, 2, 2);
            text-align: center;
            box-shadow: 0 4px 10px rgba(201, 195, 195, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            color: white;
        }
        .menu-item:hover, .drink-item:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        img {
            width: 150px;
            border-radius: 8px;
        }
        select, input[type="number"] {
            margin-top: 8px;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 100%;
        }
        .confirm-btn {
            margin-top: 30px;
            padding: 12px 25px;
            font-size: 18px;
            background-color: #800000;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .confirm-btn:hover {
            background-color: #a00000;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Pilih Menu & Minuman Ayam Gepuk</h1>
    <form action="cart.php" method="POST">

        <h3>Select Your Food</h3>
        <div class="menu-grid">
            <?php
            foreach ($foodItems as $item) {
                $isSetMeal = $item['is_set_meal'] ? "set-meal" : "";
                $itemName = htmlspecialchars($item['name']);
                $itemPrice = number_format($item['price'], 2);
                $imagePath = htmlspecialchars($item['image_path']);
                
                echo "<div class='menu-item'>
                        <label>
                            <img src='{$imagePath}' alt='{$itemName}' onerror=\"this.src='images/placeholder.svg'\"><br>
                            <input type='checkbox' name='food[]' value='{$itemName}|{$item['price']}|{$item['is_set_meal']}' class='$isSetMeal'> {$itemName} - RM {$itemPrice}
                        </label><br>
                        <label>Spiciness Level:</label>
                        <select name='spiciness[{$itemName}]'>
                            <option value='Mild'>Mild</option>
                            <option value='Medium'>Medium</option>
                            <option value='Spicy'>Spicy</option>
                            <option value='Extra Spicy'>Extra Spicy</option>
                        </select><br>
                        <label>Quantity:</label>
                        <input type='number' name='food_qty[{$itemName}]' value='1' min='1'>";

                if ($item['is_set_meal']) {
                    echo "<h4>Select a Drink</h4>";
                    foreach ($drinkItems as $drink) {
                        $drinkName = htmlspecialchars($drink['name']);
                        echo "<label>
                                <input type='radio' name='set_drink[{$itemName}]' value='{$drinkName}|0'> {$drinkName}
                              </label><br>";
                    }
                }
                echo "</div>";
            }
            ?>
        </div>

        <h3>Add-On Drinks (Optional)</h3>
        <div class="drink-grid">
            <?php
            foreach ($drinkItems as $drink) {
                $drinkName = htmlspecialchars($drink['name']);
                $drinkPrice = number_format($drink['price'], 2);
                $imagePath = htmlspecialchars($drink['image_path']);
                
                echo "<div class='drink-item'>
                        <label>
                            <img src='{$imagePath}' alt='{$drinkName}' onerror=\"this.src='images/placeholder.svg'\"><br>
                            <input type='checkbox' name='drink[]' value='{$drinkName}|{$drink['price']}'> {$drinkName} - RM {$drinkPrice}
                        </label><br>
                        <label>Quantity:</label>
                        <input type='number' name='drink_qty[{$drinkName}]' value='1' min='1'>
                      </div>";
            }
            ?>
        </div>

        <button type="submit" class="confirm-btn">Add to Cart</button>
    </form>
</div>

</body>
</html>