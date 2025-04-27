<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Проверка CSRF-токена
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    header("Location: account.php?error=csrf");
    exit();
}

// Регенерация CSRF-токена
generateCsrfToken();

$user_id = $_SESSION['user_id'];
$errors = [];

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$new_password = $_POST['password'] ?? '';
$current_password = $_POST['current_password'] ?? '';

// Валидация
if (empty($name)) {
    $errors[] = "Имя обязательно.";
} elseif (strlen($name) > 255) {
    $errors[] = "Имя слишком длинное.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Введите корректный email.";
} elseif (strlen($email) > 100) {
    $errors[] = "Email слишком длинный.";
} else {
    // Проверка уникальности email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = "Этот email уже используется.";
    }
}

if ($phone && !preg_match('/^\+?\d{10,12}$/', $phone)) {
    $errors[] = "Телефон должен содержать 10–12 цифр.";
}

if (empty($current_password)) {
    $errors[] = "Введите текущий пароль.";
} else {
    // Проверка текущего пароля
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!password_verify($current_password, $user['password'])) {
        $errors[] = "Неверный текущий пароль.";
    }
}

// Если есть ошибки, перенаправляем обратно
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    header("Location: account.php");
    exit();
}

try {
    // Обновление пользователя
    $update_fields = ["name = ?", "email = ?"];
    $params = [$name, $email];

    if ($phone) {
        $update_fields[] = "phone = ?";
        $params[] = $phone;
    } else {
        $update_fields[] = "phone = NULL";
    }

    if ($new_password) {
        $update_fields[] = "password = ?";
        $params[] = password_hash($new_password, PASSWORD_BCRYPT);
    }

    $params[] = $user_id;
    $stmt = $conn->prepare("UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?");
    $stmt->execute($params);

    // Обновление адреса
    if ($address) {
        $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address) VALUES (?, ?)");
        $stmt->execute([$user_id, $address]);
    }

    // Обновляем сессию
    $_SESSION['user_name'] = $name;

    header("Location: account.php?success=Профиль успешно обновлен");
} catch (PDOException $e) {
    $_SESSION['form_errors'] = ["Ошибка базы данных: " . $e->getMessage()];
    header("Location: account.php");
}
exit();