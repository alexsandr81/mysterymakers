<?php
session_start();

// Сбрасываем только админские ключи
unset($_SESSION['admin_id']);
unset($_SESSION['role']);

// Перенаправляем на страницу логина админа
header("Location: login.php");
exit();
?>