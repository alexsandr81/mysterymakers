<?php include 'header.php'; ?>

<main>
    <h1>Спасибо за заказ!</h1>
    <p>Ваш заказ №<?= $_GET['order_id']; ?> успешно оформлен.</p>
    <a href="catalog.php">Вернуться в каталог</a>
</main>

<?php include 'footer.php'; ?>
