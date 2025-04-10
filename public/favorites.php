<?php
session_start();
require_once '../database/db.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем избранные товары с данными о скидках
$stmt = $conn->prepare("
    SELECT p.*, f.created_at AS added_at,
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
    FROM favorites f
    JOIN products p ON f.product_id = p.id
    WHERE f.user_id = ? AND p.status = 1
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Избранное - MysteryMakers</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <h1>Избранное</h1>

    <?php if (empty($favorites)): ?>
        <p>В вашем избранном пока нет товаров.</p>
        <p><a href="catalog.php">Перейти в каталог</a></p>
    <?php else: ?>
        <div class="products">
            <?php foreach ($favorites as $favorite): ?>
                <?php
                $images = json_decode($favorite['images'], true);
                if (!is_array($images)) {
                    $images = [];
                }
                $main_image = !empty($images) ? "/mysterymakers/" . $images[0] : "/mysterymakers/public/assets/default.jpg";

                $original_price = $favorite['price'];
                $discount_value = $favorite['discount_value'] ?? 0;
                $discount_price = $original_price;

                if ($discount_value) {
                    if ($favorite['discount_type'] == 'fixed') {
                        $discount_price = max(0, $original_price - $discount_value);
                    } elseif ($favorite['discount_type'] == 'percentage') {
                        $discount_price = $original_price * (1 - $discount_value / 100);
                    }
                }
                ?>
                <div class="product">
                    <a href="product.php?id=<?= htmlspecialchars($favorite['id']); ?>">
                        <img src="<?= htmlspecialchars($main_image); ?>" alt="<?= htmlspecialchars($favorite['name']); ?>">
                        <h3><?= htmlspecialchars($favorite['name']); ?></h3>
                        <?php if ($discount_value): ?>
                            <p class="old-price"><s><?= number_format($original_price, 2, '.', ''); ?> ₽</s></p>
                            <p class="discount-price"><?= number_format($discount_price, 2, '.', ''); ?> ₽</p>
                            <p class="discount-info">
                                Скидка <?= ($favorite['discount_type'] == 'fixed') ? $favorite['discount_value'] . ' ₽' : $favorite['discount_value'] . '%'; ?>
                                <?php if ($favorite['end_date']): ?> (до <?= date('d.m.Y H:i', strtotime($favorite['end_date'])); ?>) <?php endif; ?>
                            </p>
                        <?php else: ?>
                            <p class="price"><?= number_format($original_price, 2, '.', ''); ?> ₽</p>
                        <?php endif; ?>
                    </a>
                    <form method="POST" action="remove_from_favorites.php">
                        <input type="hidden" name="product_id" value="<?= $favorite['id']; ?>">
                        <button type="submit" class="remove-btn">Удалить</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>

</body>
</html>