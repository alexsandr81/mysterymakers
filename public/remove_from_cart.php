<?php
session_start();
include '../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_POST['id'], $_POST['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Неверный запрос']);
    exit;
}

$product_id = (int)$_POST['id'];
unset($_SESSION['cart'][$product_id]);
echo json_encode(['status' => 'success']);
?>