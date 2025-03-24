<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header("Location: discounts.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM discounts WHERE id = ?");
$stmt->execute([$id]);
$discount = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$discount) {
    die("Скидка не найдена!");
}

// Получаем товары и категории
$products = $conn->query("SELECT * FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_id = $_POST['product_id'] ?: null;
    $category_id = $_POST['category_id'] ?: null;
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;

    $stmt = $conn->prepare("UPDATE discounts 
                            SET product_id = ?, category_id = ?, discount_type = ?, 
                                discount_value = ?, start_date = ?, end_date = ? 
                            WHERE id = ?");
    $stmt->execute([$product_id, $category_id, $discount_type, $discount_value, $start_date, $end_date, $id]);

    header("Location: discounts.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать скидку</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h2>Редактировать скидку</h2>

<form method="POST">
    <label>Товар:</label>
    <select name="product_id">
        <option value="">Выберите товар</option>
        <?php foreach ($products as $p): ?>
            <option value="<?= $p['id']; ?>" <?= ($discount['product_id'] == $p['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($p['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Категория:</label>
    <select name="category_id">
        <option value="">Выберите категорию</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id']; ?>" <?= ($discount['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($c['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Тип скидки:</label>
    <select name="discount_type" required>
        <option value="fixed" <?= ($discount['discount_type'] == 'fixed') ? 'selected' : ''; ?>>Фиксированная (₽)</option>
        <option value="percentage" <?= ($discount['discount_type'] == 'percentage') ? 'selected' : ''; ?>>Процентная (%)</option>
    </select>

    <label>Размер скидки:</label>
    <input type="number" step="0.01" name="discount_value" value="<?= $discount['discount_value']; ?>" required>

    <label>Дата начала:</label>
    <input type="datetime-local" name="start_date" value="<?= $discount['start_date']; ?>">

    <label>Дата окончания:</label>
    <input type="datetime-local" name="end_date" value="<?= $discount['end_date']; ?>">

    <button type="submit">💾 Сохранить изменения</button>
</form>

</body>
</html>
