<?php
session_start();
require_once '../database/db.php';

$errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];

unset($_SESSION['form_errors'], $_SESSION['form_data']);

include 'header.php';
?>

<main>
    <h1>Регистрация</h1>
    <form action="register_process.php" method="POST" id="registerForm" novalidate>
        <div class="form-group">
            <label for="name">Имя:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($form_data['name'] ?? '') ?>" required>
            <?php if (isset($errors['name'])): ?>
                <span class="error"><?= htmlspecialchars($errors['name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required>
            <?php if (isset($errors['email'])): ?>
                <span class="error"><?= htmlspecialchars($errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Пароль:</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password" required>
                <span class="toggle-password" data-field="password" aria-label="Показать/скрыть пароль">👁️</span>
            </div>
            <?php if (isset($errors['password'])): ?>
                <span class="error"><?= htmlspecialchars($errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password_confirm">Повторите пароль:</label>
            <div class="password-wrapper">
                <input type="password" id="password_confirm" name="password_confirm" required>
                <span class="toggle-password" data-field="password_confirm" aria-label="Показать/скрыть пароль">👁️</span>
            </div>
            <?php if (isset($errors['password_confirm'])): ?>
                <span class="error"><?= htmlspecialchars($errors['password_confirm']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit">Зарегистрироваться</button>
    </form>
</main>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = document.querySelector(`.toggle-password[data-field="${fieldId}"]`);
    if (!field || !toggle) return; // Защита от ошибок
    if (field.type === 'password') {
        field.type = 'text';
        toggle.textContent = '🙈';
    } else {
        field.type = 'password';
        toggle.textContent = '👁️';
    }
}

// Инициализация: удаляем дублирующиеся иконки, если они есть
document.querySelectorAll('.toggle-password').forEach(toggle => {
    const fieldId = toggle.getAttribute('data-field');
    const duplicates = document.querySelectorAll(`.toggle-password[data-field="${fieldId}"]`);
    if (duplicates.length > 1) {
        for (let i = 1; i < duplicates.length; i++) {
            duplicates[i].remove();
        }
    }
    toggle.onclick = () => togglePassword(fieldId);
});

document.getElementById('registerForm').addEventListener('submit', function(e) {
    let valid = true;
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    document.querySelectorAll('.error').forEach(el => el.remove());

    if (name.length === 0) {
        valid = false;
        const error = document.createElement('span');
        error.className = 'error';
        error.textContent = 'Имя обязательно';
        document.getElementById('name').after(error);
    }

    if (!emailRegex.test(email)) {
        valid = false;
        const error = document.createElement('span');
        error.className = 'error';
        error.textContent = 'Введите корректный email';
        document.getElementById('email').after(error);
    }

    if (password.length < 6) {
        valid = false;
        const error = document.createElement('span');
        error.className = 'error';
        error.textContent = 'Пароль должен быть не короче 6 символов';
        document.getElementById('password').after(error);
    }

    if (password !== passwordConfirm) {
        valid = false;
        const error = document.createElement('span');
        error.className = 'error';
        error.textContent = 'Пароли не совпадают';
        document.getElementById('password_confirm').after(error);
    }

    if (!valid) {
        e.preventDefault();
    }
});
</script>

<style>
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; }
.form-group input {
    width: 100%;
    padding: 8px 40px 8px 8px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
    height: 36px;
    line-height: 1.5; /* Устраняем влияние line-height */
}
.password-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
    vertical-align: top; /* Предотвращаем смещение */
}
.toggle-password {
    position: absolute;
    right: 10px;
    top: 18px; /* Фиксируем позицию относительно верха input */
    transform: none; /* Убираем transform для стабильности */
    cursor: pointer;
    font-size: 16px;
    z-index: 1;
    line-height: 1;
}
.error {
    color: red;
    font-size: 0.9em;
    display: block;
    margin-top: 5px;
}
</style>

<?php include 'footer.php'; ?>