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
        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($cart));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <form action="order_form.php" method="POST">
            <table>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Скидка</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th>Действия</th>
                </tr>

                <?php $total = 0; $total_discount = 0; ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $product_id = $product['id'];
                    $quantity = (int)$cart[$product_id];

                    // Проверяем скидку по product_id и category_id
                    $stmt = $conn->prepare("
                        SELECT discount_value, discount_type 
                        FROM discounts 
                        WHERE (product_id = ? OR category_id = ?) 
                        AND (start_date IS NULL OR start_date <= NOW()) 
                        AND (end_date IS NULL OR end_date >= NOW()) 
                        LIMIT 1
                    ");
                    $stmt->execute([$product_id, $product['category_id']]);
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
                    $total_discount += ($original_price - $final_price) * $quantity;
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
                            <button type="button" onclick="removeFromCart(<?= $product_id; ?>)">❌</button>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <td colspan="4"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($total, 2, '.', ''); ?> ₽</strong></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"><strong>Скидка:</strong></td>
                    <td><strong><?= number_format($total_discount, 2, '.', ''); ?> ₽</strong></td>
                    <td></td>
                </tr>
            </table>

            <input type="hidden" name="total_price" value="<?= $total; ?>">
            <input type="hidden" name="total_discount" value="<?= $total_discount; ?>">
            <button type="submit">Оформить заказ</button>
        </form>

        <script>
            function removeFromCart(productId) {
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    }
                });
            }
        </script>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>