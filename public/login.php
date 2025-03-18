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
