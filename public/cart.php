<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>

<main>
    <h1>Корзина</h1>

    <?php
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        echo "<p>Ваша корзина пуста.</p>";
    } else {
        $ids = implode(',', array_keys($cart));
        $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table>
        <tr>
            <th>Товар</th>
            <th>Цена</th>
            <th>Количество</th>
            <th>Сумма</th>
            <th>Действия</th>
        </tr>

        <?php $total = 0; ?>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= $product['name']; ?></td>
                <td><?= number_format($product['price'], 2, '.', ''); ?> ₽</td>
                <td><?= $cart[$product['id']]; ?></td>
                <td><?= number_format($product['price'] * $cart[$product['id']], 2, '.', ''); ?> ₽</td>
                <td>
                    <button onclick="removeFromCart(<?= $product['id']; ?>)">❌</button>
                </td>
            </tr>
            <?php $total += $product['price'] * $cart[$product['id']]; ?>
        <?php endforeach; ?>

    </table>

    <p>Итого: <strong><?= number_format($total, 2, '.', ''); ?> ₽</strong></p>
    <a href="checkout.php" class="btn-checkout">Оформить заказ</a>

    <?php } ?>
</main>

<script>
function removeFromCart(productId) {
    fetch('/mysterymakers/public/remove_from_cart.php', {
        method: 'POST',
        body: new URLSearchParams({ id: productId }),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(() => location.reload());
}
</script>

<?php include 'footer.php'; ?>
