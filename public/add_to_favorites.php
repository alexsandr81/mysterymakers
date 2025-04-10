<?php
session_start();
require_once '../database/db.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Обрабатываем только POST-запросы
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);

    $stmt = $conn->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $product_id]);

    header("Location: " . $_SERVER['HTTP_REFERER']); // Возврат на предыдущую страницу
    exit();
} else {
    // При прямом доступе перенаправляем на каталог
    header("Location: catalog.php");
    exit();
}
?>