<?php
session_start();
$id = $_POST['id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Если товар уже есть в корзине, увеличиваем количество
if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id] += $quantity;
} else {
    $_SESSION['cart'][$id] = $quantity;
}

echo json_encode(['status' => 'success', 'cart_count' => array_sum($_SESSION['cart'])]);
?>
