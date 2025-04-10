<?php
session_start();
require_once '../database/db.php';
include 'header.php';
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["token"])) {
    $token = trim($_GET["token"]);

    // Проверяем, существует ли токен в базе
    $stmt = $conn->prepare("SELECT email FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Неверный или истёкший токен.");
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["token"], $_POST["password"])) {
    $token = trim($_POST["token"]);
    $password = trim($_POST["password"]);

    // Валидация пароля
    if (strlen($password) < 8) {
        die("Пароль должен содержать минимум 8 символов!");
    }

    $new_password = password_hash($password, PASSWORD_DEFAULT);

    // Обновляем пароль в базе
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL WHERE reset_token = ?");
    $stmt->execute([$new_password, $token]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = "Пароль успешно изменён!";
        header("Location: login.php");
        exit();
    } else {
        echo "Ошибка сброса пароля. Возможно, токен недействителен.";
    }

    exit();
} else {
    die("Недействительный запрос.");
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля</title>
</head>
<body>

<h2>Сброс пароля</h2>

<form method="POST">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
    
    <label>Новый пароль:</label>
    <input type="password" name="password" required minlength="8"><br><br>

    <button type="submit">Сменить пароль</button>
</form>

</body>
</html>