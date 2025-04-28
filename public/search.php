<?php
session_start();
require_once '../database/db.php';

$query = trim($_GET['q'] ?? '');
$products = [];

if ($query) {
    // Преобразуем запрос: заменяем пробелы и дефисы на универсальный шаблон
    $search_term = '%' . str_replace([' ', '-'], '%', $query) . '%';
    $stmt = $conn->prepare("
    SELECT p.*, 
           COALESCE(
               (SELECT MAX(d.discount_value) FROM discounts d 
                WHERE d.product_id = p.id 
                  AND (d.start_date IS NULL OR d.start_date <= NOW()) 
                  AND (d.end_date IS NULL OR d.end_date >= NOW())), 
               (SELECT MAX(d.discount_value) FROM discounts d 
                WHERE d.category_id = p.category_id 
                  AND (d.start_date IS NULL OR d.start_date <= NOW()) 
                  AND (d.end_date IS NULL OR d.end_date >= NOW()))
           ) AS discount_value,
           (SELECT d.discount_type FROM discounts d 
            WHERE (d.product_id = p.id OR d.category_id = p.category_id) 
              AND (d.start_date IS NULL OR d.start_date <= NOW()) 
              AND (d.end_date IS NULL OR d.end_date >= NOW())
            ORDER BY d.discount_value DESC LIMIT 1) AS discount_type,
           (SELECT d.end_date FROM discounts d 
            WHERE (d.product_id = p.id OR d.category_id = p.category_id) 
              AND (d.start_date IS NULL OR d.start_date <= NOW()) 
              AND (d.end_date IS NULL OR d.end_date >= NOW())
            ORDER BY d.discount_value DESC LIMIT 1) AS end_date
    FROM products p
    WHERE p.status = 1
    ORDER BY p.name ASC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск - MysteryMakers</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <h1>Поиск: <?= htmlspecialchars($query); ?></h1>

    <?php if ($query): ?>
        <?php if (empty($products)): ?>
            <p>Ничего не найдено по запросу "<?= htmlspecialchars($query); ?>".</p>
        <?php else: ?>
            <div class="products">
                <?php foreach ($products as $product): ?>
                    <?php
                    $images = json_decode($product['images'], true);
                    if (!is_array($images)) {
                        $images = [];
                    }
                    $main_image = !empty($images) ? "/mysterymakers/" . $images[0] : "/mysterymakers/public/assets/default.jpg";

                    $original_price = $product['price'];
                    $discount_value = $product['discount_value'] ?? 0;
                    $discount_price = $original_price;

                    if ($discount_value) {
                        if ($product['discount_type'] == 'fixed') {
                            $discount_price = max(0, $original_price - $discount_value);
                        } elseif ($product['discount_type'] == 'percentage') {
                            $discount_price = $original_price * (1 - $discount_value / 100);
                        }
                    }
                    ?>
                    <div class="product">
                        <a href="product.php?id=<?= htmlspecialchars($product['id']); ?>">
                            <img src="<?= htmlspecialchars($main_image); ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                            <h3><?= htmlspecialchars($product['name']); ?></h3>
                            <?php if ($discount_value): ?>
                                <p class="old-price"><s><?= number_format($original_price, 2, '.', ''); ?> грн.</s></p>
                                <p class="discount-price"><?= number_format($discount_price, 2, '.', ''); ?> грн.</p>
                                <p class="discount-info">
                                    Скидка <?= ($product['discount_type'] == 'fixed') ? $product['discount_value'] . ' грн.' : $product['discount_value'] . '%'; ?>
                                    <?php if ($product['end_date']): ?> (до <?= date('d.m.Y H:i', strtotime($product['end_date'])); ?>) <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <p class="price"><?= number_format($original_price, 2, '.', ''); ?> грн.</p>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p>Введите запрос для поиска.</p>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

</body>
</html>