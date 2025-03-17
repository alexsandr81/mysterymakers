<?php 
include 'header.php'; 
include '../database/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');

// Подготовленный запрос на получение заказов пользователя
$stmt = $pdo->prepare("SELECT id, total_price, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <h1>Личный кабинет</h1>
    <p>Добро пожаловать, <?= $user_name; ?>!</p>

    <h2>История заказов</h2>

    <?php if (!empty($orders)): ?>
        <table class="order-history">
            <thead>
                <tr>
                    <th>Номер заказа</th>
                    <th>Сумма</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($order['id']); ?></td>
                        <td><?= number_format($order['total_price'], 2, '.', ''); ?> ₽</td>
                        <td><?= date("d.m.Y H:i", strtotime($order['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Вы ещё не совершали покупок.</p>
    <?php endif; ?>

    <a href="logout.php" class="logout-btn">Выйти</a>
</main>

<?php include 'footer.php'; ?>
