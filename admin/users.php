<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем список пользователей
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Пользователи</h2>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Email</th>
        <th>Статус</th>
        <th>Дата регистрации</th>
        <th>Действия</th>
    </tr>

    <?php foreach ($users as $user): ?>
    <tr>
        <td><?= $user['id']; ?></td>
        <td><?= htmlspecialchars($user['name']); ?></td>
        <td><?= htmlspecialchars($user['email']); ?></td>
        <td><?= $user['status'] == 'active' ? '✅ Активен' : '❌ Заблокирован'; ?></td>
        <td><?= $user['created_at']; ?></td>
        <td>
            <?php if ($user['status'] == 'active'): ?>
                <a href="block_user.php?id=<?= $user['id']; ?>">🚫 Заблокировать</a>
            <?php else: ?>
                <a href="unblock_user.php?id=<?= $user['id']; ?>">🔓 Разблокировать</a>
            <?php endif; ?>
            |
            <a href="delete_user.php?id=<?= $user['id']; ?>" onclick="return confirm('Удалить пользователя?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
