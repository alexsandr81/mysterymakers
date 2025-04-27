<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем заказы
$stmt = $conn->prepare("SELECT id, order_number, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История заказов - MysteryMakers</title>
    <link rel="stylesheet" href="/mysterymakers/assets/css/styles.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <h1>История заказов</h1>

    <!-- Навигация по разделам -->
    <nav class="account-nav">
        <a href="account.php" class="account-nav__link">Профиль</a>
        <a href="favorites.php" class="account-nav__link">Избранное</a>
        <a href="history.php" class="account-nav__link active">Заказы</a>
    </nav>

    <?php if (empty($orders)): ?>
        <p>У вас пока нет заказов.</p>
        <p><a href="catalog.php">Перейти в каталог</a></p>
    <?php else: ?>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Номер заказа</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_number']); ?></td>
                        <td><?= number_format($order['total_price'], 2, '.', ''); ?> ₽</td>
                        <td><?= htmlspecialchars($order['status']); ?></td>
                        <td><?= htmlspecialchars($order['created_at']); ?></td>
                        <td><a href="order_details.php?id=<?= $order['id']; ?>" class="action-link">Подробности</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

</body>
</html>