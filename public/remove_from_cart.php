<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

// Логирование
ini_set('display_errors', 0);
error_reporting(E_ALL);
file_put_contents('remove_cart_log.txt', date('Y-m-d H:i:s') . " - Начало remove_from_cart.php\n", FILE_APPEND);

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Неизвестная ошибка'];

// Проверяем подключение к базе данных
if (!$conn) {
    $response['message'] = 'Ошибка подключения к базе данных';
    file_put_contents('remove_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка: нет подключения к БД\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

// Проверяем CSRF-токен
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    $response['message'] = 'Недействительный CSRF-токен';
    file_put_contents('remove_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка CSRF-токена: " . ($_POST['csrf_token'] ?? 'не передан') . "\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

// Проверяем входные данные
if (!isset($_POST['id'])) {
    $response['message'] = 'Не указан ID товара';
    file_put_contents('remove_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка: отсутствует id\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

$product_id = (int)$_POST['id'];

if ($product_id <= 0) {
    $response['message'] = 'Некорректный ID товара';
    file_put_contents('remove_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка: некорректный id=$product_id\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

$is_guest = !isset($_SESSION['user_id']) || isset($_SESSION['logged_out']);
$cart_key = $is_guest ? 'guest_cart' : 'cart';

file_put_contents('remove_cart_log.txt', date('Y-m-d H:i:s') . " - is_guest: $is_guest, cart_key: $cart_key, product_id: $product_id, session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Удаляем товар из корзины
if (isset($_SESSION[$cart_key][$product_id])) {
    unset($_SESSION[$cart_key][$product_id]);
}

// Пересчитываем totals
$cart = $_SESSION[$cart_key] ?? [];
$total = 0;
$total_discount = 0;

if (!empty($cart)) {
    try {
        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $stmt = $conn->prepare("SELECT id, price, category_id FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($cart));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $pid = (int)$product['id'];
            $qty = (int)($cart[$pid] ?? 0);
            if ($qty <= 0) continue;

            $stmt = $conn->prepare("
                SELECT discount_value, discount_type 
                FROM discounts 
                WHERE (product_id = ? OR category_id = ?) 
                AND (start_date IS NULL OR start_date <= NOW()) 
                AND (end_date IS NULL OR end_date >= NOW()) 
                LIMIT 1
            ");
            $stmt->execute([$pid, (int)$product['category_id']]);
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
            $total += $final_price * $qty;
            $total_discount += ($original_price - $final_price) * $qty;
        }

        $_SESSION['cart_totals'] = [
            'total_price' => $total,
            'total_discount' => $total_discount
        ];
    } catch (PDOException $e) {
        $response['message'] = 'Ошибка базы данных при пересчёте: ' . $e->getMessage();
        file_put_contents('remove_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка пересчёта: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode($response);
        exit();
    }
} else {
    unset($_SESSION['cart_totals']);
}

$response = [
    'status' => 'success',
    'message' => 'Товар удалён из корзины'
];

file_put_contents('remove_cart_log.txt', date('Y-m-d H:i:s') . " - Успех: товар id=$product_id удалён\n", FILE_APPEND);
echo json_encode($response);
exit();
?>