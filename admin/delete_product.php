<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;

// Удаляем все записи о товаре в order_items
$stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
$stmt->execute([$id]);

// Теперь удаляем товар из products
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);

header("Location: products.php");
exit();
?>
