📌 Документация проекта: MysteryMakers
📁 Файл: C:\xampp\htdocs\mysterymakers\docs\project_documentation.md

1. Установка и настройка окружения
✅ Установлен XAMPP

Версия: 8.2.4
Установлены компоненты: Apache, MySQL, PHP, phpMyAdmin
Путь установки: C:\xampp
Запуск панели управления: C:\xampp\xampp-control.exe
✅ Настроены конфигурационные файлы
php.ini (C:\xampp\php\php.ini)

ini
Копировать
Редактировать
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
display_errors = On
my.ini (C:\xampp\mysql\bin\my.ini)

ini
Копировать
Редактировать
max_connections = 200
innodb_buffer_pool_size = 256M
Перезапущены Apache и MySQL.

✅ Создана структура проекта

plaintext
Копировать
Редактировать
C:\xampp\htdocs\mysterymakers\
├── public\       # Фронтенд (главная, каталог, корзина)
├── assets\       # Стили, изображения, JS
├── config\       # Файлы конфигурации
├── database\     # Подключение к БД
├── docs\         # Документация проекта
✅ Настроено подключение к базе данных (config.php)

php
Копировать
Редактировать
<?php
return [
    'db_host' => 'localhost',
    'db_name' => 'mysterymakers_db',
    'db_user' => 'root',
    'db_pass' => '',
];
?>
✅ Создана база данных mysterymakers_db

sql
Копировать
Редактировать
CREATE DATABASE mysterymakers_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
✅ Настроено подключение к базе (db.php)

php
Копировать
Редактировать
<?php
$config = include(__DIR__ . '/../config/config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8",
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>
2. Реализованные страницы
🔹 Главная (index.php)
✅ Добавлены блоки:

Шапка (header.php): Логотип, меню, поиск, корзина
Основной контент: Баннер, акции, отзывы
Подвал (footer.php): Контакты, соцсети
🔗 Проверка: http://localhost/mysterymakers/public/index.php

🔹 Каталог (catalog.php)
✅ Создана таблица товаров в БД (products)

sql
Копировать
Редактировать
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(100),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
✅ Добавлен динамический вывод товаров из БД
✅ Реализована фильтрация по категориям и сортировка
🔗 Проверка: http://localhost/mysterymakers/public/catalog.php

🔹 Страница товара (product.php)
✅ Вывод информации о товаре
✅ Галерея изображений + зум-эффект
✅ Кнопки "Добавить в корзину" и "В избранное"
✅ Отзывы (заглушка, без формы добавления)
🔗 Проверка: http://localhost/mysterymakers/public/product.php?id=1

🔹 Корзина (cart.php)
✅ Добавление товаров в корзину (через сессии)
✅ Удаление товаров из корзины
✅ AJAX-обновление корзины
✅ Подсчет итоговой суммы заказа

🔗 Проверка: http://localhost/mysterymakers/public/cart.php

✅ Файл добавления в корзину (add_to_cart.php)

php
Копировать
Редактировать
<?php
session_start();
$id = $_POST['id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id] += $quantity;
} else {
    $_SESSION['cart'][$id] = $quantity;
}

echo json_encode(['status' => 'success', 'cart_count' => array_sum($_SESSION['cart'])]);
?>
✅ Файл удаления товара (remove_from_cart.php)

php
Копировать
Редактировать
<?php
session_start();
$id = $_POST['id'] ?? 0;

if (isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

echo json_encode(['status' => 'success']);
?>
✅ AJAX-добавление в корзину (JS в product.php)

html
Копировать
Редактировать
<script>
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
3. Итог
✅ Развернута инфраструктура проекта
✅ Настроена база данных
✅ Созданы основные страницы магазина
✅ Реализована корзина (AJAX, сессии)
✅ Добавлены стили и базовая функциональность

📌 Следующие шаги (TODO):

🔜 Оформление заказа (checkout.php)
🔜 Добавление отзывов и рейтинга
🔜 Реализация личного кабинета
🔜 Админ-панель (управление товарами, заказами)
