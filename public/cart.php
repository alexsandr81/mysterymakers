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
        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($cart));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <table>
            <tr>
                <th>Товар</th>
                <th>Цена</th>
                <th>Скидка</th>
                <th>Количество</th>
                <th>Сумма</th>
                <th>Действия</th>
            </tr>

            <?php $total = 0; ?>
            <?php foreach ($products as $product): ?>
                <?php
                $product_id = $product['id'];
                $quantity = (int)$cart[$product_id];

                // Проверяем наличие скидки
                $stmt = $conn->prepare("SELECT discount_value, discount_type 
                                        FROM discounts 
                                        WHERE product_id = ? 
                                        AND (start_date IS NULL OR start_date <= NOW()) 
                                        AND (end_date IS NULL OR end_date >= NOW()) 
                                        LIMIT 1");
                $stmt->execute([$product_id]);
                $discount = $stmt->fetch(PDO::FETCH_ASSOC);

                $original_price = $product['price'];
                $discount_value = 0;
                $discount_text = "—";
                $has_discount = false;

                if ($discount) {
                    if ($discount['discount_type'] == 'fixed') {
                        $discount_value = min($discount['discount_value'], $original_price);
                        $discount_text = "- " . number_format($discount_value, 2, '.', '') . " ₽";
                    } elseif ($discount['discount_type'] == 'percentage') {
                        $discount_value = $original_price * ($discount['discount_value'] / 100);
                        $discount_text = "- " . number_format($discount['discount_value'], 0) . "%";
                    }
                    $has_discount = true;
                }

                $final_price = $original_price - $discount_value;
                $subtotal = $final_price * $quantity;
                $total += $subtotal;
                ?>

                <tr>
                    <td><?= htmlspecialchars($product['name']); ?></td>
                    <td>
                        <?php if ($has_discount): ?>
                            <del style="color: red;"><?= number_format($original_price, 2, '.', ''); ?> ₽</del>
                            <span style="color: green;"><?= number_format($final_price, 2, '.', ''); ?> ₽</span>
                        <?php else: ?>
                            <span><?= number_format($original_price, 2, '.', ''); ?> ₽</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $discount_text; ?></td>
                    <td><?= $quantity; ?></td>
                    <td><strong><?= number_format($subtotal, 2, '.', ''); ?> ₽</strong></td>
                    <td>
                        <button onclick="removeFromCart(<?= $product_id; ?>)">❌</button>
                    </td>
                </tr>
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
