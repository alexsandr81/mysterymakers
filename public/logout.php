<?php
session_start();

// Сбрасываем только пользовательские ключи
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['cart']);

// Перенаправляем на страницу логина
header("Location: login.php");
exit();
?>