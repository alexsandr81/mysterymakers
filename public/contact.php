<?php
session_start();
// require_once 'database/db.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - MysteryMakers</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<?php include 'header.php'; ?>

<main>
    <h1>Контакты</h1>
    <p>Свяжитесь с нами любым удобным способом:</p>
    <ul>
        <li><strong>Email:</strong> <a href="mailto:info@mysterymakers.ru">info@mysterymakers.ru</a></li>
        <li><strong>Телефон:</strong> +7 (999) 123-45-67</li>
        <li><strong>Адрес:</strong> г. Москва, ул. Примерная, д. 10</li>
    </ul>
    <p>График работы: Пн-Пт с 9:00 до 18:00, Сб-Вс - выходные.</p>
</main>

<?php include 'footer.php'; ?>

</body>
</html>