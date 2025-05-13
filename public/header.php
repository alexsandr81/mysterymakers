<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../database/db.php';
require_once '../includes/security.php';

// Генерация CSRF-токена
$csrf_token = generateCsrfToken();

// Проверяем куки для "Запомнить меня"
if (!isset($_SESSION['user_id']) && !isset($_SESSION['logged_out']) && isset($_COOKIE['user_id'])) {
    $user_id = intval($_COOKIE['user_id']);
    $stmt = $conn->prepare("SELECT id, name, status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['status'] === 'active') {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        unset($_SESSION['logged_out']);
    } else {
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
    <!-- <link rel="stylesheet" href="/mysterymakers/assets/css/styles.css"> -->
</head>
<body>
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
            <input class="search_form" type="text" name="q" placeholder="Поиск по названию, артикулу..." value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>">
            <button type="submit">Найти</button>
        </form>
    </div>

    <div class="icons">
        <?php
        $is_guest = !isset($_SESSION['user_id']) || isset($_SESSION['logged_out']);
        $cart_count = $is_guest ? array_sum($_SESSION['guest_cart'] ?? []) : array_sum($_SESSION['cart'] ?? []);
        ?>
        <?php if (!$is_guest): ?>
            <a href="/mysterymakers/public/favorites.php">❤️</a>
            <a href="/mysterymakers/public/cart.php" class="cart-icon">
                🛒 <span id="cart-count"><?= $cart_count; ?></span>
            </a>
            <a href="/mysterymakers/public/account.php">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Профиль'); ?></a>
            <a href="/mysterymakers/public/logout.php">🚪 Выйти</a>
        <?php else: ?>
            <a href="javascript:void(0)" onclick="alert('Пожалуйста, авторизуйтесь для доступа к избранному')">❤️</a>
            <a href="/mysterymakers/public/cart.php" class="cart-icon">
                🛒 <span id="cart-count"><?= $cart_count; ?></span>
            </a>
            <a href="/mysterymakers/public/login.php">🔑 Войти</a>
        <?php endif; ?>
    </div>
</header>

<script>
function updateCartCount() {
    fetch('/mysterymakers/public/get_cart_count.php')
        .then(res => res.json())
        .then(data => {
            const countEl = document.getElementById('cart-count');
            if (countEl) countEl.textContent = data.count;
        });
}
document.addEventListener('DOMContentLoaded', updateCartCount);
</script>