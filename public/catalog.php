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
$query = "SELECT p.*, 
                 COALESCE(
           (SELECT MAX(d.discount_value) FROM discounts d 
            WHERE d.product_id = p.id 
              AND (d.start_date IS NULL OR d.start_date <= NOW()) 
              AND (d.end_date IS NULL OR d.end_date >= NOW())), 
           (SELECT MAX(d.discount_value) FROM discounts d 
            WHERE d.category_id = p.category_id 
              AND (d.start_date IS NULL OR d.start_date <= NOW()) 
              AND (d.end_date IS NULL OR d.end_date >= NOW()))
       ) AS discount_value,
       (SELECT d.discount_type FROM discounts d 
        WHERE (d.product_id = p.id OR d.category_id = p.category_id) 
          AND (d.start_date IS NULL OR d.start_date <= NOW()) 
          AND (d.end_date IS NULL OR d.end_date >= NOW())
        ORDER BY d.discount_value DESC LIMIT 1) AS discount_type,
       (SELECT d.end_date FROM discounts d 
        WHERE (d.product_id = p.id OR d.category_id = p.category_id) 
          AND (d.start_date IS NULL OR d.start_date <= NOW()) 
          AND (d.end_date IS NULL OR d.end_date >= NOW())
        ORDER BY d.discount_value DESC LIMIT 1) AS end_date
          FROM products p
          WHERE p.status = 1";
$params = [];

if ($category) {
    $query .= " AND p.category_id = ?";
    $params[] = $category['id'];
}

if ($subcategory_id) {
    $query .= " AND p.subcategory = ?";
    $params[] = $subcategory_id;
}

// Фильтрация по цене
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
if ($min_price) {
    $query .= " AND p.price >= ?";
    $params[] = $min_price;
}
if ($max_price) {
    $query .= " AND p.price <= ?";
    $params[] = $max_price;
}

// Сортировка
$sort_by = $_GET['sort_by'] ?? 'created_at_desc';
$sort_options = [
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular' => 'p.views DESC',
    'created_at_desc' => 'p.created_at DESC'
];
$query .= " ORDER BY " . ($sort_options[$sort_by] ?? $sort_options['created_at_desc']);

// Постраничная навигация
$limit = 12;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
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
        <aside class="sidebar">
            <h2>Категории</h2>
            <ul class="category-menu">
                <li><a href="catalog.php">Все товары</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li class="category-item">
                        <a href="catalog.php?category=<?= htmlspecialchars($cat['slug']); ?>">
                            <?= htmlspecialchars($cat['name']); ?>
                        </a>
                        <?php
                        // Загружаем подкатегории
                        $stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ?");
                        $stmt->execute([$cat['id']]);
                        $cat_subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if (!empty($cat_subcategories)): ?>
                            <ul class="subcategory-menu">
                                <?php foreach ($cat_subcategories as $sub): ?>
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

            <!-- Сетка товаров -->
            <div class="products">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $original_price = $product['price'];
                        $discount_price = $original_price;

                        if ($product['discount_type']) {
                            if ($product['discount_type'] == 'fixed') {
                                $discount_price = max(0, $original_price - $product['discount_value']);
                            } else {
                                $discount_price = max(0, $original_price * (1 - $product['discount_value'] / 100));
                            }
                        }

                        $images = json_decode($product['images'], true);
                        $image_src = (!empty($images) && isset($images[0])) ? "../" . $images[0] : '../assets/no-image.jpg';
                        ?>
                        <div class="product-card">
                            <a href="product.php?id=<?= $product['id']; ?>">
                                <img src="<?= $image_src; ?>" alt="<?= htmlspecialchars($product['name']); ?>" width="200">
                            </a>
                            <h3><?= htmlspecialchars($product['name']); ?></h3>
                            <a href="product.php?id=<?= $product['id']; ?>">Подробнее</a>

                            <?php
                            $original_price = $product['price'];
                            $discount_value = $product['discount_value'] ?? 0;
                            $discount_price = $original_price;

                            if ($discount_value) {
                                if ($product['discount_type'] == 'fixed') {
                                    $discount_price = max(0, $original_price - $discount_value);
                                } elseif ($product['discount_type'] == 'percentage') {
                                    $discount_price = $original_price * (1 - $discount_value / 100);
                                }
                            }
                            ?>

                            <?php if ($discount_value): ?>
                                <p class="old-price"><s><?= number_format($original_price, 2, '.', ''); ?> грн.</s></p>
                                <p class="discount-price"><?= number_format($discount_price, 2, '.', ''); ?> грн.</p>
                                <p class="discount-info">
                                    Скидка <?= ($product['discount_type'] == 'fixed') ? $product['discount_value'] . ' грн.' : $product['discount_value'] . '%'; ?>
                                    <?php if ($product['end_date']): ?> (до <?= date('d.m.Y H:i', strtotime($product['end_date'])); ?>) <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <p class="price"><?= number_format($original_price, 2, '.', ''); ?> грн.</p>
                            <?php endif; ?>

                            <button onclick="addToCart(<?= $product['id']; ?>)">🛒 В корзину</button>
                            <form method="POST" action="add_to_favorites.php">
                                <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                <button type="submit">❤️ В избранное</button>
                            </form>
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
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const countEl = document.getElementById('cart-count');
            if (countEl) countEl.textContent = data.cart_count;
            alert("Товар добавлен в корзину!");
        } else {
            alert("Ошибка добавления");
        }
    })
    .catch(error => console.error('Ошибка:', error));
}


        function addToFavorites(productId) {
            fetch('add_to_favorites.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + productId
                }).then(response => response.text())
                .then(data => alert("Товар добавлен в избранное!"))
                .catch(error => console.error('Ошибка:', error));
        }
    </script>

</body>

</html>