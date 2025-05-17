<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

// Логирование
ini_set('display_errors', 0);
error_reporting(E_ALL);
file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - Начало update_cart.php\n", FILE_APPEND);

// Очищаем буфер вывода
ob_clean();

// Отключаем кэширование
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

// Логируем заголовки
file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - Headers: " . print_r(headers_list(), true) . "\n", FILE_APPEND);

$response = ['status' => 'error', 'message' => 'Неизвестная ошибка'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_POST['id'], $_POST['quantity'], $_POST['csrf_token']) || 
    !verifyCsrfToken($_POST['csrf_token'])) {
    $response['message'] = 'Неверный запрос или CSRF-токен';
    file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка CSRF-токена: " . ($_POST['csrf_token'] ?? 'не передан') . "\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

$product_id = (int)$_POST['id'];
$quantity = (int)$_POST['quantity'];

if ($quantity < 1 || $quantity > 99) {
    $response['message'] = 'Недопустимое количество';
    file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка: некорректное quantity=$quantity\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

$is_guest = !isset($_SESSION['user_id']) || isset($_SESSION['logged_out']);
$cart_key = $is_guest ? 'guest_cart' : 'cart';

file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - is_guest: $is_guest, cart_key: $cart_key, product_id: $product_id, quantity: $quantity, session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Проверяем, существует ли товар
try {
    $stmt = $conn->prepare("SELECT id, price, category_id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $response['message'] = 'Товар не найден';
        file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка: товар id=$product_id не найден\n", FILE_APPEND);
        echo json_encode($response);
        exit();
    }
} catch (PDOException $e) {
    $response['message'] = 'Ошибка базы данных: ' . $e->getMessage();
    file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка БД: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode($response);
    exit();
}

// Обновляем корзину
if (!isset($_SESSION[$cart_key])) {
    $_SESSION[$cart_key] = [];
}

$_SESSION[$cart_key][$product_id] = $quantity;

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
        file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - Ошибка пересчёта: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode($response);
        exit();
    }
} else {
    unset($_SESSION['cart_totals']);
}

$response = ['status' => 'success'];
file_put_contents('update_cart_log.txt', date('Y-m-d H:i:s') . " - Успех: корзина обновлена, quantity: $quantity, totals: " . print_r($_SESSION['cart_totals'], true) . "\n", FILE_APPEND);
echo json_encode($response);
exit();
?>