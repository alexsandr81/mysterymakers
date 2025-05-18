<?php
session_start();
require_once '../database/db.php';

// Проверяем авторизацию
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$product_id = $_GET['product_id'] ?? 0;
$image_path = urldecode($_GET['image'] ?? '');

// Получаем текущие изображения
$stmt = $conn->prepare("SELECT images FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Товар не найден!");
}

$current_images = json_decode($product['images'], true) ?? [];

// Удаляем изображение из массива
$new_images = array_filter($current_images, function ($img) use ($image_path) {
    return $img !== $image_path;
});

// Удаляем файл с сервера
if (file_exists("../" . $image_path)) {
    unlink("../" . $image_path);
}

// Обновляем базу
$stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
$stmt->execute([json_encode(array_values($new_images)), $product_id]);

header("Location: edit_product.php?id=$product_id");
exit();
?>
