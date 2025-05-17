<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

// Логирование
file_put_contents('order_form_log.txt', date('Y-m-d H:i:s') . " - Начало order_form.php\n", FILE_APPEND);

// Отключаем кэширование
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Генерация CSRF-токена
$csrf_token = generateCsrfToken();

// Проверяем, есть ли товары в корзине
$is_guest = !isset($_SESSION['user_id']) || isset($_SESSION['logged_out']);
$cart_key = $is_guest ? 'guest_cart' : 'cart';

if (!isset($_SESSION[$cart_key]) || empty($_SESSION[$cart_key]) || !isset($_SESSION['cart_totals'])) {
    file_put_contents('order_form_log.txt', date('Y-m-d H:i:s') . " - Ошибка: корзина пуста\n", FILE_APPEND);
    header("Location: cart.php");
    exit();
}

include 'header.php';

// Получаем данные пользователя, если авторизован
$user = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        file_put_contents('order_form_log.txt', date('Y-m-d H:i:s') . " - Пользователь id={$_SESSION['user_id']}, данные: " . print_r($user, true) . "\n", FILE_APPEND);
        if (!$user) {
            file_put_contents('order_form_log.txt', date('Y-m-d H:i:s') . " - Ошибка: пользователь id={$_SESSION['user_id']} не найден\n", FILE_APPEND);
        }
    } catch (PDOException $e) {
        file_put_contents('order_form_log.txt', date('Y-m-d H:i:s') . " - Ошибка БД при получении пользователя: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Получаем сохранённые данные и ошибки из сессии (если есть)
$errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

// Логируем значения полей
$field_values = [
    'delivery_name' => $form_data['delivery_name'] ?? ($user['name'] ?? ''),
    'phone' => $form_data['phone'] ?? ($user['phone'] ?? ''),
    'email' => $form_data['email'] ?? ($user['email'] ?? '')
];
file_put_contents('order_form_log.txt', date('Y-m-d H:i:s') . " - Значения полей: " . print_r($field_values, true) . "\n", FILE_APPEND);

// Очищаем временные данные из сессии
unset($_SESSION['form_errors'], $_SESSION['form_data']);
?>

<main>
    <h1>Оформление заказа</h1>
    
    <form action="checkout.php" method="POST" id="orderForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
        <h2>Контактные данные</h2>
        <div class="form-group">
            <label for="delivery_name">Имя:</label>
            <input type="text" id="delivery_name" name="delivery_name" 
                   value="<?= htmlspecialchars($form_data['delivery_name'] ?? ($user['name'] ?? '')); ?>" required>
            <?php if (isset($errors['delivery_name'])): ?>
                <span class="error"><?= htmlspecialchars($errors['delivery_name']); ?></span>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="text" id="phone" name="phone" 
                   value="<?= htmlspecialchars($form_data['phone'] ?? ($user['phone'] ?? '')); ?>" 
                   required placeholder="+12345678901">
            <?php if (isset($errors['phone'])): ?>
                <span class="error"><?= htmlspecialchars($errors['phone']); ?></span>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" 
                   value="<?= htmlspecialchars($form_data['email'] ?? ($user['email'] ?? '')); ?>" required>
            <?php if (isset($errors['email'])): ?>
                <span class="error"><?= htmlspecialchars($errors['email']); ?></span>
            <?php endif; ?>
        </div>
        <h2>Способ доставки</h2>
        <div class="form-group">
            <select name="delivery" required>
                <option value="Курьер" <?= ($form_data['delivery'] ?? '') === 'Курьер' ? 'selected' : ''; ?>>Курьер</option>
                <option value="Самовывоз" <?= ($form_data['delivery'] ?? '') === 'Самовывоз' ? 'selected' : ''; ?>>Самовывоз</option>
                <option value="Почта" <?= ($form_data['delivery'] ?? '') === 'Почта' ? 'selected' : ''; ?>>Почта</option>
            </select>
            <?php if (isset($errors['delivery'])): ?>
                <span class="error"><?= htmlspecialchars($errors['delivery']); ?></span>
            <?php endif; ?>
        </div>
        <h2>Способ оплаты</h2>
        <div class="form-group">
            <select name="payment" required>
                <option value="Наличными" <?= ($form_data['payment'] ?? '') === 'Наличными' ? 'selected' : ''; ?>>Наличными</option>
                <option value="Картой" <?= ($form_data['payment'] ?? '') === 'Картой' ? 'selected' : ''; ?>>Картой</option>
            </select>
            <?php if (isset($errors['payment'])): ?>
                <span class="error"><?= htmlspecialchars($errors['payment']); ?></span>
            <?php endif; ?>
        </div>
        <button type="submit">Подтвердить заказ</button>
    </form>

    <p><strong>Итого:</strong> <?= number_format($_SESSION['cart_totals']['total_price'], 2, '.', ''); ?> грн</p>
    <?php if ($_SESSION['cart_totals']['total_discount'] > 0): ?>
        <p><strong>Скидка:</strong> <?= number_format($_SESSION['cart_totals']['total_discount'], 2, '.', ''); ?> грн</p>
    <?php endif; ?>
</main>

<style>
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; }
.form-group input, .form-group select { width: 100%; padding: 8px; box-sizing: border-box; }
.error { color: red; font-size: 0.9em; display: block; margin-top: 5px; }
</style>

<?php include 'footer.php'; ?>