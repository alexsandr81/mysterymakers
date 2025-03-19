<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("Ошибка: нет доступа");
}

$id = $_POST['id'] ?? 0;

// Получаем текущий статус товара
$stmt = $conn->prepare("SELECT status FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    exit("Ошибка: товар не найден");
}

// Меняем статус (1 -> 0 или 0 -> 1)
$new_status = ($product['status'] == 1) ? 0 : 1;
$stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
$stmt->execute([$new_status, $id]);

echo "OK";
?>
