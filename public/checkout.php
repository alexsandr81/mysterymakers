<?php
session_start();
require_once '../database/db.php';

// Включаем отображение ошибок для отладки
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Проверяем, пришли ли данные из формы
if (!isset($_POST['delivery_name'], $_POST['phone'], $_POST['email'], $_POST['delivery'], $_POST['payment'], $_POST['total_price'], $_POST['total_discount'])) {
    echo "Ошибка: не все данные формы переданы.";
    print_r($_POST);
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$delivery_name = trim($_POST['delivery_name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$delivery = trim($_POST['delivery']);
$payment = trim($_POST['payment']);
$total_price = (float)$_POST['total_price'];
$total_discount = (float)$_POST['total_discount'];

// Проверяем, есть ли товары в корзине
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Генерируем уникальный номер заказа
$order_number = 'MM' . time() . rand(100, 999);

// Создаём заказ в таблице `orders`
try {
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, order_number, total_price, total_discount, status, delivery_name, phone, email, delivery, payment) 
        VALUES (:user_id, :order_number, :total_price, :total_discount, 'Новый', :delivery_name, :phone, :email, :delivery, :payment)
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':order_number' => $order_number,
        ':total_price' => $total_price,
        ':total_discount' => $total_discount,
        ':delivery_name' => $delivery_name,
        ':phone' => $phone,
        ':email' => $email,
        ':delivery' => $delivery,
        ':payment' => $payment
    ]);
} catch (PDOException $e) {
    echo "Ошибка при создании заказа: " . $e->getMessage();
    exit();
}

$order_id = $conn->lastInsertId();

// Добавляем товары в `order_items`
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = :product_id");
    $stmt->execute([':product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $stmt_discount = $conn->prepare("
            SELECT discount_value, discount_type 
            FROM discounts 
            WHERE (product_id = :product_id OR category_id = (SELECT category_id FROM products WHERE id = :product_id)) 
            AND (start_date IS NULL OR start_date <= NOW()) 
            AND (end_date IS NULL OR end_date >= NOW()) 
            LIMIT 1
        ");
        $stmt_discount->execute([':product_id' => $product_id]);
        $discount = $stmt_discount->fetch(PDO::FETCH_ASSOC);

        $original_price = $product['price'];
        $discount_value = 0;

        if ($discount) {
            if ($discount['discount_type'] == 'fixed') {
                $discount_value = min($discount['discount_value'], $original_price);
            } elseif ($discount['discount_type'] == 'percentage') {
                $discount_value = $original_price * ($discount['discount_value'] / 100);
            }
        }

        $final_price = $original_price - $discount_value;

        try {
            $stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (:order_id, :product_id, :quantity, :price)
            ");
            $stmt->execute([
                ':order_id' => $order_id,
                ':product_id' => $product_id,
                ':quantity' => $quantity,
                ':price' => $final_price
            ]);
        } catch (PDOException $e) {
            echo "Ошибка при добавлении товара в заказ: " . $e->getMessage();
            exit();
        }
    }
}

// Очищаем корзину
unset($_SESSION['cart']);

// Перенаправляем на страницу "Спасибо за заказ!"
header("Location: thank_you.php?order_number=$order_number");
exit();
?>