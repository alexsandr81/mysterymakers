<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

// Обрабатываем добавление администратора
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);

    header("Location: admins.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить администратора</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Добавить администратора</h2>

<form method="POST">
    <label>Имя:</label>
    <input type="text" name="name" required><br><br>

    <label>Email:</label>
    <input type="email" name="email" required><br><br>

    <label>Пароль:</label>
    <input type="password" name="password" required><br><br>

    <label>Роль:</label>
    <select name="role">
        <option value="admin">Админ</option>
        <option value="moderator">Модератор</option>
        <option value="superadmin">Суперадмин</option>
    </select><br><br>

    <button type="submit">Добавить</button>
</form>

</body>
</html>
