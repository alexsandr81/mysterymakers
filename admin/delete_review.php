<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header("Location: reviews.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
$stmt->execute([$id]);

header("Location: reviews.php");
exit();
?>
