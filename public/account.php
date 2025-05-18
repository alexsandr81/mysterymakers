<?php
include 'header.php';
require_once '../database/db.php';
require_once '../includes/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Логирование
file_put_contents('account_log.txt', date('Y-m-d H:i:s') . " - Начало account.php, session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Генерация CSRF-токена
$csrf_token = generateCsrfToken();

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    unset($_SESSION['user_id']);
    unset($_SESSION['cart']);
    setcookie("user_id", "", time() - 3600, "/mysterymakers/");
    header("Location: login.php?error=account_deleted");
    exit();
}

$user_name = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');

// Получаем последний адрес из user_addresses
$stmt = $conn->prepare("SELECT address FROM user_addresses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$address = $stmt->fetch(PDO::FETCH_ASSOC);
$address = $address ? $address['address'] : '';

// Сообщения
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - MysteryMakers</title>
    <!-- <link rel="stylesheet" href="/mysterymakers/assets/css/styles.css"> -->
</head>
<body>

<main>
    <h1>Личный кабинет</h1>
    <p>Добро пожаловать, <?= $user_name; ?>!</p>
    <p><a href="logout.php" class="logout-btn">Выйти</a></p>

    <!-- Навигация по разделам -->
    <nav class="account-nav">
        <a href="account.php" class="account-nav__link active">Профиль</a>
        <a href="favorites.php" class="account-nav__link">Избранное</a>
        <a href="history.php" class="account-nav__link">Заказы</a>
    </nav>

    <!-- Сообщения -->
    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if ($error == 'csrf'): ?>
        <p class="error">Ошибка: недействительный запрос. Попробуйте снова.</p>
    <?php elseif ($error): ?>
        <p class="error"><?= htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Информация о пользователе -->
    <section class="profile-info">
        <h2>Профиль</h2>
        <ul>
            <li><strong>Имя:</strong> <?= htmlspecialchars($user['name']); ?></li>
            <li><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></li>
            <li><strong>Телефон:</strong> <?= htmlspecialchars($user['phone'] ?? 'Не указан'); ?></li>
            <li><strong>Адрес доставки:</strong> <?= htmlspecialchars($address ?: 'Не указан'); ?></li>
        </ul>
        <button type="button" class="edit-profile-btn" onclick="showEditForm()">Редактировать</button>
    </section>

    <!-- Форма редактирования -->
    <section class="profile-form" style="display: none;">
        <h2>Редактировать профиль</h2>
        <form action="update_profile.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
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
            <div class="form-actions">
                <button type="submit">Сохранить</button>
                <button type="button" class="cancel-btn" onclick="hideEditForm()">Отмена</button>
            </div>
        </form>
    </section>
</main>

<?php include 'footer.php'; ?>

<script>
function showEditForm() {
    document.querySelector('.profile-info').style.display = 'none';
    document.querySelector('.profile-form').style.display = 'block';
}

function hideEditForm() {
    document.querySelector('.profile-form').style.display = 'none';
    document.querySelector('.profile-info').style.display = 'block';
}
</script>

</body>
</html>