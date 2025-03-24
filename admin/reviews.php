<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Фильтрация отзывов
$status_filter = $_GET['status'] ?? '';
$query = "SELECT r.*, u.name AS user_name, p.name AS product_name 
          FROM reviews r
          JOIN users u ON r.user_id = u.id
          JOIN products p ON r.product_id = p.id";
if ($status_filter) {
    $query .= " WHERE r.status = ?";
}
$query .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($query);
if ($status_filter) {
    $stmt->execute([$status_filter]);
} else {
    $stmt->execute();
}
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Модерация отзывов</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Модерация отзывов</h2>

<!-- Фильтр -->
<form method="GET">
    <label>Фильтр:</label>
    <select name="status" onchange="this.form.submit()">
        <option value="">Все</option>
        <option value="pending" <?= ($status_filter == 'pending') ? 'selected' : ''; ?>>На модерации</option>
        <option value="approved" <?= ($status_filter == 'approved') ? 'selected' : ''; ?>>Одобренные</option>
        <option value="rejected" <?= ($status_filter == 'rejected') ? 'selected' : ''; ?>>Отклонённые</option>
    </select>
</form>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Пользователь</th>
        <th>Товар</th>
        <th>Рейтинг</th>
        <th>Комментарий</th>
        <th>Дата</th>
        <th>Статус</th>
        <th>Действия</th>
    </tr>

    <?php foreach ($reviews as $review): ?>
    <tr>
        <td><?= $review['id']; ?></td>
        <td><?= htmlspecialchars($review['user_name']); ?></td>
        <td><?= htmlspecialchars($review['product_name']); ?></td>
        <td><?= $review['rating']; ?> ⭐</td>
        <td><?= htmlspecialchars($review['comment']); ?></td>
        <td><?= $review['created_at']; ?></td>
        <td>
            <?= ($review['status'] == 'pending') ? '⏳ На модерации' : (($review['status'] == 'approved') ? '✅ Одобрен' : '❌ Отклонён'); ?>
        </td>
        <td>
            <?php if ($review['status'] == 'pending'): ?>
                <a href="approve_review.php?id=<?= $review['id']; ?>">✅ Одобрить</a> | 
                <a href="reject_review.php?id=<?= $review['id']; ?>">❌ Отклонить</a>
            <?php endif; ?>
            | <a href="delete_review.php?id=<?= $review['id']; ?>" onclick="return confirm('Удалить отзыв?');">🗑 Удалить</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
