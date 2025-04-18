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

$id = $_GET['id'] ?? 0;

// Получаем данные администратора
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Администратор не найден!");
}

// Обновление пароля
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $stmt->execute([$password, $id]);

    header("Location: admins.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Изменить пароль администратора</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Изменить пароль администратора</h2>

<form method="POST">
    <label>Новый пароль:</label>
    <input type="password" name="password" required><br><br>

    <button type="submit">Сохранить</button>
</form>

</body>
</html>
