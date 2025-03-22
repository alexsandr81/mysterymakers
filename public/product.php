<?php 
include 'header.php'; // Подключение файла header.php
require_once '../database/db.php'; // Подключение файла с подключением к базе данных

// Получение ID товара из URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<h1>Ошибка: ID товара не указан</h1>"; // Вывод сообщения об ошибке, если ID товара не указан
    include 'footer.php'; // Подключение файла footer.php
    exit;
}

$id = $_GET['id'] ?? 0; // Получение ID товара из URL
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?"); // Подготовка SQL-запроса для выборки товара по указанному ID
$stmt->execute([$id]); // Выполнение SQL-запроса с параметром ID товара
$product = $stmt->fetch(PDO::FETCH_ASSOC); // Получение деталей товара в виде ассоциативного массива
$related_products = []; // Инициализация пустого массива для связанных товаров

if ($product) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE category = ? AND id != ? ORDER BY RAND() LIMIT 4"); // Подготовка SQL-запроса для выборки связанных товаров на основе категории текущего товара
    $stmt->execute([$product['category'], $product['id']]); // Выполнение SQL-запроса с параметрами категории и ID текущего товара
    $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC); // Получение связанных товаров в виде ассоциативного массива
}

if (!$product) {
    die("Товар не найден!"); // Вывод сообщения об ошибке, если товар не найден
}

// Данные для SEO
$seo_title = !empty($product['seo_title']) ? $product['seo_title'] : $product['name']; // Установка SEO-заголовка товара на основе указанного SEO-заголовка или названия товара
$seo_description = !empty($product['seo_description']) ? $product['seo_description'] : substr($product['description'], 0, 150); // Установка SEO-описания товара на основе указанного SEO-описания или подстроки описания товара
$seo_keywords = !empty($product['seo_keywords']) ? $product['seo_keywords'] : str_replace(' ', ',', $product['name']); // Установка SEO-ключевых слов товара на основе указанных SEO-ключевых слов или запятой-разделенного списка названия товара

if (!$product) {
    echo "<h1>Товар не найден</h1>"; // Вывод сообщения об ошибке, если товар не найден
    include 'footer.php'; // Подключение файла footer.php
    exit;
}

// Получение изображений товара
$images = json_decode($product['images'], true); // Декодирование JSON-строки изображений в ассоциативный массив
if (!is_array($images)) {
    $images = []; // Если изображения не являются массивом, установка пустого массива
}

// Получение среднего рейтинга и отзывов
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?"); // Подготовка SQL-запроса для вычисления среднего рейтинга товара
$stmt->execute([$product['id']]); // Выполнение SQL-запроса с параметром ID товара
$rating = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'], 1); // Получение среднего рейтинга и округление до одного десятичного знака

$stmt = $conn->prepare("SELECT r.*, u.name FROM reviews r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.product_id = ? ORDER BY r.created_at DESC"); // Подготовка SQL-запроса для выборки отзывов о товаре
$stmt->execute([$product['id']]); // Выполнение SQL-запроса с параметром ID товара
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC); // Получение отзывов в виде ассоциативного массива
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seo_title); ?></title>
    <meta name="description" content="<?= htmlspecialchars($seo_description); ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seo_keywords); ?>">
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
            <p class="price"><?= number_format($product['price'], 2, '.', ''); ?> ₽</p>
            <p class="stock"><?= ($product['stock'] > 0) ? '✅ В наличии' : '❌ Нет в наличии'; ?></p>

            <!-- Рейтинг товара -->
            <h3>Рейтинг: <?= ($rating > 0) ? "$rating ⭐" : "Нет отзывов"; ?></h3>

            <!-- Кнопки покупки -->
            <button class="btn-cart" onclick="addToCart(<?= $product['id']; ?>)">🛒 Добавить в корзину</button>
            <button class="btn-buy">⚡ Купить в 1 клик</button>
            <button class="btn-fav">❤️ В избранное</button>

            <h3>Описание</h3>
            <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>

            <!-- Секция отзывов -->
            <h3>Отзывы</h3>
            <?php if (!empty($reviews)): ?>
                <div class="reviews">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review">
                            <p><strong><?= htmlspecialchars($review['name']); ?></strong> ⭐<?= $review['rating']; ?>/5</p>
                            <p><?= nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php if ($review['image']): ?>
                                <img src="/mysterymakers/<?= htmlspecialchars($review['image']); ?>" width="100">
                            <?php endif; ?>
                            <hr>
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
document.addEventListener("DOMContentLoaded", function () {
    const mainImage = document.getElementById("mainImage");
    mainImage.addEventListener("mousemove", function (e) {
        let width = mainImage.clientWidth;
        let height = mainImage.clientHeight;
        let x = (e.offsetX / width) * 100;
        let y = (e.offsetY / height) * 100;
        mainImage.style.transformOrigin = x + "% " + y + "%";
        mainImage.style.transform = "scale(2)";
    });

    mainImage.addEventListener("mouseleave", function () {
        mainImage.style.transform = "scale(1)";
    });
});

// Добавление в корзину
function addToCart(productId) {
    fetch('/mysterymakers/public/add_to_cart.php', {
        method: 'POST',
        body: new URLSearchParams({ id: productId }),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        alert('Товар добавлен в корзину! Количество товаров: ' + data.cart_count);
    });
}
</script>

<?php include 'footer.php'; ?> 
