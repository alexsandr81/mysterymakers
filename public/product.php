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
?>

<main>
    <div class="product-page">
        <div class="gallery">
            <img id="mainImage" src="/mysterymakers/assets/<?= $product['image']; ?>" alt="<?= $product['name']; ?>">
            <div class="thumbnails">
                <img src="/mysterymakers/assets/<?= $product['image']; ?>" onclick="changeImage(this)">
                <img src="/mysterymakers/assets/sample1.jpg" onclick="changeImage(this)">
                <img src="/mysterymakers/assets/sample2.jpg" onclick="changeImage(this)">
            </div>
        </div>

        <div class="details">
            <h1><?= $product['name']; ?></h1>
            <p class="price"><?= number_format($product['price'], 2, '.', ''); ?> ₽</p>
            <p class="stock"><?= $product['stock'] > 0 ? 'В наличии' : 'Нет в наличии'; ?></p>

            <button class="btn-cart" onclick="addToCart(<?= $product['id']; ?>)">🛒 Добавить в корзину</button>

            <button class="btn-fav">❤️ В избранное</button>

            <h3>Описание</h3>
            <p><?= $product['description']; ?></p>

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
