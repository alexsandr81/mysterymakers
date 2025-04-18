<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

// Получаем ID материала
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM materials WHERE id = ?");
$stmt->execute([$id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$material) {
    die("Материал не найден!");
}

// Обновляем название материала
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    
    // Проверяем дублирование
    $check_stmt = $conn->prepare("SELECT id FROM materials WHERE name = ? AND id != ?");
    $check_stmt->execute([$name, $id]);
    if ($check_stmt->rowCount() > 0) {
        die("Ошибка: Такой материал уже существует!");
    }

    $stmt = $conn->prepare("UPDATE materials SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    header("Location: materials.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать материал</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Редактировать материал</h2>

<form method="POST">
    <label>Название материала:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($material['name']); ?>" required>
    <button type="submit">Сохранить</button>
</form>

</body>
</html>
