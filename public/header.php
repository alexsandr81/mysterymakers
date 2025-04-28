<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../database/db.php';
require_once '../includes/security.php';

// Генерация CSRF-токена
$csrf_token = generateCsrfToken();

// Проверяем куки для "Запомнить меня"
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $user_id = intval($_COOKIE['user_id']);
    $stmt = $conn->prepare("SELECT id, name, status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['status'] === 'active') {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
    } else {
        setcookie("user_id", "", time() - 3600, "/mysterymakers/");
    }
}

// Проверяем статус пользователя
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || $user['status'] === 'blocked') {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['cart']);
        setcookie("user_id", "", time() - 3600, "/mysterymakers/");
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
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/mysterymakers/public/favorites.php">❤️</a>
            <a href="/mysterymakers/public/cart.php" class="cart-icon">
                🛒 <span id="cart-count"><?= array_sum($_SESSION['cart'] ?? []); ?></span>
            </a>
            <a href="/mysterymakers/public/account.php">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Профиль'); ?></a>
            <a href="/mysterymakers/public/logout.php">🚪 Выйти</a>
        <?php else: ?>
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