<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../database/db.php'; 
include 'header.php'; 

// Проверяем, вошел ли пользователь
if (isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit();
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

<?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_credentials'): ?>
    <p style='color:red;'>Неверный email или пароль.</p>
<?php endif; ?>

<?php if (isset($_SESSION['message'])): ?>
    <p style="color:green;"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
<?php endif; ?>

<form method="POST" action="login_process.php">
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