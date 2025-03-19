<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    exit("Ошибка: нет доступа");
}

$id = $_POST['id'] ?? 0;
$stock = intval($_POST['stock'] ?? 0);

$stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
$stmt->execute([$stock, $id]);

echo "OK";
?>
