<?php
session_start();
require_once '../database/db.php';

// Проверяем авторизацию пользователя
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Проверяем, пришли ли данные из формы
if (!isset($_POST['name'], $_POST['phone'], $_POST['email'], $_POST['delivery'], $_POST['payment'])) {
    header("Location: order_form.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$delivery = trim($_POST['delivery']);
$payment = trim($_POST['payment']);

// Проверяем, есть ли товары в корзине
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$total_price = 0;

// Генерируем уникальный номер заказа
$order_number = 'MM' . time() . rand(100, 999);

// Создаём заказ в таблице `orders`
$stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_price, status, name, phone, email, delivery, payment) 
VALUES (:user_id, :order_number, :total_price, 'Новый', :name, :phone, :email, :delivery, :payment)");
$stmt->execute([
    ':user_id' => $user_id,
    ':order_number' => $order_number,
    ':total_price' => $total_price,
    ':name' => $name,
    ':phone' => $phone,
    ':email' => $email,
    ':delivery' => $delivery,
    ':payment' => $payment
]);

$order_id = $conn->lastInsertId(); // Получаем ID заказа

// Добавляем товары в `order_items`
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    // Получаем цену товара
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = :product_id");
    $stmt->execute([':product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $price = $product['price'];
        $total_price += $quantity * $price;

        // Записываем товар в заказ
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (:order_id, :product_id, :quantity, :price)");
        $stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $product_id,
            ':quantity' => $quantity,
            ':price' => $price
        ]);
    }
}

// Обновляем общую сумму заказа
$stmt = $conn->prepare("UPDATE orders SET total_price = :total_price WHERE id = :order_id");
$stmt->execute([
    ':total_price' => $total_price,
    ':order_id' => $order_id
]);

// Очищаем корзину
unset($_SESSION['cart']);

// Перенаправляем на страницу "Спасибо за заказ!"
header("Location: thank_you.php?order_number=$order_number");
exit();
?>
