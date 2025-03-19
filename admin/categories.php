<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем список категорий
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Добавление категории
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);
    header("Location: categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление категориями</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Категории</h2>

<form method="POST">
    <label>Название категории:</label>
    <input type="text" name="name" required>
    <button type="submit">Добавить</button>
</form>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Название</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($categories as $cat): ?>
    <tr>
        <td><?= $cat['id']; ?></td>
        <td><?= htmlspecialchars($cat['name']); ?></td>
        <td>
            <a href="delete_category.php?id=<?= $cat['id']; ?>" onclick="return confirm('Удалить категорию?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
