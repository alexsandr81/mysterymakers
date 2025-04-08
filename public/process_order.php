<?php
session_start();
include '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $total_price = $_POST['total_price'];

    // Создаем заказ
    $stmt = $conn->prepare("INSERT INTO orders (user_name, phone, email, address, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $email, $address, $total_price]);
    $order_id = $conn->lastInsertId();

    // Добавляем товары в заказ
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);
    }

    // Очищаем корзину
    $_SESSION['cart'] = [];

    // Перенаправляем на страницу "Спасибо за заказ!"
    header("Location: thank_you.php?order_id=" . $order_id);
    exit();
}
?>
