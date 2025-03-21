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
    $seo_title = trim($_POST['seo_title']) ?: $name; // Если пусто, используем название категории
    $seo_description = trim($_POST['seo_description']) ?: "Описание категории $name";
    $seo_keywords = trim($_POST['seo_keywords']) ?: str_replace(' ', ',', $name);

    // Генерация slug (ЧПУ-ссылки)
    // Функция транслитерации кириллических символов в латиницу
function transliterate($text) {
    $replace = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];
    return strtr(mb_strtolower($text), $replace);
}

// Генерация slug (ЧПУ-ссылки)
$slug = transliterate($name);
$slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($slug));
$slug = trim($slug, '-');

// Проверка уникальности slug
$stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
if ($stmt->fetchColumn() > 0) {
    $slug .= '-' . time(); // Если slug уже есть, добавляем временную метку
}

    $slug = rtrim($slug, '-');

    // Проверка уникальности slug
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        $slug .= '-' . time(); // Если slug уже есть, добавляем временную метку
    }

    // Добавляем категорию в базу
    $stmt = $conn->prepare("INSERT INTO categories 
        (name, seo_title, seo_description, seo_keywords, slug) 
        VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $seo_title, $seo_description, $seo_keywords, $slug]);

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
    <input type="text" name="name" required><br><br>

    <label>SEO Title:</label>
    <input type="text" name="seo_title"><br><br>

    <label>SEO Description:</label>
    <textarea name="seo_description"></textarea><br><br>

    <label>SEO Keywords:</label>
    <input type="text" name="seo_keywords"><br><br>

    <button type="submit">Добавить</button>
</form>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Название</th>
        <th>SEO Title</th>
        <th>Slug</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($categories as $cat): ?>
    <tr>
        <td><?= $cat['id']; ?></td>
        <td><?= htmlspecialchars($cat['name']); ?></td>
        <td><?= htmlspecialchars($cat['seo_title']); ?></td>
        <td><?= htmlspecialchars($cat['slug']); ?></td>
        <td>
            <a href="edit_category.php?id=<?= $cat['id']; ?>">✏ Редактировать</a> | 
            <a href="delete_category.php?id=<?= $cat['id']; ?>" onclick="return confirm('Удалить категорию?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
