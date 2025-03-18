<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем список товаров
$stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление товарами</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Товары</h2>
<a href="add_product.php">➕ Добавить товар</a>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Название</th>
        <th>Цена</th>
        <th>Категория</th>
        <th>Действия</th>
    </tr>

    <?php foreach ($products as $product): ?>
    <tr>
        <td><?= $product['id']; ?></td>
        <td><?= htmlspecialchars($product['name']); ?></td>
        <td><?= number_format($product['price'], 2, '.', ''); ?> ₽</td>
        <td><?= htmlspecialchars($product['category']); ?></td>
        <td>
            <a href="edit_product.php?id=<?= $product['id']; ?>">✏ Редактировать</a> | 
            <a href="delete_product.php?id=<?= $product['id']; ?>" onclick="return confirm('Удалить товар?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
