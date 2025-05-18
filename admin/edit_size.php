<?php
session_start();
require_once '../database/db.php';

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
$stmt = $conn->prepare("SELECT * FROM sizes WHERE id = ?");
$stmt->execute([$id]);
$size = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$size) {
    die("Размер не найден!");
}

// Обновляем название размера
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);

    // Проверяем дублирование
    $check_stmt = $conn->prepare("SELECT id FROM sizes WHERE name = ? AND id != ?");
    $check_stmt->execute([$name, $id]);
    if ($check_stmt->rowCount() > 0) {
        die("Ошибка: Такой размер уже существует!");
    }

    $stmt = $conn->prepare("UPDATE sizes SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    header("Location: sizes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать размер</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Редактировать размер</h2>

<form method="POST">
    <label>Название размера:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($size['name']); ?>" required>
    <button type="submit">Сохранить</button>
</form>

</body>
</html>
