<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

include 'header.php';
?>

<main>
    <h1>Оформление заказа</h1>
    
    <form action="checkout.php" method="post">
        <h2>Контактные данные</h2>
        <label>Имя:</label>
        <input type="text" name="name" required><br>
        
        <label>Телефон:</label>
        <input type="text" name="phone" required><br>
        
        <label>Email:</label>
        <input type="email" name="email" required><br>

        <h2>Способ доставки</h2>
        <select name="delivery">
            <option value="Курьер">Курьер</option>
            <option value="Самовывоз">Самовывоз</option>
            <option value="Почта">Почта</option>
        </select><br>

        <h2>Способ оплаты</h2>
        <select name="payment">
            <option value="Наличными">Наличными</option>
            <option value="Картой">Картой</option>
        </select><br>

        <!-- Скрытые поля из cart.php -->
        <input type="hidden" name="total_price" value="<?= $_POST['total_price'] ?? 0; ?>">
        <input type="hidden" name="total_discount" value="<?= $_POST['total_discount'] ?? 0; ?>">

        <button type="submit">Подтвердить заказ</button>
    </form>
</main>

<?php include 'footer.php'; ?>