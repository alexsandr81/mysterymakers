<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;

// Запрещаем удалять суперадминистратора (ID = 1)
if ($id == 1) {
    die("Ошибка: Нельзя удалить суперадмина!");
}

$stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
$stmt->execute([$id]);

header("Location: admins.php");
exit();
?>
