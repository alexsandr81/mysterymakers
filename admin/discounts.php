<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Получаем товары и категории
$products = $conn->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$discounts = $conn->query("SELECT d.*, p.name AS product_name, c.name AS category_name 
                           FROM discounts d
                           LEFT JOIN products p ON d.product_id = p.id
                           LEFT JOIN categories c ON d.category_id = c.id
                           ORDER BY d.end_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление скидками</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Скидки и акции</h2>

<form method="POST" action="add_discount.php">
    <label>Товар:</label>
    <select name="product_id">
        <option value="">Выберите товар (необязательно)</option>
        <?php foreach ($products as $p): ?>
            <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <label>Категория:</label>
    <select name="category_id">
        <option value="">Выберите категорию (необязательно)</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <label>Тип скидки:</label>
    <select name="discount_type" required>
        <option value="fixed">Фиксированная (₽)</option>
        <option value="percentage">Процентная (%)</option>
    </select>

    <label>Размер скидки:</label>
    <input type="number" step="0.01" name="discount_value" required>

    <label>Дата начала:</label>
    <input type="datetime-local" name="start_date">

    <label>Дата окончания:</label>
    <input type="datetime-local" name="end_date">

    <button type="submit">Добавить скидку</button>
</form>


<table border="1">
    <tr>
        <th>ID</th>
        <th>Товар / Категория</th>
        <th>Тип скидки</th>

        <th>Срок действия</th>
        <th>Действия</th>
    </tr>

    <?php foreach ($discounts as $d): ?>
    <tr>
        <td><?= $d['id']; ?></td>
        <td><?= $d['product_name'] ?? $d['category_name'] ?? 'Все товары'; ?></td>
        <td>
    <?php if ($d['discount_type'] == 'fixed'): ?>
        💵 Скидка: <?= "₽ " . number_format($d['discount_value'], 2, '.', ''); ?>
    <?php elseif ($d['discount_type'] == 'percentage'): ?>
        📉 Скидка: <?= $d['discount_value'] . "%"; ?>
    <?php else: ?>
        ❌ Ошибка: нет данных
    <?php endif; ?>
</td>

        <td><?= $d['start_date'] ? $d['start_date'] . ' - ' . $d['end_date'] : 'Бессрочная'; ?></td>
        <td>
        <a href="edit_discount.php?id=<?= $d['id']; ?>">✏ Редактировать</a> | 
    <a href="delete_discount.php?id=<?= $d['id']; ?>" onclick="return confirm('Удалить скидку?');">🗑 Удалить</a>   </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
