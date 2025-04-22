<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $current_password = trim($_POST['current_password'] ?? '');

    // Валидация
    if (empty($name) || empty($email) || empty($current_password)) {
        header("Location: account.php?error=Заполните все обязательные поля");
        exit();
    }

    // Валидация телефона
    if ($phone && !preg_match('/^\+380[0-9]{9}$/', $phone)) {
        header("Location: account.php?error=Неверный формат телефона. Пример: +380123456789");
        exit();
    }

    // Валидация нового пароля
    if ($password && !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,}$/', $password)) {
        header("Location: account.php?error=Пароль должен содержать минимум 8 символов, включая буквы и цифры");
        exit();
    }

    // Проверяем текущий пароль
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!password_verify($current_password, $user['password'])) {
        header("Location: account.php?error=Неверный текущий пароль");
        exit();
    }

    // Проверяем уникальность email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        header("Location: account.php?error=Этот email уже занят");
        exit();
    }

    // Обновляем профиль
    $query = "UPDATE users SET name = ?, email = ?, phone = ?";
    $params = [$name, $email, $phone ?: null];
    if (!empty($password)) {
        $query .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $query .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    // Обновляем адрес
    if ($address) {
        $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address) VALUES (?, ?)");
        $stmt->execute([$user_id, $address]);
    }

    // Обновляем сессию
    $_SESSION['user_name'] = $name;

    header("Location: account.php?success=Профиль успешно обновлён");
    exit();
}

header("Location: account.php");
exit();
?>