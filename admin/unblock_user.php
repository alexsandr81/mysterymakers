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

$stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
$stmt->execute([$id]);

header("Location: users.php");
exit();
?>
