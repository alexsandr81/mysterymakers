<?php
include 'header.php';
include '../database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Защита от CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
            <table>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Скидка</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th>Действия</th>
                </tr>

                <?php $total = 0;
                $total_discount = 0; ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $product_id = (int)$product['id'];
                    $quantity = (int)($cart[$product_id] ?? 0);

                    if ($quantity <= 0) continue;

                    $stmt = $conn->prepare("
                        SELECT discount_value, discount_type 
                        FROM discounts 
                        WHERE (product_id = ? OR category_id = ?) 
                        AND (start_date IS NULL OR start_date <= NOW()) 
                        AND (end_date IS NULL OR end_date >= NOW()) 
                        LIMIT 1
                    ");
                    $stmt->execute([$product_id, (int)$product['category_id']]);
                    $discount = $stmt->fetch(PDO::FETCH_ASSOC);

                    $original_price = (float)$product['price'];
                    $discount_value = 0;
                    $discount_text = "—";
                    $has_discount = false;

                    if ($discount) {
                        if ($discount['discount_type'] == 'fixed') {
                            $discount_value = min((float)$discount['discount_value'], $original_price);
                            $discount_text = "- " . number_format($discount_value, 2, '.', '') . " грн.";
                        } elseif ($discount['discount_type'] == 'percentage') {
                            $discount_value = $original_price * ((float)$discount['discount_value'] / 100);
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
                                <del style="color: red;"><?= number_format($original_price, 2, '.', ''); ?> грн.</del>
                                <span style="color: green;"><?= number_format($final_price, 2, '.', ''); ?> грн.</span>
                            <?php else: ?>
                                <span><?= number_format($original_price, 2, '.', ''); ?> грн.</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $discount_text; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <button type="button" onclick="changeQuantity(<?= $product_id; ?>, -1)" style="width: 20px;">−</button>
                                <input type="number"
                                    name="quantity[<?= $product_id; ?>]"
                                    value="<?= $quantity; ?>"
                                    min="1"
                                    max="99"
                                    onchange="updateQuantity(<?= $product_id; ?>, this.value)"
                                    style="width: 30px; text-align: center;">
                                <button type="button" onclick="changeQuantity(<?= $product_id; ?>, 1)" style="width: 20px;">+</button>
                            </div>
                        </td>
                        <td><strong><?= number_format($subtotal, 2, '.', ''); ?> грн.</strong></td>
                        <td>
                            <button type="button" onclick="confirmRemoveFromCart(<?= $product_id; ?>)">❌</button>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <td colspan="4"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($total, 2, '.', ''); ?> грн.</strong></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"><strong>Скидка:</strong></td>
                    <td><strong><?= number_format($total_discount, 2, '.', ''); ?> грн.</strong></td>
                    <td></td>
                </tr>
            </table>

            <?php
            // Сохраняем total_price и total_discount в сессию
            $_SESSION['cart_totals'] = [
                'total_price' => $total,
                'total_discount' => $total_discount
            ];
            ?>

            <button type="submit">Оформить заказ</button>
        </form>

        <script>
            function updateQuantity(productId, quantity) {
                if (quantity < 1 || quantity > 99) {
                    alert('Количество должно быть от 1 до 99');
                    return;
                }
                fetch('update_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(productId) + 
                          '&quantity=' + encodeURIComponent(quantity) + 
                          '&csrf_token=' + encodeURIComponent('<?= $_SESSION['csrf_token']; ?>')
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + (data.message || 'Не удалось обновить количество'));
                    }
                })
                .catch(error => alert('Ошибка соединения: ' + error));
            }

            function changeQuantity(productId, delta) {
                const input = document.querySelector(`input[name="quantity[${productId}]"]`);
                let newQuantity = parseInt(input.value) + delta;
                if (newQuantity >= 1 && newQuantity <= 99) {
                    input.value = newQuantity;
                    updateQuantity(productId, newQuantity);
                }
            }

            function confirmRemoveFromCart(productId) {
                if (confirm('Вы уверены, что хотите удалить этот товар из корзины?')) {
                    fetch('remove_from_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + encodeURIComponent(productId) + 
                              '&csrf_token=' + encodeURIComponent('<?= $_SESSION['csrf_token']; ?>')
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            alert('Ошибка: ' + (data.message || 'Не удалось удалить товар'));
                        }
                    })
                    .catch(error => alert('Ошибка соединения: ' + error));
                }
            }
        </script>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>