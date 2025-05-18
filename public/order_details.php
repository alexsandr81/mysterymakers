<?php
include 'header.php';
require_once '../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: account.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Проверяем, принадлежит ли заказ пользователю
$stmt = $conn->prepare("SELECT order_number, total_price, status, created_at FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    header("Location: account.php");
    exit();
}

// Получаем элементы заказа
$stmt = $conn->prepare("
    SELECT oi.product_id, oi.quantity, oi.price, p.name AS product_name, p.images, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main>
    <style>
        h1, h2 {
            margin-bottom: 20px;
        }
        .order-info {
            margin-bottom: 20px;
        }
        .order-info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
        table img {
            max-width: 50px;
            height: auto;
            vertical-align: middle;
        }
        .back-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 3px;
        }
        .back-link:hover {
            background-color: #0056b3;
        }
        .product-link {
            text-decoration: none;
            color: #007bff;
        }
        .product-link:hover {
            text-decoration: underline;
        }
    </style>

    <h1>Детали заказа #<?= htmlspecialchars($order['order_number']); ?></h1>
    <div class="order-info">
        <p><strong>Сумма:</strong> <?= htmlspecialchars($order['total_price']); ?> грн</p>
        <p><strong>Статус:</strong> <?= htmlspecialchars($order['status']); ?></p>
        <p><strong>Дата:</strong> <?= htmlspecialchars($order['created_at']); ?></p>
    </div>

    <h2>Товары</h2>
    <table>
        <tr>
            <th>Фото</th>
            <th>Товар</th>
            <th>Количество</th>
            <th>Цена за единицу</th>
            <th>Общая стоимость</th>
        </tr>
        <?php foreach ($items as $item): ?>
            <?php
            $images = json_decode($item['images'], true);
            $main_image = !empty($images) ? "/mysterymakers/" . $images[0] : ($item['image'] ? "/mysterymakers/" . $item['image'] : "/mysterymakers/public/assets/default.jpg");
            ?>
            <tr>
                <td>
                    <a href="product.php?id=<?= htmlspecialchars($item['product_id']); ?>" class="product-link">
                        <img src="<?= htmlspecialchars($main_image); ?>" alt="<?= htmlspecialchars($item['product_name']); ?>">
                    </a>
                </td>
                <td>
                    <a href="product.php?id=<?= htmlspecialchars($item['product_id']); ?>" class="product-link">
                        <?= htmlspecialchars($item['product_name']); ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($item['quantity']); ?></td>
                <td><?= htmlspecialchars($item['price']); ?> грн</td>
                <td><?= htmlspecialchars($item['quantity'] * $item['price']); ?> грн</td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p><a href="account.php" class="back-link">Вернуться в личный кабинет</a></p>
</main>

<?php include 'footer.php'; ?>  