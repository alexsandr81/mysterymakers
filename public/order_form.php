<?php
session_start();
require_once '../database/db.php';

// Проверяем, есть ли товары в корзине и totals в сессии
if (!isset($_SESSION['cart']) || empty($_SESSION['cart']) || !isset($_SESSION['cart_totals'])) {
    header("Location: cart.php");
    exit();
}

include 'header.php';

// Получаем данные пользователя, если авторизован
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<main>
    <h1>Оформление заказа</h1>
    
    <form action="checkout.php" method="post">
        <h2>Контактные данные</h2>
        <label>Имя:</label>
        <input type="text" name="delivery_name" value="<?= htmlspecialchars($user['name'] ?? ''); ?>" required><br>
        
        <label>Телефон:</label>
        <input type="text" name="phone" required><br>
        
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" required><br>

        <h2>Способ доставки</h2>
        <select name="delivery">
            <option value="Курьер">Курьер</option>
            <option value="Самовывоз">Самовывоз</option>
            <option value="Почта">Почта</option>
        </select><br>

        <h2>Способ оплаты</h2>
        <select name="payment">
            <option value="Наличными">Наличными</option>
            <option value="Картой">Картой</option>
        </select><br>

        <button type="submit">Подтвердить заказ</button>
    </form>

    <!-- Отображаем итоговую сумму для пользователя -->
    <p><strong>Итого:</strong> <?= number_format($_SESSION['cart_totals']['total_price'], 2, '.', ''); ?> ₽</p>
    <?php if ($_SESSION['cart_totals']['total_discount'] > 0): ?>
        <p><strong>Скидка:</strong> <?= number_format($_SESSION['cart_totals']['total_discount'], 2, '.', ''); ?> ₽</p>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>