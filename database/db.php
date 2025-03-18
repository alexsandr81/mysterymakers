<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Загружаем конфиг
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    die("❌ Ошибка: отсутствует файл конфигурации '$configPath'.");
}

$config = include $configPath;

// Проверяем, что конфиг загружен корректно и содержит нужные параметры
if (!isset($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass'])) {
    die("❌ Ошибка: Конфигурация базы данных некорректна.");
}

try {
    $pdo = new PDO(
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
