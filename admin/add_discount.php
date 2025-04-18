<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php?error=access_denied");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_id = $_POST['product_id'] ?: null;
    $category_id = $_POST['category_id'] ?: null;
    $discount_type = $_POST['discount_type'];
    $discount_value = floatval($_POST['discount_value']);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;

    if (!$product_id && !$category_id) {
        die("Ошибка: нужно выбрать либо товар, либо категорию!");
    }

    // Запрет дублирования скидок для товаров
    if ($product_id) {
        $check_stmt = $conn->prepare("SELECT id FROM discounts WHERE product_id = ? LIMIT 1");
        $check_stmt->execute([$product_id]);
        if ($check_stmt->rowCount() > 0) {
            die("Ошибка: Скидка для этого товара уже существует!");
        }
    }

    // Запрет дублирования скидок для категорий
    if ($category_id) {
        $check_stmt = $conn->prepare("SELECT id FROM discounts WHERE category_id = ? AND product_id IS NULL LIMIT 1");
        $check_stmt->execute([$category_id]);
        if ($check_stmt->rowCount() > 0) {
            die("Ошибка: Скидка для этой категории уже существует!");
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO discounts (product_id, category_id, discount_type, discount_value, start_date, end_date) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$product_id, $category_id, $discount_type, $discount_value, $start_date, $end_date]);

    header("Location: discounts.php");
    exit();
}
?>
