<?php
session_start();

$is_guest = !isset($_SESSION['user_id']) || isset($_SESSION['logged_out']);
$cart = $is_guest ? ($_SESSION['guest_cart'] ?? []) : ($_SESSION['cart'] ?? []);

echo json_encode(['count' => array_sum($cart)]);
?>