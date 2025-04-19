<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
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
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>

<h2>История действий администраторов</h2>

<?php if (empty($logs)): ?>
    <p>Логи отсутствуют.</p>
<?php else: ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Администратор</th>
            <th>Действие</th>
            <th>Подробности</th>
            <th>Дата</th>
        </tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= htmlspecialchars($log['id']); ?></td>
            <td><?= htmlspecialchars($log['admin_name']); ?></td>
            <td><?= htmlspecialchars($log['action']); ?></td>
            <td><?= htmlspecialchars($log['details'] ?? ''); ?></td>
            <td><?= htmlspecialchars($log['created_at']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<a href="index.php">Назад</a>

</body>
</html>