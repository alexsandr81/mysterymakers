<?php
session_start();

// Логирование
file_put_contents('logout_log.txt', date('Y-m-d H:i:s') . " - Начало logout.php, session before: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Сохраняем админские данные
$preserve_keys = [];
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'admin_') === 0 || in_array($key, ['admin_id', 'role', 'admin_role'])) {
        $preserve_keys[$key] = $value;
    }
}

// Очищаем сессию и восстанавливаем админские данные
$_SESSION = $preserve_keys;
$_SESSION['logged_out'] = true;

file_put_contents('logout_log.txt', date('Y-m-d H:i:s') . " - После очистки, session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Перенаправляем
header("Location: login.php");
exit();
?>