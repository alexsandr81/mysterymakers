<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("Ошибка: нет доступа");
}

// Запрашиваем все товары
$query = "SELECT p.id, p.name, p.sku, p.stock, p.price, p.created_at, 
                 c.name AS category_name, s.name AS subcategory_name, 
                 sz.name AS size_name, m.name AS material_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN subcategories s ON p.subcategory = s.id
          LEFT JOIN sizes sz ON p.size = sz.id
          LEFT JOIN materials m ON p.material = m.id";

$stmt = $conn->query($query);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Устанавливаем заголовки для скачивания
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="products.csv"');

$output = fopen('php://output', 'w');

// Очищаем буфер перед выводом данных
ob_clean();

// Добавляем BOM (Byte Order Mark) для корректного отображения в Excel
fwrite($output, "\xEF\xBB\xBF");

// Записываем заголовки CSV
fputcsv($output, ['ID', 'Название', 'Категория', 'Подкатегория', 'Размер', 'Материал', 'Артикул', 'Количество', 'Цена', 'Дата добавления'], ';');

// Записываем данные из базы
foreach ($products as $product) {
    fputcsv($output, [
        $product['id'] ?? '',
        $product['name'] ?? '',
        $product['category_name'] ?? 'Нет данных',
        $product['subcategory_name'] ?? 'Нет данных',
        $product['size_name'] ?? 'Нет данных',
        $product['material_name'] ?? 'Нет данных',
        $product['sku'] ?? 'Нет данных',
        $product['stock'] ?? '0',
        $product['price'] ?? '0.00',
        $product['created_at'] ?? ''
    ], ';');
}

fclose($output);
exit();
