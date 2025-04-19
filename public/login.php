<?php
session_start();
require_once '../database/db.php';

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit();
}

$error = $_GET['error'] ?? '';
$error_messages = [
    'empty_fields' => 'Пожалуйста, заполните все поля.',
    'account_deleted' => 'Ваш аккаунт удалён.',
    'account_blocked' => 'Ваш аккаунт заблокирован.',
    'invalid_credentials' => 'Неверный email или пароль.'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - MysteryMakers</title>
    <link rel="stylesheet" href="/mysterymakers/assets/styles.css">
</head>
<body>

<h2>Вход</h2>

<?php if ($error && isset($error_messages[$error])): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error_messages[$error]); ?></p>
<?php endif; ?>

<form action="login_process.php" method="POST">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <br>
    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <label for="remember">
        <input type="checkbox" id="remember" name="remember"> Запомнить меня
    </label>
    <br>
    <button type="submit">Войти</button>
</form>

<p><a href="register.php">Регистрация</a></p>
<p><a href="forgot_password.php">Забыли пароль?</a></p>

</body>
</html>