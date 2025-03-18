<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем ID товара
$id = $_GET['id'] ?? 0;

// Загружаем товар из базы
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Товар не найден!");
}

// Обрабатываем обновление товара
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);

    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=? WHERE id=?");
    $stmt->execute([$name, $description, $price, $category, $stock, $id]);

    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать товар</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Редактировать товар</h2>

<form method="POST">
    <label>Название:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']); ?>" required><br><br>

    <label>Описание:</label>
    <textarea name="description" required><?= htmlspecialchars($product['description']); ?></textarea><br><br>

    <label>Цена:</label>
    <input type="number" name="price" step="0.01" value="<?= $product['price']; ?>" required><br><br>

    <label>Категория:</label>
    <input type="text" name="category" value="<?= htmlspecialchars($product['category']); ?>" required><br><br>

    <label>Количество:</label>
    <input type="number" name="stock" value="<?= $product['stock']; ?>" required><br><br>

    <button type="submit">Сохранить</button>
</form>

</body>
</html>
