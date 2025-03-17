<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>

<main>
    <h1>Оформление заказа</h1>

    <?php
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        echo "<p>Корзина пуста. <a href='catalog.php'>Перейти в каталог</a></p>";
        include 'footer.php';
        exit;
    }

    $ids = implode(',', array_keys($cart));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = 0;
    ?>

    <form action="process_order.php" method="POST">
        <label>ФИО:</label>
        <input type="text" name="name" required>

        <label>Телефон:</label>
        <input type="text" name="phone" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Адрес доставки:</label>
        <textarea name="address" required></textarea>

        <h3>Ваш заказ:</h3>
        <ul>
            <?php foreach ($products as $product): ?>
                <li><?= $product['name']; ?> (<?= $cart[$product['id']]; ?> шт.) - 
                    <?= number_format($product['price'] * $cart[$product['id']], 2, '.', ''); ?> ₽</li>
                <?php $total += $product['price'] * $cart[$product['id']]; ?>
            <?php endforeach; ?>
        </ul>

        <p><strong>Итого: <?= number_format($total, 2, '.', ''); ?> ₽</strong></p>

        <input type="hidden" name="total_price" value="<?= $total; ?>">

        <button type="submit">Оформить заказ</button>
    </form>
</main>

<?php include 'footer.php'; ?>
