<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>

<?php
// Получаем ID товара из URL
$id = $_GET['id'] ?? 0;

// Загружаем информацию о товаре
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<h1>Товар не найден</h1>";
    include 'footer.php';
    exit;
}

// Получаем изображения товара
$images = json_decode($product['images'], true) ?? [];
$images = array_map(fn($img) => str_replace('\\', '/', $img), $images);
?>

<main>
    <div class="product-page">
        <div class="gallery">
            <?php if (!empty($images)): ?>
                <img id="mainImage" src="/mysterymakers/<?= htmlspecialchars($images[0]); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                <div class="thumbnails">
                    <?php foreach ($images as $img): ?>
                        <img src="/mysterymakers/<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($product['name']); ?>" onclick="changeImage(this)">
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Изображения отсутствуют</p>
            <?php endif; ?>
        </div>

        <div class="details">
            <h1><?= htmlspecialchars($product['name']); ?></h1>
            <p class="price"><?= number_format($product['price'], 2, '.', ''); ?> ₽</p>
            <p class="stock"><?= $product['stock'] > 0 ? 'В наличии' : 'Нет в наличии'; ?></p>

            <button class="btn-cart" onclick="addToCart(<?= $product['id']; ?>)">🛒 Добавить в корзину</button>
            <button class="btn-fav">❤️ В избранное</button>

            <h3>Описание</h3>
            <p><?= nl2br(htmlspecialchars($product['description'])); ?></p>

            <h3>Отзывы</h3>
            <div class="reviews">
                <p>⭐ ⭐ ⭐ ⭐ ⭐ "Отличный товар!"</p>
            </div>
        </div>
    </div>
</main>

<script>
function changeImage(img) {
    document.getElementById('mainImage').src = img.src;
}

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
