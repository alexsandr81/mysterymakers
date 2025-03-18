<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Обрабатываем добавление товара
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);

    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $category, $stock]);

    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить товар</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Добавить товар</h2>

<form method="POST">
    <label>Название:</label>
    <input type="text" name="name" required><br><br>

    <label>Описание:</label>
    <textarea name="description" required></textarea><br><br>

    <label>Цена:</label>
    <input type="number" name="price" step="0.01" required><br><br>

    <label>Категория:</label>
    <input type="text" name="category" required><br><br>

    <label>Количество:</label>
    <input type="number" name="stock" required><br><br>

    <button type="submit">Добавить</button>
</form>

</body>
</html>
