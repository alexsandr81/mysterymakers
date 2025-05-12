<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Устанавливаем флаг выхода
$_SESSION['logged_out'] = true;

// Очищаем все переменные сессии
$_SESSION = [];

// Очищаем куки "Запомнить меня" с разными вариантами параметров
if (isset($_COOKIE['user_id'])) {
    setcookie("user_id", "", time() - 3600, "/mysterymakers/", "", false, true); // secure, httponly
    setcookie("user_id", "", time() - 3600, "/mysterymakers/"); // без secure/httponly
    setcookie("user_id", "", time() - 3600, "/"); // корневой путь
}

// Уничтожаем сессию
session_destroy();

// Предотвращаем кэширование
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Перенаправляем на страницу логина
header("Location: /mysterymakers/public/login.php");
exit();
?>