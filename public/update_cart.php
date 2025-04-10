<?php
session_start();
include '../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_POST['id'], $_POST['quantity'], $_POST['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Неверный запрос']);
    exit;
}

$product_id = (int)$_POST['id'];
$quantity = (int)$_POST['quantity'];

if ($quantity < 1 || $quantity > 99) {
    echo json_encode(['status' => 'error', 'message' => 'Недопустимое количество']);
    exit;
}

$_SESSION['cart'][$product_id] = $quantity;
echo json_encode(['status' => 'success']);
?>