<?php
session_start();
require_once '../database/db.php';
require_once 'log_action.php'; // Для логирования

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

if (!isset($_GET['id']) || !$_GET['id']) {
    die("Ошибка: Не указан ID пользователя.");
}

$user_id = intval($_GET['id']);

try {
    // Проверяем, существует ли пользователь
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("Ошибка: Пользователь не найден.");
    }

    // Начинаем транзакцию
    $conn->beginTransaction();

    // Удаляем избранное пользователя
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Удаляем элементы заказов (order_items), связанные с заказами пользователя
    $stmt = $conn->prepare("
        DELETE oi FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ?
    ");
    $stmt->execute([$user_id]);

    // Удаляем заказы пользователя
    $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Удаляем пользователя
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Логируем действие
    $email = $user['email'];
    logAdminAction($conn, $_SESSION['admin_id'], 'user_deleted', "Пользователь $email удалён");

    // Подтверждаем транзакцию
    $conn->commit();

    header("Location: users.php");
    exit();
} catch (PDOException $e) {
    // Откатываем транзакцию при ошибке
    $conn->rollBack();
    die("Ошибка при удалении пользователя: " . $e->getMessage());
}
?>