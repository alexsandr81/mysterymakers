<?php
session_start();
$id = $_POST['id'] ?? 0;

if (isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}

echo json_encode(['status' => 'success']);
?>
