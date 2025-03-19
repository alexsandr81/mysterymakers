<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем категории, подкатегории, размеры, материалы
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sizes = $conn->query("SELECT * FROM sizes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$materials = $conn->query("SELECT * FROM materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Получаем все подкатегории
$subcategories = [];
if (!empty($categories)) {
    $stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ?");
    $stmt->execute([$categories[0]['id']]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Добавление товара
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

    // Проверка на выбор подкатегории
    if (!$subcategory_id) {
        die("Ошибка: Выберите подкатегорию!");
    }

    // Загрузка изображений
    $upload_dir = __DIR__ . '/../assets/products/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $image_paths = [];
    if (count($_FILES['images']['name']) > 5) {
        die("Можно загрузить максимум 5 изображений!");
    }

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['size'][$key] > 0) {
            $file_ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_ext, $allowed_ext)) {
                die("Недопустимый формат файла: {$_FILES['images']['name'][$key]}");
            }

            $file_name = md5(uniqid(rand(), true)) . "." . $file_ext;
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($tmp_name, $file_path)) {
                $image_paths[] = 'assets/products/' . $file_name;
            } else {
                die("Ошибка загрузки файла: {$_FILES['images']['name'][$key]}");
            }
        }
    }

    $images_json = json_encode($image_paths, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    // Добавляем товар в базу
    $stmt = $conn->prepare("INSERT INTO products 
        (name, description, price, category, subcategory, size, material, sku, stock, images) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $category_id, $subcategory_id, $size_id, $material_id, $sku, $stock, $images_json]);

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

    <label></label>
    <select name="category" id="category" required>
        <option value="">Категория:</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <a href="categories.php">➕</a>
    <br><br>

    <label></label>
    <select name="subcategory" id="subcategory" required>
        <option value="">Подкатегория:</option>
        <?php foreach ($subcategories as $sub): ?>
            <option value="<?= $sub['id']; ?>"><?= htmlspecialchars($sub['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <a href="subcategories.php">➕</a>
    <br><br>

    <label></label>
    <select name="size" required>
        <option value="">Размер:</option>
        <?php foreach ($sizes as $size): ?>
            <option value="<?= $size['id']; ?>"><?= htmlspecialchars($size['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <a href="sizes.php">➕</a>
    <br><br>

    <label></label>
    <select name="material" required>
        <option value="">материал:</option>
        <?php foreach ($materials as $material): ?>
            <option value="<?= $material['id']; ?>"><?= htmlspecialchars($material['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <a href="materials.php">➕</a>
    <br><br>

    <label>Артикул:</label>
    <input type="text" name="sku" required><br><br>

    <label>Количество:</label>
    <input type="number" name="stock" required><br><br>

    <label>Изображения (до 5 файлов):</label>
    <input type="file" name="images[]" multiple accept="image/*" required><br><br>

    <button type="submit">Добавить</button>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let categorySelect = document.getElementById("category");
    let subcategorySelect = document.getElementById("subcategory");

    function loadSubcategories(categoryId) {
        fetch("get_subcategories.php?category_id=" + categoryId)
            .then(response => response.json())
            .then(data => {
                subcategorySelect.innerHTML = "<option value=''>Выберите подкатегорию</option>";
                data.forEach(sub => {
                    subcategorySelect.innerHTML += `<option value="${sub.id}">${sub.name}</option>`;
                });
            });
    }

    categorySelect.addEventListener("change", function() {
        let categoryId = this.value;
        if (categoryId) {
            loadSubcategories(categoryId);
        }
    });

    // Загружаем подкатегории для первой категории
    if (categorySelect.value) {
        loadSubcategories(categorySelect.value);
    }
});
</script>

</body>
</html>
