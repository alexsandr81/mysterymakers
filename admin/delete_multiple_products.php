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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['product_ids'])) {
    $ids = $_POST['product_ids'];

    // Удаляем изображения перед удалением товаров
    $stmt = $conn->prepare("SELECT images FROM products WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
    $stmt->execute($ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $images = json_decode($product['images'], true) ?? [];
        foreach ($images as $img) {
            if (file_exists("../" . $img)) {
                unlink("../" . $img);
            }
        }
    }

    // Удаляем товары
    $stmt = $conn->prepare("DELETE FROM products WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
    $stmt->execute($ids);
}

header("Location: products.php");
exit();
?>
