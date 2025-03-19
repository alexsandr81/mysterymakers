<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем список материалов
$materials = $conn->query("SELECT * FROM materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Добавление нового материала
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $stmt = $conn->prepare("INSERT INTO materials (name) VALUES (?)");
    $stmt->execute([$name]);
    header("Location: materials.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление материалами</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Материалы</h2>

<form method="POST">
    <label>Название материала:</label>
    <input type="text" name="name" required>
    <button type="submit">Добавить</button>
</form>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Материал</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($materials as $material): ?>
    <tr>
        <td><?= $material['id']; ?></td>
        <td><?= htmlspecialchars($material['name']); ?></td>
        <td>
            <a href="delete_material.php?id=<?= $material['id']; ?>" onclick="return confirm('Удалить материал?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
