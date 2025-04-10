<?php 
include 'header.php'; 
require_once '../database/db.php'; // Проверяем, что путь верный!

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

// Выводим user_id для отладки
// echo "<p>Ваш user_id: " . $user_id . "</p>";

// Проверяем, что подключение к БД работает
if (!$conn) {
    die("Ошибка подключения к базе данных.");
}

// Проверяем, есть ли заказы у текущего пользователя
$stmt = $conn->prepare("SELECT order_number, total_price, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <h1>Личный кабинет</h1>
    <p>Добро пожаловать, <?= $user_name; ?>!</p>

    <h2>История заказов</h2>

    <table border="1">
        <tr>
            <th>Номер заказа</th>
            <th>Сумма</th>
            <th>Статус</th>
            <th>Дата</th>
        </tr>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_number']); ?></td>
                    <td><?= htmlspecialchars($order['total_price']); ?> грн</td>
                    <td><?= htmlspecialchars($order['status']); ?></td>
                    <td><?= htmlspecialchars($order['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">У вас пока нет заказов.</td></tr>
        <?php endif; ?>
    </table>

    
</main>

<?php include 'footer.php'; ?>
