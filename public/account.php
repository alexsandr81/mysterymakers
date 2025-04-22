<?php 
include 'header.php'; 
require_once '../database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');

// Получаем данные пользователя
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['cart']);
    setcookie("user_id", "", time() - 3600, "/mysterymakers/");
    header("Location: login.php?error=account_deleted");
    exit();
}

// Получаем последний адрес из user_addresses
$stmt = $conn->prepare("SELECT address FROM user_addresses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$address = $stmt->fetch(PDO::FETCH_ASSOC);
$address = $address ? $address['address'] : '';

// Получаем избранное с данными о скидках
$stmt = $conn->prepare("
    SELECT p.*, f.id AS favorite_id,
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
    FROM favorites f
    JOIN products p ON f.product_id = p.id
    WHERE f.user_id = ? AND p.status = 1
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем заказы
$stmt = $conn->prepare("SELECT id, order_number, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Сообщения
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<main>
    <style>
        form {
            max-width: 400px;
            margin: 20px 0;
        }
        form label {
            display: block;
            margin: 10px 0 5px;
        }
        form input, form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 3px;
        }
        form button:hover {
            background-color: #0056b3;
        }
        .logout-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 3px;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
        .products {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .product {
            border: 1px solid #ddd;
            padding: 10px;
            width: 200px;
            text-align: center;
        }
        .product img {
            max-width: 100%;
            height: auto;
        }
        .product h3 {
            font-size: 16px;
            margin: 10px 0;
        }
        .product .price, .product .discount-price {
            color: #28a745;
            font-weight: bold;
        }
        .product .old-price {
            color: #999;
            text-decoration: line-through;
        }
        .product .discount-info {
            color: #dc3545;
            font-size: 12px;
        }
        .product a {
            text-decoration: none;
            color: #007bff;
        }
        .product a:hover {
            text-decoration: underline;
        }
        .remove-btn, .add-to-cart-btn {
            padding: 8px 16px;
            margin: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .remove-btn {
            background-color: #dc3545;
            color: white;
        }
        .remove-btn:hover {
            background-color: #c82333;
        }
        .add-to-cart-btn {
            background-color: #28a745;
            color: white;
        }
        .add-to-cart-btn:hover {
            background-color: #218838;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .tabs {
            margin: 20px 0;
            border-bottom: 1px solid #ddd;
        }
        .tabs button {
            padding: 10px 20px;
            margin-right: 5px;
            border: none;
            background: #f2f2f2;
            cursor: pointer;
            border-radius: 3px 3px 0 0;
        }
        .tabs button.active {
            background: #007bff;
            color: white;
        }
        .tabs button:hover {
            background: #e0e0e0;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>

    <h1>Личный кабинет</h1>
    <p>Добро пожаловать, <?= $user_name; ?>!</p>
    <p><a href="logout.php" class="logout-btn">Выйти</a></p>

    <!-- Сообщения -->
    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Вкладки -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="profile">Профиль</button>
        <button class="tab-btn" data-tab="favorites">Избранное</button>
        <button class="tab-btn" data-tab="orders">Заказы</button>
    </div>

    <!-- Контент вкладок -->
    <div id="profile" class="tab-content active">
        <h2>Редактировать профиль</h2>
        <form action="update_profile.php" method="POST">
            <label for="name">Имя:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
            <label for="phone">Телефон:</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+380123456789">
            <label for="address">Адрес доставки:</label>
            <textarea id="address" name="address"><?= htmlspecialchars($address); ?></textarea>
            <label for="password">Новый пароль (оставьте пустым, если не меняете):</label>
            <input type="password" id="password" name="password">
            <label for="current_password">Текущий пароль:</label>
            <input type="password" id="current_password" name="current_password" required>
            <button type="submit">Сохранить</button>
        </form>
    </div>

    <div id="favorites" class="tab-content">
        <h2>Избранное</h2>
        <?php if (empty($favorites)): ?>
            <p>В вашем избранном пока нет товаров.</p>
            <p><a href="catalog.php">Перейти в каталог</a></p>
        <?php else: ?>
            <div class="products">
                <?php foreach ($favorites as $favorite): ?>
                    <?php
                    $images = json_decode($favorite['images'], true);
                    $main_image = !empty($images) ? "/mysterymakers/" . $images[0] : "/mysterymakers/public/assets/default.jpg";
                    $original_price = $favorite['price'];
                    $discount_value = $favorite['discount_value'] ?? 0;
                    $discount_price = $original_price;
                    if ($discount_value) {
                        if ($favorite['discount_type'] == 'fixed') {
                            $discount_price = max(0, $original_price - $discount_value);
                        } elseif ($favorite['discount_type'] == 'percentage') {
                            $discount_price = $original_price * (1 - $discount_value / 100);
                        }
                    }
                    ?>
                    <div class="product">
                        <a href="product.php?id=<?= htmlspecialchars($favorite['id']); ?>">
                            <img src="<?= htmlspecialchars($main_image); ?>" alt="<?= htmlspecialchars($favorite['name']); ?>">
                            <h3><?= htmlspecialchars($favorite['name']); ?></h3>
                            <?php if ($discount_value): ?>
                                <p class="old-price"><s><?= number_format($original_price, 2, '.', ''); ?> грн</s></p>
                                <p class="discount-price"><?= number_format($discount_price, 2, '.', ''); ?> грн</p>
                                <p class="discount-info">
                                    Скидка <?= ($favorite['discount_type'] == 'fixed') ? $discount_value . ' грн' : $discount_value . '%'; ?>
                                </p>
                            <?php else: ?>
                                <p class="price"><?= number_format($original_price, 2, '.', ''); ?> грн</p>
                            <?php endif; ?>
                        </a>
                        <div>
                            <button class="add-to-cart-btn" data-product-id="<?= $favorite['id']; ?>">В корзину</button>
                            <form method="POST" action="remove_from_favorites.php" style="display: inline;">
                                <input type="hidden" name="product_id" value="<?= $favorite['id']; ?>">
                                <button type="submit" class="remove-btn">Удалить</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="orders" class="tab-content">
        <h2>История заказов</h2>
        <table>
            <tr>
                <th>Номер заказа</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
            <?php if (empty($orders)): ?>
                <tr><td colspan="5">У вас пока нет заказов.</td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_number']); ?></td>
                        <td><?= htmlspecialchars($order['total_price']); ?> грн</td>
                        <td><?= htmlspecialchars($order['status']); ?></td>
                        <td><?= htmlspecialchars($order['created_at']); ?></td>
                        <td><a href="order_details.php?id=<?= $order['id']; ?>">Подробности</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</main>

<script>
document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${productId}&quantity=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Товар добавлен в корзину!');
                document.getElementById('cart-count').textContent = data.cart_count;
            }
        })
        .catch(error => console.error('Ошибка:', error));
    });
});

document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', function() {
        // Убираем активный класс у всех кнопок и вкладок
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        // Добавляем активный класс текущей кнопке и вкладке
        this.classList.add('active');
        document.getElementById(this.getAttribute('data-tab')).classList.add('active');
    });
});
</script>

<?php include 'footer.php'; ?>