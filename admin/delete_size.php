<?php
session_start();
require_once '../database/db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

// Получаем ID размера
$id = $_GET['id'] ?? 0;
$id = intval($id);

// Удаляем размер
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM sizes WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: sizes.php");
exit();
?>
