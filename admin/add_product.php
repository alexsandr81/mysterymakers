<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);

    // Директория для загрузки изображений
    $upload_dir = __DIR__ . '/../assets/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Создаём папку, если её нет
    }

    // Массив для хранения путей к загруженным изображениям
    $image_paths = [];

    // Проверяем, что загружено не более 5 файлов
    if (count($_FILES['images']['name']) > 5) {
        die("Можно загрузить максимум 5 изображений!");
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['size'][$key] > 0) {
            // Проверяем MIME-тип файла
            $file_info = getimagesize($tmp_name);
            if (!$file_info) {
                die("Файл {$_FILES['images']['name'][$key]} не является изображением.");
            }

            $file_ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_ext, $allowed_ext)) {
                die("Недопустимый формат файла: {$_FILES['images']['name'][$key]}");
            }

            // Хешируем имя файла
            $file_name = md5(uniqid(rand(), true)) . "." . $file_ext;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($tmp_name, $file_path)) {
                $image_paths[] = 'assets/products/' . $file_name; // Путь для хранения в БД
            } else {
                die("Ошибка загрузки файла: {$_FILES['images']['name'][$key]}");
            }
        }
    }

    // Сохраняем изображения в JSON-формате
    $images_json = json_encode($image_paths, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    // Добавляем товар в базу данных
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock, images) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $category, $stock, $images_json]);

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

<form method="POST" enctype="multipart/form-data">
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

    <label>Изображения (до 5 файлов):</label>
    <input type="file" name="images[]" multiple accept="image/*" required><br><br>

    <button type="submit">Добавить</button>
</form>

</body>
</html>
