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

