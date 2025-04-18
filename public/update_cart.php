<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_POST['id'], $_POST['quantity'], $_POST['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Неверный запрос']);
    exit;
}

$product_id = (int)$_POST['id'];
$quantity = (int)$_POST['quantity'];

if ($quantity < 1 || $quantity > 99) {
    echo json_encode(['status' => 'error', 'message' => 'Недопустимое количество']);
    exit;
}

// Обновляем корзину
$_SESSION['cart'][$product_id] = $quantity;

// Пересчитываем totals
$cart = $_SESSION['cart'] ?? [];
$total = 0;
$total_discount = 0;

if (!empty($cart)) {
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
}

// Сохраняем totals в сессию
$_SESSION['cart_totals'] = [
    'total_price' => $total,
    'total_discount' => $total_discount
];

echo json_encode(['status' => 'success']);
exit();