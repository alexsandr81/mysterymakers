<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Недопустимый метод']);
    exit();
}

// Проверяем CSRF-токен
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Недействительный CSRF-токен']);
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($id <= 0 || $quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Недопустимый ID или количество']);
    exit();
}

// Проверяем, существует ли товар
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 1");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Товар не найден']);
    exit();
}

$is_guest = !isset($_SESSION['user_id']) || isset($_SESSION['logged_out']);
$cart_key = $is_guest ? 'guest_cart' : 'cart';

// Инициализируем корзину
if (!isset($_SESSION[$cart_key])) {
    $_SESSION[$cart_key] = [];
}

// Добавляем или обновляем товар
if (isset($_SESSION[$cart_key][$id])) {
    $_SESSION[$cart_key][$id] += $quantity;
} else {
    $_SESSION[$cart_key][$id] = $quantity;
}

echo json_encode(['status' => 'success', 'cart_count' => array_sum($_SESSION[$cart_key])]);
?>