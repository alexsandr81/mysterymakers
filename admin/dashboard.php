<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Оборот за день, неделю, месяц
$sales_today = $conn->query("SELECT SUM(total_price) AS total FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$sales_week = $conn->query("SELECT SUM(total_price) AS total FROM orders WHERE YEARWEEK(created_at) = YEARWEEK(NOW())")->fetchColumn();
$sales_month = $conn->query("SELECT SUM(total_price) AS total FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();

// Количество новых заказов
$new_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Новый'")->fetchColumn();
$processing_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'В обработке'")->fetchColumn();
$completed_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Выполнен'")->fetchColumn();

// Оставшиеся товары
$low_stock = $conn->query("SELECT COUNT(*) FROM products WHERE stock < 5")->fetchColumn();

// Популярные товары (по количеству продаж)
$popular_products = $conn->query("
    SELECT p.name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// График продаж (обороты за последние 7 дней)
$sales_chart = $conn->query("
    SELECT DATE(created_at) AS date, SUM(total_price) AS total
    FROM orders
    WHERE created_at >= NOW() - INTERVAL 7 DAY
    GROUP BY DATE(created_at)
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Дэшборд</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <h2>📊 Дэшборд</h2>

    <div class="dashboard">
        <div class="stat">
            <h3>💰 Оборот</h3>
            <p>Сегодня: <b><?= number_format($sales_today, 2, '.', ''); ?> ₽</b></p>
            <p>Неделя: <b><?= number_format($sales_week, 2, '.', ''); ?> ₽</b></p>
            <p>Месяц: <b><?= number_format($sales_month, 2, '.', ''); ?> ₽</b></p>
        </div>

        <div class="stat">
            <h3>📦 Заказы</h3>
            <p>Новые: <b><?= $new_orders; ?></b></p>
            <p>В обработке: <b><?= $processing_orders; ?></b></p>
            <p>Выполненные: <b><?= $completed_orders; ?></b></p>
        </div>

        <div class="stat">
            <h3>⚠️ Остатки товаров</h3>
            <p>Товаров с низким остатком: <b><?= $low_stock; ?></b></p>
        </div>
    </div>

    <h3>🔥 Популярные товары</h3>
    <table border="1">
        <tr>
            <th>Товар</th>
            <th>Продано</th>
        </tr>
        <?php foreach ($popular_products as $product): ?>
            <tr>
                <td><?= htmlspecialchars($product['name']); ?></td>
                <td><?= $product['total_sold']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>📈 График продаж за последние 7 дней</h3>
    <canvas id="salesChart" width="400" height="200"></canvas>

    <script>
        const salesData = <?= json_encode(array_column($sales_chart, 'total')); ?>;
        const salesLabels = <?= json_encode(array_column($sales_chart, 'date')); ?>;

        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'Оборот (₽)',
                    data: salesData,
                    borderColor: 'blue',
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>

</body>

</html>