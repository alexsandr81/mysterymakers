<?php
require_once '../database/db.php';

// Загружаем все категории
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Проверяем, выбрана ли категория или подкатегория
$category_slug = $_GET['category'] ?? '';
$subcategory_id = $_GET['subcategory'] ?? '';
$category = null;
$subcategories = [];

if ($category_slug) {
    // Получаем категорию по slug
    $stmt = $conn->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$category_slug]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        // Загружаем подкатегории
        $stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ?");
        $stmt->execute([$category['id']]);
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Устанавливаем SEO-данные
$seo_title = $category ? htmlspecialchars($category['seo_title']) : 'Каталог товаров';
$seo_description = $category ? htmlspecialchars($category['seo_description']) : 'Все товары нашего магазина';
$seo_keywords = $category ? htmlspecialchars($category['seo_keywords']) : 'каталог, товары, купить';

// Фильтрация товаров
$query = "SELECT * FROM products WHERE status = 1"; // Только активные товары
$params = [];

if ($category) {
    $query .= " AND category = ?";
    $params[] = $category['id'];
}

if ($subcategory_id) {
    $query .= " AND subcategory = ?";
    $params[] = $subcategory_id;
}


// Фильтрация по цене
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
if ($min_price) {
    $query .= " AND price >= ?";
    $params[] = $min_price;
}
if ($max_price) {
    $query .= " AND price <= ?";
    $params[] = $max_price;
}

// Сортировка
$sort_by = $_GET['sort_by'] ?? 'created_at DESC';
$sort_options = [
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'popular' => 'views DESC'
];
$query .= " ORDER BY " . ($sort_options[$sort_by] ?? 'created_at DESC');

// Постраничная навигация
$limit = 12;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

$query .= " LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $seo_title; ?></title>
    <meta name="description" content="<?= $seo_description; ?>">
    <meta name="keywords" content="<?= $seo_keywords; ?>">
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="container">
    <!-- Боковое меню категорий -->
    <!-- Боковое меню категорий -->
<aside class="sidebar">
    <h2>Категории</h2>
    <ul class="category-menu"><li><a href="catalog.php">Все товары</a></li>
        <?php foreach ($categories as $cat): ?>
            <li class="category-item">
                <a href="catalog.php?category=<?= htmlspecialchars($cat['slug']); ?>">
                    <?= htmlspecialchars($cat['name']); ?>
                </a>
                <?php
                // Загружаем подкатегории
                $stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ?");
                $stmt->execute([$cat['id']]);
                $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php if (!empty($subcategories)): ?>
                    <ul class="subcategory-menu">
                        <?php foreach ($subcategories as $sub): ?>
                            <li>
                                <a href="catalog.php?category=<?= htmlspecialchars($cat['slug']); ?>&subcategory=<?= htmlspecialchars($sub['id']); ?>">
                                    <?= htmlspecialchars($sub['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>


    <!-- Основной контент -->
    <main>
        <h1><?= $category ? htmlspecialchars($category['name']) : 'Каталог товаров'; ?></h1>

        <!-- Фильтры -->
        

        <!-- Сетка товаров -->
        <div class="products">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product.php?id=<?= $product['id']; ?>">
                            <img src="../<?= json_decode($product['images'], true)[0]; ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                        </a>
                        <h3><?= htmlspecialchars($product['name']); ?></h3>
                        <a href="product.php?id=<?= $product['id']; ?>">Подробнее</a>
                        <p>Цена: <?= number_format($product['price'], 2, '.', ''); ?> ₽</p>
                        <button onclick="addToCart(<?= $product['id']; ?>)">🛒 В корзину</button>
                        <button onclick="addToFavorites(<?= $product['id']; ?>)">❤️ В избранное</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Нет товаров в этой категории.</p>
            <?php endif; ?>
        </div>

        <!-- Постраничная навигация -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="catalog.php?page=<?= $page - 1; ?>">⬅ Назад</a>
            <?php endif; ?>
            <a href="catalog.php?page=<?= $page + 1; ?>">Вперёд ➡</a>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>

<script>
function addToCart(productId) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + productId
    }).then(response => response.text())
      .then(data => alert("Товар добавлен в корзину!"))
      .catch(error => console.error('Ошибка:', error));
}

function addToFavorites(productId) {
    fetch('add_to_favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + productId
    }).then(response => response.text())
      .then(data => alert("Товар добавлен в избранное!"))
      .catch(error => console.error('Ошибка:', error));
}
</script>

</body>
</html>
