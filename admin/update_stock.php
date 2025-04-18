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

$id = $_POST['id'] ?? 0;
$stock = intval($_POST['stock'] ?? 0);

$stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
$stmt->execute([$stock, $id]);

echo "OK";
?>
