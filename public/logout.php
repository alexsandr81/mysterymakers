<?php
session_start();

// Сбрасываем только пользовательские ключи
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['cart']);

// Очищаем куки "Запомнить меня"
setcookie("user_id", "", time() - 3600, "/mysterymakers/");

// Перенаправляем на страницу логина
header("Location: login.php");
exit();
?>