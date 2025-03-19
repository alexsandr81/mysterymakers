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
$names = $conn->query("SELECT DISTINCT name FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Фильтры
$category_filter = $_GET['category'] ?? '';
$subcategory_filter = $_GET['subcategory'] ?? '';
$size_filter = $_GET['size'] ?? '';
$material_filter = $_GET['material'] ?? '';
$sku_filter = $_GET['sku'] ?? '';
$name_filter = $_GET['name_filter'] ?? '';
$name_sort = $_GET['name_sort'] ?? '';
$stock_sort = $_GET['stock_sort'] ?? '';
$sku_sort = $_GET['sku_sort'] ?? '';
$date_sort = $_GET['date_sort'] ?? '';

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
if ($name_filter) {
    $query .= " AND p.name = ?";
    $params[] = $name_filter;
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
if ($date_sort) {
    $sort_options[] = "p.created_at $date_sort";
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
    <script>
    function updateStock(productId, newStock) {
        fetch('update_stock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + productId + '&stock=' + newStock
        }).then(response => response.text())
          .then(data => console.log(data))
          .catch(error => console.error('Ошибка:', error));
    }

    function filterProducts() {
        let params = new URLSearchParams(window.location.search);
        document.querySelectorAll("select, input").forEach(input => {
            params.set(input.name, input.value);
        });
        window.location.search = params.toString();
    }
    </script>
</head>
<body>

<h2>Товары</h2>
<a href="add_product.php">➕ Добавить товар</a>
<a href="export_products.php">📥 Экспорт в CSV</a>

<table border="1">
    <tr>
        <th>ID</th>
        <th>
            Артикул<br>
            <select name="sku_sort" onchange="filterProducts()">
                <option value="">Без сортировки</option>
                <option value="ASC" <?= ($sku_sort == 'ASC') ? 'selected' : ''; ?>>По возрастанию</option>
                <option value="DESC" <?= ($sku_sort == 'DESC') ? 'selected' : ''; ?>>По убыванию</option>
            </select>
        </th>
        <th>Изображение</th>
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
            Количество<br>
            <select name="stock_sort" onchange="filterProducts()">
                <option value="">Без сортировки</option>
                <option value="ASC" <?= ($stock_sort == 'ASC') ? 'selected' : ''; ?>>По возрастанию</option>
                <option value="DESC" <?= ($stock_sort == 'DESC') ? 'selected' : ''; ?>>По убыванию</option>
            </select>
        </th>
        <th>
            Дата добавления<br>
            <select name="date_sort" onchange="filterProducts()">
                <option value="">Без сортировки</option>
                <option value="ASC" <?= ($date_sort == 'ASC') ? 'selected' : ''; ?>>Старые сначала</option>
                <option value="DESC" <?= ($date_sort == 'DESC') ? 'selected' : ''; ?>>Новые сначала</option>
            </select>
        </th>
        <th>Действия</th>
    </tr>

    <?php foreach ($products as $product): ?>
    <tr <?= ($product['stock'] < 5) ? 'style="background-color: #ffcccc;"' : ''; ?>>
        <td><?= $product['id']; ?></td>
        <td><?= htmlspecialchars($product['sku']); ?></td>
        <td>
            <?php 
            $images = json_decode($product['images'], true);
            if (!empty($images)) {
                echo '<img src="/mysterymakers/' . $images[0] . '" width="50">';
            }
            ?>
        </td>
        <td><?= htmlspecialchars($product['name']); ?></td>
        <td><?= htmlspecialchars($product['category_name']); ?></td>
        <td><?= htmlspecialchars($product['subcategory_name']); ?></td>
        <td><?= htmlspecialchars($product['size_name']); ?></td>
        <td><?= htmlspecialchars($product['material_name']); ?></td>
        
        <td>
            <input type="number" value="<?= $product['stock']; ?>" 
       style="width: 60px;" 
                   onchange="updateStock(<?= $product['id']; ?>, this.value)">
        </td>
        <td><?= $product['created_at']; ?></td>
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
