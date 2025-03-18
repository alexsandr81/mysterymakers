<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем список администраторов
$stmt = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Функция для красивого отображения ролей
function formatRole($role) {
    $roles = [
        'superadmin' => '<span class="role-superadmin">👑 Суперадмин</span>',
        'admin' => '<span class="role-admin">🔧 Администратор</span>',
        'moderator' => '<span class="role-moderator">🛠️ Модератор</span>',
    ];
    return $roles[$role] ?? '<span class="role-unknown">❓ Неизвестно</span>';
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление администраторами</title>
    <link rel="stylesheet" href="/mysterymakers/admin/styles.css">
</head>
<body>

<h2>Администраторы</h2>
<a href="add_admin.php">➕ Добавить администратора</a>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Email</th>
        <th>Роль</th>
        <th>Дата создания</th>
        <th>Действия</th>
    </tr>

    <?php foreach ($admins as $admin): ?>
    <tr>
        <td style="text-align: center;"><?= (int) $admin['id']; ?></td>
        <td><?= htmlspecialchars($admin['name']); ?></td>
        <td><?= htmlspecialchars($admin['email']); ?></td>
        <td>
            <?php 
                switch ($admin['role']) {
                    case 'superadmin': echo '👑 Суперадмин'; break;
                    case 'admin': echo '🔧 Администратор'; break;
                    case 'moderator': echo '👀 Модератор'; break;
                    default: echo 'Неизвестно';
                }
            ?>
        </td>
        <td><?= date('d.m.Y H:i', strtotime($admin['created_at'])); ?></td>
        <td>
    <a href="edit_admin.php?id=<?= $admin['id']; ?>">✏ Изменить пароль</a> | 
    <a href="edit_admin_role.php?id=<?= $admin['id']; ?>">🔄 Изменить роль</a> | 
    <a href="delete_admin.php?id=<?= $admin['id']; ?>" onclick="return confirm('Удалить администратора?');">🗑 Удалить</a>
</td>

    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
