<?php 
include 'header.php'; 
require_once '../database/db.php'; 

// Получаем ID товара из URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<h1>Ошибка: ID товара не указан</h1>";
    include 'footer.php';
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$product) {
    die("Товар не найден!");
}

// SEO-данные
$seo_title = !empty($product['seo_title']) ? $product['seo_title'] : $product['name'];
$seo_description = !empty($product['seo_description']) ? $product['seo_description'] : substr($product['description'], 0, 150);
$seo_keywords = !empty($product['seo_keywords']) ? $product['seo_keywords'] : str_replace(' ', ',', $product['name']);


if (!$product) {
    echo "<h1>Товар не найден</h1>";
    include 'footer.php';
    exit;
}

// Получаем изображения товара
$images = json_decode($product['images'], true);
if (!is_array($images)) {
    $images = [];
}

// Получаем средний рейтинг и отзывы
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?");
$stmt->execute([$product['id']]);
$rating = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'], 1);

$stmt = $conn->prepare("SELECT r.*, u.name FROM reviews r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        <!-- Описание товара -->
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

            <!-- Раздел отзывов -->
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
</main>

<!-- Скрипты -->
<script>
function changeImage(img) {
    document.getElementById('mainImage').src = img.src;
}

// Зум-эффект
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
