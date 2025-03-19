<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$id]);

header("Location: categories.php");
exit();
?>
