<?php
session_start();
require_once '../database/db.php';
require_once 'log_action.php'; // Подключаем функцию логирования

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

if (!isset($_GET['id'])) {
    die("Ошибка: Не указан ID заказа.");
}

$order_id = intval($_GET['id']);

try {
    $conn->exec("SET FOREIGN_KEY_CHECKS=0");

    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);

    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);

    // Логируем действие
    logAdminAction($conn, $_SESSION['admin_id'], 'order_deleted', "Заказ #$order_id удалён");

    $conn->exec("SET FOREIGN_KEY_CHECKS=1");

    header("Location: orders.php");
    exit();
} catch (PDOException $e) {
    die("Ошибка при удалении заказа: " . $e->getMessage());
}
?>