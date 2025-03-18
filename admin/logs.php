<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем логи
$stmt = $conn->query("SELECT admin_logs.*, admins.name AS admin_name 
                      FROM admin_logs 
                      JOIN admins ON admin_logs.admin_id = admins.id 
                      ORDER BY admin_logs.created_at DESC");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Логи действий администраторов</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>История действий администраторов</h2>

<table border="1">
    <tr>
        <th>Администратор</th>
        <th>Действие</th>
        <th>Дата</th>
    </tr>

    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?= htmlspecialchars($log['admin_name']); ?></td>
        <td><?= htmlspecialchars($log['action']); ?></td>
        <td><?= $log['created_at']; ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
