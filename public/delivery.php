<?php
session_start();
// require_once 'database/db.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доставка - MysteryMakers</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <h1>Доставка</h1>
    <p>Мы доставляем заказы по всей России удобным для вас способом:</p>
    <ul>
        <li><strong>Курьерская доставка</strong> - от 300 ₽, сроки: 1-3 дня.</li>
        <li><strong>Почта России</strong> - от 200 ₽, сроки: 5-14 дней.</li>
        <li><strong>Самовывоз</strong> - бесплатно, по адресу: г. Москва, ул. Примерная, д. 10.</li>
    </ul>
    <p>Стоимость и сроки зависят от региона. Точную информацию уточняйте при оформлении заказа.</p>
</main>

<?php include 'footer.php'; ?>

</body>
</html>