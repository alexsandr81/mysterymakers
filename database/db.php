<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Загружаем конфиг
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    die("Ошибка: отсутствует файл конфигурации.");
}

$config = include($configPath);

// Проверяем, задано ли имя БД
if (empty($config['db_name'])) {
    die("Ошибка: имя базы данных не задано.");
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
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
