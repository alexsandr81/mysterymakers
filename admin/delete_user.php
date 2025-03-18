<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;

// Удаляем сначала заказы пользователя
$stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
$stmt->execute([$id]);

// Теперь можно удалить пользователя
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);

header("Location: users.php");
exit();
?>
