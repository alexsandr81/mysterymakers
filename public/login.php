<?php include 'header.php'; ?>

<main>
    <h1>Вход</h1>
    <form action="login_process.php" method="POST">
        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Пароль:</label>
        <input type="password" name="password" required>

        <button type="submit">Войти</button>
    </form>
</main>

<?php include 'footer.php'; ?>
