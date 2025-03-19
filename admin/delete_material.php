<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Удаляем материал
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
$stmt->execute([$id]);

header("Location: materials.php");
exit();
?>
