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

// Фильтры
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';

$query = "SELECT * FROM users WHERE 1";

if ($status_filter) {
    $query .= " AND status = :status";
}

if ($date_filter) {
    $query .= " AND created_at >= :date";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);

if ($status_filter) {
    $stmt->bindParam(':status', $status_filter);
}

if ($date_filter) {
    $stmt->bindParam(':date', $date_filter);
}

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="/mysterymakers/admin/styles.css">
</head>
<body>

<h2>Пользователи</h2>

<!-- Фильтры -->
<form method="GET">
    <label>Фильтр по статусу:</label>
    <select name="status">
        <option value="">Все</option>
        <option value="active" <?= $status_filter == 'active' ? 'selected' : ''; ?>>Активные</option>
        <option value="blocked" <?= $status_filter == 'blocked' ? 'selected' : ''; ?>>Заблокированные</option>
    </select>

    <label>Дата регистрации с:</label>
    <input type="date" name="date" value="<?= $date_filter; ?>">

    <button type="submit">Фильтровать</button>
</form>

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
                <a href="block_user.php?id=<?= $user['id']; ?>&action=unblock">🔓 Разблокировать</a>
            <?php endif; ?>
            |
            <a href="delete_user.php?id=<?= $user['id']; ?>" onclick="return confirm('Удалить пользователя?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
