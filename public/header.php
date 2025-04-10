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
                <li><a href="/mysterymakers/public/catalog.php">Категории</a></li>
                <li><a href="/mysterymakers/public/about.php">О нас</a></li>
                <li><a href="/mysterymakers/public/delivery.php">Доставка</a></li>
                <li><a href="/mysterymakers/public/contact.php">Контакты</a></li>
            </ul>
        </nav>

        <div class="search">
            <form method="GET" action="search.php" class="search-form">
                <input type="text" name="q" placeholder="Поиск..." value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>">
                <button type="submit">Найти</button>
            </form>
        </div>

        <div class="icons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="favorites.php">❤️</a>
            <?php endif; ?>
            <a href="/mysterymakers/public/cart.php">🛒</a>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="/mysterymakers/public/account.php">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Профиль'); ?></a>
                <a href="/mysterymakers/public/logout.php">🚪 Выйти</a>
            <?php else: ?>
                <a href="/mysterymakers/public/login.php">🔑 Войти</a>
            <?php endif; ?>
        </div>
    </header>