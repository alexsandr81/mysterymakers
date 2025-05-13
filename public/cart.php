<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

// Генерация CSRF-токена
$csrf_token = generateCsrfToken();

$is_guest = !isset($_SESSION['user_id']) || isset($_SESSION['logged_out']);
$cart_key = $is_guest ? 'guest_cart' : 'cart';

// Пересчитываем totals
$cart = $_SESSION[$cart_key] ?? [];
$total = 0;
$total_discount = 0;

if (!empty($cart)) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $conn->prepare("SELECT id, price, category_id FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cart));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $pid = (int)$product['id'];
        $qty = (int)($cart[$pid] ?? 0);
        if ($qty <= 0) continue;

        $stmt = $conn->prepare("
            SELECT discount_value, discount_type 
            FROM discounts 
            WHERE (product_id = ? OR category_id = ?) 
            AND (start_date IS NULL OR start_date <= NOW()) 
            AND (end_date IS NULL OR end_date >= NOW()) 
            LIMIT 1
        ");
        $stmt->execute([$pid, (int)$product['category_id']]);
        $discount = $stmt->fetch(PDO::FETCH_ASSOC);

        $original_price = (float)$product['price'];
        $discount_value = 0;

        if ($discount) {
            if ($discount['discount_type'] == 'fixed') {
                $discount_value = min((float)$discount['discount_value'], $original_price);
            } elseif ($discount['discount_type'] == 'percentage') {
                $discount_value = $original_price * ((float)$discount['discount_value'] / 100);
            }
        }

        $final_price = $original_price - $discount_value;
        $total += $final_price * $qty;
        $total_discount += ($original_price - $final_price) * $qty;
    }

    $_SESSION['cart_totals'] = [
        'total_price' => $total,
        'total_discount' => $total_discount
    ];
} else {
    unset($_SESSION['cart_totals']);
}

// Получаем товары корзины
$products = [];
if (!empty($cart)) {
    $placeholders = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $conn->prepare("
        SELECT p.*, 
               COALESCE(
                   (SELECT MAX(d.discount_value) FROM discounts d 
                    WHERE d.product_id = p.id 
                      AND (d.start_date IS NULL OR d.start_date <= NOW()) 
                      AND (d.end_date IS NULL OR d.end_date >= NOW())), 
                   (SELECT MAX(d.discount_value) FROM discounts d 
                    WHERE d.category_id = p.category_id 
                      AND (d.start_date IS NULL OR d.start_date <= NOW()) 
                      AND (d.end_date IS NULL OR d.end_date >= NOW()))
               ) AS discount_value,
               (SELECT d.discount_type FROM discounts d 
                WHERE (d.product_id = p.id OR d.category_id = p.category_id) 
                  AND (d.start_date IS NULL OR d.start_date <= NOW()) 
                  AND (d.end_date IS NULL OR d.end_date >= NOW())
                ORDER BY d.discount_value DESC LIMIT 1) AS discount_type
        FROM products p
        WHERE p.id IN ($placeholders)
    ");
    $stmt->execute(array_keys($cart));
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>

<main>
    <h1>Корзина</h1>

    <?php if (empty($products)): ?>
        <p>Ваша корзина пуста.</p>
    <?php else: ?>
        <form action="order_form.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
            <table>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Скидка</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th>Действия</th>
                </tr>

                <?php foreach ($products as $product): ?>
                    <?php
                    $product_id = (int)$product['id'];
                    $quantity = (int)($cart[$product_id] ?? 0);
                    if ($quantity <= 0) continue;

                    $original_price = (float)$product['price'];
                    $discount_value = $product['discount_value'] ?? 0;
                    $discount_text = "—";
                    $has_discount = false;

                    if ($discount_value) {
                        if ($product['discount_type'] == 'fixed') {
                            $discount_text = "- " . number_format($discount_value, 2, '.', '') . " грн";
                            $discount_value = min($discount_value, $original_price);
                        } elseif ($product['discount_type'] == 'percentage') {
                            $discount_text = "- " . number_format($discount_value, 0) . "%";
                            $discount_value = $original_price * ($discount_value / 100);
                        }
                        $has_discount = true;
                    }

                    $final_price = $original_price - $discount_value;
                    $subtotal = $final_price * $quantity;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name']); ?></td>
                        <td>
                            <?php if ($has_discount): ?>
                                <del style="color: red;"><?= number_format($original_price, 2, '.', ''); ?> грн</del>
                                <span style="color: green;"><?= number_format($final_price, 2, '.', ''); ?> грн</span>
                            <?php else: ?>
                                <span><?= number_format($original_price, 2, '.', ''); ?> грн</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $discount_text; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <button type="button" class="cart-btn" onclick="debouncedChangeQuantity(<?= $product_id; ?>, -1)" style="width: 20px;">−</button>
                                <input type="number"
                                    name="quantity[<?= $product_id; ?>]"
                                    value="<?= $quantity; ?>"
                                    min="1"
                                    max="99"
                                    onchange="debouncedUpdateQuantity(<?= $product_id; ?>, this.value)"
                                    style="width: 30px; text-align: center;">
                                <button type="button" class="cart-btn" onclick="debouncedChangeQuantity(<?= $product_id; ?>, 1)" style="width: 20px;">+</button>
                            </div>
                        </td>
                        <td><strong><?= number_format($subtotal, 2, '.', ''); ?> грн</strong></td>
                        <td>
                            <button type="button" onclick="confirmRemoveFromCart(<?= $product_id; ?>)">❌</button>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <td colspan="4"><strong>Итого:</strong></td>
                    <td><strong><?= number_format($_SESSION['cart_totals']['total_price'], 2, '.', ''); ?> грн</strong></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"><strong>Скидка:</strong></td>
                    <td><strong><?= number_format($_SESSION['cart_totals']['total_discount'], 2, '.', ''); ?> грн</strong></td>
                    <td></td>
                </tr>
            </table>

            <button type="submit">Оформить заказ</button>
        </form>

        <script>
            function debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }

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
                          '&csrf_token=' + encodeURIComponent('<?= htmlspecialchars($csrf_token); ?>')
                })
                .then(response => {
                    console.log('Response status: ' + response.status);
                    if (!response.ok) throw new Error('HTTP error: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Parsed JSON: ', data);
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + (data.message || 'Не удалось обновить количество'));
                    }
                })
                .catch(error => {
                    console.error('Fetch error: ', error);
                    alert('Ошибка соединения: ' + error);
                });
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
                              '&csrf_token=' + encodeURIComponent('<?= htmlspecialchars($csrf_token); ?>')
                    })
                    .then(response => {
                        console.log('Response status: ' + response.status);
                        if (!response.ok) throw new Error('HTTP error: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Parsed JSON: ', data);
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            alert('Ошибка: ' + (data.message || 'Не удалось удалить товар'));
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error: ', error);
                        alert('Ошибка соединения: ' + error);
                    });
                }
            }

            const debouncedUpdateQuantity = debounce(updateQuantity, 300);
            const debouncedChangeQuantity = debounce(changeQuantity, 300);
        </script>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>