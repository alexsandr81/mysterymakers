<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем ID категории
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    die("Категория не найдена!");
}

// Обновляем категорию
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $seo_title = trim($_POST['seo_title']);
    $seo_description = trim($_POST['seo_description']);
    $seo_keywords = trim($_POST['seo_keywords']);

    // Генерация slug (ЧПУ-ссылки)
    $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower(trim($name)));
    $slug = rtrim($slug, '-');

    // Проверяем дублирование
    $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $check_stmt->execute([$name, $id]);
    if ($check_stmt->rowCount() > 0) {
        die("Ошибка: Такая категория уже существует!");
    }

    // Обновляем категорию
    $stmt = $conn->prepare("UPDATE categories 
        SET name = ?, seo_title = ?, seo_description = ?, seo_keywords = ?, slug = ? 
        WHERE id = ?");
    $stmt->execute([$name, $seo_title, $seo_description, $seo_keywords, $slug, $id]);

    header("Location: categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать категорию</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Редактировать категорию</h2>

<form method="POST">
    <label>Название категории:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($category['name']); ?>" required><br><br>

    <label>SEO Title:</label>
    <input type="text" name="seo_title" value="<?= htmlspecialchars($category['seo_title']); ?>"><br><br>

    <label>SEO Description:</label>
    <textarea name="seo_description"><?= htmlspecialchars($category['seo_description']); ?></textarea><br><br>

    <label>SEO Keywords:</label>
    <input type="text" name="seo_keywords" value="<?= htmlspecialchars($category['seo_keywords']); ?>"><br><br>

    <button type="submit">Сохранить</button>
</form>

</body>
</html>
