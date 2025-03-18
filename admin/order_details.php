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
    JOIN users ON orders.user_id = users.id 
    WHERE orders.id = ?
");

$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("❌ Заказ не найден!");
}

// Загружаем товары в заказе
$stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
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

<p><strong>Покупатель:</strong> <?= htmlspecialchars($order['user_name']); ?> (<?= htmlspecialchars($order['email']); ?>)</p>

<p><strong>Сумма:</strong> <?= number_format($order['total_price'], 2, '.', ''); ?> ₽</p>
<p><strong>Статус:</strong> <?= htmlspecialchars($order['status']); ?></p>
<p><strong>Дата:</strong> <?= $order['created_at']; ?></p>

<h3>Товары в заказе</h3>

<?php if (!empty($items)): ?>
    <table class="table">
        <tr>
            <th>Название</th>
            <th>Количество</th>
            <th>Цена</th>
            <th>Сумма</th>
        </tr>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']); ?></td> <!-- Исправлено: name вместо product_name -->
                <td><?= htmlspecialchars($item['quantity']); ?></td>
                <td><?= number_format($item['price'], 2, '.', ''); ?> ₽</td>
                <td><?= number_format($item['price'] * $item['quantity'], 2, '.', ''); ?> ₽</td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>❌ В этом заказе нет товаров.</p>
<?php endif; ?>

<a href="orders.php" class="back-button">⬅ Назад</a>

</body>
</html>
