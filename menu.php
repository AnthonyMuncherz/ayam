<?php session_start(); ?>
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
            $menuItems = [
                ["Ayam Gepuk Ori", "images/ayamgepukori.webp", 15.00, false],
                ["Ayam Gepuk Crispy", "images/ayamgepukcrispy.jpg", 17.00, false],
                ["Ayam Gepuk Ori Set", "images/ayamgepukoriset.jpg", 20.00, true],
                ["Ayam Gepuk Crispy Set", "images/ayamgepukcrispyset.jpg", 22.00, true]
            ];

            foreach ($menuItems as $item) {
                $isSetMeal = $item[3] ? "set-meal" : "";
                echo "<div class='menu-item'>
                        <label>
                            <img src='{$item[1]}' alt='{$item[0]}'><br>
                            <input type='checkbox' name='food[]' value='{$item[0]}|{$item[2]}|{$item[3]}' class='$isSetMeal'> {$item[0]} - RM " . number_format($item[2], 2) . "
                        </label><br>
                        <label>Spiciness Level:</label>
                        <select name='spiciness[{$item[0]}]'>
                            <option value='Mild'>Mild</option>
                            <option value='Medium'>Medium</option>
                            <option value='Spicy'>Spicy</option>
                            <option value='Extra Spicy'>Extra Spicy</option>
                        </select><br>
                        <label>Quantity:</label>
                        <input type='number' name='food_qty[{$item[0]}]' value='1' min='1'>";

                if ($item[3]) {
                    echo "<h4>Select a Drink</h4>";
                    foreach (["Honeydew Latte", "Iced Lemon Tea", "Orange Fizz", "Bali Blue", "Green Java"] as $drink) {
                        echo "<label>
                                <input type='radio' name='set_drink[{$item[0]}]' value='$drink|0'> $drink
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
            $drinks = [
                ["Honeydew Latte", "images/HoneydewLatte.webp", 5.00],
                ["Iced Lemon Tea", "images/icelemontea.webp", 4.00],
                ["Orange Fizz", "images/orangefizz.png", 6.00],
                ["Bali Blue", "images/baliblue.png", 7.00],
                ["Green Java", "images/greenjava.jpg", 6.50]
            ];

            foreach ($drinks as $drink) {
                echo "<div class='drink-item'>
                        <label>
                            <img src='{$drink[1]}' alt='{$drink[0]}'><br>
                            <input type='checkbox' name='drink[]' value='{$drink[0]}|{$drink[2]}'> {$drink[0]} - RM " . number_format($drink[2], 2) . "
                        </label><br>
                        <label>Quantity:</label>
                        <input type='number' name='drink_qty[{$drink[0]}]' value='1' min='1'>
                      </div>";
            }
            ?>
        </div>

        <button type="submit" class="confirm-btn">Add to Cart</button>
    </form>
</div>

</body>
</html>