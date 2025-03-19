<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем данные для фильтров
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $conn->query("SELECT * FROM subcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$sizes = $conn->query("SELECT * FROM sizes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$materials = $conn->query("SELECT * FROM materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Фильтры
$category_filter = $_GET['category'] ?? '';
$subcategory_filter = $_GET['subcategory'] ?? '';
$size_filter = $_GET['size'] ?? '';
$material_filter = $_GET['material'] ?? '';
$sku_filter = $_GET['sku'] ?? '';
$name_sort = $_GET['name_sort'] ?? '';
$stock_sort = $_GET['stock_sort'] ?? '';
$sku_sort = $_GET['sku_sort'] ?? '';

// Формируем SQL-запрос
$query = "SELECT p.*, c.name AS category_name, s.name AS subcategory_name, sz.name AS size_name, m.name AS material_name 
          FROM products p
          LEFT JOIN categories c ON p.category = c.id
          LEFT JOIN subcategories s ON p.subcategory = s.id
          LEFT JOIN sizes sz ON p.size = sz.id
          LEFT JOIN materials m ON p.material = m.id
          WHERE 1";

$params = [];
if ($category_filter) {
    $query .= " AND p.category = ?";
    $params[] = $category_filter;
}
if ($subcategory_filter) {
    $query .= " AND p.subcategory = ?";
    $params[] = $subcategory_filter;
}
if ($size_filter) {
    $query .= " AND p.size = ?";
    $params[] = $size_filter;
}
if ($material_filter) {
    $query .= " AND p.material = ?";
    $params[] = $material_filter;
}
if ($sku_filter) {
    $query .= " AND p.sku LIKE ?";
    $params[] = "%$sku_filter%";
}

// Обработка сортировки
$sort_options = [];
if ($name_sort) {
    $sort_options[] = "p.name $name_sort";
}
if ($sku_sort) {
    $sort_options[] = "p.sku $sku_sort";
}
if ($stock_sort) {
    $sort_options[] = "p.stock $stock_sort";
}
if (!empty($sort_options)) {
    $query .= " ORDER BY " . implode(", ", $sort_options);
} else {
    $query .= " ORDER BY p.created_at DESC"; // По умолчанию - сортировка по дате
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
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
        <th>
            Название файла<br>
            <select name="name_sort" onchange="filterProducts()">
                <option value="">Без сортировки</option>
                <option value="ASC" <?= ($name_sort == 'ASC') ? 'selected' : ''; ?>>А → Я</option>
                <option value="DESC" <?= ($name_sort == 'DESC') ? 'selected' : ''; ?>>Я → А</option>
            </select>
        </th>
        <th>
            Категория<br>
            <select name="category" onchange="filterProducts()">
                <option value="">Все</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id']; ?>" <?= ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </th>
        <th>
            Подкатегория<br>
            <select name="subcategory" onchange="filterProducts()">
                <option value="">Все</option>
                <?php foreach ($subcategories as $sub): ?>
                    <option value="<?= $sub['id']; ?>" <?= ($subcategory_filter == $sub['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($sub['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </th>
        <th>
            Размер<br>
            <select name="size" onchange="filterProducts()">
                <option value="">Все</option>
                <?php foreach ($sizes as $size): ?>
                    <option value="<?= $size['id']; ?>" <?= ($size_filter == $size['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($size['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </th>
        <th>
            Материал<br>
            <select name="material" onchange="filterProducts()">
                <option value="">Все</option>
                <?php foreach ($materials as $material): ?>
                    <option value="<?= $material['id']; ?>" <?= ($material_filter == $material['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($material['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </th>
        <th>
            Артикул<br>
            <select name="sku_sort" onchange="filterProducts()">
                <option value="">Без сортировки</option>
                <option value="ASC" <?= ($sku_sort == 'ASC') ? 'selected' : ''; ?>>По возрастанию</option>
                <option value="DESC" <?= ($sku_sort == 'DESC') ? 'selected' : ''; ?>>По убыванию</option>
            </select>
        </th>
        <th>
            Количество<br>
            <select name="stock_sort" onchange="filterProducts()">
                <option value="">Без сортировки</option>
                <option value="ASC" <?= ($stock_sort == 'ASC') ? 'selected' : ''; ?>>По возрастанию</option>
                <option value="DESC" <?= ($stock_sort == 'DESC') ? 'selected' : ''; ?>>По убыванию</option>
            </select>
        </th>
        <th>Действия</th>
    </tr>

    <?php foreach ($products as $product): ?>
    <tr>
        <td><?= $product['id']; ?></td>
        <td><?= htmlspecialchars($product['name']); ?></td>
        <td><?= htmlspecialchars($product['category_name']); ?></td>
        <td><?= htmlspecialchars($product['subcategory_name']); ?></td>
        <td><?= htmlspecialchars($product['size_name']); ?></td>
        <td><?= htmlspecialchars($product['material_name']); ?></td>
        <td><?= htmlspecialchars($product['sku']); ?></td>
        <td><?= $product['stock']; ?></td>
        <td>
            <a href="edit_product.php?id=<?= $product['id']; ?>">✏ Редактировать</a>
            <a href="delete_product.php?id=<?= $product['id']; ?>" onclick="return confirm('Удалить товар?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<script>
function filterProducts() {
    let params = new URLSearchParams(window.location.search);

    document.querySelectorAll("select, input").forEach(input => {
        params.set(input.name, input.value);
    });

    window.location.search = params.toString();
}
</script>

</body>
</html>


        