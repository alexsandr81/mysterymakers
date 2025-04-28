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

$id = $_GET['id'] ?? 0;

try {
    // Начать транзакцию
    $conn->beginTransaction();

    // Удалить связанные записи из favorites
    $stmt = $conn->prepare("DELETE FROM favorites WHERE product_id = ?");
    $stmt->execute([$id]);

    // Удалить изображения товара
    $stmt = $conn->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product && $product['images']) {
        $images = json_decode($product['images'], true);
        foreach ($images as $img) {
            if (file_exists("../" . $img)) {
                unlink("../" . $img);
            }
        }
    }

    // Удалить товар
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    // Зафиксировать транзакцию
    $conn->commit();

    // Логирование
    require_once 'log_helper.php';
    log_admin_action($_SESSION['admin_id'], "Удалил товар ID: $id");

    header("Location: products.php?success=Товар успешно удалён");
    exit();
} catch (Exception $e) {
    // Откатить транзакцию в случае ошибки
    $conn->rollBack();
    header("Location: products.php?error=Ошибка удаления: " . htmlspecialchars($e->getMessage()));
    exit();
}