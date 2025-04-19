<?php
session_start();
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']); // Запомнить меня

    if (empty($email) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit();
    }

    // Проверяем, существует ли пользователь
    $stmt = $conn->prepare("SELECT id, name, password, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Пользователь не найден (удалён или неверный email)
        setcookie("user_id", "", time() - 3600, "/mysterymakers/"); // Очищаем куки
        header("Location: login.php?error=account_deleted");
        exit();
    }

    if ($user['status'] === 'blocked') {
        // Пользователь заблокирован
        setcookie("user_id", "", time() - 3600, "/mysterymakers/"); // Очищаем куки
        header("Location: login.php?error=account_blocked");
        exit();
    }

    if (password_verify($password, $user['password'])) {
        // Успешный вход
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        // Запоминаем пользователя, если выбрано "Запомнить меня"
        if ($remember) {
            setcookie("user_id", $user['id'], time() + (86400 * 30), "/mysterymakers/"); // 30 дней
        }

        header("Location: account.php");
        exit();
    } else {
        // Неверный пароль
        header("Location: login.php?error=invalid_credentials");
        exit();
    }
}
?>