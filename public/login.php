<?php
session_start();
require_once '../database/db.php';
require_once '../includes/security.php';

// Логирование
file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Начало login.php\n", FILE_APPEND);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Заполните все поля';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, phone, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                unset($_SESSION['logged_out']);
                header("Location: index.php");
                exit();
            } else {
                $errors[] = 'Неверный email или пароль';
            }
        } catch (PDOException $e) {
            $errors[] = 'Ошибка базы данных';
            file_put_contents('login_log.txt', date('Y-m-d H:i:s') . " - Ошибка БД: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}

include 'header.php';
?>

<main>
    <h1>Вход</h1>
    <?php if (!empty($errors)): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form id="loginForm" method="POST" novalidate>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Войти</button>
    </form>
</main>

<style>
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; }
.form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
.errors { color: red; margin-bottom: 15px; }
form { max-width: 400px; margin: 0 auto; }
</style>

<?php include 'footer.php'; ?>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    if (!email || !password) {
        e.preventDefault();
        alert('Заполните все поля');
    }
});
</script>