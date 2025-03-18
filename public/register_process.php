<?php
session_start();
require_once '../database/db.php';  // Подключаем базу данных

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);  // Хешируем пароль

    // Проверяем, есть ли уже такой email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        die("Ошибка: пользователь с таким email уже существует!");
    }

    // Добавляем пользователя
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password]);

    $_SESSION['user_id'] = $conn->lastInsertId();
    $_SESSION['user_name'] = $name;

    header("Location: account.php");
    exit();
}
?>
