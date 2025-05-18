<?php
session_start();
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Фильтрация ввода
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $total_price = floatval($_POST['total_price']); // Итоговая сумма с учётом скидки из формы

    // Проверка корзины
    if (empty($_SESSION['cart'])) {
        die("Ошибка: Корзина пуста!");
    }

    // Генерация уникального номера заказа
    $order_number = 'MM' . time();

    // Создаем заказ
    $stmt = $conn->prepare("INSERT INTO orders (order_number, user_name, phone, email, address, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$order_number, $name, $phone, $email, $address, $total_price])) {
        $order_id = $conn->lastInsertId();

        // Добавляем товары в заказ с учётом скидок
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            // Получаем цену и скидку для товара
            $stmt = $conn->prepare("
                SELECT p.price, p.category_id,
                       COALESCE(
                           (SELECT MAX(d.discount_value) FROM discounts d 
                            WHERE d.product_id = p.id 
                              AND (d.start_date IS NULL OR d.start_date <= NOW()) 
                              AND (d.end_date IS NULL OR d.end_date >= NOW())),
                           (SELECT MAX(d.discount_value) FROM discounts d 
                            WHERE d.category_id = p.category_id 
                              AND (d.start_date IS NULL OR d.start_date <= NOW()) 
                              AND (d.end_date IS NULL OR d.end_date >= NOW()))
                       ) AS discount_value,
                       (SELECT d.discount_type FROM discounts d 
                        WHERE (d.product_id = p.id OR d.category_id = p.category_id) 
                          AND (d.start_date IS NULL OR d.start_date <= NOW()) 
                          AND (d.end_date IS NULL OR d.end_date >= NOW())
                        ORDER BY d.discount_value DESC LIMIT 1) AS discount_type
                FROM products p 
                WHERE p.id = ?
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $original_price = $product['price'];
                $discount_value = $product['discount_value'] ?? 0;
                $final_price = $original_price;

                if ($discount_value) {
                    if ($product['discount_type'] == 'fixed') {
                        $final_price = max(0, $original_price - $discount_value);
                    } elseif ($product['discount_type'] == 'percentage') {
                        $final_price = $original_price * (1 - $discount_value / 100);
                    }
                }

                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $product_id, $quantity, $final_price]); // Сохраняем цену с учётом скидки
            }
        }

        // Очищаем корзину
        $_SESSION['cart'] = [];

        // Перенаправляем на страницу "Спасибо за заказ!" с номером заказа
        header("Location: thank_you.php?order_number=" . urlencode($order_number));
        exit();
    } else {
        die("Ошибка при создании заказа!");
    }
}
?>