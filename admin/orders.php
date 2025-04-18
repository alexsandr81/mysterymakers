<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем список заказов
$stmt = $conn->query("
    SELECT orders.*, users.name AS user_name 
    FROM orders 
    LEFT JOIN users ON orders.user_id = users.id 
    ORDER BY orders.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление заказами</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Заказы</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Номер заказа</th>
            <th>Покупатель</th>
            <th>Сумма</th>
            <th>Статус</th>
            <th>Дата</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= $order['id']; ?></td>
                <td><?= htmlspecialchars($order['order_number']); ?></td>
                <td>
                    <?= htmlspecialchars($order['delivery_name'] ?: ($order['user_name'] ?: 'Гость')); ?>
                    <?php if ($order['user_id'] && $order['delivery_name'] !== $order['user_name']): ?>
                        (Пользователь: <?= htmlspecialchars($order['user_name']); ?>)
                    <?php endif; ?>
                </td>
                <td><?= number_format($order['total_price'], 2, '.', ''); ?> ₽</td>
                <td>
                    <form method="POST" action="update_order_status.php">
                        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                        <select name="status" class="status-<?= strtolower(str_replace(' ', '-', $order['status'])); ?>" onchange="this.form.submit()">
                            <option value="Новый" <?= $order['status'] == 'Новый' ? 'selected' : ''; ?>>Новый</option>
                            <option value="В обработке" <?= $order['status'] == 'В обработке' ? 'selected' : ''; ?>>В обработке</option>
                            <option value="Отправлен" <?= $order['status'] == 'Отправлен' ? 'selected' : ''; ?>>Отправлен</option>
                            <option value="Доставлен" <?= $order['status'] == 'Доставлен' ? 'selected' : ''; ?>>Доставлен</option>
                            <option value="Отменён" <?= $order['status'] == 'Отменён' ? 'selected' : ''; ?>>Отменён</option>
                        </select>
                    </form>
                </td>
                <td><?= $order['created_at']; ?></td>
                <td>
                    <a href="order_details.php?id=<?= $order['id']; ?>">📄 Подробнее</a>
                    <a href="delete_order.php?id=<?= $order['id']; ?>" onclick="return confirm('Вы уверены, что хотите удалить этот заказ?');" class="text-danger">❌ Удалить</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>