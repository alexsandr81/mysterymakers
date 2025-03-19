<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем ID подкатегории
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM subcategories WHERE id = ?");
$stmt->execute([$id]);
$subcategory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subcategory) {
    die("Подкатегория не найдена!");
}

// Получаем категории
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Обновляем подкатегорию
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'], $_POST['category_id'])) {
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);

    // Проверяем дублирование
    $check_stmt = $conn->prepare("SELECT id FROM subcategories WHERE name = ? AND category_id = ? AND id != ?");
    $check_stmt->execute([$name, $category_id, $id]);
    if ($check_stmt->rowCount() > 0) {
        die("Ошибка: Такая подкатегория уже существует в этой категории!");
    }

    $stmt = $conn->prepare("UPDATE subcategories SET name = ?, category_id = ? WHERE id = ?");
    $stmt->execute([$name, $category_id, $id]);

    header("Location: subcategories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать подкатегорию</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Редактировать подкатегорию</h2>

<form method="POST">
    <label>Категория:</label>
    <select name="category_id" required>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id']; ?>" <?= ($subcategory['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Название подкатегории:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($subcategory['name']); ?>" required>
    <button type="submit">Сохранить</button>
</form>

</body>
</html>
