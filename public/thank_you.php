<?php include 'header.php'; ?>

<main>
    <h1>Спасибо за заказ!</h1>

    <?php
    // Проверяем, передан ли номер заказа в GET-параметрах
    $order_number = $_GET['order_number'] ?? null;

    if ($order_number) {
        echo "<p>Ваш заказ №{$order_number} успешно оформлен.</p>";
    } else {
        echo "<p>Ошибка: номер заказа не найден.</p>";
    }
    ?>

    <a href="catalog.php">Вернуться в каталог</a>
</main>

<?php include 'footer.php'; ?>
