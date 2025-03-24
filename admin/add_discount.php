<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id']) || !isset($_POST['discount_type'], $_POST['discount_value'])) {
    header("Location: discounts.php");
    exit();
}

$product_id = $_POST['product_id'] ?: null;
$category_id = $_POST['category_id'] ?: null;
$discount_type = $_POST['discount_type'];
$discount_value = floatval($_POST['discount_value']);
$start_date = $_POST['start_date'] ?: null;
$end_date = $_POST['end_date'] ?: null;

$stmt = $conn->prepare("INSERT INTO discounts (product_id, category_id, discount_type, discount_value, start_date, end_date) 
                        VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$product_id, $category_id, $discount_type, $discount_value, $start_date, $end_date]);

header("Location: discounts.php");
exit();
?>
