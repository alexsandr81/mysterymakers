<?php 
include 'header.php'; 
include '../database/db.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = $_SESSION['cart'] ?? [];

?>

<main>
    <h1>Корзина</h1>

    <?php if (empty($cart)): ?>
        <p>Ваша корзина пуста.</p>
    <?php else: ?>
        <?php
        // Получаем товары из БД, если корзина не пуста
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
                    <td><?= htmlspecialchars($product['name']); ?></td>
                    <td><?= number_format($product['price'], 2, '.', ''); ?> ₽</td>
                    <td><?= (int)$cart[$product['id']]; ?></td>
                    <td><?= number_format($product['price'] * $cart[$product['id']], 2, '.', ''); ?> ₽</td>
                    <td>
                        <button onclick="removeFromCart(<?= (int)$product['id']; ?>)">❌</button>
                    </td>
                </tr>
                <?php $total += $product['price'] * $cart[$product['id']]; ?>
            <?php endforeach; ?>
        </table>

        <p>Итого: <strong><?= number_format($total, 2, '.', ''); ?> ₽</strong></p>
        <a href="order_form.php" class="btn-checkout">Оформить заказ</a>
    <?php endif; ?>
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
