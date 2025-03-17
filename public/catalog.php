<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>

<main>
    <h1>Каталог товаров</h1>

    <div class="filters">
        <form method="GET">
            <select name="category">
                <option value="">Все категории</option>
                <option value="Одежда">Одежда</option>
                <option value="Аксессуары">Аксессуары</option>
                <option value="Канцелярия">Канцелярия</option>
            </select>
            <select name="sort">
                <option value="price_asc">Сначала дешевые</option>
                <option value="price_desc">Сначала дорогие</option>
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

        $stmt = $pdo->prepare($query);

        if ($category) {
            $stmt->bindParam(':category', $category);
        }

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product): ?>
            <div class="product">
                <img src="/mysterymakers/assets/<?= $product['image']; ?>" alt="<?= $product['name']; ?>">
                <h2><?= $product['name']; ?></h2>
                <p><?= number_format($product['price'], 2, '.', ''); ?> ₽</p>
                <a href="product.php?id=<?= $product['id']; ?>">Подробнее</a>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
