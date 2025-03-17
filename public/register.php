<?php include 'header.php'; ?>
<?php include '../database/db.php'; ?>

<main>
    <h1>Регистрация</h1>
    <form action="register_process.php" method="POST">
        <label>Имя:</label>
        <input type="text" name="name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Пароль:</label>
        <input type="password" name="password" required>

        <button type="submit">Зарегистрироваться</button>
    </form>
</main>

<?php include 'footer.php'; ?>
