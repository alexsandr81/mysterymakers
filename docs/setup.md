# Настройка окружения (XAMPP + PHP + MySQL)

## 1. Установка XAMPP
1. Скачать XAMPP: [https://www.apachefriends.org/ru/index.html](https://www.apachefriends.org/ru/index.html).
2. Установить с компонентами: Apache, MySQL, PHP, phpMyAdmin.
3. Папка установки: `C:\xampp`.

## 2. Запуск сервера
1. Открыть `C:\xampp\xampp-control.exe`.
2. Запустить **Apache** и **MySQL** (кнопка `Start`).
3. Проверить доступность: [http://localhost](http://localhost).

## 3. Создание структуры проекта
Созданы папки:
C:\xampp\htdocs\mysterymakers
├── public\ (файлы сайта) ├── assets\ (CSS, изображения) ├── config\ (настройки) ├── database\ (подключение к БД) ├── docs\ (документация)

## 4. Создание базы данных
1. Открыть [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Выполнить SQL-запрос:
```sql
CREATE DATABASE mysterymakers_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
