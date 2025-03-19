<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем категории
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Получаем список подкатегорий
$subcategories = $conn->query("SELECT s.*, c.name AS category_name 
                               FROM subcategories s 
                               JOIN categories c ON s.category_id = c.id 
                               ORDER BY c.name, s.name")->fetchAll(PDO::FETCH_ASSOC);

// Добавление подкатегории
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'], $_POST['category_id'])) {
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $stmt = $conn->prepare("INSERT INTO subcategories (name, category_id) VALUES (?, ?)");
    $stmt->execute([$name, $category_id]);
    header("Location: subcategories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление подкатегориями</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Подкатегории</h2>

<form method="POST">
    <label>Категория:</label>
    <select name="category_id" required>
        <option value="">Выберите категорию</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <label>Название подкатегории:</label>
    <input type="text" name="name" required>
    <button type="submit">Добавить</button>
</form>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Категория</th>
        <th>Подкатегория</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($subcategories as $sub): ?>
    <tr>
        <td><?= $sub['id']; ?></td>
        <td><?= htmlspecialchars($sub['category_name']); ?></td>
        <td><?= htmlspecialchars($sub['name']); ?></td>
        <td>
            <a href="delete_subcategory.php?id=<?= $sub['id']; ?>" onclick="return confirm('Удалить подкатегорию?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
