<?php
session_start();

if (isset($_POST['order_type'])) {
    $type = $_POST['order_type'];
    $_SESSION['order_type'] = $type;

    if ($type === 'delivery') {
        header("Location: delivery.php");
    } elseif ($type === 'pickup') {
        header("Location: pickup.php");
    } else {
        header("Location: deliverypickup.php");
    }
    exit;
} else {
    header("Location: deliverypickup.php");
    exit;
}
