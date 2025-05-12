<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../database/db.php';
require_once '../includes/security.php';

// Генерация CSRF-токена
$csrf_token = generateCsrfToken();

// Временная отладка: выводим состояние сессии и куки
$debug_info = [
    'session_user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set',
    'session_logged_out' => isset($_SESSION['logged_out']) ? $_SESSION['logged_out'] : 'not set',
    'cookie_user_id' => isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : 'not set'
];

// Проверяем куки для "Запомнить меня" только если пользователь не выходил явно
if (!isset($_SESSION['user_id']) && !isset($_SESSION['logged_out']) && isset($_COOKIE['user_id'])) {
    $user_id = intval($_COOKIE['user_id']);
    $stmt = $conn->prepare("SELECT id, name, status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['status'] === 'active') {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        unset($_SESSION['logged_out']); // Сбрасываем флаг выхода
    } else {
        // Очищаем куки и сессию
        setcookie("user_id", "", time() - 3600, "/mysterymakers/", "", false, true);
        setcookie("user_id", "", time() - 3600, "/mysterymakers/");
        setcookie("user_id", "", time() - 3600, "/");
        $_SESSION = [];
        session_destroy();
    }
}

// Проверяем статус пользователя
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['status'] === 'blocked') {
        $_SESSION = [];
        setcookie("user_id", "", time() - 3600, "/mysterymakers/", "", false, true);
        setcookie("user_id", "", time() - 3600, "/mysterymakers/");
        setcookie("user_id", "", time() - 3600, "/");
        session_destroy();
        header("Location: /mysterymakers/public/login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>MysteryMakers</title>
    <link rel="stylesheet" href="/mysterymakers/assets/styles.css">
</head>
<body>

<!-- Временная отладка: выводим состояние -->
<!-- <div style="background: #fff; padding: 10px; border: 1px solid #ccc; margin-bottom: 10px;">
    <strong>Debug Info:</strong><br>
    Session user_id: <?= htmlspecialchars($debug_info['session_user_id']); ?><br>
    Session logged_out: <?= htmlspecialchars($debug_info['session_logged_out']); ?><br>
    Cookie user_id: <?= htmlspecialchars($debug_info['cookie_user_id']); ?>
</div> -->

<header>
    <div class="logo">
        <a href="/mysterymakers/public/index.php">
            <img src="/mysterymakers/public/assets/logo.png" alt="MysteryMakers">
        </a>
    </div>

    <nav>
        <ul>
            <li><a href="/mysterymakers/public/catalog.php">Категории</a></li>
            <li><a href="/mysterymakers/public/about.php">О нас</a></li>
            <li><a href="/mysterymakers/public/delivery.php">Доставка</a></li>
            <li><a href="/mysterymakers/public/contact.php">Контакты</a></li>
        </ul>
    </nav>

    <div class="search">
        <form method="GET" action="/mysterymakers/public/search.php" class="search-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
            <input type="text" name="q" placeholder="Поиск по названию, артикулу..." value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>">
            <button type="submit">Найти</button>
        </form>
    </div>

    <div class="icons">
        <?php if (isset($_SESSION['user_id']) && !isset($_SESSION['logged_out'])): ?>
            <a href="/mysterymakers/public/favorites.php">❤️</a>
            <a href="/mysterymakers/public/cart.php" class="cart-icon">
                🛒 <span id="cart-count"><?= array_sum($_SESSION['cart'] ?? []); ?></span>
            </a>
            <a href="/mysterymakers/public/account.php">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Профиль'); ?></a>
            <a href="/mysterymakers/public/logout.php">🚪 Выйти</a>
        <?php else: ?>
            <a href="javascript:void(0)" onclick="alert('Пожалуйста, авторизуйтесь для доступа к избранному')">❤️</a>
            <a href="javascript:void(0)" onclick="alert('Пожалуйста, авторизуйтесь для доступа к корзине')" class="cart-icon">
                🛒 <span id="cart-count">0</span>
            </a>
            <a href="/mysterymakers/public/login.php">🔑 Войти</a>
        <?php endif; ?>
    </div>
</header>

<script>
function updateCartCount() {
    <?php if (isset($_SESSION['user_id']) && !isset($_SESSION['logged_out'])): ?>
        fetch('/mysterymakers/public/get_cart_count.php')
            .then(res => res.json())
            .then(data => {
                const countEl = document.getElementById('cart-count');
                if (countEl) countEl.textContent = data.count;
            });
    <?php else: ?>
        const countEl = document.getElementById('cart-count');
        if (countEl) countEl.textContent = '0';
    <?php endif; ?>
}
document.addEventListener('DOMContentLoaded', updateCartCount);
</script>