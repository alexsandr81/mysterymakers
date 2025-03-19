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
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Товар не найден!");
}

// Получаем списки категорий, подкатегорий, размеров и материалов
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $conn->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sizes = $conn->query("SELECT * FROM sizes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$materials = $conn->query("SELECT * FROM materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Обрабатываем обновление товара
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category']);
    $subcategory_id = intval($_POST['subcategory']);
    $size_id = intval($_POST['size']);
    $material_id = intval($_POST['material']);
    $sku = trim($_POST['sku']);
    $stock = intval($_POST['stock']);

    // Обновляем данные в БД
    $stmt = $conn->prepare("UPDATE products 
        SET name=?, description=?, price=?, category=?, subcategory=?, size=?, material=?, sku=?, stock=? 
        WHERE id=?");
    $stmt->execute([$name, $description, $price, $category_id, $subcategory_id, $size_id, $material_id, $sku, $stock, $id]);

    header("Location: products.php");
    exit();
}

// Получаем изображения товара
$current_images = json_decode($product['images'], true) ?? [];
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
    <select name="category" required>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id']; ?>" <?= ($product['category'] == $cat['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($cat['name']); ?>
            </option>
        <?php endforeach; ?>
    </select><a href="categories.php">➕</a><br><br>

    <label>Подкатегория:</label>
    <select name="subcategory" required>
        <?php foreach ($subcategories as $sub): ?>
            <option value="<?= $sub['id']; ?>" <?= ($product['subcategory'] == $sub['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($sub['name']); ?>
            </option>
        <?php endforeach; ?>
    </select><a href="subcategories.php">➕</a><br><br>

    <label>Размер:</label>
    <select name="size" required>
        <?php foreach ($sizes as $size): ?>
            <option value="<?= $size['id']; ?>" <?= ($product['size'] == $size['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($size['name']); ?>
            </option>
        <?php endforeach; ?>
    </select><a href="sizes.php">➕</a><br><br>

    <label>Материал:</label>
    <select name="material" required>
        <?php foreach ($materials as $material): ?>
            <option value="<?= $material['id']; ?>" <?= ($product['material'] == $material['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($material['name']); ?>
            </option>
        <?php endforeach; ?>
    </select><a href="materials.php">➕</a><br><br>

    <label>Артикул (SKU):</label>
    <input type="text" name="sku" value="<?= htmlspecialchars($product['sku']); ?>" required><br><br>

    <label>Количество:</label>
    <input type="number" name="stock" value="<?= $product['stock']; ?>" required><br><br>

    <h3>Изображения товара</h3>
    <?php foreach ($current_images as $img): ?>
        <div style="display: inline-block; margin-right: 10px; text-align: center;">
            <img src="/mysterymakers/<?= $img; ?>" width="100"><br>
            <a href="delete_image.php?product_id=<?= $id; ?>&image=<?= urlencode($img); ?>" 
               onclick="return confirm('Удалить изображение?');">🗑 Удалить</a>
        </div>
    <?php endforeach; ?>

    <h3>Добавить новые изображения (до 5 файлов)</h3>
    <input type="file" name="images[]" multiple accept="image/*"><br><br>

    <button type="submit">Сохранить изменения</button>
</form>

</body>
</html>
