<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <!-- Подключаем Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .list-group a {
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container admin-container">
    <h2>Админ-панель</h2>
    <ul class="list-group">
        <li class="list-group-item"><a href="products.php">📦 Товары</a></li>
        <li class="list-group-item"><a href="orders.php">🛒 Заказы</a></li>
        <li class="list-group-item"><a href="users.php">👥 Пользователи</a></li>
        <li class="list-group-item"><a href="admins.php">🛠️ Администраторы</a></li>
        <li class="list-group-item"><a href="logs.php">📜 Логи действий</a></li>
        <li class="list-group-item text-danger"><a href="logout.php">🚪 Выйти</a></li>
    </ul>
</div>

<!-- Подключаем Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
