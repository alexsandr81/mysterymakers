<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товаров</title>
    <link rel="stylesheet" href="/mysterymakers/assets/styles.css">
</head>

<body>
<main>
    <h1>Каталог товаров</h1>

    <div class="filters">
        <form method="GET">
            <select name="category">
                <option value="">Все категории</option>
                <option value="Одежда" <?= ($_GET['category'] ?? '') == 'Одежда' ? 'selected' : ''; ?>>Одежда</option>
                <option value="Аксессуары" <?= ($_GET['category'] ?? '') == 'Аксессуары' ? 'selected' : ''; ?>>Аксессуары</option>
                <option value="Канцелярия" <?= ($_GET['category'] ?? '') == 'Канцелярия' ? 'selected' : ''; ?>>Канцелярия</option>
            </select>
            <select name="sort">
                <option value="price_asc" <?= ($_GET['sort'] ?? '') == 'price_asc' ? 'selected' : ''; ?>>Сначала дешевые</option>
                <option value="price_desc" <?= ($_GET['sort'] ?? '') == 'price_desc' ? 'selected' : ''; ?>>Сначала дорогие</option>
            </select>
            <button type="submit">Применить</button>
        </form>
    </div>

    <div class="products">
        <?php
        $category = $_GET['category'] ?? '';
        $sort = $_GET['sort'] ?? '';

        $query = "SELECT * FROM products WHERE 1";

        if ($category) {
            $query .= " AND category = :category";
        }

        if ($sort == 'price_asc') {
            $query .= " ORDER BY price ASC";
        } elseif ($sort == 'price_desc') {
            $query .= " ORDER BY price DESC";
        }

        $stmt = $conn->prepare($query);

        if ($category) {
            $stmt->bindParam(':category', $category);
        }

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product): 
            $images = json_decode($product['images'], true) ?? [];
            $mainImage = !empty($images) ? $images[0] : 'assets/no-image.jpg';
        ?>
            <div class="product">
                <img src="/mysterymakers/<?= $mainImage; ?>" alt="<?= htmlspecialchars($product['name']); ?>">
                <h2><?= htmlspecialchars($product['name']); ?></h2>
                <p><?= number_format($product['price'], 2, '.', ''); ?> ₽</p>
                <a href="product.php?id=<?= $product['id']; ?>">Подробнее</a>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
</body>
</html>