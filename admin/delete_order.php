<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Ошибка: Не указан ID заказа.");
}

$order_id = intval($_GET['id']);

try {
    // Отключаем проверку внешних ключей (если требуется)
    $conn->exec("SET FOREIGN_KEY_CHECKS=0");

    // Удаляем все позиции заказа из order_items
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);

    // Теперь можно удалить сам заказ
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);

    // Включаем проверку внешних ключей обратно
    $conn->exec("SET FOREIGN_KEY_CHECKS=1");

    header("Location: orders.php");
    exit();
} catch (PDOException $e) {
    die("Ошибка при удалении заказа: " . $e->getMessage());
}
?>
