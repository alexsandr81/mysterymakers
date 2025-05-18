<?php
// Конфигурация базы данных
$db_host = "localhost";
$db_user = "root";  // Измените, если нужен другой пользователь
$db_pass = "";      // Пароль, если установлен
$db_name = "mysterymakers_db";  // Замените на свою БД

// Путь к файлу дампа
$backup_file = __DIR__ . "/backup_" . date("Y-m-d_H-i-s") . ".sql";

// Путь к mysqldump
$mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe"; // Укажите ваш путь

// Формирование команды
$command = "\"$mysqldump\" --host=$db_host --user=$db_user --password=$db_pass $db_name > \"$backup_file\"";

// Выполнение
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "Дамп успешно создан: $backup_file";
} else {
    echo "Ошибка при создании дампа!";
}
?>
