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

// Получаем размеры
$sizes = $conn->query("SELECT * FROM sizes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Добавление размера
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $stmt = $conn->prepare("INSERT INTO sizes (name) VALUES (?)");
    $stmt->execute([$name]);
    header("Location: sizes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление размерами</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Размеры</h2>

<form method="POST">
    <label>Название размера:</label>
    <input type="text" name="name" required>
    <button type="submit">Добавить</button>
</form>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Размер</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($sizes as $size): ?>
    <tr>
        <td><?= $size['id']; ?></td>
        <td><?= htmlspecialchars($size['name']); ?></td>
        <td>
        <a href="edit_size.php?id=<?= $size['id']; ?>">✏ Редактировать</a> |
            <a href="delete_size.php?id=<?= $size['id']; ?>" onclick="return confirm('Удалить?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
