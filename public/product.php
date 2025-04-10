<?php
include 'header.php'; // Подключение файла header.php
require_once '../database/db.php'; // Подключение файла с подключением к базе данных

// Получение ID товара из URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    echo "<h1>Ошибка: ID товара не указан</h1>";
    include 'footer.php';
    exit;
}

// Получаем товар с учётом скидок по товару и категории
$stmt = $conn->prepare("
    SELECT p.*, 
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
    WHERE p.id = ? AND p.status = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<h1>Товар не найден</h1>";
    include 'footer.php';
    exit;
}

// Рассчитываем скидочную цену
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

// Получаем связанные товары
$stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND status = 1 ORDER BY RAND() LIMIT 4");
$stmt->execute([$product['category_id'], $product['id']]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Данные для SEO
$seo_title = !empty($product['seo_title']) ? $product['seo_title'] : $product['name'];
$seo_description = !empty($product['seo_description']) ? $product['seo_description'] : substr($product['description'], 0, 150);
$seo_keywords = !empty($product['seo_keywords']) ? $product['seo_keywords'] : str_replace(' ', ',', $product['name']);

// Получение изображений товара
$images = json_decode($product['images'], true);
if (!is_array($images)) {
    $images = [];
}

// Получение среднего рейтинга и отзывов
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
$stmt->execute([$product['id']]);
$rating = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'], 1);

$stmt = $conn->prepare("
    SELECT r.*, u.name AS user_name 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seo_title); ?></title>
    <meta name="description" content="<?= htmlspecialchars($seo_description); ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seo_keywords); ?>">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main>
        <div class="product-page">
            <!-- Галерея изображений -->
            <div class="gallery">
                <?php if (!empty($images)): ?>
                    <div class="zoom-wrapper">
                        <img id="mainImage" src="/mysterymakers/<?= htmlspecialchars($images[0]); ?>"
                            alt="<?= htmlspecialchars($product['name']); ?>"
                            title="<?= htmlspecialchars($product['name']); ?>"
                            class="zoom" loading="lazy">
                    </div>
                    <div class="thumbnails">
                        <?php foreach ($images as $img): ?>
                            <img src="/mysterymakers/<?= htmlspecialchars($img); ?>"
                                alt="<?= htmlspecialchars($product['name']); ?>"
                                title="Посмотреть фото"
                                onclick="changeImage(this)" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Изображения отсутствуют</p>
                <?php endif; ?>
            </div>

            <!-- Детали товара -->
            <div class="details">
                <h1><?= htmlspecialchars($product['name']); ?></h1>
                <?php if ($discount_value): ?>
                    <p class="old-price"><s><?= number_format($original_price, 2, '.', ''); ?> ₽</s></p>
                    <p class="discount-price"><?= number_format($discount_price, 2, '.', ''); ?> ₽</p>
                    <p class="discount-info">
                        Скидка <?= ($product['discount_type'] == 'fixed') ? $product['discount_value'] . ' ₽' : $product['discount_value'] . '%'; ?>
                        <?php if ($product['end_date']): ?> (до <?= date('d.m.Y H:i', strtotime($product['end_date'])); ?>) <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="price"><?= number_format($original_price, 2, '.', ''); ?> ₽</p>
                <?php endif; ?>
                <p class="stock"><?= ($product['stock'] > 0) ? '✅ В наличии' : '❌ Нет в наличии'; ?></p>

                <!-- Рейтинг товара -->
                <h3>Рейтинг: <?= ($rating > 0) ? "$rating ⭐" : "Нет отзывов"; ?></h3>

                <!-- Кнопки покупки -->
                <button class="btn-cart" onclick="addToCart(<?= $product['id']; ?>)">🛒 Добавить в корзину</button>
                <button class="btn-buy">⚡ Купить в 1 клик</button>
                <form method="POST" action="add_to_favorites.php">
                    <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                    <button type="submit">❤️ В избранное</button>
                </form>

                <h3>Описание</h3>
                <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>

                <!-- Секция отзывов -->
                <h3>Отзывы</h3>
                <?php if (!empty($reviews)): ?>
                    <div class="reviews">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review">
                                <p><b><?= htmlspecialchars($review['user_name']); ?></b>: <?= htmlspecialchars($review['comment']); ?></p>
                                <p>Рейтинг: <?= $review['rating']; ?> ⭐</p>
                                <?php if ($review['admin_response']): ?>
                                    <p class="admin-response"><b>Ответ администратора:</b> <?= htmlspecialchars($review['admin_response']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Отзывов пока нет.</p>
                <?php endif; ?>

                <!-- Форма добавления отзыва -->
                <h3>Оставить отзыв</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="add_review.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                        <label>Оценка:</label>
                        <select name="rating" required>
                            <option value="5">⭐️⭐️⭐️⭐️⭐️</option>
                            <option value="4">⭐️⭐️⭐️⭐️</option>
                            <option value="3">⭐️⭐️⭐️</option>
                            <option value="2">⭐️⭐️</option>
                            <option value="1">⭐️</option>
                        </select><br><br>

                        <label>Отзыв:</label>
                        <textarea name="comment" required></textarea><br><br>

                        <label>Фото (необязательно):</label>
                        <input type="file" name="image" accept="image/*"><br><br>

                        <button type="submit">Отправить</button>
                    </form>
                <?php else: ?>
                    <p>Для добавления отзыва <a href="login.php">авторизуйтесь</a>.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Похожие товары -->
        <?php if (!empty($related_products)): ?>
            <h2>Похожие товары</h2>
            <div class="products-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="product-card">
                        <a href="product.php?slug=<?= htmlspecialchars($related['slug']); ?>">
                            <img src="../<?= json_decode($related['images'], true)[0]; ?>" alt="<?= htmlspecialchars($related['name']); ?>">
                        </a>
                        <h3><?= htmlspecialchars($related['name']); ?></h3>
                        <p>Цена: <?= number_format($related['price'], 2, '.', ''); ?> ₽</p>
                        <a href="product.php?slug=<?= htmlspecialchars($related['slug']); ?>" class="btn">Подробнее</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Скрипты -->
    <script>
        function changeImage(img) {
            document.getElementById('mainImage').src = img.src;
        }

        // Эффект увеличения
        document.addEventListener("DOMContentLoaded", function() {
            const mainImage = document.getElementById("mainImage");
            mainImage.addEventListener("mousemove", function(e) {
                let width = mainImage.clientWidth;
                let height = mainImage.clientHeight;
                let x = (e.offsetX / width) * 100;
                let y = (e.offsetY / height) * 100;
                mainImage.style.transformOrigin = x + "% " + y + "%";
                mainImage.style.transform = "scale(2)";
            });

            mainImage.addEventListener("mouseleave", function() {
                mainImage.style.transform = "scale(1)";
            });
        });

        // Добавление в корзину
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

    </script>

    <?php include 'footer.php'; ?>
</body>
</html>