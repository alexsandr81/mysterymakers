<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Товар не найден!");
}

$current_images = json_decode($product['images'], true) ?? [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);

    // Обновляем изображения (если загружены новые)
    if (!empty($_FILES['images']['name'][0])) {
        $image_paths = [];
        $upload_dir = '../assets/products/';

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['size'][$key] > 0) {
                $file_name = time() . "_" . basename($_FILES['images']['name'][$key]);
                $file_path = $upload_dir . $file_name;
                move_uploaded_file($tmp_name, $file_path);
                $image_paths[] = str_replace('../', '', $file_path);
            }
        }

        $images_json = json_encode($image_paths);
    } else {
        $images_json = $product['images'];
    }

    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=?, images=? WHERE id=?");
    $stmt->execute([$name, $description, $price, $category, $stock, $images_json, $id]);

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

<form method="POST" enctype="multipart/form-data">
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

    <label>Текущие изображения:</label><br>
    <?php foreach ($current_images as $img): ?>
        <img src="/mysterymakers/<?= $img; ?>" width="100"><br>
    <?php endforeach; ?>

    <label>Новые изображения (заменят текущие):</label>
    <input type="file" name="images[]" multiple accept="image/*"><br><br>

    <button type="submit">Сохранить</button>
</form>

</body>
</html>
