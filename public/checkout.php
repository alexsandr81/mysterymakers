<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

// Включаем отображение ошибок и логирование
ini_set('display_errors', 1);
error_reporting(E_ALL);
file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - Начало checkout.php\n", FILE_APPEND);

// Проверяем CSRF-токен
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - Ошибка CSRF-токена\n", FILE_APPEND);
    $_SESSION['form_errors'] = ['general' => 'Недействительный CSRF-токен'];
    header("Location: cart.php");
    exit();
}

// Проверяем, есть ли данные формы и корзина
$is_guest = !isset($_SESSION['user_id']) || isset($_SESSION['logged_out']);
$cart_key = $is_guest ? 'guest_cart' : 'cart';

file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - is_guest: $is_guest, cart_key: $cart_key\n", FILE_APPEND);
file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - SESSION[cart_key]: " . print_r($_SESSION[$cart_key] ?? [], true) . "\n", FILE_APPEND);
file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - SESSION[cart_totals]: " . print_r($_SESSION['cart_totals'] ?? [], true) . "\n", FILE_APPEND);

if (!isset($_POST['delivery_name'], $_POST['phone'], $_POST['email'], $_POST['delivery'], $_POST['payment']) ||
    !isset($_SESSION[$cart_key]) || empty($_SESSION[$cart_key]) ||
    !isset($_SESSION['cart_totals'])) {
    file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - Ошибка: Не все данные доступны\n", FILE_APPEND);
    $_SESSION['form_errors'] = ['general' => 'Не все данные доступны.'];
    header("Location: cart.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$delivery_name = trim($_POST['delivery_name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$delivery = trim($_POST['delivery']);
$payment = trim($_POST['payment']);
$total_price = (float)$_SESSION['cart_totals']['total_price'];
$total_discount = (float)$_SESSION['cart_totals']['total_discount'];

// Валидация данных
$errors = [];

if (empty($delivery_name)) {
    $errors['delivery_name'] = 'Имя обязательно для заполнения';
} elseif (strlen($delivery_name) > 255) {
    $errors['delivery_name'] = 'Имя слишком длинное';
}

if (!preg_match('/^\+?\d{10,12}$/', $phone)) {
    $errors['phone'] = 'Телефон должен содержать 10–12 цифр, может начинаться с +';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email';
} elseif (strlen($email) > 255) {
    $errors['email'] = 'Email слишком длинный';
}

if (!in_array($delivery, ['Курьер', 'Самовывоз', 'Почта'])) {
    $errors['delivery'] = 'Неверный способ доставки';
}

if (!in_array($payment, ['Наличными', 'Картой'])) {
    $errors['payment'] = 'Неверный способ оплаты';
}

// Если есть ошибки, сохраняем данные и возвращаемся к форме
if (!empty($errors)) {
    file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - Ошибки валидации: " . print_r($errors, true) . "\n", FILE_APPEND);
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = [
        'delivery_name' => $delivery_name,
        'phone' => $phone,
        'email' => $email,
        'delivery' => $delivery,
        'payment' => $payment
    ];
    header("Location: order_form.php");
    exit();
}

// Пересчитываем корзину для валидации
$cart = $_SESSION[$cart_key];
$placeholders = implode(',', array_fill(0, count($cart), '?'));
$stmt = $conn->prepare("SELECT id, price, category_id FROM products WHERE id IN ($placeholders)");
$stmt->execute(array_keys($cart));
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$calculated_total = 0;
$calculated_discount = 0;

foreach ($products as $product) {
    $product_id = (int)$product['id'];
    $quantity = (int)($cart[$product_id] ?? 0);
    if ($quantity <= 0) continue;

    $stmt = $conn->prepare("
        SELECT discount_value, discount_type 
        FROM discounts 
        WHERE (product_id = ? OR category_id = ?) 
        AND (start_date IS NULL OR start_date <= NOW()) 
        AND (end_date IS NULL OR end_date >= NOW()) 
        LIMIT 1
    ");
    $stmt->execute([$product_id, (int)$product['category_id']]);
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);

    $original_price = (float)$product['price'];
    $discount_value = 0;

    if ($discount) {
        if ($discount['discount_type'] == 'fixed') {
            $discount_value = min((float)$discount['discount_value'], $original_price);
        } elseif ($discount['discount_type'] == 'percentage') {
            $discount_value = $original_price * ((float)$discount['discount_value'] / 100);
        }
    }

    $final_price = $original_price - $discount_value;
    $calculated_total += $final_price * $quantity;
    $calculated_discount += ($original_price - $final_price) * $quantity;
}

// Валидация: проверяем, что значения из сессии совпадают с пересчётом
if (abs($calculated_total - $total_price) > 0.01 || abs($calculated_discount - $total_discount) > 0.01) {
    file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - Ошибка: Итоговая сумма или скидка не совпадают\n", FILE_APPEND);
    $_SESSION['form_errors'] = ['general' => 'Итоговая сумма или скидка не совпадают с корзиной.'];
    $_SESSION['form_data'] = [
        'delivery_name' => $delivery_name,
        'phone' => $phone,
        'email' => $email,
        'delivery' => $delivery,
        'payment' => $payment
    ];
    header("Location: order_form.php");
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
    file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - Ошибка базы данных: " . $e->getMessage() . "\n", FILE_APPEND);
    $_SESSION['form_errors'] = ['general' => 'Ошибка при создании заказа: ' . $e->getMessage()];
    $_SESSION['form_data'] = [
        'delivery_name' => $delivery_name,
        'phone' => $phone,
        'email' => $email,
        'delivery' => $delivery,
        'payment' => $payment
    ];
    header("Location: order_form.php");
    exit();
}

$order_id = $conn->lastInsertId();

// Добавляем товары в `order_items`
foreach ($_SESSION[$cart_key] as $product_id => $quantity) {
    $stmt = $conn->prepare("SELECT price, category_id FROM products WHERE id = :product_id");
    $stmt->execute([':product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $stmt_discount = $conn->prepare("
            SELECT discount_value, discount_type 
            FROM discounts 
            WHERE (product_id = :product_id OR category_id = :category_id) 
            AND (start_date IS NULL OR start_date <= NOW()) 
            AND (end_date IS NULL OR end_date >= NOW()) 
            LIMIT 1
        ");
        $stmt_discount->execute([':product_id' => $product_id, ':category_id' => $product['category_id']]);
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
            file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - Ошибка добавления товара: " . $e->getMessage() . "\n", FILE_APPEND);
            $_SESSION['form_errors'] = ['general' => 'Ошибка при добавлении товара в заказ: ' . $e->getMessage()];
            $_SESSION['form_data'] = [
                'delivery_name' => $delivery_name,
                'phone' => $phone,
                'email' => $email,
                'delivery' => $delivery,
                'payment' => $payment
            ];
            header("Location: order_form.php");
            exit();
        }
    }
}

// Очищаем корзину и временные данные
unset($_SESSION[$cart_key]);
unset($_SESSION['cart_totals']);
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

// Перенаправляем на страницу "Спасибо за заказ!"
file_put_contents('checkout_log.txt', date('Y-m-d H:i:s') . " - Заказ создан, order_number: $order_number\n", FILE_APPEND);
header("Location: thank_you.php?order_number=$order_number");
exit();
?>