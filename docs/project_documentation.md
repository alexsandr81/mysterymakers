Детальный отчет о проделанной работе
🔥 1. Настройка окружения
✅ Развертывание XAMPP
Установлен XAMPP (версия 8.2.4)
Настроены основные компоненты: Apache, MySQL, PHP
Указан путь установки: C:\xampp
Запущены сервисы Apache и MySQL через xampp-control.exe
✅ Настройка PHP и MySQL
Внесены изменения в php.ini:
ini
Копировать
Редактировать
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
display_errors = On
Внесены изменения в my.ini:
ini
Копировать
Редактировать
max_connections = 200
innodb_buffer_pool_size = 256M
Перезапуск Apache и MySQL после правок
✅ Создание структуры проекта
Папка проекта: C:\xampp\htdocs\mysterymakers

bash
Копировать
Редактировать
/public     # Основные файлы сайта
/assets     # Стили, изображения
/config     # Файлы настроек
/database   # Подключение к БД
/docs       # Документация
✅ Создание конфигурации подключения к БД
Файл: config/config.php
<?php
return [
    'db_host' => 'localhost',
    'db_name' => 'mysterymakers_db',
    'db_user' => 'root',
    'db_pass' => '',
];
?>


Файл: database/db.php
<?php

// Проверяем, запущена ли сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Загружаем конфигурацию
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    die("❌ Ошибка: отсутствует файл конфигурации '$configPath'.");
}

$config = include $configPath;

// Проверяем, что конфиг содержит все нужные параметры
if (!isset($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass'])) {
    die("❌ Ошибка: Некорректная конфигурация базы данных.");
}

try {
    // Создаём подключение к БД
    $conn = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("❌ Ошибка подключения к базе данных: " . $e->getMessage());
}
?>




🏠 2. Разработка главной страницы
✅ Файлы:
public/index.php
<?php include 'header.php'; ?>
<main>
    <section class="banner">
        <h1>Добро пожаловать в MysteryMakers!</h1>
        <p>Лучшие товары по отличным ценам.</p>
    </section>
    <section class="sales">
        <h2>Акции и скидки</h2>
        <div class="sale-items">
            <div class="item">Товар 1</div>
            <div class="item">Товар 2</div>
            <div class="item">Товар 3</div>
        </div>
    </section>
    <section class="reviews">
        <h2>Отзывы клиентов</h2>
        <p>⭐ ⭐ ⭐ ⭐ ⭐ "Отличный магазин!"</p>
    </section>
</main>
<?php include 'footer.php'; ?>


public/header.php
<?php
// Запускаем сессию, если она еще не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MysteryMakers</title>
    <link rel="stylesheet" href="/mysterymakers/assets/styles.css">
</head>
<body>
<header>
    <div class="logo">
        <a href="/mysterymakers/public/index.php">
            <img src="/mysterymakers/assets/logo.png" alt="MysteryMakers">
        </a>
    </div>
    <nav>
        <ul>
            <li><a href="/mysterymakers/public/categories.php">Категории</a></li>
            <li><a href="/mysterymakers/public/about.php">О нас</a></li>
            <li><a href="/mysterymakers/public/delivery.php">Доставка</a></li>
            <li><a href="/mysterymakers/public/contact.php">Контакты</a></li>
        </ul>
    </nav>
    <div class="search">
        <form action="/mysterymakers/public/search.php" method="GET">
            <input type="text" name="q" placeholder="Поиск...">
            <button type="submit">🔍</button>
        </form>
    </div>
    <div class="icons">
    <a href="#">❤️</a>
        <a href="/mysterymakers/public/cart.php">🛒</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="/mysterymakers/public/account.php">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Профиль'); ?></a>
            <a href="/mysterymakers/public/logout.php">🚪 Выйти</a>
        <?php else: ?>
            <a href="/mysterymakers/public/login.php">🔑 Войти</a>
        <?php endif; ?>
    </div>
</header>


public/footer.php
<footer>
    <p>© 2024 MysteryMakers. Все права защищены.</p>
    <p><a href="#">Политика конфиденциальности</a> | <a href="#">Условия использования</a></p>
</footer>
</body>
</html>


✅ Функционал:
Шапка (header.php) с логотипом, меню, поиском, корзиной
Основной контент (index.php):
Баннер с приветствием
Секция "Акции и скидки"
Секция "Отзывы клиентов"
Подвал (footer.php): контакты, соцсети, политика конфиденциальности

📦 3. Каталог товаров


✅ Разработка catalog.php
Фильтры товаров (категория, сортировка по цене)
Динамический вывод товаров из базы
<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>
<main>
    <h1>Каталог товаров</h1>
    <div class="filters">
        <form method="GET">
            <select name="category">
                <option value="">Все категории</option>
                <option value="Одежда">Одежда</option>
                <option value="Аксессуары">Аксессуары</option>
                <option value="Канцелярия">Канцелярия</option>
            </select>
            <select name="sort">
                <option value="price_asc">Сначала дешевые</option>
                <option value="price_desc">Сначала дорогие</option>
            </select>
            <button type="submit">Применить</button>
        </form>
    </div>
    <div class="products">
        <?php
        $category = $_GET['category'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $query = "SELECT * FROM products WHERE 1";
        if ($category) {
            $query .= " AND category = :category";
        }
        if ($sort == 'price_asc') {
            $query .= " ORDER BY price ASC";
        } elseif ($sort == 'price_desc') {
            $query .= " ORDER BY price DESC";
        }
        $stmt = $conn->prepare($query);
        if ($category) {
            $stmt->bindParam(':category', $category);
        }
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $product): ?>
            <div class="product">
                <img src="/mysterymakers/assets/<?= $product['image']; ?>" alt="<?= $product['name']; ?>">
                <h2><?= $product['name']; ?></h2>
                <p><?= number_format($product['price'], 2, '.', ''); ?> ₽</p>
                <a href="product.php?id=<?= $product['id']; ?>">Подробнее</a>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php include 'footer.php'; ?>


📄 4. Страница товара
✅ Разработка product.php
Подключение к БД, получение информации о товаре
Галерея изображений + зум
Описание, цена, наличие
Кнопки "Добавить в корзину" и "В избранное"
<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>
<?php
// Получаем ID товара из URL
$id = $_GET['id'] ?? 0;
// Загружаем информацию о товаре
$stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    echo "<h1>Товар не найден</h1>";
    include 'footer.php';
    exit;
}
?>
<main>
    <div class="product-page">
        <div class="gallery">
            <img id="mainImage" src="/mysterymakers/assets/<?= $product['image']; ?>" alt="<?= $product['name']; ?>">
            <div class="thumbnails">
                <img src="/mysterymakers/assets/<?= $product['image']; ?>" onclick="changeImage(this)">
                <img src="/mysterymakers/assets/sample1.jpg" onclick="changeImage(this)">
                <img src="/mysterymakers/assets/sample2.jpg" onclick="changeImage(this)">
            </div>
        </div>
        <div class="details">
            <h1><?= $product['name']; ?></h1>
            <p class="price"><?= number_format($product['price'], 2, '.', ''); ?> ₽</p>
            <p class="stock"><?= $product['stock'] > 0 ? 'В наличии' : 'Нет в наличии'; ?></p>
            <button class="btn-cart" onclick="addToCart(<?= $product['id']; ?>)">🛒 Добавить в корзину</button>
            <button class="btn-fav">❤️ В избранное</button>
            <h3>Описание</h3>
            <p><?= $product['description']; ?></p>
            <h3>Отзывы</h3>
            <div class="reviews">
                <p>⭐ ⭐ ⭐ ⭐ ⭐ "Отличный товар!"</p>
            </div>
        </div>
    </div>
</main>
<script>
function changeImage(img) {
    document.getElementById('mainImage').src = img.src;
}
function addToCart(productId) {
    fetch('/mysterymakers/public/add_to_cart.php', {
        method: 'POST',
        body: new URLSearchParams({ id: productId }),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        alert('Товар добавлен в корзину! Количество товаров: ' + data.cart_count);
    });
}
</script>
<?php include 'footer.php'; ?>



🛒 5. Корзина и оформление заказа
✅ Корзина (cart.php)
Использование сессий для хранения данных о товарах
AJAX-добавление товаров в корзину
Пересчет итоговой суммы
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
        $stmt = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
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



✅ Обработчики:
add_to_cart.php – добавление товара в корзину
<?php
session_start();
$id = $_POST['id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
// Если товар уже есть в корзине, увеличиваем количество
if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id] += $quantity;
} else {
    $_SESSION['cart'][$id] = $quantity;
}
echo json_encode(['status' => 'success', 'cart_count' => array_sum($_SESSION['cart'])]);
?>


remove_from_cart.php – удаление товара
<?php
session_start();
$id = $_POST['id'] ?? 0;
if (isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}
echo json_encode(['status' => 'success']);
?>


✅ Оформление заказа (checkout.php)
Форма (ФИО, телефон, email, адрес)
Вывод содержимого корзины
<?php
session_start();
require_once '../database/db.php';
// Проверяем авторизацию пользователя
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Проверяем, пришли ли данные из формы
if (!isset($_POST['name'], $_POST['phone'], $_POST['email'], $_POST['delivery'], $_POST['payment'])) {
    header("Location: order_form.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$delivery = trim($_POST['delivery']);
$payment = trim($_POST['payment']);
// Проверяем, есть ли товары в корзине
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}
$total_price = 0;
// Генерируем уникальный номер заказа
$order_number = 'MM' . time() . rand(100, 999);
// Создаём заказ в таблице `orders`
$stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_price, status, name, phone, email, delivery, payment) 
VALUES (:user_id, :order_number, :total_price, 'Новый', :name, :phone, :email, :delivery, :payment)");
$stmt->execute([
    ':user_id' => $user_id,
    ':order_number' => $order_number,
    ':total_price' => $total_price,
    ':name' => $name,
    ':phone' => $phone,
    ':email' => $email,
    ':delivery' => $delivery,
    ':payment' => $payment
]);
$order_id = $conn->lastInsertId(); // Получаем ID заказа
// Добавляем товары в `order_items`
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    // Получаем цену товара
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = :product_id");
    $stmt->execute([':product_id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        $price = $product['price'];
        $total_price += $quantity * $price;
        // Записываем товар в заказ
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
        VALUES (:order_id, :product_id, :quantity, :price)");
        $stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $product_id,
            ':quantity' => $quantity,
            ':price' => $price
        ]);
    }
}
// Обновляем общую сумму заказа
$stmt = $conn->prepare("UPDATE orders SET total_price = :total_price WHERE id = :order_id");
$stmt->execute([
    ':total_price' => $total_price,
    ':order_id' => $order_id
]);
// Очищаем корзину
unset($_SESSION['cart']);
// Перенаправляем на страницу "Спасибо за заказ!"
header("Location: thank_you.php?order_number=$order_number");
exit();
?>


✅ Обработка заказа (process_order.php)
Генерация номера заказа
Запись заказа в таблицу orders
Запись товаров заказа в order_items
Очистка корзины
<?php
session_start();
include '../database/db.php';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $total_price = $_POST['total_price'];
    // Создаем заказ
    $stmt = $pdo->prepare("INSERT INTO orders (user_name, phone, email, address, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $phone, $email, $address, $total_price]);
    $order_id = $pdo->lastInsertId();
    // Добавляем товары в заказ
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);
    }
    // Очищаем корзину
    $_SESSION['cart'] = [];
    // Перенаправляем на страницу "Спасибо за заказ!"
    header("Location: thank_you.php?order_id=" . $order_id);
    exit();
}
?>


✅ Страница "Спасибо за заказ" (thank_you.php)
Вывод номера заказа после успешного оформления
<?php include 'header.php'; ?>
<main>
    <h1>Спасибо за заказ!</h1>
    <?php
    // Проверяем, передан ли номер заказа в GET-параметрах
    $order_number = $_GET['order_number'] ?? null;
    if ($order_number) {
        echo "<p>Ваш заказ №{$order_number} успешно оформлен.</p>";
    } else {
        echo "<p>Ошибка: номер заказа не найден.</p>";
    }
    ?>
    <a href="catalog.php">Вернуться в каталог</a>
</main>
<?php include 'footer.php'; ?>


✅ Регистрация (register.php)
Форма регистрации
Обработка пароля (хеширование)
<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>
<main>
    <h1>Регистрация</h1>
    <form action="register_process.php" method="POST">
        <label>Имя:</label>
        <input type="text" name="name" required>
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Пароль:</label>
        <input type="password" name="password" required>
        <button type="submit">Зарегистрироваться</button>
    </form>
</main>
<?php include 'footer.php'; ?>


✅ Авторизация (login.php)
Форма входа
Проверка пароля
<?php 
// Проверяем, активна ли сессия, перед запуском
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../database/db.php'; // Подключаем базу данных
include 'header.php'; 
// Проверяем, вошел ли пользователь
if (isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit();
}
// Обрабатываем вход
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']); // Запомнить меня
    // Используем $pdo вместо $conn
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];  // Добавляем имя пользователя в сессию
        // Запоминаем пользователя, если он выбрал "Запомнить меня"
        if ($remember) {
            setcookie("user_id", $user['id'], time() + (86400 * 30), "/"); // 30 дней
        }
        header("Location: account.php");
        exit();
    } else {
        $error = "Неправильный email или пароль!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
</head>
<body>
<h2>Вход</h2>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
<form method="POST" action="">
    <label>Email:</label>
    <input type="email" name="email" required><br><br>
    <label>Пароль:</label>
    <input type="password" name="password" required><br><br>
    <label>
        <input type="checkbox" name="remember"> Запомнить меня
    </label><br><br>
    <button type="submit">Войти</button>
</form>
<p><a href="register.php">Регистрация</a> | <a href="forgot_password.php">Забыли пароль?</a></p>
</body>
</html>
<?php include 'footer.php'; ?>


✅ Личный кабинет (account.php)
Вывод имени пользователя
История заказов
<?php 
include 'header.php'; 
require_once '../database/db.php'; // Проверяем, что путь верный!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
// Выводим user_id для отладки
// echo "<p>Ваш user_id: " . $user_id . "</p>";
// Проверяем, что подключение к БД работает
if (!$conn) {
    die("Ошибка подключения к базе данных.");
}
// Проверяем, есть ли заказы у текущего пользователя
$stmt = $conn->prepare("SELECT order_number, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <h1>Личный кабинет</h1>
    <p>Добро пожаловать, <?= $user_name; ?>!</p>
    <h2>История заказов</h2>
    <table border="1">
        <tr>
            <th>Номер заказа</th>
            <th>Сумма</th>
            <th>Статус</th>
            <th>Дата</th>
        </tr>
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_number']); ?></td>
                    <td><?= htmlspecialchars($order['total_price']); ?> грн</td>
                    <td><?= htmlspecialchars($order['status']); ?></td>
                    <td><?= htmlspecialchars($order['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">У вас пока нет заказов.</td></tr>
        <?php endif; ?>
    </table>
    <a href="logout.php" class="logout-btn">Выйти</a>
</main>
<?php include 'footer.php'; ?>


✅ Выход (logout.php)
Очистка сессии
<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>


"закладка 18.03.2025"


✔ Пользователь может запросить сброс пароля (forgot_password.php)
✔ Генерируется уникальный токен и отправляется пользователю

<?php
session_start();
require_once '../database/db.php'; // Подключаем базу данных
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $reset_token = bin2hex(random_bytes(32)); // Генерируем токен
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
        $stmt->execute([$reset_token, $email]);

        $reset_link = "http://localhost/mysterymakers/public/reset_password.php?token=$reset_token";
        echo "<p>Перейдите по ссылке, чтобы сбросить пароль: <a href='$reset_link'>$reset_link</a></p>";
    } else {
        echo "<p style='color:red;'>Email не найден!</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля</title>
</head>
<body>
<h2>Восстановление пароля</h2>
<form method="POST" action="">
    <label>Email:</label>
    <input type="email" name="email" required><br><br>
    <button type="submit">Отправить ссылку</button>
</form>
</body>
</html>
<?php include 'footer.php'; ?>


✔ Пользователь вводит новый пароль (reset_password.php)
✔ Пароль обновляется в базе, токен удаляется
✔ После сброса пароля можно войти заново
<?php
require_once '../database/db.php';
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["token"])) {
    $token = $_GET["token"];
    // Проверяем, существует ли токен в базе
    $stmt = $pdo->prepare("SELECT email FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        die("Неверный или истёкший токен.");
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["token"], $_POST["password"])) {
    $token = $_POST["token"];
    $new_password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    // Обновляем пароль в базе
    $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
    $stmt->execute([$new_password, $token]);
    if ($stmt->rowCount() > 0) {
        echo "Пароль успешно изменён! <a href='login.php'>Войти</a>";
    } else {
        echo "Ошибка сброса пароля.";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля</title>
</head>
<body>
<h2>Сброс пароля</h2>
<form method="POST">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <label>Новый пароль:</label>
    <input type="password" name="password" required><br><br>
    <button type="submit">Сменить пароль</button>
</form>
</body>
</html>


admin\ Вход в админку (login.php) 
<?php
session_start();
require_once '../database/db.php';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin && $password === $admin['password']) { // Позже заменим на password_verify()
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Неправильный email или пароль!";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админку</title>
    <link rel="stylesheet" href="styles.css"> <!-- Подключаем стили -->
</head>
<body>
<h2>Админ-панель – Вход</h2>
<?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
    <label>Email:</label>
    <input type="email" name="email" required><br><br>
    <label>Пароль:</label>
    <input type="password" name="password" required><br><br>
    <button type="submit">Войти</button>
</form>
</body>
</html>


✔ Главная страница админки (index.php)
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <!-- Подключаем Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .list-group a {
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container admin-container">
    <h2>Админ-панель</h2>
    <ul class="list-group">
        <li class="list-group-item"><a href="products.php">📦 Товары</a></li>
        <li class="list-group-item"><a href="orders.php">🛒 Заказы</a></li>
        <li class="list-group-item"><a href="users.php">👥 Пользователи</a></li>
        <li class="list-group-item"><a href="admins.php">🛠️ Администраторы</a></li>
        <li class="list-group-item"><a href="logs.php">📜 Логи действий</a></li>
        <li class="list-group-item text-danger"><a href="logout.php">🚪 Выйти</a></li>
    </ul>
</div>
<!-- Подключаем Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


✔ Выход из админки (logout.php)
<?php
session_start();
session_destroy();
header("Location: login.php");
exit();
?>


✔ Список товаров (products.php)
<?php
session_start();
require_once '../database/db.php';
// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Получаем список товаров
$stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление товарами</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>Товары</h2>
<a href="add_product.php">➕ Добавить товар</a>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Название</th>
        <th>Цена</th>
        <th>Категория</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($products as $product): ?>
    <tr>
        <td><?= $product['id']; ?></td>
        <td><?= htmlspecialchars($product['name']); ?></td>
        <td><?= number_format($product['price'], 2, '.', ''); ?> ₽</td>
        <td><?= htmlspecialchars($product['category']); ?></td>
        <td>
            <a href="edit_product.php?id=<?= $product['id']; ?>">✏ Редактировать</a> | 
            <a href="delete_product.php?id=<?= $product['id']; ?>" onclick="return confirm('Удалить товар?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>


✔ Добавление товара (add_product.php)
<?php
session_start();
require_once '../database/db.php';
// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Обрабатываем добавление товара
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $category, $stock]);
    header("Location: products.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить товар</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>Добавить товар</h2>
<form method="POST">
    <label>Название:</label>
    <input type="text" name="name" required><br><br>
    <label>Описание:</label>
    <textarea name="description" required></textarea><br><br>
    <label>Цена:</label>
    <input type="number" name="price" step="0.01" required><br><br>
    <label>Категория:</label>
    <input type="text" name="category" required><br><br>
    <label>Количество:</label>
    <input type="number" name="stock" required><br><br>
    <button type="submit">Добавить</button>
</form>
</body>
</html>


✔ Редактирование товара (edit_product.php)
<?php
session_start();
require_once '../database/db.php';
// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Получаем ID товара
$id = $_GET['id'] ?? 0;
// Загружаем товар из базы
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    die("Товар не найден!");
}
// Обрабатываем обновление товара
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);
    $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=? WHERE id=?");
    $stmt->execute([$name, $description, $price, $category, $stock, $id]);
    header("Location: products.php");
    require_once 'log_helper.php';
log_admin_action($_SESSION['admin_id'], "Изменил товар ID: $id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать товар</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>Редактировать товар</h2>
<form method="POST">
    <label>Название:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']); ?>" required><br><br>
    <label>Описание:</label>
    <textarea name="description" required><?= htmlspecialchars($product['description']); ?></textarea><br><br>
    <label>Цена:</label>
    <input type="number" name="price" step="0.01" value="<?= $product['price']; ?>" required><br><br>
    <label>Категория:</label>
    <input type="text" name="category" value="<?= htmlspecialchars($product['category']); ?>" required><br><br>
    <label>Количество:</label>
    <input type="number" name="stock" value="<?= $product['stock']; ?>" required><br><br>
    <button type="submit">Сохранить</button>
</form>
</body>
</html>


✔ Удаление товара (delete_product.php)
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$id = $_GET['id'] ?? 0;
// Удаляем все записи о товаре в order_items
$stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
$stmt->execute([$id]);
// Теперь удаляем товар из products
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);
header("Location: products.php");
exit();
?>


✔ Список заказов (orders.php)
✔ Изменение статуса заказа (новый → отправлен → доставлен)
<?php
session_start();
require_once '../database/db.php';
// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Получаем список заказов
$stmt = $conn->query("
    SELECT orders.*, users.name AS user_name 
    FROM orders 
    JOIN users ON orders.user_id = users.id 
    ORDER BY orders.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление заказами</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>Заказы</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Номер заказа</th>
        <th>Покупатель</th>
        <th>Сумма</th>
        <th>Статус</th>
        <th>Дата</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($orders as $order): ?>
    <tr>
        <td><?= $order['id']; ?></td>
        <td><?= htmlspecialchars($order['order_number']); ?></td>
        <td><?= htmlspecialchars($order['user_name']); ?></td>
        <td><?= number_format($order['total_price'], 2, '.', ''); ?> ₽</td>
        <td>
    <form method="POST" action="update_order_status.php">
        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
        <select name="status" class="status-<?= strtolower(str_replace(' ', '-', $order['status'])); ?>" onchange="this.form.submit()">
            <option value="Новый" <?= $order['status'] == 'Новый' ? 'selected' : ''; ?>>Новый</option>
            <option value="В обработке" <?= $order['status'] == 'В обработке' ? 'selected' : ''; ?>>В обработке</option>
            <option value="Отправлен" <?= $order['status'] == 'Отправлен' ? 'selected' : ''; ?>>Отправлен</option>
            <option value="Доставлен" <?= $order['status'] == 'Доставлен' ? 'selected' : ''; ?>>Доставлен</option>
            <option value="Отменён" <?= $order['status'] == 'Отменён' ? 'selected' : ''; ?>>Отменён</option>
        </select>
    </form>
</td>
        <td><?= $order['created_at']; ?></td>
        <td><a href="order_details.php?id=<?= $order['id']; ?>">📄 Подробнее</a></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>


✔ Просмотр товаров внутри заказа (order_details.php)
<?php
session_start();
require_once '../database/db.php';
// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Получаем ID заказа
$order_id = intval($_GET['id'] ?? 0);
// Загружаем информацию о заказе
$stmt = $conn->prepare("
    SELECT orders.*, users.name AS user_name, users.email 
    FROM orders 
    JOIN users ON orders.user_id = users.id 
    WHERE orders.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    die("❌ Заказ не найден!");
}
// Загружаем товары в заказе
$stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Детали заказа</title>
    <link rel="stylesheet" href="/mysterymakers/admin/admin.css">
</head>
<body>
<h2>Детали заказа #<?= htmlspecialchars($order['order_number']); ?></h2>
<p><strong>Покупатель:</strong> <?= htmlspecialchars($order['user_name']); ?> (<?= htmlspecialchars($order['email']); ?>)</p>
<p><strong>Сумма:</strong> <?= number_format($order['total_price'], 2, '.', ''); ?> ₽</p>
<p><strong>Статус:</strong> <?= htmlspecialchars($order['status']); ?></p>
<p><strong>Дата:</strong> <?= $order['created_at']; ?></p>
<h3>Товары в заказе</h3>
<?php if (!empty($items)): ?>
    <table class="table">
        <tr>
            <th>Название</th>
            <th>Количество</th>
            <th>Цена</th>
            <th>Сумма</th>
        </tr>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']); ?></td> <!-- Исправлено: name вместо product_name -->
                <td><?= htmlspecialchars($item['quantity']); ?></td>
                <td><?= number_format($item['price'], 2, '.', ''); ?> ₽</td>
                <td><?= number_format($item['price'] * $item['quantity'], 2, '.', ''); ?> ₽</td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>❌ В этом заказе нет товаров.</p>
<?php endif; ?>
<a href="orders.php" class="back-button">⬅ Назад</a>
</body>
</html>


✔ Список пользователей (users.php)
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Фильтры
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$query = "SELECT * FROM users WHERE 1";
if ($status_filter) {
    $query .= " AND status = :status";
}
if ($date_filter) {
    $query .= " AND created_at >= :date";
}
$query .= " ORDER BY created_at DESC"
$stmt = $conn->prepare($query);
if ($status_filter) {
    $stmt->bindParam(':status', $status_filter);
}
if ($date_filter) {
    $stmt->bindParam(':date', $date_filter);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="/mysterymakers/admin/styles.css">
</head>
<body>
<h2>Пользователи</h2>
<!-- Фильтры -->
<form method="GET">
    <label>Фильтр по статусу:</label>
    <select name="status">
        <option value="">Все</option>
        <option value="active" <?= $status_filter == 'active' ? 'selected' : ''; ?>>Активные</option>
        <option value="blocked" <?= $status_filter == 'blocked' ? 'selected' : ''; ?>>Заблокированные</option>
    </select>
    <label>Дата регистрации с:</label>
    <input type="date" name="date" value="<?= $date_filter; ?>">
    <button type="submit">Фильтровать</button>
</form>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Email</th>
        <th>Статус</th>
        <th>Дата регистрации</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($users as $user): ?>
    <tr>
        <td><?= $user['id']; ?></td>
        <td><?= htmlspecialchars($user['name']); ?></td>
        <td><?= htmlspecialchars($user['email']); ?></td>
        <td><?= $user['status'] == 'active' ? '✅ Активен' : '❌ Заблокирован'; ?></td>
        <td><?= $user['created_at']; ?></td>
        <td>
            <?php if ($user['status'] == 'active'): ?>
                <a href="block_user.php?id=<?= $user['id']; ?>">🚫 Заблокировать</a>
            <?php else: ?>
                <a href="unblock_user.php?id=<?= $user['id']; ?>">🔓 Разблокировать</a>
            <?php endif; ?>
            |
            <a href="delete_user.php?id=<?= $user['id']; ?>" onclick="return confirm('Удалить пользователя?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>


✔ Блокировка пользователей (block_user.php)
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
$stmt->execute([$id]);
header("Location: users.php");
exit();
?>


✔ Разблокировка пользователей (unblock_user.php)
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
$stmt->execute([$id]);
header("Location: users.php");
exit();
?>


✔ Удаление пользователей (delete_user.php)
Фильтр по статусу (активные / заблокированные)
✔ Фильтр по дате регистрации
✔ Список пользователей с возможностью блокировки/удаления
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$id = $_GET['id'] ?? 0;
// Удаляем сначала заказы пользователя
$stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
$stmt->execute([$id]);
// Теперь можно удалить пользователя
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);
header("Location: users.php");
exit();
?>


Список администраторов (admins.php)
<?php
session_start();
require_once '../database/db.php';
// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Получаем список администраторов
$stmt = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Функция для красивого отображения ролей
function formatRole($role) {
    $roles = [
        'superadmin' => '<span class="role-superadmin">👑 Суперадмин</span>',
        'admin' => '<span class="role-admin">🔧 Администратор</span>',
        'moderator' => '<span class="role-moderator">🛠️ Модератор</span>',
    ];
    return $roles[$role] ?? '<span class="role-unknown">❓ Неизвестно</span>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление администраторами</title>
    <link rel="stylesheet" href="/mysterymakers/admin/styles.css">
</head>
<body>
<h2>Администраторы</h2>
<a href="add_admin.php">➕ Добавить администратора</a>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Email</th>
        <th>Роль</th>
        <th>Дата создания</th>
        <th>Действия</th>
    </tr>
    <?php foreach ($admins as $admin): ?>
    <tr>
        <td style="text-align: center;"><?= (int) $admin['id']; ?></td>
        <td><?= htmlspecialchars($admin['name']); ?></td>
        <td><?= htmlspecialchars($admin['email']); ?></td>
        <td>
            <?php 
                switch ($admin['role']) {
                    case 'superadmin': echo '👑 Суперадмин'; break;
                    case 'admin': echo '🔧 Администратор'; break;
                    case 'moderator': echo '👀 Модератор'; break;
                    default: echo 'Неизвестно';
                }
            ?>
        </td>
        <td><?= date('d.m.Y H:i', strtotime($admin['created_at'])); ?></td>
        <td>
    <a href="edit_admin.php?id=<?= $admin['id']; ?>">✏ Изменить пароль</a> | 
    <a href="edit_admin_role.php?id=<?= $admin['id']; ?>">🔄 Изменить роль</a> | 
    <a href="delete_admin.php?id=<?= $admin['id']; ?>" onclick="return confirm('Удалить администратора?');">🗑 Удалить</a>
</td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>


✔ Добавление новых админов (add_admin.php)
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Обрабатываем добавление администратора
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);
    header("Location: admins.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить администратора</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>Добавить администратора</h2>
<form method="POST">
    <label>Имя:</label>
    <input type="text" name="name" required><br><br>
    <label>Email:</label>
    <input type="email" name="email" required><br><br>
    <label>Пароль:</label>
    <input type="password" name="password" required><br><br>
    <label>Роль:</label>
    <select name="role">
        <option value="admin">Админ</option>
        <option value="moderator">Модератор</option>
        <option value="superadmin">Суперадмин</option>
    </select><br><br>
    <button type="submit">Добавить</button>
</form>
</body>
</html>


✔ Удаление админов (кроме суперадмина) (delete_admin.php)
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$id = $_GET['id'] ?? 0;
// Запрещаем удалять суперадминистратора (ID = 1)
if ($id == 1) {
    die("Ошибка: Нельзя удалить суперадмина!");
}
$stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
$stmt->execute([$id]);
header("Location: admins.php");
exit();
?>


✔ Смена пароля администратора (edit_admin.php)
Кнопка "Изменить роль" в admins.php
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$admin_id = $_SESSION['admin_id'];
$edit_id = $_GET['id'] ?? 0;
// Получаем данные администратора, которого хотим изменить
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$edit_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
    die("Ошибка: Администратор не найден!");
}
// Запрещаем изменять роль суперадминистратора, если это последний супер
$check_stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'");
$check_stmt->execute();
$superadmin_count = $check_stmt->fetchColumn();

if ($admin['role'] == 'superadmin' && $superadmin_count == 1) {
    die("Ошибка: Нельзя изменить роль последнего суперадмина!");
}
// Обновление роли
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_role = $_POST['role'];
    // Защита: суперадмин может менять любые роли, но обычный админ не может менять суперадмина
    if ($admin_id != 1 && $new_role == 'superadmin') {
        die("Ошибка: Только супер-администратор может назначать суперадмина!");
    }
    $stmt = $conn->prepare("UPDATE admins SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $edit_id]);
    header("Location: admins.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Изменить роль администратора</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>Изменить роль администратора</h2>
<form method="POST">
    <label>Новая роль:</label>
    <select name="role">
        <option value="admin" <?= $admin['role'] == 'admin' ? 'selected' : ''; ?>>Администратор</option>
        <option value="moderator" <?= $admin['role'] == 'moderator' ? 'selected' : ''; ?>>Модератор</option>
        <option value="superadmin" <?= $admin['role'] == 'superadmin' ? 'selected' : ''; ?>>Суперадмин</option>
    </select><br><br>
    <button type="submit">Сохранить</button>
</form>
</body>
</html>


✔ Страница изменения роли (edit_admin_role.php)
✔ Защита от понижения последнего суперадмина
✔ Только superadmin может назначать новых суперадминов
<?php
session_start();
require_once '../database/db.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$admin_id = $_SESSION['admin_id'];
$edit_id = $_GET['id'] ?? 0;
// Получаем данные администратора, которого хотим изменить
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$edit_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$admin) {
    die("Ошибка: Администратор не найден!");
}
// Запрещаем изменять роль суперадминистратора, если это последний супер
$check_stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'");
$check_stmt->execute();
$superadmin_count = $check_stmt->fetchColumn();
if ($admin['role'] == 'superadmin' && $superadmin_count == 1) {
    die("Ошибка: Нельзя изменить роль последнего суперадмина!");
}
// Обновление роли
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_role = $_POST['role'];
    // Защита: суперадмин может менять любые роли, но обычный админ не может менять суперадмина
    if ($admin_id != 1 && $new_role == 'superadmin') {
        die("Ошибка: Только супер-администратор может назначать суперадмина!");
    }
    $stmt = $conn->prepare("UPDATE admins SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $edit_id]);
    header("Location: admins.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Изменить роль администратора</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>Изменить роль администратора</h2>
<form method="POST">
    <label>Новая роль:</label>
    <select name="role">
        <option value="admin" <?= $admin['role'] == 'admin' ? 'selected' : ''; ?>>Администратор</option>
        <option value="moderator" <?= $admin['role'] == 'moderator' ? 'selected' : ''; ?>>Модератор</option>
        <option value="superadmin" <?= $admin['role'] == 'superadmin' ? 'selected' : ''; ?>>Суперадмин</option>
    </select><br><br>
    <button type="submit">Сохранить</button>
</form>
</body>
</html>


✔ Запись действий админов в лог (admin_logs в БД)


✔ Страница logs.php для просмотра истории действий
<?php
session_start();
require_once '../database/db.php';
// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Получаем логи
$stmt = $conn->query("SELECT admin_logs.*, admins.name AS admin_name 
                      FROM admin_logs 
                      JOIN admins ON admin_logs.admin_id = admins.id 
                      ORDER BY admin_logs.created_at DESC");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Логи действий администраторов</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h2>История действий администраторов</h2>
<table border="1">
    <tr>
        <th>Администратор</th>
        <th>Действие</th>
        <th>Дата</th>
    </tr>
    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?= htmlspecialchars($log['admin_name']); ?></td>
        <td><?= htmlspecialchars($log['action']); ?></td>
        <td><?= $log['created_at']; ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>


✅ Фото товаров загружаются, редактируются и отображаются каруселью.
✅ В админке теперь можно добавлять и редактировать изображения.
✅ На странице товара работает карусель с миниатюрами.
Добавлено хеширование имени фото при загрузке (избегаем дубликатов).


🗓 Дата: 19 марта 2025


✅ Добавлены выпадающие списки в add_product.php:

Категория
Подкатегория
Размер
Материал
✅ Созданы страницы для управления:

categories.php – управление категориями
subcategories.php – управление подкатегориями
sizes.php – управление размерами
materials.php – управление материалами
✅ Добавлено:

Форма добавления категорий, подкатегорий, размеров и материалов
Удаление категорий, подкатегорий, размеров и материалов
Ссылки на управление в админке (index.php)
✅ Исправления и улучшения:

Улучшена загрузка изображений в add_product.php
Исправлена обработка выбора подкатегории через AJAX
Оптимизирована проверка загружаемых файлов

✔ Редактирование материалов (edit_material.php)
✔ Редактирование размеров (edit_size.php)
✔ Запрет на дублирование материалов и размеров
✔ Ссылки "Редактировать" в materials.php и sizes.php

✔ Редактирование категорий (edit_category.php)
✔ Редактирование подкатегорий (edit_subcategory.php)
✔ Запрет на дублирование категорий и подкатегорий
✔ Ссылки "Редактировать" в categories.php и subcategories.php

✔ Редактирование категории, подкатегории, размера, материала (выпадающие списки)
✔ Редактирование артикула (SKU)
✔ Удаление и загрузка новых фото (не более 5 изображений)

