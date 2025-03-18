
<?php
session_start();
require_once '../database/db.php'; // Подключаем базу данных

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $reset_token = bin2hex(random_bytes(32)); // Генерируем токен
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE email = ?");
        $stmt->execute([$reset_token, $email]);

        $reset_link = "http://localhost/mysterymakers/public/reset_password.php?token=$reset_token";
        echo "<p>Перейдите по ссылке, чтобы сбросить пароль: <a href='$reset_link'>$reset_link</a></p>";
    } else {
        echo "<p style='color:red;'>Email не найден!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля</title>
</head>
<body>

<h2>Восстановление пароля</h2>

<form method="POST" action="">
    <label>Email:</label>
    <input type="email" name="email" required><br><br>
    <button type="submit">Отправить ссылку</button>
</form>

</body>
</html>
<?php include 'footer.php'; ?>