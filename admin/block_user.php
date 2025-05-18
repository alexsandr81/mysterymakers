<?php
session_start();
require_once '../database/db.php';
require_once 'log_action.php'; // Для логирования

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Только superadmin может блокировать
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

if (!isset($_GET['id'])) {
    die("Ошибка: Не указан ID пользователя.");
}

$user_id = intval($_GET['id']);
$action = isset($_GET['action']) && $_GET['action'] === 'unblock' ? 'active' : 'blocked';

try {
    // Проверяем, существует ли пользователь
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("Ошибка: Пользователь не найден.");
    }

    // Обновляем статус
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$action, $user_id]);

    // Логируем действие
    $email = $user['email'];
    $log_action = $action === 'blocked' ? 'user_blocked' : 'user_unblocked';
    $log_details = $action === 'blocked' ? "Пользователь $email заблокирован" : "Пользователь $email разблокирован";
    logAdminAction($conn, $_SESSION['admin_id'], $log_action, $log_details);

    header("Location: users.php");
    exit();
} catch (PDOException $e) {
    die("Ошибка: " . $e->getMessage());
}
?>