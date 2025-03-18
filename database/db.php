<?php

// Проверяем, запущена ли сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Загружаем конфигурацию
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    die("❌ Ошибка: отсутствует файл конфигурации '$configPath'.");
}

$config = include $configPath;

// Проверяем, что конфиг содержит все нужные параметры
if (!isset($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass'])) {
    die("❌ Ошибка: Некорректная конфигурация базы данных.");
}

try {
    // Создаём подключение к БД
    $conn = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("❌ Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
