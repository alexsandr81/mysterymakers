<?php
session_start();
require_once '../database/db.php';
require_once 'log_action.php'; // Подключаем функцию логирования

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Проверяем данные из формы
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = trim($_POST['status']);

    // Обновляем статус в базе
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    // Логируем действие
    logAdminAction($conn, $_SESSION['admin_id'], 'order_status_updated', "Статус заказа #$order_id изменён на $status");
}

header("Location: orders.php");
exit();
?>