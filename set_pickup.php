<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['outlet_name'])) {
    $_SESSION['pickup_outlet_name'] = $_POST['outlet_name'];
    // Redirect ke menu atau cart
    header("Location: menu_pickup.php");
    exit;
}
?>
