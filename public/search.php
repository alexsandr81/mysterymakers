<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

// Генерация CSRF-токена
$csrf_token = generateCsrfToken();

$query = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

$products = [];
$total_products = 0;
$errors = [];

// Проверка CSRF-токена
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['csrf_token']) && !verifyCsrfToken($_GET['csrf_token'])) {
    $errors[] = "Недействительный запрос.";
}

// Формирование SQL-запроса
$sql = "
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
            ORDER BY d.discount_value DESC LIMIT 1) AS end_date,
           AVG(r.rating) AS avg_rating
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    WHERE p.status = 1
";
$params = [];

if ($query) {
    $search_term = '%' . str_replace([' ', '-'], '%', $query) . '%';
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ? OR p.seo_keywords LIKE ?)";
    $params = [$search_term, $search_term, $search_term, $search_term];
}

$sql .= " GROUP BY p.id ORDER BY p.name ASC";

// Подсчёт общего количества
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$total_products = $stmt->rowCount();

// Пагинация
$offset = ($page - 1) * $per_page;
$sql .= " LIMIT ? OFFSET ?";

// Получение товаров
$stmt = $conn->prepare($sql);
foreach ($params as $index => $param) {
    $stmt->bindValue($index + 1, $param);
}
$stmt->bindValue(count($params) + 1, $per_page, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_pages = ceil($total_products / $per_page);
?>

<?php include 'header.php'; ?>

<main>
    <h1>Поиск товаров</h1>

    <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
            <p class="error"><?= htmlspecialchars($error); ?></p>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($query): ?>
        <?php if (empty($products)): ?>
            <p>Ничего не найдено по запросу "<?= htmlspecialchars($query); ?>".</p>
        <?php else: ?>
            <h3>Результаты поиска (<?= $total_products; ?>)</h3>
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
                        <a href="/mysterymakers/public/product.php?id=<?= htmlspecialchars($product['id']); ?>">
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
                            <?php if ($product['avg_rating']): ?>
                                <p class="rating">Рейтинг: <?= number_format($product['avg_rating'], 1); ?>/5</p>
                            <?php endif; ?>
                            <p class="stock <?= $product['stock'] > 0 ? '' : 'out-of-stock'; ?>">
                                <?= $product['stock'] > 0 ? 'В наличии' : 'Нет в наличии'; ?>
                            </p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Пагинация -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?q=<?= urlencode($query); ?>&page=<?= $page - 1; ?>&csrf_token=<?= htmlspecialchars($csrf_token); ?>">Предыдущая</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?q=<?= urlencode($query); ?>&page=<?= $i; ?>&csrf_token=<?= htmlspecialchars($csrf_token); ?>" <?= $i == $page ? 'class="active"' : ''; ?>><?= $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?q=<?= urlencode($query); ?>&page=<?= $page + 1; ?>&csrf_token=<?= htmlspecialchars($csrf_token); ?>">Следующая</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <p>Введите запрос для поиска.</p>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>