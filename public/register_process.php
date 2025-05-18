<?php
session_start();
require_once '../database/db.php';
require_once '../admin/log_action.php'; // Подключаем функцию логирования

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit();
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

$errors = [];

if (empty($name)) {
    $errors['name'] = 'Имя обязательно';
} elseif (strlen($name) > 255) {
    $errors['name'] = 'Имя слишком длинное';
}

if (empty($email)) {
    $errors['email'] = 'Email обязателен';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email';
} elseif (strlen($email) > 255) {
    $errors['email'] = 'Email слишком длинный';
}

if (empty($password)) {
    $errors['password'] = 'Пароль обязателен';
} elseif (strlen($password) < 6) {
    $errors['password'] = 'Пароль должен быть не короче 6 символов';
}

if ($password !== $password_confirm) {
    $errors['password_confirm'] = 'Пароли не совпадают';
}

if (empty($errors)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors['email'] = 'Пользователь с таким email уже существует';
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = [
        'name' => $name,
        'email' => $email
    ];
    header("Location: register.php");
    exit();
}

try {
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password_hashed]);

    $_SESSION['user_id'] = $conn->lastInsertId();
    $_SESSION['user_name'] = $name;

    // Логируем регистрацию (используем admin_id из сессии или системный ID)
    $admin_id = $_SESSION['admin_id'] ?? 1; // Если админ авторизован, берём его ID, иначе системный (ID 1)
    logAdminAction($conn, $admin_id, 'user_registered', "Пользователь $email зарегистрирован");

    unset($_SESSION['form_errors'], $_SESSION['form_data']);

    header("Location: index.php");
    exit();
} catch (PDOException $e) {
    $errors['general'] = 'Ошибка при регистрации: ' . $e->getMessage();
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = [
        'name' => $name,
        'email' => $email
    ];
    header("Location: register.php");
    exit();
}
?>