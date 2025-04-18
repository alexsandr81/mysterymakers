<?php
session_start();
require_once '../database/db.php';

// Проверяем, авторизован ли админ
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем ID заказа
$order_id = intval($_GET['id'] ?? 0);

// Загружаем информацию о заказе
$stmt = $conn->prepare("
    SELECT orders.*, users.name AS user_name, users.email 
    FROM orders 
    LEFT JOIN users ON orders.user_id = users.id 
    WHERE orders.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("❌ Заказ не найден!");
}

// Загружаем товары в заказе
$stmt = $conn->prepare("
    SELECT oi.quantity, oi.price AS final_price, p.name, p.price AS original_price, p.category_id,
           COALESCE(
               (SELECT MAX(d.discount_value) FROM discounts d 
                WHERE d.product_id = p.id 
                  AND (d.start_date IS NULL OR d.start_date <= ?) 
                  AND (d.end_date IS NULL OR d.end_date >= ?)),
               (SELECT MAX(d.discount_value) FROM discounts d 
                WHERE d.category_id = p.category_id 
                  AND (d.start_date IS NULL OR d.start_date <= ?) 
                  AND (d.end_date IS NULL OR d.end_date >= ?))
           ) AS discount_value,
           (SELECT d.discount_type FROM discounts d 
            WHERE (d.product_id = p.id OR d.category_id = p.category_id) 
              AND (d.start_date IS NULL OR d.start_date <= ?) 
              AND (d.end_date IS NULL OR d.end_date >= ?)
            ORDER BY d.discount_value DESC LIMIT 1) AS discount_type
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order['created_at'], $order['created_at'], $order['created_at'], $order['created_at'], $order['created_at'], $order['created_at'], $order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Детали заказа</title>
    <link rel="stylesheet" href="/mysterymakers/admin/admin.css">
</head>
<body>
    <h2>Детали заказа #<?= htmlspecialchars($order['order_number']); ?></h2>
    <p><strong>Покупатель:</strong> 
        <?= htmlspecialchars($order['delivery_name'] ?: ($order['user_name'] ?: 'Гость')); ?>
        <?php if ($order['user_id']): ?>
            (<?= htmlspecialchars($order['email']); ?>)
            <?php if ($order['delivery_name'] !== $order['user_name']): ?>
                , Пользователь: <?= htmlspecialchars($order['user_name']); ?>
            <?php endif; ?>
        <?php endif; ?>
    </p>
    <p><strong>Сумма:</strong> <?= number_format($order['total_price'], 2, '.', ''); ?> ₽</p>
    <p><strong>Статус:</strong> <?= htmlspecialchars($order['status']); ?></p>
    <p><strong>Дата:</strong> <?= $order['created_at']; ?></p>
    <h3>Товары в заказе</h3>
    <?php if (!empty($items)): ?>
        <table class="table">
            <tr>
                <th>Название</th>
                <th>Количество</th>
                <th>Цена (без скидки)</th>
                <th>Скидка</th>
                <th>Итоговая цена</th>
                <th>Сумма</th>
            </tr>
            <?php foreach ($items as $item): ?>
                <?php
                $original_price = $item['original_price'];
                $final_price = $item['final_price'];
                $discount_value = $item['discount_value'] ?? 0;
                $subtotal = $final_price * $item['quantity'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']); ?></td>
                    <td><?= htmlspecialchars($item['quantity']); ?></td>
                    <td><?= number_format($original_price, 2, '.', ''); ?> ₽</td>
                    <td>
                        <?php if ($discount_value): ?>
                            <?= $item['discount_type'] == 'fixed' ? number_format($discount_value, 2, '.', '') . ' ₽' : $discount_value . '%'; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($final_price, 2, '.', ''); ?> ₽</td>
                    <td><?= number_format($subtotal, 2, '.', ''); ?> ₽</td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>❌ В этом заказе нет товаров.</p>
    <?php endif; ?>
    <a href="orders.php" class="back-button">⬅ Назад</a>
</body>
</html>